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
@endsection
