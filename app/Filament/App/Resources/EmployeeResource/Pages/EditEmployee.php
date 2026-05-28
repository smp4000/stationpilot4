<?php

namespace App\Filament\App\Resources\EmployeeResource\Pages;

use App\Filament\App\Resources\EmployeeResource;
use App\Mail\EmployeeAppAccessMail;
use App\Mail\EmployeeOnboardingMail;
use App\Models\DocumentTemplate;
use App\Models\EmployeeContract;
use App\Models\GeneratedDocument;
use App\Services\PlaceholderRegistry;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class EditEmployee extends EditRecord
{
    protected static string $resource = EmployeeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // ── Onboarding: alle Dokumente senden ────────────────────────
            Action::make('onboarding_senden')
                ->label('Onboarding / Dokumente senden')
                ->icon('heroicon-o-paper-airplane')
                ->color('primary')
                ->visible(fn (): bool => filled($this->record?->email))
                ->requiresConfirmation()
                ->modalHeading('Onboarding-E-Mail senden?')
                ->modalDescription(fn (): string =>
                    'Alle Dokumente (Verträge + Vorlagen mit Unterschrift-Pflicht) werden an ' .
                    ($this->record->email ?? '–') . ' gesendet.'
                )
                ->modalSubmitActionLabel('Senden')
                ->action(function (): void {
                    $employee = $this->record;
                    $tenantId = session('tenant_id');

                    // 1. Mitarbeiter-Templates mit Unterschrift-Pflicht → PDFs generieren
                    $templates = DocumentTemplate::where('tenant_id', $tenantId)
                        ->where('document_type', 'mitarbeiter')
                        ->where('requires_signature', true)
                        ->where('is_active', true)
                        ->get();

                    $generatedDocs = [];

                    foreach ($templates as $template) {
                        $bodyHtml = $template->render(PlaceholderRegistry::fromEmployee($employee));

                        $pdf  = Pdf::loadView('pdf.dokument', compact('bodyHtml', 'template', 'employee'))
                                   ->setPaper('a4', 'portrait');

                        $doc  = GeneratedDocument::create([
                            'tenant_id'     => $tenantId,
                            'template_id'   => $template->id,
                            'document_type' => 'mitarbeiter',
                            'related_type'  => \App\Models\Employee::class,
                            'related_id'    => $employee->id,
                            'generated_by'  => auth()->id(),
                            'sign_token'    => Str::random(64),
                        ]);

                        $path = 'generated-docs/' . $doc->id . '_' . Str::slug($template->name) . '.pdf';
                        Storage::disk('local')->put($path, $pdf->output());
                        $doc->update(['pdf_path' => $path]);

                        $generatedDocs[] = $doc->fresh(['template']);
                    }

                    // 2. Unsigned EmployeeContracts → sign token + status 'sent'
                    $contracts = EmployeeContract::where('employee_id', $employee->id)
                        ->whereNull('employee_signed_at')
                        ->whereIn('status', ['draft', 'sent'])
                        ->get();

                    foreach ($contracts as $contract) {
                        if (!$contract->employee_sign_token) {
                            $contract->generateSignToken();
                        }
                        if ($contract->status === 'draft') {
                            $contract->update(['status' => 'sent', 'sent_to_employee_at' => now()]);
                        }
                    }

                    // 3. E-Mail senden
                    if (empty($generatedDocs) && $contracts->isEmpty()) {
                        Notification::make()
                            ->title('Keine Dokumente vorhanden.')
                            ->body('Es wurden keine Verträge oder aktiven Dokument-Vorlagen mit Unterschrift-Pflicht gefunden.')
                            ->warning()->send();
                        return;
                    }

                    try {
                        Mail::to($employee->email)->send(
                            new EmployeeOnboardingMail($employee, $generatedDocs, $contracts->all())
                        );
                        Notification::make()
                            ->title('Onboarding-E-Mail gesendet')
                            ->body(count($generatedDocs) + $contracts->count() . ' Dokument(e) an ' . $employee->email . ' gesendet.')
                            ->success()->send();
                    } catch (\Throwable $e) {
                        Notification::make()
                            ->title('E-Mail konnte nicht gesendet werden')
                            ->body($e->getMessage())
                            ->danger()->persistent()->send();
                    }
                }),

            // ── App-Passwort zurücksetzen ─────────────────────────────────
            Action::make('app_passwort_reset')
                ->label('App-Passwort zurücksetzen')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->visible(fn (): bool => !is_null($this->record?->user_id))
                ->requiresConfirmation()
                ->modalHeading('App-Passwort zurücksetzen?')
                ->modalDescription(fn (): string =>
                    'Ein neues temporäres Passwort wird generiert und an ' .
                    $this->record->email . ' gesendet.'
                )
                ->modalSubmitActionLabel('Zurücksetzen & E-Mail senden')
                ->action(function (): void {
                    $record = $this->record;
                    $user   = $record->user;

                    if (! $user) {
                        Notification::make()
                            ->title('Kein App-Account verknüpft.')
                            ->danger()->send();
                        return;
                    }

                    $plain = Str::random(12);
                    $user->update([
                        'password'             => $plain,
                        'must_change_password' => true,
                        'is_active'            => true,
                    ]);

                    try {
                        Mail::to($user->email)->send(new EmployeeAppAccessMail($user, $plain));
                        Notification::make()
                            ->title('Passwort zurückgesetzt')
                            ->body('Neues temporäres Passwort wurde an ' . $user->email . ' gesendet.')
                            ->success()->send();
                    } catch (\Throwable) {
                        Notification::make()
                            ->title('Passwort zurückgesetzt – E-Mail fehlgeschlagen')
                            ->body('Neues temporäres Passwort: ' . $plain)
                            ->warning()->persistent()->send();
                    }
                }),

            RestoreAction::make()
                ->visible(fn (): bool => (bool) $this->record?->deleted_at),

            DeleteAction::make()
                ->hidden(fn (): bool => (bool) $this->record?->deleted_at),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (empty($data['mde_pin'])) {
            unset($data['mde_pin']);
        }

        // user_id darf NUR über die app_zugang-Actions geändert werden
        unset($data['user_id']);

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
