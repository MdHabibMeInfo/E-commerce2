<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CheckoutController extends Controller
{
    public function index(Request $request): View
    {
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

        return view('storefront.checkout', compact('cart', 'items', 'subtotal', 'deliveryFee', 'discount', 'total'));
    }

    public function store(Request $request): RedirectResponse
    {
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
            'payment_method' => ['required', 'in:cod,online'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $cart = $this->resolveCart($request);

        $order = DB::transaction(function () use ($cart, $customer, $request, $validated): Order {
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

                    if (! $variant || $variant->stock < $item->quantity) {
                        throw ValidationException::withMessages([
                            'cart' => 'A selected product variant is no longer available in the requested quantity.',
                        ]);
                    }

                    $variant->load('attributeValues.productAttribute');
                    $variantName = $variant->displayName();
                    $variantSku = $variant->sku;
                    $price = (float) $variant->price;
                    $variant->decrement('stock', $item->quantity);
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
            $order = Order::create([
                'user_id' => $request->user()?->id,
                'session_id' => $request->session()->getId(),
                'customer_name' => $validated['customer_name'],
                'phone' => $validated['phone'],
                'address' => $validated['address'],
                'city' => $validated['city'],
                'area' => $validated['area'] ?? null,
                'payment_method' => $validated['payment_method'],
                'notes' => $validated['notes'] ?? null,
                'subtotal' => $subtotal,
                'delivery_fee' => $deliveryFee,
                'discount' => $discount,
                'total' => $subtotal + $deliveryFee - $discount,
            ]);

            foreach ($orderLines as $orderLine) {
                $order->items()->create($orderLine);
            }

            $lockedCart->items()->delete();

            return $order;
        });

        $request->session()->forget('cart_id');

        return redirect()->route('checkout.success', ['order' => $order->id])->with('success', 'Order placed successfully.');
    }

    public function success(Order $order): View
    {
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
}
