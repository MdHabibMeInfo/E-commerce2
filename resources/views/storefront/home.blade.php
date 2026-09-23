<x-layouts.app>

    <x-slot:title>
        {{ config('app.name') }} — Fresh &amp; Quality Products
    </x-slot:title>

    {{-- Hero --}}
    <section class="relative overflow-hidden bg-[#f0f2dc]">
        <div class="absolute -left-20 top-10 h-48 w-48 rounded-full bg-[#d9dfbd]/50 blur-3xl"></div>
        <div class="absolute -right-20 bottom-0 h-64 w-64 rounded-full bg-[#d7b45a]/10 blur-3xl"></div>

        <div class="relative mx-auto grid max-w-7xl items-center gap-8 px-4 py-12 sm:px-6 sm:py-16 lg:grid-cols-2 lg:px-8 lg:py-20">
            <div class="max-w-xl">
                <div class="inline-flex items-center gap-2 rounded-full bg-[#dce7c6] px-4 py-2 text-xs font-bold text-[#145943]">
                    <span>✦</span>
                    Fresh
                    <span class="text-[#9a7a2e]">•</span>
                    Natural
                    <span class="text-[#9a7a2e]">•</span>
                    Organic
                </div>

                <h1 class="mt-5 text-4xl font-extrabold leading-[1.05] tracking-tight text-[#145943] sm:text-5xl lg:text-6xl">
                    Good Food<br>
                    for a <span class="text-[#a48335]">Better You</span>
                </h1>

                <p class="mt-5 max-w-lg text-base leading-7 text-[#56665f] sm:text-lg">
                    Discover fresh, high-quality products that bring health and happiness to your everyday life.
                </p>

                <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                    <a href="#featured-products" class="inline-flex items-center justify-center gap-3 rounded-full bg-[#145943] px-7 py-3.5 text-sm font-bold text-white shadow-lg shadow-[#145943]/15 transition hover:-translate-y-0.5 hover:bg-[#0f4d3a]">
                        Shop Now
                        <span aria-hidden="true">→</span>
                    </a>
                    <a href="#categories" class="inline-flex items-center justify-center rounded-full border border-[#b99a4b] bg-white/40 px-7 py-3.5 text-sm font-bold text-[#145943] transition hover:bg-white">
                        Explore Categories
                    </a>
                </div>
            </div>

            <div class="relative min-h-[300px] sm:min-h-[390px] lg:min-h-[460px]">
                <div class="absolute inset-x-4 bottom-4 h-36 rounded-[50%] bg-[#cbbf92]/30 blur-2xl sm:inset-x-10"></div>

                <div class="absolute left-1/2 top-1/2 grid w-[90%] max-w-[520px] -translate-x-1/2 -translate-y-1/2 grid-cols-2 gap-3 sm:gap-4">
                    <div class="rounded-[30px] bg-[#e8d4a1]/70 p-4 shadow-xl shadow-[#5f633f]/10 backdrop-blur sm:p-6">
                        <div class="grid aspect-square place-items-center rounded-[24px] bg-[#f7f3dc] text-7xl sm:text-8xl">🥬</div>
                    </div>
                    <div class="mt-8 rounded-[30px] bg-white/75 p-4 shadow-xl shadow-[#5f633f]/10 backdrop-blur sm:mt-12 sm:p-6">
                        <div class="grid aspect-square place-items-center rounded-[24px] bg-[#f6efd7] text-7xl sm:text-8xl">🫙</div>
                    </div>
                    <div class="-mt-4 rounded-[30px] bg-white/75 p-4 shadow-xl shadow-[#5f633f]/10 backdrop-blur sm:-mt-6 sm:p-6">
                        <div class="grid aspect-square place-items-center rounded-[24px] bg-[#edf1d9] text-7xl sm:text-8xl">🥜</div>
                    </div>
                    <div class="rounded-[30px] bg-[#dfe9c7]/80 p-4 shadow-xl shadow-[#5f633f]/10 backdrop-blur sm:p-6">
                        <div class="grid aspect-square place-items-center rounded-[24px] bg-[#f8f5e8] text-7xl sm:text-8xl">🍅</div>
                    </div>
                </div>

                <div class="absolute right-0 top-3 grid h-24 w-24 place-items-center rounded-full border-2 border-[#145943] bg-[#145943] text-center text-sm font-bold leading-5 text-white shadow-lg sm:h-28 sm:w-28 sm:text-base">
                    100%<br>
                    <span class="font-medium italic">Natural</span>
                </div>
            </div>
        </div>

        <div class="relative flex justify-center gap-2 pb-5">
            <span class="h-2 w-8 rounded-full bg-[#145943]"></span>
            <span class="h-2 w-2 rounded-full bg-[#c6c8b9]"></span>
            <span class="h-2 w-2 rounded-full bg-[#c6c8b9]"></span>
        </div>
    </section>

    {{-- Categories --}}
    <section id="categories" class="border-b border-[#e8e6db] bg-white">
        <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
            <div class="grid gap-6 lg:grid-cols-[150px_1fr] lg:items-center">
                <div>
                    <h2 class="text-2xl font-extrabold leading-tight text-[#145943]">Shop by<br class="hidden lg:block"> Category</h2>
                    <p class="mt-2 text-sm leading-5 text-[#738078]">Find what you need, all in one place.</p>
                </div>

                <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-6">
                    @foreach ([
                        ['icon' => '🥬', 'name' => 'Fresh Produce'],
                        ['icon' => '🛒', 'name' => 'Groceries'],
                        ['icon' => '🥤', 'name' => 'Beverages'],
                        ['icon' => '🥜', 'name' => 'Snacks'],
                        ['icon' => '🌿', 'name' => 'Organic & Healthy'],
                        ['icon' => '🧴', 'name' => 'Household'],
                    ] as $category)
                        <a href="#" class="group flex flex-col items-center gap-3 rounded-2xl p-3 text-center transition hover:-translate-y-1 hover:bg-[#f5f6e9]">
                            <span class="grid h-16 w-16 place-items-center rounded-full bg-[#edf1d9] text-3xl shadow-sm transition group-hover:bg-[#e0e8c5]">
                                {{ $category['icon'] }}
                            </span>
                            <span class="text-xs font-bold leading-5 text-[#145943] sm:text-sm">{{ $category['name'] }}</span>
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    {{-- Benefits --}}
    <section class="bg-[#f2f4e5]">
        <div class="mx-auto grid max-w-7xl grid-cols-2 divide-x divide-y divide-[#dfe3cf] px-4 sm:px-6 lg:grid-cols-4 lg:divide-y-0 lg:px-8">
            @foreach ([
                ['icon' => '🚚', 'title' => 'Free Shipping', 'text' => 'On orders over ৳1000'],
                ['icon' => '🌿', 'title' => 'Fresh & Natural', 'text' => 'Quality products'],
                ['icon' => '🛡️', 'title' => 'Secure Payment', 'text' => '100% secure transactions'],
                ['icon' => '◉', 'title' => '24/7 Support', 'text' => 'We are here to help'],
            ] as $benefit)
                <div class="flex items-center gap-3 px-3 py-6 sm:gap-4 sm:px-6 lg:py-7">
                    <span class="grid h-12 w-12 shrink-0 place-items-center rounded-full bg-[#e0e8c5] text-xl sm:h-14 sm:w-14 sm:text-2xl">{{ $benefit['icon'] }}</span>
                    <div>
                        <h2 class="text-xs font-extrabold text-[#145943] sm:text-sm">{{ $benefit['title'] }}</h2>
                        <p class="mt-1 text-[10px] leading-4 text-[#738078] sm:text-xs">{{ $benefit['text'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </section>

    {{-- Featured products --}}
    <section id="featured-products" class="bg-[#fbfaf4]">
        <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 sm:py-16 lg:px-8">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.18em] text-[#a48335]">Handpicked for you</p>
                    <h2 class="mt-1 text-3xl font-extrabold tracking-tight text-[#145943] sm:text-4xl">Featured Products</h2>
                    <p class="mt-2 text-sm text-[#738078]">Quality products selected for your everyday needs.</p>
                </div>
                <a href="#" class="inline-flex items-center gap-2 text-sm font-bold text-[#145943] transition hover:text-[#a48335]">View All Products <span>→</span></a>
            </div>

            <div class="mt-8 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-5">
                @foreach ([
                    ['badge' => 'Organic', 'badgeClass' => 'bg-[#145943]', 'emoji' => '🍅', 'name' => 'Fresh Tomatoes', 'rating' => '4.8', 'reviews' => '124', 'price' => '৳120/kg'],
                    ['badge' => 'Best Seller', 'badgeClass' => 'bg-[#a48335]', 'emoji' => '🌾', 'name' => 'Organic Rice (5kg)', 'rating' => '4.7', 'reviews' => '98', 'price' => '৳450'],
                    ['badge' => 'New', 'badgeClass' => 'bg-[#145943]', 'emoji' => '🥜', 'name' => 'Almonds (500g)', 'rating' => '4.9', 'reviews' => '210', 'price' => '৳650'],
                    ['badge' => 'Popular', 'badgeClass' => 'bg-[#145943]', 'emoji' => '🍵', 'name' => 'Green Tea (50 Bags)', 'rating' => '4.6', 'reviews' => '166', 'price' => '৳320'],
                    ['badge' => 'Sale', 'badgeClass' => 'bg-[#d84c43]', 'emoji' => '🫒', 'name' => 'Pure Mustard Oil (1L)', 'rating' => '4.5', 'reviews' => '89', 'price' => '৳280'],
                ] as $product)
                    <article class="group overflow-hidden rounded-2xl border border-[#e7e5da] bg-white shadow-sm transition duration-300 hover:-translate-y-1 hover:shadow-xl hover:shadow-[#145943]/8">
                        <div class="relative p-3 sm:p-4">
                            <span class="absolute left-3 top-3 z-10 rounded-md px-2 py-1 text-[9px] font-bold text-white sm:left-4 sm:top-4 sm:text-[10px] {{ $product['badgeClass'] }}">
                                {{ $product['badge'] }}
                            </span>
                            <button type="button" class="absolute right-3 top-3 z-10 grid h-8 w-8 place-items-center rounded-full bg-white text-[#145943] shadow-sm transition hover:bg-[#eef4e8] sm:right-4 sm:top-4" aria-label="Add {{ $product['name'] }} to wishlist">
                                ♡
                            </button>
                            <div class="grid aspect-square place-items-center overflow-hidden rounded-xl bg-[#f5f5e7] text-6xl transition duration-300 group-hover:scale-[1.02] sm:text-7xl">
                                {{ $product['emoji'] }}
                            </div>
                        </div>

                        <div class="px-3 pb-4 sm:px-4">
                            <h3 class="truncate text-sm font-bold text-[#173b32]">{{ $product['name'] }}</h3>
                            <div class="mt-1 flex items-center gap-1 text-[11px] text-[#7a847f]">
                                <span class="text-[#d39d22]">★</span>
                                <span class="font-semibold text-[#53615b]">{{ $product['rating'] }}</span>
                                <span>({{ $product['reviews'] }})</span>
                            </div>
                            <div class="mt-3 flex items-center justify-between gap-2">
                                <span class="text-sm font-extrabold text-[#145943] sm:text-base">{{ $product['price'] }}</span>
                                <button type="button" class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-[#145943] text-white transition hover:bg-[#0f4d3a]" aria-label="Add {{ $product['name'] }} to cart">
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                        <path d="M3 4h2l2.1 10.1a2 2 0 0 0 2 1.6h7.7a2 2 0 0 0 1.9-1.4L20 8H6"></path>
                                        <circle cx="9" cy="19" r="1.2"></circle>
                                        <circle cx="18" cy="19" r="1.2"></circle>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    {{-- CTA --}}
    <section class="px-4 pb-12 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-7xl overflow-hidden rounded-[28px] bg-[#145943] px-6 py-10 text-white sm:px-10 lg:px-14 lg:py-12">
            <div class="flex flex-col items-start justify-between gap-6 md:flex-row md:items-center">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.18em] text-[#e2c775]">Shop smarter</p>
                    <h2 class="mt-2 text-2xl font-extrabold sm:text-3xl">Fresh choices, delivered to your door.</h2>
                    <p class="mt-2 max-w-2xl text-sm leading-6 text-white/70">Explore our complete collection and find everything you need in one place.</p>
                </div>
                <a href="#" class="shrink-0 rounded-full bg-[#d7b45a] px-6 py-3.5 text-sm font-bold text-[#173b32] transition hover:bg-[#e4c779]">Start Shopping →</a>
            </div>
        </div>
    </section>

</x-layouts.app>
