@extends('admin.layout')

@section('content')
    <div class="mb-6">
        <p class="text-xs font-bold uppercase tracking-[0.2em] text-[#a48335]">Admin</p>
        <h1 class="mt-2 text-3xl font-extrabold text-[#145943]">Store Settings</h1>
    </div>

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

    <section class="rounded-2xl border border-[#e4e3d8] bg-white p-5 shadow-sm sm:p-7">
        <div class="mb-6">
            <h2 class="text-xl font-extrabold text-[#145943]">Logo &amp; Homepage Hero</h2>
            <p class="mt-1 text-sm text-[#66736d]">The hero image replaces the product carousel when uploaded.</p>
        </div>

        @can('settings.update')
            <form method="POST" action="{{ route('admin.settings.branding.update') }}" enctype="multipart/form-data" data-branding-settings-form class="space-y-6">
                @csrf
                @method('PATCH')

                <div class="grid gap-6 lg:grid-cols-2">
                    <div class="space-y-4">
                        <label class="block text-sm font-semibold text-[#4d5c56]">
                            Site logo
                            <input type="file" name="logo" accept="image/jpeg,image/png,image/webp" data-branding-image data-max-width="2000" data-max-height="1000" class="mt-2 block w-full rounded-xl border border-[#dfe3cf] bg-white px-3 py-3 text-sm file:mr-3 file:rounded-lg file:border-0 file:bg-[#145943] file:px-3 file:py-2 file:font-bold file:text-white">
                            <span class="mt-1 block text-xs font-normal text-[#738078]">JPG, PNG or WebP. Large images are optimized before upload.</span>
                        </label>
                        @if ($branding['logo_path'])
                            <div class="flex min-h-24 items-center justify-between gap-4 rounded-xl border border-[#e4e3d8] bg-[#fbfaf4] p-4">
                                <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($branding['logo_path']) }}" alt="Current site logo" class="max-h-16 max-w-48 object-contain">
                                <label class="flex items-center gap-2 text-sm text-[#4d5c56]">
                                    <input type="checkbox" name="remove_logo" value="1" class="h-4 w-4 accent-[#145943]">
                                    Remove logo
                                </label>
                            </div>
                        @endif

                        <label class="block text-sm font-semibold text-[#4d5c56]">
                            Hero image
                            <input type="file" name="hero_image" accept="image/jpeg,image/png,image/webp" data-branding-image data-max-width="4000" data-max-height="3000" class="mt-2 block w-full rounded-xl border border-[#dfe3cf] bg-white px-3 py-3 text-sm file:mr-3 file:rounded-lg file:border-0 file:bg-[#145943] file:px-3 file:py-2 file:font-bold file:text-white">
                            <span class="mt-1 block text-xs font-normal text-[#738078]">JPG, PNG or WebP. Large images are optimized before upload.</span>
                        </label>
                        @if ($branding['hero_image_path'])
                            <div class="space-y-3 rounded-xl border border-[#e4e3d8] bg-[#fbfaf4] p-3">
                                <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($branding['hero_image_path']) }}" alt="Current homepage hero" class="max-h-52 w-full object-cover">
                                <label class="flex items-center gap-2 text-sm text-[#4d5c56]">
                                    <input type="checkbox" name="remove_hero_image" value="1" class="h-4 w-4 accent-[#145943]">
                                    Remove hero image
                                </label>
                            </div>
                        @endif
                    </div>

                    <div class="space-y-4">
                        <label class="block text-sm font-semibold text-[#4d5c56]">
                            Hero heading
                            <input type="text" name="hero_title" value="{{ old('hero_title', $branding['hero_title']) }}" maxlength="150" required class="mt-2 h-11 w-full rounded-xl border border-[#dfe3cf] bg-white px-3 text-sm outline-none focus:border-[#145943] focus:ring-4 focus:ring-[#145943]/10">
                        </label>
                        <label class="block text-sm font-semibold text-[#4d5c56]">
                            Hero description
                            <textarea name="hero_description" rows="4" maxlength="500" required class="mt-2 w-full rounded-xl border border-[#dfe3cf] bg-white px-3 py-3 text-sm outline-none focus:border-[#145943] focus:ring-4 focus:ring-[#145943]/10">{{ old('hero_description', $branding['hero_description']) }}</textarea>
                        </label>
                        <div>
                            <label for="hero-target-search" class="block text-sm font-semibold text-[#4d5c56]">Advertisement destination</label>
                            <input type="hidden" name="hero_target" value="{{ old('hero_target', $heroTargetLabel ? $branding['hero_target'] : '') }}" data-hero-target-value>
                            <input
                                id="hero-target-search"
                                type="search"
                                value="{{ $heroTargetLabel ? $heroTargetLabel : '' }}"
                                placeholder="Search category, product, or variant..."
                                autocomplete="off"
                                role="combobox"
                                aria-autocomplete="list"
                                aria-expanded="false"
                                aria-controls="hero-target-results"
                                data-hero-target-search
                                data-search-url="{{ route('admin.settings.hero-targets') }}"
                                class="mt-2 h-11 w-full rounded-xl border border-[#dfe3cf] bg-white px-3 text-sm outline-none focus:border-[#145943] focus:ring-4 focus:ring-[#145943]/10"
                            >
                            <div id="hero-target-results" data-hero-target-results role="listbox" class="mt-1 hidden max-h-64 overflow-y-auto rounded-xl border border-[#dfe3cf] bg-white shadow-lg"></div>
                            <div class="mt-2 flex items-center justify-between gap-3">
                                <p data-hero-target-selected class="min-w-0 truncate text-xs text-[#66736d]" aria-live="polite">
                                    {{ $heroTargetLabel ? 'Selected: '.$heroTargetLabel : 'No destination selected; clicking the image returns home.' }}
                                </p>
                                <button type="button" data-clear-hero-target class="shrink-0 rounded-lg border border-[#dfe3cf] px-3 py-1.5 text-xs font-semibold text-[#145943]">Clear</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-between gap-4 border-t border-[#eef0ea] pt-5">
                    <p data-branding-upload-status role="status" aria-live="polite" class="text-sm text-[#66736d]"></p>
                    <button type="submit" data-branding-submit class="min-h-11 rounded-xl bg-[#145943] px-5 py-3 text-sm font-bold text-white transition hover:bg-[#0f4d3a] disabled:cursor-wait disabled:opacity-60">Save branding</button>
                </div>
            </form>
        @else
            <dl class="grid gap-4 text-sm sm:grid-cols-2">
                <div><dt class="font-bold text-[#173b32]">Hero heading</dt><dd class="mt-1 text-[#4d5c56]">{{ $branding['hero_title'] }}</dd></div>
                <div><dt class="font-bold text-[#173b32]">Hero destination</dt><dd class="mt-1 text-[#4d5c56]">{{ $branding['hero_target'] ? 'Internal item selected' : 'Image only' }}</dd></div>
            </dl>
        @endcan
    </section>

    @if (is_array($sensitiveSettings))
        <section class="mt-6 rounded-2xl border border-[#e4e3d8] bg-white p-5 shadow-sm sm:p-7">
            <div class="mb-6">
                <p class="text-xs font-bold uppercase tracking-[0.16em] text-[#a48335]">System Owner Only</p>
                <h2 class="mt-2 text-xl font-extrabold text-[#145943]">Payments &amp; Security</h2>
            </div>

            <form method="POST" action="{{ route('admin.settings.security.update') }}" class="space-y-7">
                @csrf
                @method('PATCH')

                <fieldset class="space-y-4">
                    <legend class="text-sm font-bold text-[#173b32]">Mobile wallet merchant numbers</legend>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <label class="block text-sm font-semibold text-[#4d5c56]">
                            bKash
                            <input type="tel" name="bkash_merchant_number" value="{{ old('bkash_merchant_number', $sensitiveSettings['bkash_merchant_number']) }}" inputmode="numeric" pattern="01[3-9][0-9]{8}" maxlength="11" class="mt-2 h-11 w-full rounded-xl border border-[#dfe3cf] bg-white px-3 text-sm outline-none focus:border-[#145943] focus:ring-4 focus:ring-[#145943]/10">
                        </label>
                        <label class="block text-sm font-semibold text-[#4d5c56]">
                            Nagad
                            <input type="tel" name="nagad_merchant_number" value="{{ old('nagad_merchant_number', $sensitiveSettings['nagad_merchant_number']) }}" inputmode="numeric" pattern="01[3-9][0-9]{8}" maxlength="11" class="mt-2 h-11 w-full rounded-xl border border-[#dfe3cf] bg-white px-3 text-sm outline-none focus:border-[#145943] focus:ring-4 focus:ring-[#145943]/10">
                        </label>
                    </div>
                </fieldset>

                <fieldset class="space-y-4 border-t border-[#eef0ea] pt-5">
                    <legend class="text-sm font-bold text-[#173b32]">Authentication policy</legend>
                    <label class="flex min-h-11 items-center gap-3 text-sm font-semibold text-[#4d5c56]">
                        <input type="hidden" name="security_admin_2fa_required" value="0">
                        <input type="checkbox" name="security_admin_2fa_required" value="1" {{ $sensitiveSettings['security_admin_2fa_required'] ? 'checked' : '' }} class="h-4 w-4 accent-[#145943]">
                        Require authenticator verification for admin access
                    </label>
                    <div class="grid gap-4 sm:grid-cols-3">
                        <label class="block text-sm font-semibold text-[#4d5c56]">
                            Failed login attempts
                            <input type="number" name="security_login_attempts" min="3" max="10" value="{{ old('security_login_attempts', $sensitiveSettings['security_login_attempts']) }}" required class="mt-2 h-11 w-full rounded-xl border border-[#dfe3cf] bg-white px-3 text-sm">
                        </label>
                        <label class="block text-sm font-semibold text-[#4d5c56]">
                            Lockout minutes
                            <input type="number" name="security_login_lockout_minutes" min="1" max="60" value="{{ old('security_login_lockout_minutes', $sensitiveSettings['security_login_lockout_minutes']) }}" required class="mt-2 h-11 w-full rounded-xl border border-[#dfe3cf] bg-white px-3 text-sm">
                        </label>
                        <label class="block text-sm font-semibold text-[#4d5c56]">
                            Minimum password length
                            <input type="number" name="security_password_min_length" min="12" max="32" value="{{ old('security_password_min_length', $sensitiveSettings['security_password_min_length']) }}" required class="mt-2 h-11 w-full rounded-xl border border-[#dfe3cf] bg-white px-3 text-sm">
                        </label>
                    </div>
                </fieldset>

                <fieldset class="border-t border-[#eef0ea] pt-5">
                    <legend class="text-sm font-bold text-[#173b32]">Session protections (deployment configured)</legend>
                    <dl class="mt-3 grid gap-3 text-sm sm:grid-cols-2 lg:grid-cols-4">
                        <div><dt class="text-[#738078]">Lifetime</dt><dd class="mt-1 font-semibold text-[#173b32]">{{ $sensitiveSettings['session_lifetime'] }} minutes</dd></div>
                        <div><dt class="text-[#738078]">Secure cookie</dt><dd class="mt-1 font-semibold text-[#173b32]">{{ $sensitiveSettings['session_secure'] ? 'Enabled' : 'Disabled' }}</dd></div>
                        <div><dt class="text-[#738078]">HTTP-only cookie</dt><dd class="mt-1 font-semibold text-[#173b32]">{{ $sensitiveSettings['session_http_only'] ? 'Enabled' : 'Disabled' }}</dd></div>
                        <div><dt class="text-[#738078]">SameSite</dt><dd class="mt-1 font-semibold capitalize text-[#173b32]">{{ $sensitiveSettings['session_same_site'] }}</dd></div>
                    </dl>
                </fieldset>

                <div class="flex justify-end border-t border-[#eef0ea] pt-5">
                    <button type="submit" class="min-h-11 rounded-xl bg-[#145943] px-5 py-3 text-sm font-bold text-white transition hover:bg-[#0f4d3a]">Save payment &amp; security</button>
                </div>
            </form>
        </section>
    @endif
@endsection