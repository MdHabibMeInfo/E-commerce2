@extends('admin.layout')

@section('content')
    <div class="mb-6 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
        <div>
            <p class="text-xs font-bold uppercase tracking-[0.22em] text-[#a48335]">Admin</p>
            <h1 class="mt-2 text-3xl font-extrabold text-[#145943]">{{ $attribute->name }}</h1>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <a href="{{ route('admin.attributes.edit', $attribute) }}" class="rounded-xl border border-[#dfe3cf] bg-white px-4 py-2.5 text-sm font-bold text-[#145943] transition hover:bg-[#f7f4ee]">
                Edit Attribute
            </a>
            <a href="{{ route('admin.attributes.index') }}" class="rounded-xl border border-[#dfe3cf] bg-white px-4 py-2.5 text-sm font-bold text-[#145943] transition hover:bg-[#f7f4ee]">
                Back to Attributes
            </a>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-[1.2fr_0.8fr]">
        <div class="rounded-[22px] border border-[#e5e2d9] bg-white p-5 shadow-sm">
            <div class="grid gap-5 md:grid-cols-2">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.16em] text-[#a48335]">Slug</p>
                    <p class="mt-2 text-base font-bold text-[#173b32]">{{ $attribute->slug }}</p>
                </div>
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.16em] text-[#a48335]">Sort Order</p>
                    <p class="mt-2 text-base font-bold text-[#173b32]">{{ $attribute->sort_order ?? 0 }}</p>
                </div>
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.16em] text-[#a48335]">Status</p>
                    <p class="mt-2 text-base font-bold text-[#173b32]">{{ $attribute->is_active ? 'Active' : 'Inactive' }}</p>
                </div>
            </div>
        </div>

        <div class="rounded-[22px] border border-[#e5e2d9] bg-white p-5 shadow-sm">
            <p class="text-xs font-bold uppercase tracking-[0.16em] text-[#a48335]">Quick Actions</p>
            <div class="mt-4 space-y-3">
                <a href="{{ route('admin.attributes.edit', $attribute) }}" class="block rounded-xl bg-[#145943] px-4 py-3 text-center text-sm font-bold text-white transition hover:bg-[#a48335]">
                    Edit Attribute
                </a>

                <form method="POST" action="{{ route('admin.attributes.destroy', $attribute) }}" onsubmit="return confirm('Delete this attribute?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="w-full rounded-xl bg-[#a15b1d] px-4 py-3 text-sm font-bold text-white transition hover:bg-[#8e4d1c]">
                        Delete Attribute
                    </button>
                </form>
            </div>

            @if ($attribute->values->isNotEmpty())
                <div class="mt-6">
                    <p class="text-xs font-bold uppercase tracking-[0.16em] text-[#a48335]">Values</p>
                    <ul class="mt-3 space-y-2 text-sm text-[#4d5c56]">
                        @foreach ($attribute->values as $value)
                            <li class="rounded-lg bg-[#f7f4ee] px-3 py-2">{{ $value->value }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    </div>

    <section class="mt-6 rounded-[22px] border border-[#e5e2d9] bg-white p-5 shadow-sm">
        <div class="flex flex-col gap-3 border-b border-[#efeae0] pb-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-[#a48335]">Options</p>
                <h2 class="mt-1 text-xl font-extrabold text-[#145943]">Attribute Values</h2>
            </div>
            <span class="text-sm text-[#738078]">{{ $attribute->values->whereNull('deleted_at')->count() }} active values</span>
        </div>

        @can('attribute-values.create')
            <form method="POST" action="{{ route('admin.attributes.values.store', $attribute) }}" class="mt-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
                @csrf
                <div>
                    <label for="new-attribute-value" class="mb-1 block text-xs font-bold text-[#4d5c56]">Value</label>
                    <input id="new-attribute-value" name="value" value="{{ old('value') }}" required maxlength="255" class="w-full rounded-lg border border-[#dfe3cf] px-3 py-2 text-sm focus:border-[#145943] focus:outline-none">
                    @error('value') <p class="mt-1 text-xs text-red-700">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="new-attribute-value-slug" class="mb-1 block text-xs font-bold text-[#4d5c56]">Slug</label>
                    <input id="new-attribute-value-slug" name="slug" value="{{ old('slug') }}" maxlength="255" class="w-full rounded-lg border border-[#dfe3cf] px-3 py-2 text-sm focus:border-[#145943] focus:outline-none">
                    @error('slug') <p class="mt-1 text-xs text-red-700">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="new-attribute-value-sort" class="mb-1 block text-xs font-bold text-[#4d5c56]">Sort order</label>
                    <input id="new-attribute-value-sort" type="number" name="sort_order" value="{{ old('sort_order', 0) }}" min="0" class="w-full rounded-lg border border-[#dfe3cf] px-3 py-2 text-sm focus:border-[#145943] focus:outline-none">
                </div>
                <div class="flex items-end gap-2 pb-2">
                    <input type="hidden" name="is_active" value="0">
                    <input id="new-attribute-value-active" type="checkbox" name="is_active" value="1" checked class="h-4 w-4 rounded border-[#dfe3cf] text-[#145943] focus:ring-[#145943]">
                    <label for="new-attribute-value-active" class="text-sm font-semibold text-[#4d5c56]">Active</label>
                </div>
                <div class="flex items-end">
                    <button type="submit" class="w-full rounded-lg bg-[#145943] px-4 py-2 text-sm font-bold text-white transition hover:bg-[#a48335]">Add value</button>
                </div>
            </form>
        @endcan

        @if ($errors->has('attribute_value') || $errors->has('product_attribute_id'))
            <p role="alert" class="mt-3 rounded-lg border border-[#f1c9c4] bg-[#fff1ef] px-3 py-2 text-sm text-[#9f2720]">
                {{ $errors->first('attribute_value') ?: $errors->first('product_attribute_id') }}
            </p>
        @endif

        <div class="mt-5 divide-y divide-[#efeae0]">
            @forelse ($attribute->values as $value)
                <div class="grid gap-3 py-4 lg:grid-cols-[minmax(0,1fr)_auto] lg:items-center">
                    @if ($value->trashed())
                        <div>
                            <p class="font-bold text-[#173b32]">{{ $value->value }}</p>
                            <p class="text-xs text-[#738078]">{{ $value->slug }} · Deleted</p>
                        </div>
                        @can('attribute-values.update')
                            <form method="POST" action="{{ route('admin.attributes.values.restore', [$attribute, $value]) }}">
                                @csrf
                                <button type="submit" class="rounded-lg bg-[#145943] px-3 py-2 text-xs font-bold text-white">Restore</button>
                            </form>
                        @endcan
                    @else
                        <form id="attribute-value-{{ $value->id }}" method="POST" action="{{ route('admin.attributes.values.update', [$attribute, $value]) }}" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                            @csrf
                            @method('PUT')
                            <div>
                                <label for="attribute-value-name-{{ $value->id }}" class="sr-only">Value</label>
                                <input id="attribute-value-name-{{ $value->id }}" name="value" value="{{ $value->value }}" required maxlength="255" class="w-full rounded-lg border border-[#dfe3cf] px-3 py-2 text-sm focus:border-[#145943] focus:outline-none">
                            </div>
                            <div>
                                <label for="attribute-value-slug-{{ $value->id }}" class="sr-only">Slug</label>
                                <input id="attribute-value-slug-{{ $value->id }}" name="slug" value="{{ $value->slug }}" maxlength="255" class="w-full rounded-lg border border-[#dfe3cf] px-3 py-2 text-sm focus:border-[#145943] focus:outline-none">
                            </div>
                            <div>
                                <label for="attribute-value-sort-{{ $value->id }}" class="sr-only">Sort order</label>
                                <input id="attribute-value-sort-{{ $value->id }}" type="number" name="sort_order" value="{{ $value->sort_order }}" min="0" class="w-full rounded-lg border border-[#dfe3cf] px-3 py-2 text-sm focus:border-[#145943] focus:outline-none">
                            </div>
                            <div class="flex items-center gap-2">
                                <input type="hidden" name="is_active" value="0">
                                <input id="attribute-value-active-{{ $value->id }}" type="checkbox" name="is_active" value="1" {{ $value->is_active ? 'checked' : '' }} class="h-4 w-4 rounded border-[#dfe3cf] text-[#145943] focus:ring-[#145943]">
                                <label for="attribute-value-active-{{ $value->id }}" class="text-xs font-semibold text-[#4d5c56]">Active</label>
                                @can('attribute-values.update')
                                    <button type="submit" class="ml-auto rounded-lg border border-[#dfe3cf] bg-white px-3 py-2 text-xs font-bold text-[#145943]">Save</button>
                                @endcan
                            </div>
                        </form>
                        @can('attribute-values.delete')
                            <form method="POST" action="{{ route('admin.attributes.values.destroy', [$attribute, $value]) }}" onsubmit="return confirm('Delete this value?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="rounded-lg border border-[#e8c8b7] px-3 py-2 text-xs font-bold text-[#a15b1d]">Delete</button>
                            </form>
                        @endcan
                    @endif
                </div>
            @empty
                <p class="py-6 text-center text-sm text-[#738078]">No values added yet.</p>
            @endforelse
        </div>
    </section>
@endsection
