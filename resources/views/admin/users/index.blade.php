@extends('admin.layout')

@section('title', 'Staff Accounts')

@section('content')
    <header class="rounded-[20px] border border-[#e5e2d9] bg-white p-5 shadow-sm">
        <p class="text-xs font-bold uppercase tracking-[0.2em] text-[#a48335]">Access Management</p>
        <h1 class="mt-2 text-2xl font-extrabold text-[#145943]">Staff Accounts</h1>
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
        @can('users.create')
            <section class="rounded-[20px] border border-[#e5e2d9] bg-white p-5 shadow-sm">
                <h2 class="text-lg font-extrabold text-[#145943]">Create Staff Account</h2>
                <form method="POST" action="{{ route('admin.users.store') }}" class="mt-5 space-y-4">
                    @csrf
                    <label class="block text-sm font-semibold text-[#4d5c56]">
                        Name
                        <input type="text" name="name" value="{{ old('name') }}" required maxlength="255" class="mt-2 h-11 w-full rounded-lg border border-[#d9dfd2] bg-[#fafaf5] px-3 text-sm outline-none focus:border-[#145943] focus:ring-4 focus:ring-[#145943]/10">
                    </label>
                    <label class="block text-sm font-semibold text-[#4d5c56]">
                        Email
                        <input type="email" name="email" value="{{ old('email') }}" required maxlength="255" class="mt-2 h-11 w-full rounded-lg border border-[#d9dfd2] bg-[#fafaf5] px-3 text-sm outline-none focus:border-[#145943] focus:ring-4 focus:ring-[#145943]/10">
                    </label>
                    <label class="block text-sm font-semibold text-[#4d5c56]">
                        Temporary password
                        <input type="password" name="password" required minlength="12" autocomplete="new-password" class="mt-2 h-11 w-full rounded-lg border border-[#d9dfd2] bg-[#fafaf5] px-3 text-sm outline-none focus:border-[#145943] focus:ring-4 focus:ring-[#145943]/10">
                        <span class="mt-1 block text-xs font-normal text-[#738078]">12+ characters with uppercase, lowercase, a number and a symbol.</span>
                    </label>
                    <label class="block text-sm font-semibold text-[#4d5c56]">
                        Confirm password
                        <input type="password" name="password_confirmation" required minlength="12" autocomplete="new-password" class="mt-2 h-11 w-full rounded-lg border border-[#d9dfd2] bg-[#fafaf5] px-3 text-sm outline-none focus:border-[#145943] focus:ring-4 focus:ring-[#145943]/10">
                    </label>
                    <label class="block text-sm font-semibold text-[#4d5c56]">
                        Roles
                        <select name="roles[]" multiple required class="mt-2 min-h-28 w-full rounded-lg border border-[#d9dfd2] bg-[#fafaf5] px-3 py-2 text-sm outline-none focus:border-[#145943] focus:ring-4 focus:ring-[#145943]/10">
                            @foreach ($roles as $role)
                                <option value="{{ $role->id }}" @selected(in_array($role->id, old('roles', [])))>{{ $role->name }}</option>
                            @endforeach
                        </select>
                    </label>
                    <button type="submit" class="rounded-lg bg-[#145943] px-4 py-2.5 text-sm font-bold text-white transition hover:bg-[#a48335]">Create Account</button>
                </form>
            </section>
        @endcan

        <section class="space-y-4">
            @forelse ($staffUsers as $staffUser)
                <article class="rounded-[20px] border border-[#e5e2d9] bg-white p-5 shadow-sm">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <h2 class="text-lg font-extrabold text-[#145943]">{{ $staffUser->name }}</h2>
                            <p class="mt-1 text-sm text-[#66736d]">{{ $staffUser->email }}</p>
                        </div>
                        <span class="rounded-full px-3 py-1 text-xs font-bold {{ $staffUser->status === 'active' ? 'bg-green-50 text-green-800' : 'bg-red-50 text-red-800' }}">
                            {{ ucfirst($staffUser->status) }}
                        </span>
                    </div>

                    @can('users.update')
                        <form method="POST" action="{{ route('admin.users.roles.update', $staffUser) }}" class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-end">
                            @csrf
                            @method('PUT')
                            <label class="block flex-1 text-sm font-semibold text-[#4d5c56]">
                                Roles
                                <select name="roles[]" multiple required class="mt-2 min-h-20 w-full rounded-lg border border-[#d9dfd2] bg-[#fafaf5] px-3 py-2 text-sm outline-none focus:border-[#145943] focus:ring-4 focus:ring-[#145943]/10">
                                    @foreach ($roles as $role)
                                        <option value="{{ $role->id }}" @selected(in_array($role->id, old('roles', $staffUser->roles->modelKeys())))>{{ $role->name }}</option>
                                    @endforeach
                                </select>
                            </label>
                            <button type="submit" class="rounded-lg border border-[#145943] px-4 py-2.5 text-sm font-bold text-[#145943] transition hover:bg-[#edf2e4]">Save Roles</button>
                        </form>
                    @endcan

                    @can('users.suspend')
                        <form method="POST" action="{{ route('admin.users.status.update', $staffUser) }}" class="mt-3">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="status" value="{{ $staffUser->status === 'active' ? 'suspended' : 'active' }}">
                            <button type="submit" class="text-sm font-bold {{ $staffUser->status === 'active' ? 'text-red-700 hover:text-red-900' : 'text-[#145943] hover:text-[#a48335]' }}">
                                {{ $staffUser->status === 'active' ? 'Suspend account' : 'Activate account' }}
                            </button>
                        </form>
                    @endcan
                </article>
            @empty
                <div class="rounded-[20px] border border-dashed border-[#cfd8ca] bg-white p-6 text-sm text-[#66736d]">No staff accounts have been created.</div>
            @endforelse
        </section>
    </div>
@endsection