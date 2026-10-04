<x-layouts.account>
    <x-slot:title>Order {{ $order->order_number }} | {{ config('app.name') }}</x-slot:title>

    <div class="space-y-6">
        <div class="mb-6 flex items-center justify-between gap-4">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.2em] text-[#a48335]">Order Details</p>
                <h1 class="mt-2 text-3xl font-extrabold text-[#145943]">{{ $order->order_number }}</h1>
            </div>
            <a href="{{ route('account.orders') }}" class="rounded-xl border border-[#dfe3cf] bg-white px-4 py-2 text-sm font-bold text-[#145943]">Back to My Orders</a>
        </div>

        @if (session('status'))
            <p class="rounded-[4px] border border-green-200 bg-green-50 px-4 py-3 text-sm font-semibold text-green-800">{{ session('status') }}</p>
        @endif
        @if ($errors->has('order'))
            <p class="rounded-[4px] border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-800">{{ $errors->first('order') }}</p>
        @endif
        @if ($order->status === 'pending' && $order->shipments->isEmpty())
            <form method="POST" action="{{ route('account.orders.cancel', $order) }}">
                @csrf
                <button type="submit" class="rounded-[4px] border border-red-700 px-4 py-2 text-sm font-bold text-red-800 hover:bg-red-50">Cancel order</button>
            </form>
        @endif

        <div class="mb-8 rounded-[22px] border border-[#e4e3d8] bg-white p-6 shadow-sm">
            <div class="flex flex-wrap gap-2 text-sm font-semibold">
                @php
                    $steps = [
                        'pending' => 'Order Placed',
                        'confirmed' => 'Order Confirmed',
                        'processing' => 'Processing',
                        'shipped' => 'Shipped',
                        'out_for_delivery' => 'Out for Delivery',
                        'delivered' => 'Delivered',
                        'cancelled' => 'Cancelled',
                        'returned' => 'Returned',
                    ];
                    $current = $order->status ?? 'pending';
                    $orderedKeys = ['pending', 'confirmed', 'processing', 'shipped', 'out_for_delivery', 'delivered'];
                    if ($current === 'cancelled') {
                        $orderedKeys = ['pending', 'confirmed', 'cancelled'];
                    }
                    if ($current === 'returned') {
                        $orderedKeys = ['pending', 'shipped', 'out_for_delivery', 'returned'];
                    }
                @endphp
                @foreach ($orderedKeys as $key)
                    @php $label = $steps[$key] ?? ucfirst(str_replace('_', ' ', $key)); @endphp
                    <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-bold uppercase tracking-[0.14em] {{ $current === $key ? 'bg-[#145943] text-white' : ($key === 'cancelled' || $key === 'returned' ? 'bg-[#fef0d8] text-[#a15b1d]' : 'bg-[#edf2e4] text-[#145943]') }}">
                        {{ $label }}
                    </span>
                @endforeach
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-[1.5fr_1fr]">
            <div class="space-y-6">
                <div class="rounded-[22px] border border-[#e4e3d8] bg-white p-6 shadow-sm">
                    <h2 class="text-lg font-extrabold text-[#145943]">Order Information</h2>
                    <div class="mt-5 grid gap-4 sm:grid-cols-2">
                        <div><p class="text-[10px] font-bold uppercase tracking-[0.2em] text-[#a48335]">Order date</p><p class="mt-2 text-sm text-[#4d5c56]">{{ $order->created_at?->format('d M Y, h:i A') }}</p></div>
                        <div><p class="text-[10px] font-bold uppercase tracking-[0.2em] text-[#a48335]">Order status</p><p class="mt-2 text-sm text-[#4d5c56]">{{ $order->status }}</p></div>
                        <div><p class="text-[10px] font-bold uppercase tracking-[0.2em] text-[#a48335]">Payment status</p><p class="mt-2 text-sm text-[#4d5c56]">{{ $order->payment_status ?? 'pending' }}</p></div>
                        <div><p class="text-[10px] font-bold uppercase tracking-[0.2em] text-[#a48335]">Payment method</p><p class="mt-2 text-sm text-[#4d5c56]">{{ match ($order->payment_method) { 'bkash' => 'bKash', 'nagad' => 'Nagad', default => 'Cash on Delivery' } }}</p></div>
                    </div>
                </div>

                <div class="rounded-[22px] border border-[#e4e3d8] bg-white p-6 shadow-sm">
                    <h2 class="text-lg font-extrabold text-[#145943]">Products</h2>
                    <div class="mt-5 space-y-4">
                        @foreach ($order->items as $item)
                            <div class="flex gap-4 border-b border-[#eef0ea] pb-4 last:border-b-0 last:pb-0">
                                <div class="flex h-16 w-16 items-center justify-center rounded-xl bg-[#f5f1e7] text-xs font-bold uppercase text-[#145943]">IMG</div>
                                <div class="flex-1">
                                    <p class="font-bold text-[#173b32]">{{ $item->name }}</p>
                                    @if ($item->variant_name)
                                        <p class="text-xs text-[#738078]">Variant: {{ $item->variant_name }}</p>
                                    @endif
                                    <p class="mt-1 text-xs text-[#738078]">Qty: {{ $item->quantity }}</p>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm font-bold text-[#145943]">৳{{ number_format((float) ($item->price * $item->quantity), 2) }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="rounded-[22px] border border-[#e4e3d8] bg-white p-6 shadow-sm">
                    <h2 class="text-lg font-extrabold text-[#145943]">Delivery Information</h2>
                    <div class="mt-5 space-y-2 text-sm text-[#4d5c56]">
                        <p><span class="font-bold text-[#173b32]">Recipient:</span> {{ $order->customer_name }}</p>
                        <p><span class="font-bold text-[#173b32]">Phone:</span> {{ $order->phone }}</p>
                        <p><span class="font-bold text-[#173b32]">Address:</span> {{ $order->address }}</p>
                        <p><span class="font-bold text-[#173b32]">Area / City:</span> {{ $order->area ? $order->area.' / ' : '' }}{{ $order->city }}</p>
                    </div>
                </div>
            </div>

            <aside class="space-y-6">
                <div class="rounded-[22px] border border-[#e4e3d8] bg-white p-6 shadow-sm">
                    <h2 class="text-lg font-extrabold text-[#145943]">Price Summary</h2>
                    <div class="mt-5 space-y-3 text-sm">
                        <div class="flex justify-between"><span class="text-[#4d5c56]">Subtotal</span><span class="font-semibold text-[#173b32]">৳{{ number_format((float) $order->subtotal, 2) }}</span></div>
                        <div class="flex justify-between"><span class="text-[#4d5c56]">Discount</span><span class="font-semibold text-[#173b32]">৳{{ number_format((float) $order->discount, 2) }}</span></div>
                        <div class="flex justify-between"><span class="text-[#4d5c56]">Shipping</span><span class="font-semibold text-[#173b32]">৳{{ number_format((float) $order->delivery_fee, 2) }}</span></div>
                        <div class="flex justify-between border-t border-[#eef0ea] pt-3"><span class="font-bold text-[#173b32]">Grand total</span><span class="text-lg font-extrabold text-[#145943]">৳{{ number_format((float) $order->total, 2) }}</span></div>
                    </div>
                </div>

                @php $shipment = $order->shipments->last(); @endphp
                <div id="tracking" class="rounded-[22px] border border-[#e4e3d8] bg-white p-6 shadow-sm">
                    <h2 class="text-lg font-extrabold text-[#145943]">Tracking</h2>
                    @if ($shipment)
                        <div class="mt-5 space-y-3 text-sm text-[#4d5c56]">
                            <p><span class="font-bold text-[#173b32]">Courier:</span> {{ ucfirst($shipment->courier_code) }}</p>
                            <p><span class="font-bold text-[#173b32]">Tracking ID:</span> {{ $shipment->tracking_code ?? $shipment->consignment_id ?? 'N/A' }}</p>
                            <p><span class="font-bold text-[#173b32]">Status:</span> {{ $tracking['current_status'] ?? $shipment->status }}</p>
                            @if ($shipment->updated_at)
                                <p><span class="font-bold text-[#173b32]">Last updated:</span> {{ $shipment->updated_at->format('d M Y, h:i A') }}</p>
                            @endif
                        </div>
                        @php $trackingUrl = app(App\Services\Courier\CourierManager::class)->trackingUrl($order); @endphp
                        @if ($trackingUrl)
                            <a href="{{ $trackingUrl }}" target="_blank" rel="noopener noreferrer" class="mt-5 inline-flex rounded-xl bg-[#145943] px-4 py-2 text-sm font-bold text-white">Track on Courier</a>
                        @else
                            <div class="mt-5 rounded-xl bg-[#f8f9f2] px-3 py-2 text-xs text-[#4d5c56]">Official courier tracking details are not configured for this shipment.</div>
                        @endif
                    @else
                        <p class="mt-4 text-sm text-[#738078]">No courier shipment has been created yet.</p>
                    @endif
                </div>
            </aside>
        </div>
    </div>
</x-layouts.account>
