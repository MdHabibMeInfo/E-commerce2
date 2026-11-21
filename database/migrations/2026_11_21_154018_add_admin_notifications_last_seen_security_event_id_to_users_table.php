<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('admin_notifications_last_seen_security_event_id')->nullable();
        });

        $latestManagementEventId = (int) DB::table('security_events')
            ->whereIn('action', [
                'email_changed',
                'email_change_requested',
                'email_change_verification_resent',
                'email_verification_resent',
                'login_failed',
                'login_pending_email_verification',
                'login_rejected',
                'management_user_created',
                'management_user_roles_updated',
                'management_user_status_updated',
                'other_sessions_revoked',
                'password_changed',
                'password_reset_requested',
                'password_reset_completed',
                'role_activated',
                'role_created',
                'role_deactivated',
                'role_deleted',
                'role_permissions_updated',
                'role_updated',
                'sensitive_site_settings_updated',
                'session_revoked',
                'site_branding_updated',
                'two_factor_authenticator_replaced',
                'two_factor_disabled',
                'two_factor_enabled',
                'two_factor_recovery_code_used',
                'two_factor_recovery_codes_regenerated',
            ])
            ->whereIn('user_id', DB::table('users')->select('id')->where('account_type', 'admin'))
            ->max('id');

        DB::table('users')->where('account_type', 'admin')->update([
            'admin_notifications_last_seen_security_event_id' => $latestManagementEventId,
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('admin_notifications_last_seen_security_event_id');
        });
    }
};
