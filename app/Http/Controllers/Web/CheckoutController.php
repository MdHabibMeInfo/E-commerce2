<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\SiteSettings;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

class CheckoutController extends Controller
{
    public function index(Request $request, SiteSettings $settings): View
    {
        $this->ensureStorefrontCustomer($request);

        $cart = $this->resolveCart($request);
        $items = $cart->items()->with([
            'product.primaryImage',
            'product.images',
            'productVariant.attributeValues.productAttribute',
        ])->get();

        foreach ($items as $item) {
            $item->price = $item->productVariant?->price
                ?? $item->product?->price
                ?? $item->price;

            if ($item->productVariant) {
                $item->variant_name = $item->productVariant->displayName();
            }
        }

        $subtotal = $items->sum(fn ($item) => (float) $item->price * (int) $item->quantity);
        $deliveryFee = $items->isNotEmpty() ? 60.00 : 0.00;
        $discount = 0.00;
        $total = $subtotal + $deliveryFee - $discount;

        $paymentAccounts = config('payments.mobile_wallets');
        foreach ($paymentAccounts as $provider => $paymentAccount) {
            $paymentAccounts[$provider]['merchant_number'] = $settings->get(
                $provider.'_merchant_number',
                $paymentAccount['merchant_number'],
            );
        }

        return view('storefront.checkout', compact('cart', 'items', 'subtotal', 'deliveryFee', 'discount', 'total', 'paymentAccounts'));
    }

    public function store(Request $request, SiteSettings $settings): RedirectResponse
    {
        $this->ensureStorefrontCustomer($request);

        $customer = $request->user();
        $phoneRules = ['required', 'string', 'max:20'];

        if ($customer?->account_type === 'customer') {
            $phoneRules[] = Rule::unique('users', 'phone')->ignore($customer->getKey());
        }

        $validated = $request->validate([
            'customer_name' => ['required', 'string', 'max:255'],
            'phone' => $phoneRules,
            'address' => ['required', 'string', 'max:500'],
            'city' => ['required', 'string', 'max:120'],
            'area' => ['nullable', 'string', 'max:120'],
            'payment_method' => ['required', Rule::in(['cod', 'bkash', 'nagad'])],
            'payment_sender_phone' => ['nullable', 'required_if:payment_method,bkash,nagad', 'string', 'max:20'],
            'payment_transaction_id' => [
                'nullable',
                'required_if:payment_method,bkash,nagad',
                'string',
                'max:100',
                Rule::unique('orders', 'payment_transaction_id')->where(
                    fn ($query) => $query->where('payment_method', $request->input('payment_method')),
                ),
            ],
            'payment_proof' => ['nullable', 'required_if:payment_method,bkash,nagad', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $paymentMethod = $validated['payment_method'];

        if ($paymentMethod !== 'cod' && blank($settings->get(
            $paymentMethod.'_merchant_number',
            config("payments.mobile_wallets.{$paymentMethod}.merchant_number"),
        ))) {
            throw ValidationException::withMessages([
                'payment_method' => 'This mobile payment option is not configured yet. Please choose another payment method.',
            ]);
        }

        $cart = $this->resolveCart($request);
        $paymentProofPath = null;
        $paymentProofDisk = config('payments.proof_disk', 'local');

        if ($paymentMethod !== 'cod') {
            $paymentProofPath = $request->file('payment_proof')->store('payment-proofs', $paymentProofDisk);

            if (! $paymentProofPath) {
                throw ValidationException::withMessages([
                    'payment_proof' => 'The payment receipt could not be saved. Please try again.',
                ]);
            }
        }

        try {
            $order = DB::transaction(function () use ($cart, $customer, $request, $validated, $paymentMethod, $paymentProofPath): Order {
                $lockedCart = Cart::query()->lockForUpdate()->findOrFail($cart->id);
                $items = $lockedCart->items()->orderBy('id')->get();

                if ($items->isEmpty()) {
                    throw ValidationException::withMessages([
                        'cart' => 'Your cart is empty. Add at least one product before placing an order.',
                    ]);
                }

                $orderLines = [];
                $subtotal = 0.0;

                foreach ($items as $item) {
                    $product = Product::query()
                        ->lockForUpdate()
                        ->find($item->product_id);

                    if (! $product || ! $product->is_active || $product->is_out_of_stock) {
                        throw ValidationException::withMessages([
                            'cart' => 'One or more products are no longer available.',
                        ]);
                    }

                    $variantName = null;
                    $variantSku = null;
                    $variant = null;

                    if ($product->type === 'variable') {
                        $variant = $item->product_variant_id
                            ? ProductVariant::query()
                                ->where('product_id', $product->id)
                                ->where('is_active', true)
                                ->lockForUpdate()
                                ->find($item->product_variant_id)
                            : null;

                        if (! $variant || $variant->is_out_of_stock) {
                            throw ValidationException::withMessages([
                                'cart' => 'A selected product variant is no longer available.',
                            ]);
                        }

                        $variant->load('attributeValues.productAttribute');
                        $variantName = $variant->displayName();
                        $variantSku = $variant->sku;
                        $price = (float) $variant->price;
                    } else {
                        if ($item->product_variant_id || $product->price === null) {
                            throw ValidationException::withMessages([
                                'cart' => 'One or more cart items are no longer purchasable.',
                            ]);
                        }

                        $price = (float) $product->price;
                    }

                    $item->forceFill([
                        'name' => $product->name,
                        'variant_name' => $variantName,
                        'price' => $price,
                    ]);
                    $subtotal += $price * (int) $item->quantity;

                    $orderLines[] = [
                        'product_id' => $product->id,
                        'product_variant_id' => $variant?->id,
                        'variant_name' => $variantName,
                        'variant_sku' => $variantSku,
                        'name' => $product->name,
                        'price' => $price,
                        'quantity' => $item->quantity,
                    ];
                }

                if ($customer?->account_type === 'customer') {
                    $customer->fill([
                        'name' => $validated['customer_name'],
                        'phone' => $validated['phone'],
                    ])->save();
                }

                $deliveryFee = 60.00;
                $discount = 0.00;
                $total = $subtotal + $deliveryFee - $discount;
                $order = Order::create([
                    'user_id' => $request->user()?->id,
                    'session_id' => $request->session()->getId(),
                    'customer_name' => $validated['customer_name'],
                    'phone' => $validated['phone'],
                    'address' => $validated['address'],
                    'city' => $validated['city'],
                    'area' => $validated['area'] ?? null,
                    'payment_method' => $paymentMethod,
                    'payment_sender_phone' => $validated['payment_sender_phone'] ?? null,
                    'payment_transaction_id' => $validated['payment_transaction_id'] ?? null,
                    'payment_amount' => $paymentMethod === 'cod' ? null : $total,
                    'payment_proof_path' => $paymentProofPath,
                    'notes' => $validated['notes'] ?? null,
                    'subtotal' => $subtotal,
                    'delivery_fee' => $deliveryFee,
                    'discount' => $discount,
                    'total' => $total,
                ]);

                foreach ($orderLines as $orderLine) {
                    $order->items()->create($orderLine);
                }

                $lockedCart->items()->delete();

                return $order;
            });
        } catch (Throwable $exception) {
            if ($paymentProofPath) {
                Storage::disk($paymentProofDisk)->delete($paymentProofPath);
            }

            throw $exception;
        }

        $request->session()->forget('cart_id');

        $successUrl = $customer
            ? route('checkout.success', ['order' => $order->id])
            : URL::temporarySignedRoute('checkout.success', now()->addMinutes(30), ['order' => $order->id]);

        return redirect()->to($successUrl)->with('success', 'Order placed successfully.');
    }

    public function success(Request $request, Order $order): View
    {
        $userId = $request->user()?->getAuthIdentifier();
        $isAuthenticatedOwner = $userId !== null && (string) $order->user_id === (string) $userId;
        $isOriginatingGuest = $userId === null
            && $order->user_id === null
            && (hash_equals((string) $order->session_id, (string) $request->session()->getId()) || $request->hasValidSignature());

        abort_unless($isAuthenticatedOwner || $isOriginatingGuest, 404);

        return view('storefront.checkout-success', compact('order'));
    }

    protected function resolveCart(Request $request): Cart
    {
        $cartId = $request->session()->get('cart_id');

        if ($cartId) {
            $cartQuery = Cart::query()->whereKey($cartId);

            if ($request->user()) {
                $cartQuery->where(function ($query) use ($request): void {
                    $query->where('user_id', $request->user()->id)
                        ->orWhereNull('user_id');
                });
            } else {
                $cartQuery->whereNull('user_id');
            }

            $cart = $cartQuery->first();

            if ($cart) {
                $request->session()->put('cart_id', $cart->id);

                return $cart;
            }
        }

        $cart = Cart::query()->firstOrCreate([
            'session_id' => $request->session()->getId(),
            'user_id' => $request->user()?->id,
        ]);

        $request->session()->put('cart_id', $cart->id);

        return $cart;
    }

    private function ensureStorefrontCustomer(Request $request): void
    {
        abort_if(
            $request->user()?->account_type === 'admin',
            403,
            'Management accounts cannot place customer orders.',
        );
    }
}
