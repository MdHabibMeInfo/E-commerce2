<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
@php
    $isAdminArea = request()->routeIs('admin*')
        || (auth()->user()?->can('access-admin') && request()->routeIs('account.orders*', 'account.security*'));
    $configuredMetaPixelId = (string) config('services.meta.pixel_id');
    $metaPixelId = $isAdminArea || preg_match('/^\d+$/', $configuredMetaPixelId) !== 1
        ? null
        : $configuredMetaPixelId;
@endphp

<head>
    <meta charset="utf-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    <meta
        name="theme-color"
        content="#145943"
    >

    <meta name="csrf-token" content="{{ csrf_token() }}">

    <meta
        name="description"
        content="{{ $metaDescription ?? 'Discover fresh, high-quality food and grocery products delivered to your door.' }}"
    >

    <link
        rel="canonical"
        href="{{ $canonicalUrl ?? url()->current() }}"
    >

    <title>{{ $title ?? config('app.name') }}</title>

    @vite([
        'resources/css/app.css',
        'resources/js/app.js',
    ])

    {{ $head ?? '' }}
</head>

<body class="min-h-screen bg-[#fbfaf4] text-[#173b32] antialiased" data-admin-area="{{ $isAdminArea ? 'true' : 'false' }}" data-meta-pixel-id="{{ $metaPixelId }}">

    <x-header />

    <main>
        {{ $slot }}
    </main>

    <x-footer />

    @if (filled($metaPixelId))
        <section data-meta-consent-banner class="hidden fixed inset-x-4 bottom-4 z-[100] mx-auto flex max-w-4xl flex-col gap-4 rounded-xl border border-[#dfe3cf] bg-white p-4 text-sm text-[#173b32] shadow-xl sm:flex-row sm:items-center sm:justify-between sm:p-5" aria-labelledby="meta-consent-title">
            <div class="max-w-2xl">
                <h2 id="meta-consent-title" class="font-bold">Marketing cookies</h2>
                <p class="mt-1 text-xs leading-5 text-[#59615e]">With your permission, we use Meta Pixel and Conversions API to measure ad performance. You can change this choice in Privacy settings.</p>
            </div>
            <div class="flex shrink-0 flex-wrap gap-2">
                <button type="button" data-meta-consent-choice="denied" class="rounded-lg border border-[#dfe3cf] px-3 py-2 text-xs font-semibold text-[#173b32] hover:bg-[#f5f7ee]">Reject</button>
                <button type="button" data-meta-consent-choice="granted" class="rounded-lg bg-[#145943] px-3 py-2 text-xs font-bold text-white hover:bg-[#0f4d3a]">Accept</button>
            </div>
        </section>
    @endif

    {{ $scripts ?? '' }}

</body>

</html>