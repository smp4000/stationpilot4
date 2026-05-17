<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\TenantResource\Pages;
use App\Models\Tenant;
use Database\Seeders\RolesAndPermissionsSeeder;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
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

class TenantResource extends Resource
{
    protected static ?string $model = Tenant::class;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-building-office-2';

    protected static \UnitEnum|string|null $navigationGroup = 'Mandanten';

    protected static ?int $navigationSort = 1;

    protected static ?string $label = 'Mandant';

    protected static ?string $pluralLabel = 'Mandanten';

    // ─────────────────────────────────────────────
    // Zugriffskontrolle
    // ─────────────────────────────────────────────

    public static function canAccess(): bool
    {
        return auth()->user()?->can('admin.tenants.list') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('admin.tenants.create') ?? false;
    }

    public static function canEdit($record): bool
    {
        return auth()->user()?->can('admin.tenants.edit') ?? false;
    }

    public static function canDelete($record): bool
    {
        return auth()->user()?->can('admin.tenants.delete') ?? false;
    }

    // ─────────────────────────────────────────────
    // Formular
    // ─────────────────────────────────────────────

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make('Mandant')->tabs([

                // ── Tab 1: Stammdaten ─────────────────────────
                Tab::make('Stammdaten')
                    ->icon('heroicon-o-building-office-2')
                    ->schema([
                        TextInput::make('name')
                            ->label('Firmenname')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (string $operation, $state, \Filament\Schemas\Components\Utilities\Set $set) {
                                if ($operation === 'create') {
                                    $set('slug', Tenant::generateSlug($state));
                                }
                            }),

                        TextInput::make('slug')
                            ->label('URL-Slug')
                            ->required()
                            ->unique(ignorable: fn($record) => $record)
                            ->maxLength(255)
                            ->helperText('Wird automatisch aus dem Firmennamen generiert'),

                        Select::make('owner_id')
                            ->label('Inhaber')
                            ->relationship(
                                name: 'owner',
                                titleAttribute: 'email',
                                modifyQueryUsing: fn($query) => $query->where('type', 'partner')
                            )
                            ->searchable()
                            ->preload()
                            ->nullable()
                            ->helperText('Partner-User der diesen Mandanten besitzt'),

                        TextInput::make('billing_email')
                            ->label('Rechnungs-E-Mail')
                            ->email()
                            ->required()
                            ->maxLength(255),

                        TextInput::make('phone')
                            ->label('Telefon')
                            ->tel()
                            ->maxLength(50),
                    ])->columns(2),

                // ── Tab 2: Adresse ────────────────────────────
                Tab::make('Adresse')
                    ->icon('heroicon-o-map-pin')
                    ->schema([
                        TextInput::make('billing_address.street')
                            ->label('Straße + Hausnummer')
                            ->maxLength(255),

                        TextInput::make('billing_address.zip')
                            ->label('PLZ')
                            ->maxLength(10),

                        TextInput::make('billing_address.city')
                            ->label('Stadt')
                            ->maxLength(255),

                        Select::make('billing_address.country')
                            ->label('Land')
                            ->options([
                                'DE' => '🇩🇪 Deutschland',
                                'AT' => '🇦🇹 Österreich',
                                'CH' => '🇨🇭 Schweiz',
                            ])
                            ->default('DE'),

                        TextInput::make('tax_id')
                            ->label('Steuernummer')
                            ->maxLength(50)
                            ->placeholder('z.B. 12/345/67890'),

                        TextInput::make('ust_id')
                            ->label('Umsatzsteuer-ID')
                            ->maxLength(20)
                            ->placeholder('z.B. DE123456789'),
                    ])->columns(2),

                // ── Tab 3: Abonnement ─────────────────────────
                Tab::make('Abonnement')
                    ->icon('heroicon-o-credit-card')
                    ->schema([
                        Select::make('subscription_status')
                            ->label('Abo-Status')
                            ->required()
                            ->options([
                                'trial'     => '🟡 Testphase',
                                'active'    => '🟢 Aktiv',
                                'past_due'  => '🟠 Zahlung überfällig',
                                'read_only' => '⚫ Nur lesen',
                                'cancelled' => '🔴 Gekündigt',
                                'archived'  => '⬛ Archiviert',
                            ])
                            ->default('trial'),

                        DateTimePicker::make('trial_ends_at')
                            ->label('Testphase endet am')
                            ->nullable()
                            ->displayFormat('d.m.Y H:i')
                            ->timezone('Europe/Berlin'),

                        Select::make('billing_driver')
                            ->label('Zahlungsart')
                            ->options([
                                'manual_sepa' => 'SEPA-Lastschrift (manuell)',
                                'stripe'      => 'Stripe (Phase 2)',
                            ])
                            ->default('manual_sepa'),

                        Toggle::make('is_active')
                            ->label('Mandant aktiv')
                            ->default(true)
                            ->helperText('Deaktiviert = kein Login möglich'),
                    ])->columns(2),

                // ── Tab 4: System ─────────────────────────────
                Tab::make('System')
                    ->icon('heroicon-o-cog-6-tooth')
                    ->schema([
                        Select::make('locale')
                            ->label('Sprache')
                            ->options(['de' => '🇩🇪 Deutsch', 'en' => '🇬🇧 Englisch'])
                            ->default('de'),

                        Select::make('timezone')
                            ->label('Zeitzone')
                            ->options([
                                'Europe/Berlin' => 'Europe/Berlin (Deutschland)',
                                'Europe/Vienna' => 'Europe/Vienna (Österreich)',
                                'Europe/Zurich' => 'Europe/Zurich (Schweiz)',
                            ])
                            ->default('Europe/Berlin'),

                        TextInput::make('ulid')
                            ->label('Öffentliche ID (ULID)')
                            ->disabled()
                            ->dehydrated(false)
                            ->placeholder('Wird automatisch generiert'),

                        TextInput::make('created_at')
                            ->label('Angelegt am')
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
                    ->label('Mandant')
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),

                TextColumn::make('billing_email')
                    ->label('E-Mail')
                    ->searchable()
                    ->copyable(),

                TextColumn::make('subscription_status')
                    ->label('Status')
                    ->badge()
                    ->color(fn(string $state) => match ($state) {
                        'trial'     => 'warning',
                        'active'    => 'success',
                        'past_due'  => 'danger',
                        'cancelled' => 'danger',
                        'archived'  => 'danger',
                        'read_only' => 'gray',
                        default     => 'gray',
                    })
                    ->formatStateUsing(fn($state) => match ($state) {
                        'trial'     => 'Testphase',
                        'active'    => 'Aktiv',
                        'past_due'  => 'Überfällig',
                        'read_only' => 'Nur lesen',
                        'cancelled' => 'Gekündigt',
                        'archived'  => 'Archiviert',
                        default     => $state,
                    }),

                IconColumn::make('is_active')
                    ->label('Aktiv')
                    ->boolean()
                    ->sortable(),

                TextColumn::make('trial_ends_at')
                    ->label('Trial endet')
                    ->dateTime('d.m.Y')
                    ->sortable()
                    ->placeholder('—'),

                TextColumn::make('users_count')
                    ->label('User')
                    ->counts('users')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Angelegt')
                    ->date('d.m.Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('subscription_status')
                    ->label('Abo-Status')
                    ->options([
                        'trial'     => 'Testphase',
                        'active'    => 'Aktiv',
                        'past_due'  => 'Überfällig',
                        'read_only' => 'Nur lesen',
                        'cancelled' => 'Gekündigt',
                        'archived'  => 'Archiviert',
                    ]),

                TernaryFilter::make('is_active')
                    ->label('Aktiv'),

                TrashedFilter::make(),
            ])
            ->actions([
                EditAction::make()
                    ->visible(fn() => auth()->user()?->can('admin.tenants.edit')),

                DeleteAction::make()
                    ->visible(fn() => auth()->user()?->can('admin.tenants.delete')),

                RestoreAction::make()
                    ->visible(fn() => auth()->user()?->can('admin.tenants.delete')),
            ])
            ->defaultSort('created_at', 'desc');
    }

    // ─────────────────────────────────────────────
    // Pages
    // ─────────────────────────────────────────────

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListTenants::route('/'),
            'create' => Pages\CreateTenant::route('/create'),
            'edit'   => Pages\EditTenant::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([SoftDeletingScope::class]);
    }
}
