<?php

namespace App\Filament\App\Widgets;

use App\Models\EmployeeContract;
use Filament\Widgets\Widget;

class ContractPdfPreviewWidget extends Widget
{
    protected string $view = 'filament.app.widgets.contract-pdf-preview';

    protected int|string|array $columnSpan = 'full';

    public ?EmployeeContract $contract = null;
    public string $pdfUrl = '';

    public function mount(): void
    {
        $recordId = request()->route('record');

        if (!$recordId) {
            return;
        }

        $this->contract = EmployeeContract::where('id', $recordId)
            ->where('tenant_id', session('tenant_id'))
            ->with('employee')
            ->first();

        if ($this->contract) {
            $this->pdfUrl = route('pdf.contract.preview', $this->contract->id);
        }
    }
}
