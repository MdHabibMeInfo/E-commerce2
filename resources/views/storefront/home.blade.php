<x-layouts.app>

    <x-slot:title>
        {{ config('app.name') }} — Fresh &amp; Quality Food &amp; Grocery Products
    </x-slot:title>

    <x-slot:metaDescription>
        Shop fresh, natural and high-quality food and grocery products. Discover quality essentials and everyday products delivered to your door.
    </x-slot:metaDescription>

    <x-slot:canonicalUrl>
        {{ route('home') }}
    </x-slot:canonicalUrl>
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
                        <a href="{{ route('shop') }}" class="inline-flex items-center justify-center gap-3 rounded-full bg-[#145943] px-7 py-3.5 text-sm font-bold text-white shadow-lg shadow-[#145943]/15 transition hover:-translate-y-0.5 hover:bg-[#0f4d3a]">
                        Shop Now
                        <span aria-hidden="true">→</span>
                    </a>
                    <a href="{{ route('shop') }}" class="inline-flex items-center justify-center rounded-full border border-[#b99a4b] bg-white/40 px-7 py-3.5 text-sm font-bold text-[#145943] transition hover:bg-white">
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
    <section id="categories" class="border-y border-[#e8e6db] bg-white">
        <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
            <div class="flex items-end justify-between gap-4">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.18em] text-[#a48335]">Browse our collection</p>
                    <h2 class="mt-1 text-2xl font-extrabold tracking-tight text-[#145943] sm:text-3xl">Featured Categories</h2>
                </div>
            </div>

            <div class="relative mt-6">
                {{-- Previous category --}}
                <button
                    type="button"
                    data-category-prev
                    class="absolute left-0 top-1/2 z-20 grid h-10 w-10 -translate-x-1/3 -translate-y-1/2 place-items-center rounded-full border border-[#dfe2d6] bg-white text-[#145943] shadow-md transition-colors hover:border-[#a48335] hover:bg-[#a48335] hover:text-white disabled:pointer-events-none disabled:opacity-0"
                    aria-label="Previous categories"
                    disabled
                >
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path d="m15 18-6-6 6-6"></path>
                    </svg>
                </button>

                <div class="mx-8 overflow-hidden">
                    <div
                        data-category-track
                        class="flex gap-3 overflow-x-auto scroll-smooth pb-2 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden sm:gap-4"
                        aria-label="Featured categories"
                    >
                    @forelse ($categories as $category)
                        <a
                            href="{{ route('shop', ['category' => $category->slug]) }}"
                            data-carousel-item
                            class="category-card group flex shrink-0 cursor-pointer flex-col items-center gap-2 rounded-xl bg-transparent text-center"
                        >
                            <span
                                class="grid h-[92px] w-full place-items-center overflow-hidden rounded-2xl border border-[#e8e6db] bg-white shadow-sm sm:h-[104px]"
                            >
                                @if ($category->image)
                                    <img
                                        src="{{ asset('storage/' . $category->image) }}"
                                        alt="{{ $category->name }}"
                                        width="104"
                                        height="104"
                                        loading="lazy"
                                        decoding="async"
                                        class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105"
                                    >
                                @else
                                    <span
                                        class="text-[3.2rem] leading-none sm:text-[3.6rem]"
                                        aria-hidden="true"
                                    >
                                        🛒
                                    </span>
                                @endif
                            </span>

                            <span
                                class="category-name w-full px-1 text-xs font-medium leading-4 text-[#26352f] transition-colors duration-200 sm:text-sm"
                            >
                                {{ $category->name }}
                            </span>
                        </a>
                    @empty
                        <p class="px-4 py-8 text-sm text-[#738078]">
                            No categories available right now.
                        </p>
                    @endforelse
                    </div>
                </div>

                {{-- Next category --}}
                <button
                    type="button"
                    data-category-next
                    class="absolute right-0 top-1/2 z-20 grid h-10 w-10 translate-x-1/3 -translate-y-1/2 place-items-center rounded-full border border-[#dfe2d6] bg-white text-[#145943] shadow-md transition-colors hover:border-[#a48335] hover:bg-[#a48335] hover:text-white disabled:pointer-events-none disabled:opacity-0"
                    aria-label="Next categories"
                >
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path d="m9 18 6-6-6-6"></path>
                    </svg>
                </button>
            </div>
        </div>
    </section>

    {{-- Benefits --}}
    <section class="bg-[#f2f4e5]">
        <div class="mx-auto grid max-w-7xl grid-cols-2 divide-x divide-y divide-[#dfe3cf] px-4 sm:px-6 lg:grid-cols-4 lg:divide-y-0 lg:px-8">
            @foreach ($benefits as $benefit)
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

                <a href="{{ route('shop') }}" class="inline-flex items-center gap-2 text-sm font-bold text-[#145943] transition hover:text-[#a48335]">
                    View All Products
                    <span>→</span>
                </a>
            </div>

            <div class="relative mt-8">
                <div class="overflow-hidden">
                    <div data-product-track class="flex gap-3 overflow-x-auto scroll-smooth pb-2 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden sm:gap-4" aria-label="Featured products">
                    @forelse ($featuredProducts as $product)

                        @php
                            $primaryImage = $product->images->firstWhere('is_primary', true)
                                ?? $product->images->first();
                        @endphp

                        <article
                            data-carousel-item
                            data-product-card
                            data-product-name="{{ $product->name }}"
                            data-product-price="{{ (float) $product->price }}"
                            data-product-emoji="🛍️"
                            class="group relative shrink-0 overflow-hidden rounded-xl border border-[#e3e2d9] bg-white shadow-sm"
                        >

                            <a
                                href="{{ route('products.show', ['product' => $product->slug]) }}"
                                class="absolute inset-0 z-0 rounded-xl focus:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-[#145943]"
                                aria-label="View {{ $product->name }}"
                            ></a>

                            <div class="relative z-10 overflow-hidden bg-[#f4f5e9]">

                                @if ($primaryImage)
                                    <div class="aspect-square overflow-hidden">
                                        <img
                                            src="{{ asset('storage/' . $primaryImage->image_path) }}"
                                            alt="{{ $primaryImage->alt_text ?: $product->name }}"
                                            width="500"
                                            height="500"
                                            loading="lazy"
                                            decoding="async"
                                            class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105"
                                        >
                                    </div>
                                @else
                                    <div
                                        class="grid aspect-square place-items-center text-[5.5rem] leading-none sm:text-[7rem]"
                                        aria-hidden="true"
                                    >
                                        🛍️
                                    </div>
                                @endif

                            </div>

                            <div class="relative z-10 px-3 pb-3 pt-3 sm:px-4 sm:pb-4 sm:pt-4">

                                <h3 class="truncate text-sm font-bold text-[#173b32]">
                                    {{ $product->name }}
                                </h3>

                                @if ($product->short_description)
                                    <p class="mt-1 line-clamp-2 text-[11px] leading-4 text-[#7a847f]">
                                        {{ $product->short_description }}
                                    </p>
                                @endif

                                <div class="mt-3 flex items-center justify-between gap-2">

                                    @if ($product->price !== null)
                                        <span class="text-sm font-extrabold text-[#145943] sm:text-base">
                                            ৳{{ number_format((float) $product->price, 2) }}
                                        </span>
                                    @endif

                                    <button
                                        type="button"
                                        aria-label="Add {{ $product->name }} to cart"
                                        data-product-id="{{ $product->id }}"
                                        data-product-name="{{ $product->name }}"
                                        data-product-price="{{ (float) $product->price }}"
                                        data-product-emoji="🛍️"
                                        class="inline-flex items-center justify-center rounded-lg bg-[#145943] px-3 py-2 text-[10px] font-bold text-white transition hover:bg-[#a48335] sm:px-3.5 sm:text-xs"
                                    >
                                        Add to Cart
                                    </button>

                                </div>
                            </div>

                        </article>

                    @empty

                        <p class="py-10 text-center text-sm text-[#738078]">
                            No featured products available right now.
                        </p>

                    @endforelse
                    </div>
                </div>
                <div data-product-pagination class="flex items-center justify-center gap-2.5 pt-5" role="group" aria-label="Featured product pages"></div>
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
                <a href="{{ route('shop') }}" class="shrink-0 rounded-full bg-[#d7b45a] px-6 py-3.5 text-sm font-bold text-[#173b32] transition hover:bg-[#e4c779]">Start Shopping →</a>
            </div>
        </div>
    </section>

</x-layouts.app>
