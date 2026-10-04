<x-layouts.app>
    <x-slot:title>{{ $title ?? 'My Account | '.config('app.name') }}</x-slot:title>

    <section class="min-h-[calc(100vh-80px)] bg-[#f7f7ef]">
        <div class="mx-auto min-h-[calc(100vh-80px)] max-w-[1440px] xl:grid xl:grid-cols-[248px_minmax(0,1fr)]">
            <aside class="border-b border-[#dedfcf] bg-[#f0f2e8] px-4 py-5 sm:px-6 xl:sticky xl:top-0 xl:h-[calc(100vh-80px)] xl:border-b-0 xl:border-r xl:border-[#dedfcf] xl:px-5">
                <div class="flex items-center gap-3 border-b border-[#dfe3cf] pb-5">
                    @if (auth()->user()?->profile_photo_path)
                        <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url(auth()->user()->profile_photo_path) }}" alt="" class="h-11 w-11 rounded-full border border-white object-cover shadow-sm">
                    @else
                        <div class="grid h-11 w-11 shrink-0 place-items-center rounded-full bg-[#145943] text-sm font-bold text-white">
                            {{ strtoupper(substr(auth()->user()?->name ?? 'U', 0, 1)) }}
                        </div>
                    @endif
                    <div class="min-w-0">
                        <p class="text-[10px] font-bold uppercase tracking-[0.16em] text-[#a48335]">Customer account</p>
                        <p class="mt-1 truncate text-sm font-extrabold text-[#173b32]">{{ auth()->user()?->name }}</p>
                        <p class="truncate text-xs text-[#738078]">{{ auth()->user()?->email }}</p>
                    </div>
                </div>

                <nav aria-label="Account navigation" class="mt-5 flex gap-2 overflow-x-auto pb-1 text-sm xl:flex-col xl:overflow-visible">
                    <a href="{{ route('account.profile') }}" @if (request()->routeIs('account.profile')) aria-current="page" @endif class="flex min-h-11 shrink-0 items-center justify-between gap-5 rounded-xl px-3 py-2.5 transition-colors {{ request()->routeIs('account.profile') ? 'bg-[#edf2e4] font-bold text-[#145943]' : 'font-semibold text-[#4d5c56] hover:bg-[#f5f4ec]' }}">
                        <span>Profile overview</span>
                        <span aria-hidden="true">›</span>
                    </a>
                    <a href="{{ route('account.orders') }}" @if (request()->routeIs('account.orders', 'account.orders.show')) aria-current="page" @endif class="flex min-h-11 shrink-0 items-center justify-between gap-5 rounded-xl px-3 py-2.5 transition-colors {{ request()->routeIs('account.orders', 'account.orders.show') ? 'bg-[#edf2e4] font-bold text-[#145943]' : 'font-semibold text-[#4d5c56] hover:bg-[#f5f4ec]' }}">
                        <span>My orders</span>
                        <span aria-hidden="true">›</span>
                    </a>
                    <a href="{{ route('account.orders.history') }}" @if (request()->routeIs('account.orders.history')) aria-current="page" @endif class="flex min-h-11 shrink-0 items-center justify-between gap-5 rounded-xl px-3 py-2.5 transition-colors {{ request()->routeIs('account.orders.history') ? 'bg-[#edf2e4] font-bold text-[#145943]' : 'font-semibold text-[#4d5c56] hover:bg-[#f5f4ec]' }}">
                        <span>Order history</span>
                        <span aria-hidden="true">›</span>
                    </a>
                    <a href="{{ route('account.security') }}" @if (request()->routeIs('account.security*')) aria-current="page" @endif class="flex min-h-11 shrink-0 items-center justify-between gap-5 rounded-xl px-3 py-2.5 transition-colors {{ request()->routeIs('account.security*') ? 'bg-[#edf2e4] font-bold text-[#145943]' : 'font-semibold text-[#4d5c56] hover:bg-[#f5f4ec]' }}">
                        <span>Security</span>
                        <span aria-hidden="true">›</span>
                    </a>
                </nav>

                <a href="{{ route('home') }}" class="mt-5 hidden min-h-11 items-center gap-2 border-t border-[#dfe3cf] pt-5 text-sm font-bold text-[#145943] hover:text-[#a48335] xl:flex">
                    <span aria-hidden="true">←</span>
                    <span>Continue shopping</span>
                </a>
            </aside>

            <main class="min-w-0 p-4 sm:p-6 xl:p-8">
                {{ $slot }}
            </main>
        </div>
    </section>
</x-layouts.app>