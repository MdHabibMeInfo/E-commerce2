<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\Response;

class RequireManagementTwoFactor
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof User) {
            return $next($request);
        }

        if ($user->account_type === 'customer' && $user->status !== 'active') {
            return $request->expectsJson()
                ? response()->json(['message' => 'This customer account is inactive.'], 403)
                : abort(403);
        }

        $requiresTwoFactor = $user->account_type === 'admin'
            || ($user->account_type === 'customer' && $user->two_factor_confirmed_at !== null);

        if (! $requiresTwoFactor) {
            return $next($request);
        }

        if ($user->status !== 'active') {
            return $request->expectsJson()
                ? response()->json(['message' => 'This management account is inactive.'], 403)
                : abort(403);
        }

        if ($user->two_factor_confirmed_at === null || ! $user->two_factor_secret) {
            if ($user->account_type === 'customer') {
                return $request->expectsJson()
                    ? response()->json(['message' => 'The customer authenticator is unavailable.'], 403)
                    : abort(403);
            }

            return $request->expectsJson()
                ? response()->json(['message' => 'Management access requires a verified authenticator session.'], 403)
                : redirect()->route('admin.two-factor.setup');
        }

        if (! $request->hasSession()) {
            return $request->expectsJson()
                ? response()->json(['message' => 'Management access requires a verified authenticator session.'], 403)
                : redirect()->route('two-factor.challenge');
        }

        $verifiedAt = $request->session()->get('security.two_factor_verified_at');
        $verificationLifetimeMinutes = $user->account_type === 'admin'
            ? (int) config('security.management_two_factor_lifetime', 480)
            : (int) config('session.lifetime', 120);
        $verifiedRecently = is_numeric($verifiedAt)
            && Carbon::createFromTimestamp((int) $verifiedAt)->greaterThan(now()->subMinutes($verificationLifetimeMinutes));

        if ((string) $request->session()->get('security.two_factor_verified_user') !== (string) $user->getKey() || ! $verifiedRecently) {
            return $request->expectsJson()
                ? response()->json(['message' => 'Management access requires a verified authenticator session.'], 403)
                : redirect()->route('two-factor.challenge');
        }

        if ($user->password_change_required
            && ! $request->routeIs('account.security', 'account.security.password.update', 'logout')) {
            return $request->expectsJson()
                ? response()->json(['message' => 'A password change is required before management access.'], 403)
                : redirect()->route('account.security');
        }

        return $next($request);
    }
}
