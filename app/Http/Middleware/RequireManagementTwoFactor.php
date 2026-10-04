<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\SiteSettings;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\Response;

class RequireManagementTwoFactor
{
    public function __construct(private readonly SiteSettings $settings) {}

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof User || $user->account_type !== 'admin') {
            return $next($request);
        }

        if ($this->settings->boolean('security_admin_2fa_required')) {
            if ($user->two_factor_confirmed_at === null || ! $user->two_factor_secret) {
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
            $verifiedRecently = is_numeric($verifiedAt)
                && Carbon::createFromTimestamp((int) $verifiedAt)->greaterThan(now()->subMinutes(10));

            if ((string) $request->session()->get('security.two_factor_verified_user') !== (string) $user->getKey() || ! $verifiedRecently) {
                return $request->expectsJson()
                    ? response()->json(['message' => 'Management access requires a verified authenticator session.'], 403)
                    : redirect()->route('two-factor.challenge');
            }
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
