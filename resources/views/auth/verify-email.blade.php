<x-layouts.app>
    <x-slot:title>
        Verify Email — {{ config('app.name') }}
    </x-slot:title>

    <section class="bg-[#fbfaf4] px-4 py-12 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-lg rounded-[4px] border border-[#e4e3d8] bg-white p-6 sm:p-8">
            <p class="text-xs font-bold uppercase tracking-[0.2em] text-[#a48335]">Account security</p>
            <h1 class="mt-2 text-2xl font-extrabold text-[#145943]">Verify your email</h1>
            <p class="mt-3 text-sm leading-6 text-[#4d5c56]">We sent a verification link to {{ auth()->user()->email }}. Verify the address before continuing to your account.</p>

            @if (session('status'))
                <p class="mt-4 rounded-[4px] bg-[#edf2e4] px-4 py-3 text-sm font-semibold text-[#145943]">{{ session('status') }}</p>
            @endif

            <div class="mt-6 flex flex-wrap items-center gap-3">
                <form method="POST" action="{{ route('verification.send') }}">
                    @csrf
                    <button type="submit" class="rounded-[4px] bg-[#145943] px-5 py-2.5 text-sm font-bold text-white transition hover:bg-[#a48335]">Resend verification email</button>
                </form>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="rounded-[4px] border border-[#dfe3cf] px-5 py-2.5 text-sm font-semibold text-[#4d5c56] transition hover:bg-[#f5f4ec]">Sign out</button>
                </form>
            </div>
        </div>
    </section>
</x-layouts.app>
