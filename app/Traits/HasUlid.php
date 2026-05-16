<?php

namespace App\Traits;

use Illuminate\Support\Str;

/**
 * Generiert beim Erstellen automatisch eine ULID als öffentliche ID.
 * ULID: 26 Zeichen, URL-sicher, zeitbasiert sortierbar, keine Bindestriche.
 *
 * Intern: BIGINT id (schnelle DB-JOINs)
 * Öffentlich: ulid (URLs, QR-Codes, NFC-Tags, API)
 *
 * Verwendung: `use HasUlid;` im Model.
 * Voraussetzung: Tabelle hat Spalte `ulid` char(26) unique.
 */
trait HasUlid
{
    protected static function bootHasUlid(): void
    {
        static::creating(function ($model) {
            if (empty($model->ulid)) {
                $model->ulid = (string) Str::ulid();
            }
        });
    }

    /**
     * Route-Model-Binding über ULID statt numerischer ID.
     * Niemals echte IDs in URLs exponieren.
     */
    public function getRouteKeyName(): string
    {
        return 'ulid';
    }

    /**
     * Model direkt per ULID finden.
     */
    public static function findByUlid(string $ulid): ?static
    {
        return static::where('ulid', $ulid)->first();
    }

    /**
     * Model per ULID finden oder 404 werfen.
     */
    public static function findByUlidOrFail(string $ulid): static
    {
        return static::where('ulid', $ulid)->firstOrFail();
    }
}
