<?php

namespace App\Filament\App\Resources\RollenResource\Pages;

use App\Filament\App\Resources\RollenResource;
use Filament\Resources\Pages\ListRecords;

class ListRollen extends ListRecords
{
    protected static string $resource = RollenResource::class;

    protected function getHeaderActions(): array
    {
        return [];   // CreateAction ist bereits in table()->headerActions()
    }
}
