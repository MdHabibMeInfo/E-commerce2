<?php

namespace App\Services;

use App\Models\SecurityEvent;
use App\Models\User;
use Illuminate\Http\Request;

class SecurityEventService
{
    public function recordLogin(Request $request, User $user): bool
    {
        $knownDevice = SecurityEvent::query()
            ->where('user_id', $user->getKey())
            ->where('action', 'login_success')
            ->where('ip_address', $request->ip())
            ->where('user_agent', $request->userAgent())
            ->exists();

        $this->record($request, $user, 'login_success', [
            'session_fingerprint' => hash('sha256', $request->session()->getId()),
        ]);

        return ! $knownDevice;
    }

    /** @param array<string, mixed>|null $context */
    public function record(Request $request, ?User $user, string $action, ?array $context = null): SecurityEvent
    {
        return SecurityEvent::query()->create([
            'user_id' => $user?->getKey(),
            'action' => $action,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'context' => $context,
        ]);
    }
}
