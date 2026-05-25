<?php

namespace App\Models;

use App\Traits\Auditable;
use App\Traits\BelongsToTenant;
use App\Traits\HasUlid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\StationCompetitor;
use App\Models\StationFuelPrice;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Station extends Model
{
    use HasFactory, HasUlid, BelongsToTenant, SoftDeletes, Auditable;

    protected $table = 'gas_stations';

    protected array $auditExclude = ['enriched_at', 'prices_updated_at', 'updated_at'];

    protected $fillable = [
        // Kern
        'tenant_id',
        'name',
        'brand_id',
        'station_number',

        // Allgemein / Organisation
        'sales_channel',
        'ownership_type',
        'district',
        'district_description',
        'region',
        'region_manager',
        'station_number_fuel',
        'station_number_shop',
        'has_toll_terminal',

        // Adresse
        'street',
        'house_number',
        'zip',
        'city',
        'district_part',
        'state',
        'country',

        // Geo
        'latitude',
        'longitude',

        // Kontakt
        'phone',
        'fax',
        'email',
        'website',
        'academic_title',
        'contact_first_name',
        'contact_last_name',

        // Geschäftsdaten
        'tax_id',
        'trade_register',

        // Öffnungszeiten
        'opening_hours',
        'is_24h',
        'first_opening_ok',
        'first_opening_dk',

        // Ausstattung
        'num_pumps',
        'has_camera',
        'has_car_wash',
        'has_shop',
        'fuel_types',
        'services',
        'additional_businesses',
        'car_wash_details',

        // Shop
        'shop_size',
        'shop_type',
        'shop_class',
        'shop_setup_date',
        'nielsen_area',
        'price_region',
        'assortment_level',
        'shop_partner',
        'shop_operation_number',

        // Medien
        'logo_path',
        'photos',

        // Preise (competitors jetzt in station_competitors-Tabelle)
        'price_super',
        'price_e10',
        'price_diesel',
        'prices_updated_at',

        // Sonstiges
        'notes',
        'is_active',
        'settings',

        // API-Integration
        'benzinpreis_slug',
        'benzinpreis_hash',
        'enriched_at',
    ];

    protected function casts(): array
    {
        return [
            // Boolean
            'has_toll_terminal'  => 'boolean',
            'has_camera'         => 'boolean',
            'has_car_wash'       => 'boolean',
            'has_shop'           => 'boolean',
            'is_active'          => 'boolean',
            'is_24h'             => 'boolean',

            // Geo
            'latitude'           => 'decimal:8',
            'longitude'          => 'decimal:8',

            // Preise
            'price_super'        => 'decimal:3',
            'price_e10'          => 'decimal:3',
            'price_diesel'       => 'decimal:3',

            // Dates
            'first_opening_ok'   => 'date',
            'first_opening_dk'   => 'date',
            'shop_setup_date'    => 'date',
            'prices_updated_at'  => 'datetime',
            'enriched_at'        => 'datetime',

            // JSON
            'opening_hours'        => 'array',
            'fuel_types'           => 'array',
            'services'             => 'array',
            'additional_businesses'=> 'array',
            'car_wash_details'     => 'array',
            'photos'               => 'array',
            // 'competitors' entfernt — jetzt via station_competitors-Tabelle (HasMany)
            'settings'             => 'array',
        ];
    }

    // ─────────────────────────────────────────────
    // Beziehungen
    // ─────────────────────────────────────────────

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function bankAccounts(): HasMany
    {
        return $this->hasMany(GasStationBankAccount::class, 'gas_station_id');
    }

    public function fuelPrices(): HasMany
    {
        return $this->hasMany(StationFuelPrice::class, 'station_id')->latest('recorded_at');
    }

    // Relation heißt stationCompetitors (nicht competitors), da gas_stations.competitors
    // noch als Legacy-JSON-Spalte existiert und sonst die Relation überschattet würde.
    public function stationCompetitors(): HasMany
    {
        return $this->hasMany(StationCompetitor::class, 'station_id')->orderBy('distance_km');
    }

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

    public function getContactNameAttribute(): string
    {
        return trim(collect([
            $this->academic_title,
            $this->contact_first_name,
            $this->contact_last_name,
        ])->filter()->implode(' '));
    }

    public function getBrandNameAttribute(): string
    {
        return $this->brand?->name ?? '';
    }

    public function hasCoordinates(): bool
    {
        return $this->latitude !== null && $this->longitude !== null;
    }

    // ─────────────────────────────────────────────
    // Hilfsmethoden
    // ─────────────────────────────────────────────

    public static function defaultOpeningHours(): array
    {
        return collect(['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'])
            ->mapWithKeys(fn ($day) => [$day => ['open' => '06:00', 'close' => '22:00', 'is_closed' => false]])
            ->all();
    }

    public static function ownershipTypeOptions(): array
    {
        return [
            'DOFO' => 'DOFO — Dealer Owned, Franchisee Operated',
            'COFO' => 'COFO — Company Owned, Franchisee Operated',
            'DODO' => 'DODO — Dealer Owned, Dealer Operated',
            'CODO' => 'CODO — Company Owned, Dealer Operated',
            'COCO' => 'COCO — Company Owned, Company Operated',
        ];
    }

    public static function stateOptions(): array
    {
        return [
            'BB' => 'Brandenburg',
            'BE' => 'Berlin',
            'BW' => 'Baden-Württemberg',
            'BY' => 'Bayern',
            'HB' => 'Bremen',
            'HE' => 'Hessen',
            'HH' => 'Hamburg',
            'MV' => 'Mecklenburg-Vorpommern',
            'NI' => 'Niedersachsen',
            'NW' => 'Nordrhein-Westfalen',
            'RP' => 'Rheinland-Pfalz',
            'SH' => 'Schleswig-Holstein',
            'SL' => 'Saarland',
            'SN' => 'Sachsen',
            'ST' => 'Sachsen-Anhalt',
            'TH' => 'Thüringen',
        ];
    }
}
