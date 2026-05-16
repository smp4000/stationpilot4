<?php
namespace App\Scopes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
/**
 * Globaler Scope für Mandanten-Datenisolation.
 *
 * Filtert AUTOMATISCH alle Queries nach tenant_id aus der Session.
 * Wird von BelongsToTenant Trait auf jedem Model registriert.
 *
 * Verhalten:
 * - session('tenant_id') gesetzt  → WHERE tenant_id = ?
 * - session('tenant_id') nicht gesetzt (Super-Admin) → kein Filter, alle Daten
 *
 * Sicherheit: Session-Wert wird durch EnsureTenantContext Middleware
 * immer mit user->tenant_id abgeglichen — keine Session-Manipulation möglich.
 */
class TenantScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $tenantId = session('tenant_id');
        if ($tenantId) {
            $builder->where(
                $model->getTable() . '.tenant_id',
                $tenantId
            );
        }
    }
}
