<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SecurityEvent extends Model
{
    use HasFactory;

    private const NOTIFICATION_ACTIONS = [
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
    ];

    protected $fillable = [
        'user_id',
        'action',
        'ip_address',
        'user_agent',
        'context',
    ];

    protected function casts(): array
    {
        return [
            'context' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeManagement(Builder $query): Builder
    {
        return $query->whereHas('user', fn (Builder $users) => $users->where('account_type', 'admin'));
    }

    public function scopeNotificationActivity(Builder $query): Builder
    {
        return $query->whereIn('action', self::NOTIFICATION_ACTIONS);
    }
}
