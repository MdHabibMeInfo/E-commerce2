@extends('admin.layout')

@section('title', $user->name.' | Customer')

@section('content')
    <div class="flex flex-wrap items-center justify-between gap-3">
        <a href="{{ route('admin.customers.index') }}" class="text-sm font-bold text-[#145943] hover:underline">Back to customers</a>
        <span class="text-sm font-bold {{ $user->status === 'active' ? 'text-green-700' : 'text-red-700' }}">{{ ucfirst($user->status) }}</span>
    </div>

    <section class="flex flex-wrap items-center gap-4 border-b border-[#e5e2d9] pb-5">
        @if ($user->profile_photo_url)
            <img src="{{ $user->profile_photo_url }}" alt="{{ $user->name }}" class="size-16 shrink-0 rounded-full object-cover">
        @else
            <span class="grid size-16 shrink-0 place-items-center rounded-full bg-[#edf2e4] text-xl font-bold text-[#145943]">{{ strtoupper(substr($user->name, 0, 1)) }}</span>
        @endif
        <div class="min-w-0">
            <p class="text-xs font-bold uppercase tracking-[0.16em] text-[#a48335]">Customer account</p>
            <h1 class="mt-1 text-2xl font-extrabold text-[#145943]">{{ $user->name }}</h1>
            <p class="mt-1 break-all text-sm text-[#738078]">{{ $user->email }}</p>
            <p class="mt-1 text-sm text-[#738078]">{{ $user->phone ?: 'Phone not provided' }} · {{ $user->hasVerifiedEmail() ? 'Email verified' : 'Email unverified' }} · Joined {{ $user->created_at->format('M j, Y') }}</p>
        </div>
    </section>

    <dl class="grid grid-cols-1 border border-[#e5e2d9] bg-white sm:grid-cols-3">
        <div class="border-b border-[#e5e2d9] px-4 py-4 sm:border-b-0 sm:border-r">
            <dt class="text-xs font-bold uppercase text-[#738078]">Orders</dt>
            <dd class="mt-1 text-xl font-extrabold text-[#173b32]">{{ number_format($stats['order_count']) }}</dd>
        </div>
        <div class="border-b border-[#e5e2d9] px-4 py-4 sm:border-b-0 sm:border-r">
            <dt class="text-xs font-bold uppercase text-[#738078]">Total order value</dt>
            <dd class="mt-1 text-xl font-extrabold text-[#173b32]">৳{{ number_format((float) $stats['order_value'], 2) }}</dd>
        </div>
        <div class="px-4 py-4">
            <dt class="text-xs font-bold uppercase text-[#738078]">Reviews</dt>
            <dd class="mt-1 text-xl font-extrabold text-[#173b32]">{{ number_format($stats['review_count']) }}</dd>
        </div>
    </dl>

    <section>
        <div class="flex items-baseline justify-between gap-3">
            <h2 class="text-lg font-extrabold text-[#145943]">Orders</h2>
            <span class="text-xs text-[#738078]">{{ number_format($stats['order_count']) }} total</span>
        </div>
        <div class="mt-3 overflow-x-auto border border-[#e5e2d9] bg-white">
            <table class="min-w-full text-left text-sm">
                <thead class="bg-[#f7f4ee] text-[#145943]"><tr>
                    <th class="px-4 py-3 font-bold">Order</th>
                    <th class="px-4 py-3 font-bold">Date</th>
                    <th class="px-4 py-3 font-bold">Total</th>
                    <th class="px-4 py-3 font-bold">Payment</th>
                    <th class="px-4 py-3 font-bold">Status</th>
                </tr></thead>
                <tbody>
                    @forelse ($orders as $order)
                        <tr class="border-t border-[#e5e2d9]">
                            <td class="px-4 py-3"><a href="{{ route('admin.orders.show', $order) }}" class="font-bold text-[#145943] hover:underline">{{ $order->order_number }}</a></td>
                            <td class="px-4 py-3">{{ $order->created_at->format('M j, Y') }}</td>
                            <td class="px-4 py-3">৳{{ number_format((float) $order->total, 2) }}</td>
                            <td class="px-4 py-3">{{ ucfirst($order->payment_status) }}</td>
                            <td class="px-4 py-3">{{ ucfirst($order->status) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-8 text-center text-[#738078]">No orders for this customer.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-3">{{ $orders->links() }}</div>
    </section>

    <section>
        <div class="flex items-baseline justify-between gap-3">
            <h2 class="text-lg font-extrabold text-[#145943]">Reviews</h2>
            <span class="text-xs text-[#738078]">{{ number_format($stats['review_count']) }} total</span>
        </div>
        <div class="mt-3 divide-y divide-[#e5e2d9] border border-[#e5e2d9] bg-white">
            @forelse ($reviews as $review)
                <article class="px-4 py-4">
                    <div class="flex flex-wrap items-baseline justify-between gap-x-4 gap-y-1">
                        <h3 class="font-bold text-[#173b32]">{{ $review->product->name }}</h3>
                        <span class="text-xs text-[#738078]">{{ $review->created_at->format('M j, Y') }}</span>
                    </div>
                    <div class="mt-1 flex flex-wrap gap-x-3 gap-y-1 text-sm">
                        <span class="font-bold text-[#a48335]">{{ $review->rating }} / 5</span>
                        <span class="text-[#738078]">{{ $review->rejected_at ? 'Rejected' : ($review->is_approved ? 'Published' : 'Pending') }}</span>
                    </div>
                    <p class="mt-2 whitespace-pre-line text-sm leading-6 text-[#4d5c56]">{{ $review->body }}</p>
                </article>
            @empty
                <p class="px-4 py-8 text-center text-sm text-[#738078]">No reviews for this customer.</p>
            @endforelse
        </div>
        <div class="mt-3">{{ $reviews->links() }}</div>
    </section>
@endsection