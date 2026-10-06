@extends('admin.layout')

@section('title', 'Customer Reviews | Admin Dashboard')

@section('content')
    <header class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-xs font-bold uppercase tracking-[0.18em] text-[#a48335]">Moderation queue</p>
            <h1 class="mt-2 text-3xl font-extrabold text-[#145943]">Customer Reviews</h1>
            <p class="mt-2 text-sm text-[#738078]">Read the full review and photos before publishing it.</p>
        </div>
    </header>

    <nav class="flex flex-wrap gap-2 border-b border-[#e5e2d9] pb-3" aria-label="Review status">
        @foreach (['pending' => 'Pending', 'published' => 'Published', 'rejected' => 'Rejected', 'all' => 'All'] as $key => $label)
            <a href="{{ route('admin.reviews.index', ['status' => $key]) }}" class="rounded-[4px] px-3 py-2 text-sm font-bold {{ $status === $key ? 'bg-[#145943] text-white' : 'border border-[#dfe3cf] bg-white text-[#4d5c56] hover:bg-[#f7f4ee]' }}">
                {{ $label }}{{ isset($counts[$key]) ? ' · '.$counts[$key] : '' }}
            </a>
        @endforeach
    </nav>

    <section class="divide-y divide-[#ece8dc] overflow-hidden rounded-[4px] border border-[#e4e3d8] bg-white">
        @forelse ($reviews as $review)
            <a href="{{ route('admin.reviews.show', $review) }}" class="block p-4 transition hover:bg-[#f8f9f2] sm:p-5">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <h2 class="font-bold text-[#173b32]">{{ $review->product->name }}</h2>
                            <span class="rounded-[3px] px-2 py-0.5 text-[10px] font-bold uppercase {{ $review->rejected_at ? 'bg-[#fcebe7] text-[#a13e2d]' : ($review->is_approved ? 'bg-[#edf8f1] text-[#145943]' : 'bg-[#fef0d8] text-[#a15b1d]') }}">
                                {{ $review->rejected_at ? 'Rejected' : ($review->is_approved ? 'Published' : 'Pending') }}
                            </span>
                        </div>
                        <p class="mt-1 text-sm text-[#4d5c56]">{{ $review->name }} · {{ $review->rating }}/5 · {{ $review->created_at->format('M j, Y g:i A') }}</p>
                        <p class="mt-3 line-clamp-2 whitespace-pre-line text-sm leading-6 text-[#59675f]">{{ $review->body }}</p>
                    </div>
                    <span class="shrink-0 text-sm font-bold text-[#145943]">Read review</span>
                </div>
            </a>
        @empty
            <p class="px-5 py-12 text-center text-sm text-[#738078]">No {{ $status === 'all' ? '' : $status }} reviews found.</p>
        @endforelse
    </section>

    {{ $reviews->links() }}
@endsection