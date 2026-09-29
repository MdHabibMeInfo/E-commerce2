<x-layouts.app>
    <x-slot:title>
        Shop All Products — {{ config('app.name') }}
    </x-slot:title>

    <x-slot:metaDescription>
        Explore fresh groceries, household essentials and quality food products at {{ config('app.name') }}.
    </x-slot:metaDescription>

    <x-slot:canonicalUrl>
        {{ route('shop') }}
    </x-slot:canonicalUrl>

    <section class="bg-[#fbfaf4]">
        <div class="mx-auto max-w-[1600px] px-4 py-8 sm:px-6 lg:px-8">
            <div class="grid gap-4 lg:grid-cols-[250px_minmax(0,1fr)] lg:items-start">
                <div data-shop-filter-overlay class="fixed inset-0 z-[91] hidden bg-[#102a22]/45 opacity-0 transition-opacity duration-200 lg:hidden" aria-hidden="true"></div>

                <aside id="shop-filter-sidebar" aria-label="Product filters" aria-hidden="true" inert class="fixed inset-y-0 left-0 z-[92] flex w-[min(88vw,420px)] -translate-x-full flex-col overflow-hidden bg-white p-4 shadow-2xl transition-transform duration-300 ease-out lg:sticky lg:top-6 lg:z-auto lg:block lg:w-auto lg:translate-x-0 lg:overflow-visible lg:bg-transparent lg:p-0 lg:shadow-none">
                    <div class="mb-4 flex shrink-0 items-center justify-between border-b border-[#dedede] pb-3 lg:hidden">
                        <h2 class="text-base font-bold text-[#145943]">Filters</h2>
                        <button type="button" data-shop-filter-close class="text-sm font-semibold text-[#4d5c56]" aria-label="Close filters">Close <span aria-hidden="true">×</span></button>
                    </div>
                    <form method="GET" action="{{ $activeCategory ? route('shop', ['category' => $activeCategory->slug]) : route('shop') }}" class="min-h-0 flex-1 space-y-1 overflow-y-auto lg:flex-none lg:overflow-visible" id="shop-filter-form">
                        @if ($searchQuery)
                            <input type="hidden" name="q" value="{{ $searchQuery }}">
                        @endif

                        @if ($activeCategory)
                            <input type="hidden" name="category" value="{{ $activeCategory->slug }}">
                        @endif

                        <div class="rounded-[4px] border border-[#dedede] bg-white p-2.5">
                            <h2 class="text-lg font-extrabold text-[#145943]">Category</h2>
                            <div class="mt-4 space-y-2">
                                <a
                                    href="{{ route('shop', ['sort' => $sort, 'variants' => $selectedVariants ?: null, 'flag' => $flag !== '' ? $flag : null, 'min_price' => $minPrice > 0 ? $minPrice : null, 'max_price' => $maxPrice > 0 ? $maxPrice : null]) }}"
                                    class="flex items-center justify-between rounded-xl px-2 py-2 text-sm transition {{ !$activeCategory ? 'bg-[#edf2e4] font-bold text-[#145943]' : 'text-[#4d5c56] hover:bg-[#f5f4ec]' }}"
                                >
                                    <span>All</span>
                                </a>

                                @foreach ($categories as $categoryItem)
                                    <a
                                        href="{{ route('shop', ['category' => $categoryItem->slug, 'sort' => $sort, 'variants' => $selectedVariants ?: null, 'flag' => $flag !== '' ? $flag : null, 'min_price' => $minPrice > 0 ? $minPrice : null, 'max_price' => $maxPrice > 0 ? $maxPrice : null]) }}"
                                        class="flex items-center justify-between rounded-xl px-2 py-2 text-sm transition {{ $activeCategory && $activeCategory->slug === $categoryItem->slug ? 'bg-[#edf2e4] font-bold text-[#145943]' : 'text-[#4d5c56] hover:bg-[#f5f4ec]' }}"
                                    >
                                        <span>{{ $categoryItem->name }}</span>
                                    </a>
                                @endforeach
                            </div>
                        </div>

                        <div class="rounded-[4px] border border-[#dedede] bg-white p-2.5">
                            <h2 class="text-lg font-extrabold text-[#145943]">Price Range</h2>
                            @php
                                $rangeMinValue = $minPrice > 0 ? $minPrice : 0;
                                $rangeMaxValue = $maxPrice > 0 ? $maxPrice : $maxProductPrice;
                                $rangeMinPercent = $maxProductPrice > 0 ? ($rangeMinValue / $maxProductPrice) * 100 : 0;
                                $rangeMaxPercent = $maxProductPrice > 0 ? ($rangeMaxValue / $maxProductPrice) * 100 : 100;
                                $rangeTrackWidth = max(0, $rangeMaxPercent - $rangeMinPercent);
                            @endphp
                            <div class="mt-4 space-y-4">
                                <div class="relative h-7 px-1.5">
                                    <div class="pointer-events-none absolute left-1 right-1 top-1/2 h-1.5 -translate-y-1/2 rounded-full bg-[#e9e7df]"></div>
                                    <div class="pointer-events-none absolute top-1/2 h-1.5 -translate-y-1/2 rounded-full bg-[]" style="left: {{ $rangeMinPercent }}%; width: {{ $rangeTrackWidth }}%;"></div>
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
                                        step="10"
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
                                        step="10"
                                        class="h-10 w-full rounded-xl border border-[#e3e0d6] bg-[#faf9f5] px-3 text-sm text-[#173b32] outline-none focus:border-[#145943] focus:ring-4 focus:ring-[#145943]/10"
                                    >
                                </div>
                            </div>
                        </div>

                        <div class="rounded-[4px] border border-[#dedede] bg-white p-2.5">
                            <h2 class="text-lg font-extrabold text-[#145943]">Product Variants</h2>
                            <div class="mt-4 space-y-2">
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

                    </form>
                </aside>

                <div class="space-y-5">
                    <div class="flex min-h-11 flex-wrap items-center justify-between gap-3 rounded-[4px] border border-[#dedede] bg-white px-3 py-2.5 shadow-none">
                        <form method="GET" action="{{ $activeCategory ? route('shop', ['category' => $activeCategory->slug]) : route('shop') }}" class="flex w-full flex-wrap items-center justify-between gap-3">
                            @if ($searchQuery)
                                <input type="hidden" name="q" value="{{ $searchQuery }}">
                            @endif
                            @foreach ($selectedVariants as $selectedVariant)
                                <input type="hidden" name="variants[]" value="{{ $selectedVariant }}">
                            @endforeach
                            @if ($minPrice > 0)
                                <input type="hidden" name="min_price" value="{{ $minPrice }}">
                            @endif
                            @if ($maxPrice > 0)
                                <input type="hidden" name="max_price" value="{{ $maxPrice }}">
                            @endif
                            @if ($flag !== '')
                                <input type="hidden" name="flag" value="{{ $flag }}">
                            @endif
                            <div class="flex flex-wrap items-center gap-2">
                                <button
                                    type="button"
                                    class="rounded-[3px] border border-[#dedede] bg-white px-3 py-1.5 text-xs font-semibold text-[#333] lg:hidden"
                                    data-shop-filter-toggle
                                    aria-controls="shop-filter-sidebar"
                                    aria-expanded="false"
                                >
                                    Filters
                                </button>
                                <label for="shop-sort" class="text-base font-semibold text-[#333]">Sort By :</label>
                                <select id="shop-sort" name="sort" class="h-7 rounded-[3px] border border-[#dedede] bg-white px-2 text-xs text-[#333] outline-none focus:border-[#dedede] focus:ring-0" onchange="this.form.submit()">
                                    <option value="default" {{ $sort === 'default' ? 'selected' : '' }}>Default Sorting</option>
                                    <option value="latest" {{ $sort === 'latest' ? 'selected' : '' }}>Sort by Latest</option>
                                    <option value="oldest" {{ $sort === 'oldest' ? 'selected' : '' }}>Sort by Oldest</option>
                                    <option value="price_asc" {{ $sort === 'price_asc' ? 'selected' : '' }}>Price Low to High</option>
                                    <option value="price_desc" {{ $sort === 'price_desc' ? 'selected' : '' }}>Price High to Low</option>
                                </select>
                            </div>
                            <select name="per_page" aria-label="Products per page" class="h-7 rounded-[3px] border border-[#dedede] bg-white px-2 text-xs text-[#333] outline-none focus:border-[#dedede] focus:ring-0" onchange="this.form.submit()">
                                <option value="default" {{ $perPageSelection === 'default' ? 'selected' : '' }}>Default</option>
                                <option value="16" {{ $perPageSelection === '16' ? 'selected' : '' }}>Show 16</option>
                                <option value="20" {{ $perPageSelection === '20' ? 'selected' : '' }}>Show 20</option>
                                <option value="24" {{ $perPageSelection === '24' ? 'selected' : '' }}>Show 24</option>
                                <option value="36" {{ $perPageSelection === '36' ? 'selected' : '' }}>Show 36</option>
                            </select>
                        </form>
                    </div>

                    @if ($products->isEmpty())
                        <div class="rounded-[24px] border border-dashed border-[#dfe3cf] bg-white p-10 text-center shadow-sm">
                            <div class="text-5xl" aria-hidden="true">🛒</div>
                            <h2 class="mt-4 text-xl font-bold text-[#145943]">No products found</h2>
                            <p class="mt-2 text-sm text-[#738078]">Try another keyword or select different filters to browse more items.</p>
                        </div>
                    @else
                        <div class="grid grid-cols-2 gap-4 xl:grid-cols-4">
                            @foreach ($products as $product)
                                @php
                                    $primaryImage = $product->primaryImage ?? $product->images->firstWhere('is_primary', true) ?? $product->images->first();
                                @endphp

                                <article
                                    class="group flex h-full flex-col overflow-hidden rounded-[4px] border border-[#d1d5ce] bg-white shadow-none"
                                    data-product-card
                                    data-product-name="{{ $product->name }}"
                                    data-product-price="{{ (float) $product->price }}"
                                >
                                    <a href="{{ route('products.show', ['product' => $product->slug]) }}" class="flex h-full flex-col">
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

                                        <div class="flex flex-col px-3 pt-3 sm:px-4 sm:pt-4">
                                            <div class="space-y-0">
                                                <h2 class="text-lg font-normal leading-6 text-[#173b32] line-clamp-2 sm:text-xl">
                                                    {{ $product->name }}
                                                </h2>

                                                @if ($product->price !== null || $product->compare_at_price !== null)
                                                    <div class="flex flex-wrap items-baseline justify-between gap-x-2 gap-y-1 pt-1">
                                                        @if ($product->price !== null)
                                                            <span class="text-base font-extrabold text-[#145943] sm:text-lg">৳{{ number_format((float) $product->price, 2) }}</span>
                                                        @endif
                                                        @if ($product->compare_at_price !== null)
                                                            <span class="text-xs text-[#9aa19c] line-through sm:text-sm">৳{{ number_format((float) $product->compare_at_price, 2) }}</span>
                                                        @endif
                                                    </div>
                                                @endif
                                            </div>
                                        </div>

                                        <div class="px-3 pb-3 pt-2 sm:px-4 sm:pb-4 sm:pt-2">
                                            <button
                                                type="button"
                                                aria-label="Add {{ $product->name }} to cart"
                                                data-product-id="{{ $product->id }}"
                                                data-product-name="{{ $product->name }}"
                                                data-product-price="{{ (float) $product->price }}"
                                                data-product-image="{{ $primaryImage ? '/storage/' . ltrim($primaryImage->image_path, '/') : '' }}"
                                                data-product-emoji="🛍️"
                                                class="inline-flex h-10 w-full items-center justify-center rounded-[4px] border border-[#145943] bg-white px-3 text-xs font-bold text-[#145943] transition-colors hover:bg-[#145943] hover:text-white"
                                            >
                                                Add to Cart
                                            </button>
                                        </div>
                                    </a>
                                </article>
                            @endforeach
                        </div>
                        @if ($products->hasPages())
                            <div class="pt-4">
                                {{ $products->links() }}
                            </div>
                        @endif
                    @endif
                </div>
            </div>
        </div>
    </section>
</x-layouts.app>
