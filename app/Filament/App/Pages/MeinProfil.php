<?php

namespace App\Filament\App\Pages;

use App\Models\Employee;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;

/**
 * Persönliches Profil für eingeloggte Mitarbeiter im App-Panel.
 * Nur sichtbar für User vom Typ 'employee'.
 */
class MeinProfil extends Page
{
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

    // ─── Mount ──────────────────────────────────────────────────────────────

    public function mount(): void
    {
        $employee = $this->getEmployee();
        if (! $employee) {
            return;
        }

        // Genau wie Filament's eigener EditProfile: $this->form->fill($data)
        $this->form->fill([
            'first_name'   => $employee->first_name,
            'last_name'    => $employee->last_name,
            'email'        => $employee->email,
            'phone'        => $employee->phone,
            'phone_mobile' => $employee->phone_mobile,
            'birth_date'   => $employee->birth_date?->format('Y-m-d'),
            'address'      => $employee->address,
            'house_number' => $employee->house_number,
            'zip'          => $employee->zip,
            'city'         => $employee->city,
        ]);
    }

    // ─── Haupt-Schema ────────────────────────────────────────────────────────

    public function form(Schema $schema): Schema
    {
        return $schema->components([

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

            Actions::make([
                Action::make('saveProfile')
                    ->label('Profil speichern')
                    ->icon('heroicon-o-check')
                    ->color('primary')
                    ->action('saveProfile'),
            ]),

            Section::make('Passwort ändern')
                ->schema([
                    TextInput::make('current_password')
                        ->label('Aktuelles Passwort')
                        ->password()
                        ->revealable()
                        ->dehydrated(false),

                    TextInput::make('new_password')
                        ->label('Neues Passwort')
                        ->password()
                        ->revealable()
                        ->minLength(8)
                        ->same('new_password_confirmation')
                        ->dehydrated(false),

                    TextInput::make('new_password_confirmation')
                        ->label('Passwort bestätigen')
                        ->password()
                        ->revealable()
                        ->dehydrated(false),
                ]),

            Actions::make([
                Action::make('changePassword')
                    ->label('Passwort ändern')
                    ->icon('heroicon-o-lock-closed')
                    ->color('warning')
                    ->action('changePassword'),
            ]),
        ]);
    }

    // ─── Action-Handler ─────────────────────────────────────────────────────

    public function saveProfile(): void
    {
        $data     = $this->form->getState();
        $employee = $this->getEmployee();

        if (! $employee) {
            return;
        }

        $employee->update([
            'first_name'   => $data['first_name']   ?? null,
            'last_name'    => $data['last_name']     ?? null,
            'email'        => $data['email']         ?? null,
            'phone'        => $data['phone']         ?? null,
            'phone_mobile' => $data['phone_mobile']  ?? null,
            'birth_date'   => $data['birth_date']    ?? null,
            'address'      => $data['address']       ?? null,
            'house_number' => $data['house_number']  ?? null,
            'zip'          => $data['zip']           ?? null,
            'city'         => $data['city']          ?? null,
        ]);

        Notification::make()->title('Profil gespeichert.')->success()->send();
    }

    public function changePassword(): void
    {
        $data = $this->form->getRawState();
        $user = auth()->user();

        $current = $data['current_password'] ?? '';
        $new     = $data['new_password']     ?? '';
        $confirm = $data['new_password_confirmation'] ?? '';

        if (! Hash::check($current, $user->password)) {
            Notification::make()->title('Das aktuelle Passwort ist falsch.')->danger()->send();
            return;
        }

        if ($new !== $confirm || strlen($new) < 8) {
            Notification::make()->title('Passwörter stimmen nicht überein oder sind zu kurz.')->danger()->send();
            return;
        }

        $user->update([
            'password'             => $new,
            'must_change_password' => false,
        ]);

        Notification::make()->title('Passwort erfolgreich geändert.')->success()->send();
    }

    // ─── Hilfsmethoden ──────────────────────────────────────────────────────

    public function getEmployee(): ?Employee
    {
        return Employee::where('user_id', auth()->id())->first();
    }
}
