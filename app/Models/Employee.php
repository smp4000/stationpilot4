<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class Employee extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'ulid', 'tenant_id', 'station_id', 'user_id',
        // Persönliche Daten
        'first_name', 'last_name', 'birth_name',
        'date_of_birth', 'place_of_birth', 'country_of_birth',
        'nationality', 'gender', 'marital_status',
        'severely_disabled', 'disability_degree',
        // Anschrift
        'street', 'house_number', 'zip', 'city', 'country',
        // Kontakt
        'phone_private', 'phone_mobile', 'email',
        // Steuer
        'tax_id', 'tax_class', 'tax_child_allowance', 'church_tax', 'tax_factor',
        // Sozialversicherung
        'social_security_number', 'health_insurance_name',
        'health_insurance_type', 'pension_insurance', 'unemployment_insurance',
        // Beschäftigung
        'employment_start', 'employment_end', 'employment_type',
        'employee_status', 'job_title', 'weekly_hours', 'vacation_days', 'cost_center',
        // Vergütung
        'wage_type', 'wage_amount', 'payment_interval',
        // Bank
        'iban', 'bic', 'account_holder', 'bank_name',
        // Ausbildung
        'education_level', 'vocational_training', 'vocational_title',
        // Führerschein
        'has_driving_license', 'driving_license_classes',
        'driving_license_number', 'driving_license_issued', 'driving_license_expires',
        // Arbeitsgenehmigung
        'residence_permit_type', 'residence_permit_expires',
        'work_permit_granted', 'work_permit_expires',
        // System
        'mde_pin', 'scan_code', 'nfc_uid', 'password', 'must_change_password',
        'invitation_token', 'invited_at', 'invitation_expires_at', 'status',
        // DSGVO
        'data_verified_at', 'retention_delete_after', 'anonymized_at',
    ];

    protected $casts = [
        // Verschlüsselte Felder (DSGVO — sensible Personaldaten)
        'date_of_birth'            => 'encrypted',
        'place_of_birth'           => 'encrypted',
        'country_of_birth'         => 'encrypted',
        'nationality'              => 'encrypted',
        'tax_id'                   => 'encrypted',
        'social_security_number'   => 'encrypted',
        'health_insurance_name'    => 'encrypted',
        'wage_amount'              => 'encrypted',
        'iban'                     => 'encrypted',
        'bic'                      => 'encrypted',
        'file_path'                => 'encrypted',

        // Typen
        'severely_disabled'        => 'boolean',
        'pension_insurance'        => 'boolean',
        'unemployment_insurance'   => 'boolean',
        'has_driving_license'      => 'boolean',
        'work_permit_granted'      => 'boolean',
        'driving_license_classes'  => 'array',
        'employment_start'         => 'date',
        'employment_end'           => 'date',
        'driving_license_issued'   => 'date',
        'driving_license_expires'  => 'date',
        'residence_permit_expires' => 'date',
        'work_permit_expires'      => 'date',
        'retention_delete_after'   => 'date',
        'invited_at'               => 'datetime',
        'invitation_expires_at'    => 'datetime',
        'data_verified_at'         => 'datetime',
        'anonymized_at'            => 'datetime',
        'must_change_password'     => 'boolean',
        'tax_class'                => 'integer',
        'disability_degree'        => 'integer',
        'vacation_days'            => 'integer',
    ];

    // ─── Boot ──────────────────────────────────────────────────────────────

    protected $attributes = [
        'status'          => 'neu',
        'employee_status' => 'aktiv',
    ];

    protected static function booted(): void
    {
        static::creating(function (Employee $e) {
            if (empty($e->ulid)) {
                $e->ulid = (string) Str::ulid();
            }
            if (empty($e->status)) {
                $e->status = 'neu';
            }
        });
    }

    // ─── Accessors ─────────────────────────────────────────────────────────

    public function fullName(): string
    {
        return trim($this->first_name . ' ' . $this->last_name);
    }

    public function fullAddress(): string
    {
        return collect([
            trim($this->street . ' ' . ($this->house_number ?? '')),
            trim(($this->zip ?? '') . ' ' . ($this->city ?? '')),
        ])->filter()->implode(', ');
    }

    // MDE-PIN setzen (automatisch gehasht)
    public function setMdePinAttribute(string $pin): void
    {
        $this->attributes['mde_pin'] = Hash::make($pin);
    }

    public function verifyMdePin(string $pin): bool
    {
        return Hash::check($pin, $this->mde_pin);
    }

    // ─── Selects / Options ─────────────────────────────────────────────────

    public static function genderOptions(): array
    {
        return ['m' => 'Männlich', 'w' => 'Weiblich', 'd' => 'Divers'];
    }

    public static function maritalStatusOptions(): array
    {
        return [
            'ledig'       => 'Ledig',
            'verheiratet' => 'Verheiratet',
            'geschieden'  => 'Geschieden',
            'verwitwet'   => 'Verwitwet',
            'eingetragen' => 'Eingetragene Lebenspartnerschaft',
        ];
    }

    public static function employmentTypeOptions(): array
    {
        return [
            'vollzeit'    => 'Vollzeit',
            'teilzeit'    => 'Teilzeit',
            'minijob'     => 'Minijob (geringfügig)',
            'kurzfristig' => 'Kurzfristig Beschäftigt',
            'azubi'       => 'Auszubildender',
            'praktikum'   => 'Praktikum',
            'werkstudent' => 'Werkstudent',
        ];
    }

    public static function employeeStatusOptions(): array
    {
        return [
            'arbeiter'    => 'Arbeiter',
            'angestellter'=> 'Angestellter',
            'azubi'       => 'Auszubildender',
            'praktikant'  => 'Praktikant',
        ];
    }

    public static function wageTypeOptions(): array
    {
        return [
            'stundenlohn' => 'Stundenlohn',
            'gehalt'      => 'Monatsgehalt',
            'minijob'     => 'Minijob-Pauschale',
        ];
    }

    public static function healthInsuranceTypeOptions(): array
    {
        return [
            'pflicht'     => 'Gesetzlich pflichtversichert',
            'freiwillig'  => 'Freiwillig gesetzlich versichert',
            'privat'      => 'Privat versichert',
            'befreit'     => 'Befreit',
        ];
    }

    public static function educationLevelOptions(): array
    {
        return [
            'kein'          => 'Kein Abschluss',
            'hauptschule'   => 'Hauptschulabschluss',
            'realschule'    => 'Mittlere Reife / Realschulabschluss',
            'abitur'        => 'Abitur / Fachabitur',
            'studium'       => 'Hochschulabschluss',
        ];
    }

    public static function vocationalTrainingOptions(): array
    {
        return [
            'keine'          => 'Keine Ausbildung',
            'in_ausbildung'  => 'In Ausbildung',
            'abgeschlossen'  => 'Abgeschlossene Ausbildung',
            'meister'        => 'Meister / Techniker',
        ];
    }

    public static function churchTaxOptions(): array
    {
        return [
            'keine' => 'Keine Kirchensteuer',
            'ev'    => 'Evangelisch',
            'rk'    => 'Römisch-Katholisch',
            'ak'    => 'Alt-Katholisch',
            'fr'    => 'Freireligiös',
            'jd'    => 'Jüdisch',
        ];
    }

    public static function drivingLicenseClassOptions(): array
    {
        return array_combine(
            ['AM','A1','A2','A','B','BE','C1','C1E','C','CE','D1','D1E','D','DE','L','T'],
            ['AM','A1','A2','A','B','BE','C1','C1E','C','CE','D1','D1E','D','DE','L','T']
        );
    }

    // ─── Relationen ────────────────────────────────────────────────────────

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function station(): BelongsTo
    {
        return $this->belongsTo(Station::class);
    }

    public function stations(): BelongsToMany
    {
        return $this->belongsToMany(Station::class, 'employee_station', 'employee_id', 'station_id')
                    ->withPivot('is_primary')
                    ->orderBy('name');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function emergencyContacts(): HasMany
    {
        return $this->hasMany(EmployeeEmergencyContact::class)->orderBy('priority');
    }

    public function previousEmployment(): HasOne
    {
        return $this->hasOne(EmployeePreviousEmployment::class);
    }

    /**
     * Alias als HasMany für Filament-Repeater (DB-Unique-Constraint sichert max. 1 Eintrag).
     */
    public function previousEmploymentList(): HasMany
    {
        return $this->hasMany(EmployeePreviousEmployment::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(EmployeeDocument::class)->orderByDesc('created_at');
    }

    public function accessLog(): HasMany
    {
        return $this->hasMany(EmployeeAccessLog::class, 'employee_id')->orderByDesc('accessed_at');
    }

    public function keyHandovers(): HasMany
    {
        return $this->hasMany(\App\Models\KeyHandover::class);
    }

    public function credentials(): HasMany
    {
        return $this->hasMany(\App\Models\StationCredential::class);
    }

    public function contracts(): HasMany
    {
        return $this->hasMany(\App\Models\EmployeeContract::class);
    }

    // ─── DSGVO-Hilfsmethoden ───────────────────────────────────────────────

    /**
     * Mitarbeiter anonymisieren (nach Ablauf Aufbewahrungsfrist).
     * Löscht alle personenbezogenen Daten, behält statistische Felder.
     */
    public function anonymize(): void
    {
        $this->update([
            'first_name'             => 'Anonym',
            'last_name'              => 'Gelöscht',
            'birth_name'             => null,
            'date_of_birth'          => null,
            'place_of_birth'         => null,
            'country_of_birth'       => null,
            'nationality'            => null,
            'street'                 => null,
            'house_number'           => null,
            'zip'                    => null,
            'city'                   => null,
            'phone_private'          => null,
            'phone_mobile'           => null,
            'email'                  => null,
            'tax_id'                 => null,
            'social_security_number' => null,
            'health_insurance_name'  => null,
            'iban'                   => null,
            'bic'                    => null,
            'account_holder'         => null,
            'bank_name'              => null,
            'wage_amount'            => null,
            'mde_pin'                => null,
            'invitation_token'       => null,
            'anonymized_at'          => now(),
        ]);

        // Notfallkontakte löschen
        $this->emergencyContacts()->delete();

        // Dokumente physisch löschen
        foreach ($this->documents as $doc) {
            $doc->deleteFile();
            $doc->delete();
        }
    }
}
