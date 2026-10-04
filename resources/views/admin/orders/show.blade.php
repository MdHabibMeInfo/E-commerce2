@extends('admin.layout')

@section('content')
    <div class="mb-6 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        <div>
            <p class="text-xs font-bold uppercase tracking-[0.2em] text-[#a48335]">Admin</p>
            <h1 class="mt-2 text-3xl font-extrabold text-[#145943]">Order Details</h1>
        </div>
        <a href="{{ route('admin.orders') }}" class="rounded-xl border border-[#dfe3cf] bg-white px-4 py-2 text-sm font-bold text-[#145943]">Back to Orders</a>
    </div>

    @if (session('success'))
        <p class="mb-5 rounded-[4px] border border-green-200 bg-green-50 px-4 py-3 text-sm font-semibold text-green-800">{{ session('success') }}</p>
    @endif
    @if ($errors->any())
        <p class="mb-5 rounded-[4px] border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-800">{{ $errors->first() }}</p>
    @endif

    <div class="grid gap-6 xl:grid-cols-[1.5fr_0.9fr]">
        <div class="space-y-6">
            <div class="rounded-[22px] border border-[#e4e3d8] bg-white p-6 shadow-sm">
                <h2 class="text-lg font-extrabold text-[#145943]">Order Information</h2>
                <div class="mt-5 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    <div><p class="text-[10px] font-bold uppercase tracking-[0.2em] text-[#a48335]">Order number</p><p class="mt-2 text-sm text-[#4d5c56]">{{ $order->order_number }}</p></div>
                    <div><p class="text-[10px] font-bold uppercase tracking-[0.2em] text-[#a48335]">Order date</p><p class="mt-2 text-sm text-[#4d5c56]">{{ $order->created_at?->format('d M Y') }}</p></div>
                    <div><p class="text-[10px] font-bold uppercase tracking-[0.2em] text-[#a48335]">Order status</p><p class="mt-2 text-sm text-[#4d5c56]">{{ $order->status }}</p></div>
                    <div><p class="text-[10px] font-bold uppercase tracking-[0.2em] text-[#a48335]">Payment status</p><p class="mt-2 text-sm text-[#4d5c56]">{{ $order->payment_status ?? 'pending' }}</p></div>
                    <div><p class="text-[10px] font-bold uppercase tracking-[0.2em] text-[#a48335]">Payment method</p><p class="mt-2 text-sm text-[#4d5c56]">{{ strtoupper($order->payment_method ?? 'cod') }}</p></div>
                    <div><p class="text-[10px] font-bold uppercase tracking-[0.2em] text-[#a48335]">Grand total</p><p class="mt-2 text-sm font-bold text-[#145943]">৳{{ number_format((float) $order->total, 2) }}</p></div>
                </div>
            </div>

            <div class="rounded-[22px] border border-[#e4e3d8] bg-white p-6 shadow-sm">
                <h2 class="text-lg font-extrabold text-[#145943]">Customer Information</h2>
                <div class="mt-5 grid gap-4 md:grid-cols-3">
                    <div><p class="text-[10px] font-bold uppercase tracking-[0.2em] text-[#a48335]">Name</p><p class="mt-2 text-sm text-[#4d5c56]">{{ $order->customer_name }}</p></div>
                    <div><p class="text-[10px] font-bold uppercase tracking-[0.2em] text-[#a48335]">Phone</p><p class="mt-2 text-sm text-[#4d5c56]">{{ $order->phone }}</p></div>
                    <div><p class="text-[10px] font-bold uppercase tracking-[0.2em] text-[#a48335]">Email</p><p class="mt-2 text-sm text-[#4d5c56]">{{ $order->user?->email ?? 'Not available' }}</p></div>
                </div>
            </div>

            @if (in_array($order->payment_method, ['bkash', 'nagad'], true))
                <div class="rounded-[22px] border border-[#e4e3d8] bg-white p-6 shadow-sm">
                    <h2 class="text-lg font-extrabold text-[#145943]">Mobile Payment Details</h2>
                    <div class="mt-5 grid gap-4 sm:grid-cols-2">
                        <div><p class="text-[10px] font-bold uppercase tracking-[0.2em] text-[#a48335]">Sender number</p><p class="mt-2 text-sm text-[#4d5c56]">{{ $order->payment_sender_phone }}</p></div>
                        <div><p class="text-[10px] font-bold uppercase tracking-[0.2em] text-[#a48335]">Transaction ID</p><p class="mt-2 break-all text-sm font-semibold text-[#4d5c56]">{{ $order->payment_transaction_id }}</p></div>
                        <div><p class="text-[10px] font-bold uppercase tracking-[0.2em] text-[#a48335]">Amount submitted</p><p class="mt-2 text-sm font-bold text-[#145943]">৳{{ number_format((float) $order->payment_amount, 2) }}</p></div>
                        <div><p class="text-[10px] font-bold uppercase tracking-[0.2em] text-[#a48335]">Review</p><p class="mt-2 text-sm text-[#4d5c56]">{{ $order->paymentReviewer?->name ? 'Reviewed by '.$order->paymentReviewer->name : 'Awaiting review' }}</p></div>
                    </div>
                    @if ($order->payment_proof_path)
                        <a href="{{ route('admin.orders.payment-proof', $order) }}" target="_blank" rel="noopener" class="mt-5 inline-block">
                            <img src="{{ route('admin.orders.payment-proof', $order) }}" alt="Payment receipt for {{ $order->order_number }}" class="max-h-96 max-w-full rounded-xl border border-[#dfe3cf] object-contain">
                        </a>
                    @endif
                    @if ($order->payment_status === 'pending')
                        <div class="mt-5 flex flex-col gap-3 border-t border-[#eef0ea] pt-5 sm:flex-row">
                            <form method="POST" action="{{ route('admin.orders.payment.review', $order) }}" class="flex-1">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="payment_status" value="paid">
                                <button type="submit" class="w-full rounded-xl bg-[#145943] px-4 py-3 text-sm font-bold text-white">Confirm payment</button>
                            </form>
                            <form method="POST" action="{{ route('admin.orders.payment.review', $order) }}" class="flex-1">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="payment_status" value="failed">
                                <button type="submit" class="w-full rounded-xl border border-red-300 px-4 py-3 text-sm font-bold text-red-800 hover:bg-red-50">Reject payment</button>
                            </form>
                        </div>
                    @endif
                </div>
            @endif

            <div class="rounded-[22px] border border-[#e4e3d8] bg-white p-6 shadow-sm">
                <h2 class="text-lg font-extrabold text-[#145943]">Delivery Information</h2>
                <div class="mt-5 text-sm text-[#4d5c56]">
                    <p>{{ $order->customer_name }}</p>
                    <p>{{ $order->phone }}</p>
                    <p>{{ $order->address }}</p>
                    <p>{{ $order->area ? $order->area.' / ' : '' }}{{ $order->city }}</p>
                </div>
            </div>

            <div class="rounded-[22px] border border-[#e4e3d8] bg-white p-6 shadow-sm">
                <h2 class="text-lg font-extrabold text-[#145943]">Order Items</h2>
                <div class="mt-5 space-y-4">
                    @foreach ($order->items as $item)
                        <div class="flex items-start justify-between gap-4 border-b border-[#eef0ea] pb-4 last:border-b-0 last:pb-0">
                            <div>
                                <p class="font-bold text-[#173b32]">{{ $item->name }}</p>
                                @if ($item->variant_name)
                                    <p class="text-xs text-[#738078]">{{ $item->variant_name }}{{ $item->variant_sku ? ' · '.$item->variant_sku : '' }}</p>
                                @endif
                                <p class="mt-1 text-xs text-[#738078]">Qty: {{ $item->quantity }}</p>
                            </div>
                            <div class="text-right text-sm font-semibold text-[#145943]">
                                <div>৳{{ number_format((float) $item->price, 2) }}</div>
                                <div class="text-xs text-[#738078]">Subtotal: ৳{{ number_format((float) ($item->price * $item->quantity), 2) }}</div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <aside class="space-y-6">
            <div class="rounded-[22px] border border-[#e4e3d8] bg-white p-6 shadow-sm">
                <h2 class="text-lg font-extrabold text-[#145943]">Courier Information</h2>
                @php $shipment = $order->shipments->last(); @endphp
                @if ($shipment)
                    <div class="mt-5 space-y-3 text-sm text-[#4d5c56]">
                        <p><span class="font-bold text-[#173b32]">Courier:</span> {{ ucfirst($shipment->courier_code) }}</p>
                        <p><span class="font-bold text-[#173b32]">Shipment status:</span> {{ $shipment->status }}</p>
                        <p><span class="font-bold text-[#173b32]">Consignment ID:</span> {{ $shipment->consignment_id ?? 'N/A' }}</p>
                        <p><span class="font-bold text-[#173b32]">Tracking ID:</span> {{ $shipment->tracking_code ?? 'N/A' }}</p>
                    </div>
                    @if ($shipment->error_message)
                        <p class="mt-4 rounded-[4px] border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-900">{{ $shipment->error_message }}</p>
                    @endif
                    @if ($shipment->status === 'failed')
                        <form method="POST" action="{{ route('admin.orders.shipments.store', $order) }}" class="mt-5">
                            @csrf
                            <input type="hidden" name="courier_code" value="{{ $shipment->courier_code }}">
                            <button type="submit" class="w-full rounded-xl border border-[#145943] px-4 py-3 text-sm font-bold text-[#145943] hover:bg-[#edf2e4]">Retry shipment</button>
                        </form>
                    @elseif (in_array($shipment->status, ['creating', 'unknown'], true))
                        <p class="mt-4 text-xs font-semibold text-amber-900">Check the courier merchant portal before retrying this shipment.</p>
                    @endif
                @elseif ($availableCouriers === [])
                    <p class="mt-5 text-sm text-[#738078]">No verified courier integration is enabled for this store.</p>
                @else
                    <form method="POST" action="{{ route('admin.orders.shipments.store', $order) }}" class="mt-5 space-y-4">
                        @csrf
                        <label class="block text-sm font-semibold text-[#173b32]">
                            Courier
                            <select name="courier_code" class="mt-2 block w-full rounded-xl border border-[#dfe3cf] bg-white px-3 py-3 text-sm text-[#173b32]">
                                @foreach ($availableCouriers as $courier)
                                    <option value="{{ $courier->code() }}">{{ $courier->label() }}</option>
                                @endforeach
                            </select>
                        </label>
                        <button type="submit" class="w-full rounded-xl bg-[#145943] px-4 py-3 text-sm font-bold text-white">Create Shipment</button>
                    </form>
                @endif
            </div>

            <div class="rounded-[22px] border border-[#e4e3d8] bg-white p-6 shadow-sm">
                <h2 class="text-lg font-extrabold text-[#145943]">Customer Courier History</h2>
                <div class="mt-5 space-y-2 text-sm text-[#4d5c56]">
                    <p>Total shipments: Not available from courier API</p>
                    <p>Delivered: Not available from courier API</p>
                    <p>Success ratio: Not available from courier API</p>
                </div>
            </div>
        </aside>
    </div>
@endsection
