<x-layouts.account>
    <x-slot:title>Order History | {{ config('app.name') }}</x-slot:title>

    <div class="space-y-6">
        <header>
            <p class="text-xs font-bold uppercase tracking-[0.2em] text-[#a48335]">Your account</p>
            <h1 class="mt-2 text-3xl font-extrabold text-[#145943]">Order History</h1>
        </header>

        @if ($orders->isEmpty())
            <section class="border border-dashed border-[#dfe3cf] bg-white p-8 text-center sm:p-10">
                <h2 class="text-lg font-bold text-[#173b32]">No past orders yet</h2>
                <p class="mt-2 text-sm text-[#738078]">Completed, cancelled, and returned orders will appear here.</p>
            </section>
        @else
            <div class="space-y-4">
                @foreach ($orders as $order)
                    <article class="border border-[#e4e3d8] bg-white p-5 shadow-sm sm:p-6">
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                            <div>
                                <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-[#a48335]">Order number</p>
                                <h2 class="mt-1 text-lg font-extrabold text-[#173b32]">{{ $order->order_number }}</h2>
                                <p class="mt-1 text-sm text-[#738078]">{{ $order->created_at?->format('d M Y') }}</p>
                            </div>
                            <div class="sm:text-right">
                                <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-[#a48335]">Final status</p>
                                <p class="mt-1 text-sm font-bold capitalize text-[#145943]">{{ str_replace('_', ' ', $order->status) }}</p>
                                <p class="mt-1 text-sm font-semibold text-[#173b32]">৳{{ number_format((float) $order->total, 2) }}</p>
                            </div>
                        </div>

                        <div class="mt-4 flex flex-wrap items-center justify-between gap-3 border-t border-[#eef0ea] pt-4">
                            @if ($order->shipments->isNotEmpty())
                                <p class="text-sm text-[#4d5c56]">Courier: {{ ucfirst($order->shipments->last()->courier_code) }}</p>
                            @else
                                <span></span>
                            @endif
                            <a href="{{ route('account.orders.show', $order) }}" class="rounded-[4px] border border-[#dfe3cf] px-4 py-2 text-sm font-bold text-[#145943] hover:bg-[#edf2e4]">View order</a>
                        </div>
                    </article>
                @endforeach
            </div>

            <div>{{ $orders->links() }}</div>
        @endif
    </div>
</x-layouts.account>