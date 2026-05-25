<?php

namespace App\Models;

use App\Traits\Auditable;
use App\Traits\HasUlid;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

/**
 * User-Model für alle Benutzertypen.
 *
 * Typen:
 * - super_admin: Plattform-Betreiber, tenant_id = NULL, nur /admin
 * - partner:     Tankstelleninhaber, hat Mandant, nur /app
 * - employee:    Mitarbeiter, hat Mandant, nur /app + Android-App
 * - tax_advisor: Steuerberater, hat Mandant, nur /app (read-only Lohndaten)
 *
 * Naming-Strategie:
 * - Firma: is_company = true, company_name required
 * - Person: is_company = false, first_name + last_name
 * - Accessor name gibt immer den richtigen Anzeigenamen zurück
 */
class User extends Authenticatable implements MustVerifyEmail, FilamentUser
{
    use HasFactory, HasUlid, HasApiTokens, Notifiable, SoftDeletes, HasRoles, Auditable;

    protected array $auditExclude = ['last_login_at', 'last_login_ip', 'remember_token', 'updated_at'];

    protected $fillable = [
        'tenant_id',
        'is_company',
        'first_name',
        'last_name',
        'company_name',
        'email',
        'password',
        'type',
        'phone',
        'locale',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'two_factor_confirmed_at',
        'last_login_at',
        'last_login_ip',
        'pin_hash',
        'scan_code',
        'is_active',
        'must_change_password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'pin_hash',
    ];

    protected function casts(): array
    {
        return [
            'is_company'                => 'boolean',
            'is_active'                 => 'boolean',
            'must_change_password'      => 'boolean',
            'email_verified_at'         => 'datetime',
            'two_factor_confirmed_at'   => 'datetime',
            'last_login_at'             => 'datetime',
            'password'                  => 'hashed',
            // DSGVO: verschlüsselt mit APP_KEY (AES-256-CBC)
            'two_factor_secret'         => 'encrypted',
            'two_factor_recovery_codes' => 'encrypted',
        ];
    }

    // ─────────────────────────────────────────────
    // Name-Accessor (Kern der Naming-Strategie)
    // ─────────────────────────────────────────────

    /**
     * Gibt immer den richtigen Anzeigenamen zurück.
     * Firma → company_name
     * Person → first_name + last_name (trim falls eines fehlt)
     */
    public function getNameAttribute(): string
    {
        if ($this->is_company && $this->company_name) {
            return $this->company_name;
        }

        return trim(
            collect([$this->first_name, $this->last_name])
                ->filter()
                ->implode(' ')
        );
    }

    /**
     * Formelle Anrede für E-Mails und Dokumente.
     * Firma → "Sehr geehrte Damen und Herren"
     * Person → "Guten Tag Vorname Nachname"
     */
    public function getFormalGreetingAttribute(): string
    {
        if ($this->is_company) {
            return 'Sehr geehrte Damen und Herren';
        }

        return 'Guten Tag ' . trim(
            collect([$this->first_name, $this->last_name])
                ->filter()
                ->implode(' ')
        );
    }

    // ─────────────────────────────────────────────
    // Filament Panel-Zugriff
    // ─────────────────────────────────────────────

    /**
     * Klare Trennung: Super-Admin nur /admin, alle anderen nur /app.
     * Kein Mischen. Kein Sonderfall. DSGVO-konform.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return match ($panel->getId()) {
            'admin' => $this->type === 'super_admin' && $this->is_active,
            'app'   => in_array($this->type, ['partner', 'employee', 'tax_advisor'])
                       && $this->is_active,
            default => false,
        };
    }

    // ─────────────────────────────────────────────
    // Typ-Prüfer
    // ─────────────────────────────────────────────

    public function isSuperAdmin(): bool { return $this->type === 'super_admin'; }
    public function isPartner(): bool    { return $this->type === 'partner'; }
    public function isEmployee(): bool   { return $this->type === 'employee'; }
    public function isTaxAdvisor(): bool { return $this->type === 'tax_advisor'; }

    /**
     * Hat der User vollen Zugang?
     * Super-Admin: nur is_active prüfen
     * Alle anderen: is_active + Mandant muss Zugang haben
     */
    public function hasAccess(): bool
    {
        if ($this->isSuperAdmin()) {
            return $this->is_active;
        }

        return $this->is_active
            && $this->tenant !== null
            && $this->tenant->hasAccess();
    }

    // ─────────────────────────────────────────────
    // Beziehungen
    // ─────────────────────────────────────────────

    /** Mandant des Users (null bei Super-Admin). */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /** Mandant dessen Inhaber dieser User ist. */
    public function ownedTenant(): HasOne
    {
        return $this->hasOne(Tenant::class, 'owner_id');
    }

    // Weitere Beziehungen (Station, Employee-Profil) kommen in späteren Prompts
}
