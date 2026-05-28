<?php

namespace App\Filament\App\Resources\DocumentTemplateResource\Pages;

use App\Filament\App\Resources\DocumentTemplateResource;
use App\Models\DocumentTemplate;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDocumentTemplates extends ListRecords
{
    protected static string $resource = DocumentTemplateResource::class;

    public function mount(): void
    {
        parent::mount();

        $tenantId = session('tenant_id');
        if ($tenantId) {
            DocumentTemplate::seedDefaultsForTenant($tenantId);
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Neue Vorlage'),
        ];
    }
}
