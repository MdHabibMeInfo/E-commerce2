<x-layouts.app>
    <x-slot:title>Change authenticator device</x-slot:title>

    <section class="bg-[#fbfaf4] px-4 py-12 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-xl rounded-[4px] border border-[#e4e3d8] bg-white p-6 sm:p-8">
            <p class="text-xs font-bold uppercase tracking-[0.2em] text-[#a48335]">Account security</p>
            <h1 class="mt-2 text-2xl font-extrabold text-[#145943]">Set up your new authenticator</h1>
            <p class="mt-3 text-sm leading-6 text-[#4d5c56]">Scan this QR code with the authenticator app on your new device, then enter the code it generates. Your old authenticator remains active until you confirm the new one.</p>

            @if ($errors->any())
                <p class="mt-4 rounded-[4px] bg-red-50 px-4 py-3 text-sm text-red-800">{{ $errors->first() }}</p>
            @endif

            <dl class="mt-6 space-y-3 border-y border-[#ece8dc] py-5 text-sm">
                <div>
                    <dt class="font-semibold text-[#4d5c56]">Authenticator QR code</dt>
                    <dd class="mt-2">
                        <img src="{{ $qrCodeDataUri }}" alt="New authenticator setup QR code" width="240" height="240" class="h-60 w-60 border border-[#e4e3d8] bg-white p-2">
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

            <form method="POST" action="{{ route('account.security.two-factor.replace.confirm') }}" class="mt-6 space-y-4">
                @csrf
                <label for="replacement-password" class="block text-sm font-semibold text-[#4d5c56]">Confirm your current password</label>
                <input id="replacement-password" type="password" name="current_password" autocomplete="current-password" required class="h-11 w-full rounded-[4px] border border-[#dfe3cf] px-3 text-sm text-[#173b32]">
                <label for="replacement-code" class="block text-sm font-semibold text-[#4d5c56]">Code from your new device</label>
                <input id="replacement-code" name="code" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]{6}" maxlength="6" required class="h-11 w-full rounded-[4px] border border-[#dfe3cf] px-3 font-mono text-lg tracking-widest text-[#173b32] outline-none focus:border-[#145943] focus:ring-2 focus:ring-[#145943]/10">
                <button type="submit" class="h-11 w-full rounded-[4px] bg-[#145943] text-sm font-bold text-white transition hover:bg-[#a48335]">Confirm new authenticator</button>
            </form>

            <p class="mt-4 text-sm leading-6 text-[#66736d]">This setup expires after 10 minutes. After confirmation, the old authenticator and recovery codes are invalidated and new recovery codes are shown once. Save them somewhere safe.
                @if (config('session.driver') === 'database')
                    Other sessions and access tokens are signed out.
                @else
                    Access tokens are revoked; sign out of other browser sessions manually.
                @endif
            </p>
        </div>
    </section>
</x-layouts.app>
