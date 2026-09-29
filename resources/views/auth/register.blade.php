<x-layouts.app>
    <x-slot:title>
        Create Account — {{ config('app.name') }}
    </x-slot:title>

    <section class="bg-[#fbfaf4] px-4 py-12 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-md rounded-[4px] border border-[#e4e3d8] bg-white p-6 sm:p-8">
            <p class="text-xs font-bold uppercase tracking-[0.2em] text-[#a48335]">Customer account</p>
            <h1 class="mt-2 text-2xl font-extrabold text-[#145943]">Create account</h1>

            @if ($errors->any())
                <div class="mt-5 rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-800">
                    <ul class="space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('register.store') }}" class="mt-6 space-y-4">
                @csrf

                <div>
                    <label for="register-name" class="mb-1 block text-sm font-semibold text-[#4d5c56]">Full name</label>
                    <input id="register-name" type="text" name="name" value="{{ old('name') }}" required maxlength="255" autocomplete="name" class="h-11 w-full rounded-[4px] border border-[#dfe3cf] px-3 text-sm text-[#173b32] outline-none focus:border-[#145943] focus:ring-2 focus:ring-[#145943]/10">
                    @error('name') <p class="mt-1 text-xs text-red-700">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="register-email" class="mb-1 block text-sm font-semibold text-[#4d5c56]">Email</label>
                    <input id="register-email" type="email" name="email" value="{{ old('email') }}" required maxlength="255" autocomplete="email" class="h-11 w-full rounded-[4px] border border-[#dfe3cf] px-3 text-sm text-[#173b32] outline-none focus:border-[#145943] focus:ring-2 focus:ring-[#145943]/10">
                    @error('email') <p class="mt-1 text-xs text-red-700">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="register-phone" class="mb-1 block text-sm font-semibold text-[#4d5c56]">Phone number <span class="font-normal text-[#738078]">(optional)</span></label>
                    <input id="register-phone" type="tel" name="phone" value="{{ old('phone') }}" maxlength="20" autocomplete="tel" class="h-11 w-full rounded-[4px] border border-[#dfe3cf] px-3 text-sm text-[#173b32] outline-none focus:border-[#145943] focus:ring-2 focus:ring-[#145943]/10">
                    @error('phone') <p class="mt-1 text-xs text-red-700">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="register-password" class="mb-1 block text-sm font-semibold text-[#4d5c56]">Password</label>
                    <input id="register-password" type="password" name="password" required minlength="12" autocomplete="new-password" class="h-11 w-full rounded-[4px] border border-[#dfe3cf] px-3 text-sm text-[#173b32] outline-none focus:border-[#145943] focus:ring-2 focus:ring-[#145943]/10">
                    <p class="mt-1 text-xs text-[#738078]">Use 12+ characters with uppercase, lowercase, a number and a symbol.</p>
                    @error('password') <p class="mt-1 text-xs text-red-700">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="register-password-confirmation" class="mb-1 block text-sm font-semibold text-[#4d5c56]">Confirm password</label>
                    <input id="register-password-confirmation" type="password" name="password_confirmation" required minlength="12" autocomplete="new-password" class="h-11 w-full rounded-[4px] border border-[#dfe3cf] px-3 text-sm text-[#173b32] outline-none focus:border-[#145943] focus:ring-2 focus:ring-[#145943]/10">
                </div>

                <button type="submit" class="h-11 w-full rounded-[4px] bg-[#145943] text-sm font-bold text-white transition hover:bg-[#a48335]">Create account</button>
            </form>

            <p class="mt-5 text-center text-sm text-[#66736d]">
                Already registered?
                <a href="{{ route('login') }}" class="font-bold text-[#145943] hover:text-[#a48335]">Sign in</a>
            </p>
        </div>
    </section>
</x-layouts.app>