<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CredentialType extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id', 'name', 'icon', 'sort_order', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active'  => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    // ── Dropdown-Optionen für einen Tenant ──────────────────────────────────

    public static function optionsForTenant(int $tenantId): array
    {
        static::ensureDefaults($tenantId);

        return static::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->mapWithKeys(fn ($t) => [$t->name => $t->icon . ' ' . $t->name])
            ->toArray();
    }

    // ── Standardtypen anlegen wenn noch keine vorhanden ─────────────────────

    public static function ensureDefaults(int $tenantId): void
    {
        if (static::where('tenant_id', $tenantId)->exists()) {
            return;
        }

        $defaults = [
            ['icon' => '🖥️', 'name' => 'Kasse',            'sort_order' => 1],
            ['icon' => '💳', 'name' => 'EC-Cash',           'sort_order' => 2],
            ['icon' => '📟', 'name' => 'Zahlungsterminal',  'sort_order' => 3],
            ['icon' => '🔔', 'name' => 'Alarmanlage',       'sort_order' => 4],
            ['icon' => '🔒', 'name' => 'Tresor',            'sort_order' => 5],
            ['icon' => '⛽', 'name' => 'Tankautomat',       'sort_order' => 6],
            ['icon' => '📦', 'name' => 'Sonstiges',         'sort_order' => 99],
        ];

        foreach ($defaults as $d) {
            static::create(array_merge($d, [
                'tenant_id' => $tenantId,
                'is_active' => true,
            ]));
        }
    }
}
