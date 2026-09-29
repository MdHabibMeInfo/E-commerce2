<x-layouts.app>
    <x-slot:title>Verify management sign-in</x-slot:title>

    <section class="bg-[#fbfaf4] px-4 py-12 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-md rounded-[4px] border border-[#e4e3d8] bg-white p-6 sm:p-8">
            <p class="text-xs font-bold uppercase tracking-[0.2em] text-[#a48335]">Management sign-in</p>
            <h1 class="mt-2 text-2xl font-extrabold text-[#145943]">Verify it is you</h1>
            <p class="mt-3 text-sm leading-6 text-[#4d5c56]">Enter a code from your authenticator app or one unused recovery code.</p>

            @if ($errors->any())
                <p class="mt-4 rounded-[4px] bg-red-50 px-4 py-3 text-sm text-red-800">{{ $errors->first() }}</p>
            @endif

            <form method="POST" action="{{ route('two-factor.verify') }}" class="mt-6 space-y-4">
                @csrf
                <label for="challenge-code" class="block text-sm font-semibold text-[#4d5c56]">Authenticator or recovery code</label>
                <input id="challenge-code" name="code" inputmode="text" autocomplete="one-time-code" required autofocus class="h-12 w-full rounded-[4px] border border-[#dfe3cf] px-3 font-mono text-lg tracking-widest text-[#173b32] outline-none focus:border-[#145943] focus:ring-2 focus:ring-[#145943]/10">
                <button type="submit" class="h-11 w-full rounded-[4px] bg-[#145943] text-sm font-bold text-white transition hover:bg-[#a48335]">Continue</button>
            </form>

            <form method="POST" action="{{ route('logout') }}" class="mt-4 text-center">
                @csrf
                <button type="submit" class="text-sm font-semibold text-[#145943] hover:text-[#a48335]">Cancel and sign out</button>
            </form>
        </div>
    </section>
</x-layouts.app>