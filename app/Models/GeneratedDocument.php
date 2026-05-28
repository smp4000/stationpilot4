<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class GeneratedDocument extends Model
{
    protected $fillable = [
        'tenant_id', 'template_id', 'document_type',
        'related_type', 'related_id',
        'pdf_path', 'generated_by', 'generated_at',
        'sign_token', 'signed_at', 'signature',
    ];

    protected $casts = [
        'generated_at' => 'datetime',
        'signed_at'    => 'datetime',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(DocumentTemplate::class);
    }

    public function related(): MorphTo
    {
        return $this->morphTo();
    }

    public function generatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }
}
