<?php

namespace App\Filament\App\Resources\DocumentTemplateResource\Pages;

use App\Filament\App\Resources\DocumentTemplateResource;
use App\Models\DocumentTemplate;
use Filament\Resources\Pages\CreateRecord;

class CreateDocumentTemplate extends CreateRecord
{
    protected static string $resource = DocumentTemplateResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['tenant_id']  = session('tenant_id');
        $data['is_default'] = false;
        $data['is_active']  = $data['is_active'] ?? true;

        // Pre-fill body with starter template if empty
        if (empty($data['body']) && !empty($data['document_type'])) {
            $data['body'] = DocumentTemplate::getDefaultBody($data['document_type'], $data['sub_type'] ?? null);
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('edit', ['record' => $this->getRecord()]);
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Vorlage erstellt';
    }
}
