@extends('admin.layout')

@section('content')
    <div class="mb-6 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
        <div>
            <p class="text-xs font-bold uppercase tracking-[0.22em] text-[#a48335]">Admin</p>
            <h1 class="mt-2 text-3xl font-extrabold text-[#145943]">Deleted Products</h1>
        </div>

        <a href="{{ route('admin.products.index') }}" class="rounded-xl border border-[#dfe3cf] bg-white px-4 py-2.5 text-sm font-bold text-[#145943] transition hover:bg-[#f7f4ee]">
            Back to All Products
        </a>
    </div>

    @if (session('success'))
        <div class="mb-6 rounded-2xl border border-[#cfe7d6] bg-[#edf8f1] px-4 py-3 text-sm font-medium text-[#145943]">
            {{ session('success') }}
        </div>
    @endif

    <form method="GET" action="{{ route('admin.products.trashed') }}" data-admin-search data-suggestions-url="{{ route('admin.products.suggestions', ['trashed' => true]) }}" class="mb-5 flex flex-col gap-3 sm:flex-row sm:flex-wrap">
        <div class="relative min-w-0 flex-1">
            <label for="trashed-product-search" class="sr-only">Search deleted products</label>
            <input id="trashed-product-search" data-admin-search-input type="search" name="search" value="{{ request('search') }}" placeholder="Search deleted products" maxlength="255" autocomplete="off" aria-autocomplete="list" aria-controls="trashed-product-search-suggestions" aria-expanded="false" class="w-full rounded-lg border border-[#dfe3cf] bg-white px-3 py-2.5 text-sm text-[#173b32] focus:border-[#145943] focus:outline-none">
            <div id="trashed-product-search-suggestions" data-admin-search-results role="listbox" aria-label="Deleted product suggestions" class="absolute inset-x-0 top-full z-20 mt-1 hidden max-h-72 overflow-y-auto rounded-lg border border-[#dfe3cf] bg-white py-1 shadow-lg"></div>
        </div>
        <label for="trashed-product-status" class="sr-only">Filter deleted product status</label>
        <div class="relative min-w-0 flex-1 basis-full sm:basis-40">
            <input id="trashed-product-status" data-admin-filter-input data-filter-options="status" type="search" value="{{ request('status') === 'active' ? 'Active' : (request('status') === 'inactive' ? 'Inactive' : '') }}" placeholder="Filter status" autocomplete="off" aria-autocomplete="list" aria-controls="trashed-product-status-suggestions" aria-expanded="false" class="w-full rounded-lg border border-[#dfe3cf] bg-white px-3 py-2.5 text-sm text-[#173b32] focus:border-[#145943] focus:outline-none">
            <input data-filter-value type="hidden" name="status" value="{{ request('status') }}">
            <div id="trashed-product-status-suggestions" data-admin-filter-results role="listbox" aria-label="Product status options" class="absolute inset-x-0 top-full z-20 mt-1 hidden max-h-72 overflow-y-auto rounded-lg border border-[#dfe3cf] bg-white py-1 shadow-lg"></div>
        </div>
        <label for="trashed-product-category" class="sr-only">Filter by category</label>
        <div class="relative min-w-0 flex-1 basis-full sm:basis-48">
            <input id="trashed-product-category" data-admin-filter-input data-filter-suggestions-url="{{ route('admin.categories.suggestions', ['trashed_products' => true]) }}" type="search" value="{{ $selectedCategory?->name }}" placeholder="Search category" autocomplete="off" aria-autocomplete="list" aria-controls="trashed-product-category-suggestions" aria-expanded="false" class="w-full rounded-lg border border-[#dfe3cf] bg-white px-3 py-2.5 text-sm text-[#173b32] focus:border-[#145943] focus:outline-none">
            <input data-filter-value type="hidden" name="category_id" value="{{ $selectedCategory?->id }}">
            <div id="trashed-product-category-suggestions" data-admin-filter-results role="listbox" aria-label="Category filter suggestions" class="absolute inset-x-0 top-full z-20 mt-1 hidden max-h-72 overflow-y-auto rounded-lg border border-[#dfe3cf] bg-white py-1 shadow-lg"></div>
        </div>
        <label for="trashed-product-type" class="sr-only">Filter by product type</label>
        <div class="relative min-w-0 flex-1 basis-full sm:basis-40">
            <input id="trashed-product-type" data-admin-filter-input data-filter-options="type" type="search" value="{{ request('type') === 'simple' ? 'Simple' : (request('type') === 'variable' ? 'Variable' : '') }}" placeholder="Filter type" autocomplete="off" aria-autocomplete="list" aria-controls="trashed-product-type-suggestions" aria-expanded="false" class="w-full rounded-lg border border-[#dfe3cf] bg-white px-3 py-2.5 text-sm text-[#173b32] focus:border-[#145943] focus:outline-none">
            <input data-filter-value type="hidden" name="type" value="{{ request('type') }}">
            <div id="trashed-product-type-suggestions" data-admin-filter-results role="listbox" aria-label="Product type options" class="absolute inset-x-0 top-full z-20 mt-1 hidden max-h-72 overflow-y-auto rounded-lg border border-[#dfe3cf] bg-white py-1 shadow-lg"></div>
        </div>
        <button type="submit" class="rounded-lg bg-[#145943] px-4 py-2.5 text-sm font-bold text-white transition hover:bg-[#a48335]">Filter</button>
        @if (request()->hasAny(['search', 'status', 'category_id', 'type']))
            <a href="{{ route('admin.products.trashed') }}" class="rounded-lg border border-[#dfe3cf] bg-white px-4 py-2.5 text-center text-sm font-bold text-[#145943] transition hover:bg-[#f7f4ee]">Clear</a>
        @endif
    </form>

    <div class="overflow-hidden rounded-[20px] border border-[#e5e2d9] bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm text-[#4d5c56]">
                <thead class="bg-[#f7f4ee] text-[#145943]">
                    <tr>
                        <th class="px-4 py-3 font-bold">Product</th>
                        <th class="px-4 py-3 font-bold">Category</th>
                        <th class="px-4 py-3 font-bold">Deleted At</th>
                        <th class="px-4 py-3 font-bold">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($products as $product)
                        <tr class="border-t border-[#e5e2d9] align-top">
                            <td class="px-4 py-4">
                                @php
                                    $primaryImage = $product->primaryImage ?? $product->images->firstWhere('is_primary', true) ?? $product->images->first();
                                @endphp
                                <div class="flex items-center gap-3">
                                    @if ($primaryImage)
                                        <img
                                            src="/storage/{{ ltrim($primaryImage->image_path, '/') }}"
                                            alt="{{ $primaryImage->alt_text ?: $product->name }}"
                                            class="h-11 w-11 shrink-0 rounded-xl object-cover border border-[#dfe3cf] bg-[#f4f5e9]"
                                            loading="lazy"
                                        >
                                    @else
                                        <div class="grid h-11 w-11 shrink-0 place-items-center rounded-xl bg-[#edf2e4] text-xs font-extrabold text-[#145943]">
                                            {{ strtoupper(substr($product->name, 0, 1)) }}
                                        </div>
                                    @endif
                                    <div>
                                        <p class="font-bold text-[#173b32]">{{ $product->name }}</p>
                                        <p class="text-xs text-[#738078]">{{ $product->sku ?? 'No SKU' }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-4">
                                {{ $product->category?->name ?? 'Uncategorized' }}
                            </td>
                            <td class="px-4 py-4">
                                {{ $product->deleted_at?->format('M d, Y h:i A') ?? '—' }}
                            </td>
                            <td class="px-4 py-4">
                                <form method="POST" action="{{ route('admin.products.restore', $product) }}" onsubmit="return confirm('Restore this product?')">
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
                                No deleted products found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-5">
        {{ $products->links() }}
    </div>
@endsection
