<?php

namespace App\Filament\App\Pages;

use App\Models\Employee;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Hash;

/**
 * Persönliches Profil für eingeloggte Mitarbeiter im App-Panel.
 * Nur sichtbar für User vom Typ 'employee'.
 */
class MeinProfil extends Page implements HasForms
{
    use InteractsWithForms;

    protected string $view = 'filament.app.pages.mein-profil';

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-user-circle';

    protected static ?string $navigationLabel = 'Mein Profil';

    protected static ?string $title = 'Mein Profil';

    protected static ?string $slug = 'mein-profil';

    protected static ?int $navigationSort = 1;

    // ─── Sichtbarkeit ───────────────────────────────────────────────────────

    public static function canAccess(): bool
    {
        return auth()->user()?->isEmployee() ?? false;
    }

    // ─── State ──────────────────────────────────────────────────────────────

    public ?array $profileData = [];
    public ?array $passwordData = [];

    public function mount(): void
    {
        $employee = $this->getEmployee();

        if (! $employee) {
            Notification::make()
                ->title('Kein Mitarbeiter-Profil gefunden.')
                ->warning()
                ->send();
            return;
        }

        $this->profileForm->fill([
            'first_name'   => $employee->first_name,
            'last_name'    => $employee->last_name,
            'email'        => $employee->email,
            'phone'        => $employee->phone,
            'phone_mobile' => $employee->phone_mobile,
            'birth_date'   => $employee->birth_date,
            'address'      => $employee->address,
            'house_number' => $employee->house_number,
            'zip'          => $employee->zip,
            'city'         => $employee->city,
        ]);
    }

    // ─── Formulare ──────────────────────────────────────────────────────────

    protected function getForms(): array
    {
        return ['profileForm', 'passwordForm'];
    }

    public function profileForm(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Persönliche Daten')
                    ->columns(2)
                    ->schema([
                        TextInput::make('first_name')
                            ->label('Vorname')
                            ->required()
                            ->maxLength(100),

                        TextInput::make('last_name')
                            ->label('Nachname')
                            ->required()
                            ->maxLength(100),

                        TextInput::make('email')
                            ->label('E-Mail')
                            ->email()
                            ->required()
                            ->maxLength(255),

                        DatePicker::make('birth_date')
                            ->label('Geburtsdatum')
                            ->displayFormat('d.m.Y'),

                        TextInput::make('phone')
                            ->label('Telefon')
                            ->tel()
                            ->maxLength(50),

                        TextInput::make('phone_mobile')
                            ->label('Mobil')
                            ->tel()
                            ->maxLength(50),
                    ]),

                Section::make('Adresse')
                    ->columns(2)
                    ->schema([
                        TextInput::make('address')
                            ->label('Straße')
                            ->maxLength(200),

                        TextInput::make('house_number')
                            ->label('Hausnummer')
                            ->maxLength(20),

                        TextInput::make('zip')
                            ->label('PLZ')
                            ->maxLength(20),

                        TextInput::make('city')
                            ->label('Ort')
                            ->maxLength(100),
                    ]),
            ])
            ->statePath('profileData');
    }

    public function passwordForm(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Passwort ändern')
                    ->schema([
                        TextInput::make('current_password')
                            ->label('Aktuelles Passwort')
                            ->password()
                            ->revealable()
                            ->required(),

                        TextInput::make('new_password')
                            ->label('Neues Passwort')
                            ->password()
                            ->revealable()
                            ->required()
                            ->minLength(8)
                            ->same('new_password_confirmation'),

                        TextInput::make('new_password_confirmation')
                            ->label('Passwort bestätigen')
                            ->password()
                            ->revealable()
                            ->required(),
                    ]),
            ])
            ->statePath('passwordData');
    }

    // ─── Actions ────────────────────────────────────────────────────────────

    public function saveProfile(): void
    {
        $data = $this->profileForm->getState();
        $employee = $this->getEmployee();

        if (! $employee) {
            return;
        }

        $employee->update([
            'first_name'   => $data['first_name'],
            'last_name'    => $data['last_name'],
            'email'        => $data['email'],
            'phone'        => $data['phone'] ?? null,
            'phone_mobile' => $data['phone_mobile'] ?? null,
            'birth_date'   => $data['birth_date'] ?? null,
            'address'      => $data['address'] ?? null,
            'house_number' => $data['house_number'] ?? null,
            'zip'          => $data['zip'] ?? null,
            'city'         => $data['city'] ?? null,
        ]);

        Notification::make()
            ->title('Profil gespeichert.')
            ->success()
            ->send();
    }

    public function changePassword(): void
    {
        $data = $this->passwordForm->getState();
        $user = auth()->user();

        if (! Hash::check($data['current_password'], $user->password)) {
            Notification::make()
                ->title('Das aktuelle Passwort ist falsch.')
                ->danger()
                ->send();
            return;
        }

        $user->update([
            'password'             => Hash::make($data['new_password']),
            'must_change_password' => false,
        ]);

        $this->passwordData = [];

        Notification::make()
            ->title('Passwort erfolgreich geändert.')
            ->success()
            ->send();
    }

    // ─── Hilfsmethoden ──────────────────────────────────────────────────────

    public function getEmployee(): ?Employee
    {
        return Employee::where('user_id', auth()->id())->first();
    }
}
