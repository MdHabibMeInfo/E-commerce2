<?php

namespace App\Providers;

use App\Models\Order;
use App\Models\User;
use App\Policies\OrderPolicy;
use App\Services\SiteSettings;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View as ViewFacade;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\View\View;

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
        RateLimiter::for('two-factor', static function (Request $request): array {
            $userKey = $request->user()?->getAuthIdentifier() ?? 'guest';

            return [
                Limit::perMinute(5)->by('two-factor-user:'.$userKey),
                Limit::perMinute(20)->by('two-factor-ip:'.$request->ip()),
            ];
        });

        PasswordRule::defaults(function () {
            $minimumLength = app(SiteSettings::class)->integer('security_password_min_length');
            $rule = PasswordRule::min($minimumLength)->mixedCase()->numbers()->symbols();

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

        Gate::define('system-owner-settings', static function (User $user): bool {
            return $user->is_system_owner && $user->status === 'active';
        });

        ViewFacade::composer('components.header', static function (View $view): void {
            $view->with('siteBranding', app(SiteSettings::class)->branding());
        });

        ViewFacade::composer('storefront.home', static function (View $view): void {
            $settings = app(SiteSettings::class);
            $view->with('siteBranding', $settings->branding());
        });

        Gate::policy(Order::class, OrderPolicy::class);
    }
}
