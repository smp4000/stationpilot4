<?php

namespace App\Filament\App\Resources\EmployeeContractResource\Pages;

use App\Filament\App\Resources\EmployeeContractResource;
use App\Filament\App\Widgets\ContractPdfPreviewWidget;
use App\Mail\EmployeeContractMail;
use App\Models\EmployeeContract;
use Filament\Actions\Action;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ViewEmployeeContract extends ViewRecord
{
    protected static string $resource = EmployeeContractResource::class;

    protected function getFooterWidgets(): array
    {
        return [ContractPdfPreviewWidget::class];
    }

    protected function getHeaderActions(): array
    {
        return [
            // ── Arbeitgeber unterschreiben ────────────────────────────────────
            Action::make('employer_sign')
                ->label('Als Arbeitgeber unterschreiben')
                ->icon('heroicon-o-pencil-square')
                ->color('success')
                ->visible(fn (): bool => $this->getRecord()->employer_signed_at === null)
                ->modalHeading('Vertrag als Arbeitgeber unterschreiben')
                ->form([
                    Placeholder::make('sign_info')
                        ->label('')
                        ->content(new HtmlString('
                            <div style="background:#f0fdf4;border-left:4px solid #22c55e;border-radius:0 8px 8px 0;padding:12px 16px;margin-bottom:4px;">
                                <p style="margin:0;font-size:13px;color:#15803d;line-height:1.6;">
                                    Mit Ihrer Bestätigung unterschreiben Sie den Arbeitsvertrag digitally als Arbeitgeber.
                                    Ihre Unterschrift ist rechtsverbindlich.
                                </p>
                            </div>
                        '))
                        ->columnSpanFull(),
                    TextInput::make('employer_signature')
                        ->label('Ihr vollständiger Name (Unterschrift)')
                        ->default(fn () => trim((auth()->user()->first_name ?? '') . ' ' . (auth()->user()->last_name ?? '')))
                        ->required()
                        ->helperText('Geben Sie Ihren Namen ein — dieser gilt als rechtsverbindliche digitale Unterschrift.'),
                ])
                ->modalSubmitActionLabel('Verbindlich unterschreiben')
                ->action(function (array $data): void {
                    $contract = $this->getRecord();
                    $user     = auth()->user();

                    $contract->update([
                        'employer_signature'  => $data['employer_signature'] . ' — ' . now()->format('d.m.Y H:i') . ' Uhr',
                        'employer_signed_at'  => now(),
                        'employer_signed_by'  => $user->id,
                        'status'              => $contract->employee_signed_at ? 'completed' : $contract->status,
                    ]);

                    Notification::make()
                        ->title('Vertrag unterschrieben')
                        ->body('Der Vertrag wurde erfolgreich als Arbeitgeber unterzeichnet.')
                        ->success()
                        ->send();
                }),

            // ── PDF herunterladen ─────────────────────────────────────────────
            Action::make('download')
                ->label('PDF herunterladen')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->action(function (): StreamedResponse {
                    $contract = $this->getRecord();
                    $filename = 'Arbeitsvertrag_' . $contract->employee->last_name . '_' . $contract->employee->first_name . '.pdf';
                    return Storage::disk('local')->download($contract->pdf_path, $filename);
                })
                ->visible(fn (): bool => (bool) $this->getRecord()->pdf_path),

            // ── Per E-Mail versenden (nur für generierte, nicht hochgeladene) ─
            Action::make('send_to_employee')
                ->label('Per E-Mail versenden & unterschreiben lassen')
                ->icon('heroicon-o-envelope')
                ->color('primary')
                ->requiresConfirmation()
                ->modalHeading('Vertrag versenden?')
                ->modalDescription(fn (): string => 'Der Mitarbeiter (' . ($this->getRecord()->employee->email ?? 'keine E-Mail') . ') erhält einen Link zum digitalen Unterschreiben.')
                ->modalSubmitActionLabel('Versenden')
                ->visible(fn (): bool =>
                    !$this->getRecord()->is_uploaded
                    && $this->getRecord()->status === 'draft'
                    && !empty($this->getRecord()->employee->email)
                )
                ->action(function (): void {
                    $contract = $this->getRecord();
                    $token    = $contract->generateSignToken();

                    $contract->update([
                        'status'              => 'sent',
                        'sent_to_employee_at' => now(),
                    ]);

                    try {
                        Mail::to($contract->employee->email)
                            ->send(new EmployeeContractMail($contract, $token));

                        Notification::make()
                            ->title('Vertrag versendet')
                            ->body('Der Mitarbeiter erhält den Link per E-Mail.')
                            ->success()
                            ->send();
                    } catch (\Throwable $e) {
                        Notification::make()
                            ->title('E-Mail-Fehler')
                            ->body('Vertrag gespeichert, E-Mail konnte nicht gesendet werden: ' . $e->getMessage())
                            ->warning()
                            ->send();
                    }
                }),

            // ── Unterschriften-Link kopieren ──────────────────────────────────
            Action::make('copy_sign_link')
                ->label('Unterschriften-Link kopieren')
                ->icon('heroicon-o-link')
                ->color('gray')
                ->visible(fn (): bool =>
                    !$this->getRecord()->is_uploaded
                    && in_array($this->getRecord()->status, ['sent', 'employee_signed'])
                    && $this->getRecord()->employee_sign_token !== null
                )
                ->extraAttributes(fn (): array => [
                    'x-on:click' => 'navigator.clipboard.writeText("' . route('contract.sign', $this->getRecord()->employee_sign_token ?? '') . '"); $dispatch("filament-notification", {notification: {title: "Link kopiert", color: "success"}})',
                ]),

            // ── PDF neu generieren (nur für generierte Verträge) ──────────────
            Action::make('regenerate_pdf')
                ->label('PDF neu generieren')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->visible(fn (): bool => !$this->getRecord()->is_uploaded)
                ->requiresConfirmation()
                ->action(function (): void {
                    $contract = $this->getRecord();
                    app(\App\Filament\App\Resources\EmployeeContractResource\Pages\CreateEmployeeContract::class)
                        ->generatePdf($contract, $contract->employee);

                    Notification::make()->title('PDF neu erstellt')->success()->send();
                }),
        ];
    }
}
