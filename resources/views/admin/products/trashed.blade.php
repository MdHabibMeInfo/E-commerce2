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
                                <div class="flex items-center gap-3">
                                    <div class="grid h-11 w-11 place-items-center rounded-xl bg-[#edf2e4] text-xs font-extrabold text-[#145943]">
                                        {{ strtoupper(substr($product->name, 0, 1)) }}
                                    </div>
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
@endsection
