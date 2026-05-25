<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class EmployeeDocument extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'ulid', 'employee_id', 'uploaded_by',
        'category', 'document_type', 'title',
        'file_path', 'file_hash', 'mime_type', 'file_size',
        'issued_at', 'expires_at', 'expiry_notified',
        'notes', 'last_accessed_at',
    ];

    protected $casts = [
        'file_path'          => 'encrypted',
        'issued_at'          => 'date',
        'expires_at'         => 'date',
        'last_accessed_at'   => 'datetime',
        'expiry_notified'    => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (EmployeeDocument $d) {
            if (empty($d->ulid)) {
                $d->ulid = (string) Str::ulid();
            }
        });
    }

    // ─── Kategorien ────────────────────────────────────────────────────────

    public static function categoryOptions(): array
    {
        return [
            'vertrag'      => 'Vertrag',
            'ausweis'      => 'Ausweisdokument',
            'gesundheit'   => 'Gesundheit',
            'qualifikation'=> 'Qualifikation / Zeugnis',
            'soziales'     => 'Sozialversicherung',
            'steuer'       => 'Steuer',
            'disziplinar'  => 'Disziplinarmaßnahme',
            'genehmigung'  => 'Genehmigung / Erlaubnis',
            'sonstiges'    => 'Sonstiges',
        ];
    }

    public static function documentTypeOptions(): array
    {
        return [
            'vertrag'         => [
                'arbeitsvertrag'     => 'Arbeitsvertrag',
                'aenderungsvertrag'  => 'Änderungsvertrag',
                'aufhebungsvertrag'  => 'Aufhebungsvertrag',
            ],
            'ausweis'         => [
                'personalausweis'    => 'Personalausweis-Kopie',
                'reisepass'          => 'Reisepass-Kopie',
            ],
            'gesundheit'      => [
                'gesundheitszeugnis' => 'Gesundheitszeugnis (§ 43 IfSG)',
                'impfnachweis'       => 'Impfnachweis',
            ],
            'qualifikation'   => [
                'abschlusszeugnis'   => 'Abschlusszeugnis',
                'fuehrerschein_kopie'=> 'Führerschein-Kopie',
                'zertifikat'         => 'Zertifikat / Weiterbildung',
            ],
            'soziales'        => [
                'sv_ausweis'         => 'Sozialversicherungsausweis',
                'krankenkasse'       => 'Krankenkassen-Nachweis',
            ],
            'steuer'          => [
                'lohnsteuerbescheinigung' => 'Lohnsteuerbescheinigung',
                'elstam'             => 'ELSTAM-Ausdruck',
            ],
            'disziplinar'     => [
                'abmahnung'          => 'Abmahnung',
                'ermahnung'          => 'Ermahnung',
            ],
            'genehmigung'     => [
                'arbeitserlaubnis'   => 'Arbeitserlaubnis',
                'aufenthaltstitel'   => 'Aufenthaltstitel',
            ],
            'sonstiges'       => [
                'sonstiges'          => 'Sonstiges',
            ],
        ];
    }

    // ─── Datei-Operationen ─────────────────────────────────────────────────

    /**
     * Temporäre signierte Download-URL generieren (DSGVO: kein Direktzugriff).
     */
    public function temporaryUrl(int $minutes = 5): string
    {
        return Storage::disk('private')->temporaryUrl(
            $this->file_path,
            now()->addMinutes($minutes)
        );
    }

    /**
     * Datei physisch löschen.
     */
    public function deleteFile(): void
    {
        if ($this->file_path && Storage::disk('private')->exists($this->file_path)) {
            Storage::disk('private')->delete($this->file_path);
        }
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function expiresInDays(): ?int
    {
        return $this->expires_at ? (int) now()->diffInDays($this->expires_at, false) : null;
    }

    // ─── Relationen ────────────────────────────────────────────────────────

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
