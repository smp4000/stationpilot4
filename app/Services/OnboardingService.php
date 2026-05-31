<?php

namespace App\Services;

use App\Mail\EmployeeOnboardingMail;
use App\Models\DocumentTemplate;
use App\Models\Employee;
use App\Models\EmployeeContract;
use App\Models\GeneratedDocument;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class OnboardingService
{
    /**
     * Generiert Onboarding-Dokumente als PDFs, legt GeneratedDocument-Records an,
     * und versendet eine gebündelte Onboarding-E-Mail an den Mitarbeiter.
     *
     * @param  Employee               $employee   Mitarbeiter-Datensatz
     * @param  array                  $subTypes   z.B. ['datenschutz', 'betriebsordnung', 'nda']
     * @param  EmployeeContract|null  $contract   Optionaler Vertrag (wird auf 'sent' gesetzt)
     * @return array{docs: GeneratedDocument[], contracts: EmployeeContract[], errors: string[]}
     */
    public static function sendPackage(
        Employee $employee,
        array $subTypes,
        ?EmployeeContract $contract = null
    ): array {
        $tenantId = $employee->tenant_id;
        $generatedDocs = [];
        $errors = [];

        // ── 1. Dokumente generieren ───────────────────────────────────────────
        foreach ($subTypes as $subType) {
            try {
                $template = DocumentTemplate::forTenant($tenantId, 'mitarbeiter', $subType);
            } catch (\Throwable) {
                $errors[] = 'Keine aktive Vorlage für Typ "' . $subType . '" gefunden.';
                continue;
            }

            try {
                $values   = PlaceholderRegistry::fromEmployee($employee);
                $bodyHtml = $template->render($values);

                $pdf  = Pdf::loadView('pdf.dokument', [
                    'bodyHtml' => $bodyHtml,
                    'template' => $template,
                    'employee' => $employee,
                ])->setPaper('a4', 'portrait');

                $token = Str::random(64);
                $path  = 'documents/' . $tenantId . '/'
                    . Str::slug($employee->last_name . '_' . $employee->first_name)
                    . '_' . $subType
                    . '_' . now()->format('Ymd_His')
                    . '.pdf';

                Storage::disk('local')->put($path, $pdf->output());

                $doc = GeneratedDocument::create([
                    'tenant_id'     => $tenantId,
                    'template_id'   => $template->id,
                    'document_type' => 'mitarbeiter',
                    'related_type'  => Employee::class,
                    'related_id'    => $employee->id,
                    'pdf_path'      => $path,
                    'generated_by'  => auth()->id(),
                    'generated_at'  => now(),
                    'sign_token'    => $token,
                ]);

                $generatedDocs[] = $doc->load('template');
            } catch (\Throwable $e) {
                $errors[] = 'Fehler bei "' . $subType . '": ' . $e->getMessage();
            }
        }

        // ── 2. Vertrag vorbereiten ────────────────────────────────────────────
        $contracts = [];
        if ($contract) {
            if ($contract->status === 'draft') {
                // Noch nicht versendet → jetzt versenden
                $contract->generateSignToken();
                $contract->update([
                    'status'              => 'sent',
                    'sent_to_employee_at' => now(),
                ]);
            }
            // Frisch laden damit sign_token gesetzt ist
            $contracts[] = $contract->fresh(['employee']);
        }

        // ── 3. E-Mail versenden ───────────────────────────────────────────────
        if ($employee->email && (!empty($generatedDocs) || !empty($contracts))) {
            try {
                Mail::to($employee->email)->send(
                    new EmployeeOnboardingMail($employee, $generatedDocs, $contracts)
                );
            } catch (\Throwable $e) {
                $errors[] = 'E-Mail konnte nicht gesendet werden: ' . $e->getMessage();
            }
        }

        return [
            'docs'      => $generatedDocs,
            'contracts' => $contracts,
            'errors'    => $errors,
        ];
    }

    /**
     * Gibt alle verfügbaren Onboarding-Dokumenttypen (subTypes) zurück,
     * die für einen Mandanten eine aktive Vorlage haben.
     */
    public static function availableDocumentTypes(int|string $tenantId): array
    {
        $allSubTypes = PlaceholderRegistry::subTypes('mitarbeiter');
        $available   = [];

        foreach ($allSubTypes as $key => $label) {
            $exists = DocumentTemplate::where('tenant_id', $tenantId)
                ->where('document_type', 'mitarbeiter')
                ->where('sub_type', $key)
                ->where('is_active', true)
                ->exists();

            if ($exists) {
                $available[$key] = $label;
            }
        }

        return $available;
    }
}
