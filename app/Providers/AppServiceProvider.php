<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('outbound-mail', function (object $job): Limit {
            return Limit::perMinute(30)->by('outbound-mail:provider');
        });

        RateLimiter::for('login-ip', function (Request $request): Limit {
            return Limit::perMinute(30)->by('login-ip:'.Str::transliterate((string) $request->ip()));
        });

        RateLimiter::for('registration', function (Request $request): array {
            $email = Str::transliterate(Str::lower(trim((string) $request->input('email', ''))));
            $ip = Str::transliterate((string) $request->ip());
            $account = hash('sha256', $email);

            return [
                Limit::perMinute(5)->by('registration-account:'.$account.'|ip:'.$ip),
                Limit::perMinute(20)->by('registration-ip:'.$ip),
            ];
        });

        RateLimiter::for('account-security', function (Request $request): array {
            $account = $request->user()?->getAuthIdentifier() ?? 'guest';
            $ip = Str::transliterate((string) $request->ip());

            return [
                Limit::perMinute(10)->by('account-security-user:'.$account.'|ip:'.$ip),
                Limit::perMinute(30)->by('account-security-ip:'.$ip),
            ];
        });

        RateLimiter::for('management-actions', function (Request $request): array {
            $account = $request->user()?->getAuthIdentifier() ?? 'guest';
            $ip = Str::transliterate((string) $request->ip());

            return [
                Limit::perMinute(30)->by('management-user:'.$account.'|ip:'.$ip),
                Limit::perMinute(60)->by('management-ip:'.$ip),
            ];
        });

        RateLimiter::for('email-verification', function (Request $request): array {
            $account = $request->user()?->getAuthIdentifier() ?? 'guest';
            $ip = Str::transliterate((string) $request->ip());

            return [
                Limit::perMinute(6)->by('verification-account:'.$account.'|ip:'.$ip),
                Limit::perMinute(30)->by('verification-ip:'.$ip),
            ];
        });

        RateLimiter::for('password-reset', function (Request $request): array {
            $email = Str::transliterate(Str::lower(trim((string) $request->input('email', ''))));
            $ip = Str::transliterate((string) $request->ip());
            $account = hash('sha256', $email);

            return [
                Limit::perMinute(5)->by('password-reset-account:'.$account.'|ip:'.$ip),
                Limit::perMinute(30)->by('password-reset-ip:'.$ip),
            ];
        });

        RateLimiter::for('two-factor', function (Request $request): array {
            $account = $request->user()?->getAuthIdentifier() ?? 'guest';
            $ip = Str::transliterate((string) $request->ip());

            return [
                Limit::perMinute(5)->by('two-factor-account:'.$account.'|ip:'.$ip),
                Limit::perMinute(10)->by('two-factor-user:'.$account),
            ];
        });

        RateLimiter::for('recovery-codes', function (Request $request): array {
            $account = $request->user()?->getAuthIdentifier() ?? 'guest';
            $ip = Str::transliterate((string) $request->ip());

            return [
                Limit::perMinute(3)->by('recovery-codes-account:'.$account.'|ip:'.$ip),
                Limit::perMinute(10)->by('recovery-codes-user:'.$account),
            ];
        });

        PasswordRule::defaults(function () {
            $rule = PasswordRule::min(12)->mixedCase()->numbers()->symbols();

            return $this->app->isProduction() ? $rule->uncompromised() : $rule;
        });

        Gate::before(static function (User $user): ?bool {
            if ($user->is_system_owner) {
                return $user->status === 'active';
            }

            if ($user->account_type === 'admin' && $user->status !== 'active') {
                return false;
            }

            return null;
        });

        Gate::define('access-admin', static function (User $user): bool {
            return $user->status === 'active'
                && $user->account_type === 'admin'
                && $user->roles()->where('is_active', true)->exists();
        });
    }
}
