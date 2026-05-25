<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FuelType extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'category',
        'brand',
        'color',
        'description',
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
    // Scopes
    // ─────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    // ─────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────

    /**
     * Slug → Name map für Filament Select/CheckboxList.
     * Optionale Gruppe nach Kategorie.
     */
    public static function selectOptions(bool $grouped = false): array
    {
        $items = static::active()->ordered()->get(['slug', 'name', 'category', 'brand']);

        if ($grouped) {
            $labels = [
                'standard'   => 'Standard',
                'premium'    => 'Premium / Marken',
                'alternativ' => 'Alternativ & Eco',
                'elektro'    => 'Elektro',
            ];
            $groups = [];
            foreach ($items as $item) {
                $group = $labels[$item->category] ?? $item->category;
                $groups[$group][$item->slug] = $item->name;
            }
            return $groups;
        }

        return $items->pluck('name', 'slug')->toArray();
    }

    public static function categoryOptions(): array
    {
        return [
            'standard'   => 'Standard',
            'premium'    => 'Premium / Marken',
            'alternativ' => 'Alternativ & Eco',
            'elektro'    => 'Elektro',
        ];
    }
}
