<?php

namespace App\Filament\App\Resources\GoPilotRollenResource\Pages;

use App\Filament\App\Resources\GoPilotRollenResource;
use Filament\Resources\Pages\ListRecords;

class ListGoPilotRollen extends ListRecords
{
    protected static string $resource = GoPilotRollenResource::class;

    protected function getHeaderActions(): array
    {
        return [];   // CreateAction ist bereits in table()->headerActions()
    }
}
