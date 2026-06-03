<?php

namespace App\Models;

use App\Traits\HasUlid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class MdeDevice extends Model
{
    use HasUlid, SoftDeletes;

    protected $fillable = [
        'ulid', 'tenant_id', 'station_id',
        'device_name', 'device_model', 'android_id',
        'token_name', 'is_active', 'last_seen_at', 'app_version',
    ];

    protected $casts = [
        'is_active'     => 'boolean',
        'last_seen_at'  => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (MdeDevice $d) {
            if (empty($d->ulid)) {
                $d->ulid = (string) Str::ulid();
            }
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function station(): BelongsTo
    {
        return $this->belongsTo(Station::class, 'station_id');
    }

    public function touchLastSeen(): void
    {
        $this->update(['last_seen_at' => now()]);
    }
}
