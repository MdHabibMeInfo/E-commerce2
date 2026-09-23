<header class="border-b border-gray-200 bg-white">
    <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-4 sm:px-6 lg:px-8">

        <a
            href="{{ route('home') }}"
            class="text-xl font-bold tracking-tight text-gray-900"
        >
            {{ config('app.name') }}
        </a>

        <nav class="hidden items-center gap-6 md:flex">

            <a
                href="{{ route('home') }}"
                class="text-sm font-medium text-gray-700 transition hover:text-gray-900"
            >
                Home
            </a>

            <a
                href="#"
                class="text-sm font-medium text-gray-700 transition hover:text-gray-900"
            >
                Shop
            </a>

            <a
                href="#"
                class="text-sm font-medium text-gray-700 transition hover:text-gray-900"
            >
                Categories
            </a>

        </nav>

        <div class="flex items-center gap-4">

            <a
                href="#"
                class="text-sm font-medium text-gray-700 transition hover:text-gray-900"
            >
                Cart
            </a>

        </div>

    </div>
</header>