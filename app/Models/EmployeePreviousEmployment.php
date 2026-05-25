<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeePreviousEmployment extends Model
{
    protected $table = 'employee_previous_employment';

    protected $fillable = [
        'employee_id', 'employer_name',
        'employed_from', 'employed_until',
        'gross_wages_ytd', 'income_tax_ytd', 'solidarity_tax_ytd',
    ];

    protected $casts = [
        'employed_from'       => 'date',
        'employed_until'      => 'date',
        'gross_wages_ytd'     => 'encrypted',
        'income_tax_ytd'      => 'encrypted',
        'solidarity_tax_ytd'  => 'encrypted',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
