<x-layouts.app>
    <x-slot:title>
        Shop All Products — {{ config('app.name') }}
    </x-slot:title>

    <x-slot:metaDescription>
        Explore fresh groceries, household essentials and quality food products at {{ config('app.name') }}.
    </x-slot:metaDescription>

    <x-slot:canonicalUrl>
        {{ $activeCategory ? route('shop', ['category' => $activeCategory->slug]) : route('shop') }}
    </x-slot:canonicalUrl>

    <section class="bg-[#fbfaf4]">
        <div class="mx-auto max-w-[1440px] px-3 py-8 sm:px-4 lg:px-5">
            <div class="grid gap-4 lg:grid-cols-[260px_minmax(0,1fr)] lg:items-start">
                <aside id="shop-filter-sidebar" aria-hidden="true" inert class="fixed inset-y-0 left-0 z-[95] w-[min(92vw,400px)] -translate-x-full overflow-y-auto rounded-r-[12px] border-r border-[#e5e2d9] bg-white p-4 shadow-xl transition-transform duration-300 ease-out lg:sticky lg:top-6 lg:z-auto lg:h-auto lg:w-auto lg:translate-x-0 lg:overflow-visible lg:rounded-none lg:border-0 lg:bg-transparent lg:p-0 lg:shadow-none">
                    <div class="mb-5 flex items-center justify-between border-b border-[#e5e2d9] pb-3 lg:hidden">
                        <h2 class="text-lg font-extrabold text-[#145943]">Filters</h2>
                        <button type="button" data-shop-filter-close aria-label="Close filters" class="grid h-9 w-9 place-items-center rounded-full text-[#4d5c56] hover:bg-[#f5f4ec]">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18" /></svg>
                        </button>
                    </div>
                    <form method="GET" action="{{ $activeCategory ? route('shop', ['category' => $activeCategory->slug]) : route('shop') }}" class="space-y-3" id="shop-filter-form">
                        @if ($searchQuery)
                            <input type="hidden" name="q" value="{{ $searchQuery }}">
                        @endif

                        @if ($activeCategory)
                            <input type="hidden" name="category" value="{{ $activeCategory->slug }}">
                        @endif
                        <input type="hidden" name="sort" value="{{ $sort }}">
                        @if ($perPageSelection !== 'default')
                            <input type="hidden" name="per_page" value="{{ $perPageSelection }}">
                        @endif
                        @if ($flag !== '')
                            <input type="hidden" name="flag" value="{{ $flag }}">
                        @endif

                        <section data-filter-accordion class="rounded-[4px] border border-[#e5e2d9] bg-white p-3">
                            <button type="button" data-filter-accordion-toggle aria-expanded="true" aria-controls="shop-filter-category" aria-label="Collapse Category filters" class="flex min-h-10 w-full items-center justify-between gap-3 text-left">
                                <span class="text-base font-extrabold text-black">Category</span>
                                <span data-filter-accordion-icon class="grid h-9 w-9 shrink-0 place-items-center text-4xl font-light leading-none text-[#145943] transition-colors" aria-hidden="true">−</span>
                            </button>
                            <div id="shop-filter-category" data-filter-accordion-panel class="filter-accordion-panel">
                                <div class="min-h-0 overflow-hidden">
                                    <div class="mt-3 space-y-2">
                                <a
                                    href="{{ route('shop', ['sort' => $sort, 'per_page' => $perPageSelection !== 'default' ? $perPageSelection : null, 'q' => $searchQuery ?: null, 'variants' => $selectedVariants ?: null, 'flag' => $flag !== '' ? $flag : null, 'min_price' => $minPrice > 0 ? $minPrice : null, 'max_price' => $requestedMaxPrice > 0 ? $requestedMaxPrice : null]) }}"
                                    class="flex items-center justify-between rounded-xl px-2 py-2 text-sm transition {{ !$activeCategory ? 'bg-[#edf2e4] font-bold text-[#145943]' : 'text-[#4d5c56] hover:bg-[#f5f4ec]' }}"
                                >
                                    <span>All</span>
                                </a>

                                @foreach ($categories as $categoryItem)
                                    <a
                                        href="{{ route('shop', ['category' => $categoryItem->slug, 'sort' => $sort, 'per_page' => $perPageSelection !== 'default' ? $perPageSelection : null, 'q' => $searchQuery ?: null, 'variants' => $selectedVariants ?: null, 'flag' => $flag !== '' ? $flag : null, 'min_price' => $minPrice > 0 ? $minPrice : null, 'max_price' => $requestedMaxPrice > 0 ? $requestedMaxPrice : null]) }}"
                                        class="flex items-center justify-between rounded-xl px-2 py-2 text-sm transition {{ $activeCategory && $activeCategory->slug === $categoryItem->slug ? 'bg-[#edf2e4] font-bold text-[#145943]' : 'text-[#4d5c56] hover:bg-[#f5f4ec]' }}"
                                    >
                                        <span>{{ $categoryItem->name }}</span>
                                    </a>
                                @endforeach
                                    </div>
                                </div>
                            </div>
                        </section>

                        <section data-filter-accordion class="rounded-[4px] border border-[#e5e2d9] bg-white p-3">
                            <button type="button" data-filter-accordion-toggle aria-expanded="true" aria-controls="shop-filter-price" aria-label="Collapse Price Range filters" class="flex min-h-10 w-full items-center justify-between gap-3 text-left">
                                <span class="text-base font-extrabold text-black">Price Range</span>
                                <span data-filter-accordion-icon class="grid h-9 w-9 shrink-0 place-items-center text-4xl font-light leading-none text-[#145943] transition-colors" aria-hidden="true">−</span>
                            </button>
                            <div id="shop-filter-price" data-filter-accordion-panel class="filter-accordion-panel">
                                <div class="min-h-0 overflow-hidden">
                            @php
                                $rangeMinValue = $minPrice > 0 ? $minPrice : 0;
                                $rangeMaxValue = $maxPrice > 0 ? $maxPrice : $maxProductPrice;
                                $rangeMinPercent = $maxProductPrice > 0 ? ($rangeMinValue / $maxProductPrice) * 100 : 0;
                                $rangeMaxPercent = $maxProductPrice > 0 ? ($rangeMaxValue / $maxProductPrice) * 100 : 100;
                                $rangeTrackWidth = max(0, $rangeMaxPercent - $rangeMinPercent);
                            @endphp
                            <div class="mt-3 space-y-4">
                                <div class="relative h-7 px-1.5">
                                    <div class="pointer-events-none absolute left-1 right-1 top-1/2 h-1.5 -translate-y-1/2 rounded-full bg-[#e9e7df]"></div>
                                    <div data-price-range-track class="price-range-active-track pointer-events-none absolute top-1/2 h-1.5 -translate-y-1/2 rounded-full bg-[#145943]" style="left: {{ $rangeMinPercent }}%; width: {{ $rangeTrackWidth }}%;"></div>
                                    <input
                                        type="range"
                                        min="0"
                                        max="{{ $maxProductPrice }}"
                                        step="10"
                                        value="{{ $rangeMinValue }}"
                                        data-price-range="min"
                                        class="price-range-slider"
                                    >
                                    <input
                                        type="range"
                                        min="0"
                                        max="{{ $maxProductPrice }}"
                                        step="10"
                                        value="{{ $rangeMaxValue }}"
                                        data-price-range="max"
                                        class="price-range-slider"
                                    >
                                </div>

                                <div class="flex items-center justify-between text-sm font-bold text-[#145943]">
                                    <span>৳{{ number_format($rangeMinValue, 0, '.', ',') }}</span>
                                    <span>৳{{ number_format($rangeMaxValue, 0, '.', ',') }}</span>
                                </div>

                                <div class="flex items-center gap-3">
                                    <input
                                        type="number"
                                        id="min-price-input"
                                        name="min_price"
                                        value="{{ $rangeMinValue }}"
                                        min="0"
                                        max="{{ $maxProductPrice }}"
                                        step="0.01"
                                        class="h-10 w-full rounded-xl border border-[#e3e0d6] bg-[#faf9f5] px-3 text-sm text-[#173b32] outline-none focus:border-[#145943] focus:ring-4 focus:ring-[#145943]/10"
                                    >
                                    <span class="text-[#738078]">to</span>
                                    <input
                                        type="number"
                                        id="max-price-input"
                                        name="max_price"
                                        value="{{ $rangeMaxValue }}"
                                        min="0"
                                        max="{{ $maxProductPrice }}"
                                        step="0.01"
                                        class="h-10 w-full rounded-xl border border-[#e3e0d6] bg-[#faf9f5] px-3 text-sm text-[#173b32] outline-none focus:border-[#145943] focus:ring-4 focus:ring-[#145943]/10"
                                    >
                                </div>
                                <button type="submit" data-price-filter-apply hidden class="inline-flex min-h-10 w-full items-center justify-center rounded-[4px] bg-[#145943] px-4 text-sm font-bold text-white transition hover:bg-[#a48335] sm:w-auto">Apply price filter</button>
                                </div>
                            </div>
                        </section>

                        <section data-filter-accordion class="rounded-[4px] border border-[#e5e2d9] bg-white p-3">
                            <button type="button" data-filter-accordion-toggle aria-expanded="true" aria-controls="shop-filter-variants" aria-label="Collapse Product Variants filters" class="flex min-h-10 w-full items-center justify-between gap-3 text-left">
                                <span class="text-base font-extrabold text-black">Product Variants</span>
                                <span data-filter-accordion-icon class="grid h-9 w-9 shrink-0 place-items-center text-4xl font-light leading-none text-[#145943] transition-colors" aria-hidden="true">−</span>
                            </button>
                            <div id="shop-filter-variants" data-filter-accordion-panel class="filter-accordion-panel">
                                <div class="min-h-0 overflow-hidden">
                                    <div class="mt-3 space-y-2">
                                @foreach ($variantOptions as $variantOption)
                                    <label class="flex cursor-pointer items-center gap-3 rounded-xl px-2 py-2 text-sm text-[#4d5c56] transition hover:bg-[#f5f4ec]">
                                        <input
                                            type="checkbox"
                                            name="variants[]"
                                            value="{{ $variantOption['value'] }}"
                                            {{ in_array($variantOption['value'], $selectedVariants, true) ? 'checked' : '' }}
                                            class="h-4 w-4 rounded border-[#c9cfbf] text-[#145943] focus:ring-[#145943]"
                                        >
                                        <span>{{ $variantOption['label'] }}</span>
                                    </label>
                                @endforeach
                                    </div>
                                </div>
                            </div>
                        </section>

                    </form>
                </aside>
                <button type="button" data-shop-filter-overlay aria-label="Close filters" aria-hidden="true" tabindex="-1" class="fixed inset-0 z-[90] hidden bg-[#173b32]/45 opacity-0 transition-opacity duration-200"></button>

                <div class="space-y-5">
                    <div class="flex flex-wrap items-center justify-between gap-3 rounded-[4px] border border-[#e5e2d9] bg-white px-4 py-3 shadow-none lg:pl-5">
                        @php
                            $sortLabels = [
                                'default' => 'Default Sorting',
                                'latest' => 'Sort by Latest',
                                'oldest' => 'Sort by Oldest',
                                'price_asc' => 'Price Low to High',
                                'price_desc' => 'Price High to Low',
                            ];
                            $perPageLabels = [
                                'default' => 'Default',
                                '16' => 'Show 16',
                                '20' => 'Show 20',
                                '24' => 'Show 24',
                                '36' => 'Show 36',
                            ];
                        @endphp
                        <div class="w-full lg:flex-1">
                            <form method="GET" action="{{ $activeCategory ? route('shop', ['category' => $activeCategory->slug]) : route('shop') }}" class="grid w-full grid-cols-[36px_minmax(0,1fr)_100px] items-center gap-1.5 lg:flex lg:items-center lg:gap-2">
                                @if ($searchQuery)
                                    <input type="hidden" name="q" value="{{ $searchQuery }}">
                                @endif
                                <input type="hidden" name="sort" value="{{ $sort }}">
                                <input type="hidden" name="per_page" value="{{ $perPageSelection }}">
                                @foreach ($selectedVariants as $selectedVariant)
                                    <input type="hidden" name="variants[]" value="{{ $selectedVariant }}">
                                @endforeach
                                @if ($minPrice > 0)
                                    <input type="hidden" name="min_price" value="{{ $minPrice }}">
                                @endif
                                @if ($requestedMaxPrice > 0)
                                    <input type="hidden" name="max_price" value="{{ $requestedMaxPrice }}">
                                @endif
                                @if ($flag !== '')
                                    <input type="hidden" name="flag" value="{{ $flag }}">
                                @endif
                                <button type="button" data-shop-filter-toggle aria-label="Open filters" aria-controls="shop-filter-sidebar" aria-expanded="false" class="col-start-1 row-start-1 grid h-9 w-9 place-items-center rounded-[4px] border border-[#dfe2d6] text-[#145943] hover:bg-[#f5f4ec] lg:hidden">
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M4 7h16M7 12h10m-7 5h4" /><circle cx="8" cy="7" r="1.5" fill="white" /><circle cx="15" cy="12" r="1.5" fill="white" /><circle cx="11" cy="17" r="1.5" fill="white" /></svg>
                                </button>
                                <span class="hidden text-sm font-semibold text-black lg:inline">Sort by</span>
                                <details data-shop-select="sort" class="relative col-start-2 row-start-1 min-w-0 lg:static lg:col-auto lg:row-auto">
                                    <summary aria-label="Sort products: {{ $sortLabels[$sort] ?? 'Default Sorting' }}" class="flex h-9 w-full cursor-pointer list-none items-center justify-between gap-2 rounded-[4px] border border-[#dfe2d6] bg-white px-2 text-xs font-medium text-[#173b32] [&::-webkit-details-marker]:hidden lg:min-w-[160px] lg:px-3 lg:text-sm">
                                        <span data-shop-select-label class="truncate">{{ $sortLabels[$sort] ?? 'Default Sorting' }}</span>
                                        <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="m7 10 5 5 5-5" /></svg>
                                    </summary>
                                    <div role="menu" aria-label="Sort products" class="absolute left-1/2 top-full z-[70] mt-1 w-48 -translate-x-1/2 overflow-hidden rounded-[4px] border border-[#dfe2d6] bg-white py-1 shadow-lg lg:left-0 lg:translate-x-0">
                                        @foreach ($sortLabels as $value => $label)
                                            <button type="button" role="menuitemradio" aria-checked="{{ $sort === $value ? 'true' : 'false' }}" data-shop-option="{{ $value }}" class="block w-full px-3 py-2 text-left text-sm text-[#173b32] hover:bg-[#f5f4ec] aria-checked:bg-[#edf2e4]">
                                                {{ $label }}
                                            </button>
                                        @endforeach
                                    </div>
                                </details>
                                <details data-shop-select="per_page" class="relative col-start-3 row-start-1 ml-auto justify-self-end lg:order-2 lg:col-auto lg:row-auto">
                                    <summary aria-label="Products per page: {{ $perPageLabels[$perPageSelection] ?? 'Default' }}" class="flex h-9 min-w-[100px] cursor-pointer list-none items-center justify-between gap-2 rounded-[4px] border border-[#dfe2d6] bg-white px-2 text-xs font-medium text-[#173b32] [&::-webkit-details-marker]:hidden lg:px-3 lg:text-sm">
                                        <span data-shop-select-label>{{ $perPageLabels[$perPageSelection] ?? 'Default' }}</span>
                                        <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="m7 10 5 5 5-5" /></svg>
                                    </summary>
                                    <div role="menu" aria-label="Products per page" class="absolute right-0 top-full z-[70] mt-1 w-32 overflow-hidden rounded-[4px] border border-[#dfe2d6] bg-white py-1 shadow-lg">
                                        @foreach ($perPageLabels as $value => $label)
                                            <button type="button" role="menuitemradio" aria-checked="{{ $perPageSelection === (string) $value ? 'true' : 'false' }}" data-shop-option="{{ $value }}" class="block w-full px-3 py-2 text-left text-sm text-[#173b32] hover:bg-[#f5f4ec] aria-checked:bg-[#edf2e4]">
                                                {{ $label }}
                                            </button>
                                        @endforeach
                                    </div>
                                </details>
                            </form>
                        </div>

                    </div>

                    @if ($products->isEmpty())
                        <div class="rounded-[24px] border border-dashed border-[#dfe3cf] bg-white p-10 text-center shadow-sm">
                            <div class="text-5xl" aria-hidden="true">🛒</div>
                            <h2 class="mt-4 text-xl font-bold text-[#145943]">No products found</h2>
                            <p class="mt-2 text-sm text-[#738078]">Try another keyword or select different filters to browse more items.</p>
                        </div>
                    @else
                        <div class="grid grid-cols-2 items-start gap-3 sm:gap-4 md:grid-cols-2 xl:grid-cols-4">
                            @foreach ($products as $product)
                                @php
                                    $primaryImage = $product->primaryImage ?? $product->images->firstWhere('is_primary', true) ?? $product->images->first();
                                    $hasAvailableVariant = $product->variants->contains(fn ($variant) => ! $variant->is_out_of_stock);
                                    $canPurchase = ! $product->is_out_of_stock && ($product->type !== 'variable' || $hasAvailableVariant);
                                @endphp

                                <article
                                    class="group flex self-start flex-col overflow-hidden rounded-[4px] border border-[#d1d5ce] bg-white shadow-none"
                                    data-product-card
                                    data-product-name="{{ $product->name }}"
                                    data-product-price="{{ (float) $product->price }}"
                                >
                                    <a href="{{ route('products.show', ['product' => $product->slug]) }}" class="flex flex-col">
                                        <div class="relative overflow-hidden bg-white">
                                            @if ($primaryImage)
                                                <div class="aspect-square overflow-hidden">
                                                    <img
                                                        src="/storage/{{ ltrim($primaryImage->image_path, '/') }}"
                                                        alt="{{ $primaryImage->alt_text ?: $product->name }}"
                                                        width="600"
                                                        height="600"
                                                        loading="lazy"
                                                        decoding="async"
                                                        class="h-full w-full object-contain transition-transform duration-300 group-hover:scale-105"
                                                    >
                                                </div>
                                            @else
                                                <div class="grid aspect-square place-items-center text-[5.5rem] leading-none sm:text-[7rem]" aria-hidden="true">🛍️</div>
                                            @endif
                                        </div>

                                        <div class="flex flex-col px-3 pt-3 sm:px-4 sm:pt-4 max-sm:flex-1">
                                            <div class="flex min-h-0 flex-col justify-start gap-1">
                                                <h2 class="text-lg font-normal leading-6 text-[#173b32] line-clamp-2 sm:text-xl">
                                                    {{ $product->name }}
                                                </h2>

                                                @include('storefront.partials.product-price', [
                                                    'product' => $product,
                                                    'containerClass' => 'flex flex-wrap items-baseline justify-between gap-x-2 gap-y-1 pt-1',
                                                    'priceClass' => 'text-base font-extrabold text-[#145943] sm:text-lg',
                                                    'comparePriceClass' => 'text-xs text-[#9aa19c] line-through sm:text-sm',
                                                ])
                                            </div>
                                        </div>
                                    </a>

                                        <div class="mt-auto px-3 pb-2 pt-2 sm:px-4 sm:pb-4 sm:pt-2">
                                            @if (! $canPurchase)
                                                <button type="button" disabled aria-label="{{ $product->name }} is out of stock"
                                                    class="inline-flex h-10 w-full cursor-not-allowed items-center justify-center rounded-[4px] border border-[#dfe3cf] bg-[#f4f1ea] px-3 text-xs font-bold text-[#738078]"
                                                >Out of stock</button>
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
                                                    class="inline-flex h-10 w-full items-center justify-center rounded-[4px] border border-[#145943] bg-white px-3 text-xs font-bold text-[#145943] transition-colors hover:bg-[#145943] hover:text-white"
                                                >{{ $product->type === 'variable' ? 'Choose options' : 'Add to Cart' }}</button>
                                            @endif
                                        </div>
                                </article>
                            @endforeach
                        </div>
                        @if ($products->hasPages())
                            <div class="mt-6">
                                {{ $products->links() }}
                            </div>
                        @endif
                    @endif
                </div>
            </div>
        </div>
    </section>
</x-layouts.app>
