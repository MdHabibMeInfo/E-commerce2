<?php

namespace App\Console\Commands;

use App\Models\SecurityEvent;
use App\Models\User;
use App\Notifications\SecurityAlertNotification;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

#[Signature('admin:two-factor-reset {email}')]
#[Description('Reset a system owner authenticator after explicit confirmation.')]
class ResetManagementTwoFactor extends Command
{
    public function handle(): int
    {
        $user = User::query()
            ->where('email', mb_strtolower(trim((string) $this->argument('email'))))
            ->where('account_type', 'admin')
            ->where('is_system_owner', true)
            ->first();

        if (! $user) {
            $this->error('No system owner account matches that email.');

            return self::FAILURE;
        }

        if (! $this->confirm('Reset the system owner authenticator and revoke all sessions?')) {
            $this->info('Authenticator reset cancelled.');

            return self::FAILURE;
        }

        $user->forceFill([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
            'two_factor_last_used_at' => null,
        ])->save();

        DB::table(config('session.table'))->where('user_id', $user->getKey())->delete();
        $user->tokens()->delete();
        SecurityEvent::query()->create([
            'user_id' => $user->getKey(),
            'action' => 'two_factor_reset_from_console',
            'context' => ['source' => 'console'],
        ]);
        $user->notify(new SecurityAlertNotification(
            'System owner authenticator reset',
            'The authenticator for your system owner account was reset from the server console. All sessions were revoked, and a new authenticator is required at next sign-in.',
        ));

        $this->info('Authenticator reset. The system owner must enroll again at next sign-in.');

        return self::SUCCESS;
    }
}
