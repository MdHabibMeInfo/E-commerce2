<x-layouts.app>
    <x-slot:title>
        Sign In — {{ config('app.name') }}
    </x-slot:title>

    <section class="bg-[#fbfaf4] px-4 py-12 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-md rounded-[4px] border border-[#e4e3d8] bg-white p-6 sm:p-8">
            <p class="text-xs font-bold uppercase tracking-[0.2em] text-[#a48335]">Account access</p>
            <h1 class="mt-2 text-2xl font-extrabold text-[#145943]">Sign in</h1>

            <form method="POST" action="{{ route('login.store') }}" class="mt-6 space-y-4">
                @csrf

                <div>
                    <label for="login-email" class="mb-1 block text-sm font-semibold text-[#4d5c56]">Email</label>
                    <input id="login-email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username" class="h-11 w-full rounded-[4px] border border-[#dfe3cf] px-3 text-sm text-[#173b32] outline-none focus:border-[#145943] focus:ring-2 focus:ring-[#145943]/10">
                    @error('email') <p class="mt-1 text-xs text-red-700">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="login-password" class="mb-1 block text-sm font-semibold text-[#4d5c56]">Password</label>
                    <div class="relative">
                        <input id="login-password" type="password" name="password" required autocomplete="current-password" class="h-11 w-full rounded-[4px] border border-[#dfe3cf] px-3 pr-12 text-sm text-[#173b32] outline-none focus:border-[#145943] focus:ring-2 focus:ring-[#145943]/10">
                        <x-password-toggle input-id="login-password" />
                    </div>
                </div>

                <div class="text-right">
                    <a href="{{ route('password.request') }}" class="text-sm font-semibold text-[#145943] hover:text-[#a48335]">Forgot password?</a>
                </div>

                <label class="flex items-center gap-2 text-sm text-[#4d5c56]">
                    <input type="checkbox" name="remember" value="1" class="h-4 w-4 rounded border-[#c9cfbf] text-[#145943] focus:ring-[#145943]">
                    Remember me
                </label>

                <button type="submit" class="h-11 w-full rounded-[4px] bg-[#145943] text-sm font-bold text-white transition hover:bg-[#a48335]">Sign in</button>
            </form>

            <p class="mt-5 text-center text-sm text-[#66736d]">
                New customer?
                <a href="{{ route('register') }}" class="font-bold text-[#145943] hover:text-[#a48335]">Create account</a>
            </p>
        </div>
    </section>
</x-layouts.app>
