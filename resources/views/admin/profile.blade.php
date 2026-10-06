@extends('admin.layout')

@section('title', 'Profile | Admin Dashboard')

@section('content')
    @php
        $profileName = $profileUser?->name ?? 'System Owner';
        $profileEmail = $profileUser?->email ?? 'No email available';
        $profileRole = $profileUser?->getRoleNames()->first() ?? 'System Owner';
    @endphp

    <div>
        <p class="text-xs font-bold uppercase tracking-[0.2em] text-[#a48335]">Admin account</p>
        <h1 class="mt-2 text-3xl font-extrabold text-[#145943]">Profile</h1>
    </div>

    <section class="mt-6 max-w-2xl rounded-[4px] border border-[#e4e3d8] bg-white p-6">
        @if (session('status'))
            <p class="mb-5 rounded-[4px] bg-[#edf2e4] px-4 py-3 text-sm font-semibold text-[#145943]">{{ session('status') }}</p>
        @endif

        <h2 class="mb-5 text-xl font-extrabold text-[#145943]">Profile details</h2>

        <div class="flex items-center gap-4 border-b border-[#ece8dc] pb-5">
            @if ($profilePhotoUrl)
                <img src="{{ $profilePhotoUrl }}" alt="Profile photo for {{ $profileName }}" class="h-16 w-16 rounded-full border border-[#dce6ce] object-cover">
            @else
                <div class="grid h-16 w-16 place-items-center rounded-full bg-[#edf2e4] text-xl font-bold text-[#145943]" aria-hidden="true">
                    {{ strtoupper(substr($profileName, 0, 1)) }}
                </div>
            @endif
            <div>
                <h2 class="text-xl font-bold text-[#173b32]">{{ $profileName }}</h2>
                <p class="mt-1 text-sm text-[#738078]">{{ $profileRole }}</p>
            </div>
        </div>

        <form method="POST" action="{{ route('admin.profile.update') }}" enctype="multipart/form-data" class="mt-5 space-y-4">
            @csrf
            @method('PUT')

            <div>
                <label for="profile-name" class="mb-1 block text-sm font-semibold text-[#4d5c56]">Name</label>
                <input id="profile-name" name="name" value="{{ old('name', $profileUser->name) }}" required maxlength="255" class="h-11 w-full rounded-[4px] border border-[#dfe3cf] px-3 text-sm text-[#173b32] outline-none focus:border-[#145943] focus:ring-2 focus:ring-[#145943]/10">
                @error('name') <p class="mt-1 text-xs text-red-700">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="profile-email" class="mb-1 block text-sm font-semibold text-[#4d5c56]">Email</label>
                <input id="profile-email" type="email" name="email" value="{{ $profileUser->email }}" readonly class="h-11 w-full rounded-[4px] border border-[#dfe3cf] bg-[#f7f4ee] px-3 text-sm text-[#173b32] outline-none">
                <p class="mt-1 text-xs text-[#738078]">Email changes require verification. <a href="{{ route('account.security') }}" class="font-semibold text-[#145943] underline">Manage email in Account Security</a></p>
                @error('email') <p class="mt-1 text-xs text-red-700">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="current-password" class="mb-1 block text-sm font-semibold text-[#4d5c56]">Current password <span class="font-normal text-[#738078]">(required to change email)</span></label>
                <input id="current-password" type="password" name="current_password" autocomplete="current-password" class="h-11 w-full rounded-[4px] border border-[#dfe3cf] px-3 text-sm text-[#173b32] outline-none focus:border-[#145943] focus:ring-2 focus:ring-[#145943]/10">
                @error('current_password') <p class="mt-1 text-xs text-red-700">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="profile-phone" class="mb-1 block text-sm font-semibold text-[#4d5c56]">Phone</label>
                <input id="profile-phone" type="tel" name="phone" value="{{ old('phone', $profileUser->phone) }}" maxlength="20" class="h-11 w-full rounded-[4px] border border-[#dfe3cf] px-3 text-sm text-[#173b32] outline-none focus:border-[#145943] focus:ring-2 focus:ring-[#145943]/10">
                @error('phone') <p class="mt-1 text-xs text-red-700">{{ $message }}</p> @enderror
            </div>

            <div data-profile-photo-field class="flex flex-col gap-3 sm:flex-row sm:items-end">
                <label for="profile-photo" class="mb-1 block min-w-0 flex-1 text-sm font-semibold text-[#4d5c56]">Profile photo
                    <input id="profile-photo" type="file" name="profile_photo" accept="image/jpeg,image/png,image/webp" data-profile-photo-input class="mt-1 block w-full rounded-[4px] border border-[#dfe3cf] bg-white text-sm text-[#4d5c56] file:mr-3 file:border-0 file:bg-[#edf2e4] file:px-3 file:py-2.5 file:text-sm file:font-semibold file:text-[#145943]">
                </label>
                <img data-profile-photo-preview alt="Selected profile photo" hidden class="h-14 w-14 shrink-0 rounded-full border border-[#dce6ce] object-cover">
                @if ($profileUser->profile_photo_path)
                    <button type="submit" form="admin-profile-photo-remove" class="shrink-0 rounded-[4px] border border-[#dfe3cf] px-4 py-2.5 text-sm font-semibold text-[#4d5c56] transition hover:bg-[#f5f4ec]">Remove photo</button>
                @endif
            </div>
            <div>
                <p class="mt-1 text-xs text-[#738078]">JPG, PNG or WebP, up to 2 MB.</p>
                @error('profile_photo') <p class="mt-1 text-xs text-red-700">{{ $message }}</p> @enderror
            </div>

            <button type="submit" class="rounded-[4px] bg-[#145943] px-5 py-2.5 text-sm font-bold text-white transition hover:bg-[#a48335]">Save changes</button>
        </form>

        @if ($profileUser->profile_photo_path)
            <form id="admin-profile-photo-remove" method="POST" action="{{ route('admin.profile.photo.destroy') }}" class="hidden">
                @csrf
                @method('DELETE')
            </form>
        @endif

        <form method="POST" action="{{ route('logout') }}" class="mt-3">
            @csrf
            <button type="submit" class="rounded-[4px] border border-[#dfe3cf] px-5 py-2.5 text-sm font-semibold text-[#4d5c56] transition hover:bg-[#f5f4ec]">Sign out</button>
        </form>
    </section>

        <section class="mt-6 max-w-2xl rounded-[4px] border border-[#e4e3d8] bg-white p-6">
            <div class="flex items-center justify-between gap-3">
                <h2 class="text-xl font-extrabold text-[#145943]">Active orders</h2>
                <a href="{{ route('account.orders') }}" class="text-sm font-bold text-[#145943]">View all</a>
            </div>

            @if ($activeOrders->isEmpty())
                <p class="mt-4 text-sm text-[#738078]">No active orders right now.</p>
            @else
                <div class="mt-5 space-y-3">
                    @foreach ($activeOrders as $order)
                        <div class="flex flex-col gap-3 rounded-[4px] border border-[#eef0ea] bg-[#fafaf7] p-4 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-[#a48335]">Order</p>
                                <p class="mt-1 font-extrabold text-[#173b32]">{{ $order->order_number }}</p>
                            </div>
                            <div class="sm:text-right">
                                <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-[#a48335]">Status</p>
                                <p class="mt-1 text-sm font-semibold text-[#145943]">{{ $order->status }}</p>
                            </div>
                            <a href="{{ route('account.orders.show', $order) }}" class="inline-flex min-h-9 items-center justify-center rounded-[4px] bg-[#145943] px-3 text-sm font-bold text-white">View details</a>
                        </div>
                    @endforeach
                </div>
            @endif
        </section>
@endsection
