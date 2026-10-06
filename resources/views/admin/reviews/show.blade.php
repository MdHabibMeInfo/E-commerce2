@extends('admin.layout')

@section('title', 'Review Details | Admin Dashboard')

@section('content')
    <div class="flex flex-wrap items-center justify-between gap-3">
        <a href="{{ route('admin.reviews.index') }}" class="text-sm font-bold text-[#145943] hover:underline">Back to reviews</a>
        <span class="rounded-[3px] px-2.5 py-1 text-xs font-bold uppercase {{ $review->rejected_at ? 'bg-[#fcebe7] text-[#a13e2d]' : ($review->is_approved ? 'bg-[#edf8f1] text-[#145943]' : 'bg-[#fef0d8] text-[#a15b1d]') }}">
            {{ $review->rejected_at ? 'Rejected' : ($review->is_approved ? 'Published' : 'Pending decision') }}
        </span>
    </div>

    <article class="overflow-hidden rounded-[4px] border border-[#e5e2d9] bg-white">
        <header class="border-b border-[#e5e2d9] px-5 py-5 sm:px-7">
            <p class="text-xs font-bold uppercase tracking-[0.16em] text-[#a48335]">Review details</p>
            <h1 class="mt-2 text-2xl font-extrabold text-[#145943]">{{ $review->product->name }}</h1>
            <a href="{{ route('products.show', $review->product->slug) }}#customer-reviews" target="_blank" rel="noopener" class="mt-1 inline-block text-sm font-semibold text-[#59675f] underline">View product page</a>
        </header>

        <div class="grid gap-6 px-5 py-6 sm:px-7 lg:grid-cols-[minmax(0,1fr)_280px]">
            <div>
                <div class="flex flex-wrap items-center gap-x-4 gap-y-2 text-sm text-[#4d5c56]">
                    <span class="font-bold text-[#173b32]">{{ $review->name }}</span>
                    <a href="mailto:{{ $review->email }}" class="underline">{{ $review->email }}</a>
                    <span>{{ $review->created_at->format('M j, Y g:i A') }}</span>
                </div>
                <p class="mt-5 text-lg font-bold text-[#a48335]">Rating {{ $review->rating }} / 5</p>
                <div class="mt-4 whitespace-pre-line text-base leading-7 text-[#35443d]">{{ $review->body }}</div>

                @if ($review->images)
                    <div class="mt-6">
                        <h2 class="text-sm font-bold text-[#173b32]">Customer photos</h2>
                        <div class="mt-3 grid grid-cols-2 gap-3 sm:grid-cols-3">
                            @foreach ($review->images as $image)
                                <a href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($image) }}" target="_blank" rel="noopener" class="block overflow-hidden rounded-[4px] border border-[#e5e2d9]">
                                    <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($image) }}" alt="Photo submitted by {{ $review->name }}" class="aspect-square w-full object-cover">
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            <aside class="h-fit border-t border-[#e5e2d9] pt-5 lg:border-l lg:border-t-0 lg:pl-6 lg:pt-0">
                <h2 class="font-extrabold text-[#173b32]">Moderation decision</h2>
                <p class="mt-2 text-sm leading-6 text-[#59675f]">Publishing makes this review visible on the product page. Rejecting keeps it off the storefront.</p>
                @if (! $review->is_approved && $review->rejected_at === null)
                    @can('reviews.moderate')
                        <div class="mt-5 grid gap-2">
                            <form method="POST" action="{{ route('admin.reviews.update', $review) }}">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="decision" value="publish">
                                <button type="submit" class="w-full rounded-[4px] bg-[#145943] px-4 py-3 text-sm font-bold text-white transition hover:bg-[#0f4936]">Publish review</button>
                            </form>
                            <form method="POST" action="{{ route('admin.reviews.update', $review) }}" onsubmit="return confirm('Reject this review? It will remain available in the rejected list and will not appear on the store.')">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="decision" value="reject">
                                <button type="submit" class="w-full rounded-[4px] border border-[#a13e2d] px-4 py-3 text-sm font-bold text-[#a13e2d] transition hover:bg-[#fcebe7]">Reject review</button>
                            </form>
                        </div>
                    @endcan
                @endif
            </aside>
        </div>
    </article>
@endsection