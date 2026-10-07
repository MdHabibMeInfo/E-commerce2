@extends('admin.layout')

@section('title', 'Customers')

@section('content')
    <header class="flex flex-wrap items-end justify-between gap-4 border-b border-[#e5e2d9] pb-4">
        <div>
            <p class="text-xs font-bold uppercase tracking-[0.16em] text-[#a48335]">Accounts</p>
            <h1 class="mt-1 text-2xl font-extrabold text-[#145943]">Customer Accounts</h1>
        </div>
        <div class="flex gap-4 text-sm"><span><b>{{ $stats['active'] }}</b> active</span><span><b>{{ $stats['suspended'] }}</b> suspended</span></div>
    </header>

    @if (session('success'))
        <p class="border border-green-200 bg-green-50 px-4 py-3 text-sm font-semibold text-green-800">{{ session('success') }}</p>
    @endif
    @if ($errors->any())
        <p class="border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ $errors->first() }}</p>
    @endif

    <form method="GET" action="{{ route('admin.customers.index') }}" class="flex flex-wrap gap-3">
        <label class="sr-only" for="customer-search">Search customers</label>
        <input id="customer-search" name="search" value="{{ $search }}" maxlength="100" placeholder="Name, email, or phone" class="h-10 min-w-0 flex-1 border border-[#dfe3cf] bg-white px-3 text-sm">
        <label class="sr-only" for="customer-status">Filter status</label>
        <select id="customer-status" name="status" class="h-10 border border-[#dfe3cf] bg-white px-3 text-sm">
            <option value="">All statuses</option>
            <option value="active" @selected(request('status') === 'active')>Active</option>
            <option value="suspended" @selected(request('status') === 'suspended')>Suspended</option>
        </select>
        <button class="h-10 bg-[#145943] px-4 text-sm font-bold text-white">Search</button>
    </form>

    <section class="overflow-x-auto border border-[#e5e2d9] bg-white">
        <table class="min-w-full text-left text-sm">
            <thead class="bg-[#f7f4ee] text-[#145943]"><tr>
                <th class="px-4 py-3 font-bold">Customer</th>
                <th class="px-4 py-3 font-bold">Phone</th>
                <th class="px-4 py-3 font-bold">Email status</th>
                <th class="px-4 py-3 font-bold">Orders</th>
                <th class="px-4 py-3 font-bold">Account</th>
                <th class="px-4 py-3 font-bold">Action</th>
            </tr></thead>
            <tbody>
                @forelse ($customers as $customer)
                    <tr class="border-t border-[#e5e2d9]">
                        <td class="px-4 py-3">
                            <a href="{{ route('admin.customers.show', $customer) }}" class="flex min-w-48 items-center gap-3">
                                @if ($customer->profile_photo_url)
                                    <img src="{{ $customer->profile_photo_url }}" alt="{{ $customer->name }}" class="size-10 shrink-0 rounded-full object-cover">
                                @else
                                    <span class="grid size-10 shrink-0 place-items-center rounded-full bg-[#edf2e4] text-sm font-bold text-[#145943]">{{ strtoupper(substr($customer->name, 0, 1)) }}</span>
                                @endif
                                <span class="min-w-0">
                                    <span class="block truncate font-bold text-[#173b32]">{{ $customer->name }}</span>
                                    <span class="block truncate text-xs text-[#738078]">{{ $customer->email }}</span>
                                </span>
                            </a>
                        </td>
                        <td class="px-4 py-3">{{ $customer->phone ?: 'Not provided' }}</td>
                        <td class="px-4 py-3">{{ $customer->hasVerifiedEmail() ? 'Verified' : 'Unverified' }}</td>
                        <td class="px-4 py-3">{{ $customer->orders_count }}</td>
                        <td class="px-4 py-3 font-bold {{ $customer->status === 'active' ? 'text-green-700' : 'text-red-700' }}">{{ ucfirst($customer->status) }}</td>
                        <td class="px-4 py-3">
                            <form method="POST" action="{{ route('admin.customers.status.update', $customer) }}">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="status" value="{{ $customer->status === 'active' ? 'suspended' : 'active' }}">
                                <button class="text-xs font-bold {{ $customer->status === 'active' ? 'text-red-700' : 'text-[#145943]' }}">{{ $customer->status === 'active' ? 'Suspend' : 'Reactivate' }}</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-10 text-center text-[#738078]">No customer accounts match these filters.</td></tr>
                @endforelse
            </tbody>
        </table>
    </section>
    <div>{{ $customers->links() }}</div>
@endsection