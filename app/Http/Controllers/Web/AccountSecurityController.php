<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\SecurityEvent;
use App\Models\User;
use App\Notifications\EmailChangeVerificationNotification;
use App\Notifications\SecurityAlertNotification;
use App\Services\SecurityEventService;
use App\Services\TotpService;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;

class AccountSecurityController extends Controller
{
    public function profile(Request $request): View|RedirectResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        if ($user->can('access-admin')) {
            return redirect()->route('admin.profile');
        }

        $activeOrders = $user->orders()
            ->whereNotIn('status', ['delivered', 'cancelled', 'returned'])
            ->with('shipments')
            ->latest()
            ->get();

        return view('account.profile', compact('user', 'activeOrders'));
    }

    public function updateProfile(Request $request, SecurityEventService $events): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20', Rule::unique('users', 'phone')->ignore($user->getKey())],
            'profile_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $oldProfilePhotoPath = $user->profile_photo_path;
        $profilePhotoPath = $oldProfilePhotoPath;

        if ($request->hasFile('profile_photo')) {
            $profilePhotoPath = $request->file('profile_photo')->store('profile-photos', 'public');
        }

        $user->fill([
            'name' => $validated['name'],
            'phone' => $validated['phone'] ?? null,
            'profile_photo_path' => $profilePhotoPath,
        ])->save();

        if ($request->hasFile('profile_photo') && ! empty($oldProfilePhotoPath) && $oldProfilePhotoPath !== $profilePhotoPath) {
            Storage::disk('public')->delete($oldProfilePhotoPath);
        }

        $events->record($request, $user, 'profile_updated', ['fields' => ['name', 'phone', 'profile_photo']]);

        return back()->with('status', 'Profile details updated.');
    }

    public function deleteProfilePhoto(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        if ($user->profile_photo_path) {
            Storage::disk('public')->delete($user->profile_photo_path);
            $user->forceFill(['profile_photo_path' => null])->save();
        }

        return redirect()->route('account.profile')->with('status', 'Profile photo removed.');
    }

    public function index(Request $request): View
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        $sessions = config('session.driver') === 'database'
            ? DB::table(config('session.table'))
                ->where('user_id', $user->getKey())
                ->orderByDesc('last_activity')
                ->get()
            : collect();
        $securityEvents = SecurityEvent::query()
            ->where('user_id', $user->getKey())
            ->latest()
            ->limit(50)
            ->get();
        $recoveryCodes = [];
        $encryptedRecoveryCodes = $request->session()->get('recovery_codes');

        if (is_string($encryptedRecoveryCodes)) {
            try {
                $decodedRecoveryCodes = json_decode(Crypt::decryptString($encryptedRecoveryCodes), true, 512, JSON_THROW_ON_ERROR);
                $recoveryCodes = is_array($decodedRecoveryCodes) ? $decodedRecoveryCodes : [];
            } catch (\JsonException|DecryptException) {
                $recoveryCodes = [];
            }
        }

        return view('account.security', compact('user', 'sessions', 'securityEvents', 'recoveryCodes'));
    }

    public function updateEmail(Request $request, SecurityEventService $events): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        $request->merge(['email' => is_string($request->input('email')) ? Str::lower(trim($request->input('email'))) : $request->input('email')]);

        $validated = $request->validate([
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->getKey()),
                Rule::unique('users', 'pending_email')->ignore($user->getKey()),
            ],
            'current_password' => ['required', 'current_password'],
        ]);

        if ($user->email !== $validated['email']) {
            $user->forceFill(['pending_email' => $validated['email']])->save();
            $verificationUrl = URL::temporarySignedRoute(
                'verification.email-change',
                now()->addHours(24),
                ['user' => $user->getKey(), 'hash' => sha1($validated['email'])],
            );
            Notification::route('mail', $validated['email'])
                ->notify(new EmailChangeVerificationNotification($verificationUrl));
            $events->record($request, $user, 'email_change_requested');

            if ($user->account_type === 'admin') {
                $user->notify(new SecurityAlertNotification(
                    'Email change requested',
                    'A request was made to change the email address on your management account. The current address remains active until confirmation.',
                ));
                Notification::route('mail', $user->email)->notify(new SecurityAlertNotification(
                    'Management email change requested',
                    'A request was made to change this email address for a management account. The change is not complete until the new address is verified.',
                ));
            }
        }

        return back()->with('status', 'Confirm the link sent to your new email address to complete the change.');
    }

    public function resendEmailVerification(Request $request, SecurityEventService $events): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User && is_string($user->pending_email), 422);

        $request->validate([
            'current_password' => ['required', 'current_password'],
        ]);

        $verificationUrl = URL::temporarySignedRoute(
            'verification.email-change',
            now()->addHours(24),
            ['user' => $user->getKey(), 'hash' => sha1($user->pending_email)],
        );
        Notification::route('mail', $user->pending_email)
            ->notify(new EmailChangeVerificationNotification($verificationUrl));
        $events->record($request, $user, 'email_change_verification_resent');

        return back()->with('status', 'A new confirmation link was sent to the pending email address.');
    }

    public function updatePassword(Request $request, SecurityEventService $events): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', PasswordRule::defaults()],
        ]);

        $user->forceFill([
            'password' => $validated['password'],
            'remember_token' => Str::random(60),
            'password_change_required' => false,
        ])->save();
        $this->deleteOtherSessions($request, $user);
        $user->tokens()->delete();
        $request->session()->regenerate();
        $events->record($request, $user, 'password_changed');

        if ($user->account_type === 'admin') {
            $user->notify(new SecurityAlertNotification(
                'Management password changed',
                'The password for your management account was changed. Other sessions have been signed out.',
            ));
        }

        return back()->with('status', 'Password updated. Other sessions were signed out.');
    }

    public function regenerateRecoveryCodes(Request $request, TotpService $totp, SecurityEventService $events): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User && $user->two_factor_confirmed_at !== null, 403);

        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'code' => ['required', 'digits:6'],
        ]);

        if (! $totp->consumeTotpCode($user, $validated['code'])) {
            throw ValidationException::withMessages(['code' => 'The authenticator code is invalid.']);
        }

        $recoveryCodes = $totp->generateRecoveryCodes();
        $user->forceFill([
            'two_factor_recovery_codes' => array_map(
                static fn (string $code): string => Hash::make(str_replace('-', '', $code)),
                $recoveryCodes,
            ),
        ])->save();
        $events->record($request, $user, 'two_factor_recovery_codes_regenerated');

        if ($user->account_type === 'admin') {
            $user->notify(new SecurityAlertNotification(
                'Recovery codes regenerated',
                'New recovery codes were generated for your management account. Previous codes are no longer valid.',
            ));
        }

        return back()->with(
            'recovery_codes',
            Crypt::encryptString(json_encode($recoveryCodes, JSON_THROW_ON_ERROR)),
        );
    }

    public function destroySession(Request $request, string $sessionId, SecurityEventService $events): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User && config('session.driver') === 'database', 403);
        abort_if(hash_equals($request->session()->getId(), $sessionId), 422);

        $deleted = DB::table(config('session.table'))
            ->where('id', $sessionId)
            ->where('user_id', $user->getKey())
            ->delete();

        if ($deleted > 0) {
            $events->record($request, $user, 'session_revoked');
        }

        return back()->with('status', 'Selected session signed out.');
    }

    public function destroyOtherSessions(Request $request, SecurityEventService $events): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User && config('session.driver') === 'database', 403);

        $request->validate([
            'current_password' => ['required', 'current_password'],
        ]);

        $this->deleteOtherSessions($request, $user);
        $events->record($request, $user, 'other_sessions_revoked');

        return back()->with('status', 'All other sessions were signed out.');
    }

    private function deleteOtherSessions(Request $request, User $user): void
    {
        if (config('session.driver') !== 'database') {
            return;
        }

        DB::table(config('session.table'))
            ->where('user_id', $user->getKey())
            ->where('id', '!=', $request->session()->getId())
            ->delete();
    }
}
