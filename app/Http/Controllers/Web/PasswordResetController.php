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
        $email = $request->input('email');

        if (is_string($email)) {
            $request->merge(['email' => Str::lower(trim($email))]);
        }

        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ]);

        $user = User::query()->where('email', $validated['email'])->first();

        if ($user) {
            $events->record($request, $user, 'password_reset_requested');
        }

        if ($user?->account_type === 'admin') {
            $user->notify(new SecurityAlertNotification(
                'Management password reset requested',
                'A password reset was requested for your management account.',
            ));
        }

        Password::sendResetLink($validated);

        return back()->with('status', 'If an account exists for that email, a reset link has been sent.');
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

        $events = app(SecurityEventService::class);
        $status = Password::reset($validated, function (User $user, string $password) use ($request, $events): void {
            $user->forceFill([
                'password' => $password,
                'remember_token' => Str::random(60),
            ])->save();

            DB::table(config('session.table'))
                ->where('user_id', $user->getKey())
                ->delete();

            $user->tokens()->delete();

            $events->record($request, $user, 'password_reset_completed');

            if ($user->account_type === 'admin') {
                $user->notify(new SecurityAlertNotification(
                    'Management password reset completed',
                    'Your management account password was reset and active sessions were revoked.',
                ));
            }

            event(new PasswordReset($user));
        });

        if ($status === Password::PASSWORD_RESET) {
            return redirect()->route('login')->with('status', 'Your password has been reset.');
        }

        return back()
            ->withErrors(['email' => trans($status)])
            ->withInput($request->only('email'));
    }
}
