<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\LoginNotification;
use App\Services\SecurityEventService;
use App\Services\SiteSettings;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AdminSessionController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(Request $request, SecurityEventService $events, SiteSettings $settings): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string'],
        ]);
        $credentials['email'] = Str::lower($credentials['email']);
        $throttleKey = $this->throttleKey($request);
        $maxAttempts = $settings->integer('security_login_attempts');
        $decaySeconds = $settings->integer('security_login_lockout_minutes') * 60;

        if (RateLimiter::tooManyAttempts($throttleKey, $maxAttempts)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            throw ValidationException::withMessages([
                'email' => "Too many login attempts. Try again in {$seconds} seconds.",
            ]);
        }

        RateLimiter::hit($throttleKey, $decaySeconds);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            $events->record(
                $request,
                User::query()->where('email', $credentials['email'])->first(),
                'login_failed',
            );

            return back()
                ->withErrors(['email' => 'These credentials do not match our records.'])
                ->onlyInput('email');
        }

        $user = $request->user();
        $canUseAdmin = $user instanceof User && $user->can('access-admin');
        $canUseStorefront = $user instanceof User
            && $user->account_type === 'customer'
            && $user->status === 'active';

        if (! $canUseAdmin && ! $canUseStorefront) {
            $events->record($request, $user, 'login_rejected');
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()
                ->withErrors(['email' => 'This account does not have access.'])
                ->onlyInput('email');
        }

        RateLimiter::clear($throttleKey);
        $request->session()->regenerate();

        if ($canUseAdmin && ! $user->hasVerifiedEmail()) {
            $events->record($request, $user, 'login_pending_email_verification');

            return redirect()->route('verification.notice');
        }

        $requiresTwoFactor = $canUseAdmin
            ? $settings->boolean('security_admin_2fa_required')
            : $user->two_factor_confirmed_at !== null;

        if ($requiresTwoFactor) {
            $request->session()->forget('security.two_factor_verified_user');
            $request->session()->forget('security.two_factor_verified_at');
            $request->session()->put('security.pending_login_after_mfa', true);

            if ($canUseAdmin && $user->two_factor_confirmed_at === null) {
                return redirect()->route('admin.two-factor.setup');
            }

            return redirect()->route('two-factor.challenge');
        }

        $this->recordSuccessfulLogin($request, $user, $events);

        return redirect()->intended(route('home'));
    }

    public function destroy(Request $request, SecurityEventService $events): RedirectResponse
    {
        $events->record($request, $request->user(), 'logout');
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    private function throttleKey(Request $request): string
    {
        $email = $request->string('email')->lower()->trim()->toString();

        return 'admin-login:'.hash('sha256', Str::transliterate($email)).'|'.$request->ip();
    }

    private function recordSuccessfulLogin(Request $request, User $user, SecurityEventService $events): void
    {
        $newDevice = $events->recordLogin($request, $user);

        if ($user->account_type === 'admin' && $newDevice) {
            $user->notify(new LoginNotification);
        }
    }
}
