<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * DSGVO Audit-Log Model (Stub).
 * Wird in Prompt 06 vollständig implementiert:
 * - encrypted Cast für old_values + new_values
 * - AuditService Integration
 * - Auth-Event-Listener
 */
class AuditLog extends Model
{
    // Logs sind unveränderlich — kein updated_at
    const UPDATED_AT = null;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'user_type',
        'action',
        'auditable_type',
        'auditable_id',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
        'reason',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    // ─────────────────────────────────────────────
    // Beziehungen
    // ─────────────────────────────────────────────

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ─────────────────────────────────────────────
    // Hilfsmethoden
    // ─────────────────────────────────────────────

    /**
     * Model-Name kurz (ohne Namespace) für Anzeige in Tabelle.
     */
    public function getAuditableTypeShortAttribute(): string
    {
        if (! $this->auditable_type) {
            return '—';
        }

        return class_basename($this->auditable_type);
    }
}
