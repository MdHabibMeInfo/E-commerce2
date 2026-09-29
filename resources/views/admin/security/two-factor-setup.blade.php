<x-layouts.app>
    <x-slot:title>Secure management access</x-slot:title>

    <section class="bg-[#fbfaf4] px-4 py-12 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-xl rounded-[4px] border border-[#e4e3d8] bg-white p-6 sm:p-8">
            <p class="text-xs font-bold uppercase tracking-[0.2em] text-[#a48335]">Management security</p>
            <h1 class="mt-2 text-2xl font-extrabold text-[#145943]">Set up an authenticator</h1>
            <p class="mt-3 text-sm leading-6 text-[#4d5c56]">Scan the QR code with your authenticator app, or enter the secret key manually.</p>

            @if ($errors->any())
                <p class="mt-4 rounded-[4px] bg-red-50 px-4 py-3 text-sm text-red-800">{{ $errors->first() }}</p>
            @endif

            <dl class="mt-6 space-y-3 border-y border-[#ece8dc] py-5 text-sm">
                <div>
                    <dt class="font-semibold text-[#4d5c56]">Authenticator QR code</dt>
                    <dd class="mt-2">
                        <img src="{{ $qrCodeDataUri }}" alt="Authenticator setup QR code" width="240" height="240" class="h-60 w-60 border border-[#e4e3d8] bg-white p-2">
                    </dd>
                </div>
                <div>
                    <dt class="font-semibold text-[#4d5c56]">Account</dt>
                    <dd class="mt-1 break-all font-mono text-[#173b32]">{{ auth()->user()->email }}</dd>
                </div>
                <div>
                    <dt class="font-semibold text-[#4d5c56]">Secret key</dt>
                    <dd class="mt-1 break-all select-all font-mono text-[#173b32]">{{ $secret }}</dd>
                </div>
            </dl>

            <form method="POST" action="{{ route('admin.two-factor.confirm') }}" class="mt-6 space-y-4">
                @csrf
                <label for="setup-code" class="block text-sm font-semibold text-[#4d5c56]">Authenticator code</label>
                <input id="setup-code" name="code" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]{6}" maxlength="6" required class="h-11 w-full rounded-[4px] border border-[#dfe3cf] px-3 font-mono text-lg tracking-widest text-[#173b32] outline-none focus:border-[#145943] focus:ring-2 focus:ring-[#145943]/10">
                <button type="submit" class="h-11 w-full rounded-[4px] bg-[#145943] text-sm font-bold text-white transition hover:bg-[#a48335]">Confirm authenticator</button>
            </form>
        </div>
    </section>
</x-layouts.app>