<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeAccessLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'employee_id', 'accessed_by', 'action', 'resource',
        'resource_id', 'ip_address', 'user_agent',
        'changed_fields', 'accessed_at',
    ];

    protected $casts = [
        'changed_fields' => 'array',
        'accessed_at'    => 'datetime',
    ];

    // Aktionen
    public const ACTION_VIEW     = 'view';
    public const ACTION_EDIT     = 'edit';
    public const ACTION_DOWNLOAD = 'download';
    public const ACTION_DELETE   = 'delete';
    public const ACTION_INVITE   = 'invite';
    public const ACTION_EXPORT   = 'export';

    /**
     * Zugriff protokollieren.
     */
    public static function record(
        int    $employeeId,
        string $action,
        string $resource,
        ?int   $resourceId    = null,
        ?array $changedFields = null
    ): void {
        static::create([
            'employee_id'    => $employeeId,
            'accessed_by'    => auth()->id(),
            'action'         => $action,
            'resource'       => $resource,
            'resource_id'    => $resourceId,
            'ip_address'     => request()->ip(),
            'user_agent'     => substr(request()->userAgent() ?? '', 0, 300),
            'changed_fields' => $changedFields,
            'accessed_at'    => now(),
        ]);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function accessedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'accessed_by');
    }
}
