@php
    $primaryImage = $product->primaryImage
        ?? $product->images->firstWhere('is_primary', true)
        ?? $product->images->first();
    $hasAvailableVariant = $product->variants->contains(fn ($variant) => $variant->is_active && ! $variant->is_out_of_stock);
    $canPurchase = ! $product->is_out_of_stock && ($product->type !== 'variable' || $hasAvailableVariant);
@endphp

<article
    data-carousel-item
    data-product-card
    data-product-name="{{ $product->name }}"
    data-product-price="{{ (float) $product->price }}"
    data-product-emoji="🛍️"
    class="group relative shrink-0 overflow-hidden rounded-[4px] border border-[#d1d5ce] bg-white shadow-none max-sm:flex max-sm:flex-col"
>
    <a
        href="{{ route('products.show', ['product' => $product->slug]) }}"
        class="block overflow-hidden bg-white focus:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-[#145943]"
        aria-label="View {{ $product->name }}"
    >
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
            <div class="grid aspect-square place-items-center text-[5.5rem] leading-none sm:text-[7rem]" aria-hidden="true">🛍️</div>
        @endif
    </a>

    <div class="px-3 pt-3 sm:px-4 sm:pt-4 max-sm:flex-1">
        <h3 class="line-clamp-2 text-sm font-normal leading-5 text-[#173b32] sm:text-base">
            <a href="{{ route('products.show', ['product' => $product->slug]) }}" class="transition-colors hover:text-[#145943] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#145943]">
                {{ $product->name }}
            </a>
        </h3>

        @include('storefront.partials.product-price', [
            'product' => $product,
            'containerClass' => 'flex flex-wrap items-baseline justify-between gap-x-2 gap-y-1 pt-1',
            'priceClass' => 'text-base font-extrabold text-[#145943] sm:text-lg',
            'comparePriceClass' => 'text-xs text-[#9aa19c] line-through sm:text-sm',
        ])
    </div>

    <div class="mt-auto px-3 pb-2 pt-2 sm:px-4 sm:pb-4 sm:pt-2">
        @if (! $canPurchase)
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