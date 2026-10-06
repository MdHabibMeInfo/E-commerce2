@extends('admin.layout')

@section('title', 'Notifications | Admin Dashboard')

@section('content')
    <div>
        <p class="text-xs font-bold uppercase tracking-[0.2em] text-[#a48335]">Admin activity</p>
        <h1 class="mt-2 text-3xl font-extrabold text-[#145943]">Notifications</h1>
        <p class="mt-2 text-sm text-[#738078]">New order and customer review activity for your store.</p>
    </div>

    @if ($reviewNotifications->isNotEmpty())
        <section class="mt-6 max-w-4xl overflow-hidden rounded-[4px] border border-[#e4e3d8] bg-white">
            <div class="border-b border-[#ece8dc] px-5 py-4">
                <h2 class="font-extrabold text-[#145943]">Customer reviews</h2>
            </div>
            <div class="divide-y divide-[#ece8dc]">
                @foreach ($reviewNotifications as $review)
                    <a href="{{ route('admin.reviews.show', $review) }}" class="flex items-start justify-between gap-4 p-4 transition hover:bg-[#f8f9f2] sm:p-5">
                        <span class="min-w-0">
                            <span class="block truncate text-sm font-bold text-[#173b32]">New customer review · {{ $review->product->name }}</span>
                            <span class="mt-1 block truncate text-sm text-[#4d5c56]">{{ $review->name }} · {{ $review->rating }}/5 · {{ $review->body }}</span>
                            <span class="mt-1 block text-xs text-[#738078]">{{ $review->rejected_at ? 'Rejected' : ($review->is_approved ? 'Published' : 'Awaiting review') }} · {{ $review->created_at?->format('M j, Y g:i A') }}</span>
                        </span>
                        <span class="shrink-0 text-sm font-bold text-[#145943]">Review details</span>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    @if ($notifications->isNotEmpty() || $reviewNotifications->isEmpty())
        <section class="mt-6 max-w-3xl overflow-hidden rounded-[4px] border border-[#e4e3d8] bg-white">
            <div class="border-b border-[#ece8dc] px-5 py-4">
                <h2 class="font-extrabold text-[#145943]">Orders</h2>
            </div>
            <div class="divide-y divide-[#ece8dc]">
        @forelse ($notifications as $notification)
            <a href="{{ route('admin.orders') }}" class="flex items-start justify-between gap-4 p-4 transition hover:bg-[#f8f9f2] sm:p-5">
                <div class="flex min-w-0 items-start gap-3">
                    <span class="mt-0.5 grid h-9 w-9 shrink-0 place-items-center rounded-full bg-[#edf2e4] text-[#145943]" aria-hidden="true">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">
                            <path d="M5 8h14l1 13H4L5 8Z"></path>
                            <path d="M9 8V6a3 3 0 0 1 6 0v2"></path>
                        </svg>
                    </span>
                    <span class="min-w-0">
                        <span class="block text-sm font-bold text-[#173b32]">New order received</span>
                        <span class="mt-1 block truncate text-sm text-[#4d5c56]">{{ $notification->order_number }} · {{ $notification->customer_name }}</span>
                        <span class="mt-1 block text-xs text-[#738078]">{{ ucfirst($notification->status) }} · {{ $notification->created_at?->format('M j, Y g:i A') }}</span>
                    </span>
                </div>
                <span class="shrink-0 text-sm font-bold text-[#145943]">৳{{ number_format((float) $notification->total, 2) }}</span>
            </a>
        @empty
            <p class="px-5 py-10 text-center text-sm text-[#738078]">No recent order notifications.</p>
        @endforelse
            </div>
        </section>
    @endif
@endsection
