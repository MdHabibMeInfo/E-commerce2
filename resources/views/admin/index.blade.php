@extends('admin.layout')

@section('content')
    <header class="rounded-[20px] border border-[#e5e2d9] bg-white p-5 shadow-sm">
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.2em] text-[#a48335]">Dashboard</p>
                <h1 class="mt-2 text-3xl font-extrabold text-[#145943]">{{ $currentUser?->is_system_owner ? 'System Owner Overview' : 'Management Dashboard' }}</h1>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <span class="rounded-full bg-[#edf2e4] px-3 py-1 text-xs font-bold uppercase tracking-[0.12em] text-[#145943]">Access verified</span>
                @can('products.view')
                    <a href="{{ route('admin.products.index') }}" class="rounded-xl bg-[#145943] px-4 py-2 text-sm font-bold text-white transition hover:bg-[#a48335]">Manage Products</a>
                @endcan
                @can('orders.view')
                    <a href="{{ route('admin.orders') }}" class="rounded-xl border border-[#dfe3cf] bg-white px-4 py-2 text-sm font-bold text-[#145943] transition hover:bg-[#f7f4ee]">Orders</a>
                @endcan
            </div>
        </div>
    </header>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        @if ($stats['products'] !== null)
            <div class="rounded-[4px] bg-[#145943] p-5 text-white">
                <p class="text-xs font-bold uppercase tracking-[0.16em] text-white/75">Active products</p>
                <p class="mt-2 text-3xl font-extrabold">{{ number_format($stats['products']) }}</p>
            </div>
        @endif
        @if ($stats['orders'] !== null)
            <div class="rounded-[4px] bg-[#5a6f70] p-5 text-white">
                <p class="text-xs font-bold uppercase tracking-[0.16em] text-white/75">All orders</p>
                <p class="mt-2 text-3xl font-extrabold">{{ number_format($stats['orders']) }}</p>
            </div>
            <div class="rounded-[4px] bg-[#a15b1d] p-5 text-white">
                <p class="text-xs font-bold uppercase tracking-[0.16em] text-white/75">Orders to process</p>
                <p class="mt-2 text-3xl font-extrabold">{{ number_format($stats['pending_orders']) }}</p>
            </div>
            <div class="rounded-[4px] bg-[#d4721d] p-5 text-white">
                <p class="text-xs font-bold uppercase tracking-[0.16em] text-white/75">Payments confirmed</p>
                <p class="mt-2 text-3xl font-extrabold">৳{{ number_format((float) $stats['revenue'], 2) }}</p>
            </div>
        @endif
        @if ($stats['customers'] !== null)
            <div class="rounded-[4px] bg-[#a48335] p-5 text-white">
                <p class="text-xs font-bold uppercase tracking-[0.16em] text-white/75">Active customers</p>
                <p class="mt-2 text-3xl font-extrabold">{{ number_format($stats['customers']) }}</p>
            </div>
        @endif
        @if ($stats['active_coupons'] !== null)
            <div class="rounded-[4px] bg-[#3f6860] p-5 text-white">
                <p class="text-xs font-bold uppercase tracking-[0.16em] text-white/75">Active promotions</p>
                <p class="mt-2 text-3xl font-extrabold">{{ number_format($stats['active_coupons']) }}</p>
            </div>
        @endif
    </div>

    @if ($canViewOrders)
        <section class="overflow-hidden border border-[#e5e2d9] bg-white">
            <div class="flex items-center justify-between border-b border-[#e5e2d9] px-5 py-4">
                <div><h2 class="text-lg font-bold text-[#173b32]">Recent orders</h2><p class="mt-1 text-xs text-[#738078]">Latest eight orders</p></div>
                <a href="{{ route('admin.orders') }}" class="text-sm font-bold text-[#145943]">View all</a>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead class="bg-[#f7f4ee] text-[#145943]"><tr><th class="px-4 py-3 font-bold">Order</th><th class="px-4 py-3 font-bold">Customer</th><th class="px-4 py-3 font-bold">Total</th><th class="px-4 py-3 font-bold">Payment</th><th class="px-4 py-3 font-bold">Status</th></tr></thead>
                    <tbody>
                        @forelse ($recentOrders as $order)
                            <tr class="border-t border-[#e5e2d9]"><td class="px-4 py-3"><a href="{{ route('admin.orders.show', $order) }}" class="font-bold text-[#145943]">{{ $order->order_number }}</a><p class="text-xs text-[#738078]">{{ $order->created_at->diffForHumans() }}</p></td><td class="px-4 py-3">{{ $order->customer_name }}</td><td class="px-4 py-3">৳{{ number_format((float) $order->total, 2) }}</td><td class="px-4 py-3">{{ ucfirst($order->payment_status) }}</td><td class="px-4 py-3">{{ ucfirst($order->status) }}</td></tr>
                        @empty
                            <tr><td colspan="5" class="px-4 py-8 text-center text-[#738078]">No orders yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    @endif

    @if ($canViewProducts && $lowStockProducts->isNotEmpty())
        <section class="border border-[#e5e2d9] bg-white">
            <div class="flex items-center justify-between border-b border-[#e5e2d9] px-5 py-4"><h2 class="text-lg font-bold text-[#173b32]">Availability alerts</h2><a href="{{ route('admin.products.index', ['status' => 'inactive']) }}" class="text-sm font-bold text-[#145943]">Manage catalog</a></div>
            <ul class="divide-y divide-[#ece8dc]">
                @foreach ($lowStockProducts as $product)
                    <li class="flex flex-wrap items-center justify-between gap-3 px-5 py-3 text-sm"><a href="{{ route('admin.products.show', $product) }}" class="font-semibold text-[#145943]">{{ $product->name }}</a><span class="font-bold {{ $product->is_out_of_stock ? 'text-red-700' : 'text-[#a15b1d]' }}">{{ $product->is_out_of_stock ? 'Out of stock' : $product->stock.' remaining' }}</span></li>
                @endforeach
            </ul>
        </section>
    @endif

    @if ($canViewReviews)
        <section class="overflow-hidden rounded-[4px] border border-[#e5e2d9] bg-white">
            <div class="flex flex-wrap items-center justify-between gap-4 border-b border-[#e5e2d9] px-5 py-4">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.16em] text-[#a48335]">Customer feedback</p>
                    <h2 class="mt-1 text-xl font-extrabold text-[#145943]">Reviews to review</h2>
                    <p class="mt-1 text-sm text-[#738078]">{{ $stats['pending_reviews'] }} waiting for a publishing decision</p>
                </div>
                <a href="{{ route('admin.reviews.index') }}" class="rounded-[4px] border border-[#145943] px-4 py-2 text-sm font-bold text-[#145943] transition hover:bg-[#145943] hover:text-white">Open review queue</a>
            </div>
            <div class="divide-y divide-[#ece8dc]">
                @forelse ($pendingReviews as $review)
                    <a href="{{ route('admin.reviews.show', $review) }}" class="flex flex-col gap-2 px-5 py-4 transition hover:bg-[#f8f9f2] sm:flex-row sm:items-center sm:justify-between">
                        <span class="min-w-0">
                            <span class="block truncate text-sm font-bold text-[#173b32]">{{ $review->product->name }} · {{ $review->name }}</span>
                            <span class="mt-1 block truncate text-sm text-[#59675f]">{{ $review->body }}</span>
                        </span>
                        <span class="shrink-0 text-xs font-semibold text-[#738078]">{{ $review->rating }}/5 · {{ $review->created_at->diffForHumans() }}</span>
                    </a>
                @empty
                    <p class="px-5 py-7 text-sm text-[#738078]">No reviews are waiting for moderation.</p>
                @endforelse
            </div>
        </section>
    @endif

    <div class="grid gap-6 xl:grid-cols-[1.2fr_0.8fr]">
        <div class="rounded-[20px] border border-[#e5e2d9] bg-white p-5 shadow-sm">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-xl font-extrabold text-[#145943]">Current User Permissions</h2>
                <span class="rounded-full bg-[#edf2e4] px-2.5 py-1 text-[10px] font-bold uppercase tracking-[0.12em] text-[#145943]">{{ $currentUser?->is_system_owner ? 'System Owner' : 'Authorized' }}</span>
            </div>

            @if ($currentUser?->is_system_owner)
                <p class="mb-4 rounded-xl border border-[#cfe7d6] bg-[#edf8f1] px-3 py-2 text-sm font-semibold text-[#145943]">System Owner has access to all platform capabilities, including owner-only actions.</p>
            @endif

            <div class="grid gap-3 sm:grid-cols-2">
                @foreach (array_slice($permissions, 0, 8) as $permission)
                    <div class="rounded-xl border border-[#e5e2d9] bg-[#fafaf5] px-3 py-2 text-sm font-medium text-[#173b32]">
                        {{ str_replace(['.', '_'], ' • ', $permission) }}
                    </div>
                @endforeach
            </div>

            @if (count($permissions) > 8)
                <details class="mt-4 border-t border-[#e5e2d9] pt-3">
                    <summary class="cursor-pointer text-sm font-bold text-[#145943]">View the remaining {{ count($permissions) - 8 }} permissions</summary>
                    <div class="mt-3 grid gap-3 sm:grid-cols-2">
                        @foreach (array_slice($permissions, 8) as $permission)
                            <div class="rounded-xl border border-[#e5e2d9] bg-[#fafaf5] px-3 py-2 text-sm font-medium text-[#173b32]">
                                {{ str_replace(['.', '_'], ' • ', $permission) }}
                            </div>
                        @endforeach
                    </div>
                </details>
            @endif
        </div>

        <div class="rounded-[20px] border border-[#e5e2d9] bg-white p-5 shadow-sm">
            <h2 class="text-xl font-extrabold text-[#145943]">Maintainable Actions</h2>
            <div class="mt-4 space-y-3 text-sm">
                @if ($canViewProducts)
                    <a href="{{ route('admin.products.index') }}" class="block rounded-xl border border-[#e5e2d9] bg-[#f9f8f3] px-3 py-2.5 text-[#173b32] transition hover:border-[#145943] hover:text-[#145943]">Manage Products</a>
                @endif
                @if ($canViewOrders)
                    <a href="{{ route('admin.orders') }}" class="block rounded-xl border border-[#e5e2d9] bg-[#f9f8f3] px-3 py-2.5 text-[#173b32] transition hover:border-[#145943] hover:text-[#145943]">Manage Orders</a>
                @endif
                @if ($canViewCustomers)
                    <a href="{{ route('admin.customers.index') }}" class="block rounded-xl border border-[#e5e2d9] bg-[#f9f8f3] px-3 py-2.5 text-[#173b32] transition hover:border-[#145943] hover:text-[#145943]">Manage Customers</a>
                @endif
                @if ($canViewCoupons)
                    <a href="{{ route('admin.coupons.index') }}" class="block rounded-xl border border-[#e5e2d9] bg-[#f9f8f3] px-3 py-2.5 text-[#173b32] transition hover:border-[#145943] hover:text-[#145943]">Manage Coupons &amp; Promotions</a>
                @endif
                @if ($currentUser?->is_system_owner)
                    <a href="{{ route('admin.roles.index') }}" class="block rounded-xl border border-[#e5e2d9] bg-[#f9f8f3] px-3 py-2.5 text-[#173b32] transition hover:border-[#145943] hover:text-[#145943]">
                        Manage Roles & Permissions
                    </a>
                    <a href="{{ route('admin.users.index') }}" class="block rounded-xl border border-[#e5e2d9] bg-[#f9f8f3] px-3 py-2.5 text-[#173b32] transition hover:border-[#145943] hover:text-[#145943]">
                        Manage Staff Accounts
                    </a>
                @endif
                @canany(['settings.view', 'settings.update'])
                    <a href="{{ route('admin.settings') }}" class="block rounded-xl border border-[#e5e2d9] bg-[#f9f8f3] px-3 py-2.5 text-[#173b32] transition hover:border-[#145943] hover:text-[#145943]">Site Settings</a>
                @endcanany
            </div>
        </div>
    </div>

    <div class="rounded-[20px] border border-[#e5e2d9] bg-white p-5 shadow-sm">
        <div class="mb-5 flex items-center justify-between gap-3">
            <h2 class="text-xl font-extrabold text-[#145943]">Access Summary</h2>
            <span class="text-sm font-bold text-[#a48335]">{{ $currentUser?->email ?? 'owner@site.com' }}</span>
        </div>

        <div class="overflow-hidden rounded-2xl border border-[#e5e2d9]">
            <table class="min-w-full text-left text-sm text-[#4d5c56]">
                <thead class="bg-[#f7f4ee] text-[#145943]">
                    <tr>
                        <th class="px-4 py-3 font-bold">Role</th>
                        <th class="px-4 py-3 font-bold">Access Level</th>
                        <th class="px-4 py-3 font-bold">Maintenance Scope</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($roleNames as $roleName)
                        <tr class="border-t border-[#e5e2d9]">
                            <td class="px-4 py-3 font-semibold text-[#173b32]">{{ $roleName }}</td>
                            <td class="px-4 py-3">{{ $currentUser?->is_system_owner ? 'System owner' : 'Assigned role' }}</td>
                            <td class="px-4 py-3">{{ $currentUser?->is_system_owner ? 'All platform capabilities' : count($permissions).' granted permissions' }}</td>
                        </tr>
                    @empty
                        <tr class="border-t border-[#e5e2d9]"><td colspan="3" class="px-4 py-3 text-[#738078]">No management role assigned.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
