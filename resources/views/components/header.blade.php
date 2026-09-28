<header class="relative z-50">
    {{-- Announcement bar --}}
    <div class="bg-[#145943] text-white">
        <div class="mx-auto flex max-w-[1600px] items-center justify-between gap-4 px-4 py-2 text-[11px] font-medium sm:px-6 sm:text-xs lg:px-8">
            <p class="flex items-center gap-2">
                <span class="text-[#d7b45a]">✦</span>
                Fresh &amp; Healthy Products
                <span class="hidden text-white/50 sm:inline">•</span>
                <span class="hidden sm:inline">Free Shipping on Orders Over ৳1000</span>
            </p>

            <div class="hidden items-center gap-4 md:flex">
                <a href="#" class="transition hover:text-[#e5cf8b]">Track Order</a>
                <span class="text-white/30">|</span>
                <a href="#" class="transition hover:text-[#e5cf8b]">Help</a>
                <span class="text-white/30">|</span>
                <a href="#" class="transition hover:text-[#e5cf8b]">Login / Register</a>
            </div>
        </div>
    </div>

    {{-- Main navigation --}}
    <div class="border-b border-[#e7e4d8] bg-[#fffef9]/95 backdrop-blur">
        <div class="mx-auto flex h-[78px] max-w-[1600px] items-center gap-4 px-4 sm:px-6 lg:px-8">

            <a href="{{ route('home') }}" class="flex shrink-0 items-center" aria-label="{{ config('app.name') }} home">
                <img
                    src="{{ asset('images/brand/ziyanmart-logo.png') }}"
                    alt="{{ config('app.name') }}"
                    class="h-12 w-auto object-contain sm:h-14"
                >
            </a>

            {{-- Search --}}
            <div class="hidden min-w-0 flex-1 lg:block lg:max-w-[430px] lg:mx-auto">
                <form action="{{ route('shop') }}" method="GET" class="relative">
                    <label for="header-search" class="sr-only">Search products</label>
                    <input
                        id="header-search"
                        type="search"
                        name="q"
                        value="{{ request('q') }}"
                        placeholder="Search for products..."
                        class="h-11 w-full rounded-full border border-[#e5e2d8] bg-white pl-4 pr-12 text-sm text-[#173b32] outline-none transition placeholder:text-[#9aa19c] focus:border-[#145943] focus:ring-4 focus:ring-[#145943]/10"
                    >
                    <button type="submit" aria-label="Search products" class="absolute right-1 top-1/2 grid h-9 w-9 -translate-y-1/2 place-items-center rounded-full text-[#7c8580] transition hover:bg-[#eef4e8] hover:text-[#145943]">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <circle cx="11" cy="11" r="6.5"></circle>
                            <path d="m16 16 4.2 4.2"></path>
                        </svg>
                    </button>
                </form>
            </div>

            {{-- Desktop navigation --}}
            <nav class="ml-auto hidden items-center gap-6 xl:flex" aria-label="Primary navigation">
                <a href="{{ route('home') }}" class="relative py-3 text-sm font-semibold text-[#145943] after:absolute after:bottom-0 after:left-0 after:h-0.5 after:w-full after:bg-[#b28d35]">
                    Home
                </a>
                <a href="{{ route('shop') }}" class="py-3 text-sm font-medium text-[#4d5c56] transition hover:text-[#145943]">Shop</a>
                <a href="{{ route('shop') }}" class="py-3 text-sm font-medium text-[#4d5c56] transition hover:text-[#145943]">Categories</a>
                <a href="#" class="py-3 text-sm font-medium text-[#4d5c56] transition hover:text-[#145943]">About</a>
                <a href="#" class="py-3 text-sm font-medium text-[#4d5c56] transition hover:text-[#145943]">Contact</a>
            </nav>

            {{-- Actions --}}
            <div class="ml-auto flex shrink-0 items-center gap-2 sm:gap-3 xl:ml-5">
                <a href="#" class="grid h-10 w-10 place-items-center rounded-full text-[#145943] transition hover:bg-[#eef4e8]" aria-label="Wishlist">
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
                        <path d="M20.8 8.7c0 5.2-8.8 10-8.8 10s-8.8-4.8-8.8-10A4.7 4.7 0 0 1 8 4c1.6 0 3 0.8 4 2.1A4.8 4.8 0 0 1 16 4a4.7 4.7 0 0 1 4.8 4.7Z"></path>
                    </svg>
                </a>

                <a href="#" data-open-cart class="relative grid h-10 w-10 place-items-center rounded-full text-[#145943] transition hover:bg-[#eef4e8]" aria-label="Shopping cart">
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
                        <path d="M3 4h2l2.1 10.1a2 2 0 0 0 2 1.6h7.7a2 2 0 0 0 1.9-1.4L20 8H6"></path>
                        <circle cx="9" cy="19" r="1.2"></circle>
                        <circle cx="18" cy="19" r="1.2"></circle>
                    </svg>
                    <span data-cart-count-badge class="absolute -right-0.5 -top-0.5 grid h-5 min-w-5 place-items-center rounded-full bg-[#145943] px-1 text-[10px] font-bold text-white">0</span>
                </a>

                <button
                    type="button"
                    data-mobile-menu-toggle
                    class="grid h-10 w-10 place-items-center rounded-full text-[#145943] transition hover:bg-[#eef4e8] xl:hidden"
                    aria-expanded="false"
                    aria-controls="mobile-navigation"
                    aria-label="Open menu"
                >
                    <svg data-menu-open-icon class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path d="M4 6h16M4 12h16M4 18h16"></path>
                    </svg>
                    <svg data-menu-close-icon class="hidden h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path d="m6 6 12 12M18 6 6 18"></path>
                    </svg>
                </button>
            </div>
        </div>

        {{-- Mobile navigation --}}
        <div id="mobile-navigation" data-mobile-menu class="hidden border-t border-[#e7e4d8] bg-[#fffef9] xl:hidden">
            <div class="mx-auto max-w-[1600px] px-4 py-4 sm:px-6">
                <form action="{{ route('shop') }}" method="GET" class="relative mb-4">
                    <label for="mobile-search" class="sr-only">Search products</label>
                    <input
                        id="mobile-search"
                        type="search"
                        name="q"
                        value="{{ request('q') }}"
                        placeholder="Search for products..."
                        class="h-11 w-full rounded-full border border-[#e5e2d8] bg-white pl-4 pr-12 text-sm outline-none focus:border-[#145943] focus:ring-4 focus:ring-[#145943]/10"
                    >
                    <button type="submit" aria-label="Search products" class="absolute right-1 top-1/2 grid h-9 w-9 -translate-y-1/2 place-items-center rounded-full text-[#7c8580] transition hover:bg-[#eef4e8] hover:text-[#145943]">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <circle cx="11" cy="11" r="6.5"></circle>
                            <path d="m16 16 4.2 4.2"></path>
                        </svg>
                    </button>
                </form>

                <nav class="grid gap-1" aria-label="Mobile navigation">
                    <a href="{{ route('home') }}" class="rounded-xl bg-[#eef4e8] px-4 py-3 text-sm font-semibold text-[#145943]">Home</a>
                    <a href="{{ route('shop') }}" class="rounded-xl px-4 py-3 text-sm font-medium text-[#4d5c56] hover:bg-[#f5f4ec]">Shop</a>
                    <a href="{{ route('shop') }}" class="rounded-xl px-4 py-3 text-sm font-medium text-[#4d5c56] hover:bg-[#f5f4ec]">Categories</a>
                    <a href="#" class="rounded-xl px-4 py-3 text-sm font-medium text-[#4d5c56] hover:bg-[#f5f4ec]">About</a>
                    <a href="#" class="rounded-xl px-4 py-3 text-sm font-medium text-[#4d5c56] hover:bg-[#f5f4ec]">Contact</a>
                    <div class="mt-2 flex gap-2 border-t border-[#e7e4d8] pt-3">
                        <a href="#" class="flex-1 rounded-xl border border-[#dedbd0] px-4 py-3 text-center text-sm font-semibold text-[#145943]">Track Order</a>
                        <a href="{{ route('checkout') }}" class="flex-1 rounded-xl bg-[#145943] px-4 py-3 text-center text-sm font-semibold text-white">Login / Register</a>
                    </div>
                </nav>
            </div>
        </div>
    </div>
</header>
