<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireRecentManagementTwoFactor
{
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

        $verifiedAt = $request->session()->get('security.two_factor_sensitive_verified_at');
        $reauthenticationLifetime = (int) config('security.sensitive_reauthentication_lifetime', 10);
        $verifiedRecently = is_numeric($verifiedAt)
            && (int) $verifiedAt <= now()->timestamp
            && (int) $verifiedAt > now()->subMinutes($reauthenticationLifetime)->timestamp;

        if ($verifiedRecently) {
            return $next($request);
        }

        $request->session()->put('security.two_factor_step_up_required', true);
        $request->session()->put(
            'security.two_factor_step_up_destination',
            $this->destinationRoute($request),
        );

        return redirect()->route('two-factor.challenge');
    }

    private function destinationRoute(Request $request): string
    {
        if ($request->routeIs('account.security*')) {
            return 'account.security';
        }

        if ($request->routeIs('admin.roles.*')) {
            return 'admin.roles.index';
        }

        if ($request->routeIs('admin.users.*')) {
            return 'admin.users.index';
        }

        return 'admin.settings';
    }
}
