<x-layouts.app>
    <x-slot:title>
        Checkout — {{ config('app.name') }}
    </x-slot:title>

    <x-slot:metaDescription>
        Complete your order securely with cash on delivery or online payment.
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

                    <form method="POST" action="{{ route('checkout.store') }}" class="mt-8 space-y-8">
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
                                        value="{{ old('customer_name', 'John Doe') }}"
                                        class="mt-2 h-11 w-full rounded-xl border border-[#e3e0d6] bg-[#faf9f5] px-3 text-sm outline-none focus:border-[#145943] focus:ring-4 focus:ring-[#145943]/10"
                                        required
                                    >
                                </label>
                                <label class="block text-sm font-medium text-[#4d5c56]">
                                    Phone number
                                    <input
                                        type="tel"
                                        name="phone"
                                        value="{{ old('phone', '01712-345678') }}"
                                        class="mt-2 h-11 w-full rounded-xl border border-[#e3e0d6] bg-[#faf9f5] px-3 text-sm outline-none focus:border-[#145943] focus:ring-4 focus:ring-[#145943]/10"
                                        required
                                    >
                                </label>
                                <label class="block text-sm font-medium text-[#4d5c56] sm:col-span-2">
                                    Address
                                    <input
                                        type="text"
                                        name="address"
                                        value="{{ old('address', 'House 12, Road 6, Dhanmondi') }}"
                                        class="mt-2 h-11 w-full rounded-xl border border-[#e3e0d6] bg-[#faf9f5] px-3 text-sm outline-none focus:border-[#145943] focus:ring-4 focus:ring-[#145943]/10"
                                        required
                                    >
                                </label>
                                <label class="block text-sm font-medium text-[#4d5c56]">
                                    City
                                    <input
                                        type="text"
                                        name="city"
                                        value="{{ old('city', 'Dhaka') }}"
                                        class="mt-2 h-11 w-full rounded-xl border border-[#e3e0d6] bg-[#faf9f5] px-3 text-sm outline-none focus:border-[#145943] focus:ring-4 focus:ring-[#145943]/10"
                                        required
                                    >
                                </label>
                                <label class="block text-sm font-medium text-[#4d5c56]">
                                    Area
                                    <input
                                        type="text"
                                        name="area"
                                        value="{{ old('area', 'Dhanmondi') }}"
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

                                <label class="flex cursor-pointer items-center justify-between rounded-2xl border border-[#dfe3cf] bg-[#f8f9f2] p-4">
                                    <div class="flex items-center gap-3">
                                        <input type="radio" name="payment_method" value="online" {{ old('payment_method') === 'online' ? 'checked' : '' }} class="h-4 w-4 accent-[#145943]">
                                        <div>
                                            <p class="font-semibold text-[#173b32]">Online Payment</p>
                                            <p class="text-xs text-[#738078]">Card, mobile banking, and digital wallet.</p>
                                        </div>
                                    </div>
                                    <span class="text-sm font-bold text-[#a48335]">Secure</span>
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
