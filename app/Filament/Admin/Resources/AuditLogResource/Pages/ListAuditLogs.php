<?php

namespace App\Filament\Admin\Resources\AuditLogResource\Pages;

use App\Filament\Admin\Resources\AuditLogResource;
use Filament\Resources\Pages\ListRecords;

class ListAuditLogs extends ListRecords
{
    protected static string $resource = AuditLogResource::class;

    // Kein CreateAction — Audit-Logs sind unveränderlich
    protected function getHeaderActions(): array
    {
        return [];
    }
}
