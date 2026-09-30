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

    @if ($errors->any())
        <div role="alert" class="mb-6 rounded-xl border border-[#f1c9c4] bg-[#fff1ef] px-4 py-3 text-sm text-[#9f2720]">
            <ul class="space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
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
                    <p class="text-xs font-bold uppercase tracking-[0.16em] text-[#a48335]">Availability</p>
                    <p class="mt-2 text-base font-bold text-[#173b32]">{{ $product->is_out_of_stock ? 'Out of stock' : 'Available' }}</p>
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

            {{-- Product Gallery & Image Management --}}
            <div class="mt-6 rounded-2xl border border-[#e5e2d9] bg-white p-5 shadow-xs">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between border-b border-[#efeae0] pb-4">
                    <div>
                        <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-[#a48335]">Gallery</p>
                        <h3 class="text-xl font-extrabold text-[#145943]">Product Images ({{ $product->images->count() }})</h3>
                    </div>
                    <span class="text-xs text-[#738078]">The primary image appears as the main thumbnail in shop and home</span>
                </div>

                @if ($product->images->isNotEmpty())
                    <div class="mt-5 grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4">
                        @foreach ($product->images as $img)
                            <div class="group relative flex flex-col overflow-hidden rounded-xl border {{ $img->is_primary ? 'border-[#145943] ring-2 ring-[#145943]/30' : 'border-[#e5e2d9]' }} bg-white shadow-xs transition hover:shadow-md">
                                <div class="relative aspect-square w-full overflow-hidden bg-[#f1f4e5]">
                                    <img
                                        src="/storage/{{ ltrim($img->image_path, '/') }}"
                                        alt="{{ $img->alt_text ?: $product->name }}"
                                        class="h-full w-full object-cover transition duration-300 group-hover:scale-105"
                                        loading="lazy"
                                    >
                                    @if ($img->is_primary)
                                        <span class="absolute left-2.5 top-2.5 rounded-md bg-[#145943] px-2 py-0.5 text-[11px] font-bold text-white shadow-sm">
                                            ★ Primary
                                        </span>
                                    @endif
                                </div>
                                <div class="flex items-center justify-between border-t border-[#f0eee6] bg-[#fafaf6] p-2.5">
                                    @if (! $img->is_primary)
                                        <form method="POST" action="{{ route('admin.products.images.primary', [$product, $img]) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="rounded-lg bg-white px-2.5 py-1 text-xs font-bold text-[#145943] border border-[#dfe3cf] transition hover:bg-[#edf2e4]">
                                                Set Primary
                                            </button>
                                        </form>
                                    @else
                                        <span class="text-xs font-bold text-[#145943]">Primary</span>
                                    @endif

                                    <form method="POST" action="{{ route('admin.products.images.destroy', [$product, $img]) }}" onsubmit="return confirm('Delete this image?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="rounded-lg p-1.5 text-[#b3261e] transition hover:bg-[#fdeeed]" title="Delete Image">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="mt-4 rounded-xl border border-dashed border-[#dfe3cf] bg-[#fafaf5] p-6 text-center text-sm text-[#738078]">
                        No images uploaded yet. Upload images below to showcase this product.
                    </div>
                @endif

                {{-- Quick Upload Section --}}
                <form method="POST" action="{{ route('admin.products.images.store', $product) }}" enctype="multipart/form-data" class="mt-6 rounded-xl border border-[#dfe3cf] bg-[#fafaf6] p-4">
                    @csrf
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h4 class="text-xs font-bold uppercase tracking-[0.16em] text-[#145943]">Add More Images</h4>
                            <p class="text-xs text-[#738078]">Upload one or multiple images at once (JPEG, PNG, WebP up to 5MB).</p>
                        </div>
                    </div>
                    <div class="mt-3 flex flex-col gap-3 sm:flex-row sm:items-center">
                        <input
                            type="file"
                            name="images[]"
                            id="show_upload_images"
                            multiple
                            required
                            accept="image/jpeg,image/png,image/webp,image/avif"
                            class="block w-full cursor-pointer rounded-xl border border-[#dfe3cf] bg-white px-3 py-2 text-sm text-[#173b32] file:mr-4 file:cursor-pointer file:rounded-lg file:border-0 file:bg-[#145943] file:px-4 file:py-1.5 file:text-xs file:font-bold file:text-white hover:file:bg-[#a48335] focus:outline-none"
                        >
                        <button type="submit" class="shrink-0 rounded-xl bg-[#145943] px-5 py-2 text-sm font-bold text-white transition hover:bg-[#a48335]">
                            Upload Images
                        </button>
                    </div>
                    <div id="show_image_preview_container" class="mt-4 hidden grid-cols-2 gap-3 sm:grid-cols-4 md:grid-cols-6"></div>
                </form>
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

    @if ($product->type === 'variable')
        <div class="mt-6 grid gap-6 xl:grid-cols-[0.8fr_1.2fr]">
            <section class="rounded-[22px] border border-[#e5e2d9] bg-white p-5 shadow-sm">
                <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-[#a48335]">Configuration</p>
                <h2 class="mt-1 text-xl font-extrabold text-[#145943]">Product Attributes</h2>
                <p class="mt-2 text-sm text-[#738078]">Choose the attributes that define this product's variants.</p>

                @can('products.update')
                    <form method="POST" action="{{ route('admin.products.attributes.update', $product) }}" class="mt-4 space-y-3">
                            @csrf
                            @method('PUT')
                            @forelse ($availableAttributes as $attribute)
                                <label class="flex items-start gap-3 rounded-lg border border-[#e5e2d9] px-3 py-3">
                                    <input type="checkbox" name="product_attribute_ids[]" value="{{ $attribute->id }}" {{ $product->productAttributes->contains('id', $attribute->id) ? 'checked' : '' }} class="mt-0.5 h-4 w-4 rounded border-[#dfe3cf] text-[#145943] focus:ring-[#145943]">
                                    <span class="min-w-0">
                                        <span class="block text-sm font-bold text-[#173b32]">{{ $attribute->name }}</span>
                                        <span class="block text-xs text-[#738078]">{{ $attribute->values->where('is_active', true)->count() }} active values</span>
                                    </span>
                                </label>
                            @empty
                                <p class="rounded-lg bg-[#f7f4ee] px-3 py-3 text-sm text-[#738078]">Create active product attributes and values before configuring variants.</p>
                            @endforelse
                            @error('product_attribute_ids') <p class="text-xs text-red-700">{{ $message }}</p> @enderror
                            @error('product_attribute_ids.*') <p class="text-xs text-red-700">{{ $message }}</p> @enderror
                            <button type="submit" class="w-full rounded-lg bg-[#145943] px-4 py-2.5 text-sm font-bold text-white transition hover:bg-[#a48335]">Assign Attributes</button>
                        </form>
                @endcan

                @can('variants.create')
                    @if ($canCreateVariants)
                        <details class="mt-6 border-t border-[#efeae0] pt-4">
                            <summary class="cursor-pointer text-sm font-bold text-[#145943]">Create Variant</summary>
                            <form method="POST" action="{{ route('admin.products.variants.store', $product) }}" class="mt-4 space-y-4">
                                @csrf
                                <div class="grid gap-3 sm:grid-cols-2">
                                    <div>
                                        <label for="variant-name" class="mb-1 block text-xs font-bold text-[#4d5c56]">Variant name</label>
                                        <input id="variant-name" name="name" value="{{ old('name') }}" maxlength="255" class="w-full rounded-lg border border-[#dfe3cf] px-3 py-2 text-sm focus:border-[#145943] focus:outline-none">
                                        @error('name') <p class="mt-1 text-xs text-red-700">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label for="variant-sku" class="mb-1 block text-xs font-bold text-[#4d5c56]">SKU</label>
                                        <input id="variant-sku" name="sku" value="{{ old('sku') }}" required maxlength="255" class="w-full rounded-lg border border-[#dfe3cf] px-3 py-2 text-sm focus:border-[#145943] focus:outline-none">
                                        @error('sku') <p class="mt-1 text-xs text-red-700">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label for="variant-price" class="mb-1 block text-xs font-bold text-[#4d5c56]">Price</label>
                                        <input id="variant-price" type="number" name="price" value="{{ old('price') }}" required min="0" step="0.01" class="w-full rounded-lg border border-[#dfe3cf] px-3 py-2 text-sm focus:border-[#145943] focus:outline-none">
                                        @error('price') <p class="mt-1 text-xs text-red-700">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label for="variant-compare-price" class="mb-1 block text-xs font-bold text-[#4d5c56]">Compare-at price</label>
                                        <input id="variant-compare-price" type="number" name="compare_at_price" value="{{ old('compare_at_price') }}" min="0" step="0.01" class="w-full rounded-lg border border-[#dfe3cf] px-3 py-2 text-sm focus:border-[#145943] focus:outline-none">
                                        @error('compare_at_price') <p class="mt-1 text-xs text-red-700">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label for="variant-stock" class="mb-1 block text-xs font-bold text-[#4d5c56]">Variant stock</label>
                                        <input id="variant-stock" type="number" name="stock" value="{{ old('stock', 0) }}" required min="0" class="w-full rounded-lg border border-[#dfe3cf] px-3 py-2 text-sm focus:border-[#145943] focus:outline-none">
                                        @error('stock') <p class="mt-1 text-xs text-red-700">{{ $message }}</p> @enderror
                                    </div>
                                </div>

                                <fieldset class="space-y-3">
                                    <legend class="text-xs font-bold text-[#4d5c56]">Attribute values</legend>
                                    @foreach ($product->productAttributes as $attribute)
                                        <div>
                                            <p class="mb-2 text-xs font-semibold text-[#738078]">{{ $attribute->name }}</p>
                                            <div class="flex flex-wrap gap-2">
                                                @foreach ($attribute->values->whereNull('deleted_at')->where('is_active', true) as $value)
                                                    <label class="flex items-center gap-2 rounded-md border border-[#e5e2d9] px-2.5 py-1.5 text-xs text-[#4d5c56]">
                                                        <input type="checkbox" name="attribute_value_ids[]" value="{{ $value->id }}" {{ in_array($value->id, old('attribute_value_ids', [])) ? 'checked' : '' }} class="h-3.5 w-3.5 rounded border-[#dfe3cf] text-[#145943] focus:ring-[#145943]">
                                                        {{ $value->value }}
                                                    </label>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endforeach
                                    @error('attribute_value_ids') <p class="text-xs text-red-700">{{ $message }}</p> @enderror
                                    @error('attribute_value_ids.*') <p class="text-xs text-red-700">{{ $message }}</p> @enderror
                                </fieldset>

                                <div class="flex items-center gap-2">
                                    <input type="hidden" name="is_active" value="0">
                                    <input id="variant-active" type="checkbox" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }} class="h-4 w-4 rounded border-[#dfe3cf] text-[#145943] focus:ring-[#145943]">
                                    <label for="variant-active" class="text-xs font-semibold text-[#4d5c56]">Active variant</label>
                                </div>
                                <button type="submit" class="w-full rounded-lg bg-[#145943] px-4 py-2.5 text-sm font-bold text-white transition hover:bg-[#a48335]">Save Variant</button>
                            </form>
                        </details>
                    @else
                        <p class="mt-5 border-t border-[#efeae0] pt-4 text-sm text-[#738078]">Assign at least one active attribute with an active value before creating variants.</p>
                    @endif
                @endcan
            </section>

            <section class="rounded-[22px] border border-[#e5e2d9] bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between gap-3 border-b border-[#efeae0] pb-4">
                    <div>
                        <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-[#a48335]">Catalog</p>
                        <h2 class="mt-1 text-xl font-extrabold text-[#145943]">Product Variants</h2>
                    </div>
                    <span class="text-sm text-[#738078]">{{ $variants->total() }} total</span>
                </div>

                <div class="divide-y divide-[#efeae0]">
                    @forelse ($variants as $variant)
                        <article class="py-4">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <h3 class="font-bold text-[#173b32]">{{ $variant->name ?: $variant->attributeValues->pluck('value')->implode(' / ') }}</h3>
                                    <p class="mt-1 text-xs text-[#738078]">{{ $variant->sku }} · ৳{{ number_format((float) $variant->price, 2) }} · Variant stock {{ $variant->stock }}</p>
                                    <p class="mt-1 text-xs {{ $variant->trashed() ? 'text-[#a15b1d]' : ($variant->is_active ? 'text-[#145943]' : 'text-[#738078]') }}">
                                        {{ $variant->trashed() ? 'Deleted' : ($variant->is_active ? 'Active' : 'Inactive') }}
                                        @if ($variant->attributeValues->isNotEmpty())
                                            · {{ $variant->attributeValues->map(fn ($value) => $value->productAttribute->name.': '.$value->value)->implode(' / ') }}
                                        @endif
                                    </p>
                                </div>
                                @if ($variant->trashed())
                                    @can('variants.update')
                                        <form method="POST" action="{{ route('admin.products.variants.restore', [$product, $variant]) }}">
                                            @csrf
                                            <button type="submit" class="rounded-lg bg-[#145943] px-3 py-2 text-xs font-bold text-white">Restore</button>
                                        </form>
                                    @endcan
                                @else
                                    <details>
                                        <summary class="cursor-pointer rounded-lg border border-[#dfe3cf] px-3 py-2 text-xs font-bold text-[#145943]">Edit variant</summary>
                                        @can('variants.update')
                                            <form method="POST" action="{{ route('admin.products.variants.update', [$product, $variant]) }}" class="mt-3 space-y-3 rounded-lg border border-[#e5e2d9] p-3">
                                                @csrf
                                                @method('PUT')
                                                <div class="grid gap-2 sm:grid-cols-2">
                                                    <input name="name" value="{{ $variant->name }}" placeholder="Variant name" maxlength="255" class="rounded-lg border border-[#dfe3cf] px-3 py-2 text-sm">
                                                    <input name="sku" value="{{ $variant->sku }}" required maxlength="255" aria-label="Variant SKU" class="rounded-lg border border-[#dfe3cf] px-3 py-2 text-sm">
                                                    <input type="number" name="price" value="{{ $variant->price }}" required min="0" step="0.01" aria-label="Variant price" class="rounded-lg border border-[#dfe3cf] px-3 py-2 text-sm">
                                                    <input type="number" name="compare_at_price" value="{{ $variant->compare_at_price }}" min="0" step="0.01" aria-label="Variant compare-at price" class="rounded-lg border border-[#dfe3cf] px-3 py-2 text-sm">
                                                    <input type="number" name="stock" value="{{ $variant->stock }}" required min="0" aria-label="Variant stock" class="rounded-lg border border-[#dfe3cf] px-3 py-2 text-sm">
                                                </div>
                                                @foreach ($product->productAttributes as $attribute)
                                                    <fieldset>
                                                        <legend class="mb-1 text-xs font-bold text-[#4d5c56]">{{ $attribute->name }}</legend>
                                                        <div class="flex flex-wrap gap-2">
                                                            @foreach ($attribute->values->whereNull('deleted_at')->where('is_active', true) as $value)
                                                                <label class="flex items-center gap-2 rounded-md border border-[#e5e2d9] px-2 py-1 text-xs text-[#4d5c56]">
                                                                    <input type="checkbox" name="attribute_value_ids[]" value="{{ $value->id }}" {{ $variant->attributeValues->contains('id', $value->id) ? 'checked' : '' }} class="h-3.5 w-3.5 rounded border-[#dfe3cf] text-[#145943]">
                                                                    {{ $value->value }}
                                                                </label>
                                                            @endforeach
                                                        </div>
                                                    </fieldset>
                                                @endforeach
                                                <div class="flex items-center gap-2">
                                                    <input type="hidden" name="is_active" value="0">
                                                    <input id="variant-{{ $variant->id }}-active" type="checkbox" name="is_active" value="1" {{ $variant->is_active ? 'checked' : '' }} class="h-4 w-4 rounded border-[#dfe3cf] text-[#145943]">
                                                    <label for="variant-{{ $variant->id }}-active" class="text-xs font-semibold text-[#4d5c56]">Active</label>
                                                    <button type="submit" class="ml-auto rounded-lg bg-[#145943] px-3 py-2 text-xs font-bold text-white">Save</button>
                                                </div>
                                            </form>
                                        @endcan
                                    </details>
                                @endif
                            </div>

                            @if (! $variant->trashed())
                                @can('variants.delete')
                                    <form method="POST" action="{{ route('admin.products.variants.destroy', [$product, $variant]) }}" onsubmit="return confirm('Delete this variant?')" class="mt-3 text-right">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-xs font-bold text-[#a15b1d]">Delete variant</button>
                                    </form>
                                @endcan
                            @endif
                        </article>
                    @empty
                        <p class="py-8 text-center text-sm text-[#738078]">No variants have been created for this product.</p>
                    @endforelse
                </div>
                <div class="mt-4">{{ $variants->links() }}</div>
            </section>
        </div>
    @endif

    <script>
        document.getElementById('show_upload_images')?.addEventListener('change', function(e) {
            const container = document.getElementById('show_image_preview_container');
            if (!container) return;

            container.innerHTML = '';
            const files = Array.from(e.target.files || []);

            if (!files.length) {
                container.classList.add('hidden');
                container.classList.remove('grid');
                return;
            }

            container.classList.remove('hidden');
            container.classList.add('grid');

            files.forEach((file, index) => {
                const reader = new FileReader();
                reader.onload = function(event) {
                    const card = document.createElement('div');
                    card.className = 'group relative aspect-square overflow-hidden rounded-xl border border-[#dfe3cf] bg-white p-1 shadow-xs';
                    card.innerHTML = `
                        <img src="${event.target.result}" alt="Preview ${index + 1}" class="h-full w-full rounded-lg object-cover">
                        <span class="absolute left-2 top-2 rounded-md bg-[#145943] px-1.5 py-0.5 text-[10px] font-bold text-white">
                            Ready
                        </span>
                    `;
                    container.appendChild(card);
                };
                reader.readAsDataURL(file);
            });
        });
    </script>
@endsection
