<x-layouts.account>
    <x-slot:title>My Orders | {{ config('app.name') }}</x-slot:title>

    <div class="space-y-6">
        <div class="mb-8 flex items-center justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.2em] text-[#a48335]">Account</p>
                <h1 class="mt-2 text-3xl font-extrabold text-[#145943]">My Orders</h1>
            </div>
            <a href="{{ route('home') }}" class="rounded-xl border border-[#dfe3cf] bg-white px-4 py-2 text-sm font-bold text-[#145943]">Continue Shopping</a>
        </div>

        @if ($orders->isEmpty())
            <div class="rounded-2xl border border-dashed border-[#dfe3cf] bg-white p-10 text-center">
                <p class="text-lg font-semibold text-[#173b32]">No orders yet.</p>
                <p class="mt-2 text-sm text-[#738078]">Your order history will appear here once you place an order.</p>
            </div>
        @else
            <div class="space-y-5">
                @foreach ($orders as $order)
                    @php
                        $shipment = $order->shipments->last();
                    @endphp
                    <div class="rounded-[22px] border border-[#e4e3d8] bg-white p-6 shadow-sm">
                        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                            <div>
                                <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-[#a48335]">Order number</p>
                                <h2 class="mt-2 text-xl font-extrabold text-[#173b32]">{{ $order->order_number }}</h2>
                            </div>
                            <div class="text-left md:text-right">
                                <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-[#a48335]">Total</p>
                                <p class="mt-2 text-xl font-extrabold text-[#145943]">৳{{ number_format((float) $order->total, 2) }}</p>
                            </div>
                        </div>

                        <div class="mt-6 grid gap-4 md:grid-cols-4">
                            <div>
                                <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-[#a48335]">Date</p>
                                <p class="mt-2 text-sm font-medium text-[#4d5c56]">{{ $order->created_at?->format('d M Y') }}</p>
                            </div>
                            <div>
                                <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-[#a48335]">Payment status</p>
                                <p class="mt-2 text-sm font-medium text-[#4d5c56]">{{ $order->payment_status ?? 'pending' }}</p>
                            </div>
                            <div>
                                <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-[#a48335]">Order status</p>
                                <p class="mt-2 text-sm font-medium text-[#4d5c56]">{{ $order->status }}</p>
                            </div>
                            <div>
                                <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-[#a48335]">Courier</p>
                                <p class="mt-2 text-sm font-medium text-[#4d5c56]">{{ $shipment?->courier_code ? ucfirst($shipment->courier_code) : 'N/A' }}</p>
                            </div>
                        </div>

                        <div class="mt-6 flex flex-wrap gap-3">
                            <a href="{{ route('account.orders.show', $order) }}" class="rounded-xl bg-[#145943] px-4 py-2 text-sm font-bold text-white">View Details</a>
                            @if ($shipment)
                                <a href="{{ route('account.orders.show', $order) }}#tracking" class="rounded-xl border border-[#dfe3cf] bg-white px-4 py-2 text-sm font-bold text-[#145943]">Track Order</a>
                            @endif
                            @if ($order->status === 'pending' && $order->shipments->isEmpty())
                                <form method="POST" action="{{ route('account.orders.cancel', $order) }}">
                                    @csrf
                                    <button type="submit" class="rounded-xl border border-red-700 px-4 py-2 text-sm font-bold text-red-800 hover:bg-red-50">Cancel order</button>
                                </form>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</x-layouts.account>
