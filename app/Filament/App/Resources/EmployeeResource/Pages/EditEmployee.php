<?php

namespace App\Filament\App\Resources\EmployeeResource\Pages;

use App\Filament\App\Resources\EmployeeResource;
use App\Mail\EmployeeAppAccessMail;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class EditEmployee extends EditRecord
{
    protected static string $resource = EmployeeResource::class;

    protected function getHeaderActions(): array
    {
        return [
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
