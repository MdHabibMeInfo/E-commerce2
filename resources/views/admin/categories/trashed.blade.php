@extends('admin.layout')

@section('content')
    <div class="mb-6 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
        <div>
            <p class="text-xs font-bold uppercase tracking-[0.22em] text-[#a48335]">Admin</p>
            <h1 class="mt-2 text-3xl font-extrabold text-[#145943]">Deleted Categories</h1>
        </div>

        <a href="{{ route('admin.categories.index') }}" class="rounded-xl border border-[#dfe3cf] bg-white px-4 py-2.5 text-sm font-bold text-[#145943] transition hover:bg-[#f7f4ee]">
            Back to Categories
        </a>
    </div>

    <form method="GET" action="{{ route('admin.categories.trashed') }}" data-admin-search data-suggestions-url="{{ route('admin.categories.suggestions', ['trashed' => true]) }}" class="mb-5 flex flex-col gap-3 sm:flex-row sm:flex-wrap">
        <div class="relative min-w-0 flex-1">
            <label for="trashed-category-search" class="sr-only">Search deleted categories</label>
            <input id="trashed-category-search" data-admin-search-input type="search" name="search" value="{{ request('search') }}" placeholder="Search deleted categories" maxlength="100" autocomplete="off" aria-autocomplete="list" aria-controls="trashed-category-search-suggestions" aria-expanded="false" class="w-full rounded-lg border border-[#dfe3cf] bg-white px-3 py-2.5 text-sm text-[#173b32] focus:border-[#145943] focus:outline-none">
            <div id="trashed-category-search-suggestions" data-admin-search-results role="listbox" aria-label="Deleted category suggestions" class="absolute inset-x-0 top-full z-20 mt-1 hidden max-h-72 overflow-y-auto rounded-lg border border-[#dfe3cf] bg-white py-1 shadow-lg"></div>
        </div>
        <label for="trashed-category-parent-filter" class="sr-only">Filter by parent category</label>
        <div class="relative min-w-0 flex-1 basis-full sm:basis-48">
            <input id="trashed-category-parent-filter" data-admin-filter-input data-filter-suggestions-url="{{ route('admin.categories.suggestions', ['trashed' => true]) }}" data-filter-root-option type="search" value="{{ request('parent_id') === 'root' ? 'Root categories' : $selectedParentCategory?->name }}" placeholder="Search parent category" autocomplete="off" aria-autocomplete="list" aria-controls="trashed-category-parent-suggestions" aria-expanded="false" class="w-full rounded-lg border border-[#dfe3cf] bg-white px-3 py-2.5 text-sm text-[#173b32] focus:border-[#145943] focus:outline-none">
            <input data-filter-value type="hidden" name="parent_id" value="{{ request('parent_id') }}">
            <div id="trashed-category-parent-suggestions" data-admin-filter-results role="listbox" aria-label="Parent category filter suggestions" class="absolute inset-x-0 top-full z-20 mt-1 hidden max-h-72 overflow-y-auto rounded-lg border border-[#dfe3cf] bg-white py-1 shadow-lg"></div>
        </div>
        <label for="trashed-category-status" class="sr-only">Filter deleted category status</label>
        <div class="relative min-w-0 flex-1 basis-full sm:basis-40">
            <input id="trashed-category-status" data-admin-filter-input data-filter-options="status" type="search" value="{{ request('status') === 'active' ? 'Active' : (request('status') === 'inactive' ? 'Inactive' : '') }}" placeholder="Filter status" autocomplete="off" aria-autocomplete="list" aria-controls="trashed-category-status-suggestions" aria-expanded="false" class="w-full rounded-lg border border-[#dfe3cf] bg-white px-3 py-2.5 text-sm text-[#173b32] focus:border-[#145943] focus:outline-none">
            <input data-filter-value type="hidden" name="status" value="{{ request('status') }}">
            <div id="trashed-category-status-suggestions" data-admin-filter-results role="listbox" aria-label="Category status options" class="absolute inset-x-0 top-full z-20 mt-1 hidden max-h-72 overflow-y-auto rounded-lg border border-[#dfe3cf] bg-white py-1 shadow-lg"></div>
        </div>
        <button type="submit" class="rounded-lg bg-[#145943] px-4 py-2.5 text-sm font-bold text-white transition hover:bg-[#a48335]">Filter</button>
        @if (request()->hasAny(['search', 'status', 'parent_id']))
            <a href="{{ route('admin.categories.trashed') }}" class="rounded-lg border border-[#dfe3cf] bg-white px-4 py-2.5 text-center text-sm font-bold text-[#145943] transition hover:bg-[#f7f4ee]">Clear</a>
        @endif
    </form>

    <div class="overflow-hidden rounded-[20px] border border-[#e5e2d9] bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm text-[#4d5c56]">
                <thead class="bg-[#f7f4ee] text-[#145943]">
                    <tr>
                        <th class="px-4 py-3 font-bold">Category</th>
                        <th class="px-4 py-3 font-bold">Parent</th>
                        <th class="px-4 py-3 font-bold">Deleted At</th>
                        <th class="px-4 py-3 font-bold">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($categories as $category)
                        <tr class="border-t border-[#e5e2d9] align-top">
                            <td class="px-4 py-4">
                                <div class="flex items-center gap-3">
                                    @if ($category->image_url)
                                        <img src="{{ $category->image_url }}" alt="{{ $category->name }}" loading="lazy" class="h-11 w-11 shrink-0 rounded-xl border border-[#dfe3cf] object-cover">
                                    @else
                                        <div class="grid h-11 w-11 shrink-0 place-items-center rounded-xl bg-[#edf2e4] text-xs font-extrabold text-[#145943]">
                                            {{ strtoupper(substr($category->name, 0, 1)) }}
                                        </div>
                                    @endif
                                    <div>
                                        <span class="font-bold text-[#173b32]">{{ $category->name }}</span>
                                        <p class="text-xs text-[#738078]">{{ $category->slug }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-4">
                                {{ $category->parent?->name ?? 'Root category' }}
                            </td>
                            <td class="px-4 py-4">
                                {{ $category->deleted_at?->format('d M Y, h:i A') ?? '-' }}
                            </td>
                            <td class="px-4 py-4">
                                <form method="POST" action="{{ route('admin.categories.restore', $category) }}" onsubmit="return confirm('Restore this category?')">
                                    @csrf
                                    <button type="submit" class="rounded-lg bg-[#145943] px-3 py-1.5 text-xs font-bold text-white transition hover:bg-[#a48335]">
                                        Restore
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-10 text-center text-[#738078]">
                                No deleted categories found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-5">
        {{ $categories->links() }}
    </div>
@endsection
