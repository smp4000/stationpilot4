<?php
namespace App\Providers;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
/**
 * Zentraler Application Service Provider.
 * Wird in späteren Prompts mit Audit-Logging + SMTP-Override erweitert.
 */
class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}
    public function boot(): void
    {
        // APP_URL als Root-URL erzwingen.
        // Wichtig für: Einladungslinks, E-Mail-Links, QR-Codes —
        // wenn der Server über 127.0.0.1 läuft aber externe Links
        // (z.B. für Handy) auf APP_URL zeigen sollen.
        URL::forceRootUrl(config('app.url'));
    }
}
