<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\ProductReview;
use App\Models\User;
use App\Notifications\LoginNotification;
use App\Notifications\SecurityAlertNotification;
use App\Services\SecurityEventService;
use App\Services\TotpService;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class TwoFactorController extends Controller
{
    public function setup(Request $request, TotpService $totp): View|RedirectResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User && $this->canUseAuthenticator($user), 403);

        if ($user->two_factor_confirmed_at !== null) {
            return redirect()->route('account.security');
        }

        $secret = $this->pendingSecret($request);

        if ($secret === null) {
            $secret = $totp->generateSecret();
            $request->session()->put('security.pending_two_factor_secret', Crypt::encryptString($secret));
        }

        $provisioningUri = $totp->provisioningUri($secret, $user->email ?? (string) $user->getKey(), config('app.name'));

        return view('admin.security.two-factor-setup', [
            'secret' => $secret,
            'qrCodeDataUri' => $totp->qrCodeDataUri($provisioningUri),
        ]);
    }

    public function confirm(Request $request, TotpService $totp, SecurityEventService $events): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User && $this->canUseAuthenticator($user), 403);

        $validated = $request->validate([
            'code' => ['required', 'digits:6'],
        ]);
        $secret = $this->pendingSecret($request);

        $matchedTimestamp = is_string($secret)
            ? $totp->matchingTimestamp($secret, $validated['code'])
            : null;

        if ($matchedTimestamp === null) {
            throw ValidationException::withMessages(['code' => 'The authenticator code is invalid.']);
        }

        $recoveryCodes = $totp->generateRecoveryCodes();
        $user->forceFill([
            'two_factor_secret' => $secret,
            'two_factor_recovery_codes' => array_map(
                static fn (string $code): string => Hash::make(str_replace('-', '', $code)),
                $recoveryCodes,
            ),
            'two_factor_confirmed_at' => now(),
            'two_factor_last_used_at' => now()->setTimestamp($matchedTimestamp),
        ])->save();

        $request->session()->forget('security.pending_two_factor_secret');
        $request->session()->regenerate();
        $request->session()->put('security.two_factor_verified_user', $user->getKey());
        $request->session()->put('security.two_factor_verified_at', now()->timestamp);
        $events->record($request, $user, 'two_factor_enabled');
        if ($user->account_type === 'admin') {
            $user->notify(new SecurityAlertNotification(
                'Management authenticator enabled',
                'A TOTP authenticator was enabled for your management account. New recovery codes were issued and are shown once in your account.',
            ));
        }

        if ($request->session()->pull('security.pending_login_after_mfa', false)) {
            $this->recordSuccessfulLogin($request, $user, $events);
        }

        return redirect()->route('account.security')->with(
            'recovery_codes',
            Crypt::encryptString(json_encode($recoveryCodes, JSON_THROW_ON_ERROR)),
        );
    }

    public function challenge(Request $request): View|RedirectResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User && $this->canUseAuthenticator($user), 403);

        if ($user->two_factor_confirmed_at === null || ! $user->two_factor_secret) {
            return redirect()->route('admin.two-factor.setup');
        }

        $verifiedAt = $request->session()->get('security.two_factor_verified_at');

        if ((string) $request->session()->get('security.two_factor_verified_user') === (string) $user->getKey()
            && is_numeric($verifiedAt)
            && now()->diffInMinutes(now()->setTimestamp((int) $verifiedAt)) < 10) {
            return redirect()->route('admin');
        }

        return view('auth.two-factor-challenge');
    }

    public function verify(Request $request, TotpService $totp, SecurityEventService $events): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User && $this->canUseAuthenticator($user), 403);

        $validated = $request->validate([
            'code' => ['required', 'string', 'min:6', 'max:24'],
        ]);
        $code = trim($validated['code']);
        $usedRecoveryCode = false;

        if (! $totp->consumeTotpCode($user, $code)) {
            $usedRecoveryCode = $totp->consumeRecoveryCode($user, $code);

            if (! $usedRecoveryCode) {
                $events->record($request, $user, 'two_factor_failed');

                throw ValidationException::withMessages(['code' => 'The authenticator or recovery code is invalid.']);
            }
        }

        $request->session()->regenerate();
        $request->session()->put('security.two_factor_verified_user', $user->getKey());
        $request->session()->put('security.two_factor_verified_at', now()->timestamp);
        $events->record($request, $user, $usedRecoveryCode ? 'two_factor_recovery_code_used' : 'two_factor_verified');

        if ($request->session()->pull('security.pending_login_after_mfa', false)) {
            $this->recordSuccessfulLogin($request, $user, $events);
        }

        return redirect()->intended($user->account_type === 'admin' ? route('admin') : route('home'));
    }

    public function disableCustomer(Request $request, TotpService $totp, SecurityEventService $events): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User && $user->account_type === 'customer', 403);

        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'code' => ['required', 'digits:6'],
        ]);

        if (! $totp->consumeTotpCode($user, $validated['code'])) {
            throw ValidationException::withMessages(['code' => 'The authenticator code is invalid.']);
        }

        $user->forceFill([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
            'two_factor_last_used_at' => null,
        ])->save();
        $events->record($request, $user, 'two_factor_disabled');

        return redirect()->route('account.security')->with('status', 'Authenticator disabled.');
    }

    private function canUseAuthenticator(User $user): bool
    {
        return $user->can('access-admin')
            || ($user->account_type === 'customer' && $user->status === 'active');
    }

    private function recordSuccessfulLogin(Request $request, User $user, SecurityEventService $events): void
    {
        if ($user->can('access-admin')) {
            $notificationReadState = [];

            if ($user->admin_notifications_last_seen_order_id === null) {
                $notificationReadState['admin_notifications_last_seen_order_id'] = (int) (Order::query()->max('id') ?? 0);
            }
            if ($user->admin_notifications_last_seen_review_id === null) {
                $notificationReadState['admin_notifications_last_seen_review_id'] = (int) (ProductReview::query()->max('id') ?? 0);
            }

            if ($notificationReadState !== []) {
                $user->forceFill($notificationReadState)->save();
            }
        }

        $newDevice = $events->recordLogin($request, $user);

        if ($user->account_type === 'admin' && $newDevice) {
            $user->notify(new LoginNotification);
        }
    }

    private function pendingSecret(Request $request): ?string
    {
        $encryptedSecret = $request->session()->get('security.pending_two_factor_secret');

        if (! is_string($encryptedSecret)) {
            return null;
        }

        try {
            return Crypt::decryptString($encryptedSecret);
        } catch (DecryptException) {
            return null;
        }
    }
}
