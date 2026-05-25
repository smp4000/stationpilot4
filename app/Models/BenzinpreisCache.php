<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BenzinpreisCache extends Model
{
    protected $table      = 'benzinpreis_cache';
    protected $primaryKey = 'hash';
    protected $keyType    = 'string';
    public    $incrementing = false;

    protected $fillable = [
        'hash',
        'slug',
        'mts_uuid',
        'name',
        'brand',
        'e5',
        'e10',
        'diesel',
        'fetched_at',
        'last_changed_at',
    ];

    protected function casts(): array
    {
        return [
            'e5'              => 'decimal:3',
            'e10'             => 'decimal:3',
            'diesel'          => 'decimal:3',
            'fetched_at'      => 'datetime',
            'last_changed_at' => 'datetime',
        ];
    }

    /** Format a price value for display, e.g. "1,859 €" */
    public function formatPrice(?string $fuel): string
    {
        $val = $this->{$fuel} ?? null;
        return $val ? number_format((float) $val, 3, ',', '.') . ' €' : '—';
    }
}
