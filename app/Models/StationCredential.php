<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class StationCredential extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id', 'employee_id', 'type', 'label',
        'username', 'credential_value', 'pin_value', 'notes', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active'        => 'boolean',
            'credential_value' => 'encrypted',
            'pin_value'        => 'encrypted',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function stations(): BelongsToMany
    {
        return $this->belongsToMany(
            Station::class,
            'credential_station',
            'station_credential_id',
            'station_id'
        );
    }

    // Optionen aus der DB laden (tenant-spezifisch)
    public static function typeOptions(?int $tenantId = null): array
    {
        $tenantId ??= auth()->user()?->tenant_id;
        if (! $tenantId) return [];
        return CredentialType::optionsForTenant($tenantId);
    }

    // Fallback: gibt einfach den gespeicherten Typ zurück
    public static function typeLabel(string $type): string
    {
        return $type;
    }
}
