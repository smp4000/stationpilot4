<?php

namespace App\Filament\App\Pages;

use App\Models\Employee;
use App\Models\EmployeeContract;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
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

    public static function canAccess(): bool
    {
        return auth()->user()?->isEmployee() ?? false;
    }

    // Livewire-State-Container für das Form
    public array $data = [];

    // ─── Mount ──────────────────────────────────────────────────────────────

    public function mount(): void
    {
        $employee = $this->getEmployee();
        if (! $employee) {
            return;
        }

        $this->form->fill([
            // Tab 1 – Name
            'first_name'   => $employee->first_name,
            'last_name'    => $employee->last_name,

            // Tab 2 – Kontakt & Adresse
            'email'        => $employee->email,
            'phone'        => $employee->phone,
            'phone_mobile' => $employee->phone_mobile,
            'address'      => $employee->address,
            'house_number' => $employee->house_number,
            'zip'          => $employee->zip,
            'city'         => $employee->city,
        ]);
    }

    // ─── Schema ─────────────────────────────────────────────────────────────

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([

            Tabs::make('Profil')
                ->tabs([

                    // ── Tab 1: Mein Name ─────────────────────────────────
                    Tab::make('Mein Name')
                        ->icon('heroicon-o-user')
                        ->schema([
                            Section::make()
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
                                ]),

                            Actions::make([
                                Action::make('saveProfile')
                                    ->label('Speichern')
                                    ->icon('heroicon-o-check')
                                    ->color('primary')
                                    ->action('saveProfile'),
                            ]),
                        ]),

                    // ── Tab 2: Kontakt & Adresse ──────────────────────────
                    Tab::make('Kontakt & Adresse')
                        ->icon('heroicon-o-map-pin')
                        ->schema([
                            Section::make('Kontakt')
                                ->columns(2)
                                ->schema([
                                    TextInput::make('email')
                                        ->label('E-Mail')
                                        ->email()
                                        ->required()
                                        ->maxLength(255),

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
                                Action::make('saveProfile2')
                                    ->label('Speichern')
                                    ->icon('heroicon-o-check')
                                    ->color('primary')
                                    ->action('saveProfile'),
                            ]),
                        ]),

                    // ── Tab 4: Meine Verträge ─────────────────────────────
                    Tab::make('Meine Verträge')
                        ->icon('heroicon-o-document-text')
                        ->schema([
                            \Filament\Schemas\Components\View::make('filament.app.pages.mein-profil-vertraege')
                                ->viewData(['contracts' => $this->getContracts()]),
                        ]),

                    // ── Tab 3: Passwort ───────────────────────────────────
                    Tab::make('Passwort ändern')
                        ->icon('heroicon-o-lock-closed')
                        ->schema([
                            Section::make()
                                ->schema([
                                    \Filament\Schemas\Components\View::make('filament.app.components.passwort-hinweis')
                                        ->viewData(['mustChange' => auth()->user()?->must_change_password]),

                                    TextInput::make('current_password')
                                        ->label('Aktuelles Passwort')
                                        ->helperText('Geben Sie das per E-Mail erhaltene temporäre Passwort ein.')
                                        ->password()
                                        ->revealable()
                                        ->required()
                                        ->dehydrated(false),

                                    TextInput::make('new_password')
                                        ->label('Neues Passwort')
                                        ->password()
                                        ->revealable()
                                        ->required()
                                        ->minLength(8)
                                        ->same('new_password_confirmation')
                                        ->dehydrated(false),

                                    TextInput::make('new_password_confirmation')
                                        ->label('Passwort bestätigen')
                                        ->password()
                                        ->revealable()
                                        ->required()
                                        ->dehydrated(false),
                                ]),

                            Actions::make([
                                Action::make('changePassword')
                                    ->label('Passwort ändern')
                                    ->icon('heroicon-o-lock-closed')
                                    ->color('warning')
                                    ->action('changePassword'),
                            ]),
                        ]),

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
            'address'      => $data['address']       ?? null,
            'house_number' => $data['house_number']  ?? null,
            'zip'          => $data['zip']           ?? null,
            'city'         => $data['city']          ?? null,
        ]);

        Notification::make()->title('Profil gespeichert.')->success()->send();
    }

    public function changePassword(): void
    {
        $user    = auth()->user();
        $current = $this->data['current_password'] ?? '';
        $new     = $this->data['new_password']     ?? '';
        $confirm = $this->data['new_password_confirmation'] ?? '';

        if (! Hash::check($current, $user->password)) {
            Notification::make()->title('Das aktuelle Passwort ist falsch.')->danger()->send();
            return;
        }

        if ($new !== $confirm || strlen($new) < 8) {
            Notification::make()->title('Passwörter stimmen nicht überein oder sind zu kurz (mind. 8 Zeichen).')->danger()->send();
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

    public function getContracts(): \Illuminate\Support\Collection
    {
        $employee = $this->getEmployee();
        if (!$employee) {
            return collect();
        }

        return EmployeeContract::where('employee_id', $employee->id)
            ->whereNull('deleted_at')
            ->orderByDesc('created_at')
            ->get();
    }
}
