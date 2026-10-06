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

    @php
        $homeAdImagePath = $siteBranding['home_ad_image_path'] ?? null;
        $homeAdUrl = $siteBranding['home_ad_url'] ?? null;
        $showHomeAd = filled($homeAdImagePath) && filled($homeAdUrl);
    @endphp

    @if ($heroBanners->isNotEmpty())
        <div class="mx-auto max-w-[1600px] px-4 pt-4 sm:px-6 lg:px-8">
            <div class="grid items-stretch gap-4 {{ $showHomeAd ? 'lg:grid-cols-[minmax(0,2fr)_minmax(0,1fr)]' : '' }}">
                <div class="min-w-0">
                    <section data-hero-carousel class="relative h-[clamp(250px,25vw,420px)] w-full overflow-hidden bg-[#fbfaf4]" aria-label="Homepage hero images" aria-roledescription="carousel">
                        <div data-hero-viewport class="h-full overflow-hidden">
                            <div data-hero-track class="flex h-full transition-transform duration-500 ease-out motion-reduce:transition-none">
                                @foreach ($heroBanners as $index => $heroBanner)
                                    <article
                                        data-hero-slide
                                        aria-roledescription="slide"
                                        aria-label="{{ $index + 1 }} of {{ $heroBanners->count() }}"
                                        aria-hidden="{{ $index === 0 ? 'false' : 'true' }}"
                                        @if ($index !== 0) inert @endif
                                        class="relative h-full w-full shrink-0"
                                    >
                                        <a
                                            href="{{ $heroBannerDestinations[$heroBanner->id] ?? route('home') }}"
                                            aria-label="{{ $heroBannerDestinations[$heroBanner->id] ? 'Open selected destination' : 'Open homepage' }}"
                                            class="absolute inset-0 block"
                                        >
                                            <img
                                                src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($heroBanner->image_path) }}"
                                                alt=""
                                                @if ($index === 0) fetchpriority="high" @else loading="lazy" @endif
                                                decoding="async"
                                                class="h-full w-full object-contain"
                                            >
                                        </a>
                                    </article>
                                @endforeach
                            </div>
                        </div>

                        @if ($heroBanners->count() > 1)
                            <button type="button" data-hero-prev class="absolute left-3 top-1/2 z-10 grid h-10 w-10 -translate-y-1/2 place-items-center rounded-full bg-black/35 text-white transition hover:bg-black/60 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white" aria-label="Previous hero image">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="m15 18-6-6 6-6" /></svg>
                            </button>
                            <button type="button" data-hero-next class="absolute right-3 top-1/2 z-10 grid h-10 w-10 -translate-y-1/2 place-items-center rounded-full bg-black/35 text-white transition hover:bg-black/60 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white" aria-label="Next hero image">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="m9 18 6-6-6-6" /></svg>
                            </button>
                        @endif
                    </section>

                    @if ($heroBanners->count() > 1)
                        <nav data-hero-pagination class="flex items-center justify-center gap-1 bg-[#fbfaf4] py-1" aria-label="Choose hero image">
                            @foreach ($heroBanners as $index => $heroBanner)
                                <button
                                    type="button"
                                    data-hero-dot="{{ $index }}"
                                    aria-label="Show hero image {{ $index + 1 }}"
                                    aria-current="{{ $index === 0 ? 'true' : 'false' }}"
                                    class="grid h-8 w-8 place-items-center rounded-full focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#145943]"
                                >
                                    <span class="h-2.5 {{ $index === 0 ? 'w-6 bg-[#145943]' : 'w-2.5 bg-[#c6c8b9]' }} rounded-full transition-all duration-300" aria-hidden="true"></span>
                                </button>
                            @endforeach
                        </nav>
                    @endif
                </div>

                @if ($showHomeAd)
                    <a href="{{ $homeAdUrl }}" target="_blank" rel="noopener noreferrer" aria-label="Open advertisement" class="hidden h-[clamp(250px,25vw,420px)] overflow-hidden bg-[#fbfaf4] lg:block">
                        <img
                            src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($homeAdImagePath) }}"
                            alt="Advertisement"
                            loading="lazy"
                            decoding="async"
                            class="h-full w-full object-contain"
                        >
                    </a>
                @endif
            </div>
        </div>
    @endif

    {{-- Categories --}}
    <section id="categories" class="border-t border-[#e8e6db] bg-[#fbfaf4]">
        <div class="mx-auto max-w-[1600px] px-4 pt-8 pb-10 sm:px-6 lg:px-8">
            <h2 class="text-center text-xl font-medium text-[#26352f] sm:text-2xl">Featured Categories</h2>

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
                                class="grid aspect-square w-full place-items-center overflow-hidden rounded-xl bg-white"
                            >
                                @if ($category->image)
                                    <img
                                        src="/storage/{{ ltrim($category->image, '/') }}"
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
                                class="category-name w-full px-1 text-sm font-medium leading-5 text-[#26352f] transition-colors duration-200 sm:text-base"
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
                    class="absolute right-0 top-1/2 z-20 grid h-10 w-10 translate-x-1/3 -translate-y-1/2 place-items-center rounded-full border border-[#dfe2d6] bg-white text-[#145943] transition-colors hover:border-[#a48335] hover:bg-[#a48335] hover:text-white disabled:pointer-events-none disabled:opacity-0"
                    aria-label="Next categories"
                >
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path d="m9 18 6-6-6-6"></path>
                    </svg>
                </button>
            </div>
        </div>
    </section>

    {{-- Top selling products --}}
    @if ($topSellingProducts->isNotEmpty())
        <section id="top-selling-products" class="bg-[#fbfaf4]">
            <div class="mx-auto max-w-[1600px] px-4 pt-0 pb-8 sm:px-6 sm:pb-10 lg:px-8">
                <h2 class="mb-6 text-center text-2xl font-semibold text-[#26352f] sm:text-3xl">Top Selling Products</h2>

                <div class="grid grid-cols-2 gap-3 sm:gap-4 md:grid-cols-2 md:gap-5">
                    @foreach ($topSellingProducts as $product)
                        @php
                            $primaryImage = $product->primaryImage ?? $product->images->firstWhere('is_primary', true) ?? $product->images->first();
                        @endphp

                        <article class="group relative flex min-w-0 flex-col overflow-hidden rounded-[4px] border border-[#d1d5ce] bg-white md:grid md:min-h-[270px] md:grid-cols-[48%_minmax(0,1fr)] md:rounded-sm md:border-0 lg:min-h-[300px]">
                            <span class="absolute right-2 top-2 z-10 rounded-[3px] bg-[#ef4938] px-2 py-1 text-[10px] font-bold text-white">Best Selling</span>

                            <a href="{{ route('products.show', ['product' => $product->slug]) }}" class="flex aspect-square w-full items-center justify-center overflow-hidden bg-white md:h-full md:aspect-auto md:min-h-0" aria-label="View {{ $product->name }}">
                                @if ($primaryImage)
                                    <img
                                        src="/storage/{{ ltrim($primaryImage->image_path, '/') }}"
                                        alt="{{ $primaryImage->alt_text ?: $product->name }}"
                                        width="500"
                                        height="500"
                                        loading="lazy"
                                        decoding="async"
                                        class="h-full w-full object-contain transition-transform duration-300 group-hover:scale-110"
                                    >
                                @else
                                    <span class="text-5xl sm:text-7xl" aria-hidden="true">🛍️</span>
                                @endif
                            </a>

                            <div class="flex min-w-0 flex-col px-3 pt-3 pb-2 sm:px-4 md:justify-center md:px-6 md:py-8 max-sm:flex-1">
                                <a href="{{ route('products.show', ['product' => $product->slug]) }}" class="line-clamp-2 text-sm font-normal leading-5 text-[#173b32] hover:text-[#145943] md:text-lg md:font-semibold md:leading-6">
                                    {{ $product->name }}
                                </a>
                                @include('storefront.partials.product-price', [
                                    'product' => $product,
                                    'containerClass' => 'mt-1 flex flex-wrap items-baseline justify-between gap-x-2 gap-y-1 md:mt-2',
                                    'priceClass' => 'text-sm font-extrabold text-[#145943] md:text-base',
                                    'comparePriceClass' => 'text-xs text-[#9aa19c] line-through',
                                ])

                                <div class="mt-3 flex flex-col gap-2.5 md:mt-5 md:flex-row md:flex-wrap max-sm:mt-auto">
                                    @if ($product->is_out_of_stock || ($product->type === 'variable' && ! $product->variants->contains(fn ($variant) => $variant->is_active && ! $variant->is_out_of_stock)))
                                        <button type="button" disabled aria-label="{{ $product->name }} is out of stock" class="inline-flex h-10 w-full cursor-not-allowed items-center justify-center rounded-[4px] border border-[#dfe3cf] bg-[#f4f1ea] px-3 text-sm font-semibold text-[#738078] md:w-auto md:rounded-sm md:px-4">
                                            Out of stock
                                        </button>
                                    @else
                                    <button
                                        type="button"
                                        aria-label="Add {{ $product->name }} to cart"
                                        data-product-id="{{ $product->id }}"
                                        data-product-name="{{ $product->name }}"
                                        data-product-price="{{ (float) $product->price }}"
                                        data-product-type="{{ $product->type }}"
                                        data-product-url="{{ route('products.show', ['product' => $product->slug]) }}"
                                        @if ($product->type === 'variable') data-select-variant @endif
                                        data-product-image="{{ $primaryImage ? '/storage/' . ltrim($primaryImage->image_path, '/') : '' }}"
                                        data-product-emoji="🛍️"
                                        class="inline-flex h-10 w-full items-center justify-center gap-2 rounded-[4px] border border-[#145943] px-3 text-sm font-semibold text-[#145943] transition-all duration-200 hover:bg-[#145943] hover:text-white md:w-auto md:rounded-sm md:px-4"
                                    >
                                        <svg data-cart-button-icon class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                            <path d="M3 3h2l2.4 12.1a2 2 0 0 0 2 1.6h8.8a2 2 0 0 0 2-1.6L22 7H6" />
                                            <circle cx="9" cy="21" r="1" />
                                            <circle cx="19" cy="21" r="1" />
                                        </svg>
                                        <svg data-cart-button-spinner class="hidden h-4 w-4 shrink-0 motion-safe:animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                            <circle class="opacity-25" cx="12" cy="12" r="9" stroke="currentColor" stroke-width="3" />
                                            <path class="opacity-90" fill="currentColor" d="M12 3a9 9 0 0 1 9 9h-3a6 6 0 0 0-6-6V3Z" />
                                        </svg>
                                        <svg data-cart-button-success class="hidden h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true">
                                            <path d="m5 12 4 4L19 6" />
                                        </svg>
                                        <span data-cart-button-label>{{ $product->type === 'variable' ? 'Choose options' : 'Add to Cart' }}</span>
                                    </button>
                                    <button
                                        type="button"
                                        aria-label="Buy {{ $product->name }} now"
                                        data-buy-now
                                        data-product-id="{{ $product->id }}"
                                        data-product-name="{{ $product->name }}"
                                        data-product-price="{{ (float) $product->price }}"
                                        data-product-type="{{ $product->type }}"
                                        data-product-url="{{ route('products.show', ['product' => $product->slug]) }}"
                                        @if ($product->type === 'variable') data-select-variant @endif
                                        data-product-image="{{ $primaryImage ? '/storage/' . ltrim($primaryImage->image_path, '/') : '' }}"
                                        data-product-emoji="🛍️"
                                        class="inline-flex h-10 w-full items-center justify-center rounded-[4px] border border-[#a48335] bg-[#a48335] px-3 text-sm font-semibold text-white transition-colors hover:border-[#145943] hover:bg-[#145943] md:w-auto md:rounded-sm md:px-4"
                                    >
                                        Buy now
                                    </button>
                                    @endif
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- New arrivals --}}
    <section id="new-arrivals" class="bg-[#fbfaf4]">
        <div class="mx-auto max-w-[1600px] px-4 pt-12 pb-4 sm:px-6 sm:pt-16 sm:pb-4 lg:px-8">
            <div class="flex items-center justify-between gap-3 border-b border-[#e7e2d4] pb-2">
                <h2 class="text-xl font-extrabold tracking-tight text-[#26352f]">New Arrivals</h2>
                <a href="{{ route('shop') }}" class="inline-flex items-center gap-2 text-sm font-bold text-[#145943] transition hover:text-[#a48335]">
                    View All Products
                    <span>→</span>
                </a>
            </div>

            <div class="relative mt-4">
                <div class="overflow-hidden">
                    <div data-product-track class="flex gap-3 overflow-x-auto scroll-smooth pb-2 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden sm:gap-4" aria-label="New arrivals">
                        @forelse ($newArrivals as $product)
                            @include('storefront.partials.home-product-card', ['product' => $product])
                        @empty
                            <p class="py-10 text-center text-sm text-[#738078]">No new arrivals available right now.</p>
                        @endforelse
                    </div>
                </div>
                <div data-product-pagination class="flex items-center justify-center gap-2.5 pt-5" role="group" aria-label="New arrivals pages"></div>
            </div>
        </div>
    </section>

    {{-- Additional category product rows --}}
    <section class="bg-[#fbfaf4]">
        <div class="mx-auto max-w-[1600px] px-4 pt-4 pb-12 sm:px-6 lg:px-8">
            @foreach ($categoryRows as $category)
                @php
                    $categoryProducts = $category->products->take(10);
                @endphp

                @if ($categoryProducts->isNotEmpty())
                    <div class="mb-8">
                        <div class="flex items-center justify-between gap-3 border-b border-[#e7e2d4] pb-2">
                            <h3 class="text-xl font-extrabold tracking-tight text-[#26352f]">{{ $category->name }}</h3>
                            <a href="{{ route('shop', ['category' => $category->slug]) }}" class="inline-flex items-center gap-2 text-sm font-bold text-[#145943] transition hover:text-[#a48335]">
                                View All
                                <span>→</span>
                            </a>
                        </div>

                        <div class="relative mt-4">
                            <div class="overflow-hidden">
                                <div data-category-product-track="{{ $category->slug }}" class="flex gap-3 overflow-x-auto scroll-smooth pb-2 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden sm:gap-4" aria-label="{{ $category->name }} products">
                                    @foreach ($categoryProducts as $product)
                                        @php
                                            $primaryImage = $product->primaryImage ?? $product->images->firstWhere('is_primary', true) ?? $product->images->first();
                                        @endphp

                                        <article
                                            data-carousel-item
                                            class="group relative flex shrink-0 flex-col overflow-hidden rounded-[4px] border border-[#e9e5db] bg-white shadow-none"
                                        >
                                            <a href="{{ route('products.show', ['product' => $product->slug]) }}" class="flex flex-1 flex-col">
                                                <div class="overflow-hidden bg-white">
                                                    @if ($primaryImage)
                                                        <div class="aspect-square overflow-hidden">
                                                            <img
                                                                src="/storage/{{ ltrim($primaryImage->image_path, '/') }}"
                                                                alt="{{ $primaryImage->alt_text ?: $product->name }}"
                                                                width="500"
                                                                height="500"
                                                                loading="lazy"
                                                                decoding="async"
                                                                class="h-full w-full object-contain transition-transform duration-300 group-hover:scale-105"
                                                            >
                                                        </div>
                                                    @else
                                                        <div class="grid aspect-square place-items-center text-[3.5rem] leading-none" aria-hidden="true">🛍️</div>
                                                    @endif
                                                </div>

                                                <div class="flex flex-1 flex-col px-3 pt-3 sm:px-4 sm:pt-4">
                                                    <h4 class="line-clamp-2 text-sm font-normal leading-5 text-[#173b32] sm:text-base">
                                                        {{ $product->name }}
                                                    </h4>

                                                    @include('storefront.partials.product-price', [
                                                        'product' => $product,
                                                        'containerClass' => 'flex flex-wrap items-baseline justify-between gap-x-2 gap-y-1 pt-2',
                                                        'priceClass' => 'text-sm font-extrabold text-[#145943] sm:text-base',
                                                        'comparePriceClass' => 'text-xs text-[#9aa19c] line-through sm:text-sm',
                                                    ])
                                                </div>
                                            </a>

                                            <div class="mt-auto px-3 pb-2 pt-2 sm:px-4 sm:pb-4 sm:pt-2">
                                                @if ($product->is_out_of_stock || ($product->type === 'variable' && ! $product->variants->contains(fn ($variant) => $variant->is_active && ! $variant->is_out_of_stock)))
                                                    <button type="button" disabled aria-label="{{ $product->name }} is out of stock" class="inline-flex h-10 w-full cursor-not-allowed items-center justify-center rounded-[4px] border border-[#dfe3cf] bg-[#f4f1ea] px-3 text-sm font-bold text-[#738078]">
                                                        Out of stock
                                                    </button>
                                                @else
                                                    <button
                                                        type="button"
                                                        aria-label="Add {{ $product->name }} to cart"
                                                        data-product-id="{{ $product->id }}"
                                                        data-product-name="{{ $product->name }}"
                                                        data-product-price="{{ (float) $product->price }}"
                                                        data-product-type="{{ $product->type }}"
                                                        data-product-url="{{ route('products.show', ['product' => $product->slug]) }}"
                                                        @if ($product->type === 'variable') data-select-variant @endif
                                                        data-product-image="{{ $primaryImage ? '/storage/' . ltrim($primaryImage->image_path, '/') : '' }}"
                                                        data-product-emoji="🛍️"
                                                        class="inline-flex h-10 w-full items-center justify-center gap-2 rounded-[4px] border border-[#145943] bg-white px-3 text-sm font-bold text-[#145943] transition-all duration-200 hover:bg-[#145943] hover:text-white"
                                                    >
                                                        <svg data-cart-button-icon class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                                            <path d="M3 3h2l2.4 12.1a2 2 0 0 0 2 1.6h8.8a2 2 0 0 0 2-1.6L22 7H6" />
                                                            <circle cx="9" cy="21" r="1" />
                                                            <circle cx="19" cy="21" r="1" />
                                                        </svg>
                                                        <svg data-cart-button-spinner class="hidden h-4 w-4 shrink-0 motion-safe:animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                                            <circle class="opacity-25" cx="12" cy="12" r="9" stroke="currentColor" stroke-width="3" />
                                                            <path class="opacity-90" fill="currentColor" d="M12 3a9 9 0 0 1 9 9h-3a6 6 0 0 0-6-6V3Z" />
                                                        </svg>
                                                        <svg data-cart-button-success class="hidden h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true">
                                                            <path d="m5 12 4 4L19 6" />
                                                        </svg>
                                                        <span data-cart-button-label>{{ $product->type === 'variable' ? 'Choose options' : 'Add to Cart' }}</span>
                                                    </button>
                                                @endif
                                            </div>
                                        </article>
                                    @endforeach
                                </div>
                            </div>

                            <div data-category-product-pagination="{{ $category->slug }}" class="flex items-center justify-center gap-2.5 pt-5" role="group" aria-label="{{ $category->name }} product pages"></div>
                        </div>
                    </div>
                @endif
            @endforeach
        </div>
    </section>

    {{-- Site-selected recommendations --}}
    <section id="just-for-you" class="bg-[#fbfaf4]">
        <div class="mx-auto max-w-[1600px] px-4 pt-8 pb-12 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between gap-3 border-b border-[#e7e2d4] pb-2">
                <h2 class="text-xl font-extrabold tracking-tight text-[#26352f]">Just For You</h2>
                <a href="{{ route('shop') }}" class="inline-flex items-center gap-2 text-sm font-bold text-[#145943] transition hover:text-[#a48335]">
                    View All Products
                    <span>→</span>
                </a>
            </div>

            <div class="relative mt-4">
                <div class="overflow-hidden">
                    <div data-just-for-you-track class="flex gap-3 overflow-x-auto scroll-smooth pb-2 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden sm:gap-4" aria-label="Just For You products">
                        @forelse ($justForYouProducts as $product)
                            @include('storefront.partials.home-product-card', ['product' => $product])
                        @empty
                            <p class="py-10 text-center text-sm text-[#738078]">No product recommendations available right now.</p>
                        @endforelse
                    </div>
                </div>
                <div data-just-for-you-pagination class="flex items-center justify-center gap-2.5 pt-5" role="group" aria-label="Just For You product pages"></div>
            </div>
        </div>
    </section>

    <section id="customer-reviews" class="border-t border-[#e8e6db] bg-[#fbfaf4]">
        <div class="mx-auto max-w-[1600px] px-4 py-10 sm:px-6 lg:px-8">
            @forelse ($approvedReviews as $review)
                @if ($loop->first)
                    <div class="relative mt-5">
                        <div class="overflow-hidden">
                            <div data-home-review-track class="flex gap-4 overflow-x-auto scroll-smooth pb-2 pr-px [scrollbar-width:none] [&::-webkit-scrollbar]:hidden" role="list" aria-label="Customer reviews">
                @endif
                                <article data-carousel-item role="listitem" class="flex min-h-52 flex-col rounded-[4px] border border-[#e5e2d9] bg-white p-5 shadow-sm">
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="flex min-w-0 items-center gap-3">
                                            @if ($review->reviewer?->profile_photo_path)
                                                <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($review->reviewer->profile_photo_path) }}" alt="{{ $review->name }} profile photo" class="h-10 w-10 shrink-0 rounded-full border border-[#dce6ce] object-cover">
                                            @else
                                                <span role="img" aria-label="Avatar for {{ $review->name }}" class="grid h-10 w-10 shrink-0 place-items-center rounded-full bg-[#edf2e4] text-sm font-bold text-[#145943]">{{ strtoupper(substr(trim($review->name), 0, 1)) }}</span>
                                            @endif
                                            <span class="min-w-0">
                                                <span class="block truncate text-sm font-bold text-[#173b32]">{{ $review->name }}</span>
                                            </span>
                                        </div>
                                        <span class="shrink-0 text-sm leading-none text-[#d9a700]" aria-label="{{ $review->rating }} out of 5 stars">
                                            @for ($star = 1; $star <= 5; $star++)
                                                <span aria-hidden="true">{{ $star <= $review->rating ? '★' : '☆' }}</span>
                                            @endfor
                                        </span>
                                    </div>
                                    <p class="mt-4 whitespace-pre-line break-words text-sm leading-6 text-[#59675f]">{{ $review->body }}</p>
                                    <div class="mt-auto pt-5">
                                        <p class="text-[10px] font-bold uppercase tracking-[0.14em] text-[#738078]">Review for</p>
                                        <a href="{{ route('products.show', ['product' => $review->product->slug]) }}" class="mt-1 flex items-center justify-between gap-3 rounded-[4px] border border-[#dce6ce] bg-[#f7f9f1] px-3 py-2 text-sm font-bold text-[#145943] transition hover:border-[#145943]">
                                            <span class="min-w-0 break-words">{{ $review->product->name }}</span>
                                            <span aria-hidden="true">&rarr;</span>
                                        </a>
                                    </div>
                                </article>
                @if ($loop->last)
                            </div>
                        </div>
                        <div data-home-review-pagination class="flex items-center justify-center gap-2.5 pt-4" role="group" aria-label="Customer review pages"></div>
                    </div>
                @endif
            @empty
                <p class="py-6 text-sm text-[#738078]">Customer reviews will appear here after they are approved.</p>
            @endforelse
        </div>
    </section>

</x-layouts.app>
