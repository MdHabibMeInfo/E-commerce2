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
        <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8 lg:py-14">

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

                {{-- Product image --}}
                <div class="overflow-hidden rounded-xl bg-[#f1f4e5]">

                    @if ($product->images->isNotEmpty())

                        <img
                            src="{{ asset('storage/' . $product->images->first()->image_path) }}"
                            alt="{{ $product->images->first()->alt_text ?: $product->name }}"
                            width="800"
                            height="800"
                            fetchpriority="high"
                            decoding="async"
                            class="aspect-square h-full w-full object-contain"
                        >

                    @else

                        <div
                            class="grid aspect-square place-items-center text-8xl"
                            aria-hidden="true"
                        >
                            🛍️
                        </div>

                    @endif

                </div>

                {{-- Product information --}}
                <div class="flex flex-col justify-center">

                    @if ($product->category)
                        <p class="text-xs font-bold uppercase tracking-[0.18em] text-[#a48335]">
                            {{ $product->category->name }}
                        </p>
                    @endif

                    <h1 class="mt-2 text-3xl font-extrabold tracking-tight text-[#145943] sm:text-4xl">
                        {{ $product->name }}
                    </h1>

                    @if ($product->short_description)
                        <p class="mt-4 leading-7 text-[#66736d]">
                            {{ $product->short_description }}
                        </p>
                    @endif

                    {{-- Price --}}
                    @if ($product->price !== null || $product->compare_at_price !== null)

                        <div class="mt-6 flex flex-wrap items-center gap-3">

                            @if ($product->price !== null)
                                <span class="text-2xl font-extrabold text-[#145943]">
                                    ৳{{ number_format((float) $product->price, 2) }}
                                </span>
                            @endif

                            @if ($product->compare_at_price !== null)
                                <span class="text-sm text-[#9aa19c] line-through">
                                    ৳{{ number_format((float) $product->compare_at_price, 2) }}
                                </span>
                            @endif

                        </div>

                    @endif

                    {{-- Cart placeholder --}}
                    <div class="mt-7 flex flex-col gap-3 sm:flex-row">

                        <button
                            type="button"
                            aria-label="Add {{ $product->name }} to cart"
                            data-product-id="{{ $product->id }}"
                            data-product-name="{{ $product->name }}"
                            data-product-price="{{ (float) $product->price }}"
                            data-product-emoji="🛍️"
                            class="inline-flex w-full items-center justify-center rounded-xl bg-[#145943] px-6 py-3.5 text-sm font-bold text-white transition-colors hover:bg-[#b28d35] sm:w-fit"
                        >
                            Add to Cart
                        </button>

                        <a
                            href="{{ route('checkout') }}"
                            class="inline-flex w-full items-center justify-center rounded-xl bg-[#d7b45a] px-6 py-3.5 text-sm font-bold text-[#173b32] transition-colors hover:bg-[#e5c774] sm:w-fit"
                        >
                            Buy Now
                        </a>

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

            <div class="mt-8 rounded-2xl border border-[#e3e2d9] bg-white p-5 shadow-sm sm:p-7">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.18em] text-[#a48335]">Customer Reviews</p>
                        <h2 class="mt-2 text-2xl font-extrabold text-[#145943]">What buyers are saying</h2>
                    </div>
                    <span class="rounded-full bg-[#edf1d8] px-3 py-1.5 text-xs font-bold text-[#145943]">4.8/5 average</span>
                </div>

                <div class="mt-6 grid gap-4 md:grid-cols-3">
                    @foreach([
                        ['name' => 'Nabila', 'text' => 'Fresh, clean, and exactly as described. Delivery was fast and packaging was neat.', 'rating' => '★★★★★'],
                        ['name' => 'Sami', 'text' => 'Very good quality products with a reliable service. I will order again.', 'rating' => '★★★★★'],
                        ['name' => 'Rafi', 'text' => 'The fruit quality is excellent and the site is easy to shop from. COD option is great.', 'rating' => '★★★★☆'],
                    ] as $review)
                        <div class="rounded-2xl border border-[#e7e5da] bg-[#fafaf5] p-4">
                            <div class="flex items-center justify-between">
                                <p class="font-bold text-[#173b32]">{{ $review['name'] }}</p>
                                <span class="text-sm text-[#f5b700]">{{ $review['rating'] }}</span>
                            </div>
                            <p class="mt-3 text-sm leading-6 text-[#66736d]">{{ $review['text'] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>

        </div>
    </section>

</x-layouts.app>