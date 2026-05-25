<?php

namespace App\Filament\App\Resources\EmployeeResource\Pages;

use App\Filament\App\Resources\EmployeeResource;
use App\Mail\EmployeeInvitationMail;
use App\Models\Employee;
use App\Models\Station;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class ListEmployees extends ListRecords
{
    protected static string $resource = EmployeeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // ── Schnell-Einladung ──────────────────────────────────────────
            Action::make('schnelleinladung')
                ->label('Mitarbeiter einladen')
                ->icon('heroicon-o-envelope')
                ->color('success')
                ->modalHeading('Mitarbeiter einladen')
                ->modalDescription('Geben Sie Name und E-Mail-Adresse ein. Der Mitarbeiter erhält einen Link, um seine Daten selbst einzutragen.')
                ->modalSubmitActionLabel('Einladung senden')
                ->modalWidth('lg')
                ->form([
                    TextInput::make('first_name')
                        ->label('Vorname')
                        ->required()
                        ->maxLength(100),
                    TextInput::make('last_name')
                        ->label('Nachname')
                        ->required()
                        ->maxLength(100),
                    TextInput::make('email')
                        ->label('E-Mail-Adresse')
                        ->email()
                        ->required()
                        ->maxLength(191),
                    Select::make('station_id')
                        ->label('Station (optional)')
                        ->options(fn (): array => Station::where('tenant_id', session('tenant_id'))
                            ->orderBy('name')
                            ->pluck('name', 'id')
                            ->toArray())
                        ->searchable()
                        ->placeholder('Keine Station zugewiesen'),
                ])
                ->action(function (array $data): void {
                    $token = Str::random(64);

                    $employee = Employee::create([
                        'tenant_id'              => session('tenant_id'),
                        'first_name'             => $data['first_name'],
                        'last_name'              => $data['last_name'],
                        'email'                  => $data['email'],
                        'station_id'             => $data['station_id'] ?? null,
                        'status'                 => 'eingeladen',
                        'invitation_token'       => $token,
                        'invited_at'             => now(),
                        'invitation_expires_at'  => now()->addDays(7),
                        'employment_start'       => now()->toDateString(),
                    ]);

                    try {
                        Mail::to($employee->email)->send(new EmployeeInvitationMail($employee));
                        Notification::make()
                            ->title('Einladung versendet')
                            ->body('Die Einladung wurde an ' . $employee->email . ' geschickt.')
                            ->success()
                            ->send();
                    } catch (\Throwable $e) {
                        // Mail fehlgeschlagen – Link trotzdem anzeigen
                        $link = route('employee.invitation.show', $token);
                        Notification::make()
                            ->title('Mitarbeiter angelegt – E-Mail fehlgeschlagen')
                            ->body('Einladungslink: ' . $link)
                            ->warning()
                            ->persistent()
                            ->send();
                    }
                }),

            // ── Vollständig anlegen ────────────────────────────────────────
            CreateAction::make()
                ->label('Manuell anlegen'),
        ];
    }
}
