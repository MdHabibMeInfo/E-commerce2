@extends('admin.layout')

@section('content')
    <div class="mb-6 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
        <div>
            <p class="text-xs font-bold uppercase tracking-[0.22em] text-[#a48335]">Admin</p>
            <h1 class="mt-2 text-3xl font-extrabold text-[#145943]">Create Product</h1>
        </div>

        <a href="{{ route('admin.products.index') }}" class="rounded-xl border border-[#dfe3cf] bg-white px-4 py-2.5 text-sm font-bold text-[#145943] transition hover:bg-[#f7f4ee]">
            Back to Products
        </a>
    </div>

    <div class="rounded-[22px] border border-[#e5e2d9] bg-white p-5 shadow-sm">
        <form action="{{ route('admin.products.store') }}" method="POST" class="space-y-6">
            @csrf

            <div class="grid gap-5 md:grid-cols-2">
                <div>
                    <label for="category_id" class="mb-2 block text-sm font-bold text-[#173b32]">Category</label>
                    <select name="category_id" id="category_id" class="w-full rounded-xl border border-[#dfe3cf] bg-[#fafaf5] px-3 py-2.5 text-sm text-[#173b32] focus:border-[#145943] focus:outline-none">
                        <option value="">Select category</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                        @endforeach
                    </select>
                    @error('category_id')
                        <p class="mt-2 text-xs font-medium text-[#b3261e]">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="name" class="mb-2 block text-sm font-bold text-[#173b32]">Product Name</label>
                    <input type="text" name="name" id="name" value="{{ old('name') }}" class="w-full rounded-xl border border-[#dfe3cf] bg-[#fafaf5] px-3 py-2.5 text-sm text-[#173b32] focus:border-[#145943] focus:outline-none" required>
                    @error('name')
                        <p class="mt-2 text-xs font-medium text-[#b3261e]">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="slug" class="mb-2 block text-sm font-bold text-[#173b32]">Slug</label>
                    <input type="text" name="slug" id="slug" value="{{ old('slug') }}" class="w-full rounded-xl border border-[#dfe3cf] bg-[#fafaf5] px-3 py-2.5 text-sm text-[#173b32] focus:border-[#145943] focus:outline-none">
                    @error('slug')
                        <p class="mt-2 text-xs font-medium text-[#b3261e]">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="sku" class="mb-2 block text-sm font-bold text-[#173b32]">SKU</label>
                    <input type="text" name="sku" id="sku" value="{{ old('sku') }}" class="w-full rounded-xl border border-[#dfe3cf] bg-[#fafaf5] px-3 py-2.5 text-sm text-[#173b32] focus:border-[#145943] focus:outline-none">
                    @error('sku')
                        <p class="mt-2 text-xs font-medium text-[#b3261e]">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="type" class="mb-2 block text-sm font-bold text-[#173b32]">Product Type</label>
                    <select name="type" id="type" class="w-full rounded-xl border border-[#dfe3cf] bg-[#fafaf5] px-3 py-2.5 text-sm text-[#173b32] focus:border-[#145943] focus:outline-none" required>
                        <option value="simple" {{ old('type', 'simple') === 'simple' ? 'selected' : '' }}>Simple</option>
                        <option value="variable" {{ old('type') === 'variable' ? 'selected' : '' }}>Variable</option>
                    </select>
                    @error('type')
                        <p class="mt-2 text-xs font-medium text-[#b3261e]">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="stock" class="mb-2 block text-sm font-bold text-[#173b32]">Stock</label>
                    <input type="number" name="stock" id="stock" value="{{ old('stock') }}" min="0" class="w-full rounded-xl border border-[#dfe3cf] bg-[#fafaf5] px-3 py-2.5 text-sm text-[#173b32] focus:border-[#145943] focus:outline-none">
                    @error('stock')
                        <p class="mt-2 text-xs font-medium text-[#b3261e]">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="price" class="mb-2 block text-sm font-bold text-[#173b32]">Price</label>
                    <input type="number" name="price" id="price" value="{{ old('price') }}" min="0" step="0.01" class="w-full rounded-xl border border-[#dfe3cf] bg-[#fafaf5] px-3 py-2.5 text-sm text-[#173b32] focus:border-[#145943] focus:outline-none">
                    @error('price')
                        <p class="mt-2 text-xs font-medium text-[#b3261e]">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="compare_at_price" class="mb-2 block text-sm font-bold text-[#173b32]">Compare At Price</label>
                    <input type="number" name="compare_at_price" id="compare_at_price" value="{{ old('compare_at_price') }}" min="0" step="0.01" class="w-full rounded-xl border border-[#dfe3cf] bg-[#fafaf5] px-3 py-2.5 text-sm text-[#173b32] focus:border-[#145943] focus:outline-none">
                    @error('compare_at_price')
                        <p class="mt-2 text-xs font-medium text-[#b3261e]">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div>
                <label for="short_description" class="mb-2 block text-sm font-bold text-[#173b32]">Short Description</label>
                <textarea name="short_description" id="short_description" rows="3" class="w-full rounded-xl border border-[#dfe3cf] bg-[#fafaf5] px-3 py-2.5 text-sm text-[#173b32] focus:border-[#145943] focus:outline-none">{{ old('short_description') }}</textarea>
                @error('short_description')
                    <p class="mt-2 text-xs font-medium text-[#b3261e]">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="description" class="mb-2 block text-sm font-bold text-[#173b32]">Description</label>
                <textarea name="description" id="description" rows="6" class="w-full rounded-xl border border-[#dfe3cf] bg-[#fafaf5] px-3 py-2.5 text-sm text-[#173b32] focus:border-[#145943] focus:outline-none">{{ old('description') }}</textarea>
                @error('description')
                    <p class="mt-2 text-xs font-medium text-[#b3261e]">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex items-center gap-3">
                <input type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }} class="h-4 w-4 rounded border-[#dfe3cf] text-[#145943] focus:ring-[#145943]">
                <label for="is_active" class="text-sm font-bold text-[#173b32]">Active product</label>
            </div>

            <div class="flex items-center justify-end gap-3">
                <a href="{{ route('admin.products.index') }}" class="rounded-xl border border-[#dfe3cf] bg-white px-4 py-2.5 text-sm font-bold text-[#145943] transition hover:bg-[#f7f4ee]">
                    Cancel
                </a>
                <button type="submit" class="rounded-xl bg-[#145943] px-5 py-2.5 text-sm font-bold text-white transition hover:bg-[#a48335]">
                    Save Product
                </button>
            </div>
        </form>
    </div>
@endsection
