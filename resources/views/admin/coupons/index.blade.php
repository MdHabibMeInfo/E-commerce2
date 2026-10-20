@extends('admin.layout')

@section('title', 'Promotions')

@section('content')
    <header class="flex flex-wrap items-end justify-between gap-4 border-b border-[#e5e2d9] pb-4">
        <div>
            <p class="text-xs font-bold uppercase tracking-[0.16em] text-[#a48335]">Sales</p>
            <h1 class="mt-1 text-2xl font-extrabold text-[#145943]">Promotions</h1>
        </div>
        <span class="text-sm text-[#66736d]">{{ $coupons->total() }} promotions</span>
    </header>

    @if (session('success'))
        <p class="border border-green-200 bg-green-50 px-4 py-3 text-sm font-semibold text-green-800">{{ session('success') }}</p>
    @endif
    @if ($errors->any())
        <ul class="border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    @endif

    @can('coupons.create')
        <section class="border border-[#e5e2d9] bg-white p-5">
            <h2 class="text-lg font-bold text-[#173b32]">Create promotion</h2>
            <form method="POST" action="{{ route('admin.coupons.store') }}" class="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                @csrf
                <label class="text-sm font-semibold text-[#4d5c56]">Coupon code
                    <input name="code" value="{{ old('code') }}" maxlength="40" pattern="[A-Za-z0-9][A-Za-z0-9_-]*" class="mt-1 h-10 w-full border border-[#dfe3cf] px-3 uppercase">
                </label>
                <label class="flex min-h-10 items-center gap-2 text-sm font-semibold text-[#4d5c56]">
                    <input type="hidden" name="is_automatic" value="0">
                    <input type="checkbox" name="is_automatic" value="1" @checked(old('is_automatic')) class="h-4 w-4 accent-[#145943]">
                    Apply automatically
                </label>
                <label class="text-sm font-semibold text-[#4d5c56]">Offer type
                    <select name="discount_type" required class="mt-1 h-10 w-full border border-[#dfe3cf] bg-white px-3">
                        <option value="fixed" @selected(old('discount_type') === 'fixed')>Fixed discount</option>
                        <option value="percentage" @selected(old('discount_type') === 'percentage')>Percentage discount</option>
                        <option value="free_shipping" @selected(old('discount_type') === 'free_shipping')>Free delivery</option>
                    </select>
                </label>
                <label class="text-sm font-semibold text-[#4d5c56]">Discount value (0 for free delivery)
                    <input type="number" name="discount_value" value="{{ old('discount_value', '0') }}" required min="0" step="0.01" class="mt-1 h-10 w-full border border-[#dfe3cf] px-3">
                </label>
                <div class="sm:col-span-2 xl:col-span-4">
                    <label for="coupon-target-search-new" class="block text-sm font-semibold text-[#4d5c56]">Applies to</label>
                    <input type="hidden" name="target" value="{{ old('target', 'all') }}" data-hero-target-value>
                    <input id="coupon-target-search-new" type="search" value="{{ $oldTargetLabel ?? '' }}" placeholder="Search category, product, or variant..." autocomplete="off" role="combobox" aria-autocomplete="list" aria-expanded="false" aria-controls="coupon-target-results-new" data-hero-target-search data-search-url="{{ route('admin.coupons.targets') }}" data-empty-value="all" data-empty-message="All products" class="mt-1 h-10 w-full border border-[#dfe3cf] bg-white px-3 text-sm">
                    <div id="coupon-target-results-new" data-hero-target-results role="listbox" class="mt-1 hidden max-h-64 overflow-y-auto border border-[#dfe3cf] bg-white shadow-lg"></div>
                    <div class="mt-2 flex items-center justify-between gap-3">
                        <p data-hero-target-selected class="min-w-0 text-xs text-[#66736d]" aria-live="polite">
                            @if (old('target', 'all') === 'all')
                                All products
                            @else
                                Selected target: {{ $oldTargetLabel ?? old('target') }}
                            @endif
                        </p>
                        <button type="button" data-clear-hero-target class="shrink-0 border border-[#dfe3cf] px-3 py-1.5 text-xs font-semibold text-[#145943]">All products</button>
                    </div>
                </div>
                <label class="text-sm font-semibold text-[#4d5c56]">Minimum subtotal
                    <input type="number" name="minimum_subtotal" value="{{ old('minimum_subtotal', '0') }}" required min="0" step="0.01" class="mt-1 h-10 w-full border border-[#dfe3cf] px-3">
                </label>
                <label class="text-sm font-semibold text-[#4d5c56]">Maximum discount (optional)
                    <input type="number" name="maximum_discount" value="{{ old('maximum_discount') }}" min="0.01" step="0.01" class="mt-1 h-10 w-full border border-[#dfe3cf] px-3">
                </label>
                <label class="text-sm font-semibold text-[#4d5c56]">Total uses (optional)
                    <input type="number" name="usage_limit" value="{{ old('usage_limit') }}" min="1" step="1" class="mt-1 h-10 w-full border border-[#dfe3cf] px-3">
                </label>
                <label class="text-sm font-semibold text-[#4d5c56]">Uses per customer (optional)
                    <input type="number" name="per_customer_limit" value="{{ old('per_customer_limit') }}" min="1" step="1" class="mt-1 h-10 w-full border border-[#dfe3cf] px-3">
                </label>
                <label class="text-sm font-semibold text-[#4d5c56]">Starts at (optional)
                    <input type="datetime-local" name="starts_at" value="{{ old('starts_at') }}" class="mt-1 h-10 w-full border border-[#dfe3cf] px-3">
                </label>
                <label class="text-sm font-semibold text-[#4d5c56]">Expires at (optional)
                    <input type="datetime-local" name="expires_at" value="{{ old('expires_at') }}" class="mt-1 h-10 w-full border border-[#dfe3cf] px-3">
                </label>
                <label class="flex min-h-10 items-center gap-2 text-sm font-semibold text-[#4d5c56]">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1" checked class="h-4 w-4 accent-[#145943]">
                    Active immediately
                </label>
                <div class="flex items-end">
                    <button type="submit" class="h-10 bg-[#145943] px-5 text-sm font-bold text-white hover:bg-[#a48335]">Create promotion</button>
                </div>
            </form>
        </section>
    @endcan

    <form method="GET" action="{{ route('admin.coupons.index') }}#promotion-results" class="flex flex-wrap gap-3">
        <label class="sr-only" for="coupon-search">Search coupon code</label>
        <input id="coupon-search" name="search" value="{{ $search }}" maxlength="40" placeholder="Search coupon code" class="h-10 min-w-0 flex-1 border border-[#dfe3cf] bg-white px-3 text-sm">
        <label class="sr-only" for="promotion-type">Filter promotion type</label>
        <select id="promotion-type" name="promotion_type" class="h-10 border border-[#dfe3cf] bg-white px-3 text-sm">
            <option value="">All promotion types</option>
            <option value="coupon" @selected(request('promotion_type') === 'coupon')>Coupon code</option>
            <option value="automatic" @selected(request('promotion_type') === 'automatic')>Automatic offer</option>
        </select>
        <label class="sr-only" for="promotion-discount-type">Filter discount type</label>
        <select id="promotion-discount-type" name="discount_type" class="h-10 border border-[#dfe3cf] bg-white px-3 text-sm">
            <option value="">All discount types</option>
            <option value="fixed" @selected(request('discount_type') === 'fixed')>Fixed amount</option>
            <option value="percentage" @selected(request('discount_type') === 'percentage')>Percentage</option>
            <option value="free_shipping" @selected(request('discount_type') === 'free_shipping')>Free delivery</option>
        </select>
        <label class="sr-only" for="promotion-target-type">Filter applies to</label>
        <select id="promotion-target-type" name="target_type" class="h-10 border border-[#dfe3cf] bg-white px-3 text-sm">
            <option value="">All targets</option>
            <option value="all" @selected(request('target_type') === 'all')>All products</option>
            <option value="category" @selected(request('target_type') === 'category')>Category</option>
            <option value="product" @selected(request('target_type') === 'product')>Product</option>
            <option value="variant" @selected(request('target_type') === 'variant')>Variant</option>
        </select>
        <label class="sr-only" for="coupon-status">Filter promotion status</label>
        <select id="coupon-status" name="status" class="h-10 border border-[#dfe3cf] bg-white px-3 text-sm">
            <option value="">All statuses</option>
            <option value="active" @selected(request('status') === 'active')>Active</option>
            <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
        </select>
        <button class="h-10 bg-[#145943] px-4 text-sm font-bold text-white">Search</button>
        <a href="{{ route('admin.coupons.index') }}#promotion-results" class="inline-flex h-10 items-center border border-[#dfe3cf] px-4 text-sm font-bold text-[#145943]">Clear</a>
    </form>

    <section id="promotion-results" class="overflow-x-auto border border-[#e5e2d9] bg-white">
        <table class="min-w-full text-left text-sm">
            <thead class="bg-[#f7f4ee] text-[#145943]"><tr>
                <th class="px-4 py-3 font-bold">Promotion</th>
                <th class="px-4 py-3 font-bold">Discount</th>
                <th class="px-4 py-3 font-bold">Uses</th>
                <th class="px-4 py-3 font-bold">Schedule</th>
                <th class="px-4 py-3 font-bold">Status</th>
                <th class="px-4 py-3 font-bold">Actions</th>
            </tr></thead>
            <tbody>
                @forelse ($coupons as $coupon)
                    @php
                        $targetLabel = $couponTargetLabels[$coupon->id] ?? null;
                    @endphp
                    <tr class="border-t border-[#e5e2d9] align-top">
                        <td class="px-4 py-4 font-mono font-bold text-[#173b32]">{{ $coupon->is_automatic ? 'Automatic offer' : $coupon->code }}<p class="mt-1 font-sans text-xs font-normal text-[#738078]">@if ($coupon->target_type === 'all')All products@else{{ ucfirst($coupon->target_type) }}: {{ $targetLabel ?? '#'.$coupon->target_id }}@endif · Min ৳{{ number_format((float) $coupon->minimum_subtotal, 2) }}</p></td>
                        <td class="px-4 py-4">{{ $coupon->discount_type === 'free_shipping' ? 'Free delivery' : ($coupon->discount_type === 'percentage' ? number_format((float) $coupon->discount_value, 2).'%' : '৳'.number_format((float) $coupon->discount_value, 2)) }} @if ($coupon->maximum_discount) <span class="block text-xs text-[#738078]">Cap ৳{{ number_format((float) $coupon->maximum_discount, 2) }}</span> @endif</td>
                        <td class="px-4 py-4">{{ $coupon->redemptions_count }}{{ $coupon->usage_limit ? ' / '.$coupon->usage_limit : '' }}<span class="block text-xs text-[#738078]">Per customer: {{ $coupon->per_customer_limit ?? 'unlimited' }}</span></td>
                        <td class="px-4 py-4 text-xs text-[#66736d]">{{ $coupon->starts_at?->format('d M Y H:i') ?? 'Any time' }}<span class="block">to {{ $coupon->expires_at?->format('d M Y H:i') ?? 'No expiry' }}</span></td>
                        <td class="px-4 py-4"><span class="font-bold {{ $coupon->is_active ? 'text-green-700' : 'text-[#738078]' }}">{{ $coupon->is_active ? 'Active' : 'Inactive' }}</span></td>
                        <td class="space-y-2 px-4 py-4">
                            @can('coupons.update')
                                <form method="POST" action="{{ route('admin.coupons.status.update', $coupon) }}">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="is_active" value="{{ $coupon->is_active ? 0 : 1 }}">
                                    <button class="text-xs font-bold text-[#145943]">{{ $coupon->is_automatic ? ($coupon->is_active ? 'Turn discount off' : 'Turn discount on') : ($coupon->is_active ? 'Deactivate coupon' : 'Activate coupon') }}</button>
                                </form>
                                @if ($coupon->redemptions_count === 0)
                                    <details>
                                        <summary class="cursor-pointer text-xs font-bold text-[#145943]">Edit rules</summary>
                                        <form method="POST" action="{{ route('admin.coupons.update', $coupon) }}" class="mt-2 grid min-w-64 gap-2">
                                            @csrf
                                            @method('PUT')
                                            <input name="code" value="{{ $coupon->code }}" maxlength="40" placeholder="Coupon code" class="h-9 border border-[#dfe3cf] px-2 font-mono uppercase">
                                            <label class="flex items-center gap-2 text-xs font-semibold"><input type="hidden" name="is_automatic" value="0"><input type="checkbox" name="is_automatic" value="1" @checked($coupon->is_automatic)> Apply automatically</label>
                                            <select name="discount_type" class="h-9 border border-[#dfe3cf] bg-white px-2"><option value="fixed" @selected($coupon->discount_type === 'fixed')>Fixed discount</option><option value="percentage" @selected($coupon->discount_type === 'percentage')>Percentage discount</option><option value="free_shipping" @selected($coupon->discount_type === 'free_shipping')>Free delivery</option></select>
                                            <input type="number" name="discount_value" value="{{ $coupon->discount_value }}" required min="0" step="0.01" class="h-9 border border-[#dfe3cf] px-2">
                                            <input type="hidden" name="target" value="{{ $coupon->target_type === 'all' ? 'all' : $coupon->target_type.':'.$coupon->target_id }}" data-hero-target-value>
                                            <input type="search" value="{{ $targetLabel ?? '' }}" placeholder="Search category, product, or variant..." autocomplete="off" role="combobox" aria-autocomplete="list" aria-expanded="false" data-hero-target-search data-search-url="{{ route('admin.coupons.targets') }}" data-empty-value="all" data-empty-message="All products" class="h-9 border border-[#dfe3cf] px-2">
                                            <div data-hero-target-results role="listbox" class="hidden max-h-48 overflow-y-auto border border-[#dfe3cf] bg-white"></div>
                                            <p data-hero-target-selected class="text-xs text-[#66736d]">Current target: {{ $coupon->target_type === 'all' ? 'All products' : ucfirst($coupon->target_type).': '.($targetLabel ?? '#'.$coupon->target_id) }}</p>
                                            <button type="button" data-clear-hero-target class="justify-self-start text-xs font-semibold text-[#145943]">All products</button>
                                            <input type="number" name="minimum_subtotal" value="{{ $coupon->minimum_subtotal }}" required min="0" step="0.01" class="h-9 border border-[#dfe3cf] px-2">
                                            <input type="number" name="maximum_discount" value="{{ $coupon->maximum_discount }}" min="0.01" step="0.01" placeholder="Maximum discount" class="h-9 border border-[#dfe3cf] px-2">
                                            <input type="number" name="usage_limit" value="{{ $coupon->usage_limit }}" min="1" placeholder="Total uses" class="h-9 border border-[#dfe3cf] px-2">
                                            <input type="number" name="per_customer_limit" value="{{ $coupon->per_customer_limit }}" min="1" placeholder="Uses per customer" class="h-9 border border-[#dfe3cf] px-2">
                                            <input type="datetime-local" name="starts_at" value="{{ $coupon->starts_at?->format('Y-m-d\TH:i') }}" class="h-9 border border-[#dfe3cf] px-2">
                                            <input type="datetime-local" name="expires_at" value="{{ $coupon->expires_at?->format('Y-m-d\TH:i') }}" class="h-9 border border-[#dfe3cf] px-2">
                                            <input type="hidden" name="is_active" value="{{ $coupon->is_active ? 1 : 0 }}">
                                            <button class="h-9 bg-[#145943] px-3 text-xs font-bold text-white">Save rules</button>
                                        </form>
                                    </details>
                                @else
                                    <span class="block text-xs text-[#738078]">Rules locked after first use</span>
                                @endif
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-10 text-center text-[#738078]">No promotions found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </section>
    <div>{{ $coupons->links() }}</div>
@endsection