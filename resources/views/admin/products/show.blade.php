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
