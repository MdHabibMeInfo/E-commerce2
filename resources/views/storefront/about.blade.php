<x-layouts.app>
    <x-slot:title>About Us | {{ config('app.name') }}</x-slot:title>
    <x-slot:metaDescription>Learn more about {{ config('app.name') }}.</x-slot:metaDescription>
    <x-slot:canonicalUrl>{{ route('about') }}</x-slot:canonicalUrl>

    <section>
        <div class="bg-[#eeeeee] px-4 py-10 sm:px-6 sm:py-12 lg:px-8">
            <h1 class="text-center text-[30px] leading-[1.1] font-bold text-[#252a28] sm:text-[34px]">About Us</h1>
        </div>

        <div class="min-h-[42vh] bg-[#fbfaf7] px-4 pb-14 pt-5 sm:px-6 lg:px-8">
            <div class="mx-auto max-w-6xl">
                <nav class="flex items-center gap-2 border-b border-[#e7e4dc] pb-4 text-[13px] leading-[1.5] font-normal text-[#59615e]" aria-label="Breadcrumb">
                    <a href="{{ route('home') }}" class="hover:text-[#c96b2c]">Home</a>
                    <span aria-hidden="true">›</span>
                    <span class="text-[#252a28]">About Us</span>
                </nav>

                @if (filled($aboutUsArticle))
                    <article class="mt-8 whitespace-pre-line text-sm leading-[1.86] text-[#737a76]">{{ $aboutUsArticle }}</article>
                @else
                    <p class="mt-8 rounded-lg border border-[#e7e4dc] bg-white p-5 text-sm leading-[1.86] text-[#59615e]" role="status">
                        This page has not been published yet. Please check back soon.
                    </p>
                @endif
            </div>
        </div>
    </section>
</x-layouts.app>
