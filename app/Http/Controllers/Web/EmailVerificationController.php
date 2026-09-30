<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\SecurityEventService;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class EmailVerificationController extends Controller
{
    public function notice(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return redirect()->intended($user->can('access-admin') ? route('admin') : route('home'));
        }

        return view('auth.verify-email');
    }

    public function verify(EmailVerificationRequest $request, SecurityEventService $events): RedirectResponse
    {
        if (! $request->user()->hasVerifiedEmail()) {
            $request->fulfill();
            $events->record($request, $request->user(), 'email_verified');
        }

        $destination = $request->user()->can('access-admin') ? route('admin') : route('home');

        return redirect()->intended($destination)->with('status', 'Email address verified.');
    }

    public function send(Request $request, SecurityEventService $events): RedirectResponse
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return redirect()->intended($user->can('access-admin') ? route('admin') : route('home'));
        }

        $user->sendEmailVerificationNotification();
        $events->record($request, $user, 'email_verification_resent');

        return back()->with('status', 'A new verification link has been sent to your email address.');
    }

    public function verifyPendingEmail(
        Request $request,
        User $user,
        string $hash,
        SecurityEventService $events
    ): RedirectResponse {
        abort_unless($request->user()?->is($user), 403);
        abort_unless(is_string($user->pending_email) && hash_equals(sha1($user->pending_email), $hash), 403);

        $user->forceFill([
            'email' => $user->pending_email,
            'pending_email' => null,
            'email_verified_at' => now(),
            'remember_token' => Str::random(60),
        ])->save();
        $user->tokens()->delete();

        if (config('session.driver') === 'database') {
            DB::table(config('session.table'))
                ->where('user_id', $user->getKey())
                ->where('id', '!=', $request->session()->getId())
                ->delete();
        }

        $events->record($request, $user, 'email_changed');

        return redirect()->route('account.security');
    }
}
