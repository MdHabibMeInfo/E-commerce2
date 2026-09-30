@extends('admin.layout')

@section('content')
    <div class="mb-6 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
        <div>
            <p class="text-xs font-bold uppercase tracking-[0.22em] text-[#a48335]">Admin</p>
            <h1 class="mt-2 text-3xl font-extrabold text-[#145943]">{{ $category->name }}</h1>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <a href="{{ route('admin.categories.edit', $category) }}" class="rounded-xl border border-[#dfe3cf] bg-white px-4 py-2.5 text-sm font-bold text-[#145943] transition hover:bg-[#f7f4ee]">
                Edit Category
            </a>
            <a href="{{ route('admin.categories.index') }}" class="rounded-xl border border-[#dfe3cf] bg-white px-4 py-2.5 text-sm font-bold text-[#145943] transition hover:bg-[#f7f4ee]">
                Back to Categories
            </a>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-[1.3fr_0.7fr]">
        <div class="rounded-[22px] border border-[#e5e2d9] bg-white p-5 shadow-sm">
            @error('category')
                <div role="alert" class="mb-5 rounded-xl border border-[#f1c9c4] bg-[#fff1ef] px-4 py-3 text-sm font-medium text-[#9f2720]">
                    {{ $message }}
                </div>
            @enderror
            @if ($category->image_url)
                <img src="{{ $category->image_url }}" alt="{{ $category->name }}" class="mb-6 h-40 w-40 rounded-xl border border-[#e5e2d9] object-cover">
            @endif
            <div class="grid gap-5 md:grid-cols-2">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.16em] text-[#a48335]">Slug</p>
                    <p class="mt-2 text-base font-bold text-[#173b32]">{{ $category->slug }}</p>
                </div>
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.16em] text-[#a48335]">Parent</p>
                    <p class="mt-2 text-base font-bold text-[#173b32]">{{ $category->parent?->name ?? 'Root category' }}</p>
                </div>
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.16em] text-[#a48335]">Sort Order</p>
                    <p class="mt-2 text-base font-bold text-[#173b32]">{{ $category->sort_order ?? 0 }}</p>
                </div>
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.16em] text-[#a48335]">Status</p>
                    <p class="mt-2 text-base font-bold text-[#173b32]">{{ $category->is_active ? 'Active' : 'Inactive' }}</p>
                </div>
            </div>

            <div class="mt-6">
                <p class="text-xs font-bold uppercase tracking-[0.16em] text-[#a48335]">Description</p>
                <p class="mt-2 text-[15px] leading-7 text-[#4d5c56]">
                    {{ $category->description ?: 'No description provided.' }}
                </p>
            </div>
        </div>

        <div class="rounded-[22px] border border-[#e5e2d9] bg-white p-5 shadow-sm">
            <p class="text-xs font-bold uppercase tracking-[0.16em] text-[#a48335]">Quick Actions</p>
            <div class="mt-4 space-y-3">
                <a href="{{ route('admin.categories.edit', $category) }}" class="block rounded-xl bg-[#145943] px-4 py-3 text-center text-sm font-bold text-white transition hover:bg-[#a48335]">
                    Edit Category
                </a>

                <form method="POST" action="{{ route('admin.categories.destroy', $category) }}" onsubmit="return confirm('Delete this category?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="w-full rounded-xl bg-[#a15b1d] px-4 py-3 text-sm font-bold text-white transition hover:bg-[#8e4d1c]">
                        Delete Category
                    </button>
                </form>
            </div>

            @if ($category->children->isNotEmpty())
                <div class="mt-6">
                    <p class="text-xs font-bold uppercase tracking-[0.16em] text-[#a48335]">Child Categories</p>
                    <ul class="mt-3 space-y-2 text-sm text-[#4d5c56]">
                        @foreach ($category->children as $child)
                            <li class="rounded-lg bg-[#f7f4ee] px-3 py-2">{{ $child->name }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    </div>
@endsection
