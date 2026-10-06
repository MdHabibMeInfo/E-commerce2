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

    @php
        $isProfileMenuOpen = request()->routeIs('admin.profile', 'account.orders*', 'account.security*');
    @endphp
    <details class="group mb-4 rounded-xl" {{ $isProfileMenuOpen ? 'open' : '' }}>
        <summary class="flex cursor-pointer list-none items-center justify-between rounded-xl px-3 py-2.5 {{ $isProfileMenuOpen ? 'bg-[#edf2e4] font-bold text-[#145943]' : 'text-[#4d5c56] hover:bg-[#f5f4ec]' }}" style="list-style: none;">
            <span>Profile</span>
            <span class="text-xs transition-transform group-open:rotate-90" aria-hidden="true">›</span>
        </summary>
        <div class="mt-2 space-y-1 border-l border-[#e5e2d9] pl-4 text-sm">
            <a href="{{ route('admin.profile') }}" class="block rounded-lg px-2 py-2 {{ request()->routeIs('admin.profile') ? 'bg-[#edf2e4] font-bold text-[#145943]' : 'text-[#4d5c56] hover:bg-[#f5f4ec]' }}">Profile overview</a>
            <a href="{{ route('account.orders') }}" class="block rounded-lg px-2 py-2 {{ request()->routeIs('account.orders') ? 'bg-[#edf2e4] font-bold text-[#145943]' : 'text-[#4d5c56] hover:bg-[#f5f4ec]' }}">My orders</a>
            <a href="{{ route('account.orders.history') }}" class="block rounded-lg px-2 py-2 {{ request()->routeIs('account.orders.history') ? 'bg-[#edf2e4] font-bold text-[#145943]' : 'text-[#4d5c56] hover:bg-[#f5f4ec]' }}">Order history</a>
            <a href="{{ route('account.security') }}" class="block rounded-lg px-2 py-2 {{ request()->routeIs('account.security*') ? 'bg-[#edf2e4] font-bold text-[#145943]' : 'text-[#4d5c56] hover:bg-[#f5f4ec]' }}">Security</a>
        </div>
    </details>

    <nav class="space-y-2 text-sm">
        @php
            $isProductsMenuOpen = request()->routeIs('admin.products.*');
            $isCategoriesMenuOpen = request()->routeIs('admin.categories.*');
            $isAttributesMenuOpen = request()->routeIs('admin.attributes.*');
            $isRolesMenuOpen = request()->routeIs('admin.roles.*');
            $isUsersMenuOpen = request()->routeIs('admin.users.*');
        @endphp

        <a href="{{ route('admin') }}" class="flex items-center justify-between rounded-xl px-3 py-2.5 {{ request()->routeIs('admin') ? 'bg-[#edf2e4] font-bold text-[#145943]' : 'text-[#4d5c56] hover:bg-[#f5f4ec]' }}">
            <span>Overview</span>
            <span class="text-xs">›</span>
        </a>

        @can('orders.view')
            <a href="{{ route('admin.orders') }}" class="flex items-center justify-between rounded-xl px-3 py-2.5 {{ request()->routeIs('admin.orders*') ? 'bg-[#edf2e4] font-bold text-[#145943]' : 'text-[#4d5c56] hover:bg-[#f5f4ec]' }}">
                <span>Orders</span>
                <span class="text-xs">›</span>
            </a>
        @endcan

        @can('reviews.view')
            <a href="{{ route('admin.reviews.index') }}" class="flex items-center justify-between rounded-xl px-3 py-2.5 {{ request()->routeIs('admin.reviews.*') ? 'bg-[#edf2e4] font-bold text-[#145943]' : 'text-[#4d5c56] hover:bg-[#f5f4ec]' }}">
                <span>Customer Reviews</span>
                <span class="text-xs">›</span>
            </a>
        @endcan

        @canany(['categories.view', 'categories.create', 'categories.update', 'categories.delete'])
            <details class="group rounded-xl" {{ $isCategoriesMenuOpen ? 'open' : '' }}>
                <summary class="flex cursor-pointer list-none items-center justify-between rounded-xl px-3 py-2.5 text-[#4d5c56] hover:bg-[#f5f4ec]" style="list-style: none;">
                    <span class="font-semibold text-[#4d5c56]">Categories</span>
                    <span class="text-xs text-[#4d5c56] transition-transform group-open:rotate-90">›</span>
                </summary>
                <div class="mt-2 space-y-1 border-l border-[#e5e2d9] pl-4">
                    @can('categories.view')
                        <a href="{{ route('admin.categories.index') }}" class="block rounded-lg px-2 py-2 text-[#4d5c56] hover:bg-[#f5f4ec] {{ request()->routeIs('admin.categories.index') ? 'bg-[#edf2e4] font-bold text-[#145943]' : '' }}">All Categories</a>
                    @endcan
                    @can('categories.create')
                        <a href="{{ route('admin.categories.create') }}" class="block rounded-lg px-2 py-2 text-[#4d5c56] hover:bg-[#f5f4ec] {{ request()->routeIs('admin.categories.create') ? 'bg-[#edf2e4] font-bold text-[#145943]' : '' }}">Create Category</a>
                    @endcan
                    @can('categories.view')
                        <a href="{{ route('admin.categories.trashed') }}" class="block rounded-lg px-2 py-2 text-[#4d5c56] hover:bg-[#f5f4ec] {{ request()->routeIs('admin.categories.trashed') ? 'bg-[#edf2e4] font-bold text-[#145943]' : '' }}">Deleted Categories</a>
                    @endcan
                </div>
            </details>
        @endcanany

        @canany(['attributes.view', 'attributes.create', 'attributes.update', 'attributes.delete'])
            <details class="group rounded-xl" {{ $isAttributesMenuOpen ? 'open' : '' }}>
                <summary class="flex cursor-pointer list-none items-center justify-between rounded-xl px-3 py-2.5 text-[#4d5c56] hover:bg-[#f5f4ec]" style="list-style: none;">
                    <span class="font-semibold text-[#4d5c56]">Attributes</span>
                    <span class="text-xs text-[#4d5c56] transition-transform group-open:rotate-90">›</span>
                </summary>
                <div class="mt-2 space-y-1 border-l border-[#e5e2d9] pl-4">
                    @can('attributes.view')
                        <a href="{{ route('admin.attributes.index') }}" class="block rounded-lg px-2 py-2 text-[#4d5c56] hover:bg-[#f5f4ec] {{ request()->routeIs('admin.attributes.index') ? 'bg-[#edf2e4] font-bold text-[#145943]' : '' }}">All Attributes</a>
                    @endcan
                    @can('attributes.create')
                        <a href="{{ route('admin.attributes.create') }}" class="block rounded-lg px-2 py-2 text-[#4d5c56] hover:bg-[#f5f4ec] {{ request()->routeIs('admin.attributes.create') ? 'bg-[#edf2e4] font-bold text-[#145943]' : '' }}">Create Attribute</a>
                    @endcan
                    @can('attributes.view')
                        <a href="{{ route('admin.attributes.trashed') }}" class="block rounded-lg px-2 py-2 text-[#4d5c56] hover:bg-[#f5f4ec] {{ request()->routeIs('admin.attributes.trashed') ? 'bg-[#edf2e4] font-bold text-[#145943]' : '' }}">Deleted Attributes</a>
                    @endcan
                </div>
            </details>
        @endcanany

        @canany(['products.view', 'products.create', 'products.update', 'products.delete'])
            <details class="group rounded-xl" {{ $isProductsMenuOpen ? 'open' : '' }}>
                <summary class="flex cursor-pointer list-none items-center justify-between rounded-xl px-3 py-2.5 text-[#4d5c56] hover:bg-[#f5f4ec]" style="list-style: none;">
                    <span class="font-semibold text-[#4d5c56]">Products</span>
                    <span class="text-xs text-[#4d5c56] transition-transform group-open:rotate-90">›</span>
                </summary>
                <div class="mt-2 space-y-1 border-l border-[#e5e2d9] pl-4">
                    @can('products.view')
                        <a href="{{ route('admin.products.index') }}" class="block rounded-lg px-2 py-2 text-[#4d5c56] hover:bg-[#f5f4ec] {{ request()->routeIs('admin.products.index') ? 'bg-[#edf2e4] font-bold text-[#145943]' : '' }}">All Products</a>
                    @endcan
                    @can('products.create')
                        <a href="{{ route('admin.products.create') }}" class="block rounded-lg px-2 py-2 text-[#4d5c56] hover:bg-[#f5f4ec] {{ request()->routeIs('admin.products.create') ? 'bg-[#edf2e4] font-bold text-[#145943]' : '' }}">Create Product</a>
                    @endcan
                    @can('products.view')
                        <a href="{{ route('admin.products.trashed') }}" class="block rounded-lg px-2 py-2 text-[#4d5c56] hover:bg-[#f5f4ec] {{ request()->routeIs('admin.products.trashed') ? 'bg-[#edf2e4] font-bold text-[#145943]' : '' }}">Deleted Products</a>
                    @endcan
                </div>
            </details>
        @endcanany

        <a href="#" class="flex items-center justify-between rounded-xl px-3 py-2.5 text-[#4d5c56] hover:bg-[#f5f4ec]">
            <span>Customers</span>
            <span class="text-xs">›</span>
        </a>
        @canany(['roles.view', 'roles.create', 'roles.update'])
            <a href="{{ route('admin.roles.index') }}" class="flex items-center justify-between rounded-xl px-3 py-2.5 {{ $isRolesMenuOpen ? 'bg-[#edf2e4] font-bold text-[#145943]' : 'text-[#4d5c56] hover:bg-[#f5f4ec]' }}">
                <span>Roles & Permissions</span>
                <span class="text-xs">›</span>
            </a>
        @endcanany
        @canany(['users.view', 'users.create', 'users.update'])
            <a href="{{ route('admin.users.index') }}" class="flex items-center justify-between rounded-xl px-3 py-2.5 {{ $isUsersMenuOpen ? 'bg-[#edf2e4] font-bold text-[#145943]' : 'text-[#4d5c56] hover:bg-[#f5f4ec]' }}">
                <span>Staff Accounts</span>
                <span class="text-xs">›</span>
            </a>
        @endcanany
        @can('audit-logs.view')
            <a href="{{ route('admin.security.events') }}" class="flex items-center justify-between rounded-xl px-3 py-2.5 {{ request()->routeIs('admin.security.events') ? 'bg-[#edf2e4] font-bold text-[#145943]' : 'text-[#4d5c56] hover:bg-[#f5f4ec]' }}">
                <span>Security Audit Log</span>
                <span class="text-xs">›</span>
            </a>
        @endcan
        @canany(['settings.view', 'settings.update'])
            <a href="{{ route('admin.settings') }}" class="flex items-center justify-between rounded-xl px-3 py-2.5 {{ request()->routeIs('admin.settings*') ? 'bg-[#edf2e4] font-bold text-[#145943]' : 'text-[#4d5c56] hover:bg-[#f5f4ec]' }}">
                <span>Settings</span>
                <span class="text-xs">›</span>
            </a>
        @endcanany
    </nav>

    <div class="mt-8 rounded-[18px] bg-[#f7f4ee] p-4">
        <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-[#a48335]">Access</p>
        <h3 class="mt-2 text-lg font-extrabold text-[#173b32]">{{ auth()->user()?->getRoleNames()->first() ?? 'System Owner' }}</h3>
        <p class="mt-2 text-sm text-[#5a685f]">{{ count(auth()->user()?->getPermissionNames() ?? []) }} permissions active</p>
    </div>
</aside>