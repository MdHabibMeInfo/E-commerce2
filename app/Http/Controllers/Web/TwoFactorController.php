<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\ProductReview;
use App\Models\SecurityEvent;
use App\Models\User;
use App\Notifications\LoginNotification;
use App\Notifications\SecurityAlertNotification;
use App\Services\SecurityEventService;
use App\Services\TotpService;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
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
        if ($user->account_type === 'admin') {
            $request->session()->put('security.two_factor_sensitive_verified_at', now()->timestamp);
        }
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
            if ($user->account_type === 'admin') {
                return redirect()->route('admin.two-factor.setup');
            }

            if ($user->two_factor_confirmed_at !== null) {
                abort(403);
            }

            return redirect()->route('account.security.two-factor.setup');
        }

        $verifiedAt = $request->session()->get('security.two_factor_verified_at');
        $verificationLifetimeMinutes = $user->account_type === 'admin'
            ? (int) config('security.management_two_factor_lifetime', 480)
            : (int) config('session.lifetime', 120);
        $verifiedRecently = is_numeric($verifiedAt)
            && Carbon::createFromTimestamp((int) $verifiedAt)->greaterThan(now()->subMinutes($verificationLifetimeMinutes));

        if ((string) $request->session()->get('security.two_factor_verified_user') === (string) $user->getKey()
            && $verifiedRecently
            && ! (bool) $request->session()->get('security.two_factor_step_up_required', false)) {
            return redirect()->route($user->account_type === 'admin' ? 'admin' : 'home');
        }

        return view('auth.two-factor-challenge', [
            'isStepUpChallenge' => (bool) $request->session()->get('security.two_factor_step_up_required', false),
        ]);
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
        $request->session()->forget('security.two_factor_recovery_verified_at');
        if ($usedRecoveryCode) {
            $request->session()->put('security.two_factor_recovery_verified_at', now()->timestamp);
        }
        if ($user->account_type === 'admin') {
            $request->session()->put('security.two_factor_sensitive_verified_at', now()->timestamp);
        }
        $events->record($request, $user, $usedRecoveryCode ? 'two_factor_recovery_code_used' : 'two_factor_verified');

        $isStepUpChallenge = $request->session()->pull('security.two_factor_step_up_required', false);
        $stepUpDestination = $request->session()->pull('security.two_factor_step_up_destination');

        if ($request->session()->pull('security.pending_login_after_mfa', false)) {
            $this->recordSuccessfulLogin($request, $user, $events);
        }

        if ($isStepUpChallenge && is_string($stepUpDestination)) {
            return redirect()->route($stepUpDestination);
        }

        return redirect()->intended($user->account_type === 'admin' ? route('admin') : route('home'));
    }

    public function startAuthenticatorReplacement(Request $request, TotpService $totp): RedirectResponse
    {
        $user = $request->user();
        abort_unless(
            $user instanceof User
                && $this->canUseAuthenticator($user)
                && $user->two_factor_confirmed_at !== null
                && is_string($user->two_factor_secret),
            403,
        );

        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'code' => ['nullable', 'digits:6'],
        ]);
        $code = $validated['code'] ?? null;
        $verificationMethod = 'recovery_code';

        if (is_string($code) && $code !== '') {
            if (! $totp->consumeTotpCode($user, $code)) {
                throw ValidationException::withMessages(['code' => 'The current authenticator code is invalid.']);
            }

            $verificationMethod = 'authenticator';
        } elseif (! $this->hasRecentRecoveryCodeVerification($request, $user)) {
            throw ValidationException::withMessages([
                'code' => 'Enter a current authenticator code, or sign in with a recovery code within the last 10 minutes.',
            ]);
        }

        $request->session()->put(
            'security.pending_two_factor_replacement_secret',
            Crypt::encryptString($totp->generateSecret()),
        );
        $request->session()->put('security.pending_two_factor_replacement_started_at', now()->timestamp);
        $request->session()->put('security.pending_two_factor_replacement_existing_secret_hash', hash('sha256', $user->two_factor_secret));
        $request->session()->put('security.pending_two_factor_replacement_method', $verificationMethod);

        return redirect()->route('account.security.two-factor.replace');
    }

    public function showAuthenticatorReplacement(Request $request, TotpService $totp): Response|RedirectResponse
    {
        $user = $request->user();
        abort_unless(
            $user instanceof User
                && $this->canUseAuthenticator($user)
                && $user->two_factor_confirmed_at !== null
                && is_string($user->two_factor_secret),
            403,
        );

        $secret = $this->pendingReplacementSecret($request);
        $existingSecretHash = $request->session()->get('security.pending_two_factor_replacement_existing_secret_hash');
        $verificationMethod = $request->session()->get('security.pending_two_factor_replacement_method');

        if ($secret === null
            || ! is_string($existingSecretHash)
            || ! in_array($verificationMethod, ['authenticator', 'recovery_code'], true)) {
            return redirect()->route('account.security')->with('status', 'Authenticator setup expired. Start the replacement again.');
        }

        $provisioningUri = $totp->provisioningUri($secret, $user->email ?? (string) $user->getKey(), config('app.name'));

        return response()
            ->view('account.security.two-factor-replace', [
                'secret' => $secret,
                'qrCodeDataUri' => $totp->qrCodeDataUri($provisioningUri),
            ])
            ->header('Cache-Control', 'no-store, private')
            ->header('Pragma', 'no-cache');
    }

    public function confirmAuthenticatorReplacement(Request $request, TotpService $totp, SecurityEventService $events): RedirectResponse
    {
        $user = $request->user();
        abort_unless(
            $user instanceof User
                && $this->canUseAuthenticator($user)
                && $user->two_factor_confirmed_at !== null
                && is_string($user->two_factor_secret),
            403,
        );

        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'code' => ['required', 'digits:6'],
        ]);
        $secret = $this->pendingReplacementSecret($request);
        $existingSecretHash = $request->session()->get('security.pending_two_factor_replacement_existing_secret_hash');
        $verificationMethod = $request->session()->get('security.pending_two_factor_replacement_method');
        $matchedTimestamp = is_string($secret)
            ? $totp->matchingTimestamp($secret, $validated['code'])
            : null;

        if ($secret === null
            || ! is_string($existingSecretHash)
            || ! in_array($verificationMethod, ['authenticator', 'recovery_code'], true)) {
            return redirect()->route('account.security')->with('status', 'Authenticator setup expired. Start the replacement again.');
        }

        if ($matchedTimestamp === null) {
            throw ValidationException::withMessages(['code' => 'The new authenticator code is invalid.']);
        }

        $recoveryCodes = $totp->generateRecoveryCodes();
        $lockedUser = DB::transaction(function () use ($user, $secret, $existingSecretHash, $matchedTimestamp, $recoveryCodes): User {
            $lockedUser = User::query()->lockForUpdate()->findOrFail($user->getKey());
            $currentSecret = $lockedUser->two_factor_secret;

            abort_unless(
                $this->canUseAuthenticator($lockedUser)
                    && $lockedUser->two_factor_confirmed_at !== null
                    && is_string($currentSecret)
                    && hash_equals($existingSecretHash, hash('sha256', $currentSecret)),
                409,
                'The authenticator changed during setup. Start the replacement again.',
            );

            $lockedUser->forceFill([
                'two_factor_secret' => $secret,
                'two_factor_recovery_codes' => array_map(
                    static fn (string $code): string => Hash::make(str_replace('-', '', $code)),
                    $recoveryCodes,
                ),
                'two_factor_confirmed_at' => now(),
                'two_factor_last_used_at' => now()->setTimestamp($matchedTimestamp),
            ])->save();

            return $lockedUser;
        });

        $request->session()->forget([
            'security.pending_two_factor_replacement_secret',
            'security.pending_two_factor_replacement_started_at',
            'security.pending_two_factor_replacement_existing_secret_hash',
            'security.pending_two_factor_replacement_method',
            'security.two_factor_recovery_verified_at',
        ]);
        $request->session()->regenerate();
        $request->session()->put('security.two_factor_verified_user', $lockedUser->getKey());
        $request->session()->put('security.two_factor_verified_at', now()->timestamp);
        if ($lockedUser->account_type === 'admin') {
            $request->session()->put('security.two_factor_sensitive_verified_at', now()->timestamp);
        }

        if (config('session.driver') === 'database') {
            DB::table(config('session.table'))
                ->where('user_id', $lockedUser->getKey())
                ->where('id', '!=', $request->session()->getId())
                ->delete();
        }
        $lockedUser->tokens()->delete();

        $events->record($request, $lockedUser, 'two_factor_authenticator_replaced', [
            'verification_method' => $verificationMethod,
        ]);

        if ($lockedUser->account_type === 'admin') {
            $sessionRevocationMessage = config('session.driver') === 'database'
                ? 'Other sessions and access tokens were signed out, and new recovery codes were issued.'
                : 'Access tokens were revoked and new recovery codes were issued. Other browser sessions may remain active; sign them out manually.';
            $lockedUser->notify(new SecurityAlertNotification(
                'Management authenticator changed',
                'The authenticator for your management account was changed. '.$sessionRevocationMessage,
            ));
        }

        return redirect()->route('account.security')->with(
            'recovery_codes',
            Crypt::encryptString(json_encode($recoveryCodes, JSON_THROW_ON_ERROR)),
        );
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
            if ($user->admin_notifications_last_seen_security_event_id === null) {
                $securityEvents = SecurityEvent::query()->notificationActivity();
                if ($user->is_system_owner) {
                    $securityEvents->management();
                } else {
                    $securityEvents->where('user_id', $user->getKey());
                }

                $notificationReadState['admin_notifications_last_seen_security_event_id'] = (int) (
                    $securityEvents->max('id') ?? 0
                );
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

    private function hasRecentRecoveryCodeVerification(Request $request, User $user): bool
    {
        $verifiedAt = $request->session()->get('security.two_factor_recovery_verified_at');
        $verifiedUser = $request->session()->get('security.two_factor_verified_user');
        $verificationLifetime = (int) config('security.sensitive_reauthentication_lifetime', 10);

        return (string) $verifiedUser === (string) $user->getKey()
            && is_numeric($verifiedAt)
            && (int) $verifiedAt <= now()->timestamp
            && (int) $verifiedAt > now()->subMinutes($verificationLifetime)->timestamp;
    }

    private function pendingReplacementSecret(Request $request): ?string
    {
        $startedAt = $request->session()->get('security.pending_two_factor_replacement_started_at');
        $verificationLifetime = (int) config('security.sensitive_reauthentication_lifetime', 10);

        if (! is_numeric($startedAt)
            || (int) $startedAt > now()->timestamp
            || (int) $startedAt <= now()->subMinutes($verificationLifetime)->timestamp) {
            $this->clearPendingReplacement($request);

            return null;
        }

        $encryptedSecret = $request->session()->get('security.pending_two_factor_replacement_secret');

        if (! is_string($encryptedSecret)) {
            $this->clearPendingReplacement($request);

            return null;
        }

        try {
            return Crypt::decryptString($encryptedSecret);
        } catch (DecryptException) {
            $this->clearPendingReplacement($request);

            return null;
        }
    }

    private function clearPendingReplacement(Request $request): void
    {
        $request->session()->forget([
            'security.pending_two_factor_replacement_secret',
            'security.pending_two_factor_replacement_started_at',
            'security.pending_two_factor_replacement_existing_secret_hash',
            'security.pending_two_factor_replacement_method',
        ]);
    }
}
