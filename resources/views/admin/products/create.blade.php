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
        <form action="{{ route('admin.products.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
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

            {{-- Product Images Upload --}}
            <div class="rounded-2xl border border-[#dfe3cf] bg-[#fafaf5] p-4 sm:p-5">
                <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <label class="block text-sm font-bold text-[#173b32]">Product Images (Multiple)</label>
                        <p class="text-xs text-[#738078]">Upload multiple images (JPG, PNG, WebP up to 5MB each). Click on any image to mark it as the <strong>Primary Image</strong>.</p>
                    </div>
                    <span id="selected_images_count" style="display: none;" class="rounded-full bg-[#145943]/10 px-3 py-1 text-xs font-bold text-[#145943]">
                        0 images selected
                    </span>
                </div>

                {{-- Hidden file inputs kept outside dropzone to avoid click recursion --}}
                <input
                    type="file"
                    id="product_images"
                    multiple
                    accept="image/jpeg,image/png,image/webp,image/avif"
                    class="hidden"
                >
                <input type="file" name="images[]" id="real_product_images" multiple class="hidden">
                <input type="hidden" name="primary_index" id="primary_index_input" value="0">

                {{-- Interactive Dropzone --}}
                <div
                    id="dropzone_area"
                    role="button"
                    tabindex="0"
                    class="mt-4 flex cursor-pointer flex-col items-center justify-center rounded-2xl border-2 border-dashed border-[#dfe3cf] bg-white p-6 text-center transition hover:border-[#145943] hover:bg-[#f7f9f3] focus:outline-none focus:border-[#145943]"
                >
                    <svg class="h-10 w-10 text-[#145943]/70 mb-2 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    <p class="text-sm font-bold text-[#173b32] pointer-events-none">Click to browse or drag & drop multiple images here</p>
                    <p class="mt-1 text-xs text-[#738078] pointer-events-none">You can add multiple images at once, or add them incrementally one by one.</p>
                </div>

                @error('images')
                    <p class="mt-2 text-xs font-medium text-[#b3261e]">{{ $message }}</p>
                @enderror
                @error('images.*')
                    <p class="mt-2 text-xs font-medium text-[#b3261e]">{{ $message }}</p>
                @enderror

                {{-- Preview Grid --}}
                <div id="image_preview_container" style="display: none;" class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-4 md:grid-cols-6"></div>

                <div id="add_more_wrapper" style="display: none;" class="mt-4">
                    <button
                        type="button"
                        id="add_more_btn"
                        class="inline-flex items-center gap-2 rounded-xl border border-[#145943] bg-white px-4 py-2 text-xs font-bold text-[#145943] transition hover:bg-[#145943] hover:text-white"
                    >
                        <span>+</span> Add More Images
                    </button>
                </div>
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

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const fileInput = document.getElementById('product_images');
            const dropzone = document.getElementById('dropzone_area');
            const previewContainer = document.getElementById('image_preview_container');
            const addMoreWrapper = document.getElementById('add_more_wrapper');
            const addMoreBtn = document.getElementById('add_more_btn');
            const countBadge = document.getElementById('selected_images_count');
            const primaryInput = document.getElementById('primary_index_input');

            let accumulatedFiles = [];
            let primaryIndex = 0;
            let objectUrls = [];

            const cleanupObjectUrls = () => {
                objectUrls.forEach(url => URL.revokeObjectURL(url));
                objectUrls = [];
            };

            const updateFileInput = () => {
                try {
                    const dt = new DataTransfer();
                    accumulatedFiles.forEach(file => dt.items.add(file));
                    document.getElementById('real_product_images').files = dt.files;
                } catch (err) {
                    console.warn('DataTransfer not available:', err);
                }
            };

            const renderPreviews = () => {
                cleanupObjectUrls();
                previewContainer.innerHTML = '';

                if (!accumulatedFiles.length) {
                    previewContainer.style.display = 'none';
                    addMoreWrapper.style.display = 'none';
                    countBadge.style.display = 'none';
                    primaryIndex = 0;
                    primaryInput.value = 0;
                    return;
                }

                previewContainer.style.display = 'grid';
                addMoreWrapper.style.display = 'block';
                countBadge.style.display = 'inline-block';
                countBadge.textContent = `${accumulatedFiles.length} ${accumulatedFiles.length === 1 ? 'image' : 'images'} selected`;

                if (primaryIndex >= accumulatedFiles.length) {
                    primaryIndex = 0;
                }
                primaryInput.value = primaryIndex;

                accumulatedFiles.forEach((file, index) => {
                    const isPrimary = (index === primaryIndex);
                    const blobUrl = URL.createObjectURL(file);
                    objectUrls.push(blobUrl);

                    const card = document.createElement('div');
                    card.className = `group relative aspect-square overflow-hidden rounded-xl border-2 transition-all p-1 bg-white shadow-xs cursor-pointer ${isPrimary ? 'border-[#145943] ring-2 ring-[#145943]/30' : 'border-[#dfe3cf] hover:border-[#145943]/60'}`;
                    card.title = isPrimary ? 'Primary Image (Click any other image to change)' : 'Click to set as Primary Image';

                    const fileSize = file.size > 1024 * 1024
                        ? (file.size / (1024 * 1024)).toFixed(1) + ' MB'
                        : Math.round(file.size / 1024) + ' KB';

                    card.innerHTML = `
                        <img src="${blobUrl}" alt="${file.name}" class="h-full w-full rounded-lg object-cover pointer-events-none">
                        <span class="absolute left-2 top-2 rounded-md ${isPrimary ? 'bg-[#145943] text-white shadow-xs' : 'bg-black/60 text-white'} px-1.5 py-0.5 text-[10px] font-bold pointer-events-none">
                            ${isPrimary ? '★ Primary' : '#' + (index + 1)}
                        </span>
                        <span class="absolute left-2 bottom-2 rounded-md bg-black/60 px-1.5 py-0.5 text-[9px] font-medium text-white truncate max-w-[80%] pointer-events-none">
                            ${fileSize}
                        </span>
                        <button
                            type="button"
                            data-remove-index="${index}"
                            class="absolute right-2 top-2 grid h-6 w-6 place-items-center rounded-full bg-white/95 text-xs font-bold text-[#b3261e] shadow-sm hover:bg-[#b3261e] hover:text-white transition"
                            title="Remove Image"
                            aria-label="Remove image ${index + 1}"
                        >
                            &times;
                        </button>
                    `;

                    card.addEventListener('click', (evt) => {
                        if (evt.target.closest('[data-remove-index]')) return;
                        primaryIndex = index;
                        primaryInput.value = index;
                        renderPreviews();
                    });

                    const removeBtn = card.querySelector('[data-remove-index]');
                    removeBtn?.addEventListener('click', (evt) => {
                        evt.stopPropagation();
                        accumulatedFiles.splice(index, 1);
                        if (primaryIndex >= accumulatedFiles.length) {
                            primaryIndex = Math.max(0, accumulatedFiles.length - 1);
                        }
                        updateFileInput();
                        renderPreviews();
                    });

                    previewContainer.appendChild(card);
                });
            };

            const handleNewFiles = (newFiles) => {
                if (!newFiles || !newFiles.length) return;
                const validFiles = Array.from(newFiles).filter(file => {
                    return !file.type || file.type.startsWith('image/') || /\.(jpe?g|png|webp|avif|gif)$/i.test(file.name);
                });
                if (!validFiles.length) return;

                accumulatedFiles = accumulatedFiles.concat(validFiles);
                updateFileInput();
                renderPreviews();
            };

            dropzone.addEventListener('click', () => {
                fileInput.click();
            });

            dropzone.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    fileInput.click();
                }
            });

            addMoreBtn.addEventListener('click', () => {
                fileInput.click();
            });

            fileInput.addEventListener('click', (e) => {
                e.stopPropagation();
            });

            fileInput.addEventListener('change', (e) => {
                handleNewFiles(e.target.files);
                fileInput.value = '';
            });

            dropzone.addEventListener('dragover', (e) => {
                e.preventDefault();
                e.stopPropagation();
                dropzone.classList.add('border-[#145943]', 'bg-[#f7f9f3]');
            });

            dropzone.addEventListener('dragleave', (e) => {
                e.preventDefault();
                e.stopPropagation();
                dropzone.classList.remove('border-[#145943]', 'bg-[#f7f9f3]');
            });

            dropzone.addEventListener('drop', (e) => {
                e.preventDefault();
                e.stopPropagation();
                dropzone.classList.remove('border-[#145943]', 'bg-[#f7f9f3]');
                if (e.dataTransfer?.files) {
                    handleNewFiles(e.dataTransfer.files);
                }
            });
        });
    </script>
@endsection
