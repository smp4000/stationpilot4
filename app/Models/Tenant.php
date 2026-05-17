<?php

namespace App\Models;

use App\Traits\Auditable;
use App\Traits\HasUlid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * Mandanten-Model.
 * Jeder Tankstellenpartner ist ein Mandant mit eigenen, isolierten Daten.
 * Super-Admin hat KEINEN Mandanten (tenant_id = NULL auf users).
 */
class Tenant extends Model
{
    use HasFactory, HasUlid, SoftDeletes, Auditable;

    protected array $auditExclude = ['updated_at'];

    protected $fillable = [
        'owner_id',
        'name',
        'slug',
        'billing_email',
        'billing_address',
        'tax_id',
        'ust_id',
        'phone',
        'logo',
        'subscription_status',
        'trial_ends_at',
        'billing_driver',
        'locale',
        'timezone',
        'settings',
        'is_active',
        'archived_at',
    ];

    protected $casts = [
        'billing_address' => 'array',
        'settings'        => 'array',
        'is_active'       => 'boolean',
        'trial_ends_at'   => 'datetime',
        'archived_at'     => 'datetime',
    ];

    // ─────────────────────────────────────────────
    // Beziehungen
    // ─────────────────────────────────────────────

    /** Inhaber des Mandanten. */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /** Alle User dieses Mandanten. */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    // Weitere Beziehungen (Station, Employee, Invoice) kommen in späteren Prompts

    // ─────────────────────────────────────────────
    // Abo-Status Hilfsmethoden
    // ─────────────────────────────────────────────

    /** Trial läuft noch. */
    public function isOnTrial(): bool
    {
        return $this->subscription_status === 'trial'
            && $this->trial_ends_at !== null
            && $this->trial_ends_at->isFuture();
    }

    /** Trial abgelaufen, kein aktives Abo. */
    public function isTrialExpired(): bool
    {
        return $this->subscription_status === 'trial'
            && $this->trial_ends_at !== null
            && $this->trial_ends_at->isPast();
    }

    /** Aktives bezahltes Abo. */
    public function hasActiveSubscription(): bool
    {
        return $this->subscription_status === 'active';
    }

    /** Zahlung überfällig — Login noch möglich, aber Warnhinweis. */
    public function isPastDue(): bool
    {
        return $this->subscription_status === 'past_due';
    }

    /** Nur lesender Zugriff (Trial abgelaufen oder Mahnstufe 3). */
    public function isReadOnly(): bool
    {
        return $this->subscription_status === 'read_only';
    }

    /** Mandant hat vollen Zugriff (Trial aktiv oder Abo aktiv oder past_due). */
    public function hasAccess(): bool
    {
        return $this->is_active
            && in_array($this->subscription_status, ['trial', 'active', 'past_due'])
            && ($this->subscription_status !== 'trial' || $this->isOnTrial());
    }

    /** Mandant ist archiviert — kein Login möglich. */
    public function isArchived(): bool
    {
        return $this->subscription_status === 'archived';
    }

    // ─────────────────────────────────────────────
    // Hilfsmethoden
    // ─────────────────────────────────────────────

    /**
     * Eindeutigen Slug aus dem Firmennamen generieren.
     * Prüft auf Kollisionen (inkl. soft-deleted).
     */
    public static function generateSlug(string $name): string
    {
        $slug = Str::slug($name);
        $original = $slug;
        $counter = 1;

        while (static::withTrashed()->where('slug', $slug)->exists()) {
            $slug = $original . '-' . $counter++;
        }

        return $slug;
    }
}
