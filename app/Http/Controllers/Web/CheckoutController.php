<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Jobs\SendMetaPurchaseEvent;
use App\Models\Cart;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\CouponService;
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
    public function index(Request $request, SiteSettings $settings, CouponService $coupons): View
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
        $legacyDeliveryFee = $settings->get('delivery_fee');
        $deliveryFeeInsideDhakaDefault = $legacyDeliveryFee
            ?? config('storefront.delivery_fee_inside_dhaka', config('storefront.delivery_fee', 60.00));
        $deliveryFeeOutsideDhakaDefault = $legacyDeliveryFee
            ?? config('storefront.delivery_fee_outside_dhaka', config('storefront.delivery_fee', 60.00));
        $deliveryFeeInsideDhaka = $items->isNotEmpty()
            ? (float) $settings->get('delivery_fee_inside_dhaka', $deliveryFeeInsideDhakaDefault)
            : 0.00;
        $deliveryFeeOutsideDhaka = $items->isNotEmpty()
            ? (float) $settings->get('delivery_fee_outside_dhaka', $deliveryFeeOutsideDhakaDefault)
            : 0.00;
        $deliveryFee = $items->isNotEmpty()
            ? $this->deliveryFeeForCity((string) old('city', ''), $settings)
            : 0.00;
        $promotionLines = $items->map(fn ($item): array => [
            'product_id' => (int) $item->product_id,
            'category_id' => $item->product?->category_id ? (int) $item->product->category_id : null,
            'product_variant_id' => $item->product_variant_id ? (int) $item->product_variant_id : null,
            'amount' => (float) $item->price * (int) $item->quantity,
        ])->all();
        $appliedCouponCode = (string) $request->session()->get('checkout.coupon_code', '');
        $couponError = null;
        $couponDiscount = 0.00;
        $deliveryDiscount = 0.00;
        $freeDelivery = false;
        $promotion = null;

        if ($appliedCouponCode !== '') {
            try {
                $customerKey = $request->session()->get('checkout.coupon_customer_key')
                    ?? $coupons->customerKey($request->user(), old('phone', $request->user()?->phone));
                $couponPreview = $coupons->calculateForCart($appliedCouponCode, $promotionLines, $subtotal, $deliveryFee, $customerKey);
                $promotion = $couponPreview['coupon'];
                $couponDiscount = $couponPreview['discount'];
                $deliveryDiscount = $couponPreview['delivery_discount'];
                $freeDelivery = $couponPreview['free_delivery'];
            } catch (ValidationException $exception) {
                $request->session()->forget(['checkout.coupon_code', 'checkout.coupon_customer_key']);
                $appliedCouponCode = '';
                $couponError = $exception->errors()['coupon_code'][0] ?? 'This coupon is no longer valid.';
            }
        } else {
            $phone = old('phone', $request->user()?->phone);
            $customerKey = filled($phone) ? $coupons->customerKey($request->user(), $phone) : null;
            $promotionPreview = $coupons->calculateAutomaticForCart($promotionLines, $subtotal, $deliveryFee, $customerKey);
            $promotion = $promotionPreview['coupon'];
            $couponDiscount = $promotionPreview['discount'];
            $deliveryDiscount = $promotionPreview['delivery_discount'];
            $freeDelivery = $promotionPreview['free_delivery'];
        }

        $discount = $couponDiscount;
        $request->session()->put('checkout.promotion_preview', [
            'campaign_id' => $promotion?->getKey(),
            'subtotal' => round($subtotal, 2),
            'discount' => round($discount, 2),
        ]);
        $deliveryDiscountInsideDhaka = $freeDelivery ? $deliveryFeeInsideDhaka : 0.00;
        $deliveryDiscountOutsideDhaka = $freeDelivery ? $deliveryFeeOutsideDhaka : 0.00;
        $deliveryFee = max(0, $deliveryFee - $deliveryDiscount);
        if ($freeDelivery) {
            $deliveryFeeInsideDhaka = 0.00;
            $deliveryFeeOutsideDhaka = 0.00;
        }
        $promotionLabel = $promotion?->code ?: ($promotion ? 'Automatic offer' : null);
        $total = $subtotal + $deliveryFee - $discount;

        $paymentAccounts = config('payments.mobile_wallets');
        foreach ($paymentAccounts as $provider => $paymentAccount) {
            $paymentAccounts[$provider]['merchant_number'] = $settings->get(
                $provider.'_merchant_number',
                $paymentAccount['merchant_number'],
            );
        }

        return view('storefront.checkout', compact(
            'cart',
            'items',
            'subtotal',
            'deliveryFee',
            'deliveryFeeInsideDhaka',
            'deliveryFeeOutsideDhaka',
            'discount',
            'total',
            'paymentAccounts',
            'appliedCouponCode',
            'couponError',
            'couponDiscount',
            'deliveryDiscount',
            'deliveryDiscountInsideDhaka',
            'deliveryDiscountOutsideDhaka',
            'freeDelivery',
            'promotionLabel',
        ));
    }

    public function applyCoupon(Request $request, CouponService $coupons): RedirectResponse
    {
        $validated = $request->validate([
            'coupon_code' => ['required', 'string', 'max:40'],
            'phone' => ['required', 'string', 'max:20'],
        ]);
        $cart = $this->resolveCart($request);
        $customerKey = $coupons->customerKey($request->user(), $validated['phone']);

        try {
            $coupon = $coupons->calculateForCart(
                $validated['coupon_code'],
                $this->cartPromotionLines($cart),
                $this->cartSubtotal($cart),
                0,
                $customerKey,
            );
        } catch (ValidationException $exception) {
            $request->session()->forget(['checkout.coupon_code', 'checkout.coupon_customer_key']);

            return redirect()->route('checkout')
                ->withInput($request->except(['coupon_code']))
                ->withErrors(['coupon_code' => $exception->errors()['coupon_code'][0] ?? 'This coupon is no longer valid.']);
        }

        $request->session()->put([
            'checkout.coupon_code' => $coupon['coupon']->code,
            'checkout.coupon_customer_key' => $customerKey,
            'checkout.promotion_preview' => [
                'campaign_id' => $coupon['coupon']->getKey(),
                'subtotal' => round($this->cartSubtotal($cart), 2),
                'discount' => round($coupon['discount'], 2),
            ],
        ]);

        return redirect()->route('checkout')
            ->withInput($request->only(['customer_name', 'phone', 'address', 'city', 'area', 'notes', 'coupon_code']))
            ->with('coupon_status', 'Coupon applied successfully.');
    }

    public function removeCoupon(Request $request): RedirectResponse
    {
        $request->session()->forget(['checkout.coupon_code', 'checkout.coupon_customer_key', 'checkout.promotion_preview']);

        return redirect()->route('checkout')->with('coupon_status', 'Coupon removed.');
    }

    public function store(Request $request, SiteSettings $settings, CouponService $coupons): RedirectResponse
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
            'payment_method' => ['required', Rule::in(['cod', 'bkash', 'nagad', 'rocket'])],
            'payment_sender_phone' => ['nullable', 'required_if:payment_method,bkash,nagad,rocket', 'string', 'max:20'],
            'payment_transaction_id' => [
                'nullable',
                'required_if:payment_method,bkash,nagad,rocket',
                'string',
                'max:100',
                Rule::unique('orders', 'payment_transaction_id')->where(
                    fn ($query) => $query->where('payment_method', $request->input('payment_method')),
                ),
            ],
            'payment_proof' => ['nullable', 'required_if:payment_method,bkash,nagad,rocket', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'coupon_code' => ['nullable', 'string', 'max:40'],
        ]);

        $couponCode = (string) $request->session()->get('checkout.coupon_code', '');
        $submittedCouponCode = strtoupper(trim((string) ($validated['coupon_code'] ?? '')));

        if (($couponCode === '') !== ($submittedCouponCode === '')
            || ($couponCode !== '' && strtoupper($couponCode) !== $submittedCouponCode)) {
            throw ValidationException::withMessages([
                'coupon_code' => 'Apply or remove the coupon before placing your order.',
            ]);
        }

        $couponCustomerKey = $coupons->customerKey($customer, $validated['phone']);
        $promotionPreview = $request->session()->get('checkout.promotion_preview');

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
        $deliveryFee = $this->deliveryFeeForCity($validated['city'], $settings);

        if ($paymentMethod !== 'cod') {
            $paymentProofPath = $request->file('payment_proof')->store('payment-proofs', $paymentProofDisk);

            if (! $paymentProofPath) {
                throw ValidationException::withMessages([
                    'payment_proof' => 'The payment receipt could not be saved. Please try again.',
                ]);
            }
        }

        try {
            $order = DB::transaction(function () use ($cart, $customer, $request, $validated, $paymentMethod, $paymentProofPath, $deliveryFee, $couponCode, $couponCustomerKey, $promotionPreview, $coupons): Order {
                $lockedCart = Cart::query()->lockForUpdate()->findOrFail($cart->id);
                $items = $lockedCart->items()->orderBy('id')->get();

                if ($items->isEmpty()) {
                    throw ValidationException::withMessages([
                        'cart' => 'Your cart is empty. Add at least one product before placing an order.',
                    ]);
                }

                $orderLines = [];
                $promotionLines = [];
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
                    $promotionLines[] = [
                        'product_id' => (int) $product->id,
                        'category_id' => $product->category_id ? (int) $product->category_id : null,
                        'product_variant_id' => $variant?->id ? (int) $variant->id : null,
                        'amount' => $price * (int) $item->quantity,
                    ];
                }

                if ($customer?->account_type === 'customer') {
                    $customer->fill([
                        'name' => $validated['customer_name'],
                        'phone' => $validated['phone'],
                    ])->save();
                }

                $calculation = $couponCode !== ''
                    ? $coupons->calculateForCart($couponCode, $promotionLines, $subtotal, $deliveryFee, $couponCustomerKey, true)
                    : $coupons->calculateAutomaticForCart($promotionLines, $subtotal, $deliveryFee, $couponCustomerKey, true);
                $coupon = $calculation['coupon'];
                $discount = $calculation['discount'];
                $deliveryDiscount = $calculation['delivery_discount'];

                if (is_array($promotionPreview)
                    && ((int) ($promotionPreview['campaign_id'] ?? 0) !== (int) ($coupon?->getKey() ?? 0)
                        || abs((float) ($promotionPreview['subtotal'] ?? -1) - round($subtotal, 2)) > 0.009
                        || abs((float) ($promotionPreview['discount'] ?? -1) - round($discount, 2)) > 0.009)) {
                    throw ValidationException::withMessages([
                        'coupon_code' => 'The offer or cart total changed. Review the updated checkout total and try again.',
                    ]);
                }

                $payableDeliveryFee = max(0, $deliveryFee - $deliveryDiscount);
                $total = max(0, $subtotal + $payableDeliveryFee - $discount);
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
                    'delivery_fee' => $payableDeliveryFee,
                    'delivery_discount' => $deliveryDiscount,
                    'discount' => $discount,
                    'coupon_id' => $coupon?->getKey(),
                    'coupon_code' => $coupon?->code,
                    'total' => $total,
                ]);

                if ($coupon && $couponCustomerKey !== null) {
                    $coupons->recordRedemption($coupon, $order, $customer, $couponCustomerKey, $discount, $deliveryDiscount);
                }

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

        $request->session()->forget(['cart_id', 'checkout.coupon_code', 'checkout.coupon_customer_key', 'checkout.promotion_preview']);

        $order->load('items');
        $metaPurchaseEvent = $this->metaPurchaseEvent($order);

        if (
            $request->cookie('meta_marketing_consent') === 'granted'
            && (bool) config('services.meta.capi_enabled')
            && preg_match('/^\d+$/', (string) config('services.meta.pixel_id')) === 1
            && filled(config('services.meta.capi_access_token'))
        ) {
            SendMetaPurchaseEvent::dispatch(
                orderId: (int) $order->id,
                eventSourceUrl: url(route('checkout.success', ['order' => $order->id], false)),
                clientIpAddress: $request->ip(),
                clientUserAgent: $request->userAgent(),
                fbp: is_string($request->cookie('_fbp')) ? $request->cookie('_fbp') : null,
                fbc: is_string($request->cookie('_fbc')) ? $request->cookie('_fbc') : null,
            );
        }

        $successUrl = $customer
            ? route('checkout.success', ['order' => $order->id])
            : URL::temporarySignedRoute('checkout.success', now()->addMinutes(30), ['order' => $order->id]);

        return redirect()->to($successUrl)
            ->with('success', 'Order placed successfully.')
            ->with('metaPurchaseEvent', $metaPurchaseEvent);
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

    private function deliveryFeeForCity(string $city, SiteSettings $settings): float
    {
        $trimmedCity = trim($city);
        $normalizedCity = strtolower($trimmedCity);
        $isInsideDhaka = str_starts_with($normalizedCity, 'dhaka') || str_starts_with($trimmedCity, 'ঢাকা');
        $settingKey = $isInsideDhaka ? 'delivery_fee_inside_dhaka' : 'delivery_fee_outside_dhaka';
        $configKey = $isInsideDhaka ? 'storefront.delivery_fee_inside_dhaka' : 'storefront.delivery_fee_outside_dhaka';
        $legacyDeliveryFee = $settings->get('delivery_fee');
        $defaultDeliveryFee = $legacyDeliveryFee ?? config($configKey, config('storefront.delivery_fee', 60.00));

        return (float) $settings->get($settingKey, $defaultDeliveryFee);
    }

    private function cartSubtotal(Cart $cart): float
    {
        return (float) collect($this->cartPromotionLines($cart))->sum('amount');
    }

    /** @return list<array{product_id: int, category_id: ?int, product_variant_id: ?int, amount: float}> */
    private function cartPromotionLines(Cart $cart): array
    {
        return $cart->items()
            ->with(['product:id,category_id,price', 'productVariant:id,price'])
            ->get()
            ->map(fn ($item): array => [
                'product_id' => (int) $item->product_id,
                'category_id' => $item->product?->category_id ? (int) $item->product->category_id : null,
                'product_variant_id' => $item->product_variant_id ? (int) $item->product_variant_id : null,
                'amount' => (float) ($item->productVariant?->price ?? $item->product?->price ?? $item->price) * (int) $item->quantity,
            ])->all();
    }

    /** @return array{event_id: string, value: float, currency: string, contents: list<array{id: string, quantity: int, item_price: float}>, content_ids: list<string>, content_type: string} */
    private function metaPurchaseEvent(Order $order): array
    {
        $contents = $order->items
            ->map(fn ($item): array => [
                'id' => (string) ($item->variant_sku ?: $item->product_id ?: $item->id),
                'quantity' => (int) $item->quantity,
                'item_price' => (float) $item->price,
            ])
            ->values()
            ->all();

        return [
            'event_id' => 'purchase-order-'.$order->id,
            'value' => (float) $order->total,
            'currency' => (string) config('services.meta.currency', 'BDT'),
            'contents' => $contents,
            'content_ids' => array_column($contents, 'id'),
            'content_type' => 'product',
        ];
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
