@extends('admin.layout')

@section('content')
    <div class="mb-6 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
        <div>
            <p class="text-xs font-bold uppercase tracking-[0.22em] text-[#a48335]">Admin</p>
            <h1 class="mt-2 text-3xl font-extrabold text-[#145943]">Update Category</h1>
        </div>

        <a href="{{ route('admin.categories.index') }}" class="rounded-xl border border-[#dfe3cf] bg-white px-4 py-2.5 text-sm font-bold text-[#145943] transition hover:bg-[#f7f4ee]">
            Back to Categories
        </a>
    </div>

    <div class="rounded-[22px] border border-[#e5e2d9] bg-white p-5 shadow-sm">
        <form action="{{ route('admin.categories.update', $category) }}" method="POST" class="space-y-6">
            @csrf
            @method('PUT')

            <div class="grid gap-5 md:grid-cols-2">
                <div>
                    <label for="name" class="mb-2 block text-sm font-bold text-[#173b32]">Category Name</label>
                    <input type="text" name="name" id="name" value="{{ old('name', $category->name) }}" class="w-full rounded-xl border border-[#dfe3cf] bg-[#fafaf5] px-3 py-2.5 text-sm text-[#173b32] focus:border-[#145943] focus:outline-none" required>
                    @error('name')
                        <p class="mt-2 text-xs font-medium text-[#b3261e]">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="slug" class="mb-2 block text-sm font-bold text-[#173b32]">Slug</label>
                    <input type="text" name="slug" id="slug" value="{{ old('slug', $category->slug) }}" class="w-full rounded-xl border border-[#dfe3cf] bg-[#fafaf5] px-3 py-2.5 text-sm text-[#173b32] focus:border-[#145943] focus:outline-none">
                    @error('slug')
                        <p class="mt-2 text-xs font-medium text-[#b3261e]">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="parent_id" class="mb-2 block text-sm font-bold text-[#173b32]">Parent Category</label>
                    <select name="parent_id" id="parent_id" class="w-full rounded-xl border border-[#dfe3cf] bg-[#fafaf5] px-3 py-2.5 text-sm text-[#173b32] focus:border-[#145943] focus:outline-none">
                        <option value="">Root Category</option>
                        @foreach ($categories as $parentCategory)
                            <option value="{{ $parentCategory->id }}" {{ old('parent_id', $category->parent_id) == $parentCategory->id ? 'selected' : '' }}>{{ $parentCategory->name }}</option>
                        @endforeach
                    </select>
                    @error('parent_id')
                        <p class="mt-2 text-xs font-medium text-[#b3261e]">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="sort_order" class="mb-2 block text-sm font-bold text-[#173b32]">Sort Order</label>
                    <input type="number" name="sort_order" id="sort_order" value="{{ old('sort_order', $category->sort_order ?? 0) }}" min="0" class="w-full rounded-xl border border-[#dfe3cf] bg-[#fafaf5] px-3 py-2.5 text-sm text-[#173b32] focus:border-[#145943] focus:outline-none">
                    @error('sort_order')
                        <p class="mt-2 text-xs font-medium text-[#b3261e]">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div>
                <label for="description" class="mb-2 block text-sm font-bold text-[#173b32]">Description</label>
                <textarea name="description" id="description" rows="6" class="w-full rounded-xl border border-[#dfe3cf] bg-[#fafaf5] px-3 py-2.5 text-sm text-[#173b32] focus:border-[#145943] focus:outline-none">{{ old('description', $category->description) }}</textarea>
                @error('description')
                    <p class="mt-2 text-xs font-medium text-[#b3261e]">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="image" class="mb-2 block text-sm font-bold text-[#173b32]">Image Path</label>
                <input type="text" name="image" id="image" value="{{ old('image', $category->image) }}" class="w-full rounded-xl border border-[#dfe3cf] bg-[#fafaf5] px-3 py-2.5 text-sm text-[#173b32] focus:border-[#145943] focus:outline-none">
                @error('image')
                    <p class="mt-2 text-xs font-medium text-[#b3261e]">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex items-center gap-3">
                <input type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', $category->is_active) ? 'checked' : '' }} class="h-4 w-4 rounded border-[#dfe3cf] text-[#145943] focus:ring-[#145943]">
                <label for="is_active" class="text-sm font-bold text-[#173b32]">Active category</label>
            </div>

            <div class="flex items-center justify-end gap-3">
                <a href="{{ route('admin.categories.index') }}" class="rounded-xl border border-[#dfe3cf] bg-white px-4 py-2.5 text-sm font-bold text-[#145943] transition hover:bg-[#f7f4ee]">
                    Cancel
                </a>
                <button type="submit" class="rounded-xl bg-[#145943] px-5 py-2.5 text-sm font-bold text-white transition hover:bg-[#a48335]">
                    Update Category
                </button>
            </div>
        </form>
    </div>
@endsection
