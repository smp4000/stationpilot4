<?php

namespace App\Traits;

use App\Services\AuditService;

trait Auditable
{
    private array $auditOriginals = [];

    public static function bootAuditable(): void
    {
        static::created(function (self $model) {
            app(AuditService::class)->logCreate($model);
        });

        static::updating(function (self $model) {
            $model->auditOriginals = $model->getOriginal();
        });

        static::updated(function (self $model) {
            app(AuditService::class)->logUpdate($model, $model->auditOriginals);
            $model->auditOriginals = [];
        });

        static::deleted(function (self $model) {
            app(AuditService::class)->logDelete($model);
        });
    }

    public function getAuditExclude(): array
    {
        return $this->auditExclude ?? [];
    }

    public function auditKey(): string
    {
        return (string) $this->getKey();
    }
}
