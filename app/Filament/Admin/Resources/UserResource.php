<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Hash;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-users';

    protected static \UnitEnum|string|null $navigationGroup = 'Benutzer';

    protected static ?int $navigationSort = 1;

    protected static ?string $label = 'Benutzer';

    protected static ?string $pluralLabel = 'Benutzer';

    // ─────────────────────────────────────────────
    // Zugriffskontrolle
    // ─────────────────────────────────────────────

    public static function canAccess(): bool
    {
        return auth()->user()?->can('admin.users.list') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('admin.users.create') ?? false;
    }

    public static function canEdit($record): bool
    {
        return auth()->user()?->can('admin.users.edit') ?? false;
    }

    public static function canDelete($record): bool
    {
        return auth()->user()?->can('admin.users.delete') ?? false;
    }

    // ─────────────────────────────────────────────
    // Formular
    // ─────────────────────────────────────────────

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make('Benutzer')->tabs([

                // ── Tab 1: Person / Firma ─────────────────────
                Tab::make('Person / Firma')
                    ->icon('heroicon-o-user')
                    ->schema([
                        Toggle::make('is_company')
                            ->label('Ist eine Firma')
                            ->default(false)
                            ->live()
                            ->helperText('Schaltet die Namensfelder um'),

                        TextInput::make('first_name')
                            ->label('Vorname')
                            ->maxLength(255)
                            ->visible(fn($get) => ! $get('is_company'))
                            ->required(fn($get) => ! $get('is_company')),

                        TextInput::make('last_name')
                            ->label('Nachname')
                            ->maxLength(255)
                            ->visible(fn($get) => ! $get('is_company'))
                            ->required(fn($get) => ! $get('is_company')),

                        TextInput::make('company_name')
                            ->label('Firmenname')
                            ->maxLength(255)
                            ->visible(fn($get) => $get('is_company'))
                            ->required(fn($get) => $get('is_company')),
                    ])->columns(2),

                // ── Tab 2: Kontakt ────────────────────────────
                Tab::make('Kontakt')
                    ->icon('heroicon-o-envelope')
                    ->schema([
                        TextInput::make('email')
                            ->label('E-Mail')
                            ->email()
                            ->required()
                            ->unique(ignorable: fn($record) => $record)
                            ->maxLength(255),

                        TextInput::make('phone')
                            ->label('Telefon')
                            ->tel()
                            ->maxLength(50),

                        Select::make('locale')
                            ->label('Sprache')
                            ->options(['de' => '🇩🇪 Deutsch', 'en' => '🇬🇧 Englisch'])
                            ->default('de'),
                    ])->columns(2),

                // ── Tab 3: Zugang ─────────────────────────────
                Tab::make('Zugang')
                    ->icon('heroicon-o-lock-closed')
                    ->schema([
                        Select::make('type')
                            ->label('Benutzertyp')
                            ->required()
                            ->options([
                                'super_admin' => '🔴 Super-Admin',
                                'partner'     => '🔵 Partner',
                                'employee'    => '🟢 Mitarbeiter',
                                'tax_advisor' => '🟣 Steuerberater',
                            ])
                            ->default('employee'),

                        Select::make('tenant_id')
                            ->label('Mandant')
                            ->relationship('tenant', 'name')
                            ->searchable()
                            ->preload()
                            ->nullable()
                            ->helperText('Leer lassen = Super-Admin'),

                        TextInput::make('password')
                            ->label('Passwort')
                            ->password()
                            ->revealable()
                            ->required(fn(string $operation) => $operation === 'create')
                            ->dehydrateStateUsing(fn($state) => filled($state) ? Hash::make($state) : null)
                            ->dehydrated(fn($state) => filled($state))
                            ->minLength(12)
                            ->helperText('Mindestens 12 Zeichen'),

                        Toggle::make('is_active')
                            ->label('Aktiv')
                            ->default(true),
                    ])->columns(2),

                // ── Tab 4: Android-App ────────────────────────
                Tab::make('Android-App')
                    ->icon('heroicon-o-device-phone-mobile')
                    ->schema([
                        TextInput::make('scan_code')
                            ->label('NFC / Scan-Code')
                            ->maxLength(50)
                            ->unique(ignorable: fn($record) => $record)
                            ->nullable()
                            ->helperText('Eindeutiger Code für NFC-Tag oder Barcode-Login'),

                        TextInput::make('last_login_at')
                            ->label('Letzter Login')
                            ->disabled()
                            ->dehydrated(false)
                            ->formatStateUsing(fn($state) => $state
                                ? \Carbon\Carbon::parse($state)->format('d.m.Y H:i')
                                : '—'
                            ),

                        TextInput::make('last_login_ip')
                            ->label('Letzte IP')
                            ->disabled()
                            ->dehydrated(false),
                    ])->columns(2),

            ])->columnSpanFull(),
        ]);
    }

    // ─────────────────────────────────────────────
    // Tabelle
    // ─────────────────────────────────────────────

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Name')
                    ->getStateUsing(fn($record) => $record->name)
                    ->searchable(['first_name', 'last_name', 'company_name'])
                    ->sortable(['last_name'])
                    ->weight('medium'),

                TextColumn::make('email')
                    ->label('E-Mail')
                    ->searchable()
                    ->copyable(),

                TextColumn::make('type')
                    ->label('Typ')
                    ->badge()
                    ->color(fn(string $state) => match ($state) {
                        'super_admin' => 'danger',
                        'partner'     => 'primary',
                        'employee'    => 'success',
                        'tax_advisor' => 'warning',
                        default       => 'gray',
                    })
                    ->formatStateUsing(fn($state) => match ($state) {
                        'super_admin' => 'Super-Admin',
                        'partner'     => 'Partner',
                        'employee'    => 'Mitarbeiter',
                        'tax_advisor' => 'Steuerberater',
                        default       => $state,
                    }),

                TextColumn::make('tenant.name')
                    ->label('Mandant')
                    ->placeholder('—')
                    ->sortable()
                    ->searchable(),

                IconColumn::make('is_active')
                    ->label('Aktiv')
                    ->boolean(),

                TextColumn::make('last_login_at')
                    ->label('Letzter Login')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->placeholder('—'),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label('Typ')
                    ->options([
                        'super_admin' => 'Super-Admin',
                        'partner'     => 'Partner',
                        'employee'    => 'Mitarbeiter',
                        'tax_advisor' => 'Steuerberater',
                    ]),

                SelectFilter::make('tenant_id')
                    ->label('Mandant')
                    ->relationship('tenant', 'name')
                    ->searchable()
                    ->preload(),

                TernaryFilter::make('is_active')
                    ->label('Aktiv'),

                TrashedFilter::make(),
            ])
            ->actions([
                EditAction::make()
                    ->visible(fn() => auth()->user()?->can('admin.users.edit')),

                DeleteAction::make()
                    ->visible(fn() => auth()->user()?->can('admin.users.delete')),

                RestoreAction::make()
                    ->visible(fn() => auth()->user()?->can('admin.users.delete')),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit'   => Pages\EditUser::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([SoftDeletingScope::class]);
    }
}
