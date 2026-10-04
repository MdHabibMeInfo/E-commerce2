@extends('admin.layout')

@section('content')
    <div class="mb-6 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
        <div>
            <p class="text-xs font-bold uppercase tracking-[0.22em] text-[#a48335]">Admin</p>
            <h1 class="mt-2 text-3xl font-extrabold text-[#145943]">Update Product</h1>
        </div>

        <a href="{{ route('admin.products.show', $product) }}" class="rounded-xl border border-[#dfe3cf] bg-white px-4 py-2.5 text-sm font-bold text-[#145943] transition hover:bg-[#f7f4ee]">
            View Product
        </a>
    </div>

    <div class="rounded-[22px] border border-[#e5e2d9] bg-white p-5 shadow-sm">
        <form action="{{ route('admin.products.update', $product) }}" method="POST" enctype="multipart/form-data" class="space-y-6">
            @csrf
            @method('PUT')

            <div class="grid gap-5 md:grid-cols-2">
                <div>
                    <label for="category_id" class="mb-2 block text-sm font-bold text-[#173b32]">Category</label>
                    <select name="category_id" id="category_id" class="w-full rounded-xl border border-[#dfe3cf] bg-[#fafaf5] px-3 py-2.5 text-sm text-[#173b32] focus:border-[#145943] focus:outline-none">
                        <option value="">Select category</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" {{ old('category_id', $product->category_id) == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                        @endforeach
                    </select>
                    @error('category_id')
                        <p class="mt-2 text-xs font-medium text-[#b3261e]">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="name" class="mb-2 block text-sm font-bold text-[#173b32]">Product Name</label>
                    <input type="text" name="name" id="name" value="{{ old('name', $product->name) }}" class="w-full rounded-xl border border-[#dfe3cf] bg-[#fafaf5] px-3 py-2.5 text-sm text-[#173b32] focus:border-[#145943] focus:outline-none" required>
                    @error('name')
                        <p class="mt-2 text-xs font-medium text-[#b3261e]">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="slug" class="mb-2 block text-sm font-bold text-[#173b32]">Slug</label>
                    <input type="text" name="slug" id="slug" value="{{ old('slug', $product->slug) }}" class="w-full rounded-xl border border-[#dfe3cf] bg-[#fafaf5] px-3 py-2.5 text-sm text-[#173b32] focus:border-[#145943] focus:outline-none">
                    @error('slug')
                        <p class="mt-2 text-xs font-medium text-[#b3261e]">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="sku" class="mb-2 block text-sm font-bold text-[#173b32]">SKU</label>
                    <input type="text" name="sku" id="sku" value="{{ old('sku', $product->sku) }}" class="w-full rounded-xl border border-[#dfe3cf] bg-[#fafaf5] px-3 py-2.5 text-sm text-[#173b32] focus:border-[#145943] focus:outline-none">
                    @error('sku')
                        <p class="mt-2 text-xs font-medium text-[#b3261e]">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="type" class="mb-2 block text-sm font-bold text-[#173b32]">Product Type</label>
                    <select name="type" id="type" class="w-full rounded-xl border border-[#dfe3cf] bg-[#fafaf5] px-3 py-2.5 text-sm text-[#173b32] focus:border-[#145943] focus:outline-none" required>
                        <option value="simple" {{ old('type', $product->type) === 'simple' ? 'selected' : '' }}>Simple</option>
                        <option value="variable" {{ old('type', $product->type) === 'variable' ? 'selected' : '' }}>Variable</option>
                    </select>
                    @error('type')
                        <p class="mt-2 text-xs font-medium text-[#b3261e]">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="price" class="mb-2 block text-sm font-bold text-[#173b32]">Price</label>
                    <input type="number" name="price" id="price" value="{{ old('price', $product->price) }}" min="0" step="0.01" class="w-full rounded-xl border border-[#dfe3cf] bg-[#fafaf5] px-3 py-2.5 text-sm text-[#173b32] focus:border-[#145943] focus:outline-none">
                    @error('price')
                        <p class="mt-2 text-xs font-medium text-[#b3261e]">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="compare_at_price" class="mb-2 block text-sm font-bold text-[#173b32]">Compare At Price</label>
                    <input type="number" name="compare_at_price" id="compare_at_price" value="{{ old('compare_at_price', $product->compare_at_price) }}" min="0" step="0.01" class="w-full rounded-xl border border-[#dfe3cf] bg-[#fafaf5] px-3 py-2.5 text-sm text-[#173b32] focus:border-[#145943] focus:outline-none">
                    @error('compare_at_price')
                        <p class="mt-2 text-xs font-medium text-[#b3261e]">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div>
                <label for="short_description" class="mb-2 block text-sm font-bold text-[#173b32]">Short Description</label>
                <textarea name="short_description" id="short_description" rows="3" class="w-full rounded-xl border border-[#dfe3cf] bg-[#fafaf5] px-3 py-2.5 text-sm text-[#173b32] focus:border-[#145943] focus:outline-none">{{ old('short_description', $product->short_description) }}</textarea>
                @error('short_description')
                    <p class="mt-2 text-xs font-medium text-[#b3261e]">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="description" class="mb-2 block text-sm font-bold text-[#173b32]">Description</label>
                <textarea name="description" id="description" rows="6" class="w-full rounded-xl border border-[#dfe3cf] bg-[#fafaf5] px-3 py-2.5 text-sm text-[#173b32] focus:border-[#145943] focus:outline-none">{{ old('description', $product->description) }}</textarea>
                @error('description')
                    <p class="mt-2 text-xs font-medium text-[#b3261e]">{{ $message }}</p>
                @enderror
            </div>

            {{-- Existing Images Management --}}
            <div class="rounded-2xl border border-[#dfe3cf] bg-[#fafaf5] p-4 sm:p-5">
                <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h3 class="text-sm font-bold text-[#173b32]">Current Saved Images ({{ $product->images->count() }})</h3>
                        <p class="text-xs text-[#738078]">The primary image appears as the main thumbnail in shop and catalogs.</p>
                    </div>
                </div>

                @if ($product->images->isNotEmpty())
                    <div class="mt-4 grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6">
                        @foreach ($product->images as $img)
                            <div class="relative flex flex-col overflow-hidden rounded-xl border {{ $img->is_primary ? 'border-[#145943] ring-2 ring-[#145943]/20' : 'border-[#dfe3cf]' }} bg-white shadow-xs">
                                <div class="relative aspect-square w-full overflow-hidden bg-[#f1f4e5]">
                                    <img src="/storage/{{ ltrim($img->image_path, '/') }}" alt="{{ $img->alt_text ?: $product->name }}" class="h-full w-full object-cover">
                                    @if ($img->is_primary)
                                        <span class="absolute left-2 top-2 rounded-md bg-[#145943] px-1.5 py-0.5 text-[10px] font-bold text-white shadow-xs">
                                            ★ Primary
                                        </span>
                                    @endif
                                </div>
                                <div class="flex items-center justify-between border-t border-[#f0eee6] p-2 bg-[#fdfcf9]">
                                    @if (! $img->is_primary)
                                        <button
                                            type="button"
                                            onclick="document.getElementById('set-primary-form-{{ $img->id }}').submit();"
                                            class="text-[11px] font-bold text-[#145943] hover:text-[#a48335] hover:underline"
                                        >
                                            Make Primary
                                        </button>
                                    @else
                                        <span class="text-[11px] font-semibold text-[#145943]">Main Image</span>
                                    @endif
                                    <button
                                        type="button"
                                        onclick="if(confirm('Are you sure you want to delete this image?')) document.getElementById('delete-img-form-{{ $img->id }}').submit();"
                                        class="rounded-md p-1 text-[#b3261e] hover:bg-[#fdeeed]"
                                        title="Delete Image"
                                    >
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="mt-3 text-xs italic text-[#738078]">No images currently uploaded for this product.</p>
                @endif

                {{-- Interactive Dropzone for Uploading Additional Images --}}
                <div class="mt-6 border-t border-[#e5e2d9] pt-5">
                    <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <label class="block text-sm font-bold text-[#173b32]">Upload Additional Images</label>
                            <p class="text-xs text-[#738078]">Select or drag multiple images (JPG, PNG, WebP up to 5MB each) to add to this product.</p>
                        </div>
                        <span id="edit_selected_count" style="display: none;" class="rounded-full bg-[#145943]/10 px-3 py-1 text-xs font-bold text-[#145943]">
                            0 new images selected
                        </span>
                    </div>

                    {{-- Isolated input --}}
                    <input
                        type="file"
                        id="edit_product_images"
                        multiple
                        accept="image/jpeg,image/png,image/webp,image/avif"
                        class="hidden"
                    >
                    <input type="file" name="images[]" id="real_edit_product_images" multiple class="hidden">
                    <input type="hidden" name="primary_index" id="edit_primary_index" value="">

                    <div
                        id="edit_dropzone_area"
                        role="button"
                        tabindex="0"
                        class="mt-3 flex cursor-pointer flex-col items-center justify-center rounded-2xl border-2 border-dashed border-[#dfe3cf] bg-white p-6 text-center transition hover:border-[#145943] hover:bg-[#f7f9f3] focus:outline-none focus:border-[#145943]"
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

                    {{-- Preview Grid for New Images --}}
                    <div id="edit_image_preview_container" style="display: none;" class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-4 md:grid-cols-6"></div>

                    <div id="edit_add_more_wrapper" style="display: none;" class="mt-4">
                        <button
                            type="button"
                            id="edit_add_more_btn"
                            class="inline-flex items-center gap-2 rounded-xl border border-[#145943] bg-white px-4 py-2 text-xs font-bold text-[#145943] transition hover:bg-[#145943] hover:text-white"
                        >
                            <span>+</span> Add More Images
                        </button>
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', $product->is_active) ? 'checked' : '' }} class="h-4 w-4 rounded border-[#dfe3cf] text-[#145943] focus:ring-[#145943]">
                <label for="is_active" class="text-sm font-bold text-[#173b32]">Active product</label>
            </div>

            <div class="flex items-center gap-3">
                <input type="hidden" name="is_featured" value="0">
                <input type="checkbox" name="is_featured" id="is_featured" value="1" {{ old('is_featured', $product->is_featured) ? 'checked' : '' }} class="h-4 w-4 rounded border-[#dfe3cf] text-[#145943] focus:ring-[#145943]">
                <label for="is_featured" class="text-sm font-bold text-[#173b32]">Show in Just For You</label>
            </div>

            <div class="flex items-center justify-end gap-3">
                <a href="{{ route('admin.products.show', $product) }}" class="rounded-xl border border-[#dfe3cf] bg-white px-4 py-2.5 text-sm font-bold text-[#145943] transition hover:bg-[#f7f4ee]">
                    Cancel
                </a>
                <button type="submit" class="rounded-xl bg-[#145943] px-5 py-2.5 text-sm font-bold text-white transition hover:bg-[#a48335]">
                    Update Product
                </button>
            </div>
        </form>
    </div>

    {{-- Hidden forms for setting primary and deleting individual images outside the main form --}}
    @foreach ($product->images as $img)
        @if (! $img->is_primary)
            <form id="set-primary-form-{{ $img->id }}" action="{{ route('admin.products.images.primary', [$product, $img]) }}" method="POST" class="hidden">
                @csrf
                @method('PATCH')
            </form>
        @endif
        <form id="delete-img-form-{{ $img->id }}" action="{{ route('admin.products.images.destroy', [$product, $img]) }}" method="POST" class="hidden">
            @csrf
            @method('DELETE')
        </form>
    @endforeach

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const fileInput = document.getElementById('edit_product_images');
            const dropzone = document.getElementById('edit_dropzone_area');
            const previewContainer = document.getElementById('edit_image_preview_container');
            const addMoreWrapper = document.getElementById('edit_add_more_wrapper');
            const addMoreBtn = document.getElementById('edit_add_more_btn');
            const countBadge = document.getElementById('edit_selected_count');
            const primaryInput = document.getElementById('edit_primary_index');

            if (!fileInput || !dropzone) return;

            let accumulatedFiles = [];
            let primaryIndex = null;
            let objectUrls = [];

            const cleanupObjectUrls = () => {
                objectUrls.forEach(url => URL.revokeObjectURL(url));
                objectUrls = [];
            };

            const updateFileInput = () => {
                try {
                    const dt = new DataTransfer();
                    accumulatedFiles.forEach(file => dt.items.add(file));
                    document.getElementById('real_edit_product_images').files = dt.files;
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
                    primaryIndex = null;
                    if (primaryInput) primaryInput.value = '';
                    return;
                }

                previewContainer.style.display = 'grid';
                addMoreWrapper.style.display = 'block';
                countBadge.style.display = 'inline-block';
                countBadge.textContent = `${accumulatedFiles.length} ${accumulatedFiles.length === 1 ? 'new image' : 'new images'} selected to save`;

                if (primaryIndex !== null && primaryIndex >= accumulatedFiles.length) {
                    primaryIndex = 0;
                }
                if (primaryInput) {
                    primaryInput.value = primaryIndex !== null ? primaryIndex : '';
                }

                accumulatedFiles.forEach((file, index) => {
                    const isPrimary = (primaryIndex === index);
                    const blobUrl = URL.createObjectURL(file);
                    objectUrls.push(blobUrl);

                    const card = document.createElement('div');
                    card.className = `group relative aspect-square overflow-hidden rounded-xl border-2 transition-all p-1 bg-white shadow-xs cursor-pointer ${isPrimary ? 'border-[#145943] ring-2 ring-[#145943]/30' : 'border-[#dfe3cf] hover:border-[#145943]/60'}`;
                    card.title = isPrimary ? 'Set as new primary image' : 'Click to set this new image as primary';

                    const fileSize = file.size > 1024 * 1024
                        ? (file.size / (1024 * 1024)).toFixed(1) + ' MB'
                        : Math.round(file.size / 1024) + ' KB';

                    card.innerHTML = `
                        <img src="${blobUrl}" alt="${file.name}" class="h-full w-full rounded-lg object-cover pointer-events-none">
                        <span class="absolute left-2 top-2 rounded-md ${isPrimary ? 'bg-[#145943] text-white shadow-xs' : 'bg-black/60 text-white'} px-1.5 py-0.5 text-[10px] font-bold pointer-events-none">
                            ${isPrimary ? '★ New Primary' : '+New #' + (index + 1)}
                        </span>
                        <span class="absolute left-2 bottom-2 rounded-md bg-black/60 px-1.5 py-0.5 text-[9px] font-medium text-white truncate max-w-[80%] pointer-events-none">
                            ${fileSize}
                        </span>
                        <button
                            type="button"
                            data-remove-index="${index}"
                            class="absolute right-2 top-2 grid h-6 w-6 place-items-center rounded-full bg-white/95 text-xs font-bold text-[#b3261e] shadow-sm hover:bg-[#b3261e] hover:text-white transition"
                            title="Remove from upload"
                            aria-label="Remove new image ${index + 1}"
                        >
                            &times;
                        </button>
                    `;

                    card.addEventListener('click', (evt) => {
                        if (evt.target.closest('[data-remove-index]')) return;
                        primaryIndex = (primaryIndex === index) ? null : index;
                        renderPreviews();
                    });

                    const removeBtn = card.querySelector('[data-remove-index]');
                    removeBtn?.addEventListener('click', (evt) => {
                        evt.stopPropagation();
                        accumulatedFiles.splice(index, 1);
                        if (primaryIndex !== null && primaryIndex >= accumulatedFiles.length) {
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

            addMoreBtn?.addEventListener('click', () => {
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
