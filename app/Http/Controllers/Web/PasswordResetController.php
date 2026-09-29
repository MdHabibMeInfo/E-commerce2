<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\SecurityAlertNotification;
use App\Services\SecurityEventService;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;

class PasswordResetController extends Controller
{
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    public function send(Request $request, SecurityEventService $events): RedirectResponse
    {
        $request->merge(['email' => Str::lower(trim((string) $request->input('email', '')))]);
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ]);

        $user = User::query()->where('email', $validated['email'])->first();

        if ($user !== null) {
            $events->record($request, $user, 'password_reset_requested');
        }

        Password::sendResetLink(['email' => $validated['email']]);

        return back()->with('status', 'If an account matches that email address, a password reset link will be sent.');
    }

    public function edit(Request $request, string $token): View
    {
        $email = $request->query('email');

        return view('auth.reset-password', compact('email', 'token'));
    }

    public function update(Request $request): RedirectResponse
    {
        $email = $request->input('email');

        if (is_string($email)) {
            $request->merge(['email' => Str::lower(trim($email))]);
        }

        $validated = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'confirmed', PasswordRule::defaults()],
        ]);

        $status = Password::reset($validated, function (User $user, string $password) use ($request): void {
            $user->forceFill([
                'password' => $password,
                'remember_token' => Str::random(60),
            ])->save();

            if (config('session.driver') === 'database') {
                DB::table(config('session.table'))
                    ->where('user_id', $user->getKey())
                    ->delete();
            }
            $user->tokens()->delete();

            event(new PasswordReset($user));

            app(SecurityEventService::class)->record($request, $user, 'password_reset_completed');

            if ($user->account_type === 'admin') {
                $user->notify(new SecurityAlertNotification(
                    'Management account password reset',
                    'The password for your management account was successfully reset. All active sessions have been signed out.',
                ));
            }
        });

        if ($status === Password::PASSWORD_RESET) {
            return redirect()->route('login')->with('status', 'Your password has been reset. Sign in with your new password.');
        }

        return back()
            ->withErrors(['email' => trans($status)])
            ->withInput($request->only('email'));
    }
}
