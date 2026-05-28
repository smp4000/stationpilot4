<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KeyHandover extends Model
{
    use HasFactory;

    protected $fillable = [
        'key_id', 'employee_id', 'handed_out_by', 'handed_out_at',
        'returned_to', 'returned_at', 'notes',
        'employee_confirmed_at', 'receipt_signature',
        'employee_returned_at', 'return_signature',
    ];

    protected function casts(): array
    {
        return [
            'handed_out_at'        => 'datetime',
            'returned_at'          => 'datetime',
            'employee_confirmed_at'=> 'datetime',
            'employee_returned_at' => 'datetime',
        ];
    }

    public function key(): BelongsTo
    {
        return $this->belongsTo(Key::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function handedOutBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handed_out_by');
    }

    public function returnedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'returned_to');
    }

    public function isActive(): bool
    {
        return $this->returned_at === null && $this->employee_returned_at === null;
    }

    public function isReceiptConfirmed(): bool
    {
        return $this->employee_confirmed_at !== null;
    }

    public function isReturnConfirmed(): bool
    {
        return $this->employee_returned_at !== null;
    }
}
