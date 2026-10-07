<x-layouts.app>
    <x-slot:title>
        Checkout — {{ config('app.name') }}
    </x-slot:title>

    <x-slot:metaDescription>
        Complete your order securely with cash on delivery, bKash, or Nagad.
    </x-slot:metaDescription>

    <x-slot:canonicalUrl>
        {{ route('checkout') }}
    </x-slot:canonicalUrl>

    <section class="bg-[#f5f7ee]">
        <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
            <nav class="mb-6 text-sm text-[#738078]" aria-label="Breadcrumb">
                <a href="{{ route('home') }}" class="font-semibold text-[#145943]">Home</a>
                <span class="px-2">/</span>
                <a href="{{ route('shop') }}" class="font-semibold text-[#145943]">Shop</a>
                <span class="px-2">/</span>
                <span>Checkout</span>
            </nav>

            <div class="grid gap-6 lg:grid-cols-[1.4fr_0.9fr]">
                <div class="rounded-[26px] border border-[#e4e3d8] bg-white p-5 shadow-sm sm:p-7">
                    <h1 class="text-3xl font-extrabold tracking-tight text-[#145943]">Checkout</h1>
                    <p class="mt-2 text-sm text-[#66736d]">Deliver fresh essentials right to your doorstep.</p>

                    @if ($errors->any())
                        <div class="mt-6 rounded-2xl border border-red-200 bg-red-50 p-3 text-sm text-red-700">
                            <ul class="space-y-1">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('checkout.store') }}" enctype="multipart/form-data" data-checkout-form class="mt-8 space-y-8">
                        @csrf
                        <input type="hidden" name="cart_id" value="{{ $cart->id ?? '' }}">

                        <div>
                            <h2 class="text-lg font-bold text-[#145943]">Shipping Information</h2>
                            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                                <label class="block text-sm font-medium text-[#4d5c56]">
                                    Full name
                                    <input
                                        type="text"
                                        name="customer_name"
                                        value="{{ old('customer_name', auth()->user()?->name ?? '') }}"
                                        class="mt-2 h-11 w-full rounded-xl border border-[#e3e0d6] bg-[#faf9f5] px-3 text-sm outline-none focus:border-[#145943] focus:ring-4 focus:ring-[#145943]/10"
                                        required
                                    >
                                </label>
                                <label class="block text-sm font-medium text-[#4d5c56]">
                                    Phone number
                                    <input
                                        type="tel"
                                        name="phone"
                                        value="{{ old('phone', auth()->user()?->phone ?? '') }}"
                                        class="mt-2 h-11 w-full rounded-xl border border-[#e3e0d6] bg-[#faf9f5] px-3 text-sm outline-none focus:border-[#145943] focus:ring-4 focus:ring-[#145943]/10"
                                        required
                                    >
                                </label>
                                <label class="block text-sm font-medium text-[#4d5c56] sm:col-span-2">
                                    Address
                                    <input
                                        type="text"
                                        name="address"
                                        value="{{ old('address') }}"
                                        class="mt-2 h-11 w-full rounded-xl border border-[#e3e0d6] bg-[#faf9f5] px-3 text-sm outline-none focus:border-[#145943] focus:ring-4 focus:ring-[#145943]/10"
                                        required
                                    >
                                </label>
                                <label class="block text-sm font-medium text-[#4d5c56]">
                                    City
                                    <input
                                        type="text"
                                        name="city"
                                        value="{{ old('city') }}"
                                        data-delivery-city
                                        class="mt-2 h-11 w-full rounded-xl border border-[#e3e0d6] bg-[#faf9f5] px-3 text-sm outline-none focus:border-[#145943] focus:ring-4 focus:ring-[#145943]/10"
                                        required
                                    >
                                </label>
                                <label class="block text-sm font-medium text-[#4d5c56]">
                                    Area
                                    <input
                                        type="text"
                                        name="area"
                                        value="{{ old('area') }}"
                                        class="mt-2 h-11 w-full rounded-xl border border-[#e3e0d6] bg-[#faf9f5] px-3 text-sm outline-none focus:border-[#145943] focus:ring-4 focus:ring-[#145943]/10"
                                    >
                                </label>
                            </div>
                        </div>

                        <section class="border-t border-[#e4e3d8] pt-6">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <h2 class="text-lg font-bold text-[#145943]">Discount code</h2>
                                    @if ($appliedCouponCode)
                                        <p class="mt-1 text-sm text-green-700">{{ $appliedCouponCode }} is applied. {{ $freeDelivery ? 'Free delivery' : 'Discount: ৳'.number_format((float) $couponDiscount, 2) }}</p>
                                    @elseif ($promotionLabel)
                                        <p class="mt-1 text-sm text-green-700">{{ $promotionLabel }} applied. {{ $freeDelivery ? 'Free delivery' : 'Discount: ৳'.number_format((float) $couponDiscount, 2) }}</p>
                                    @endif
                                    @if (session('coupon_status'))
                                        <p class="mt-1 text-sm text-green-700">{{ session('coupon_status') }}</p>
                                    @endif
                                    @if ($couponError)
                                        <p class="mt-1 text-sm text-red-700">{{ $couponError }}</p>
                                    @endif
                                </div>
                            </div>
                            <div class="mt-3 flex flex-col gap-2 sm:flex-row">
                                <label class="sr-only" for="checkout-coupon-code">Coupon code</label>
                                <input id="checkout-coupon-code" type="text" name="coupon_code" value="{{ old('coupon_code', $appliedCouponCode) }}" maxlength="40" autocomplete="off" autocapitalize="characters" placeholder="Enter coupon code" class="h-11 min-w-0 flex-1 rounded-xl border border-[#e3e0d6] bg-[#faf9f5] px-3 text-sm uppercase outline-none focus:border-[#145943] focus:ring-4 focus:ring-[#145943]/10">
                                <button type="submit" formaction="{{ route('checkout.coupon.apply') }}" formmethod="POST" formnovalidate class="h-11 rounded-xl border border-[#145943] px-4 text-sm font-bold text-[#145943] hover:bg-[#edf2e4]">Apply code</button>
                                @if ($appliedCouponCode)
                                    <button type="submit" formaction="{{ route('checkout.coupon.remove') }}" formmethod="POST" formnovalidate class="h-11 rounded-xl border border-[#dfe3cf] px-4 text-sm font-bold text-[#66736d] hover:bg-[#f5f4ec]">Remove</button>
                                @endif
                            </div>
                        </section>

                        <div>
                            <h2 class="text-lg font-bold text-[#145943]">Payment Method</h2>
                            <div class="mt-4 space-y-3">
                                <label class="flex cursor-pointer items-center justify-between rounded-2xl border border-[#dfe3cf] bg-[#f8f9f2] p-4">
                                    <div class="flex items-center gap-3">
                                        <input type="radio" name="payment_method" value="cod" {{ old('payment_method', 'cod') === 'cod' ? 'checked' : '' }} class="h-4 w-4 accent-[#145943]">
                                        <div>
                                            <p class="font-semibold text-[#173b32]">Cash on delivery</p>
                                            <p class="text-xs text-[#738078]">Pay when you receive your order.</p>
                                        </div>
                                    </div>
                                    <span class="text-sm font-bold text-[#145943]">Default</span>
                                </label>
                                @foreach ($paymentAccounts as $provider => $paymentAccount)
                                    <label data-payment-option style="--wallet-accent: {{ $paymentAccount['accent'] }}" class="flex cursor-pointer items-center justify-between gap-4 rounded-2xl border border-[#dfe3cf] bg-white p-4">
                                        <span class="flex items-center gap-3">
                                            <img data-wallet-logo="{{ $provider }}" src="{{ asset($paymentAccount['logo']) }}" alt="{{ $paymentAccount['label'] }} logo" class="h-12 w-12 shrink-0 rounded-lg object-contain">
                                            <input
                                                type="radio"
                                                name="payment_method"
                                                value="{{ $provider }}"
                                                data-payment-method
                                                data-payment-accent="{{ $paymentAccount['accent'] }}"
                                                data-payment-label="{{ $paymentAccount['label'] }}"
                                                style="--wallet-accent: {{ $paymentAccount['accent'] }}"
                                                {{ old('payment_method') === $provider ? 'checked' : '' }}
                                                {{ blank($paymentAccount['merchant_number']) ? 'disabled' : '' }}
                                                class="h-4 w-4 appearance-none rounded-full border border-[#9aa19c] bg-white checked:border-[var(--wallet-accent)] checked:bg-[var(--wallet-accent)] checked:shadow-[inset_0_0_0_3px_white]"
                                            >
                                            <span>
                                                <span class="block font-semibold text-[#173b32]">{{ $paymentAccount['label'] }}</span>
                                                <span class="block text-xs text-[#738078]">
                                                    {{ blank($paymentAccount['merchant_number']) ? 'Not available yet' : 'Pay securely using mobile banking.' }}
                                                </span>
                                            </span>
                                        </span>
                                        <span class="shrink-0 text-right text-sm font-bold text-[#145943]">
                                            {{ $paymentAccount['merchant_number'] ?: 'Unavailable' }}
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                            <div data-payment-details hidden inert class="mt-4 space-y-4 rounded-2xl border border-[#dfe3cf] bg-[#f8f9f2] p-4 sm:p-5">
                                <div>
                                    <h3 class="font-bold text-[#173b32]">Mobile payment instructions</h3>
                                    <p class="mt-1 text-sm text-[#66736d]">Send exactly ৳{{ number_format((float) $total, 2) }} to the selected merchant number, then enter the transaction details below.</p>
                                </div>
                                @foreach ($paymentAccounts as $provider => $paymentAccount)
                                    <div data-wallet-instructions="{{ $provider }}" hidden>
                                        <span class="block text-sm font-medium text-[#4d5c56]">{{ $paymentAccount['label'] }} merchant number</span>
                                        <div class="mt-2 flex items-center justify-between gap-3 rounded-xl border border-[#e3e0d6] bg-white px-3 py-2.5">
                                            <span class="font-bold text-[#173b32]">{{ $paymentAccount['merchant_number'] }}</span>
                                            <button type="button" data-copy-payment-number="{{ $paymentAccount['merchant_number'] }}" aria-label="Copy {{ $paymentAccount['label'] }} merchant number" class="inline-flex min-h-9 items-center gap-2 rounded-lg px-3 text-sm font-bold text-white transition hover:opacity-90" style="background-color: {{ $paymentAccount['accent'] }}">
                                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="8" y="8" width="12" height="12" rx="2" /><path d="M16 8V5a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h3" /></svg>
                                                <span data-copy-label>Copy</span>
                                            </button>
                                        </div>
                                    </div>
                                @endforeach
                                <div class="grid gap-4 sm:grid-cols-2">
                                    <label class="block text-sm font-medium text-[#4d5c56]">
                                        <span data-payment-field-label="account">Your {{ $paymentAccounts[old('payment_method')]['label'] ?? 'wallet' }} account number</span>
                                        <input type="tel" name="payment_sender_phone" value="{{ old('payment_sender_phone') }}" autocomplete="tel" maxlength="20" data-payment-required class="mt-2 h-11 w-full rounded-xl border border-[#e3e0d6] bg-white px-3 text-sm outline-none focus:border-[#145943] focus:ring-4 focus:ring-[#145943]/10">
                                    </label>
                                    <label class="block text-sm font-medium text-[#4d5c56]">
                                        <span data-payment-field-label="transaction">Your {{ $paymentAccounts[old('payment_method')]['label'] ?? 'wallet' }} transaction ID</span>
                                        <input type="text" name="payment_transaction_id" value="{{ old('payment_transaction_id') }}" maxlength="100" autocomplete="off" data-payment-required class="mt-2 h-11 w-full rounded-xl border border-[#e3e0d6] bg-white px-3 text-sm outline-none focus:border-[#145943] focus:ring-4 focus:ring-[#145943]/10">
                                    </label>
                                </div>
                                <div>
                                    <span class="block text-sm font-medium text-[#4d5c56]">Payment receipt image</span>
                                    <div data-payment-proof-picker class="mt-2">
                                        <label for="payment-proof-input" class="relative flex min-h-28 cursor-pointer flex-col items-center justify-center gap-2 overflow-hidden rounded-xl border-2 border-dashed border-[#cfd8cc] bg-white px-4 py-5 text-center transition-colors hover:border-[#145943] hover:bg-[#f8f9f2] focus-within:ring-4 focus-within:ring-[#145943]/10">
                                            <svg class="h-7 w-7 text-[#145943]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M12 16V4m0 0L7.5 8.5M12 4l4.5 4.5M4 15.5v3A1.5 1.5 0 0 0 5.5 20h13a1.5 1.5 0 0 0 1.5-1.5v-3" /></svg>
                                            <span class="text-sm font-bold text-[#173b32]">Choose a receipt image</span>
                                            <span class="text-xs text-[#738078]">JPG, PNG, or WebP · Max 5 MB</span>
                                            <input id="payment-proof-input" type="file" name="payment_proof" accept="image/jpeg,image/png,image/webp" data-payment-required data-payment-proof-input class="absolute inset-0 h-full w-full cursor-pointer opacity-0">
                                        </label>
                                    </div>
                                    <div data-payment-proof-preview hidden class="mt-3 flex items-center gap-3 rounded-xl border border-[#dfe3cf] bg-white p-3">
                                        <img data-payment-proof-image alt="" class="h-16 w-16 shrink-0 rounded-lg border border-[#e8e6db] object-cover">
                                        <div class="min-w-0 flex-1">
                                            <p class="text-sm font-semibold text-[#173b32]">Receipt selected</p>
                                            <p data-payment-proof-name class="truncate text-xs text-[#738078]"></p>
                                        </div>
                                        <button type="button" data-payment-proof-remove class="shrink-0 rounded-lg border border-red-200 px-3 py-2 text-xs font-bold text-red-700 transition hover:bg-red-50">Remove image</button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div>
                            <h2 class="text-lg font-bold text-[#145943]">Order Notes</h2>
                            <textarea
                                name="notes"
                                rows="4"
                                placeholder="Add delivery instructions or special requests..."
                                class="mt-3 w-full rounded-2xl border border-[#e3e0d6] bg-[#faf9f5] px-3 py-3 text-sm text-[#173b32] outline-none focus:border-[#145943] focus:ring-4 focus:ring-[#145943]/10"
                            >{{ old('notes') }}</textarea>
                        </div>

                        <div class="flex items-center justify-end">
                            <button type="submit" class="w-full rounded-xl bg-[#145943] px-4 py-3.5 text-sm font-bold text-white transition hover:bg-[#a48335]">
                                Place Order
                            </button>
                        </div>
                    </form>
                </div>

                <aside class="rounded-[26px] border border-[#e4e3d8] bg-white p-5 shadow-sm sm:p-7">
                    <h2 class="text-xl font-extrabold text-[#145943]">Order Summary</h2>

                    @if ($items->isEmpty())
                        <div class="mt-5 rounded-2xl border border-dashed border-[#dfe3cf] bg-[#f8f9f2] p-5 text-sm text-[#738078]">
                            Your cart is empty.
                        </div>
                    @else
                        <div class="mt-5 space-y-3">
                            @foreach ($items as $item)
                                @php
                                    $itemProduct = $item->product;
                                    $itemImage = $itemProduct?->primaryImage ?? $itemProduct?->images?->firstWhere('is_primary', true) ?? $itemProduct?->images?->first();
                                @endphp
                                <div class="flex items-center justify-between gap-4 rounded-2xl bg-[#f7f6f1] p-3 sm:p-4">
                                    <div class="flex items-center gap-3.5 min-w-0">
                                        @if ($itemImage)
                                            <div class="h-20 w-20 sm:h-24 sm:w-24 shrink-0 rounded-2xl border border-[#dfe3cf] bg-white p-1.5 flex items-center justify-center overflow-hidden">
                                                <img
                                                    src="/storage/{{ ltrim($itemImage->image_path, '/') }}"
                                                    alt="{{ $item->name }}"
                                                    class="h-full w-full object-contain"
                                                >
                                            </div>
                                        @else
                                            <div class="h-20 w-20 sm:h-24 sm:w-24 shrink-0 rounded-2xl border border-[#dfe3cf] bg-[#edf1d8] flex items-center justify-center text-3xl">
                                                🛍️
                                            </div>
                                        @endif
                                        <div class="min-w-0">
                                            <p class="text-sm sm:text-base font-bold text-[#173b32] line-clamp-2">{{ $item->name }}</p>
                                            @if ($item->variant_name)
                                                <p class="mt-1 text-xs text-[#738078]">{{ $item->variant_name }}</p>
                                            @endif
                                            <p class="mt-1 text-xs sm:text-sm text-[#738078]">Qty: <span class="font-semibold text-[#173b32]">{{ $item->quantity }}</span> × ৳{{ number_format((float) $item->price, 2) }}</p>
                                        </div>
                                    </div>
                                    <p class="shrink-0 text-right font-extrabold text-[#145943] text-sm sm:text-base">৳{{ number_format((float) ($item->price * $item->quantity), 2) }}</p>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    <div class="mt-6 space-y-3 border-t border-[#e4e3d8] pt-5 text-sm text-[#4d5c56]">
                        <div class="flex items-center justify-between"><span>Subtotal</span><span>৳{{ number_format((float) $subtotal, 2) }}</span></div>
                        <div class="flex items-center justify-between gap-4"><span data-delivery-fee-label>Delivery charge</span><span data-delivery-fee-amount data-inside-fee="{{ number_format((float) $deliveryFeeInsideDhaka, 2, '.', '') }}" data-outside-fee="{{ number_format((float) $deliveryFeeOutsideDhaka, 2, '.', '') }}">৳{{ number_format((float) $deliveryFee, 2) }}</span></div>
                        @if ($freeDelivery)
                            <div class="flex items-center justify-between gap-4"><span>Delivery offer</span><span data-delivery-discount-amount data-inside-discount="{{ number_format((float) $deliveryDiscountInsideDhaka, 2, '.', '') }}" data-outside-discount="{{ number_format((float) $deliveryDiscountOutsideDhaka, 2, '.', '') }}">-৳{{ number_format((float) $deliveryDiscount, 2) }}</span></div>
                        @endif
                        <div class="flex items-center justify-between"><span>Discount{{ $promotionLabel ? ' ('.$promotionLabel.')' : '' }}</span><span>-৳{{ number_format((float) $discount, 2) }}</span></div>
                    </div>

                    <div class="mt-5 flex items-center justify-between border-t border-[#e4e3d8] pt-5">
                        <span class="text-base font-bold text-[#173b32]">Total</span>
                        <span data-order-total data-subtotal="{{ number_format((float) $subtotal, 2, '.', '') }}" data-discount="{{ number_format((float) $discount, 2, '.', '') }}" class="text-2xl font-extrabold text-[#145943]">৳{{ number_format((float) $total, 2) }}</span>
                    </div>

                    <p class="mt-4 text-center text-xs text-[#738078]">
                        By placing the order, you agree to our terms and policy.
                    </p>
                </aside>
            </div>
        </div>
    </section>
</x-layouts.app>
