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

                        <div>
                            <h2 class="text-lg font-bold text-[#145943]">Payment Method</h2>
                            <div class="mt-4 space-y-3">
                                <label class="flex cursor-pointer items-center justify-between rounded-2xl border border-[#dfe3cf] bg-[#f8f9f2] p-4">
                                    <div class="flex items-center gap-3">
                                        <input type="radio" name="payment_method" value="cod" {{ old('payment_method', 'cod') === 'cod' ? 'checked' : '' }} class="h-4 w-4 accent-[#145943]">
                                        <div>
                                            <p class="font-semibold text-[#173b32]">Cash on Delivery</p>
                                            <p class="text-xs text-[#738078]">Pay when your order arrives.</p>
                                        </div>
                                    </div>
                                    <span class="text-sm font-bold text-[#145943]">Default</span>
                                </label>
                                @foreach ($paymentAccounts as $provider => $paymentAccount)
                                    <label class="flex cursor-pointer items-center justify-between gap-4 rounded-2xl border border-[#dfe3cf] bg-white p-4">
                                        <span class="flex items-center gap-3">
                                            <input
                                                type="radio"
                                                name="payment_method"
                                                value="{{ $provider }}"
                                                data-payment-method
                                                {{ old('payment_method') === $provider ? 'checked' : '' }}
                                                {{ blank($paymentAccount['merchant_number']) ? 'disabled' : '' }}
                                                class="h-4 w-4 accent-[#145943]"
                                            >
                                            <span>
                                                <span class="block font-semibold text-[#173b32]">{{ $paymentAccount['label'] }}</span>
                                                <span class="block text-xs text-[#738078]">
                                                    {{ blank($paymentAccount['merchant_number']) ? 'Not available yet' : 'Pay securely using your mobile wallet.' }}
                                                </span>
                                            </span>
                                        </span>
                                        <span class="shrink-0 text-right text-sm font-bold text-[#145943]">
                                            {{ $paymentAccount['merchant_number'] ? '৳ '.$paymentAccount['merchant_number'] : 'Unavailable' }}
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                            <div data-payment-details hidden inert class="mt-4 space-y-4 rounded-2xl border border-[#dfe3cf] bg-[#f8f9f2] p-4 sm:p-5">
                                <div>
                                    <h3 class="font-bold text-[#173b32]">Mobile payment details</h3>
                                    <p class="mt-1 text-sm text-[#66736d]">Pay exactly ৳{{ number_format((float) $total, 2) }} to the selected store number, then enter the transaction details below.</p>
                                </div>
                                <label data-wallet-instructions="bkash" hidden class="block text-sm font-medium text-[#4d5c56]">
                                    bKash merchant number
                                    <span class="mt-2 block rounded-xl border border-[#e3e0d6] bg-white px-3 py-3 font-bold text-[#145943]">{{ $paymentAccounts['bkash']['merchant_number'] }}</span>
                                </label>
                                <label data-wallet-instructions="nagad" hidden class="block text-sm font-medium text-[#4d5c56]">
                                    Nagad merchant number
                                    <span class="mt-2 block rounded-xl border border-[#e3e0d6] bg-white px-3 py-3 font-bold text-[#145943]">{{ $paymentAccounts['nagad']['merchant_number'] }}</span>
                                </label>
                                <div class="grid gap-4 sm:grid-cols-2">
                                    <label class="block text-sm font-medium text-[#4d5c56]">
                                        Sender wallet number
                                        <input type="tel" name="payment_sender_phone" value="{{ old('payment_sender_phone') }}" autocomplete="tel" maxlength="20" data-payment-required class="mt-2 h-11 w-full rounded-xl border border-[#e3e0d6] bg-white px-3 text-sm outline-none focus:border-[#145943] focus:ring-4 focus:ring-[#145943]/10">
                                    </label>
                                    <label class="block text-sm font-medium text-[#4d5c56]">
                                        Transaction ID
                                        <input type="text" name="payment_transaction_id" value="{{ old('payment_transaction_id') }}" maxlength="100" autocomplete="off" data-payment-required class="mt-2 h-11 w-full rounded-xl border border-[#e3e0d6] bg-white px-3 text-sm outline-none focus:border-[#145943] focus:ring-4 focus:ring-[#145943]/10">
                                    </label>
                                </div>
                                <label class="block text-sm font-medium text-[#4d5c56]">
                                    Payment receipt image
                                    <input type="file" name="payment_proof" accept="image/jpeg,image/png,image/webp" data-payment-required class="mt-2 block w-full rounded-xl border border-[#e3e0d6] bg-white px-3 py-3 text-sm file:mr-3 file:rounded-lg file:border-0 file:bg-[#145943] file:px-3 file:py-2 file:font-bold file:text-white">
                                    <span class="mt-1 block text-xs text-[#738078]">JPG, PNG, or WebP; maximum 5 MB.</span>
                                </label>
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
                        <div class="flex items-center justify-between"><span>Delivery</span><span>৳{{ number_format((float) $deliveryFee, 2) }}</span></div>
                        <div class="flex items-center justify-between"><span>Discount</span><span>-৳{{ number_format((float) $discount, 2) }}</span></div>
                    </div>

                    <div class="mt-5 flex items-center justify-between border-t border-[#e4e3d8] pt-5">
                        <span class="text-base font-bold text-[#173b32]">Total</span>
                        <span class="text-2xl font-extrabold text-[#145943]">৳{{ number_format((float) $total, 2) }}</span>
                    </div>

                    <p class="mt-4 text-center text-xs text-[#738078]">
                        By placing the order, you agree to our terms and policy.
                    </p>
                </aside>
            </div>
        </div>
    </section>
</x-layouts.app>
