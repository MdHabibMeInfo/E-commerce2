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
        <div class="mx-auto max-w-[1440px] px-3 py-8 sm:px-4 lg:px-5">
            <div class="grid gap-4 lg:grid-cols-[220px_minmax(0,1fr)] lg:items-start">
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
                            <span class="text-sm font-semibold text-[#145943]">Sort by :</span>
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
                                <div class="relative inline-flex">
                                    <select name="sort" class="h-9 appearance-none rounded-none border border-[#e5e2d9] bg-white pl-3 pr-8 text-sm font-semibold text-[#173b32] shadow-none outline-none transition hover:border-[#d7d2c8] focus:border-[#e5e2d9] focus:ring-0" onchange="this.form.submit()" style="background-color: #ffffff; border-color: #e5e2d9; color: #173b32;">
                                        <option value="default" {{ $sort === 'default' ? 'selected' : '' }} style="background-color: #ffffff; color: #173b32;">Default Sorting</option>
                                        <option value="latest" {{ $sort === 'latest' ? 'selected' : '' }} style="background-color: #ffffff; color: #173b32;">Sort by Latest</option>
                                        <option value="oldest" {{ $sort === 'oldest' ? 'selected' : '' }} style="background-color: #ffffff; color: #173b32;">Sort by Oldest</option>
                                        <option value="price_asc" {{ $sort === 'price_asc' ? 'selected' : '' }} style="background-color: #ffffff; color: #173b32;">Price Low to High</option>
                                        <option value="price_desc" {{ $sort === 'price_desc' ? 'selected' : '' }} style="background-color: #ffffff; color: #173b32;">Price High to Low</option>
                                    </select>
                                    <svg class="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-[#145943]" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                        <path fill-rule="evenodd" d="M5.22 7.47a.75.75 0 0 1 1.06 0L10 11.19l3.72-3.72a.75.75 0 1 1 1.06 1.06l-4.25 4.25a.75.75 0 0 1-1.06 0L5.22 8.53a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd"/>
                                    </svg>
                                </div>
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

                                                <div class="pt-1">
                                                    <p class="text-base font-extrabold text-[#145943] sm:text-lg">
                                                        ৳{{ number_format((float) $product->price, 2) }}
                                                    </p>
                                                </div>
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
                                                <span data-cart-button-label>Add to Cart</span>
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
