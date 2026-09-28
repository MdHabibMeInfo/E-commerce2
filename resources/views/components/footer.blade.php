<footer class="bg-[#0f4d3a] text-white">
    <div class="mx-auto max-w-[1600px] px-4 py-10 sm:px-6 lg:px-8">
        <div class="grid gap-10 md:grid-cols-2 lg:grid-cols-4">
            <div class="lg:col-span-1">
                <a href="{{ route('home') }}" class="inline-flex items-center">
                    <img
                        src="{{ asset('images/brand/ziyanmart-logo.png') }}"
                        alt="{{ config('app.name') }}"
                        class="h-12 w-auto brightness-0 invert"
                    >
                </a>
                <p class="mt-4 max-w-xs text-sm leading-6 text-white/65">
                    Fresh, quality products delivered to your door with a simple and secure shopping experience.
                </p>
            </div>

            <div>
                <h2 class="text-sm font-bold uppercase tracking-wider text-[#e2c775]">Shop</h2>
                <div class="mt-4 grid gap-3 text-sm text-white/70">
                    <a href="{{ route('shop') }}" class="transition hover:text-white">All Products</a>
                    <a href="{{ route('shop') }}" class="transition hover:text-white">Categories</a>
                    <a href="{{ route('home') }}#featured-products" class="transition hover:text-white">Featured Products</a>
                    <a href="{{ route('checkout') }}" class="transition hover:text-white">Checkout</a>
                </div>
            </div>

            <div>
                <h2 class="text-sm font-bold uppercase tracking-wider text-[#e2c775]">Information</h2>
                <div class="mt-4 grid gap-3 text-sm text-white/70">
                    <a href="#" class="transition hover:text-white">About Us</a>
                    <a href="#" class="transition hover:text-white">Contact</a>
                    <a href="#" class="transition hover:text-white">Privacy Policy</a>
                    <a href="#" class="transition hover:text-white">Terms &amp; Conditions</a>
                </div>
            </div>

            <div>
                <h2 class="text-sm font-bold uppercase tracking-wider text-[#e2c775]">Need Help?</h2>
                <p class="mt-4 text-sm leading-6 text-white/70">
                    Our support team is here to help with your orders and shopping experience.
                </p>
                <a href="#" class="mt-4 inline-flex rounded-full border border-white/20 px-5 py-2.5 text-sm font-semibold transition hover:border-[#e2c775] hover:text-[#e2c775]">
                    Contact Support
                </a>
            </div>
        </div>

        <div class="mt-10 flex flex-col gap-4 border-t border-white/10 pt-6 text-xs text-white/55 sm:flex-row sm:items-center sm:justify-between">
            <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
            <p>Fresh &amp; natural, every day.</p>
        </div>
    </div>
</footer>
