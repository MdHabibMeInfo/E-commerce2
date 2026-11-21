@extends('admin.layout')

@section('title', 'Notifications | Admin Dashboard')

@section('content')
    <div>
        <p class="text-xs font-bold uppercase tracking-[0.2em] text-[#a48335]">Admin activity</p>
        <h1 class="mt-2 text-3xl font-extrabold text-[#145943]">Notifications</h1>
        <p class="mt-2 text-sm text-[#738078]">Complete order, review, and account activity history.</p>
    </div>

    @if ($notifications->count() > 0)
        <section class="mt-6 max-w-4xl overflow-hidden rounded-[4px] border border-[#e4e3d8] bg-white">
            <div class="border-b border-[#ece8dc] px-5 py-4">
                <h2 class="font-extrabold text-[#145943]">Activity history</h2>
            </div>
            <div class="divide-y divide-[#ece8dc]">
                @foreach ($notifications as $notification)
                    @php($occurredAt = \Illuminate\Support\Carbon::parse($notification->occurred_at))
                    @if ($notification->notification_type === 'order')
                        <a href="{{ route('admin.orders.show', $notification->id) }}" class="flex items-start justify-between gap-4 p-4 transition hover:bg-[#f8f9f2] sm:p-5">
                            <span class="min-w-0">
                                <span class="block text-sm font-bold text-[#173b32]">New order received · {{ $notification->subject }}</span>
                                <span class="mt-1 block truncate text-sm text-[#4d5c56]">{{ $notification->actor }} · {{ ucfirst(str_replace('_', ' ', $notification->detail)) }}</span>
                                <time class="mt-1 block text-xs text-[#738078]" datetime="{{ $occurredAt->toIso8601String() }}">{{ $occurredAt->format('M j, Y g:i A') }}</time>
                            </span>
                            <span class="shrink-0 text-sm font-bold text-[#145943]">৳{{ number_format((float) $notification->amount, 2) }}</span>
                        </a>
                    @elseif ($notification->notification_type === 'review')
                        <a href="{{ route('admin.reviews.show', $notification->id) }}" class="flex items-start justify-between gap-4 p-4 transition hover:bg-[#f8f9f2] sm:p-5">
                            <span class="min-w-0">
                                <span class="block truncate text-sm font-bold text-[#173b32]">New customer review · {{ $notification->subject }}</span>
                                <span class="mt-1 block text-sm text-[#4d5c56]">{{ $notification->actor }} · {{ $notification->amount }}/5</span>
                                <span class="mt-1 block text-sm text-[#4d5c56]">{{ $notification->detail }}</span>
                                <time class="mt-1 block text-xs text-[#738078]" datetime="{{ $occurredAt->toIso8601String() }}">{{ $occurredAt->format('M j, Y g:i A') }}</time>
                            </span>
                            <span class="shrink-0 text-sm font-bold text-[#145943]">Review details</span>
                        </a>
                    @else
                        <article class="p-4 sm:p-5">
                            <div class="flex items-start justify-between gap-4">
                                <span class="min-w-0">
                                    <span class="block text-sm font-bold text-[#173b32]">{{ str($notification->subject)->replace('_', ' ')->title() }}</span>
                                    <span class="mt-1 block truncate text-sm text-[#4d5c56]">{{ $notification->actor }} · {{ $notification->detail }}</span>
                                    <time class="mt-1 block text-xs text-[#738078]" datetime="{{ $occurredAt->toIso8601String() }}">{{ $occurredAt->format('M j, Y g:i A') }}</time>
                                </span>
                                <span class="shrink-0 rounded-full bg-[#f8f1df] px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide text-[#8b6c23]">Account security</span>
                            </div>
                        </article>
                    @endif
                @endforeach
            </div>
        </section>

        <div class="mt-5">
            {{ $notifications->links() }}
        </div>
    @else
        <section class="mt-6 max-w-3xl rounded-[4px] border border-[#e4e3d8] bg-white px-5 py-10 text-center text-sm text-[#738078]">
            No activity has been recorded yet.
        </section>
    @endif
@endsection
