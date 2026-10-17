<x-layouts.app>
    <x-slot:title>Contact | {{ config('app.name') }}</x-slot:title>
    <x-slot:metaDescription>Contact {{ config('app.name') }} for order and shopping support.</x-slot:metaDescription>
    <x-slot:canonicalUrl>{{ route('contact') }}</x-slot:canonicalUrl>

    <section>
        <div class="bg-[#eeeeee] px-4 py-10 sm:px-6 sm:py-12 lg:px-8">
            <h1 class="text-center text-[30px] leading-[1.1] font-bold text-[#252a28] sm:text-[34px]">Contact</h1>
            <p class="mt-2 text-center text-sm text-[#59615e]">যোগাযোগ</p>
        </div>

        <div class="min-h-[42vh] bg-[#fbfaf7] px-4 pb-14 pt-5 sm:px-6 lg:px-8">
            <div class="mx-auto max-w-6xl">
                <nav class="flex items-center gap-2 border-b border-[#e7e4dc] pb-4 text-[13px] leading-normal font-normal text-[#59615e]" aria-label="Breadcrumb">
                    <a href="{{ route('home') }}" class="hover:text-[#c96b2c]">Home</a>
                    <span aria-hidden="true">›</span>
                    <span class="text-[#252a28]">Contact</span>
                </nav>

                @if (filled($contactContent))
                    <article class="mt-8 whitespace-pre-line text-sm leading-[1.86] text-[#737a76]">{{ $contactContent }}</article>
                @else
                    <p class="mt-8 rounded-lg border border-[#e7e4dc] bg-white p-5 text-sm leading-[1.86] text-[#59615e]" role="status">
                        Contact details have not been published yet. Please check back soon.
                    </p>
                @endif

                @if (filled($callUrl) || filled($whatsappUrl))
                    <div class="mt-6 flex flex-wrap gap-3">
                        @if (filled($callUrl))
                            <a href="{{ $callUrl }}" class="inline-flex min-h-11 items-center rounded-lg bg-[#145943] px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-[#0f4d3a]">
                                Call Support{{ filled($whatsappNumber) ? ' · '.$whatsappNumber : '' }}
                            </a>
                        @endif
                        @if (filled($whatsappUrl))
                            <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener noreferrer" class="inline-flex min-h-11 items-center rounded-lg border border-[#145943] px-4 py-2.5 text-sm font-semibold text-[#145943] transition hover:bg-[#edf2e4]">
                                Message on WhatsApp
                            </a>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </section>
</x-layouts.app>