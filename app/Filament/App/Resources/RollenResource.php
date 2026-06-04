<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\RollenResource\Pages;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RollenResource extends Resource
{
    protected static ?string $model = Role::class;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-shield-exclamation';
    protected static \UnitEnum|string|null $navigationGroup  = 'Einstellungen';
    protected static ?string $navigationLabel                = 'Rollen & Rechte';
    protected static ?string $modelLabel                     = 'Rolle';
    protected static ?string $pluralModelLabel               = 'Rollen & Rechte';
    protected static ?int $navigationSort                    = 95;

    // ── Nur Partner mit settings.edit ───────────────────────────────────────

    public static function canAccess(): bool
    {
        return auth()->user()?->can('partner.settings.edit') ?? false;
    }

    public static function canDelete(Model $record): bool
    {
        return ! static::isBuiltIn($record->name);
    }

    // ── Nur Rollen des eigenen Mandanten ────────────────────────────────────

    public static function getEloquentQuery(): Builder
    {
        $tenantId = (int) session('tenant_id', 0);
        return parent::getEloquentQuery()
            ->where('tenant_id', $tenantId)
            ->where('guard_name', 'web');
    }

    // ── Hilfsmethoden ────────────────────────────────────────────────────────

    public static function isBuiltIn(string $name): bool
    {
        return in_array($name, ['partner_owner', 'partner_manager', 'station_manager', 'employee', 'tax_advisor']);
    }

    /**
     * Alle Permission-Gruppen mit Bezeichnungen.
     * Wird in Form (CheckboxLists) und in mutateData verwendet.
     */
    public static function permissionGroups(): array
    {
        return [
            'perms_dashboard' => [
                'label' => '🏠 Dashboard',
                'perms' => [
                    'partner.dashboard.view' => 'Dashboard anzeigen',
                ],
            ],
            'perms_stations' => [
                'label' => '⛽ Tankstellen',
                'perms' => [
                    'partner.stations.list'   => 'Liste anzeigen',
                    'partner.stations.view'   => 'Details einsehen',
                    'partner.stations.create' => 'Anlegen',
                    'partner.stations.edit'   => 'Bearbeiten',
                    'partner.stations.delete' => 'Löschen',
                ],
            ],
            'perms_employees' => [
                'label' => '👥 Personal',
                'perms' => [
                    'partner.employees.list'      => 'Liste anzeigen',
                    'partner.employees.view'      => 'Details einsehen',
                    'partner.employees.create'    => 'Anlegen',
                    'partner.employees.edit'      => 'Bearbeiten',
                    'partner.employees.delete'    => 'Löschen',
                    'partner.employees.invite'    => 'Einladen',
                    'partner.employees.approve'   => 'Genehmigen',
                    'partner.employees.terminate' => 'Kündigen',
                ],
            ],
            'perms_contracts' => [
                'label' => '📄 Arbeitsverträge',
                'perms' => [
                    'partner.contracts.list'   => 'Liste anzeigen',
                    'partner.contracts.view'   => 'Details einsehen',
                    'partner.contracts.create' => 'Erstellen',
                    'partner.contracts.edit'   => 'Bearbeiten',
                    'partner.contracts.delete' => 'Löschen',
                    'partner.contracts.send'   => 'Versenden (Onboarding)',
                ],
            ],
            'perms_documents' => [
                'label' => '📋 Generierte Dokumente',
                'perms' => [
                    'partner.documents.list'   => 'Liste anzeigen',
                    'partner.documents.view'   => 'Details einsehen',
                    'partner.documents.create' => 'Generieren / Senden',
                    'partner.documents.delete' => 'Löschen',
                ],
            ],
            'perms_document_templates' => [
                'label' => '📝 Dokument-Vorlagen',
                'perms' => [
                    'partner.document_templates.list'   => 'Liste anzeigen',
                    'partner.document_templates.create' => 'Erstellen',
                    'partner.document_templates.edit'   => 'Bearbeiten',
                    'partner.document_templates.delete' => 'Löschen',
                ],
            ],
            'perms_keys' => [
                'label' => '🔑 Schlüssel',
                'perms' => [
                    'partner.keys.list'   => 'Liste anzeigen',
                    'partner.keys.view'   => 'Details einsehen',
                    'partner.keys.create' => 'Ausgeben',
                    'partner.keys.edit'   => 'Bearbeiten',
                    'partner.keys.delete' => 'Löschen',
                ],
            ],
            'perms_mde' => [
                'label' => '📱 MDE-Gerät / Android-App',
                'perms' => [
                    'partner.mde.list'    => 'Aktivitäten & Logs einsehen',
                    'partner.mde.view'    => 'Einzelne Aktivität details',
                    'partner.mde.manage'  => 'MDE-Zugänge verwalten (PIN, Scan-Code)',
                    'partner.mde.assign'  => 'Gerät einem Mitarbeiter zuweisen',
                    'partner.mde.reports' => 'Schichtprotokolle & Berichte',
                ],
            ],
            'perms_billing' => [
                'label' => '💳 Abrechnung',
                'perms' => [
                    'partner.billing.view'   => 'Einsehen',
                    'partner.billing.manage' => 'Verwalten',
                ],
            ],
            'perms_reports' => [
                'label' => '📊 Berichte',
                'perms' => [
                    'partner.reports.view'   => 'Anzeigen',
                    'partner.reports.export' => 'Exportieren',
                ],
            ],
            'perms_settings' => [
                'label' => '⚙️ Einstellungen & Team',
                'perms' => [
                    'partner.settings.view' => 'Einstellungen einsehen',
                    'partner.settings.edit' => 'Bearbeiten & Team-Verwaltung',
                ],
            ],

            // ── GoPilot App Permissions ─────────────────────────────────────
            'perms_gopilot_bistro' => [
                'label' => '🍽️ GoPilot — Bistro',
                'perms' => [
                    'employee.bistro.view'     => 'Bistro-Bereich sichtbar',
                    'employee.bistro.orders'   => 'Bestellungen',
                    'employee.bistro.daily'    => 'Tagesabschluss',
                    'employee.bistro.delivery' => 'Wareneingang',
                ],
            ],
            'perms_gopilot_shop' => [
                'label' => '🏪 GoPilot — Shop',
                'perms' => [
                    'employee.shop.view'      => 'Shop-Bereich sichtbar',
                    'employee.shop.cashier'   => 'Kassenabschluss',
                    'employee.shop.delivery'  => 'Wareneingang',
                    'employee.shop.inventory' => 'Inventur',
                ],
            ],
            'perms_gopilot_station' => [
                'label' => '⛽ GoPilot — Tankstelle',
                'perms' => [
                    'employee.station.view'     => 'Tankstellen-Bereich sichtbar',
                    'employee.station.shift'    => 'Schichtprotokoll führen',
                    'employee.station.tank'     => 'Tankkontrolle durchführen',
                    'employee.station.incident' => 'Störungen melden',
                ],
            ],
            'perms_gopilot_keys' => [
                'label' => '🔑 GoPilot — Schlüssel & Zugang',
                'perms' => [
                    'employee.keys.view'     => 'Schlüssel-Übergabe sichtbar',
                    'employee.keys.handover' => 'Schlüssel übergeben / zurücknehmen',
                ],
            ],
        ];
    }

    /** Eine einzelne Permission-Karte (Section + CheckboxList mit "Alle auswählen"). */
    private static function permCard(string $fieldName): Section
    {
        $group = static::permissionGroups()[$fieldName];
        return Section::make($group['label'])
            ->compact()
            ->schema([
                CheckboxList::make($fieldName)
                    ->label('')
                    ->options($group['perms'])
                    ->bulkToggleable()
                    ->columns(2)
                    ->gridDirection('row'),
            ]);
    }

    // ── Form ─────────────────────────────────────────────────────────────────

    public static function form(Schema $schema): Schema
    {
        return $schema->components([

            TextInput::make('name')
                ->label('Rollen-Name')
                ->required()
                ->maxLength(64)
                ->unique(table: 'roles', column: 'name', ignoreRecord: true)
                ->placeholder('z.B. Buchhalter, Filialleiter-Nord')
                ->helperText('Standard-Rollen können nicht umbenannt werden.')
                ->disabledOn('edit')
                ->dehydratedWhenHidden(),

            Tabs::make('Berechtigungen')
                ->columnSpanFull()
                ->tabs([

                    // ── Tab 1: Kernfunktionen ─────────────────────────────
                    Tab::make('Ressourcen')
                        ->icon('heroicon-o-squares-2x2')
                        ->schema([
                            Grid::make(['default' => 1, 'md' => 2, 'xl' => 3])->schema([
                                static::permCard('perms_stations'),
                                static::permCard('perms_employees'),
                                static::permCard('perms_contracts'),
                                static::permCard('perms_documents'),
                                static::permCard('perms_document_templates'),
                                static::permCard('perms_keys'),
                            ]),
                        ]),

                    // ── Tab 2: MDE-Gerät ──────────────────────────────────
                    Tab::make('MDE-Gerät')
                        ->icon('heroicon-o-device-phone-mobile')
                        ->schema([
                            Grid::make(['default' => 1, 'md' => 2])->schema([
                                static::permCard('perms_mde'),
                                static::permCard('perms_dashboard'),
                            ]),
                        ]),

                    // ── Tab 3: Berichte & Finanzen ────────────────────────
                    Tab::make('Berichte & Finanzen')
                        ->icon('heroicon-o-chart-bar')
                        ->schema([
                            Grid::make(['default' => 1, 'md' => 2])->schema([
                                static::permCard('perms_reports'),
                                static::permCard('perms_billing'),
                            ]),
                        ]),

                    // ── Tab 4: GoPilot App ────────────────────────────────
                    Tab::make('GoPilot App')
                        ->icon('heroicon-o-device-phone-mobile')
                        ->badge('NEU')
                        ->schema([
                            Grid::make(['default' => 1, 'md' => 2, 'xl' => 3])->schema([
                                static::permCard('perms_gopilot_bistro'),
                                static::permCard('perms_gopilot_shop'),
                                static::permCard('perms_gopilot_station'),
                                static::permCard('perms_gopilot_keys'),
                            ]),
                        ]),

                    // ── Tab 5: Einstellungen ──────────────────────────────
                    Tab::make('Einstellungen')
                        ->icon('heroicon-o-cog-6-tooth')
                        ->schema([
                            Grid::make(['default' => 1, 'md' => 2])->schema([
                                static::permCard('perms_settings'),
                            ]),
                        ]),
                ]),
        ]);
    }

    // ── Table ────────────────────────────────────────────────────────────────

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Rolle')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'partner_owner'   => '👑 Inhaber',
                        'partner_manager' => '🏢 Manager',
                        'station_manager' => '🏪 Stationsleiter',
                        'employee'        => '👤 Mitarbeiter',
                        'tax_advisor'     => '📊 Steuerberater',
                        default           => $state,
                    })
                    ->searchable()
                    ->weight('semibold'),

                TextColumn::make('name_raw')
                    ->label('Technischer Name')
                    ->getStateUsing(fn (Role $record): string => $record->name)
                    ->color('gray')
                    ->size('sm'),

                TextColumn::make('permissions_count')
                    ->label('Berechtigungen')
                    ->getStateUsing(fn (Role $record): string => $record->permissions->count() . ' Rechte')
                    ->badge()
                    ->color('info'),

                TextColumn::make('users_count')
                    ->label('Benutzer')
                    ->getStateUsing(function (Role $record): string {
                        $tenantId = (int) session('tenant_id', 0);
                        $count = \DB::table('model_has_roles')
                            ->where('role_id', $record->id)
                            ->where('tenant_id', $tenantId)
                            ->count();
                        return $count . ' Benutzer';
                    })
                    ->badge()
                    ->color('success'),
            ])
            ->recordActions([
                \Filament\Actions\EditAction::make()
                    ->label('Rechte bearbeiten'),
                \Filament\Actions\DeleteAction::make()
                    ->hidden(fn (Role $record): bool => static::isBuiltIn($record->name))
                    ->requiresConfirmation(),
            ])
            ->headerActions([
                \Filament\Actions\CreateAction::make()
                    ->label('Neue Rolle erstellen'),
            ]);
    }

    // ── Pages ────────────────────────────────────────────────────────────────

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListRollen::route('/'),
            'create' => Pages\CreateRolle::route('/create'),
            'edit'   => Pages\EditRolle::route('/{record}/edit'),
        ];
    }
}
