<?php

namespace App\Listeners;

use App\Services\AuditService;
use Illuminate\Auth\Events\Failed;

class LogFailedLogin
{
    public function handle(Failed $event): void
    {
        // Passwort niemals loggen — nur E-Mail aus den Credentials
        $email = $event->credentials['email'] ?? 'unknown';

        app(AuditService::class)->logFailedLogin($email);
    }
}
