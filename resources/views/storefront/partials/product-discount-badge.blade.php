@php
    $variantOffers = $product->type === 'variable'
        ? $product->variants->filter(fn ($variant) => $variant->catalog_offer !== null)
        : collect();
    $discountOffer = $variantOffers
        ->sortBy(fn ($variant) => $variant->catalog_offer['price'])
        ->first()?->catalog_offer;
    $discountOffer ??= $product->catalog_offer;
@endphp

@if ($discountOffer && filled(trim($discountOffer['badge'] ?? '')))
    <span class="absolute right-2 top-2 z-10 max-w-[calc(100%-1rem)] rounded-[3px] bg-green-100 px-2 py-1 text-[10px] font-extrabold text-green-800 ring-1 ring-inset ring-green-200 sm:text-xs">
        {{ $discountOffer['badge'] }}
    </span>
@endif
