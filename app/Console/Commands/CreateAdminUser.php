<?php

namespace App\Console\Commands;

use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

#[Signature('admin:create')]
#[Description('Create a system owner account for the admin dashboard.')]
class CreateAdminUser extends Command
{
    public function handle(): int
    {
        if (User::query()->where('is_system_owner', true)->exists()) {
            $this->error('A system owner already exists.');

            return self::FAILURE;
        }

        $systemOwnerRole = Role::query()
            ->where('name', 'System Owner')
            ->where('guard_name', 'web')
            ->where('is_system', true)
            ->where('is_active', true)
            ->first();

        if ($systemOwnerRole === null) {
            $this->error('The reserved System Owner role is missing or inactive. Run the application migrations first.');

            return self::FAILURE;
        }

        $input = [
            'name' => trim((string) $this->ask('Admin name')),
            'email' => Str::lower(trim((string) $this->ask('Admin email'))),
            'password' => (string) $this->secret('Password (12+ characters, mixed case, number, symbol)'),
            'password_confirmation' => (string) $this->secret('Confirm password'),
        ];

        $validator = Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', PasswordRule::defaults(), 'same:password_confirmation'],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $validated = $validator->validated();
        app(PermissionSeeder::class)->run();
        $user = User::query()->create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
        ]);
        $user->forceFill([
            'account_type' => 'admin',
            'is_system_owner' => true,
            'system_owner_slot' => 1,
            'status' => 'active',
        ])->save();
        $user->syncRoles($systemOwnerRole);
        $user->syncPermissions(Permission::query()->where('guard_name', 'web')->get());
        $user->sendEmailVerificationNotification();

        $this->info('System owner account created.');

        return self::SUCCESS;
    }
}
