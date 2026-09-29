<x-layouts.app>
    <x-slot:title>Account security | {{ config('app.name') }}</x-slot:title>

    <section class="bg-[#fbfaf4] px-4 py-10 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-5xl space-y-6">
            <header>
                <p class="text-xs font-bold uppercase tracking-[0.2em] text-[#a48335]">Account</p>
                <h1 class="mt-2 text-3xl font-extrabold text-[#145943]">Security</h1>
            </header>

            @if (session('status'))
                <p class="rounded-[4px] border border-green-200 bg-green-50 px-4 py-3 text-sm font-semibold text-green-800">{{ session('status') }}</p>
            @endif
            @if ($errors->any())
                <p class="rounded-[4px] border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ $errors->first() }}</p>
            @endif
            @if ($user->password_change_required)
                <p class="rounded-[4px] border border-amber-300 bg-amber-50 px-4 py-3 text-sm font-semibold text-amber-900">Change your temporary password before opening management tools.</p>
            @endif

            <section class="border border-[#e4e3d8] bg-white p-5 sm:p-6">
                <h2 class="text-lg font-bold text-[#173b32]">Profile details</h2>
                <form method="POST" action="{{ route('account.profile.update') }}" class="mt-4 grid gap-3 sm:grid-cols-2">
                    @csrf
                    @method('PUT')
                    <label class="block text-sm font-semibold text-[#4d5c56]">Name
                        <input type="text" name="name" value="{{ old('name', $user->name) }}" required maxlength="255" class="mt-1 h-11 w-full rounded-[4px] border border-[#dfe3cf] px-3 text-sm">
                    </label>
                    <label class="block text-sm font-semibold text-[#4d5c56]">Phone
                        <input type="tel" name="phone" value="{{ old('phone', $user->phone) }}" maxlength="20" class="mt-1 h-11 w-full rounded-[4px] border border-[#dfe3cf] px-3 text-sm">
                    </label>
                    <div class="sm:col-span-2">
                        <button type="submit" class="h-10 rounded-[4px] bg-[#145943] px-4 text-sm font-bold text-white hover:bg-[#a48335]">Save profile</button>
                    </div>
                </form>
            </section>

            @if ($recoveryCodes !== [])
                <section class="border border-amber-300 bg-amber-50 p-5" aria-labelledby="recovery-codes-title">
                    <h2 id="recovery-codes-title" class="text-lg font-bold text-[#173b32]">Save these recovery codes</h2>
                    <p class="mt-1 text-sm text-[#4d5c56]">Each code can be used once. They will not be shown again.</p>
                    <ul class="mt-4 grid grid-cols-2 gap-2 font-mono text-sm text-[#173b32] sm:grid-cols-4">
                        @foreach ($recoveryCodes as $recoveryCode)
                            <li class="select-all border border-amber-200 bg-white px-2 py-2 text-center">{{ $recoveryCode }}</li>
                        @endforeach
                    </ul>
                </section>
            @endif

            @if ($user->account_type === 'admin' || $user->account_type === 'customer')
                <section class="border border-[#e4e3d8] bg-white p-5 sm:p-6">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <h2 class="text-lg font-bold text-[#173b32]">Authenticator</h2>
                            <p class="mt-1 text-sm text-[#66736d]">
                                @if ($user->two_factor_confirmed_at)
                                    {{ $user->account_type === 'admin' ? 'Enabled and required for management access.' : 'Enabled for your account sign-ins.' }}
                                @else
                                    {{ $user->account_type === 'admin' ? 'Setup is required before dashboard access.' : 'Optional authenticator protection for future sign-ins.' }}
                                @endif
                            </p>
                        </div>
                        <span class="rounded-[3px] px-3 py-1 text-xs font-bold {{ $user->two_factor_confirmed_at ? 'bg-green-50 text-green-800' : 'bg-amber-50 text-amber-800' }}">{{ $user->two_factor_confirmed_at ? 'Enabled' : 'Setup required' }}</span>
                    </div>
                    @if ($user->account_type === 'customer' && ! $user->two_factor_confirmed_at)
                        <a href="{{ route('account.security.two-factor.setup') }}" class="mt-4 inline-flex h-10 items-center rounded-[4px] bg-[#145943] px-4 text-sm font-bold text-white hover:bg-[#a48335]">Set up authenticator</a>
                    @elseif ($user->account_type === 'customer' && $user->two_factor_confirmed_at)
                        <form method="POST" action="{{ route('account.security.two-factor.disable') }}" class="mt-5 grid gap-3 sm:grid-cols-[1fr_1fr_auto]">
                            @csrf
                            @method('DELETE')
                            <input type="password" name="current_password" required autocomplete="current-password" placeholder="Current password" aria-label="Current password" class="h-11 rounded-[4px] border border-[#dfe3cf] px-3 text-sm">
                            <input name="code" required inputmode="numeric" autocomplete="one-time-code" placeholder="6-digit authenticator code" aria-label="Authenticator code" class="h-11 rounded-[4px] border border-[#dfe3cf] px-3 font-mono text-sm">
                            <button class="h-11 rounded-[4px] border border-red-700 px-4 text-sm font-bold text-red-800">Disable authenticator</button>
                        </form>
                    @endif
                    @if ($user->two_factor_confirmed_at)
                        <form method="POST" action="{{ route('account.security.recovery-codes') }}" class="mt-5 grid gap-3 sm:grid-cols-[1fr_1fr_auto]">
                            @csrf
                            <input type="password" name="current_password" required autocomplete="current-password" placeholder="Current password" aria-label="Current password" class="h-11 rounded-[4px] border border-[#dfe3cf] px-3 text-sm">
                            <input name="code" required inputmode="numeric" autocomplete="one-time-code" placeholder="6-digit authenticator code" aria-label="Authenticator code" class="h-11 rounded-[4px] border border-[#dfe3cf] px-3 font-mono text-sm">
                            <button class="h-11 rounded-[4px] border border-[#145943] px-4 text-sm font-bold text-[#145943] hover:bg-[#edf2e4]">Replace recovery codes</button>
                        </form>
                    @endif
                </section>
            @endif

            <div class="grid gap-6 lg:grid-cols-2">
                <section class="border border-[#e4e3d8] bg-white p-5 sm:p-6">
                    <h2 class="text-lg font-bold text-[#173b32]">Change email</h2>
                    @if ($user->pending_email)
                        <p class="mt-2 text-sm text-amber-800">Confirmation pending for {{ $user->pending_email }}. Your current email remains active.</p>
                        <form method="POST" action="{{ route('account.security.email.resend') }}" class="mt-3 flex flex-wrap items-end gap-3">
                            @csrf
                            <label class="block flex-1 text-sm font-semibold text-[#4d5c56]">Confirm with current password
                                <input type="password" name="current_password" required autocomplete="current-password" class="mt-1 h-11 w-full rounded-[4px] border border-[#dfe3cf] px-3 text-sm">
                            </label>
                            <button type="submit" class="h-11 rounded-[4px] border border-[#145943] px-4 text-sm font-bold text-[#145943]">Resend confirmation</button>
                        </form>
                    @endif
                    <form method="POST" action="{{ route('account.security.email.update') }}" class="mt-4 space-y-3">
                        @csrf
                        @method('PUT')
                        <label class="block text-sm font-semibold text-[#4d5c56]">Email address
                            <input type="email" name="email" value="{{ $user->email }}" required autocomplete="email" class="mt-1 h-11 w-full rounded-[4px] border border-[#dfe3cf] px-3 text-sm">
                        </label>
                        <label class="block text-sm font-semibold text-[#4d5c56]">Confirm with current password
                            <input type="password" name="current_password" required autocomplete="current-password" class="mt-1 h-11 w-full rounded-[4px] border border-[#dfe3cf] px-3 text-sm">
                        </label>
                        <button class="h-10 rounded-[4px] bg-[#145943] px-4 text-sm font-bold text-white hover:bg-[#a48335]">Update email</button>
                    </form>
                </section>

                <section class="border border-[#e4e3d8] bg-white p-5 sm:p-6">
                    <h2 class="text-lg font-bold text-[#173b32]">Change password</h2>
                    <form method="POST" action="{{ route('account.security.password.update') }}" class="mt-4 space-y-3">
                        @csrf
                        @method('PUT')
                        <label class="block text-sm font-semibold text-[#4d5c56]">Current password
                            <input type="password" name="current_password" required autocomplete="current-password" class="mt-1 h-11 w-full rounded-[4px] border border-[#dfe3cf] px-3 text-sm">
                        </label>
                        <label class="block text-sm font-semibold text-[#4d5c56]">New password
                            <input type="password" name="password" required autocomplete="new-password" class="mt-1 h-11 w-full rounded-[4px] border border-[#dfe3cf] px-3 text-sm">
                        </label>
                        <label class="block text-sm font-semibold text-[#4d5c56]">Confirm new password
                            <input type="password" name="password_confirmation" required autocomplete="new-password" class="mt-1 h-11 w-full rounded-[4px] border border-[#dfe3cf] px-3 text-sm">
                        </label>
                        <button class="h-10 rounded-[4px] bg-[#145943] px-4 text-sm font-bold text-white hover:bg-[#a48335]">Update password</button>
                    </form>
                </section>
            </div>

            <section class="border border-[#e4e3d8] bg-white p-5 sm:p-6">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-bold text-[#173b32]">Active sessions</h2>
                        <p class="mt-1 text-sm text-[#66736d]">{{ config('session.driver') === 'database' ? 'Sessions signed in to this account.' : 'Session listing requires the database session driver.' }}</p>
                    </div>
                    @if ($sessions->where('id', '!=', request()->session()->getId())->isNotEmpty())
                        <form method="POST" action="{{ route('account.security.sessions.destroy-others') }}" class="flex flex-wrap gap-2">
                            @csrf
                            @method('DELETE')
                            <input type="password" name="current_password" required autocomplete="current-password" placeholder="Current password" aria-label="Current password" class="h-10 rounded-[4px] border border-[#dfe3cf] px-3 text-sm">
                            <button class="h-10 rounded-[4px] border border-red-700 px-3 text-sm font-bold text-red-800">Sign out other sessions</button>
                        </form>
                    @endif
                </div>
                <ul class="mt-4 divide-y divide-[#ece8dc]">
                    @forelse ($sessions as $session)
                        <li class="flex flex-wrap items-center justify-between gap-3 py-3 text-sm">
                            <div>
                                <p class="font-semibold text-[#173b32]">{{ $session->id === request()->session()->getId() ? 'This device' : ($session->ip_address ?? 'Unknown device') }}</p>
                                <p class="mt-1 text-xs text-[#66736d]">{{ $session->ip_address }} · {{ \Illuminate\Support\Carbon::createFromTimestamp($session->last_activity)->diffForHumans() }}</p>
                            </div>
                            @if ($session->id !== request()->session()->getId())
                                <form method="POST" action="{{ route('account.security.sessions.destroy', $session->id) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button class="text-sm font-bold text-red-800">Sign out</button>
                                </form>
                            @endif
                        </li>
                    @empty
                        <li class="py-3 text-sm text-[#66736d]">No database sessions were found.</li>
                    @endforelse
                </ul>
            </section>

            <section class="border border-[#e4e3d8] bg-white p-5 sm:p-6">
                <h2 class="text-lg font-bold text-[#173b32]">Recent security activity</h2>
                <ul class="mt-4 divide-y divide-[#ece8dc]">
                    @forelse ($securityEvents as $event)
                        <li class="flex flex-wrap justify-between gap-2 py-3 text-sm">
                            <span class="font-semibold text-[#173b32]">{{ str($event->action)->replace('_', ' ')->title() }}</span>
                            <span class="text-xs text-[#66736d]">{{ $event->ip_address ?? 'Unknown IP' }} · {{ $event->created_at->diffForHumans() }}</span>
                        </li>
                    @empty
                        <li class="py-3 text-sm text-[#66736d]">No recorded security activity yet.</li>
                    @endforelse
                </ul>
            </section>
        </div>
    </section>
</x-layouts.app>