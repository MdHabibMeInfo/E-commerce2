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
        <form action="{{ route('admin.categories.update', $category) }}" method="POST" enctype="multipart/form-data" class="space-y-6">
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

            <div class="rounded-2xl border border-[#dfe3cf] bg-[#fafaf5] p-4 sm:p-5">
                <div>
                    <h3 class="text-sm font-bold text-[#173b32]">Current Saved Image</h3>
                    <p class="text-xs text-[#738078]">The image currently used for this category.</p>
                </div>

                <div class="mt-4 flex flex-col items-start gap-4 sm:flex-row sm:items-center">
                    @if ($category->image_url)
                        <img src="{{ $category->image_url }}" alt="{{ $category->name }}" class="h-32 w-32 rounded-xl border border-[#dfe3cf] bg-white object-cover">
                        <div>
                            <label for="remove_image" class="inline-flex items-center gap-2 text-sm font-medium text-[#4d5c56]">
                                <input type="checkbox" name="remove_image" id="remove_image" value="1" class="h-4 w-4 rounded border-[#dfe3cf] text-[#145943] focus:ring-[#145943]">
                                Remove current image
                            </label>
                            @error('remove_image')
                                <p class="mt-2 text-xs font-medium text-[#b3261e]">{{ $message }}</p>
                            @enderror
                        </div>
                    @else
                        <p class="text-sm text-[#738078]">No image is currently saved for this category.</p>
                    @endif
                </div>
            </div>

            <div class="rounded-2xl border border-[#dfe3cf] bg-[#fafaf5] p-4 sm:p-5">
                <div>
                    <h3 class="text-sm font-bold text-[#173b32]">Upload Replacement Image</h3>
                    <p class="text-xs text-[#738078]">Choose a new image to replace the current one. JPG, PNG, WebP or AVIF up to 5 MB.</p>
                </div>

                <label for="image" class="relative mt-4 flex cursor-pointer flex-col items-center justify-center rounded-2xl border-2 border-dashed border-[#dfe3cf] bg-white p-6 text-center transition hover:border-[#145943] hover:bg-[#f7f9f3] focus-within:border-[#145943] focus-within:ring-2 focus-within:ring-[#145943]/20">
                    <svg class="pointer-events-none mb-2 h-10 w-10 text-[#145943]/70" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    <p class="pointer-events-none text-sm font-bold text-[#173b32]">Click anywhere to choose a replacement image</p>
                    <p class="pointer-events-none mt-1 text-xs text-[#738078]">Select one image from your device</p>
                    <input type="file" name="image" id="image" data-category-image-input="category-image-preview" accept="image/jpeg,image/png,image/webp,image/avif" aria-label="Choose replacement category image" class="absolute inset-0 h-full w-full cursor-pointer opacity-0">
                </label>
                <div id="category-image-preview" class="mt-4 hidden items-center gap-4 rounded-xl border border-[#dfe3cf] bg-white p-3" aria-live="polite">
                    <img data-category-image-preview alt="Selected replacement category image" class="h-20 w-20 shrink-0 rounded-lg object-cover">
                    <div class="min-w-0">
                        <p class="text-sm font-bold text-[#173b32]">Selected for upload</p>
                        <p data-category-image-name class="mt-1 truncate text-xs text-[#738078]"></p>
                    </div>
                </div>
                @error('image')
                    <p class="mt-2 text-xs font-medium text-[#b3261e]">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex items-center gap-3">
                <input type="hidden" name="is_active" value="0">
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
