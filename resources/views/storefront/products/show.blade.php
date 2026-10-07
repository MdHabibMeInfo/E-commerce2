<x-layouts.app>

    <x-slot:title>
        {{ $product->name }} — {{ config('app.name') }}
    </x-slot:title>

    <x-slot:metaDescription>
        {{ $product->short_description ?: 'Shop ' . $product->name . ' at ' . config('app.name') . '.' }}
    </x-slot:metaDescription>

    <x-slot:canonicalUrl>
        {{ route('products.show', ['product' => $product->slug]) }}
    </x-slot:canonicalUrl>

    <section class="bg-[#fbfaf4]">
        <div class="mx-auto max-w-[1600px] px-4 py-10 sm:px-6 lg:px-8 lg:py-14">

            {{-- Breadcrumb --}}
            <nav
                class="mb-6 text-sm text-[#738078]"
                aria-label="Breadcrumb"
            >
                <a
                    href="{{ route('home') }}"
                    class="font-semibold text-[#145943] transition hover:text-[#a48335]"
                >
                    Home
                </a>

                @if ($product->category)
                    <span class="px-2">/</span>

                    <span>
                        {{ $product->category->name }}
                    </span>
                @endif

                <span class="px-2">/</span>

                <span class="text-[#4d5c56]">
                    {{ $product->name }}
                </span>
            </nav>

            {{-- Product --}}
            <div class="grid gap-8 rounded-2xl border border-[#e3e2d9] bg-white p-5 shadow-sm md:grid-cols-2 md:p-7">

                {{-- Product image carousel --}}
                <div data-product-gallery class="flex flex-col gap-3">
                    <div class="relative overflow-hidden rounded-2xl border border-[#e3e2d9] bg-[#f1f4e5]">
                        @if ($product->images->isNotEmpty())
                            {{-- Carousel Track --}}
                            <div
                                id="product-carousel-track"
                                class="flex w-full overflow-x-auto scroll-smooth snap-x snap-mandatory [scrollbar-width:none] [&::-webkit-scrollbar]:hidden"
                                aria-label="Product images carousel"
                            >
                                @foreach ($product->images as $index => $img)
                                    <div
                                        class="carousel-slide relative flex aspect-square w-full shrink-0 snap-center items-center justify-center p-2"
                                        data-slide-index="{{ $index }}"
                                    >
                                        <img
                                            src="/storage/{{ ltrim($img->image_path, '/') }}"
                                            alt="{{ $img->alt_text ?: $product->name }}"
                                            width="800"
                                            height="800"
                                            {{ $index === 0 ? 'fetchpriority="high"' : 'loading="lazy"' }}
                                            decoding="async"
                                            class="aspect-square h-full w-full object-contain"
                                        >
                                        @if ($img->is_primary)
                                            <span class="absolute left-3 top-3 z-10 rounded-md bg-[#145943] px-2 py-0.5 text-xs font-bold text-white shadow-xs">
                                                ★ Primary
                                            </span>
                                        @endif
                                    </div>
                                @endforeach
                            </div>

                            {{-- Navigation Arrows & Counter if multiple images --}}
                            @if ($product->images->count() > 1)
                                <button
                                    type="button"
                                    id="carousel-prev-btn"
                                    class="absolute left-3 top-1/2 z-20 -translate-y-1/2 flex h-10 w-10 items-center justify-center rounded-full bg-white/90 shadow-md text-[#145943] transition hover:bg-white hover:scale-105 active:scale-95 focus:outline-none"
                                    aria-label="Previous image"
                                >
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7" />
                                    </svg>
                                </button>
                                <button
                                    type="button"
                                    id="carousel-next-btn"
                                    class="absolute right-3 top-1/2 z-20 -translate-y-1/2 flex h-10 w-10 items-center justify-center rounded-full bg-white/90 shadow-md text-[#145943] transition hover:bg-white hover:scale-105 active:scale-95 focus:outline-none"
                                    aria-label="Next image"
                                >
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" />
                                    </svg>
                                </button>
                                <span
                                    id="carousel-counter"
                                    class="absolute right-3 top-3 z-20 rounded-full bg-black/60 px-2.5 py-1 text-[11px] font-bold text-white shadow-xs backdrop-blur-xs"
                                >
                                    1 / {{ $product->images->count() }}
                                </span>
                            @endif
                        @else
                            <div
                                class="grid aspect-square place-items-center text-8xl"
                                aria-hidden="true"
                            >
                                🛍️
                            </div>
                        @endif
                    </div>

                    {{-- Synced Thumbnail Strip --}}
                    @if ($product->images->count() > 1)
                        <div class="flex items-center gap-2.5 overflow-x-auto pb-1" id="product-thumbnails">
                            @foreach ($product->images as $index => $img)
                                <button
                                    type="button"
                                    data-target-slide="{{ $index }}"
                                    class="carousel-thumb-btn group relative aspect-square h-16 w-16 shrink-0 overflow-hidden rounded-xl border-2 transition-all p-1 bg-white hover:border-[#145943] focus:outline-none {{ $index === 0 ? 'border-[#145943] ring-2 ring-[#145943]/20' : 'border-[#e3e2d9]' }}"
                                    aria-label="Go to image {{ $index + 1 }}"
                                >
                                    <img
                                        src="/storage/{{ ltrim($img->image_path, '/') }}"
                                        alt="{{ $img->alt_text ?: $product->name }}"
                                        class="h-full w-full rounded-lg object-cover"
                                        loading="lazy"
                                    >
                                    @if ($img->is_primary)
                                        <span class="absolute bottom-1 right-1 h-2 w-2 rounded-full bg-[#145943] ring-1 ring-white"></span>
                                    @endif
                                </button>
                            @endforeach
                        </div>
                    @endif
                </div>

                {{-- Product information --}}
                <div class="flex flex-col justify-center">
                    <h1 class="text-3xl font-extrabold tracking-tight text-[#145943] sm:text-4xl">
                        {{ $product->name }}
                    </h1>

                    @if ($product->type === 'variable')
                        @php
                            $startingVariant = $product->variants
                                ->where('is_out_of_stock', false)
                                ->sortBy('price')
                                ->first();
                        @endphp
                        <div class="mt-6 flex flex-wrap items-center gap-3">
                            <span data-variant-price-display class="text-2xl font-extrabold text-[#145943]">
                                @if ($startingVariant)
                                    From ৳{{ number_format((float) ($startingVariant->catalog_offer['price'] ?? $startingVariant->price), 2) }}
                                @else
                                    No variants available
                                @endif
                            </span>
                            <span data-variant-compare-display class="{{ $startingVariant?->catalog_offer ? 'text-sm text-[#9aa19c] line-through' : 'hidden text-sm text-[#9aa19c] line-through' }}">
                                {{ $startingVariant?->catalog_offer ? '৳'.number_format((float) $startingVariant->price, 2) : '' }}
                            </span>
                            @php
                                $startingBadge = trim($startingVariant?->catalog_offer['badge'] ?? '');
                            @endphp
                            <span data-variant-offer-display class="{{ $startingVariant?->catalog_offer && $startingBadge !== '' ? 'inline-flex' : 'hidden' }} rounded-full bg-green-100 px-2.5 py-1 text-xs font-extrabold text-green-800 ring-1 ring-inset ring-green-200">
                                {{ $startingBadge }}
                            </span>
                            <span data-variant-offer-terms class="text-xs text-[#66736d]">
                                @if (data_get($startingVariant?->catalog_offer, 'code')) Use code {{ data_get($startingVariant->catalog_offer, 'code') }} @endif
                                @if (data_get($startingVariant?->catalog_offer, 'condition')) · {{ data_get($startingVariant->catalog_offer, 'condition') }} @endif
                            </span>
                        </div>

                        <label for="product-variant-select" class="mt-5 block text-sm font-bold text-[#173b32]">Choose a variant</label>
                        <select
                            id="product-variant-select"
                            data-product-variant-select
                            data-product-unavailable="{{ $product->is_out_of_stock ? 'true' : 'false' }}"
                            class="mt-2 h-12 w-full max-w-md rounded-lg border border-[#d9ddd4] bg-white px-3 text-sm text-[#173b32] outline-none focus:border-[#145943] focus:ring-2 focus:ring-[#145943]/15"
                        >
                            <option value="">Select an available option</option>
                            @foreach ($product->variants as $variant)
                                <option
                                    value="{{ $variant->id }}"
                                    data-price="{{ $variant->price }}"
                                    data-compare-at-price="{{ $variant->compare_at_price }}"
                                    data-promotion-price="{{ $variant->catalog_offer['price'] ?? '' }}"
                                    data-promotion-badge="{{ $variant->catalog_offer['badge'] ?? '' }}"
                                    data-promotion-code="{{ $variant->catalog_offer['code'] ?? '' }}"
                                    data-promotion-condition="{{ $variant->catalog_offer['condition'] ?? '' }}"
                                    data-unavailable="{{ $variant->is_out_of_stock ? 'true' : 'false' }}"
                                    @selected((string) $variant->id === $selectedVariantId)
                                    @disabled($variant->is_out_of_stock || $product->is_out_of_stock)
                                >
                                    {{ $variant->displayName() }} · ৳{{ number_format((float) ($variant->catalog_offer['price'] ?? $variant->price), 2) }}{{ $variant->catalog_offer ? ' · '.$variant->catalog_offer['badge'] : '' }}{{ $variant->is_out_of_stock ? ' · Out of stock' : '' }}
                                </option>
                            @endforeach
                        </select>
                        <p data-variant-selection-status class="mt-2 min-h-5 text-sm text-[#738078]">
                            @if ($product->variants->isEmpty())
                                No active variants are available.
                            @else
                                Select an available option to continue.
                            @endif
                        </p>
                    @elseif ($product->price !== null || $product->compare_at_price !== null)
                        <div class="mt-6 flex flex-wrap items-center gap-3">
                            @if ($product->price !== null)
                                <span class="text-2xl font-extrabold text-[#145943]">৳{{ number_format((float) ($product->catalog_offer['price'] ?? $product->price), 2) }}</span>
                            @endif

                            @if ($product->catalog_offer)
                                <span class="text-sm text-[#9aa19c] line-through">৳{{ number_format((float) $product->price, 2) }}</span>
                            @elseif ($product->compare_at_price !== null)
                                <span class="text-sm text-[#9aa19c] line-through">
                                    ৳{{ number_format((float) $product->compare_at_price, 2) }}
                                </span>
                            @endif
                        </div>
                        @if ($product->catalog_offer)
                            <p class="mt-2 inline-flex flex-wrap items-center gap-1.5">
                                @if (filled(trim($product->catalog_offer['badge'] ?? '')))
                                    <span class="inline-flex rounded-full bg-green-100 px-2.5 py-1 text-xs font-extrabold text-green-800 ring-1 ring-inset ring-green-200">{{ $product->catalog_offer['badge'] }}</span>
                                @endif
                                @if ($product->catalog_offer['code'])
                                    <span class="text-xs font-semibold text-[#4d5c56]">Use code {{ $product->catalog_offer['code'] }}</span>
                                @endif
                                @if ($product->catalog_offer['condition'])
                                    <span class="text-xs text-[#66736d]">{{ $product->catalog_offer['condition'] }}</span>
                                @endif
                            </p>
                        @endif
                    @endif

                    <div class="mt-7 flex flex-col gap-3 sm:flex-row">
                        @if ($product->is_out_of_stock)
                            <button type="button" disabled aria-label="{{ $product->name }} is out of stock" class="inline-flex w-full cursor-not-allowed items-center justify-center rounded-xl border border-[#dfe3cf] bg-[#f4f1ea] px-6 py-3.5 text-sm font-bold text-[#738078] sm:w-fit">
                                Out of stock
                            </button>
                        @else
                            <button
                                type="button"
                                aria-label="Add {{ $product->name }} to cart"
                                data-product-id="{{ $product->id }}"
                                data-product-name="{{ $product->name }}"
                                data-product-type="{{ $product->type }}"
                                data-product-url="{{ route('products.show', ['product' => $product->slug]) }}"
                                data-product-price="{{ (float) ($startingVariant?->price ?? $product->price) }}"
                                data-product-image="{{ $product->primaryImage ? '/storage/' . ltrim($product->primaryImage->image_path, '/') : ($product->images->first() ? '/storage/' . ltrim($product->images->first()->image_path, '/') : '') }}"
                                data-product-emoji="🛍️"
                                @if ($product->type === 'variable') data-product-variant-purchase @disabled(true) @endif
                                class="inline-flex w-full items-center justify-center rounded-xl bg-[#145943] px-6 py-3.5 text-sm font-bold text-white transition-colors hover:bg-[#b28d35] disabled:cursor-not-allowed disabled:opacity-50 sm:w-fit"
                            >
                                Add to Cart
                            </button>

                            <button
                                type="button"
                                aria-label="Buy {{ $product->name }} now"
                                data-buy-now
                                data-product-id="{{ $product->id }}"
                                data-product-name="{{ $product->name }}"
                                data-product-type="{{ $product->type }}"
                                data-product-url="{{ route('products.show', ['product' => $product->slug]) }}"
                                data-product-price="{{ (float) ($startingVariant?->price ?? $product->price) }}"
                                data-product-image="{{ $product->primaryImage ? '/storage/' . ltrim($product->primaryImage->image_path, '/') : ($product->images->first() ? '/storage/' . ltrim($product->images->first()->image_path, '/') : '') }}"
                                data-product-emoji="🛍️"
                                @if ($product->type === 'variable') data-product-variant-purchase @disabled(true) @endif
                                class="inline-flex w-full items-center justify-center rounded-xl bg-[#d7b45a] px-6 py-3.5 text-sm font-bold text-[#173b32] transition-colors hover:bg-[#e5c774] disabled:cursor-not-allowed disabled:opacity-50 sm:w-fit"
                            >
                                Buy Now
                            </button>
                        @endif

                        <button
                            type="button"
                            class="inline-flex w-full items-center justify-center rounded-xl border border-[#d9d8ce] px-6 py-3.5 text-sm font-bold text-[#145943] transition-colors hover:border-[#145943] hover:bg-[#f5f7ed] sm:w-fit"
                            aria-label="Add {{ $product->name }} to wishlist"
                        >
                            ♡ Wishlist
                        </button>

                    </div>

                </div>

            </div>

            <section id="product-details" class="mt-10 border-y border-[#e3e2d9] py-8 sm:py-10">
                <div class="max-w-4xl">
                    <h2 class="mt-2 text-2xl font-extrabold text-[#145943]">Product Details</h2>

                    @if ($product->short_description)
                        <p class="mt-4 text-base font-medium leading-7 text-[#44564d]">{{ $product->short_description }}</p>
                    @endif

                    @if ($product->description)
                        <div class="mt-4 whitespace-pre-line text-sm leading-7 text-[#66736d]">{{ $product->description }}</div>
                    @elseif (! $product->short_description)
                        <p class="mt-4 text-sm leading-7 text-[#66736d]">Detailed product information will be added soon.</p>
                    @endif
                </div>
            </section>

            <section id="customer-reviews" class="mt-10 scroll-mt-8">
                @php
                    $reviewAverage = (float) ($product->reviews_avg_rating ?? 0);
                @endphp

                <div class="flex flex-col justify-between gap-5 border-b border-[#e3e2d9] pb-5 sm:flex-row sm:items-end">
                    <div>
                        <h2 class="mt-2 text-2xl font-extrabold text-[#145943]">Customer Reviews</h2>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="text-3xl font-extrabold text-[#145943]">{{ number_format($reviewAverage, 1) }}</span>
                        <span>
                            <span class="flex text-lg leading-none" aria-label="{{ number_format($reviewAverage, 1) }} out of 5 stars">
                                @for ($star = 1; $star <= 5; $star++)
                                    <span class="{{ $star <= round($reviewAverage) ? 'text-[#f5b700]' : 'text-[#d7d9d0]' }}" aria-hidden="true">★</span>
                                @endfor
                            </span>
                            <span class="mt-1 block text-xs text-[#66736d]">{{ $product->reviews_count }} {{ $product->reviews_count === 1 ? 'review' : 'reviews' }}</span>
                        </span>
                    </div>
                </div>

                <div class="grid gap-10 pt-6 lg:grid-cols-[minmax(0,1fr)_minmax(320px,0.8fr)] lg:gap-14">
                    <div>
                        @forelse ($product->approvedReviews as $review)
                            <article class="border-b border-[#e8e6db] py-5 first:pt-0">
                                <div class="flex flex-wrap items-start justify-between gap-3">
                                    <div>
                                        <h3 class="font-bold text-[#173b32]">{{ $review->name }}</h3>
                                    </div>
                                    <div class="flex text-lg leading-none" aria-label="{{ $review->rating }} out of 5 stars">
                                        @for ($star = 1; $star <= 5; $star++)
                                            <span class="{{ $star <= $review->rating ? 'text-[#f5b700]' : 'text-[#d7d9d0]' }}" aria-hidden="true">★</span>
                                        @endfor
                                    </div>
                                </div>
                                <p class="mt-3 whitespace-pre-line text-sm leading-6 text-[#59675f]">{{ $review->body }}</p>

                                @if ($review->images)
                                    <div class="mt-4 flex flex-wrap gap-3">
                                        @foreach ($review->images as $reviewImage)
                                            <a href="{{ asset('storage/' . ltrim($reviewImage, '/')) }}" target="_blank" rel="noopener noreferrer" class="block h-20 w-20 overflow-hidden rounded-md border border-[#e3e2d9] bg-white">
                                                <img src="{{ asset('storage/' . ltrim($reviewImage, '/')) }}" alt="Photo shared by {{ $review->name }}" loading="lazy" class="h-full w-full object-cover">
                                            </a>
                                        @endforeach
                                    </div>
                                @endif
                            </article>
                        @empty
                            <p class="py-4 text-sm text-[#66736d]">No reviews yet. Share your experience with this product.</p>
                        @endforelse
                    </div>

                    <div id="review-form" class="border-t border-[#e3e2d9] pt-7 lg:border-l lg:border-t-0 lg:pl-10 lg:pt-0">
                        <h3 class="text-lg font-bold text-[#173b32]">Write a review</h3>
                        <p class="mt-1 text-sm text-[#66736d]">Your email address will not be published.</p>

                        @if (session('status'))
                            <p role="status" class="mt-4 border-l-4 border-[#145943] bg-[#edf5eb] px-4 py-3 text-sm text-[#145943]">{{ session('status') }}</p>
                        @endif

                        @if ($errors->any())
                            <div role="alert" class="mt-4 grid gap-1 border-l-4 border-red-500 bg-red-50 px-4 py-3 text-sm text-red-800">
                                @foreach ($errors->all() as $error)
                                    <p>{{ $error }}</p>
                                @endforeach
                            </div>
                        @endif

                        <form method="POST" action="{{ route('products.reviews.store', ['product' => $product->slug]) }}" enctype="multipart/form-data" class="mt-5 grid gap-4">
                            @csrf

                            <fieldset>
                                <legend class="text-sm font-semibold text-[#26352f]">Your rating <span class="text-red-600" aria-hidden="true">*</span></legend>
                                <div class="mt-2 flex gap-1" role="radiogroup" aria-label="Your rating">
                                    @for ($rating = 1; $rating <= 5; $rating++)
                                        <label class="grid h-10 w-10 cursor-pointer place-items-center rounded-md focus-within:ring-2 focus-within:ring-[#145943]">
                                            <input type="radio" name="rating" value="{{ $rating }}" data-review-rating-input class="sr-only" aria-label="{{ $rating }} {{ $rating === 1 ? 'star' : 'stars' }}" @checked((int) old('rating', 5) === $rating) required>
                                            <span data-review-rating-star="{{ $rating }}" class="text-3xl leading-none {{ $rating <= (int) old('rating', 5) ? 'text-[#f5b700]' : 'text-[#c8ccc2]' }}" aria-hidden="true">★</span>
                                        </label>
                                    @endfor
                                </div>
                                @error('rating')
                                    <p class="mt-1 text-xs text-red-700">{{ $message }}</p>
                                @enderror
                            </fieldset>

                            <div class="grid gap-4 sm:grid-cols-2">
                                <label class="grid gap-1.5 text-sm font-semibold text-[#26352f]">
                                    Name <span class="text-red-600" aria-hidden="true">*</span>
                                    <input type="text" name="name" value="{{ old('name') }}" autocomplete="name" required maxlength="120" class="h-11 rounded-md border border-[#d9ddd4] bg-white px-3 font-normal outline-none focus:border-[#145943] focus:ring-2 focus:ring-[#145943]/15">
                                    @error('name')
                                        <span class="text-xs font-normal text-red-700">{{ $message }}</span>
                                    @enderror
                                </label>
                                <label class="grid gap-1.5 text-sm font-semibold text-[#26352f]">
                                    Email <span class="text-red-600" aria-hidden="true">*</span>
                                    <input type="email" name="email" value="{{ old('email') }}" autocomplete="email" required maxlength="255" class="h-11 rounded-md border border-[#d9ddd4] bg-white px-3 font-normal outline-none focus:border-[#145943] focus:ring-2 focus:ring-[#145943]/15">
                                    @error('email')
                                        <span class="text-xs font-normal text-red-700">{{ $message }}</span>
                                    @enderror
                                </label>
                            </div>

                            <label class="grid gap-1.5 text-sm font-semibold text-[#26352f]">
                                Your review <span class="text-red-600" aria-hidden="true">*</span>
                                <textarea name="body" rows="5" required maxlength="3000" class="resize-y rounded-md border border-[#d9ddd4] bg-white px-3 py-2.5 font-normal leading-6 outline-none focus:border-[#145943] focus:ring-2 focus:ring-[#145943]/15">{{ old('body') }}</textarea>
                                @error('body')
                                    <span class="text-xs font-normal text-red-700">{{ $message }}</span>
                                @enderror
                            </label>

                            <div>
                                <p class="text-sm font-semibold text-[#26352f]">Photos <span class="font-normal text-[#77837c]">(optional, up to 3)</span></p>
                                <label for="review-images" data-review-dropzone class="mt-2 flex min-h-32 cursor-pointer flex-col items-center justify-center gap-2 rounded-md border border-dashed border-[#aebbb0] bg-[#f7faf5] px-4 py-5 text-center transition-colors hover:border-[#145943] hover:bg-[#f0f6ed] focus-within:ring-2 focus-within:ring-[#145943]">
                                    <input id="review-images" type="file" name="images[]" accept="image/jpeg,image/png,image/webp" multiple data-review-image-input class="sr-only">
                                    <svg class="h-7 w-7 text-[#145943]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
                                        <path d="M4 7h3l1.5-2h7L17 7h3v12H4z" />
                                        <circle cx="12" cy="13" r="3.5" />
                                    </svg>
                                    <span class="text-sm font-semibold text-[#173b32]">Drop photos here or browse files</span>
                                    <span data-review-image-message class="text-xs font-normal text-[#77837c]">Up to 3 images, 5 MB each</span>
                                </label>
                                <div data-review-image-preview class="mt-3 flex flex-wrap gap-2"></div>
                                @error('images')
                                    <p class="mt-1 text-xs text-red-700">{{ $message }}</p>
                                @enderror
                                @error('images.0')
                                    <p class="mt-1 text-xs text-red-700">{{ $message }}</p>
                                @enderror
                            </div>

                            <button type="submit" class="mt-1 inline-flex h-11 items-center justify-center rounded-md bg-[#145943] px-5 text-sm font-bold text-white transition hover:bg-[#0f4d3a] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#145943] focus-visible:ring-offset-2 sm:w-fit">Submit Review</button>
                        </form>
                    </div>
                </div>
            </section>

        </div>
    </section>

    @if ($relatedProducts->isNotEmpty())
        <section id="related-products" class="bg-[#fbfaf4]">
            <div class="mx-auto max-w-[1600px] px-4 py-10 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between gap-3 border-b border-[#e7e2d4] pb-3">
                    <h2 class="text-xl font-extrabold text-[#26352f] sm:text-2xl">Related Products</h2>
                    <a href="{{ route('shop') }}" class="inline-flex shrink-0 items-center gap-2 text-sm font-bold text-[#145943] transition hover:text-[#a48335]">
                        More Products
                        <span aria-hidden="true">&rarr;</span>
                    </a>
                </div>

                <div class="relative mt-4">
                    <div class="overflow-hidden">
                        <div data-related-product-track class="flex gap-3 overflow-x-auto scroll-smooth pb-2 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden sm:gap-4" aria-label="Related products">
                            @foreach ($relatedProducts as $relatedProduct)
                                @include('storefront.partials.home-product-card', ['product' => $relatedProduct])
                            @endforeach
                        </div>
                    </div>
                    <div data-related-product-pagination class="flex items-center justify-center gap-2.5 pt-4" role="group" aria-label="Related product pages"></div>
                </div>
            </div>
        </section>
    @endif

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const ratingInputs = Array.from(document.querySelectorAll('[data-review-rating-input]'));
            const ratingStars = Array.from(document.querySelectorAll('[data-review-rating-star]'));
            const updateRatingStars = () => {
                const selectedRating = Number(ratingInputs.find((input) => input.checked)?.value || 0);

                ratingStars.forEach((star) => {
                    const isSelected = Number(star.dataset.reviewRatingStar) <= selectedRating;
                    star.classList.toggle('text-[#f5b700]', isSelected);
                    star.classList.toggle('text-[#c8ccc2]', !isSelected);
                });
            };

            ratingInputs.forEach((input) => input.addEventListener('change', updateRatingStars));
            updateRatingStars();

            const reviewImageInput = document.querySelector('[data-review-image-input]');
            const reviewDropzone = document.querySelector('[data-review-dropzone]');
            const reviewImagePreview = document.querySelector('[data-review-image-preview]');
            const reviewImageMessage = document.querySelector('[data-review-image-message]');

            if (reviewImageInput && reviewDropzone && reviewImagePreview && reviewImageMessage) {
                const renderReviewImages = () => {
                    reviewImagePreview.querySelectorAll('img').forEach((image) => URL.revokeObjectURL(image.src));
                    reviewImagePreview.replaceChildren();

                    const selectedImages = Array.from(reviewImageInput.files || []);
                    const hasTooManyImages = selectedImages.length > 3;
                    reviewImageInput.setCustomValidity(hasTooManyImages ? 'Choose up to 3 images.' : '');
                    reviewImageMessage.textContent = hasTooManyImages
                        ? 'Choose no more than 3 images.'
                        : selectedImages.length
                            ? `${selectedImages.length} ${selectedImages.length === 1 ? 'image' : 'images'} selected`
                            : 'Up to 3 images, 5 MB each';
                    reviewImageMessage.classList.toggle('text-red-700', hasTooManyImages);

                    selectedImages.slice(0, 3).forEach((image) => {
                        if (!image.type.startsWith('image/')) return;

                        const preview = document.createElement('img');
                        preview.src = URL.createObjectURL(image);
                        preview.alt = image.name;
                        preview.className = 'h-20 w-20 rounded-md border border-[#d9ddd4] object-cover';
                        reviewImagePreview.appendChild(preview);
                    });
                };

                reviewImageInput.addEventListener('change', renderReviewImages);
                reviewDropzone.addEventListener('dragover', (event) => {
                    event.preventDefault();
                    reviewDropzone.classList.add('border-[#145943]', 'bg-[#f0f6ed]');
                });
                reviewDropzone.addEventListener('dragleave', () => {
                    reviewDropzone.classList.remove('border-[#145943]', 'bg-[#f0f6ed]');
                });
                reviewDropzone.addEventListener('drop', (event) => {
                    event.preventDefault();
                    reviewDropzone.classList.remove('border-[#145943]', 'bg-[#f0f6ed]');

                    if (event.dataTransfer?.files) {
                        reviewImageInput.files = event.dataTransfer.files;
                        renderReviewImages();
                    }
                });
            }

            const track = document.getElementById('product-carousel-track');
            if (!track) return;

            const slides = Array.from(track.querySelectorAll('.carousel-slide'));
            if (!slides.length) return;

            const prevBtn = document.getElementById('carousel-prev-btn');
            const nextBtn = document.getElementById('carousel-next-btn');
            const counter = document.getElementById('carousel-counter');
            const thumbButtons = Array.from(document.querySelectorAll('.carousel-thumb-btn'));
            const gallery = track.closest('[data-product-gallery]');
            const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

            let currentIndex = 0;
            let isProgrammatic = false;
            let scrollEndTimer = null;
            let autoSlideTimer = null;
            let autoDirection = 1;
            let isHovered = false;
            let isFocused = false;
            let touchResumeTimer = null;

            function setActiveSlideIndex(newIndex) {
                if (newIndex < 0 || newIndex >= slides.length) return;
                currentIndex = newIndex;

                if (counter) {
                    counter.textContent = `${currentIndex + 1} / ${slides.length}`;
                }

                thumbButtons.forEach((btn, idx) => {
                    const isActive = idx === currentIndex;
                    btn.classList.toggle('border-[#145943]', isActive);
                    btn.classList.toggle('ring-2', isActive);
                    btn.classList.toggle('ring-[#145943]/20', isActive);
                    btn.classList.toggle('border-[#e3e2d9]', !isActive);
                    if (isActive) {
                        btn.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'nearest' });
                    }
                });
            }

            function scrollToSlide(index, smooth = true) {
                if (!slides[index]) return;
                isProgrammatic = true;
                clearTimeout(scrollEndTimer);

                track.scrollTo({
                    left: slides[index].offsetLeft,
                    behavior: smooth && !prefersReducedMotion ? 'smooth' : 'auto'
                });

                setActiveSlideIndex(index);

                scrollEndTimer = setTimeout(() => {
                    isProgrammatic = false;
                }, 450);
            }

            function stopAutoSlide() {
                window.clearInterval(autoSlideTimer);
                autoSlideTimer = null;
            }

            function startAutoSlide() {
                stopAutoSlide();
                if (prefersReducedMotion || slides.length < 2 || isHovered || isFocused) return;

                autoSlideTimer = window.setInterval(() => {
                    let nextIndex = currentIndex + autoDirection;

                    if (nextIndex < 0 || nextIndex >= slides.length) {
                        autoDirection *= -1;
                        nextIndex = currentIndex + autoDirection;
                    }

                    scrollToSlide(nextIndex);
                }, 3000);
            }

            if ('IntersectionObserver' in window) {
                const observer = new IntersectionObserver((entries) => {
                    if (isProgrammatic) return;
                    entries.forEach((entry) => {
                        if (entry.isIntersecting) {
                            const idx = parseInt(entry.target.getAttribute('data-slide-index'), 10);
                            if (!isNaN(idx)) {
                                setActiveSlideIndex(idx);
                            }
                        }
                    });
                }, {
                    root: track,
                    threshold: 0.55
                });

                slides.forEach((slide) => observer.observe(slide));
            } else {
                track.addEventListener('scroll', () => {
                    if (isProgrammatic) return;
                    const scrollLeft = track.scrollLeft;
                    const slideWidth = track.clientWidth || 1;
                    const current = Math.round(scrollLeft / slideWidth);
                    setActiveSlideIndex(current);
                }, { passive: true });
            }

            if (prevBtn) {
                prevBtn.addEventListener('click', () => {
                    autoDirection = -1;
                    const prevIndex = (currentIndex - 1 + slides.length) % slides.length;
                    scrollToSlide(prevIndex);
                    startAutoSlide();
                });
            }

            if (nextBtn) {
                nextBtn.addEventListener('click', () => {
                    autoDirection = 1;
                    const nextIndex = (currentIndex + 1) % slides.length;
                    scrollToSlide(nextIndex);
                    startAutoSlide();
                });
            }

            thumbButtons.forEach((btn) => {
                const targetIdx = parseInt(btn.getAttribute('data-target-slide'), 10);
                btn.addEventListener('click', () => {
                    if (!isNaN(targetIdx)) {
                        autoDirection = targetIdx >= currentIndex ? 1 : -1;
                        scrollToSlide(targetIdx);
                        startAutoSlide();
                    }
                });
            });

            if (gallery) {
                gallery.addEventListener('mouseenter', () => {
                    isHovered = true;
                    stopAutoSlide();
                });

                gallery.addEventListener('mouseleave', () => {
                    isHovered = false;
                    startAutoSlide();
                });

                gallery.addEventListener('focusin', () => {
                    isFocused = true;
                    stopAutoSlide();
                });

                gallery.addEventListener('focusout', (event) => {
                    if (!gallery.contains(event.relatedTarget)) {
                        isFocused = false;
                        startAutoSlide();
                    }
                });

                gallery.addEventListener('touchstart', () => {
                    stopAutoSlide();
                    window.clearTimeout(touchResumeTimer);
                }, { passive: true });

                gallery.addEventListener('touchend', () => {
                    touchResumeTimer = window.setTimeout(startAutoSlide, 1500);
                }, { passive: true });
            }

            window.addEventListener('resize', () => {
                if (slides[currentIndex]) {
                    track.scrollTo({ left: slides[currentIndex].offsetLeft, behavior: 'auto' });
                }
            }, { passive: true });

            startAutoSlide();
        });
    </script>
</x-layouts.app>