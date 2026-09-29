@extends('admin.layout')

@section('title', 'Roles & Permissions')

@section('content')
    <header class="rounded-[20px] border border-[#e5e2d9] bg-white p-5 shadow-sm">
        <p class="text-xs font-bold uppercase tracking-[0.2em] text-[#a48335]">Access Management</p>
        <h1 class="mt-2 text-2xl font-extrabold text-[#145943]">Roles & Permissions</h1>
    </header>

    @if (session('success'))
        <p class="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm font-semibold text-green-800">{{ session('success') }}</p>
    @endif

    @if ($errors->any())
        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
            <ul class="space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="grid gap-6 xl:grid-cols-[0.8fr_1.2fr]">
        @if (auth()->user()?->is_system_owner && auth()->user()?->can('roles.create'))
            <section class="rounded-[20px] border border-[#e5e2d9] bg-white p-5 shadow-sm">
                <h2 class="text-lg font-extrabold text-[#145943]">Create Role</h2>
                <form method="POST" action="{{ route('admin.roles.store') }}" class="mt-5 space-y-5">
                    @csrf
                    <label class="block text-sm font-semibold text-[#4d5c56]">
                        Role name
                        <input type="text" name="name" value="{{ old('name') }}" required maxlength="255" class="mt-2 h-11 w-full rounded-lg border border-[#d9dfd2] bg-[#fafaf5] px-3 text-sm outline-none focus:border-[#145943] focus:ring-4 focus:ring-[#145943]/10">
                    </label>

                    <fieldset>
                        <legend class="text-sm font-semibold text-[#4d5c56]">Permissions</legend>
                        <div class="mt-3 grid max-h-80 gap-2 overflow-y-auto rounded-lg border border-[#e5e2d9] bg-[#fafaf5] p-3 sm:grid-cols-2">
                            @foreach ($permissions as $permission)
                                <label class="flex items-start gap-2 text-xs text-[#4d5c56]">
                                    <input type="checkbox" name="permissions[]" value="{{ $permission->id }}" @checked(in_array($permission->id, old('permissions', []))) class="mt-0.5 rounded border-[#aeb8aa] text-[#145943] focus:ring-[#145943]">
                                    <span>{{ $permission->name }}</span>
                                </label>
                            @endforeach
                        </div>
                    </fieldset>

                    <button type="submit" class="rounded-lg bg-[#145943] px-4 py-2.5 text-sm font-bold text-white transition hover:bg-[#a48335]">Create Role</button>
                </form>
            </section>
        @endif

        <section class="space-y-4">
            @forelse ($roles as $role)
                <article class="rounded-[20px] border border-[#e5e2d9] bg-white p-5 shadow-sm">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <h2 class="text-lg font-extrabold text-[#145943]">{{ $role->name }}</h2>
                            <p class="mt-1 text-xs text-[#66736d]">{{ $role->permissions->count() }} permissions</p>
                        </div>
                        @if ($role->is_system)
                            <span class="rounded-full bg-[#f7f4ee] px-3 py-1 text-xs font-semibold text-[#8a6b2e]">System role</span>
                        @endif
                    </div>

                    @if (auth()->user()?->is_system_owner && ! $role->is_system && auth()->user()?->can('roles.update'))
                        <form method="POST" action="{{ route('admin.roles.update', $role) }}" class="mt-4 flex flex-wrap items-end gap-3">
                            @csrf
                            @method('PUT')
                            <label class="block flex-1 text-sm font-semibold text-[#4d5c56]">
                                Role name
                                <input type="text" name="name" value="{{ $role->name }}" required maxlength="255" class="mt-2 h-10 w-full rounded-lg border border-[#d9dfd2] bg-[#fafaf5] px-3 text-sm">
                            </label>
                            <button type="submit" class="h-10 rounded-lg border border-[#145943] px-4 text-sm font-bold text-[#145943]">Rename</button>
                        </form>

                        <form method="POST" action="{{ route('admin.roles.permissions.update', $role) }}" class="mt-4">
                            @csrf
                            @method('PUT')
                            <div class="grid max-h-72 gap-2 overflow-y-auto rounded-lg border border-[#e5e2d9] bg-[#fafaf5] p-3 sm:grid-cols-2">
                                @foreach ($permissions as $permission)
                                    <label class="flex items-start gap-2 text-xs text-[#4d5c56]">
                                        <input type="checkbox" name="permissions[]" value="{{ $permission->id }}" @checked(in_array($permission->id, old('permissions', $role->permissions->modelKeys()))) class="mt-0.5 rounded border-[#aeb8aa] text-[#145943] focus:ring-[#145943]">
                                        <span>{{ $permission->name }}</span>
                                    </label>
                                @endforeach
                            </div>
                            <button type="submit" class="mt-4 rounded-lg border border-[#145943] px-4 py-2 text-sm font-bold text-[#145943] transition hover:bg-[#edf2e4]">Save Permissions</button>
                        </form>
                        <div class="mt-4 flex flex-wrap items-center gap-4 border-t border-[#e5e2d9] pt-4">
                            <form method="POST" action="{{ route('admin.roles.status.update', $role) }}">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="is_active" value="{{ $role->is_active ? 0 : 1 }}">
                                <button type="submit" class="text-sm font-bold {{ $role->is_active ? 'text-amber-800' : 'text-[#145943]' }}">{{ $role->is_active ? 'Deactivate role' : 'Activate role' }}</button>
                            </form>
                            @if ($role->users_count === 0 && auth()->user()?->can('roles.delete'))
                                <form method="POST" action="{{ route('admin.roles.destroy', $role) }}" onsubmit="return confirm('Delete this role?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-sm font-bold text-red-800">Delete role</button>
                                </form>
                            @else
                                <span class="text-xs text-[#66736d]">{{ $role->users_count }} assigned user(s); deletion is unavailable.</span>
                            @endif
                        </div>
                    @else
                        <div class="mt-4 flex flex-wrap gap-2">
                            @foreach ($role->permissions as $permission)
                                <span class="rounded-md bg-[#f2f4ec] px-2 py-1 text-xs text-[#4d5c56]">{{ $permission->name }}</span>
                            @endforeach
                        </div>
                    @endcan
                </article>
            @empty
                <div class="rounded-[20px] border border-dashed border-[#cfd8ca] bg-white p-6 text-sm text-[#66736d]">No roles have been created.</div>
            @endforelse
        </section>
    </div>
@endsection