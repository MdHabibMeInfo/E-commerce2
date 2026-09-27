<x-layouts.app>

    <x-slot:title>
        {{ $product['name'] }} — {{ config('app.name') }}
    </x-slot:title>

    <section class="bg-[#fbfaf4]">
        <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8 lg:py-14">
            <div class="mb-6 text-sm text-[#738078]">
                <a href="{{ route('home') }}" class="font-semibold text-[#145943] hover:text-[#a48335]">Home</a>
                <span class="px-2">/</span>
                <span>{{ $product['name'] }}</span>
            </div>

            <div class="grid gap-8 rounded-2xl border border-[#e3e2d9] bg-white p-5 shadow-sm md:grid-cols-2 md:p-7">
                <div class="grid aspect-square place-items-center overflow-hidden rounded-xl bg-[#f1f4e5] text-[9rem]">
                    <span>{{ $product['emoji'] }}</span>
                </div>

                <div class="flex flex-col justify-center">
                    <p class="text-xs font-bold uppercase tracking-[0.18em] text-[#a48335]">Featured Product</p>
                    <h1 class="mt-2 text-3xl font-extrabold tracking-tight text-[#145943] sm:text-4xl">{{ $product['name'] }}</h1>
                    <p class="mt-4 leading-7 text-[#66736d]">{{ $product['description'] }}</p>
                    <p class="mt-6 text-2xl font-extrabold text-[#145943]">{{ $product['price'] }}</p>
                    <button type="button" class="mt-7 inline-flex w-full items-center justify-center rounded-xl bg-[#145943] px-6 py-3.5 text-sm font-bold text-white transition-colors hover:bg-[#b28d35] sm:w-fit">Add to Cart</button>
                </div>
            </div>
        </div>
    </section>

</x-layouts.app>
