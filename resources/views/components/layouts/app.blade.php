<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

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

<body class="min-h-screen bg-[#fbfaf4] text-[#173b32] antialiased" data-admin-area="{{ request()->routeIs('admin*') ? 'true' : 'false' }}">

    <x-header />

    <main>
        {{ $slot }}
    </main>

    <x-footer />

    {{ $scripts ?? '' }}

</body>

</html>