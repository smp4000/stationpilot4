<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class EmployeeContract extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'tenant_id', 'employee_id', 'created_by',
        'contract_type', 'status',
        'employer_name', 'employer_company', 'employer_street', 'employer_zip', 'employer_city', 'signing_location',
        'contract_data',
        'pdf_path', 'is_uploaded', 'original_filename',
        'employee_sign_token', 'employee_signed_at', 'employee_signature', 'sent_to_employee_at',
        'employer_signature', 'employer_signed_at', 'employer_signed_by',
        'notes',
    ];

    protected $casts = [
        'contract_data'       => 'array',
        'is_uploaded'         => 'boolean',
        'employee_signed_at'  => 'datetime',
        'employer_signed_at'  => 'datetime',
        'sent_to_employee_at' => 'datetime',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function isSigned(): bool
    {
        return $this->employee_signed_at !== null;
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function contractTypeLabel(): string
    {
        return match ($this->contract_type) {
            'unbefristet' => 'Unbefristet',
            'befristet'   => 'Befristet',
            'minijob'     => 'Minijob (geringfügig)',
            default       => $this->contract_type,
        };
    }

    public function generateSignToken(): string
    {
        $token = Str::random(64);
        $this->employee_sign_token = $token;
        $this->save();
        return $token;
    }

    // Liest einzelne contract_data Felder sicher aus
    public function data(string $key, mixed $default = null): mixed
    {
        return data_get($this->contract_data, $key, $default);
    }
}
