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

                {{-- Product image carousel --}}
                <div class="flex flex-col gap-3">
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
                            data-product-image="{{ $product->primaryImage ? '/storage/' . ltrim($product->primaryImage->image_path, '/') : ($product->images->first() ? '/storage/' . ltrim($product->images->first()->image_path, '/') : '') }}"
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

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const track = document.getElementById('product-carousel-track');
            if (!track) return;

            const slides = Array.from(track.querySelectorAll('.carousel-slide'));
            if (!slides.length) return;

            const prevBtn = document.getElementById('carousel-prev-btn');
            const nextBtn = document.getElementById('carousel-next-btn');
            const counter = document.getElementById('carousel-counter');
            const thumbButtons = Array.from(document.querySelectorAll('.carousel-thumb-btn'));

            let currentIndex = 0;
            let isProgrammatic = false;
            let scrollEndTimer = null;

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
                    behavior: smooth ? 'smooth' : 'auto'
                });

                setActiveSlideIndex(index);

                scrollEndTimer = setTimeout(() => {
                    isProgrammatic = false;
                }, 450);
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
                    const prevIndex = (currentIndex - 1 + slides.length) % slides.length;
                    scrollToSlide(prevIndex);
                });
            }

            if (nextBtn) {
                nextBtn.addEventListener('click', () => {
                    const nextIndex = (currentIndex + 1) % slides.length;
                    scrollToSlide(nextIndex);
                });
            }

            thumbButtons.forEach((btn) => {
                const targetIdx = parseInt(btn.getAttribute('data-target-slide'), 10);
                btn.addEventListener('click', () => {
                    if (!isNaN(targetIdx)) {
                        scrollToSlide(targetIdx);
                    }
                });
            });

            window.addEventListener('resize', () => {
                if (slides[currentIndex]) {
                    track.scrollTo({ left: slides[currentIndex].offsetLeft, behavior: 'auto' });
                }
            }, { passive: true });
        });
    </script>
</x-layouts.app>