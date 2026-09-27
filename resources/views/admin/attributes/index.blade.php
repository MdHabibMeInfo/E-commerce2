@extends('admin.layout')

@section('content')
    <div class="mb-6 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
        <div>
            <p class="text-xs font-bold uppercase tracking-[0.22em] text-[#a48335]">Admin</p>
            <h1 class="mt-2 text-3xl font-extrabold text-[#145943]">Attribute Management</h1>
        </div>
        <div class="flex flex-wrap items-center gap-3">
            <a href="{{ route('admin.attributes.create') }}" class="rounded-xl bg-[#145943] px-4 py-2.5 text-sm font-bold text-white transition hover:bg-[#a48335]">
                Create Attribute
            </a>
            <a href="{{ route('admin.attributes.trashed') }}" class="rounded-xl border border-[#dfe3cf] bg-white px-4 py-2.5 text-sm font-bold text-[#145943] transition hover:bg-[#f7f4ee]">
                Deleted Attributes
            </a>
        </div>
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
                        <th class="px-4 py-3 font-bold">Attribute</th>
                        <th class="px-4 py-3 font-bold">Slug</th>
                        <th class="px-4 py-3 font-bold">Sort</th>
                        <th class="px-4 py-3 font-bold">Status</th>
                        <th class="px-4 py-3 font-bold">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($attributes as $attribute)
                        <tr class="border-t border-[#e5e2d9] align-top">
                            <td class="px-4 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="grid h-11 w-11 place-items-center rounded-xl bg-[#edf2e4] text-xs font-extrabold text-[#145943]">
                                        {{ strtoupper(substr($attribute->name, 0, 1)) }}
                                    </div>
                                    <div>
                                        <a href="{{ route('admin.attributes.show', $attribute) }}" class="font-bold text-[#173b32] hover:text-[#145943]">
                                            {{ $attribute->name }}
                                        </a>
                                        <p class="text-xs text-[#738078]">{{ $attribute->values()->count() }} values</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-4">{{ $attribute->slug }}</td>
                            <td class="px-4 py-4">{{ $attribute->sort_order ?? 0 }}</td>
                            <td class="px-4 py-4">
                                @if ($attribute->trashed())
                                    <span class="rounded-full bg-[#fef0d8] px-2.5 py-1 text-[10px] font-bold uppercase tracking-[0.14em] text-[#a15b1d]">Deleted</span>
                                @elseif ($attribute->is_active)
                                    <span class="rounded-full bg-[#edf8f1] px-2.5 py-1 text-[10px] font-bold uppercase tracking-[0.14em] text-[#145943]">Active</span>
                                @else
                                    <span class="rounded-full bg-[#f4f1ea] px-2.5 py-1 text-[10px] font-bold uppercase tracking-[0.14em] text-[#4d5c56]">Inactive</span>
                                @endif
                            </td>
                            <td class="px-4 py-4">
                                <div class="flex flex-wrap items-center gap-2">
                                    <a href="{{ route('admin.attributes.show', $attribute) }}" class="rounded-lg border border-[#dfe3cf] bg-white px-3 py-1.5 text-xs font-bold text-[#145943] transition hover:bg-[#f7f4ee]">
                                        View
                                    </a>
                                    <a href="{{ route('admin.attributes.edit', $attribute) }}" class="rounded-lg border border-[#dfe3cf] bg-white px-3 py-1.5 text-xs font-bold text-[#145943] transition hover:bg-[#f7f4ee]">
                                        Edit
                                    </a>
                                    @if ($attribute->trashed())
                                        <form method="POST" action="{{ route('admin.attributes.restore', $attribute) }}" onsubmit="return confirm('Restore this attribute?')">
                                            @csrf
                                            <button type="submit" class="rounded-lg bg-[#145943] px-3 py-1.5 text-xs font-bold text-white transition hover:bg-[#a48335]">
                                                Restore
                                            </button>
                                        </form>
                                    @else
                                        <form method="POST" action="{{ route('admin.attributes.destroy', $attribute) }}" onsubmit="return confirm('Delete this attribute?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="rounded-lg bg-[#a15b1d] px-3 py-1.5 text-xs font-bold text-white transition hover:bg-[#8e4d1c]">
                                                Delete
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-10 text-center text-[#738078]">
                                No attributes found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
