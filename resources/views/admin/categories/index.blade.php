@extends('admin.layout')

@section('content')
    <div class="mb-6 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
        <div>
            <p class="text-xs font-bold uppercase tracking-[0.22em] text-[#a48335]">Admin</p>
            <h1 class="mt-2 text-3xl font-extrabold text-[#145943]">Category Management</h1>
        </div>
        <div class="flex flex-wrap items-center gap-3">
            <a href="{{ route('admin.categories.create') }}" class="rounded-xl bg-[#145943] px-4 py-2.5 text-sm font-bold text-white transition hover:bg-[#a48335]">
                Create Category
            </a>
            <a href="{{ route('admin.categories.trashed') }}" class="rounded-xl border border-[#dfe3cf] bg-white px-4 py-2.5 text-sm font-bold text-[#145943] transition hover:bg-[#f7f4ee]">
                Deleted Categories
            </a>
        </div>
    </div>

    @if (session('success'))
        <div class="mb-6 rounded-2xl border border-[#cfe7d6] bg-[#edf8f1] px-4 py-3 text-sm font-medium text-[#145943]">
            {{ session('success') }}
        </div>
    @endif

    @error('category')
        <div role="alert" class="mb-6 rounded-xl border border-[#f1c9c4] bg-[#fff1ef] px-4 py-3 text-sm font-medium text-[#9f2720]">
            {{ $message }}
        </div>
    @enderror

    <form method="GET" action="{{ route('admin.categories.index') }}" data-admin-search data-suggestions-url="{{ route('admin.categories.suggestions') }}" class="mb-5 flex flex-col gap-3 sm:flex-row sm:flex-wrap">
        <div class="relative min-w-0 flex-1">
            <label for="category-search" class="sr-only">Search categories</label>
            <input id="category-search" data-admin-search-input type="search" name="search" value="{{ request('search') }}" placeholder="Search name or slug" maxlength="100" autocomplete="off" aria-autocomplete="list" aria-controls="category-search-suggestions" aria-expanded="false" class="w-full rounded-lg border border-[#dfe3cf] bg-white px-3 py-2.5 text-sm text-[#173b32] focus:border-[#145943] focus:outline-none">
            <div id="category-search-suggestions" data-admin-search-results role="listbox" aria-label="Category suggestions" class="absolute inset-x-0 top-full z-20 mt-1 hidden max-h-72 overflow-y-auto rounded-lg border border-[#dfe3cf] bg-white py-1 shadow-lg"></div>
        </div>
        <label for="category-parent-filter" class="sr-only">Filter by parent category</label>
        <div class="relative min-w-0 flex-1 basis-full sm:basis-48">
            <input id="category-parent-filter" data-admin-filter-input data-filter-suggestions-url="{{ route('admin.categories.suggestions', ['parents_only' => true]) }}" data-filter-root-option type="search" value="{{ request('parent_id') === 'root' ? 'Root categories' : $selectedParentCategory?->name }}" placeholder="Search parent category" autocomplete="off" aria-autocomplete="list" aria-controls="category-parent-suggestions" aria-expanded="false" class="w-full rounded-lg border border-[#dfe3cf] bg-white px-3 py-2.5 text-sm text-[#173b32] focus:border-[#145943] focus:outline-none">
            <input data-filter-value type="hidden" name="parent_id" value="{{ request('parent_id') }}">
            <div id="category-parent-suggestions" data-admin-filter-results role="listbox" aria-label="Parent category filter suggestions" class="absolute inset-x-0 top-full z-20 mt-1 hidden max-h-72 overflow-y-auto rounded-lg border border-[#dfe3cf] bg-white py-1 shadow-lg"></div>
        </div>
        <label for="category-status" class="sr-only">Filter category status</label>
        <div class="relative min-w-0 flex-1 basis-full sm:basis-40">
            <input id="category-status" data-admin-filter-input data-filter-options="status" type="search" value="{{ request('status') === 'active' ? 'Active' : (request('status') === 'inactive' ? 'Inactive' : '') }}" placeholder="Filter status" autocomplete="off" aria-autocomplete="list" aria-controls="category-status-suggestions" aria-expanded="false" class="w-full rounded-lg border border-[#dfe3cf] bg-white px-3 py-2.5 text-sm text-[#173b32] focus:border-[#145943] focus:outline-none">
            <input data-filter-value type="hidden" name="status" value="{{ request('status') }}">
            <div id="category-status-suggestions" data-admin-filter-results role="listbox" aria-label="Category status options" class="absolute inset-x-0 top-full z-20 mt-1 hidden max-h-72 overflow-y-auto rounded-lg border border-[#dfe3cf] bg-white py-1 shadow-lg"></div>
        </div>
        <button type="submit" class="rounded-lg bg-[#145943] px-4 py-2.5 text-sm font-bold text-white transition hover:bg-[#a48335]">Filter</button>
        @if (request()->hasAny(['search', 'status', 'parent_id']))
            <a href="{{ route('admin.categories.index') }}" class="rounded-lg border border-[#dfe3cf] bg-white px-4 py-2.5 text-center text-sm font-bold text-[#145943] transition hover:bg-[#f7f4ee]">Clear</a>
        @endif
    </form>

    <div class="overflow-hidden rounded-[20px] border border-[#e5e2d9] bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm text-[#4d5c56]">
                <thead class="bg-[#f7f4ee] text-[#145943]">
                    <tr>
                        <th class="px-4 py-3 font-bold">Category</th>
                        <th class="px-4 py-3 font-bold">Parent</th>
                        <th class="px-4 py-3 font-bold">Status</th>
                        <th class="px-4 py-3 font-bold">Actions</th>
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
                                        <a href="{{ route('admin.categories.show', $category) }}" class="font-bold text-[#173b32] hover:text-[#145943]">
                                            {{ $category->name }}
                                        </a>
                                        <p class="text-xs text-[#738078]">{{ $category->slug }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-4">
                                {{ $category->parent?->name ?? 'Root category' }}
                            </td>
                            <td class="px-4 py-4">
                                @if ($category->trashed())
                                    <span class="rounded-full bg-[#fef0d8] px-2.5 py-1 text-[10px] font-bold uppercase tracking-[0.14em] text-[#a15b1d]">Deleted</span>
                                @elseif ($category->is_active)
                                    <span class="rounded-full bg-[#edf8f1] px-2.5 py-1 text-[10px] font-bold uppercase tracking-[0.14em] text-[#145943]">Active</span>
                                @else
                                    <span class="rounded-full bg-[#f4f1ea] px-2.5 py-1 text-[10px] font-bold uppercase tracking-[0.14em] text-[#4d5c56]">Inactive</span>
                                @endif
                            </td>
                            <td class="px-4 py-4">
                                <div class="flex flex-wrap items-center gap-2">
                                    <a href="{{ route('admin.categories.show', $category) }}" class="rounded-lg border border-[#dfe3cf] bg-white px-3 py-1.5 text-xs font-bold text-[#145943] transition hover:bg-[#f7f4ee]">
                                        View
                                    </a>
                                    <a href="{{ route('admin.categories.edit', $category) }}" class="rounded-lg border border-[#dfe3cf] bg-white px-3 py-1.5 text-xs font-bold text-[#145943] transition hover:bg-[#f7f4ee]">
                                        Edit
                                    </a>
                                    @if ($category->trashed())
                                        <form method="POST" action="{{ route('admin.categories.restore', $category) }}" onsubmit="return confirm('Restore this category?')">
                                            @csrf
                                            <button type="submit" class="rounded-lg bg-[#145943] px-3 py-1.5 text-xs font-bold text-white transition hover:bg-[#a48335]">
                                                Restore
                                            </button>
                                        </form>
                                    @else
                                        <form method="POST" action="{{ route('admin.categories.destroy', $category) }}" onsubmit="return confirm('Delete this category?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="rounded-lg bg-[#a15b1d] px-3 py-1.5 text-xs font-bold text-white transition hover:bg-[#8e4d1c]">
                                                Delete
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-10 text-center text-[#738078]">
                                No categories found.
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
