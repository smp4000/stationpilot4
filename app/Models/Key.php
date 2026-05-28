<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Key extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id', 'station_id', 'type', 'name', 'key_number',
        'description', 'copies_total', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active'    => 'boolean',
            'copies_total' => 'integer',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function station(): BelongsTo
    {
        return $this->belongsTo(Station::class);
    }

    public function handovers(): HasMany
    {
        return $this->hasMany(KeyHandover::class);
    }

    public function activeHandovers(): HasMany
    {
        return $this->hasMany(KeyHandover::class)
            ->with('employee')
            ->whereNull('returned_at')
            ->whereNull('employee_returned_at');
    }

    // Art-Optionen für Schlüssel / Zugangsmedien
    public static function typeOptions(): array
    {
        return [
            'schluessel' => '🔑 Schlüssel',
            'chip'       => '📡 Chip (Alarmanlage / Funk)',
            'karte'      => '💳 Zugangskarte',
            'code'       => '🔢 Code / PIN',
            'sonstiges'  => '📦 Sonstiges',
        ];
    }

    public static function typeLabel(string $type): string
    {
        return static::typeOptions()[$type] ?? $type;
    }

    // Anzeige-Label für Dropdown (type + name + nummer + station)
    public function getSelectLabelAttribute(): string
    {
        $typeLabels = [
            'schluessel' => '🔑',
            'chip'       => '📡',
            'karte'      => '💳',
            'code'       => '🔢',
            'sonstiges'  => '📦',
        ];
        $icon = $typeLabels[$this->type] ?? '📦';
        $label = $icon . ' ' . $this->name;
        if ($this->key_number) $label .= ' (' . $this->key_number . ')';
        if ($this->station)    $label .= ' – ' . $this->station->name;
        return $label;
    }
}
