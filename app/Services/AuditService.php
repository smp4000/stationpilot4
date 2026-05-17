<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class AuditService
{
    private const SKIP_FIELDS = [
        'password',
        'pin_hash',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
    ];

    private const MASK_FIELDS = [
        'iban',
        'tax_id',
        'ust_id',
        'bank_account',
    ];

    // ─────────────────────────────────────────────
    // Sanitize
    // ─────────────────────────────────────────────

    public function sanitize(array $data): array
    {
        $result = [];
        foreach ($data as $key => $value) {
            if (in_array($key, self::SKIP_FIELDS)) {
                continue;
            }
            $result[$key] = in_array($key, self::MASK_FIELDS) ? '***' : $value;
        }
        return $result;
    }

    // ─────────────────────────────────────────────
    // Fehlertoleranz — Audit darf nie die App brechen
    // ─────────────────────────────────────────────

    public function safe(callable $fn): void
    {
        try {
            $fn();
        } catch (\Throwable) {
            // intentionally swallowed
        }
    }

    // ─────────────────────────────────────────────
    // CRUD-Logging
    // ─────────────────────────────────────────────

    public function logCreate(Model $model): void
    {
        $this->safe(function () use ($model) {
            AuditLog::create($this->basePayload($model, 'created') + [
                'new_values' => $this->sanitize($model->getAttributes()),
            ]);
        });
    }

    public function logUpdate(Model $model, array $originalAttributes): void
    {
        $changes = $model->getChanges();

        $exclude = array_merge(
            method_exists($model, 'getAuditExclude') ? $model->getAuditExclude() : [],
            ['updated_at']
        );

        foreach ($exclude as $field) {
            unset($changes[$field]);
        }

        if (empty($changes)) {
            return;
        }

        $old = array_intersect_key($originalAttributes, $changes);

        $this->safe(function () use ($model, $old, $changes) {
            AuditLog::create($this->basePayload($model, 'updated') + [
                'old_values' => $this->sanitize($old),
                'new_values' => $this->sanitize($changes),
            ]);
        });
    }

    public function logDelete(Model $model): void
    {
        $this->safe(function () use ($model) {
            AuditLog::create($this->basePayload($model, 'deleted') + [
                'old_values' => $this->sanitize($model->getAttributes()),
            ]);
        });
    }

    // ─────────────────────────────────────────────
    // Auth-Logging
    // ─────────────────────────────────────────────

    public function logLogin(User $user): void
    {
        $this->safe(function () use ($user) {
            AuditLog::create([
                'tenant_id'  => $user->tenant_id,
                'user_id'    => $user->id,
                'user_type'  => 'user',
                'action'     => 'login',
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
        });
    }

    public function logLogout(User $user): void
    {
        $this->safe(function () use ($user) {
            AuditLog::create([
                'tenant_id'  => $user->tenant_id,
                'user_id'    => $user->id,
                'user_type'  => 'user',
                'action'     => 'logout',
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
        });
    }

    public function logFailedLogin(string $email): void
    {
        $this->safe(function () use ($email) {
            AuditLog::create([
                'user_type'  => 'guest',
                'action'     => 'login_failed',
                'new_values' => ['email' => $email],
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
        });
    }

    // ─────────────────────────────────────────────
    // Interner Basis-Payload
    // ─────────────────────────────────────────────

    private function basePayload(Model $model, string $action): array
    {
        $user = auth()->user();

        return [
            'tenant_id'      => session('tenant_id') ?? ($model->tenant_id ?? null),
            'user_id'        => $user?->id,
            'user_type'      => $user ? 'user' : 'system',
            'action'         => $action,
            'auditable_type' => get_class($model),
            'auditable_id'   => method_exists($model, 'auditKey')
                                    ? $model->auditKey()
                                    : (string) $model->getKey(),
            'ip_address'     => request()->ip(),
            'user_agent'     => request()->userAgent(),
        ];
    }
}
