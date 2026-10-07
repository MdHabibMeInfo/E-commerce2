@php
    $variantPrices = $product->type === 'variable'
        ? $product->variants
            ->where('is_active', true)
            ->pluck('price')
            ->filter(fn ($price) => $price !== null)
            ->map(fn ($price) => (float) $price)
        : collect();
    $minimumVariantPrice = $variantPrices->min();
    $maximumVariantPrice = $variantPrices->max();
    $hasVariantPriceRange = $minimumVariantPrice !== null;
    $variantOffers = $product->type === 'variable'
        ? $product->variants->filter(fn ($variant) => $variant->catalog_offer !== null)
        : collect();
    $minimumPromotionalVariant = $variantOffers
        ->sortBy(fn ($variant) => $variant->catalog_offer['price'])
        ->first();
@endphp

@if ($hasVariantPriceRange || $product->price !== null || $product->compare_at_price !== null)
    <div class="{{ $containerClass }}">
        @if ($hasVariantPriceRange)
            <span class="{{ $priceClass }}">
                ৳{{ number_format($minimumVariantPrice, 2) }}@if ($maximumVariantPrice > $minimumVariantPrice) – ৳{{ number_format($maximumVariantPrice, 2) }}@endif
            </span>
        @elseif ($product->price !== null)
            @if ($product->catalog_offer)
                <span class="{{ $priceClass }}">৳{{ number_format((float) $product->catalog_offer['price'], 2) }}</span>
                <span class="{{ $comparePriceClass }}">৳{{ number_format((float) $product->price, 2) }}</span>
            @else
                <span class="{{ $priceClass }}">৳{{ number_format((float) $product->price, 2) }}</span>
            @endif
        @endif
        @if (! $hasVariantPriceRange && ! $product->catalog_offer && $product->compare_at_price !== null)
            <span class="{{ $comparePriceClass }}">৳{{ number_format((float) $product->compare_at_price, 2) }}</span>
        @endif
        @if ($minimumPromotionalVariant)
            <span class="w-full inline-flex flex-wrap items-center gap-1.5 pt-1">
                <span class="text-sm font-extrabold text-green-800">৳{{ number_format((float) $minimumPromotionalVariant->catalog_offer['price'], 2) }}</span>
                <span class="text-xs text-[#738078] line-through">৳{{ number_format((float) $minimumPromotionalVariant->price, 2) }}</span>
                @if (filled(trim($minimumPromotionalVariant->catalog_offer['badge'] ?? '')))
                    <span class="inline-flex rounded-full bg-green-100 px-2.5 py-1 text-[11px] font-extrabold text-green-800 ring-1 ring-inset ring-green-200">
                        {{ $minimumPromotionalVariant->catalog_offer['badge'] }}
                    </span>
                @endif
                @if ($minimumPromotionalVariant->catalog_offer['code'])
                    <span class="text-xs font-semibold text-[#4d5c56]">Use code {{ $minimumPromotionalVariant->catalog_offer['code'] }}</span>
                @endif
                @if ($minimumPromotionalVariant->catalog_offer['condition'])
                    <span class="text-xs text-[#66736d]">{{ $minimumPromotionalVariant->catalog_offer['condition'] }}</span>
                @endif
            </span>
        @elseif ($product->catalog_offer)
            <span class="w-full inline-flex flex-wrap items-center gap-1.5 pt-1">
                @if (filled(trim($product->catalog_offer['badge'] ?? '')))
                    <span class="inline-flex rounded-full bg-green-100 px-2.5 py-1 text-[11px] font-extrabold text-green-800 ring-1 ring-inset ring-green-200">
                        {{ $product->catalog_offer['badge'] }}
                    </span>
                @endif
                @if ($product->catalog_offer['code'])
                    <span class="text-xs font-semibold text-[#4d5c56]">Use code {{ $product->catalog_offer['code'] }}</span>
                @endif
                @if ($product->catalog_offer['condition'])
                    <span class="text-xs text-[#66736d]">{{ $product->catalog_offer['condition'] }}</span>
                @endif
            </span>
        @endif
    </div>
@endif