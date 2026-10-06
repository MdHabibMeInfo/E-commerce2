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
@endphp

@if ($hasVariantPriceRange || $product->price !== null || $product->compare_at_price !== null)
    <div class="{{ $containerClass }}">
        @if ($hasVariantPriceRange)
            <span class="{{ $priceClass }}">
                ৳{{ number_format($minimumVariantPrice, 2) }}@if ($maximumVariantPrice > $minimumVariantPrice) – ৳{{ number_format($maximumVariantPrice, 2) }}@endif
            </span>
        @elseif ($product->price !== null)
            <span class="{{ $priceClass }}">৳{{ number_format((float) $product->price, 2) }}</span>
        @endif
        @if (! $hasVariantPriceRange && $product->compare_at_price !== null)
            <span class="{{ $comparePriceClass }}">৳{{ number_format((float) $product->compare_at_price, 2) }}</span>
        @endif
    </div>
@endif