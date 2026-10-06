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
            <h2 class="text-xl font-extrabold text-[#145943]">Site Logo</h2>
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

                    </div>
                </div>

                <div class="flex items-center justify-between gap-4 border-t border-[#eef0ea] pt-5">
                    <p data-branding-upload-status role="status" aria-live="polite" class="text-sm text-[#66736d]"></p>
                    <button type="submit" data-branding-submit class="min-h-11 rounded-xl bg-[#145943] px-5 py-3 text-sm font-bold text-white transition hover:bg-[#0f4d3a] disabled:cursor-wait disabled:opacity-60">Save branding</button>
                </div>
            </form>
        @else
            @if ($branding['logo_path'])
                <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($branding['logo_path']) }}" alt="Current site logo" class="max-h-16 max-w-48 object-contain">
            @endif
        @endcan
    </section>

    <section class="mt-6 rounded-2xl border border-[#e4e3d8] bg-white p-5 shadow-sm sm:p-7">
        <div class="mb-6">
            <h2 class="text-xl font-extrabold text-[#145943]">Homepage Side Advertisement</h2>
            <p class="mt-1 text-sm text-[#738078]">Shown beside the hero on desktop screens when both the image and link are set.</p>
        </div>

        @can('settings.update')
            <form method="POST" action="{{ route('admin.settings.branding.update') }}" enctype="multipart/form-data" class="grid gap-5 lg:grid-cols-2">
                @csrf
                @method('PATCH')

                <label class="block text-sm font-semibold text-[#4d5c56]">
                    Advertisement image
                    <input type="file" name="home_ad_image" accept="image/jpeg,image/png,image/webp" class="mt-2 block w-full rounded-xl border border-[#dfe3cf] bg-white px-3 py-3 text-sm file:mr-3 file:rounded-lg file:border-0 file:bg-[#145943] file:px-3 file:py-2 file:font-bold file:text-white">
                    <span class="mt-1 block text-xs font-normal text-[#738078]">JPG, PNG or WebP, up to 8 MB.</span>
                </label>

                <label class="block text-sm font-semibold text-[#4d5c56]">
                    Advertisement link
                    <input type="url" name="home_ad_url" value="{{ old('home_ad_url', $branding['home_ad_url']) }}" maxlength="2048" placeholder="https://example.com/offer" class="mt-2 h-12 w-full rounded-xl border border-[#dfe3cf] bg-white px-3 text-sm outline-none focus:border-[#145943] focus:ring-4 focus:ring-[#145943]/10">
                </label>

                @if ($branding['home_ad_image_path'])
                    <div class="flex items-center justify-between gap-4 rounded-xl border border-[#e4e3d8] bg-[#fbfaf4] p-3 lg:col-span-2">
                        <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($branding['home_ad_image_path']) }}" alt="Current homepage advertisement" class="h-20 w-32 rounded-lg object-cover">
                        <label class="flex items-center gap-2 text-sm text-[#4d5c56]">
                            <input type="checkbox" name="remove_home_ad_image" value="1" class="h-4 w-4 accent-[#145943]">
                            Remove advertisement image
                        </label>
                    </div>
                @endif

                <div class="flex justify-end border-t border-[#eef0ea] pt-5 lg:col-span-2">
                    <button type="submit" class="min-h-11 rounded-xl bg-[#145943] px-5 py-3 text-sm font-bold text-white transition hover:bg-[#0f4d3a]">Save advertisement</button>
                </div>
            </form>
        @elseif ($branding['home_ad_image_path'])
            <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($branding['home_ad_image_path']) }}" alt="Current homepage advertisement" class="h-20 w-32 rounded-lg object-cover">
        @endif
    </section>

    <section class="mt-6 rounded-2xl border border-[#e4e3d8] bg-white p-5 shadow-sm sm:p-7">
        <div class="mb-6">
            <h2 class="text-xl font-extrabold text-[#145943]">Homepage Hero Images</h2>
        </div>

        @can('settings.update')
            <form method="POST" action="{{ route('admin.settings.hero-banners.store') }}" enctype="multipart/form-data" class="mb-6 grid gap-4 border-b border-[#eef0ea] pb-6 sm:grid-cols-[minmax(0,1fr)_9rem] lg:grid-cols-[minmax(0,1.15fr)_8rem_minmax(0,1.35fr)_auto] lg:items-start">
                @csrf
                <div data-hero-banner-upload-container class="min-w-0">
                    <label for="hero-banner-image-new" class="block text-sm font-semibold text-[#4d5c56]">Add image</label>
                    <label for="hero-banner-image-new" class="relative mt-2 flex h-11 cursor-pointer items-center gap-3 overflow-hidden rounded-xl border border-dashed border-[#dfe3cf] bg-white px-3 transition hover:border-[#145943] hover:bg-[#f7f9f3] focus-within:border-[#145943] focus-within:ring-2 focus-within:ring-[#145943]/20">
                        <svg class="pointer-events-none h-5 w-5 shrink-0 text-[#145943]/70" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                        <span data-hero-banner-upload-name class="pointer-events-none truncate text-sm text-[#738078]">Choose a hero image</span>
                        <input id="hero-banner-image-new" type="file" name="image" accept="image/jpeg,image/png,image/webp" required data-hero-banner-upload class="absolute inset-0 h-full w-full cursor-pointer opacity-0">
                    </label>
                </div>
                <label class="block text-sm font-semibold text-[#4d5c56]">
                    Position
                    <input type="number" name="sort_order" min="0" max="65535" class="mt-2 h-11 w-full rounded-xl border border-[#dfe3cf] bg-white px-3 text-sm">
                </label>
                <div>
                    <label for="hero-target-search-new" class="block text-sm font-semibold text-[#4d5c56]">Image destination</label>
                    <input type="hidden" name="target" value="{{ old('target') }}" data-hero-target-value>
                    <input id="hero-target-search-new" type="search" value="" placeholder="Search category, product, or variant..." autocomplete="off" role="combobox" aria-autocomplete="list" aria-expanded="false" aria-controls="hero-target-results-new" data-hero-target-search data-search-url="{{ route('admin.settings.hero-targets') }}" class="mt-2 h-11 w-full rounded-xl border border-[#dfe3cf] bg-white px-3 text-sm outline-none focus:border-[#145943] focus:ring-4 focus:ring-[#145943]/10">
                    <div id="hero-target-results-new" data-hero-target-results role="listbox" class="mt-1 hidden max-h-64 overflow-y-auto rounded-xl border border-[#dfe3cf] bg-white shadow-lg"></div>
                    <div class="mt-2 grid grid-cols-[minmax(0,1fr)_auto] items-center gap-2">
                        <p data-hero-target-selected class="min-w-0 text-xs text-[#66736d]" aria-live="polite">No destination selected; clicking the image opens the homepage.</p>
                        <button type="button" data-clear-hero-target class="shrink-0 rounded-lg border border-[#dfe3cf] px-3 py-1.5 text-xs font-semibold text-[#145943]">Clear</button>
                    </div>
                </div>
                <button type="submit" class="min-h-11 rounded-xl bg-[#145943] px-5 py-3 text-sm font-bold text-white transition hover:bg-[#0f4d3a] lg:mt-7">Add image</button>
                <div data-hero-banner-preview-wrap hidden class="flex items-center gap-3 rounded-xl border border-[#dfe3cf] bg-white p-2 sm:col-span-2 lg:col-span-4">
                    <img data-hero-banner-preview alt="Selected hero image preview" class="h-16 w-16 shrink-0 rounded-lg object-cover">
                    <div class="min-w-0">
                        <p class="text-sm font-semibold text-[#173b32]">Selected for upload</p>
                        <p data-hero-banner-preview-name class="truncate text-xs text-[#738078]"></p>
                    </div>
                </div>
            </form>

            <div class="space-y-4">
                @forelse ($heroBanners as $heroBanner)
                    <div class="grid gap-4 border-b border-[#eef0ea] pb-4 last:border-0 last:pb-0 lg:grid-cols-[16rem_1fr_auto] lg:items-center">
                        <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($heroBanner->image_path) }}" alt="" class="aspect-[16/7] w-full rounded-lg object-cover">
                        <form id="hero-banner-update-{{ $heroBanner->id }}" method="POST" action="{{ route('admin.settings.hero-banners.update', $heroBanner) }}" enctype="multipart/form-data" class="grid gap-3 sm:grid-cols-[1fr_9rem] sm:items-start">
                            @csrf
                            @method('PUT')
                            <div data-hero-banner-upload-container class="min-w-0">
                                <label for="hero-banner-image-{{ $heroBanner->id }}" class="block text-sm font-semibold text-[#4d5c56]">Replace image</label>
                                <label for="hero-banner-image-{{ $heroBanner->id }}" class="relative mt-2 flex h-11 cursor-pointer items-center gap-3 overflow-hidden rounded-xl border border-dashed border-[#dfe3cf] bg-white px-3 transition hover:border-[#145943] hover:bg-[#f7f9f3] focus-within:border-[#145943] focus-within:ring-2 focus-within:ring-[#145943]/20">
                                    <svg class="pointer-events-none h-5 w-5 shrink-0 text-[#145943]/70" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                    <span data-hero-banner-upload-name class="pointer-events-none truncate text-sm text-[#738078]">Choose a replacement image</span>
                                    <input id="hero-banner-image-{{ $heroBanner->id }}" type="file" name="image" accept="image/jpeg,image/png,image/webp" data-hero-banner-upload class="absolute inset-0 h-full w-full cursor-pointer opacity-0">
                                </label>
                                <div data-hero-banner-preview-wrap hidden class="mt-2 flex items-center gap-3 rounded-xl border border-[#dfe3cf] bg-white p-2">
                                    <img data-hero-banner-preview alt="Selected replacement image preview" class="h-16 w-16 shrink-0 rounded-lg object-cover">
                                    <div class="min-w-0">
                                        <p class="text-sm font-semibold text-[#173b32]">Selected for upload</p>
                                        <p data-hero-banner-preview-name class="truncate text-xs text-[#738078]"></p>
                                    </div>
                                </div>
                            </div>
                            <label class="block text-sm font-semibold text-[#4d5c56]">
                                Position
                                <input type="number" name="sort_order" value="{{ $heroBanner->sort_order }}" min="0" max="65535" required class="mt-2 h-11 w-full rounded-xl border border-[#dfe3cf] bg-white px-3 text-sm">
                            </label>
                            <div class="sm:col-span-2">
                                <label for="hero-target-search-{{ $heroBanner->id }}" class="block text-sm font-semibold text-[#4d5c56]">Image destination</label>
                                <input type="hidden" name="target" value="{{ $heroBanner->target }}" data-hero-target-value>
                                <input id="hero-target-search-{{ $heroBanner->id }}" type="search" value="{{ $heroBannerTargetLabels[$heroBanner->id] ?? '' }}" placeholder="Search category, product, or variant..." autocomplete="off" role="combobox" aria-autocomplete="list" aria-expanded="false" aria-controls="hero-target-results-{{ $heroBanner->id }}" data-hero-target-search data-search-url="{{ route('admin.settings.hero-targets') }}" class="mt-2 h-11 w-full rounded-xl border border-[#dfe3cf] bg-white px-3 text-sm outline-none focus:border-[#145943] focus:ring-4 focus:ring-[#145943]/10">
                                <div id="hero-target-results-{{ $heroBanner->id }}" data-hero-target-results role="listbox" class="mt-1 hidden max-h-64 overflow-y-auto rounded-xl border border-[#dfe3cf] bg-white shadow-lg"></div>
                                <div class="mt-2 flex items-center justify-between gap-3">
                                    <p data-hero-target-selected class="min-w-0 truncate text-xs text-[#66736d]" aria-live="polite">{{ $heroBannerTargetLabels[$heroBanner->id] ? 'Selected: '.$heroBannerTargetLabels[$heroBanner->id] : 'No destination selected; clicking the image opens the homepage.' }}</p>
                                    <button type="button" data-clear-hero-target class="shrink-0 rounded-lg border border-[#dfe3cf] px-3 py-1.5 text-xs font-semibold text-[#145943]">Clear</button>
                                </div>
                            </div>
                        </form>
                        <div class="flex gap-2">
                            <button type="submit" form="hero-banner-update-{{ $heroBanner->id }}" class="min-h-11 rounded-xl border border-[#dfe3cf] px-4 text-sm font-bold text-[#145943]">Save</button>
                            <form method="POST" action="{{ route('admin.settings.hero-banners.destroy', $heroBanner) }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="min-h-11 rounded-xl border border-red-200 px-4 text-sm font-bold text-red-700">Delete</button>
                            </form>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-[#66736d]">No hero images added.</p>
                @endforelse
            </div>
        @else
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($heroBanners as $heroBanner)
                    <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($heroBanner->image_path) }}" alt="" class="aspect-[16/7] w-full rounded-lg object-cover">
                @endforeach
            </div>
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
                    <legend class="text-sm font-bold text-[#173b32]">Delivery charges</legend>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <label class="block text-sm font-semibold text-[#4d5c56]">
                            Inside Dhaka Delivery (৳)
                            <input type="number" name="delivery_fee_inside_dhaka" min="0" max="100000" step="0.01" value="{{ old('delivery_fee_inside_dhaka', $sensitiveSettings['delivery_fee_inside_dhaka']) }}" required class="mt-2 h-11 w-full rounded-xl border border-[#dfe3cf] bg-white px-3 text-sm outline-none focus:border-[#145943] focus:ring-4 focus:ring-[#145943]/10">
                        </label>
                        <label class="block text-sm font-semibold text-[#4d5c56]">
                            Outside Dhaka Delivery (৳)
                            <input type="number" name="delivery_fee_outside_dhaka" min="0" max="100000" step="0.01" value="{{ old('delivery_fee_outside_dhaka', $sensitiveSettings['delivery_fee_outside_dhaka']) }}" required class="mt-2 h-11 w-full rounded-xl border border-[#dfe3cf] bg-white px-3 text-sm outline-none focus:border-[#145943] focus:ring-4 focus:ring-[#145943]/10">
                        </label>
                    </div>
                </fieldset>

                <fieldset class="space-y-4">
                    <legend class="text-sm font-bold text-[#173b32]">Mobile wallet merchant numbers</legend>
                    <div class="grid gap-4 sm:grid-cols-3">
                        <label class="block text-sm font-semibold text-[#4d5c56]">
                            bKash
                            <input type="tel" name="bkash_merchant_number" value="{{ old('bkash_merchant_number', $sensitiveSettings['bkash_merchant_number']) }}" inputmode="numeric" pattern="01[3-9][0-9]{8}" maxlength="11" class="mt-2 h-11 w-full rounded-xl border border-[#dfe3cf] bg-white px-3 text-sm outline-none focus:border-[#145943] focus:ring-4 focus:ring-[#145943]/10">
                        </label>
                        <label class="block text-sm font-semibold text-[#4d5c56]">
                            Nagad
                            <input type="tel" name="nagad_merchant_number" value="{{ old('nagad_merchant_number', $sensitiveSettings['nagad_merchant_number']) }}" inputmode="numeric" pattern="01[3-9][0-9]{8}" maxlength="11" class="mt-2 h-11 w-full rounded-xl border border-[#dfe3cf] bg-white px-3 text-sm outline-none focus:border-[#145943] focus:ring-4 focus:ring-[#145943]/10">
                        </label>
                        <label class="block text-sm font-semibold text-[#4d5c56]">
                            Rocket
                            <input type="tel" name="rocket_merchant_number" value="{{ old('rocket_merchant_number', $sensitiveSettings['rocket_merchant_number']) }}" inputmode="numeric" pattern="01[3-9][0-9]{8}" maxlength="11" class="mt-2 h-11 w-full rounded-xl border border-[#dfe3cf] bg-white px-3 text-sm outline-none focus:border-[#145943] focus:ring-4 focus:ring-[#145943]/10">
                        </label>
                    </div>
                </fieldset>

                <fieldset class="space-y-4 border-t border-[#eef0ea] pt-5">
                    <legend class="text-sm font-bold text-[#173b32]">About Us &amp; Contact</legend>
                    <label class="block text-sm font-semibold text-[#4d5c56]">
                        About Us article
                        <textarea name="about_us_article" rows="8" maxlength="10000" class="mt-2 w-full rounded-xl border border-[#dfe3cf] bg-white px-3 py-3 text-sm outline-none focus:border-[#145943] focus:ring-4 focus:ring-[#145943]/10">{{ old('about_us_article', $sensitiveSettings['about_us_article']) }}</textarea>
                    </label>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <label class="block text-sm font-semibold text-[#4d5c56]">
                            WhatsApp number
                            <input type="tel" name="whatsapp_number" value="{{ old('whatsapp_number', $sensitiveSettings['whatsapp_number']) }}" maxlength="30" class="mt-2 h-11 w-full rounded-xl border border-[#dfe3cf] bg-white px-3 text-sm outline-none focus:border-[#145943] focus:ring-4 focus:ring-[#145943]/10">
                        </label>
                        <label class="block text-sm font-semibold text-[#4d5c56]">
                            WhatsApp link
                            <input type="url" name="whatsapp_url" value="{{ old('whatsapp_url', $sensitiveSettings['whatsapp_url']) }}" maxlength="2048" placeholder="https://wa.me/8801XXXXXXXXX" class="mt-2 h-11 w-full rounded-xl border border-[#dfe3cf] bg-white px-3 text-sm outline-none focus:border-[#145943] focus:ring-4 focus:ring-[#145943]/10">
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