<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GasStationBankAccount extends Model
{
    protected $fillable = [
        'gas_station_id',
        'iban',
        'bank_name',
        'bic',
        'account_holder',
        'description',
        'account_type',
    ];

    protected function casts(): array
    {
        return [
            // IBAN & BIC verschlüsselt gespeichert (DSGVO)
            'iban' => 'encrypted',
            'bic'  => 'encrypted',
        ];
    }

    // ─────────────────────────────────────────────
    // Beziehungen
    // ─────────────────────────────────────────────

    public function station(): BelongsTo
    {
        return $this->belongsTo(Station::class, 'gas_station_id');
    }

    // ─────────────────────────────────────────────
    // Kontoarten
    // ─────────────────────────────────────────────

    public static function accountTypeOptions(): array
    {
        return [
            'geschaeftskonto' => 'Geschäftskonto',
            'agenturkonto'    => 'Agenturkonto',
            'lottokonto'      => 'Lottokonto',
            'shop'            => 'Shop-Konto',
            'waschanlage'     => 'Waschanlage',
            'sonstiges'       => 'Sonstiges',
        ];
    }

    // ─────────────────────────────────────────────
    // Accessor
    // ─────────────────────────────────────────────

    public function getDisplayNameAttribute(): string
    {
        $type = self::accountTypeOptions()[$this->account_type] ?? $this->account_type;
        return ($this->bank_name ?? 'Bank') . ' — ' . ($type ?? 'Konto');
    }
}
