<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Brand extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'color',
        'logo_path',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active'  => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    // ─────────────────────────────────────────────
    // Beziehungen
    // ─────────────────────────────────────────────

    public function stations(): HasMany
    {
        return $this->hasMany(Station::class);
    }

    // ─────────────────────────────────────────────
    // Hilfsmethoden
    // ─────────────────────────────────────────────

    /**
     * Alle aktiven Marken als [id => name] für Filament Select-Felder.
     */
    public static function selectOptions(): array
    {
        return static::where('is_active', true)
            ->orderBy('sort_order')
            ->pluck('name', 'id')
            ->toArray();
    }
}
