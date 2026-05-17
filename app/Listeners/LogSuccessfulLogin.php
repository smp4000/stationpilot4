<?php

namespace App\Listeners;

use App\Services\AuditService;
use Illuminate\Auth\Events\Login;

class LogSuccessfulLogin
{
    public function handle(Login $event): void
    {
        $user = $event->user;

        if ($user->tenant_id) {
            session(['tenant_id' => $user->tenant_id]);
        }

        $user->updateQuietly([
            'last_login_at' => now(),
            'last_login_ip' => request()->ip(),
        ]);

        app(AuditService::class)->logLogin($user);
    }
}
