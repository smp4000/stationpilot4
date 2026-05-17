<?php

namespace App\Models;

use App\Traits\Auditable;
use App\Traits\BelongsToTenant;
use App\Traits\HasUlid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Station extends Model
{
    use HasFactory, HasUlid, BelongsToTenant, SoftDeletes, Auditable;

    protected array $auditExclude = ['enriched_at', 'updated_at'];

    protected $fillable = [
        'tenant_id', 'name', 'brand', 'station_number',
        'street', 'house_number', 'zip', 'city', 'country',
        'lat', 'lng', 'opening_hours',
        'tank_count', 'dispenser_count', 'has_car_wash', 'wash_model',
        'has_bistro', 'has_shop', 'meta',
        'bank_name', 'iban', 'bic', 'account_holder',
        'benzinpreis_slug', 'benzinpreis_hash', 'enriched_at',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'opening_hours' => 'array',
            'meta'          => 'array',
            'has_car_wash'  => 'boolean',
            'has_bistro'    => 'boolean',
            'has_shop'      => 'boolean',
            'is_active'     => 'boolean',
            'lat'           => 'decimal:8',
            'lng'           => 'decimal:8',
            'enriched_at'   => 'datetime',
            'iban'          => 'encrypted',
            'bic'           => 'encrypted',
        ];
    }

    // ─────────────────────────────────────────────
    // Beziehungen
    // ─────────────────────────────────────────────

    public function employees(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'station_user')
            ->withPivot(['role_at_station', 'assigned_at', 'removed_at'])
            ->wherePivotNull('removed_at')
            ->withTimestamps();
    }

    // ─────────────────────────────────────────────
    // Accessors
    // ─────────────────────────────────────────────

    public function getFullAddressAttribute(): string
    {
        return trim(collect([
            trim(($this->street ?? '') . ' ' . ($this->house_number ?? '')),
            trim(($this->zip ?? '') . ' ' . ($this->city ?? '')),
        ])->filter()->implode(', '));
    }

    public function hasCoordinates(): bool
    {
        return $this->lat !== null && $this->lng !== null;
    }

    public static function defaultOpeningHours(): array
    {
        return collect(['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'])
            ->mapWithKeys(fn($day) => [$day => ['open' => '06:00', 'close' => '22:00', 'is_closed' => false]])
            ->all();
    }
}
