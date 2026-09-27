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
                <aside class="border-r border-[#e5e2d9] bg-white p-5 shadow-sm xl:sticky xl:top-0 xl:min-h-[calc(100vh-80px)] xl:overflow-visible xl:rounded-r-[0px]">
                    <div class="mb-6 flex items-center gap-3 border-b border-[#ece8dc] pb-4">
                        <div class="grid h-11 w-11 place-items-center rounded-2xl bg-[#145943] text-lg font-bold text-white">
                            {{ strtoupper(substr(auth()->user()?->name ?? 'SO', 0, 1)) }}
                        </div>
                        <div>
                            <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-[#a48335]">System</p>
                            <h2 class="text-base font-extrabold text-[#173b32]">{{ auth()->user()?->name ?? 'System Owner' }}</h2>
                        </div>
                    </div>

                    <nav class="space-y-2 text-sm">
                        @php
                            $isProductsMenuOpen = request()->routeIs('admin.products.*');
                            $isCategoriesMenuOpen = request()->routeIs('admin.categories.*');
                        @endphp

                        <a
                            href="{{ route('admin') }}"
                            class="flex items-center justify-between rounded-xl px-3 py-2.5 {{ request()->routeIs('admin') ? 'bg-[#edf2e4] font-bold text-[#145943]' : 'text-[#4d5c56] hover:bg-[#f5f4ec]' }}"
                        >
                            <span>Overview</span>
                            <span class="text-xs">›</span>
                        </a>

                        <details class="group rounded-xl" {{ $isCategoriesMenuOpen ? 'open' : '' }}>
                            <summary class="flex cursor-pointer list-none items-center justify-between rounded-xl px-3 py-2.5 text-[#4d5c56] hover:bg-[#f5f4ec]" style="list-style: none;">
                                <span class="font-semibold text-[#4d5c56]">Categories</span>
                                <span class="text-xs text-[#4d5c56] transition-transform group-open:rotate-90">›</span>
                            </summary>

                            <div class="mt-2 space-y-1 border-l border-[#e5e2d9] pl-4">
                                <a href="{{ route('admin.categories.index') }}" class="block rounded-lg px-2 py-2 text-[#4d5c56] hover:bg-[#f5f4ec] {{ request()->routeIs('admin.categories.index') ? 'bg-[#edf2e4] font-bold text-[#145943]' : '' }}">
                                    All Categories
                                </a>
                                <a href="{{ route('admin.categories.create') }}" class="block rounded-lg px-2 py-2 text-[#4d5c56] hover:bg-[#f5f4ec] {{ request()->routeIs('admin.categories.create') ? 'bg-[#edf2e4] font-bold text-[#145943]' : '' }}">
                                    Create Category
                                </a>
                                <a href="{{ route('admin.categories.trashed') }}" class="block rounded-lg px-2 py-2 text-[#4d5c56] hover:bg-[#f5f4ec] {{ request()->routeIs('admin.categories.trashed') ? 'bg-[#edf2e4] font-bold text-[#145943]' : '' }}">
                                    Deleted Categories
                                </a>
                            </div>
                        </details>

                        <details class="group rounded-xl" {{ $isProductsMenuOpen ? 'open' : '' }}>
                            <summary class="flex cursor-pointer list-none items-center justify-between rounded-xl px-3 py-2.5 text-[#4d5c56] hover:bg-[#f5f4ec]" style="list-style: none;">
                                <span class="font-semibold text-[#4d5c56]">Products</span>
                                <span class="text-xs text-[#4d5c56] transition-transform group-open:rotate-90">›</span>
                            </summary>

                            <div class="mt-2 space-y-1 border-l border-[#e5e2d9] pl-4">
                                <a href="{{ route('admin.products.index') }}" class="block rounded-lg px-2 py-2 text-[#4d5c56] hover:bg-[#f5f4ec] {{ request()->routeIs('admin.products.index') ? 'bg-[#edf2e4] font-bold text-[#145943]' : '' }}">
                                    All Products
                                </a>
                                <a href="{{ route('admin.products.create') }}" class="block rounded-lg px-2 py-2 text-[#4d5c56] hover:bg-[#f5f4ec] {{ request()->routeIs('admin.products.create') ? 'bg-[#edf2e4] font-bold text-[#145943]' : '' }}">
                                    Create Product
                                </a>
                                <a href="{{ route('admin.products.trashed') }}" class="block rounded-lg px-2 py-2 text-[#4d5c56] hover:bg-[#f5f4ec] {{ request()->routeIs('admin.products.trashed') ? 'bg-[#edf2e4] font-bold text-[#145943]' : '' }}">
                                    Deleted Products
                                </a>
                            </div>
                        </details>

                        <a
                            href="{{ route('admin.orders') }}"
                            class="flex items-center justify-between rounded-xl px-3 py-2.5 {{ request()->routeIs('admin.orders') ? 'bg-[#edf2e4] font-bold text-[#145943]' : 'text-[#4d5c56] hover:bg-[#f5f4ec]' }}"
                        >
                            <span>Orders</span>
                            <span class="text-xs">›</span>
                        </a>
                        <a href="#" class="flex items-center justify-between rounded-xl px-3 py-2.5 text-[#4d5c56] hover:bg-[#f5f4ec]">
                            <span>Customers</span>
                            <span class="text-xs">›</span>
                        </a>
                        <a href="#" class="flex items-center justify-between rounded-xl px-3 py-2.5 text-[#4d5c56] hover:bg-[#f5f4ec]">
                            <span>Users & Roles</span>
                            <span class="text-xs">›</span>
                        </a>
                        <a href="#" class="flex items-center justify-between rounded-xl px-3 py-2.5 text-[#4d5c56] hover:bg-[#f5f4ec]">
                            <span>Settings</span>
                            <span class="text-xs">›</span>
                        </a>
                    </nav>

                    <div class="mt-8 rounded-[18px] bg-[#f7f4ee] p-4">
                        <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-[#a48335]">Access</p>
                        <h3 class="mt-2 text-lg font-extrabold text-[#173b32]">{{ auth()->user()?->getRoleNames()->first() ?? 'System Owner' }}</h3>
                        <p class="mt-2 text-sm text-[#5a685f]">{{ count(auth()->user()?->getPermissionNames() ?? []) }} permissions active</p>
                    </div>
                </aside>

                <main class="space-y-6 p-5 sm:p-6 xl:p-8 xl:pt-7">
                    @yield('content')
                </main>
            </div>
        </div>
    </section>
</x-layouts.app>
