<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    <meta name="theme-color" content="#145943">

    <title>{{ $title ?? config('app.name') }}</title>

    @vite([
        'resources/css/app.css',
        'resources/js/app.js',
    ])

    {{ $head ?? '' }}
</head>

<body class="min-h-screen bg-[#fbfaf4] text-[#173b32] antialiased">

    <x-header />

    <main>
        {{ $slot }}
    </main>

    <x-footer />

    {{ $scripts ?? '' }}

</body>

</html>
