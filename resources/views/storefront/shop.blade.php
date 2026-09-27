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
        <div class="mx-auto max-w-[1380px] px-3 py-8 sm:px-4 lg:px-5">
            <div class="grid gap-5 lg:grid-cols-[260px_minmax(0,1fr)] lg:items-start">
                <aside class="rounded-[12px] border border-[#e5e2d9] bg-white p-4 shadow-none lg:sticky lg:top-6">
                    <form method="GET" action="{{ $activeCategory ? route('shop', ['category' => $activeCategory->slug]) : route('shop') }}" class="space-y-6" id="shop-filter-form">
                        @if ($searchQuery)
                            <input type="hidden" name="q" value="{{ $searchQuery }}">
                        @endif

                        @if ($activeCategory)
                            <input type="hidden" name="category" value="{{ $activeCategory->slug }}">
                        @endif

                        <div>
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

                        <div>
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
                                    <div class="pointer-events-none absolute top-1/2 h-1.5 -translate-y-1/2 rounded-full bg-[#f58216]" style="left: {{ $rangeMinPercent }}%; width: {{ $rangeTrackWidth }}%;"></div>
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

                        <div>
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
                    <div class="flex flex-wrap items-center justify-between gap-3 rounded-[10px] border border-[#e5e2d9] bg-white px-3 py-2 shadow-none lg:pl-4">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="text-sm font-semibold text-[#145943]">Sort by</span>
                            <form method="GET" action="{{ $activeCategory ? route('shop', ['category' => $activeCategory->slug]) : route('shop') }}" class="flex flex-wrap items-center gap-2">
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
                                <select name="sort" class="h-9 rounded-[8px] border border-[#e3e0d6] bg-[#f8f7f2] px-3 text-sm text-[#173b32] outline-none focus:border-[#145943] focus:ring-2 focus:ring-[#145943]/10" onchange="this.form.submit()">
                                    <option value="latest" {{ $sort === 'latest' ? 'selected' : '' }}>Latest</option>
                                    <option value="price_asc" {{ $sort === 'price_asc' ? 'selected' : '' }}>Price: Low to High</option>
                                    <option value="price_desc" {{ $sort === 'price_desc' ? 'selected' : '' }}>Price: High to Low</option>
                                    <option value="name_asc" {{ $sort === 'name_asc' ? 'selected' : '' }}>Name: A–Z</option>
                                    <option value="name_desc" {{ $sort === 'name_desc' ? 'selected' : '' }}>Name: Z–A</option>
                                </select>
                            </form>
                        </div>

                        <div class="flex flex-wrap gap-2">
                            @foreach ($categories as $category)
                                <a
                                    href="{{ route('shop', ['category' => $category->slug, 'sort' => $sort, 'variants' => $selectedVariants ?: null, 'min_price' => $minPrice > 0 ? $minPrice : null, 'max_price' => $maxPrice > 0 ? $maxPrice : null, 'flag' => $flag !== '' ? $flag : null]) }}"
                                    class="rounded-full border px-3 py-1.5 text-xs font-semibold transition {{ $activeCategory && $activeCategory->id === $category->id ? 'border-[#145943] bg-[#145943] text-white' : 'border-[#dfe2d6] bg-white text-[#4d5c56] hover:border-[#145943] hover:text-[#145943]' }}"
                                >
                                    {{ $category->name }}
                                </a>
                            @endforeach
                        </div>
                    </div>

                    @if ($products->isEmpty())
                        <div class="rounded-[24px] border border-dashed border-[#dfe3cf] bg-white p-10 text-center shadow-sm">
                            <div class="text-5xl" aria-hidden="true">🛒</div>
                            <h2 class="mt-4 text-xl font-bold text-[#145943]">No products found</h2>
                            <p class="mt-2 text-sm text-[#738078]">Try another keyword or select different filters to browse more items.</p>
                        </div>
                    @else
                        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                            @foreach ($products as $product)
                                @php
                                    $primaryImage = $product->images->firstWhere('is_primary', true) ?? $product->images->first();
                                @endphp

                                <article
                                    class="flex h-full flex-col overflow-hidden rounded-[10px] border border-[#e4e1d9] bg-white shadow-none"
                                    data-product-card
                                    data-product-name="{{ $product->name }}"
                                    data-product-price="{{ (float) $product->price }}"
                                >
                                    <a href="{{ route('products.show', ['product' => $product->slug]) }}" class="flex h-full flex-col">
                                        <div class="relative overflow-hidden bg-[#f4f5e9]">
                                            @if ($primaryImage)
                                                <img
                                                    src="{{ asset('storage/' . $primaryImage->image_path) }}"
                                                    alt="{{ $primaryImage->alt_text ?: $product->name }}"
                                                    width="600"
                                                    height="600"
                                                    loading="lazy"
                                                    decoding="async"
                                                    class="h-52 w-full object-cover"
                                                >
                                            @else
                                                <div class="grid h-52 place-items-center text-4xl" aria-hidden="true">🛍️</div>
                                            @endif
                                        </div>

                                        <div class="flex flex-1 flex-col p-2.5">
                                            <div class="space-y-1.5">
                                                <h2 class="text-sm font-extrabold leading-5 text-[#173b32] line-clamp-2">
                                                    {{ $product->name }}
                                                </h2>

                                                <div class="pt-1">
                                                    <p class="text-base font-extrabold text-[#145943]">
                                                        ৳{{ number_format((float) $product->price, 2) }}
                                                    </p>
                                                    @if ($product->compare_at_price)
                                                        <p class="text-[10px] text-[#9aa19c] line-through">
                                                            ৳{{ number_format((float) $product->compare_at_price, 2) }}
                                                        </p>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>

                                        <div class="px-2.5 pb-2.5">
                                            <button
                                                type="button"
                                                aria-label="Add {{ $product->name }} to cart"
                                                data-product-id="{{ $product->id }}"
                                                data-product-name="{{ $product->name }}"
                                                data-product-price="{{ (float) $product->price }}"
                                                data-product-emoji="🛍️"
                                                class="inline-flex w-full items-center justify-center rounded-full bg-[#145943] px-3 py-2.5 text-[10px] font-bold text-white transition hover:bg-[#a48335]"
                                            >
                                                Add to Cart
                                            </button>
                                        </div>
                                    </a>
                                </article>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </section>
</x-layouts.app>
