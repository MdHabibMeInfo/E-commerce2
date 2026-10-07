<x-layouts.app>
    <x-slot:title>
        Order Placed — {{ config('app.name') }}
    </x-slot:title>

    <x-slot:metaDescription>
        Your order has been placed successfully and is now being processed.
    </x-slot:metaDescription>

    <x-slot:canonicalUrl>
        {{ route('home') }}
    </x-slot:canonicalUrl>

    @if (is_array(session('metaPurchaseEvent')))
        <script>
            window.metaPurchaseEvent = {{ \Illuminate\Support\Js::from(session('metaPurchaseEvent')) }};
        </script>
    @endif

    <x-slot:head>
        <meta name="robots" content="noindex, nofollow">
    </x-slot:head>

    <section class="bg-[#f5f7ee]">
        <div class="mx-auto max-w-3xl px-4 py-16 sm:px-6 lg:px-8">
            <div class="rounded-[28px] border border-[#dfe3cf] bg-white p-8 text-center shadow-sm sm:p-10">
                <div class="mx-auto mb-5 grid h-16 w-16 place-items-center rounded-full bg-[#edf1d8] text-3xl">✅</div>
                <p class="text-xs font-bold uppercase tracking-[0.2em] text-[#a48335]">Order Success</p>
                <h1 class="mt-3 text-3xl font-extrabold text-[#145943]">Your order has been placed</h1>
                <p class="mt-3 text-sm text-[#66736d]">
                    Thank you, {{ $order->customer_name }}. Your order number is <span class="font-bold text-[#145943]">{{ $order->order_number }}</span>.
                </p>

                <div class="mt-6 rounded-2xl bg-[#f7f6f1] p-5 text-left text-sm text-[#4d5c56]">
                    <div class="flex items-center justify-between gap-4">
                        <span>Payment method</span>
                        <span class="font-semibold text-[#173b32]">{{ match ($order->payment_method) { 'bkash' => 'bKash', 'nagad' => 'Nagad', default => 'Cash on Delivery' } }}</span>
                    </div>
                    @if (in_array($order->payment_method, ['bkash', 'nagad'], true))
                        <div class="mt-3 flex items-center justify-between gap-4">
                            <span>Payment status</span>
                            <span class="font-semibold capitalize text-[#173b32]">{{ $order->payment_status }}</span>
                        </div>
                        <p class="mt-3 text-xs text-[#738078]">Your payment will be confirmed after the transaction details are reviewed.</p>
                    @endif
                    <div class="mt-3 flex items-center justify-between gap-4">
                        <span>Total amount</span>
                        <span class="font-bold text-[#145943]">৳{{ number_format((float) $order->total, 2) }}</span>
                    </div>
                    <div class="mt-3 flex items-center justify-between gap-4">
                        <span>Status</span>
                        <span class="font-semibold capitalize text-[#145943]">{{ $order->status }}</span>
                    </div>
                </div>

                <div class="mt-8 flex flex-col justify-center gap-3 sm:flex-row">
                    <a href="{{ route('shop') }}" class="inline-flex items-center justify-center rounded-xl bg-[#145943] px-5 py-3 text-sm font-bold text-white transition hover:bg-[#a48335]">
                        Continue Shopping
                    </a>
                    <a href="{{ route('home') }}" class="inline-flex items-center justify-center rounded-xl border border-[#dfe3cf] bg-white px-5 py-3 text-sm font-bold text-[#145943] transition hover:border-[#145943]">
                        Go Home
                    </a>
                </div>
            </div>
        </div>
    </section>
</x-layouts.app>
