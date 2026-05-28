<?php

namespace App\Http\Controllers;

use App\Models\DocumentTemplate;
use App\Models\EmployeeContract;
use App\Services\PlaceholderRegistry;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ContractSigningController extends Controller
{
    public function show(string $token): View|RedirectResponse
    {
        $contract = EmployeeContract::where('employee_sign_token', $token)
            ->with('employee')
            ->firstOrFail();

        return view('contract.sign', compact('contract'));
    }

    public function submit(Request $request, string $token): RedirectResponse
    {
        $contract = EmployeeContract::where('employee_sign_token', $token)
            ->with('employee')
            ->firstOrFail();

        if ($contract->employee_signed_at) {
            return redirect()->route('contract.sign', $token)->with('signed', true);
        }

        $request->validate(['signature' => 'required|string']);

        $contract->update([
            'employee_signature'  => $request->input('signature'),
            'employee_signed_at'  => now(),
            'status'              => 'employee_signed',
        ]);

        // PDF mit Unterschrift neu generieren
        $this->regeneratePdf($contract);

        return redirect()->route('contract.sign', $token)->with('signed', true);
    }

    public function previewAdmin(int $id): \Illuminate\Http\Response|StreamedResponse
    {
        $contract = EmployeeContract::where('id', $id)
            ->where('tenant_id', session('tenant_id'))
            ->with('employee')
            ->firstOrFail();

        if (!$contract->pdf_path || !Storage::disk('local')->exists($contract->pdf_path)) {
            $pdf = $this->buildPdf($contract);
            return response($pdf->output(), 200, [
                'Content-Type'        => 'application/pdf',
                'Content-Disposition' => 'inline; filename="Arbeitsvertrag.pdf"',
            ]);
        }

        return Storage::disk('local')->response($contract->pdf_path, null, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline',
        ]);
    }

    public function download(int $id): StreamedResponse
    {
        $contract = EmployeeContract::where('id', $id)
            ->where('tenant_id', session('tenant_id'))
            ->firstOrFail();

        abort_unless($contract->pdf_path && Storage::disk('local')->exists($contract->pdf_path), 404);

        $filename = 'Arbeitsvertrag_' . $contract->employee->last_name . '_' . $contract->employee->first_name . '.pdf';
        return Storage::disk('local')->download($contract->pdf_path, $filename);
    }

    // Öffentlich für den Sign-Link — inline anzeigen (kein Tenant-Check nötig, Token ist Schutz)
    public function downloadPublic(string $token): \Symfony\Component\HttpFoundation\BinaryFileResponse|StreamedResponse
    {
        $contract = EmployeeContract::where('employee_sign_token', $token)
            ->with('employee')
            ->firstOrFail();

        if (!$contract->pdf_path || !Storage::disk('local')->exists($contract->pdf_path)) {
            $pdf = $this->buildPdf($contract);
            return response($pdf->output(), 200, [
                'Content-Type'        => 'application/pdf',
                'Content-Disposition' => 'inline; filename="Arbeitsvertrag.pdf"',
            ]);
        }

        $filename = 'Arbeitsvertrag_' . $contract->employee->last_name . '_' . $contract->employee->first_name . '.pdf';
        return Storage::disk('local')->response($contract->pdf_path, $filename, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
        ]);
    }

    private function regeneratePdf(EmployeeContract $contract): void
    {
        $pdf = $this->buildPdf($contract);
        Storage::disk('local')->put($contract->pdf_path, $pdf->output());
    }

    public function downloadEmployee(int $id): \Illuminate\Http\Response|StreamedResponse
    {
        $employee = \App\Models\Employee::where('user_id', auth()->id())->firstOrFail();

        $contract = EmployeeContract::where('id', $id)
            ->where('employee_id', $employee->id)
            ->with('employee')
            ->firstOrFail();

        if (!$contract->pdf_path || !Storage::disk('local')->exists($contract->pdf_path)) {
            $pdf = $this->buildPdf($contract);
            return response($pdf->output(), 200, [
                'Content-Type'        => 'application/pdf',
                'Content-Disposition' => 'inline; filename="Arbeitsvertrag.pdf"',
            ]);
        }

        return Storage::disk('local')->response($contract->pdf_path, null, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline',
        ]);
    }

    private function buildPdf(EmployeeContract $contract): \Barryvdh\DomPDF\PDF
    {
        $employee = $contract->employee;
        $template = DocumentTemplate::forTenant($contract->tenant_id, 'arbeitsvertrag', $contract->contract_type);
        $bodyHtml = $template->render(PlaceholderRegistry::fromContract($contract));

        return Pdf::loadView('pdf.arbeitsvertrag', compact('contract', 'employee', 'bodyHtml'))
                  ->setPaper('a4', 'portrait');
    }
}
