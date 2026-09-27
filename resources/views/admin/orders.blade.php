@extends('admin.layout')

@section('content')
    <div class="mb-6 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        <div>
            <p class="text-xs font-bold uppercase tracking-[0.2em] text-[#a48335]">Admin</p>
            <h1 class="mt-2 text-3xl font-extrabold text-[#145943]">Orders</h1>
        </div>

        <div class="flex gap-2">
            <a href="{{ route('admin') }}" class="rounded-xl border border-[#dfe3cf] bg-white px-4 py-2 text-sm font-bold text-[#145943]">Products</a>
            <a href="{{ route('admin.orders') }}" class="rounded-xl bg-[#145943] px-4 py-2 text-sm font-bold text-white">Orders</a>
        </div>
    </div>

    <div class="overflow-hidden rounded-[22px] border border-[#e4e3d8] bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm text-[#4d5c56]">
                <thead class="bg-[#f8f9f2] text-[#145943]">
                    <tr>
                        <th class="px-5 py-3 font-bold">Order</th>
                        <th class="px-5 py-3 font-bold">Customer</th>
                        <th class="px-5 py-3 font-bold">Products</th>
                        <th class="px-5 py-3 font-bold">Total</th>
                        <th class="px-5 py-3 font-bold">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($orders as $order)
                        <tr class="border-t border-[#e4e3d8]">
                            <td class="px-5 py-4 font-semibold text-[#173b32]">{{ $order->order_number }}</td>
                            <td class="px-5 py-4">
                                <div class="font-semibold text-[#173b32]">{{ $order->customer_name }}</div>
                                <div class="text-xs text-[#738078]">{{ $order->phone }}</div>
                            </td>
                            <td class="px-5 py-4">
                                @foreach ($order->items as $item)
                                    <div class="text-xs text-[#4d5c56]">{{ $item->name }} × {{ $item->quantity }}</div>
                                @endforeach
                            </td>
                            <td class="px-5 py-4 font-bold text-[#145943]">৳{{ number_format((float) $order->total, 2) }}</td>
                            <td class="px-5 py-4">
                                <span class="rounded-full bg-[#edf2e4] px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide text-[#145943]">
                                    {{ $order->status }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-10 text-center text-[#738078]">
                                No orders yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
