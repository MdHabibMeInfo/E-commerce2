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
@endsection
