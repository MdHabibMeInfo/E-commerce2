<x-layouts.app>
    <x-slot:title>
        @yield('title', 'Admin Dashboard')
    </x-slot:title>

    <x-slot:metaDescription>
        @yield('metaDescription', 'Admin dashboard for managing store operations.')
    </x-slot:metaDescription>

    <x-slot:canonicalUrl>
        @yield('canonicalUrl', route('admin'))
    </x-slot:canonicalUrl>

    <section class="bg-[#fbfaf4]">
        <div class="mx-auto max-w-[100vw] px-0">
            <div class="grid min-h-[calc(100vh-80px)] grid-cols-1 xl:grid-cols-[260px_minmax(0,1fr)] xl:gap-0">
                @include('admin.partials.sidebar')

                <main class="space-y-6 p-5 sm:p-6 xl:p-8 xl:pt-7">
                    @yield('content')
                </main>
            </div>
        </div>
    </section>
</x-layouts.app>
