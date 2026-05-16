<?php
namespace App\Traits;
use App\Models\Tenant;
use App\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
/**
 * Macht ein Eloquent-Model mandantenfähig.
 *
 * Verwendung (ein einziges Mal pro Model):
 *   use BelongsToTenant;
 *
 * Voraussetzungen:
 *   - Tabelle hat Spalte: unsignedBigInteger('tenant_id')->nullable()->index()
 *   - Model hat HasUlid Trait (empfohlen für öffentliche Referenzen)
 *
 * Was passiert automatisch:
 *   - Alle Queries werden nach tenant_id gefiltert (TenantScope)
 *   - Beim Erstellen wird tenant_id aus der Session gesetzt
 *   - Beziehung tenant() ist verfügbar
 *
 * Super-Admin Ausnahme:
 *   Model::withoutTenantScope()->get() — für globale Abfragen
 */
trait BelongsToTenant
{
    public static function bootBelongsToTenant(): void
    {
        // Globalen Scope registrieren — läuft bei JEDER Query
        static::addGlobalScope(new TenantScope());
        // Beim Erstellen: tenant_id automatisch aus Session setzen
        static::creating(function ($model) {
            if (empty($model->tenant_id) && session()->has('tenant_id')) {
                $model->tenant_id = session('tenant_id');
            }
        });
    }
    /**
     * Beziehung zum Mandanten.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
    /**
     * Query ohne Tenant-Filter ausführen.
     * Nur für Super-Admin oder interne Jobs verwenden.
     *
     * Beispiel:
     *   Station::withoutTenantScope()->where('is_active', true)->get()
     */
    public static function withoutTenantScope(): \Illuminate\Database\Eloquent\Builder
    {
        return static::query()->withoutGlobalScope(TenantScope::class);
    }
}
