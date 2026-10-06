@extends('admin.layout')

@section('content')
    <header class="rounded-[20px] border border-[#e5e2d9] bg-white p-5 shadow-sm">
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.2em] text-[#a48335]">Dashboard</p>
                <h1 class="mt-2 text-3xl font-extrabold text-[#145943]">System Owner Overview</h1>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <span class="rounded-full bg-[#edf2e4] px-3 py-1 text-xs font-bold uppercase tracking-[0.12em] text-[#145943]">Online</span>
                <a href="{{ route('admin.products.index') }}" class="rounded-xl bg-[#145943] px-4 py-2 text-sm font-bold text-white transition hover:bg-[#a48335]">Manage Products</a>
                <a href="{{ route('admin.orders') }}" class="rounded-xl border border-[#dfe3cf] bg-white px-4 py-2 text-sm font-bold text-[#145943] transition hover:bg-[#f7f4ee]">Orders</a>
            </div>
        </div>
    </header>

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-[20px] bg-[#145943] p-5 text-white shadow-sm">
            <p class="text-xs font-bold uppercase tracking-[0.2em] text-white/75">Products</p>
            <h3 class="mt-3 text-3xl font-extrabold">{{ $stats['products'] }}</h3>
            <p class="mt-3 text-sm text-white/80">Active catalog items</p>
        </div>

        <div class="rounded-[20px] bg-[#5a6f70] p-5 text-white shadow-sm">
            <p class="text-xs font-bold uppercase tracking-[0.2em] text-white/75">Orders</p>
            <h3 class="mt-3 text-3xl font-extrabold">{{ $stats['orders'] }}</h3>
            <p class="mt-3 text-sm text-white/80">Total orders in system</p>
        </div>

        <div class="rounded-[20px] bg-[#a48335] p-5 text-white shadow-sm">
            <p class="text-xs font-bold uppercase tracking-[0.2em] text-white/75">Users</p>
            <h3 class="mt-3 text-3xl font-extrabold">{{ $stats['users'] }}</h3>
            <p class="mt-3 text-sm text-white/80">Logged-in and managed users</p>
        </div>

        <div class="rounded-[20px] bg-[#d4721d] p-5 text-white shadow-sm">
            <p class="text-xs font-bold uppercase tracking-[0.2em] text-white/75">Revenue</p>
            <h3 class="mt-3 text-3xl font-extrabold">৳{{ number_format((float) $stats['revenue'], 2) }}</h3>
            <p class="mt-3 text-sm text-white/80">Gross order value</p>
        </div>
    </div>

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
                <span class="rounded-full bg-[#edf2e4] px-2.5 py-1 text-[10px] font-bold uppercase tracking-[0.12em] text-[#145943]">Authorized</span>
            </div>

            <div class="grid gap-3 sm:grid-cols-2">
                @foreach (array_slice($permissions, 0, 8) as $permission)
                    <div class="rounded-xl border border-[#e5e2d9] bg-[#fafaf5] px-3 py-2 text-sm font-medium text-[#173b32]">
                        {{ str_replace(['.', '_'], ' • ', $permission) }}
                    </div>
                @endforeach
            </div>
        </div>

        <div class="rounded-[20px] border border-[#e5e2d9] bg-white p-5 shadow-sm">
            <h2 class="text-xl font-extrabold text-[#145943]">Maintainable Actions</h2>
            <div class="mt-4 space-y-3 text-sm">
                <a href="{{ route('admin.products.index') }}" class="block rounded-xl border border-[#e5e2d9] bg-[#f9f8f3] px-3 py-2.5 text-[#173b32] transition hover:border-[#145943] hover:text-[#145943]">
                    Manage Products
                </a>
                <div class="rounded-xl border border-[#e5e2d9] bg-[#f9f8f3] px-3 py-2.5 text-[#173b32]">
                    Manage Orders
                </div>
                @canany(['roles.view', 'roles.create', 'roles.update'])
                    <a href="{{ route('admin.roles.index') }}" class="block rounded-xl border border-[#e5e2d9] bg-[#f9f8f3] px-3 py-2.5 text-[#173b32] transition hover:border-[#145943] hover:text-[#145943]">
                        Manage Roles & Permissions
                    </a>
                @endcanany
                @canany(['users.view', 'users.create', 'users.update'])
                    <a href="{{ route('admin.users.index') }}" class="block rounded-xl border border-[#e5e2d9] bg-[#f9f8f3] px-3 py-2.5 text-[#173b32] transition hover:border-[#145943] hover:text-[#145943]">
                        Manage Staff Accounts
                    </a>
                @endcanany
                <div class="rounded-xl border border-[#e5e2d9] bg-[#f9f8f3] px-3 py-2.5 text-[#173b32]">
                    Site Settings & Security
                </div>
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
                    <tr class="border-t border-[#e5e2d9]">
                        <td class="px-4 py-3 font-semibold text-[#173b32]">{{ $roleNames[0] ?? 'System Owner' }}</td>
                        <td class="px-4 py-3">Full control</td>
                        <td class="px-4 py-3">Platform, users, orders, settings, security</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
@endsection
