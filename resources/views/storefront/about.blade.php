<x-layouts.app>
    <x-slot:title>About Us | {{ config('app.name') }}</x-slot:title>
    <x-slot:metaDescription>Learn more about {{ config('app.name') }}.</x-slot:metaDescription>
    <x-slot:canonicalUrl>{{ route('about') }}</x-slot:canonicalUrl>

    <section class="min-h-[50vh] bg-white px-4 py-12 sm:px-6 sm:py-16 lg:px-8">
        <div class="mx-auto max-w-4xl">
            <a href="{{ route('home') }}" class="text-sm font-semibold text-[#59615e] hover:text-[#c96b2c]">Home</a>
            <h1 class="mt-6 text-3xl font-bold text-[#252a28] sm:text-4xl">About Us</h1>
            @if (filled($aboutUsArticle))
                <article class="mt-8 whitespace-pre-line text-base leading-8 text-[#505855]">{{ $aboutUsArticle }}</article>
            @else
                <p class="mt-8 text-base leading-8 text-[#737a76]">About Us information will be added soon.</p>
            @endif
        </div>
    </section>
</x-layouts.app>
