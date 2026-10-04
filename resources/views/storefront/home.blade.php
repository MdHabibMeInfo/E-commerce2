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
        $heroProducts = $featuredProducts->take(3);
        $hasCustomHero = filled($siteBranding['hero_image_path']);
        $heroSlideCount = max(1, $heroProducts->count() + ($hasCustomHero ? 1 : 0));
    @endphp
    <section data-hero-carousel class="relative overflow-hidden bg-[#f0f2dc]" aria-label="Featured products" aria-roledescription="carousel">
        <div class="relative mx-auto max-w-[1600px]">
            <div data-hero-viewport class="overflow-hidden">
                <div data-hero-track class="flex transition-transform duration-500 ease-out motion-reduce:transition-none">
                    @if ($hasCustomHero)
                        <article
                            data-hero-slide
                            aria-roledescription="slide"
                            aria-label="1 of {{ $heroSlideCount }}"
                            aria-hidden="false"
                            class="grid min-h-[380px] w-full shrink-0 items-center gap-8 px-4 py-10 sm:min-h-[420px] sm:px-6 lg:min-h-[480px] lg:grid-cols-2 lg:px-8 lg:py-14"
                        >
                            <div class="max-w-xl">
                                <p class="text-xs font-bold uppercase tracking-[0.14em] text-[#a48335]">Fresh · Natural · Organic</p>
                                <h1 class="mt-4 text-4xl font-extrabold leading-[1.05] text-[#145943] sm:text-5xl lg:text-6xl">{{ $siteBranding['hero_title'] }}</h1>
                                <p class="mt-5 max-w-lg text-base leading-7 text-[#56665f] sm:text-lg">{{ $siteBranding['hero_description'] }}</p>
                            </div>
                            <a href="{{ $heroDestination ?? route('home') }}" aria-label="{{ $siteBranding['hero_title'] }}" class="group relative mx-auto flex min-h-[240px] w-full max-w-2xl items-center justify-center overflow-hidden bg-[#e4ead5] sm:min-h-[300px] lg:min-h-[390px]">
                                <img
                                    src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($siteBranding['hero_image_path']) }}"
                                    alt="{{ $siteBranding['hero_title'] }}"
                                    width="900"
                                    height="700"
                                    fetchpriority="high"
                                    decoding="async"
                                    class="h-full max-h-[480px] w-full object-cover transition-transform duration-500 group-hover:scale-[1.02]"
                                >
                            </a>
                        </article>
                    @endif

                    @forelse ($heroProducts as $index => $product)
                        @php
                            $heroImage = $product->primaryImage ?? $product->images->firstWhere('is_primary', true) ?? $product->images->first();
                            $slideIndex = $index + ($hasCustomHero ? 1 : 0);
                        @endphp

                        <article
                            data-hero-slide
                            aria-roledescription="slide"
                            aria-label="{{ $slideIndex + 1 }} of {{ $heroSlideCount }}"
                            aria-hidden="{{ $slideIndex === 0 ? 'false' : 'true' }}"
                            @if ($slideIndex !== 0) inert @endif
                            class="grid min-h-[380px] w-full shrink-0 items-center gap-8 px-4 py-10 sm:min-h-[420px] sm:px-6 lg:min-h-[480px] lg:grid-cols-2 lg:px-8 lg:py-14"
                        >
                            <div class="max-w-xl">
                                <p class="inline-flex items-center gap-2 text-xs font-bold uppercase tracking-[0.14em] text-[#a48335]">
                                    <span class="h-2 w-2 rounded-full bg-[#a48335]" aria-hidden="true"></span>
                                    {{ $product->category?->name ?? 'Fresh picks for you' }}
                                </p>

                                @if ($slideIndex === 0)
                                    <h1 class="mt-4 text-4xl font-extrabold leading-[1.05] text-[#145943] sm:text-5xl lg:text-6xl">{{ $product->name }}</h1>
                                @else
                                    <h2 class="mt-4 text-4xl font-extrabold leading-[1.05] text-[#145943] sm:text-5xl lg:text-6xl">{{ $product->name }}</h2>
                                @endif

                                <p class="mt-5 max-w-lg text-base leading-7 text-[#56665f] sm:text-lg">
                                    Carefully selected quality products for everyday freshness, taste, and value.
                                </p>

                            </div>

                            <a href="{{ route('products.show', ['product' => $product->slug]) }}" class="group relative mx-auto flex min-h-[240px] w-full max-w-2xl items-center justify-center overflow-hidden bg-[#e4ead5] sm:min-h-[300px] lg:min-h-[390px]" aria-label="View {{ $product->name }}">
                                @if ($heroImage)
                                    <img
                                        src="/storage/{{ ltrim($heroImage->image_path, '/') }}"
                                        alt="{{ $heroImage->alt_text ?: $product->name }}"
                                        width="700"
                                        height="600"
                                        fetchpriority="{{ $slideIndex === 0 ? 'high' : 'auto' }}"
                                        loading="{{ $slideIndex === 0 ? 'eager' : 'lazy' }}"
                                        decoding="async"
                                        class="h-full max-h-[390px] w-full object-contain transition-transform duration-500 group-hover:scale-105"
                                    >
                                @else
                                    <span class="text-8xl sm:text-9xl" aria-hidden="true">🛍️</span>
                                @endif
                            </a>
                        </article>
                    @empty
                        @if (! $hasCustomHero)
                        <article data-hero-slide aria-roledescription="slide" aria-label="1 of 1" class="grid min-h-[380px] w-full shrink-0 items-center gap-8 px-4 py-10 sm:min-h-[420px] sm:px-6 lg:min-h-[480px] lg:grid-cols-2 lg:px-8 lg:py-14">
                            <div class="max-w-xl">
                                <p class="text-xs font-bold uppercase tracking-[0.14em] text-[#a48335]">Fresh · Natural · Organic</p>
                                <h1 class="mt-4 text-4xl font-extrabold leading-[1.05] text-[#145943] sm:text-5xl lg:text-6xl">Good Food for a Better You</h1>
                                <p class="mt-5 max-w-lg text-base leading-7 text-[#56665f] sm:text-lg">Discover fresh, high-quality products that bring health and happiness to your everyday life.</p>
                            </div>
                            <div class="grid min-h-[240px] place-items-center bg-[#e4ead5] text-8xl sm:min-h-[300px] sm:text-9xl lg:min-h-[390px]" aria-hidden="true">🥬</div>
                        </article>
                        @endif
                    @endforelse
                </div>
            </div>

            @if ($heroSlideCount > 1)
                <button type="button" data-hero-prev class="absolute left-2 top-1/2 z-10 grid h-10 w-10 -translate-y-1/2 place-items-center rounded-full border border-[#dfe2d6] bg-white/95 text-[#145943] shadow-md transition hover:bg-[#145943] hover:text-white sm:left-4" aria-label="Previous hero slide">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="m15 18-6-6 6-6" /></svg>
                </button>
                <button type="button" data-hero-next class="absolute right-2 top-1/2 z-10 grid h-10 w-10 -translate-y-1/2 place-items-center rounded-full border border-[#dfe2d6] bg-white/95 text-[#145943] shadow-md transition hover:bg-[#145943] hover:text-white sm:right-4" aria-label="Next hero slide">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="m9 18 6-6-6-6" /></svg>
                </button>
            @endif
        </div>

        @if ($heroSlideCount > 1)
            <div data-hero-pagination class="flex items-center justify-center gap-2 pb-5" role="group" aria-label="Choose hero slide">
                @for ($index = 0; $index < $heroSlideCount; $index++)
                    <button type="button" data-hero-dot="{{ $index }}" class="h-2.5 {{ $index === 0 ? 'w-8 bg-[#145943]' : 'w-2.5 bg-[#c6c8b9]' }} rounded-full transition-all duration-300 hover:bg-[#a48335] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#a48335] focus-visible:ring-offset-2" aria-label="Show hero slide {{ $index + 1 }}" aria-current="{{ $index === 0 ? 'true' : 'false' }}"></button>
                @endfor
            </div>
        @endif
    </section>

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
                                class="grid h-[92px] w-full place-items-center overflow-hidden rounded-2xl border border-[#e8e6db] bg-white shadow-sm sm:h-[104px]"
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

    {{-- Top selling products --}}
    @if ($topSellingProducts->isNotEmpty())
        <section id="top-selling-products" class="bg-[#fbfaf4]">
            <div class="mx-auto max-w-[1600px] px-4 pt-0 pb-8 sm:px-6 sm:pb-10 lg:px-8">
                <h2 class="mb-6 text-center text-2xl font-semibold text-[#26352f] sm:text-3xl">Top Selling Products</h2>

                <div class="grid gap-5 md:grid-cols-2">
                    @foreach ($topSellingProducts as $product)
                        @php
                            $primaryImage = $product->primaryImage ?? $product->images->firstWhere('is_primary', true) ?? $product->images->first();
                        @endphp

                        <article class="group relative grid min-h-[230px] grid-cols-[48%_minmax(0,1fr)] overflow-hidden rounded-sm bg-white sm:min-h-[270px] lg:min-h-[300px]">
                            <span class="absolute right-2 top-2 z-10 rounded-[3px] bg-[#ef4938] px-2 py-1 text-[10px] font-bold text-white">Best Selling</span>

                            <a href="{{ route('products.show', ['product' => $product->slug]) }}" class="flex h-full min-h-0 items-center justify-center overflow-hidden bg-white" aria-label="View {{ $product->name }}">
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

                            <div class="flex min-w-0 flex-col justify-center py-8 pl-3 pr-3 sm:px-6">
                                <a href="{{ route('products.show', ['product' => $product->slug]) }}" class="line-clamp-2 text-base font-semibold leading-6 text-[#173b32] hover:text-[#145943] sm:text-lg">
                                    {{ $product->name }}
                                </a>
                                @if ($product->price !== null || $product->compare_at_price !== null)
                                    <div class="mt-2 flex flex-wrap items-baseline justify-between gap-x-2 gap-y-1">
                                        @if ($product->price !== null)
                                            <span class="text-base font-bold text-[#145943] sm:text-lg">৳{{ number_format((float) $product->price, 2) }}</span>
                                        @endif
                                        @if ($product->compare_at_price !== null)
                                            <span class="text-xs text-[#9aa19c] line-through sm:text-sm">৳{{ number_format((float) $product->compare_at_price, 2) }}</span>
                                        @endif
                                    </div>
                                @endif

                                <div class="mt-5 flex flex-wrap gap-2.5">
                                    @if ($product->is_out_of_stock || ($product->type === 'variable' && ! $product->variants->contains(fn ($variant) => $variant->is_active && ! $variant->is_out_of_stock)))
                                        <button type="button" disabled aria-label="{{ $product->name }} is out of stock" class="inline-flex h-10 cursor-not-allowed items-center justify-center rounded-sm border border-[#dfe3cf] bg-[#f4f1ea] px-3 text-sm font-semibold text-[#738078] sm:px-4">
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
                                        class="inline-flex h-10 items-center justify-center gap-2 rounded-sm border border-[#145943] px-3 text-sm font-semibold text-[#145943] transition-all duration-200 hover:bg-[#145943] hover:text-white sm:px-4"
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
                                        class="inline-flex h-9 items-center justify-center rounded-sm border border-[#a48335] bg-[#a48335] px-3 text-xs font-semibold text-white transition-colors hover:border-[#145943] hover:bg-[#145943] sm:h-10 sm:px-4 sm:text-sm"
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
                                            class="group relative shrink-0 overflow-hidden rounded-[4px] border border-[#e9e5db] bg-white shadow-none"
                                        >
                                            <a href="{{ route('products.show', ['product' => $product->slug]) }}" class="flex h-full flex-col">
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

                                                    @if ($product->price !== null || $product->compare_at_price !== null)
                                                        <div class="flex flex-wrap items-baseline justify-between gap-x-2 gap-y-1 pt-2">
                                                            @if ($product->price !== null)
                                                                <span class="text-sm font-extrabold text-[#145943] sm:text-base">৳{{ number_format((float) $product->price, 2) }}</span>
                                                            @endif
                                                            @if ($product->compare_at_price !== null)
                                                                <span class="text-xs text-[#9aa19c] line-through sm:text-sm">৳{{ number_format((float) $product->compare_at_price, 2) }}</span>
                                                            @endif
                                                        </div>
                                                    @endif
                                                </div>

                                                <div class="px-3 pb-3 pt-2 sm:px-4 sm:pb-4 sm:pt-2">
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
                                            </a>
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

    {{-- CTA --}}
    <section class="border-t border-[#e3e2d9] px-4 py-10 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-[1600px]">
            <div class="flex flex-col items-start justify-between gap-6 md:flex-row md:items-center">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.18em] text-[#a48335]">Shop smarter</p>
                    <h2 class="mt-2 text-2xl font-extrabold text-[#145943] sm:text-3xl">Fresh choices, delivered to your door.</h2>
                    <p class="mt-2 max-w-2xl text-sm leading-6 text-[#56665f]">Explore our complete collection and find everything you need in one place.</p>
                </div>
                <a href="{{ route('shop') }}" class="shrink-0 rounded-full border border-[#145943] bg-[#145943] px-6 py-3.5 text-sm font-bold text-white transition hover:bg-[#a48335] hover:border-[#a48335]">Start Shopping →</a>
            </div>
        </div>
    </section>

    {{-- Site-selected recommendations --}}
    <section id="just-for-you" class="border-t border-[#e8e6db] bg-[#fbfaf4]">
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

    <section id="customer-reviews" class="border-t border-[#e8e6db] bg-[#fffef9]">
        <div class="mx-auto max-w-[1600px] px-4 py-10 sm:px-6 lg:px-8">
            <div class="border-b border-[#e7e2d4] pb-3">
                <p class="text-xs font-bold uppercase tracking-[0.16em] text-[#a48335]">Customer feedback</p>
                <h2 class="mt-1 text-xl font-extrabold text-[#26352f] sm:text-2xl">Customer Reviews</h2>
            </div>

            @forelse ($approvedReviews as $review)
                <div class="grid gap-4 border-b border-[#e8e6db] py-5 last:border-b-0 sm:grid-cols-[minmax(0,1fr)_auto] sm:items-start">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="flex text-sm leading-none text-[#d9a700]" aria-label="{{ $review->rating }} out of 5 stars">
                                @for ($star = 1; $star <= 5; $star++)
                                    <span aria-hidden="true">{{ $star <= $review->rating ? '★' : '☆' }}</span>
                                @endfor
                            </span>
                            <span class="text-sm font-bold text-[#173b32]">{{ $review->name }}</span>
                        </div>
                        <p class="mt-2 max-w-4xl whitespace-pre-line text-sm leading-6 text-[#59675f]">{{ $review->body }}</p>
                        <time class="mt-2 block text-xs text-[#77837c]" datetime="{{ $review->created_at->toDateString() }}">{{ $review->created_at->diffForHumans() }}</time>
                    </div>
                    <a href="{{ route('products.show', ['product' => $review->product->slug]) }}" class="text-sm font-semibold text-[#145943] transition hover:text-[#a48335]">
                        {{ $review->product->name }}
                    </a>
                </div>
            @empty
                <p class="py-6 text-sm text-[#738078]">Customer reviews will appear here after they are approved.</p>
            @endforelse
        </div>
    </section>

</x-layouts.app>
