<?php

use App\Models\User;
use App\Services\TotpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something()
{
    // ..
}

function createSystemOwnerUser(array $attributes = []): User
{
    $user = User::factory()->create($attributes);
    $user->forceFill([
        'account_type' => 'admin',
        'is_system_owner' => true,
        'status' => 'active',
    ])->save();
    $ownerRole = Role::query()
        ->where('name', 'System Owner')
        ->where('guard_name', 'web')
        ->first();

    if ($ownerRole !== null) {
        $user->assignRole($ownerRole);
    }

    return $user;
}

function completeManagementTwoFactorForTest(User $user): User
{
    $user->forceFill([
        'two_factor_secret' => app(TotpService::class)->generateSecret(),
        'two_factor_recovery_codes' => [Hash::make(bin2hex(random_bytes(12)))],
        'two_factor_confirmed_at' => now(),
    ])->save();

    session()->put('security.two_factor_verified_user', $user->getKey());
    session()->put('security.two_factor_verified_at', now()->timestamp);

    return $user;
}
