@extends('admin.layout')

@section('content')
    <div class="mb-6 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
        <div>
            <p class="text-xs font-bold uppercase tracking-[0.22em] text-[#a48335]">Admin</p>
            <h1 class="mt-2 text-3xl font-extrabold text-[#145943]">Deleted Attributes</h1>
        </div>

        <a href="{{ route('admin.attributes.index') }}" class="rounded-xl border border-[#dfe3cf] bg-white px-4 py-2.5 text-sm font-bold text-[#145943] transition hover:bg-[#f7f4ee]">
            Back to Attributes
        </a>
    </div>

    <form method="GET" action="{{ route('admin.attributes.trashed') }}" data-admin-search data-suggestions-url="{{ route('admin.attributes.suggestions', ['trashed' => true]) }}" class="mb-5 flex flex-col gap-3 sm:flex-row sm:flex-wrap">
        <div class="relative min-w-0 flex-1">
            <label for="trashed-attribute-search" class="sr-only">Search deleted attributes</label>
            <input id="trashed-attribute-search" data-admin-search-input type="search" name="search" value="{{ request('search') }}" placeholder="Search deleted attributes" maxlength="100" autocomplete="off" aria-autocomplete="list" aria-controls="trashed-attribute-search-suggestions" aria-expanded="false" class="w-full rounded-lg border border-[#dfe3cf] bg-white px-3 py-2.5 text-sm text-[#173b32] focus:border-[#145943] focus:outline-none">
            <div id="trashed-attribute-search-suggestions" data-admin-search-results role="listbox" aria-label="Deleted attribute suggestions" class="absolute inset-x-0 top-full z-20 mt-1 hidden max-h-72 overflow-y-auto rounded-lg border border-[#dfe3cf] bg-white py-1 shadow-lg"></div>
        </div>
        <label for="trashed-attribute-status" class="sr-only">Filter deleted attribute status</label>
        <div class="relative min-w-0 flex-1 basis-full sm:basis-40">
            <input id="trashed-attribute-status" data-admin-filter-input data-filter-options="status" type="search" value="{{ request('status') === 'active' ? 'Active' : (request('status') === 'inactive' ? 'Inactive' : '') }}" placeholder="Filter status" autocomplete="off" aria-autocomplete="list" aria-controls="trashed-attribute-status-suggestions" aria-expanded="false" class="w-full rounded-lg border border-[#dfe3cf] bg-white px-3 py-2.5 text-sm text-[#173b32] focus:border-[#145943] focus:outline-none">
            <input data-filter-value type="hidden" name="status" value="{{ request('status') }}">
            <div id="trashed-attribute-status-suggestions" data-admin-filter-results role="listbox" aria-label="Attribute status options" class="absolute inset-x-0 top-full z-20 mt-1 hidden max-h-72 overflow-y-auto rounded-lg border border-[#dfe3cf] bg-white py-1 shadow-lg"></div>
        </div>
        <button type="submit" class="rounded-lg bg-[#145943] px-4 py-2.5 text-sm font-bold text-white transition hover:bg-[#a48335]">Filter</button>
        @if (request()->hasAny(['search', 'status']))
            <a href="{{ route('admin.attributes.trashed') }}" class="rounded-lg border border-[#dfe3cf] bg-white px-4 py-2.5 text-center text-sm font-bold text-[#145943] transition hover:bg-[#f7f4ee]">Clear</a>
        @endif
    </form>

    <div class="overflow-hidden rounded-[20px] border border-[#e5e2d9] bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm text-[#4d5c56]">
                <thead class="bg-[#f7f4ee] text-[#145943]">
                    <tr>
                        <th class="px-4 py-3 font-bold">Attribute</th>
                        <th class="px-4 py-3 font-bold">Slug</th>
                        <th class="px-4 py-3 font-bold">Deleted At</th>
                        <th class="px-4 py-3 font-bold">Action</th>
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
                                        <span class="font-bold text-[#173b32]">{{ $attribute->name }}</span>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-4">{{ $attribute->slug }}</td>
                            <td class="px-4 py-4">{{ $attribute->deleted_at?->format('d M Y, h:i A') ?? '-' }}</td>
                            <td class="px-4 py-4">
                                <form method="POST" action="{{ route('admin.attributes.restore', $attribute) }}" onsubmit="return confirm('Restore this attribute?')">
                                    @csrf
                                    <button type="submit" class="rounded-lg bg-[#145943] px-3 py-1.5 text-xs font-bold text-white transition hover:bg-[#a48335]">
                                        Restore
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-10 text-center text-[#738078]">
                                No deleted attributes found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-5">
        {{ $attributes->links() }}
    </div>
@endsection
