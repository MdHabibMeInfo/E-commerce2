@extends('admin.layout')

@section('content')
    <div class="mb-6 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
        <div>
            <p class="text-xs font-bold uppercase tracking-[0.2em] text-[#a48335]">Admin</p>
            <h1 class="mt-2 text-3xl font-extrabold text-[#145943]">{{ $product->name }}</h1>
        </div>
        <div class="flex flex-wrap items-center gap-3">
            <a href="{{ route('admin.products.index') }}" class="rounded-xl border border-[#dfe3cf] bg-white px-4 py-2 text-sm font-bold text-[#145943] transition hover:bg-[#f7f4ee]">
                Back to Products
            </a>
            <a href="{{ route('admin.products.edit', $product) }}" class="rounded-xl bg-[#145943] px-4 py-2 text-sm font-bold text-white transition hover:bg-[#a48335]">
                Edit Product
            </a>
        </div>
    </div>

    @if (session('success'))
        <div class="mb-6 rounded-2xl border border-[#cfe7d6] bg-[#edf8f1] px-4 py-3 text-sm font-medium text-[#145943]">
            {{ session('success') }}
        </div>
    @endif

    <div class="grid gap-6 xl:grid-cols-[1.1fr_0.9fr]">
        <div class="rounded-[22px] border border-[#e5e2d9] bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between gap-4 border-b border-[#efeae0] pb-4">
                <div>
                    <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-[#a48335]">Product</p>
                    <h2 class="mt-2 text-2xl font-extrabold text-[#145943]">Overview</h2>
                </div>
                <span class="rounded-full {{ $product->trashed() ? 'bg-[#fef0d8] text-[#a15b1d]' : 'bg-[#edf8f1] text-[#145943]' }} px-2.5 py-1 text-[10px] font-bold uppercase tracking-[0.14em]">
                    {{ $product->trashed() ? 'Deleted' : 'Active' }}
                </span>
            </div>

            <div class="mt-5 grid gap-4 sm:grid-cols-2">
                <div class="rounded-2xl bg-[#f8f7f2] p-4">
                    <p class="text-xs font-bold uppercase tracking-[0.16em] text-[#a48335]">Category</p>
                    <p class="mt-2 text-base font-bold text-[#173b32]">{{ $product->category?->name ?? 'Uncategorized' }}</p>
                </div>
                <div class="rounded-2xl bg-[#f8f7f2] p-4">
                    <p class="text-xs font-bold uppercase tracking-[0.16em] text-[#a48335]">SKU</p>
                    <p class="mt-2 text-base font-bold text-[#173b32]">{{ $product->sku ?? 'N/A' }}</p>
                </div>
                <div class="rounded-2xl bg-[#f8f7f2] p-4">
                    <p class="text-xs font-bold uppercase tracking-[0.16em] text-[#a48335]">Price</p>
                    <p class="mt-2 text-base font-bold text-[#173b32]">৳{{ number_format((float) ($product->price ?? 0), 2) }}</p>
                </div>
                <div class="rounded-2xl bg-[#f8f7f2] p-4">
                    <p class="text-xs font-bold uppercase tracking-[0.16em] text-[#a48335]">Stock</p>
                    <p class="mt-2 text-base font-bold text-[#173b32]">{{ $product->stock ?? 0 }}</p>
                </div>
            </div>

            <div class="mt-6 rounded-2xl border border-[#e5e2d9] bg-[#fafaf6] p-4">
                <p class="text-xs font-bold uppercase tracking-[0.16em] text-[#a48335]">Short Description</p>
                <p class="mt-2 text-sm leading-6 text-[#4d5c56]">
                    {{ $product->short_description ?: 'No short description provided.' }}
                </p>
            </div>

            <div class="mt-6 rounded-2xl border border-[#e5e2d9] bg-[#fafaf6] p-4">
                <p class="text-xs font-bold uppercase tracking-[0.16em] text-[#a48335]">Description</p>
                <div class="mt-2 text-sm leading-7 text-[#4d5c56]">
                    {!! nl2br(e($product->description ?: 'No detailed description yet.')) !!}
                </div>
            </div>
        </div>

        <div class="space-y-6">
            <div class="rounded-[22px] border border-[#e5e2d9] bg-white p-5 shadow-sm">
                <h2 class="text-xl font-extrabold text-[#145943]">Quick Actions</h2>

                <div class="mt-4 space-y-3">
                    <a href="{{ route('admin.products.edit', $product) }}" class="block rounded-xl bg-[#145943] px-4 py-3 text-center text-sm font-bold text-white transition hover:bg-[#a48335]">
                        Update Product
                    </a>

                    @if ($product->trashed())
                        <form method="POST" action="{{ route('admin.products.restore', $product) }}" onsubmit="return confirm('Restore this product?')">
                            @csrf
                            <button type="submit" class="w-full rounded-xl bg-[#145943] px-4 py-3 text-sm font-bold text-white transition hover:bg-[#a48335]">
                                Restore Product
                            </button>
                        </form>
                    @else
                        <form method="POST" action="{{ route('admin.products.destroy', $product) }}" onsubmit="return confirm('Delete this product?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="w-full rounded-xl bg-[#a15b1d] px-4 py-3 text-sm font-bold text-white transition hover:bg-[#8e4d1c]">
                                Delete Product
                            </button>
                        </form>
                    @endif
                </div>
            </div>

            <div class="rounded-[22px] border border-[#e5e2d9] bg-white p-5 shadow-sm">
                <h2 class="text-xl font-extrabold text-[#145943]">Meta</h2>
                <div class="mt-4 space-y-3 text-sm text-[#4d5c56]">
                    <div class="flex items-center justify-between gap-3 border-b border-[#efeae0] pb-2">
                        <span>Type</span>
                        <span class="font-semibold text-[#173b32]">{{ ucfirst($product->type ?? 'simple') }}</span>
                    </div>
                    <div class="flex items-center justify-between gap-3 border-b border-[#efeae0] pb-2">
                        <span>Compare At Price</span>
                        <span class="font-semibold text-[#173b32]">৳{{ number_format((float) ($product->compare_at_price ?? 0), 2) }}</span>
                    </div>
                    <div class="flex items-center justify-between gap-3 border-b border-[#efeae0] pb-2">
                        <span>Status</span>
                        <span class="font-semibold text-[#173b32]">{{ $product->is_active ? 'Active' : 'Inactive' }}</span>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <span>Slug</span>
                        <span class="font-semibold text-[#173b32]">{{ $product->slug }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
