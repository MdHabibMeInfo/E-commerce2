<x-layouts.app>
    <x-slot:title>
        Choose a New Password — {{ config('app.name') }}
    </x-slot:title>

    <section class="bg-[#fbfaf4] px-4 py-12 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-md rounded-[4px] border border-[#e4e3d8] bg-white p-6 sm:p-8">
            <p class="text-xs font-bold uppercase tracking-[0.2em] text-[#a48335]">Account recovery</p>
            <h1 class="mt-2 text-2xl font-extrabold text-[#145943]">Choose a new password</h1>

            @if ($errors->any())
                <div class="mt-5 rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-800">
                    <ul class="space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('password.update') }}" class="mt-6 space-y-4">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">
                <div>
                    <label for="reset-email" class="mb-1 block text-sm font-semibold text-[#4d5c56]">Email</label>
                    <input id="reset-email" type="email" name="email" value="{{ old('email', $email) }}" required maxlength="255" autocomplete="email" class="h-11 w-full rounded-[4px] border border-[#dfe3cf] px-3 text-sm text-[#173b32] outline-none focus:border-[#145943] focus:ring-2 focus:ring-[#145943]/10">
                    @error('email') <p class="mt-1 text-xs text-red-700">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="reset-password" class="mb-1 block text-sm font-semibold text-[#4d5c56]">New password</label>
                    <div class="relative">
                        <input id="reset-password" type="password" name="password" required minlength="12" autocomplete="new-password" class="h-11 w-full rounded-[4px] border border-[#dfe3cf] px-3 pr-12 text-sm text-[#173b32] outline-none focus:border-[#145943] focus:ring-2 focus:ring-[#145943]/10">
                        <x-password-toggle input-id="reset-password" />
                    </div>
                    <p class="mt-1 text-xs text-[#738078]">Use 12+ characters with uppercase, lowercase, a number and a symbol.</p>
                    @error('password') <p class="mt-1 text-xs text-red-700">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="reset-password-confirmation" class="mb-1 block text-sm font-semibold text-[#4d5c56]">Confirm new password</label>
                    <div class="relative">
                        <input id="reset-password-confirmation" type="password" name="password_confirmation" required minlength="12" autocomplete="new-password" class="h-11 w-full rounded-[4px] border border-[#dfe3cf] px-3 pr-12 text-sm text-[#173b32] outline-none focus:border-[#145943] focus:ring-2 focus:ring-[#145943]/10">
                        <x-password-toggle input-id="reset-password-confirmation" />
                    </div>
                </div>
                <button type="submit" class="h-11 w-full rounded-[4px] bg-[#145943] text-sm font-bold text-white transition hover:bg-[#a48335]">Save new password</button>
            </form>
        </div>
    </section>
</x-layouts.app>