<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\EmployeeResource\Pages;
use App\Mail\EmployeeAppAccessMail;
use App\Mail\EmployeeInvitationMail;
use App\Mail\EmployeePasswordMail;
use App\Models\Employee;
use App\Models\EmployeeAccessLog;
use App\Models\Station;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Filament\Actions\Action;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class EmployeeResource extends Resource
{
    protected static ?string $model = Employee::class;

    /** Nur Partner und Steuerberater dürfen alle Mitarbeiter verwalten. */
    public static function canAccess(): bool
    {
        $user = auth()->user();
        return $user && ($user->isPartner() || $user->isTaxAdvisor());
    }

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-users';

    protected static \UnitEnum|string|null $navigationGroup = 'Personal';

    protected static ?int $navigationSort = 1;

    protected static ?string $label = 'Mitarbeiter';

    protected static ?string $pluralLabel = 'Mitarbeiter';

    // ─────────────────────────────────────────────
    // Form
    // ─────────────────────────────────────────────

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make('Mitarbeiter')->tabs([

                // ── Tab 1: Stammdaten ──────────────────────────────────────
                Tab::make('Stammdaten')
                    ->icon('heroicon-o-identification')
                    ->schema([
                        Grid::make(3)->schema([
                            TextInput::make('first_name')
                                ->label('Vorname')
                                ->required()
                                ->maxLength(100),
                            TextInput::make('last_name')
                                ->label('Nachname')
                                ->required()
                                ->maxLength(100),
                            TextInput::make('birth_name')
                                ->label('Geburtsname')
                                ->maxLength(100),
                        ]),
                        Grid::make(3)->schema([
                            DatePicker::make('date_of_birth')
                                ->label('Geburtsdatum')
                                ->helperText('🔒 Verschlüsselt gespeichert'),
                            TextInput::make('place_of_birth')
                                ->label('Geburtsort')
                                ->helperText('🔒 Verschlüsselt gespeichert')
                                ->maxLength(100),
                            Select::make('country_of_birth')
                                ->label('Geburtsland')
                                ->helperText('🔒 Verschlüsselt gespeichert')
                                ->options(static::countryOptions())
                                ->default('Deutschland')
                                ->searchable(),
                        ]),
                        Grid::make(3)->schema([
                            Select::make('nationality')
                                ->label('Staatsangehörigkeit')
                                ->helperText('🔒 Verschlüsselt gespeichert')
                                ->options(static::nationalityOptions())
                                ->default('deutsch')
                                ->searchable(),
                            Select::make('gender')
                                ->label('Geschlecht')
                                ->options(Employee::genderOptions()),
                            Select::make('marital_status')
                                ->label('Familienstand')
                                ->options(Employee::maritalStatusOptions()),
                        ]),
                        Toggle::make('severely_disabled')
                            ->label('Schwerbehinderung')
                            ->live(),
                        TextInput::make('disability_degree')
                            ->label('Grad der Behinderung (%)')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->suffix('%')
                            ->visible(fn (Get $get): bool => (bool) $get('severely_disabled')),
                    ]),

                // ── Tab 2: Anschrift & Kontakt ─────────────────────────────
                Tab::make('Anschrift & Kontakt')
                    ->icon('heroicon-o-map-pin')
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('street')
                                ->label('Straße')
                                ->maxLength(150),
                            TextInput::make('house_number')
                                ->label('Hausnummer')
                                ->maxLength(20),
                        ]),
                        Grid::make(3)->schema([
                            TextInput::make('zip')
                                ->label('PLZ')
                                ->maxLength(10),
                            TextInput::make('city')
                                ->label('Ort')
                                ->maxLength(100),
                            Select::make('country')
                                ->label('Land')
                                ->options(static::countryOptions())
                                ->default('Deutschland')
                                ->searchable(),
                        ]),
                        Grid::make(3)->schema([
                            TextInput::make('phone_private')
                                ->label('Telefon privat')
                                ->tel()
                                ->maxLength(30),
                            TextInput::make('phone_mobile')
                                ->label('Mobil')
                                ->tel()
                                ->maxLength(30),
                            TextInput::make('email')
                                ->label('E-Mail')
                                ->email()
                                ->maxLength(191),
                        ]),
                    ]),

                // ── Tab 3: Beschäftigung ───────────────────────────────────
                Tab::make('Beschäftigung')
                    ->icon('heroicon-o-briefcase')
                    ->schema([
                        Grid::make(2)->schema([
                            Select::make('station_id')
                                ->label('Primärstation')
                                ->options(fn (): array => Station::where('tenant_id', session('tenant_id'))
                                    ->orderBy('name')
                                    ->pluck('name', 'id')
                                    ->toArray())
                                ->searchable(),
                            DatePicker::make('employment_start')
                                ->label('Eintrittsdatum')
                                ->required(),
                        ]),
                        Grid::make(2)->schema([
                            DatePicker::make('employment_end')
                                ->label('Austrittsdatum'),
                            Select::make('employment_type')
                                ->label('Beschäftigungsart')
                                ->options(Employee::employmentTypeOptions()),
                        ]),
                        Grid::make(2)->schema([
                            Select::make('employee_status')
                                ->label('Berufsstatus')
                                ->options(Employee::employeeStatusOptions()),
                            TextInput::make('job_title')
                                ->label('Berufsbezeichnung')
                                ->maxLength(100),
                        ]),
                        Grid::make(3)->schema([
                            TextInput::make('weekly_hours')
                                ->label('Wochenstunden')
                                ->numeric()
                                ->minValue(0)
                                ->maxValue(168)
                                ->step(0.5),
                            TextInput::make('vacation_days')
                                ->label('Urlaubstage')
                                ->integer()
                                ->minValue(0)
                                ->maxValue(365),
                            TextInput::make('cost_center')
                                ->label('Kostenstelle')
                                ->maxLength(50),
                        ]),
                        CheckboxList::make('stations')
                            ->label('Weitere Stationen')
                            ->relationship('stations', 'name')
                            ->options(fn (): array => \App\Models\Station::where('tenant_id', session('tenant_id'))
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->toArray())
                            ->helperText('Primärstation wird oben gesetzt. Hier weitere Einsatzorte wählen.'),
                    ]),

                // ── Tab 4: Steuer & Soziales ───────────────────────────────
                Tab::make('Steuer & Soziales')
                    ->icon('heroicon-o-calculator')
                    ->schema([
                        Section::make('Steuer')->schema([
                            Grid::make(2)->schema([
                                TextInput::make('tax_id')
                                    ->label('Steuer-ID')
                                    ->helperText('🔒 Verschlüsselt gespeichert')
                                    ->maxLength(11),
                                Select::make('tax_class')
                                    ->label('Steuerklasse')
                                    ->options([
                                        1 => 'Klasse I',
                                        2 => 'Klasse II',
                                        3 => 'Klasse III',
                                        4 => 'Klasse IV',
                                        5 => 'Klasse V',
                                        6 => 'Klasse VI',
                                    ])
                                    ->live(),
                            ]),
                            Grid::make(2)->schema([
                                TextInput::make('tax_child_allowance')
                                    ->label('Kinderfreibeträge')
                                    ->numeric()
                                    ->step(0.5)
                                    ->minValue(0),
                                Select::make('church_tax')
                                    ->label('Konfession')
                                    ->options(Employee::churchTaxOptions()),
                            ]),
                            TextInput::make('tax_factor')
                                ->label('Faktor (Steuerklasse IV)')
                                ->numeric()
                                ->step(0.001)
                                ->minValue(0)
                                ->maxValue(1)
                                ->visible(fn (Get $get): bool => (int) $get('tax_class') === 4),
                        ]),
                        Section::make('Sozialversicherung')->schema([
                            Grid::make(1)->schema([
                                TextInput::make('social_security_number')
                                    ->label('Sozialversicherungsnummer')
                                    ->helperText('🔒 Verschlüsselt gespeichert')
                                    ->maxLength(12),
                            ]),
                            Grid::make(2)->schema([
                                TextInput::make('health_insurance_name')
                                    ->label('Krankenkasse')
                                    ->helperText('🔒 Verschlüsselt gespeichert')
                                    ->maxLength(100),
                                Select::make('health_insurance_type')
                                    ->label('KV-Art')
                                    ->options(Employee::healthInsuranceTypeOptions()),
                            ]),
                            Grid::make(2)->schema([
                                Toggle::make('pension_insurance')
                                    ->label('RV-Pflichtversichert')
                                    ->default(true),
                                Toggle::make('unemployment_insurance')
                                    ->label('ALV-Pflichtversichert')
                                    ->default(true),
                            ]),
                        ]),
                    ]),

                // ── Tab 5: Vergütung & Bank ────────────────────────────────
                Tab::make('Vergütung & Bank')
                    ->icon('heroicon-o-banknotes')
                    ->schema([
                        Section::make('Vergütung')->schema([
                            Grid::make(3)->schema([
                                Select::make('wage_type')
                                    ->label('Lohnart')
                                    ->options(Employee::wageTypeOptions()),
                                TextInput::make('wage_amount')
                                    ->label('Betrag €')
                                    ->helperText('🔒 Verschlüsselt gespeichert')
                                    ->numeric()
                                    ->step(0.01)
                                    ->minValue(0)
                                    ->prefix('€'),
                                Select::make('payment_interval')
                                    ->label('Zahlungsweise')
                                    ->options([
                                        'monatlich'   => 'Monatlich',
                                        'woechentlich' => 'Wöchentlich',
                                    ]),
                            ]),
                        ]),
                        Section::make('Bankverbindung')->schema([
                            Grid::make(2)->schema([
                                TextInput::make('iban')
                                    ->label('IBAN')
                                    ->helperText('🔒 Verschlüsselt gespeichert')
                                    ->maxLength(34),
                                TextInput::make('bic')
                                    ->label('BIC')
                                    ->helperText('🔒 Verschlüsselt gespeichert')
                                    ->maxLength(11),
                            ]),
                            Grid::make(2)->schema([
                                TextInput::make('account_holder')
                                    ->label('Kontoinhaber')
                                    ->maxLength(100),
                                TextInput::make('bank_name')
                                    ->label('Geldinstitut')
                                    ->maxLength(100),
                            ]),
                        ]),
                    ]),

                // ── Tab 6: Ausbildung & Führerschein ──────────────────────
                Tab::make('Ausbildung & Führerschein')
                    ->icon('heroicon-o-academic-cap')
                    ->schema([
                        Section::make('Ausbildung')->schema([
                            Grid::make(2)->schema([
                                Select::make('education_level')
                                    ->label('Schulabschluss')
                                    ->options(Employee::educationLevelOptions()),
                                Select::make('vocational_training')
                                    ->label('Berufsausbildung')
                                    ->options(Employee::vocationalTrainingOptions()),
                            ]),
                            TextInput::make('vocational_title')
                                ->label('Berufsbezeichnung Ausbildung')
                                ->maxLength(100),
                        ]),
                        Section::make('Führerschein')->schema([
                            Toggle::make('has_driving_license')
                                ->label('Führerschein vorhanden')
                                ->live(),
                            CheckboxList::make('driving_license_classes')
                                ->label('Klassen')
                                ->options(Employee::drivingLicenseClassOptions())
                                ->columns(4)
                                ->visible(fn (Get $get): bool => (bool) $get('has_driving_license')),
                            Grid::make(3)->schema([
                                TextInput::make('driving_license_number')
                                    ->label('Führerschein-Nr.')
                                    ->maxLength(50),
                                DatePicker::make('driving_license_issued')
                                    ->label('Ausstellungsdatum'),
                                DatePicker::make('driving_license_expires')
                                    ->label('Ablaufdatum'),
                            ])->visible(fn (Get $get): bool => (bool) $get('has_driving_license')),
                        ]),
                    ]),

                // ── Tab 7: Genehmigungen ───────────────────────────────────
                Tab::make('Genehmigungen')
                    ->icon('heroicon-o-document-check')
                    ->schema([
                        Section::make('Aufenthaltstitel')->schema([
                            Grid::make(2)->schema([
                                TextInput::make('residence_permit_type')
                                    ->label('Aufenthaltstitel-Typ')
                                    ->maxLength(100),
                                DatePicker::make('residence_permit_expires')
                                    ->label('Gültig bis'),
                            ]),
                        ]),
                        Section::make('Arbeitserlaubnis')->schema([
                            Toggle::make('work_permit_granted')
                                ->label('Arbeitserlaubnis erteilt')
                                ->live(),
                            DatePicker::make('work_permit_expires')
                                ->label('Ablaufdatum Arbeitserlaubnis')
                                ->visible(fn (Get $get): bool => (bool) $get('work_permit_granted')),
                        ]),
                    ]),

                // ── Tab 8: Notfallkontakte ─────────────────────────────────
                Tab::make('Notfallkontakte')
                    ->icon('heroicon-o-phone')
                    ->schema([
                        Repeater::make('emergencyContacts')
                            ->label('Notfallkontakte')
                            ->relationship('emergencyContacts')
                            ->schema([
                                Hidden::make('priority'),
                                Grid::make(2)->schema([
                                    TextInput::make('name')
                                        ->label('Name')
                                        ->required()
                                        ->maxLength(100),
                                    TextInput::make('relationship')
                                        ->label('Beziehung')
                                        ->maxLength(50),
                                ]),
                                Grid::make(2)->schema([
                                    TextInput::make('phone')
                                        ->label('Telefon')
                                        ->required()
                                        ->tel()
                                        ->maxLength(30),
                                    TextInput::make('phone_mobile')
                                        ->label('Mobil')
                                        ->tel()
                                        ->maxLength(30),
                                ]),
                            ])
                            ->maxItems(2)
                            ->minItems(0)
                            ->addActionLabel('Notfallkontakt hinzufügen')
                            ->mutateRelationshipDataBeforeCreateUsing(function (array $data, $livewire): array {
                                // Auto-Priorität anhand der vorhandenen Kontakte setzen
                                $record = $livewire->getRecord();
                                if ($record) {
                                    $data['priority'] = $record->emergencyContacts()->count() + 1;
                                } else {
                                    $data['priority'] = 1;
                                }
                                return $data;
                            }),
                    ]),

                // ── Tab 9: Vorarbeitgeber ──────────────────────────────────
                Tab::make('Vorarbeitgeber')
                    ->icon('heroicon-o-building-office')
                    ->schema([
                        Repeater::make('previousEmploymentList')
                            ->label('Vorheriger Arbeitgeber')
                            ->relationship('previousEmployment')
                            ->schema([
                                TextInput::make('employer_name')
                                    ->label('Arbeitgeber Name')
                                    ->maxLength(150),
                                Grid::make(2)->schema([
                                    DatePicker::make('employed_from')
                                        ->label('Beschäftigt von'),
                                    DatePicker::make('employed_until')
                                        ->label('Beschäftigt bis'),
                                ]),
                                Section::make('Lohnsteuer lfd. Jahr')
                                    ->collapsed()
                                    ->schema([
                                        Grid::make(3)->schema([
                                            TextInput::make('gross_wages_ytd')
                                                ->label('Bruttolohn €')
                                                ->helperText('🔒 Verschlüsselt gespeichert')
                                                ->numeric()
                                                ->step(0.01)
                                                ->prefix('€'),
                                            TextInput::make('income_tax_ytd')
                                                ->label('Lohnsteuer €')
                                                ->helperText('🔒 Verschlüsselt gespeichert')
                                                ->numeric()
                                                ->step(0.01)
                                                ->prefix('€'),
                                            TextInput::make('solidarity_tax_ytd')
                                                ->label('Solidaritätszuschlag €')
                                                ->helperText('🔒 Verschlüsselt gespeichert')
                                                ->numeric()
                                                ->step(0.01)
                                                ->prefix('€'),
                                        ]),
                                    ]),
                            ])
                            ->maxItems(1)
                            ->defaultItems(0)
                            ->addActionLabel('Vorarbeitgeber hinzufügen')
                            ->collapsible(),
                    ]),

                // ── Tab 10: Zugang & System ────────────────────────────────
                Tab::make('Zugang & System')
                    ->icon('heroicon-o-key')
                    ->schema([
                        TextInput::make('mde_pin')
                            ->label('MDE-PIN')
                            ->password()
                            ->revealable()
                            ->minLength(4)
                            ->maxLength(6)
                            ->formatStateUsing(fn () => null)   // Bcrypt-Hash nie anzeigen
                            ->dehydrated(fn ($state) => filled($state)) // Nur speichern wenn befüllt
                            ->helperText('4–6 Stellen. Leer lassen = nicht ändern. Wird verschlüsselt gespeichert.'),
                        Select::make('status')
                            ->label('Status')
                            ->options([
                                'neu'        => 'Neu',
                                'eingeladen' => 'Eingeladen',
                                'aktiv'      => 'Aktiv',
                                'inaktiv'    => 'Inaktiv',
                            ])
                            ->default('neu'),
                        Placeholder::make('invitation_info')
                            ->label('Einladungsstatus')
                            ->content(fn ($record): string => $record
                                ? ($record->invited_at
                                    ? 'Einladung verschickt am ' . $record->invited_at->format('d.m.Y H:i')
                                      . ($record->invitation_expires_at
                                          ? ' (gültig bis ' . $record->invitation_expires_at->format('d.m.Y H:i') . ')'
                                          : '')
                                    : 'Noch keine Einladung verschickt.')
                                : 'Neuer Mitarbeiter – noch nicht gespeichert.'),

                        Placeholder::make('app_zugang_info')
                            ->label('App-Panel Zugang')
                            ->content(fn ($record): string => $record
                                ? ($record->user_id
                                    ? '✅ Aktiv — verknüpft mit User-Account #' . $record->user_id
                                      . ($record->user ? ' (' . $record->user->email . ')' : '')
                                    : '❌ Kein App-Panel-Zugang. Button „App-Zugang erstellen" in der Zeilenaktion nutzen.')
                                : '—'),
                    ]),

            ])->columnSpanFull(),
        ]);
    }

    // ─────────────────────────────────────────────
    // Table
    // ─────────────────────────────────────────────

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('full_name')
                    ->label('Name')
                    ->getStateUsing(fn (Employee $record): string => $record->fullName())
                    ->searchable(['first_name', 'last_name'])
                    ->sortable(['last_name', 'first_name']),
                TextColumn::make('station.name')
                    ->label('Station')
                    ->sortable()
                    ->placeholder('—'),
                TextColumn::make('employment_type')
                    ->label('Beschäftigungsart')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'vollzeit'    => 'success',
                        'teilzeit'    => 'info',
                        'minijob'     => 'warning',
                        'kurzfristig' => 'gray',
                        'azubi'       => 'primary',
                        'praktikum'   => 'danger',
                        'werkstudent' => 'indigo',
                        default       => 'gray',
                    })
                    ->formatStateUsing(fn (?string $state): string => Employee::employmentTypeOptions()[$state] ?? $state ?? '—'),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'neu'        => 'gray',
                        'eingeladen' => 'warning',
                        'aktiv'      => 'success',
                        'inaktiv'    => 'danger',
                        default      => 'gray',
                    })
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'neu'        => 'Neu',
                        'eingeladen' => 'Eingeladen',
                        'aktiv'      => 'Aktiv',
                        'inaktiv'    => 'Inaktiv',
                        default      => $state ?? '—',
                    }),
                TextColumn::make('employment_start')
                    ->label('Eintrittsdatum')
                    ->date('d.m.Y')
                    ->sortable()
                    ->placeholder('—'),
                TextColumn::make('email')
                    ->label('E-Mail')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('station_id')
                    ->label('Station')
                    ->options(fn (): array => Station::where('tenant_id', session('tenant_id'))
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->toArray()),
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'neu'        => 'Neu',
                        'eingeladen' => 'Eingeladen',
                        'aktiv'      => 'Aktiv',
                        'inaktiv'    => 'Inaktiv',
                    ]),
                SelectFilter::make('employment_type')
                    ->label('Beschäftigungsart')
                    ->options(Employee::employmentTypeOptions()),
                TrashedFilter::make(),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordAction('view')
            ->recordUrl(null)
            ->actions([
                ViewAction::make(),
                EditAction::make(),
                Action::make('passwort_senden')
                    ->label('Passwort senden')
                    ->icon('heroicon-o-lock-closed')
                    ->color('warning')
                    ->visible(fn ($record): bool => !empty($record->email))
                    ->requiresConfirmation()
                    ->modalHeading('Neues Passwort zusenden?')
                    ->modalDescription(fn ($record) => 'Ein zufälliges Passwort wird an ' . $record->email . ' gesendet. Das aktuelle Passwort wird überschrieben.')
                    ->modalSubmitActionLabel('Passwort senden')
                    ->action(function ($record): void {
                        $plain = \Illuminate\Support\Str::random(10);
                        $record->password             = \Illuminate\Support\Facades\Hash::make($plain);
                        $record->must_change_password = true;
                        $record->save();
                        \Illuminate\Support\Facades\Mail::to($record->email)
                            ->send(new EmployeePasswordMail($record, $plain));
                        \Filament\Notifications\Notification::make()
                            ->title('Passwort gesendet')
                            ->body('Ein temporäres Passwort wurde an ' . $record->email . ' verschickt.')
                            ->success()
                            ->send();
                    }),
                Action::make('einladen')
                    ->label('Einladen')
                    ->icon('heroicon-o-envelope')
                    ->color('info')
                    ->visible(fn (Employee $record): bool => $record->email !== null && $record->status !== 'aktiv')
                    ->action(function (Employee $record): void {
                        $record->invitation_token      = Str::random(64);
                        $record->invited_at            = now();
                        $record->invitation_expires_at = now()->addDays(7);
                        $record->status                = 'eingeladen';
                        $record->save();

                        Mail::to($record->email)->send(new EmployeeInvitationMail($record));

                        EmployeeAccessLog::record(
                            $record->id,
                            EmployeeAccessLog::ACTION_INVITE,
                            'employee',
                            $record->id
                        );

                        \Filament\Notifications\Notification::make()
                            ->title('Einladung versendet')
                            ->body('Einladung wurde an ' . $record->email . ' versendet.')
                            ->success()
                            ->send();
                    }),
                // ── App-Zugang erstellen ──────────────────────────
                Action::make('app_zugang')
                    ->label('App-Zugang erstellen')
                    ->icon('heroicon-o-computer-desktop')
                    ->color('success')
                    ->visible(fn (Employee $record): bool =>
                        is_null($record->user_id) && !empty($record->email) && !$record->deleted_at
                    )
                    ->requiresConfirmation()
                    ->modalHeading('App-Zugang erstellen')
                    ->modalDescription(fn (Employee $record): string =>
                        $record->first_name . ' ' . $record->last_name .
                        ' erhält einen Login für das App-Panel. Ein temporäres Passwort wird an ' .
                        $record->email . ' gesendet.'
                    )
                    ->modalSubmitActionLabel('Zugang erstellen & E-Mail senden')
                    ->action(function (Employee $record): void {
                        // Zufälliges Passwort
                        $plain = Str::random(12);

                        // Bestehenden (evtl. deaktivierten) User wiederverwenden
                        // oder neuen anlegen – verhindert unique-Constraint-Fehler
                        $user = User::withTrashed()
                            ->where('email', $record->email)
                            ->where('tenant_id', $record->tenant_id)
                            ->first();

                        if ($user) {
                            $user->restore(); // stellt soft-delete wieder her (falls gelöscht)
                            $user->update([
                                'first_name'           => $record->first_name,
                                'last_name'            => $record->last_name,
                                'password'             => Hash::make($plain),
                                'type'                 => 'employee',
                                'is_active'            => true,
                                'must_change_password' => true,
                                'email_verified_at'    => now(),
                            ]);
                        } else {
                            $user = User::create([
                                'tenant_id'            => $record->tenant_id,
                                'first_name'           => $record->first_name,
                                'last_name'            => $record->last_name,
                                'email'                => $record->email,
                                'password'             => Hash::make($plain),
                                'type'                 => 'employee',
                                'is_active'            => true,
                                'must_change_password' => true,
                                'email_verified_at'    => now(),
                                'locale'               => 'de',
                            ]);
                        }

                        // Mit Employee verknüpfen
                        $record->user_id = $user->id;
                        $record->save();

                        // Rollen zuweisen (Spatie)
                        try {
                            $user->assignRole('employee');
                        } catch (\Throwable) {
                            // Rolle nicht vorhanden – kein Fehler
                        }

                        // Einladungs-E-Mail senden
                        try {
                            Mail::to($user->email)->send(new EmployeeAppAccessMail($user, $plain));
                        } catch (\Throwable) {
                            // Mail-Fehler nicht fatal
                        }

                        EmployeeAccessLog::record(
                            $record->id,
                            EmployeeAccessLog::ACTION_INVITE,
                            'user',
                            $user->id
                        );

                        \Filament\Notifications\Notification::make()
                            ->title('App-Zugang erstellt')
                            ->body('Login-Daten wurden an ' . $user->email . ' gesendet.')
                            ->success()
                            ->send();
                    }),

                // ── App-Zugang entziehen ──────────────────────────
                Action::make('app_zugang_entziehen')
                    ->label('App-Zugang entziehen')
                    ->icon('heroicon-o-no-symbol')
                    ->color('danger')
                    ->visible(fn (Employee $record): bool =>
                        !is_null($record->user_id) && !$record->deleted_at
                    )
                    ->requiresConfirmation()
                    ->modalHeading('App-Zugang entziehen?')
                    ->modalDescription('Der Mitarbeiter kann sich dann nicht mehr ins App-Panel einloggen. Der Mitarbeiter-Datensatz bleibt erhalten.')
                    ->modalSubmitActionLabel('Zugang entziehen')
                    ->action(function (Employee $record): void {
                        // User deaktivieren und Verknüpfung trennen
                        if ($record->user) {
                            $record->user->update(['is_active' => false]);
                        }
                        $record->user_id = null;
                        $record->save();

                        \Filament\Notifications\Notification::make()
                            ->title('App-Zugang entzogen')
                            ->body('Der Mitarbeiter-Account wurde deaktiviert.')
                            ->success()
                            ->send();
                    }),

                DeleteAction::make()
                    ->hidden(fn (Employee $record): bool => (bool) $record->deleted_at),
                RestoreAction::make()
                    ->visible(fn (Employee $record): bool => (bool) $record->deleted_at),
            ])
            ->defaultSort('last_name', 'asc');
    }

    // ─────────────────────────────────────────────
    // Pages
    // ─────────────────────────────────────────────

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListEmployees::route('/'),
            'create' => Pages\CreateEmployee::route('/create'),
            'view'   => Pages\ViewEmployee::route('/{record}'),
            'edit'   => Pages\EditEmployee::route('/{record}/edit'),
        ];
    }

    // ─────────────────────────────────────────────
    // Soft-delete Scopes
    // ─────────────────────────────────────────────

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([SoftDeletingScope::class])
            ->where('tenant_id', session('tenant_id'));
    }

    // ─────────────────────────────────────────────
    // Helper: Länder-Optionen
    // ─────────────────────────────────────────────

    public static function countryOptions(): array
    {
        return [
            'Deutschland'     => 'Deutschland',
            'Österreich'      => 'Österreich',
            'Schweiz'         => 'Schweiz',
            'Frankreich'      => 'Frankreich',
            'Italien'         => 'Italien',
            'Spanien'         => 'Spanien',
            'Niederlande'     => 'Niederlande',
            'Belgien'         => 'Belgien',
            'Polen'           => 'Polen',
            'Tschechien'      => 'Tschechien',
            'Ungarn'          => 'Ungarn',
            'Rumänien'        => 'Rumänien',
            'Bulgarien'       => 'Bulgarien',
            'Griechenland'    => 'Griechenland',
            'Kroatien'        => 'Kroatien',
            'Türkei'          => 'Türkei',
            'Russland'        => 'Russland',
            'Ukraine'         => 'Ukraine',
            'Vereinigtes Königreich' => 'Vereinigtes Königreich',
            'Portugal'        => 'Portugal',
            'Schweden'        => 'Schweden',
            'Norwegen'        => 'Norwegen',
            'Dänemark'        => 'Dänemark',
            'Finnland'        => 'Finnland',
            'Serbien'         => 'Serbien',
            'Kosovo'          => 'Kosovo',
            'Bosnien und Herzegowina' => 'Bosnien und Herzegowina',
            'Nordmazedonien'  => 'Nordmazedonien',
            'Albanien'        => 'Albanien',
            'Syrien'          => 'Syrien',
            'Afghanistan'     => 'Afghanistan',
            'Irak'            => 'Irak',
            'Iran'            => 'Iran',
            'Marokko'         => 'Marokko',
            'Tunesien'        => 'Tunesien',
            'Algerien'        => 'Algerien',
            'Ägypten'         => 'Ägypten',
            'Ghana'           => 'Ghana',
            'Nigeria'         => 'Nigeria',
            'Eritrea'         => 'Eritrea',
            'Somalia'         => 'Somalia',
            'Äthiopien'       => 'Äthiopien',
            'Pakistan'        => 'Pakistan',
            'Indien'          => 'Indien',
            'China'           => 'China',
            'Vietnam'         => 'Vietnam',
            'Philippinen'     => 'Philippinen',
            'USA'             => 'USA',
            'Kanada'          => 'Kanada',
            'Brasilien'       => 'Brasilien',
            'Kolumbien'       => 'Kolumbien',
        ];
    }

    public static function nationalityOptions(): array
    {
        return [
            'deutsch'          => 'Deutsch',
            'oesterreichisch'  => 'Österreichisch',
            'schweizerisch'    => 'Schweizerisch',
            'franzoesisch'     => 'Französisch',
            'italienisch'      => 'Italienisch',
            'spanisch'         => 'Spanisch',
            'polnisch'         => 'Polnisch',
            'tschechisch'      => 'Tschechisch',
            'ungarisch'        => 'Ungarisch',
            'rumaenisch'       => 'Rumänisch',
            'bulgarisch'       => 'Bulgarisch',
            'griechisch'       => 'Griechisch',
            'kroatisch'        => 'Kroatisch',
            'tuerkisch'        => 'Türkisch',
            'russisch'         => 'Russisch',
            'ukrainisch'       => 'Ukrainisch',
            'britisch'         => 'Britisch',
            'portugiesisch'    => 'Portugiesisch',
            'niederlaendisch'  => 'Niederländisch',
            'belgisch'         => 'Belgisch',
            'serbisch'         => 'Serbisch',
            'bosnisch'         => 'Bosnisch',
            'albanisch'        => 'Albanisch',
            'syrisch'          => 'Syrisch',
            'afghanisch'       => 'Afghanisch',
            'irakisch'         => 'Irakisch',
            'iranisch'         => 'Iranisch',
            'marokkanisch'     => 'Marokkanisch',
            'tunesisch'        => 'Tunesisch',
            'algerisch'        => 'Algerisch',
            'aegyptisch'       => 'Ägyptisch',
            'pakistanisch'     => 'Pakistanisch',
            'indisch'          => 'Indisch',
            'chinesisch'       => 'Chinesisch',
            'vietnamesisch'    => 'Vietnamesisch',
            'philippinisch'    => 'Philippinisch',
            'amerikanisch'     => 'Amerikanisch',
            'kanadisch'        => 'Kanadisch',
            'brasilianisch'    => 'Brasilianisch',
            'staatenlos'       => 'Staatenlos',
            'ungeklaert'       => 'Ungeklärt',
        ];
    }
}
