<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\RoleResource\Pages;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\TextInput;
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

class RoleResource extends Resource
{
    protected static ?string $model = Role::class;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-shield-exclamation';
    protected static \UnitEnum|string|null $navigationGroup  = 'System';
    protected static ?string $navigationLabel                = 'Rollen & Rechte';
    protected static ?string $modelLabel                     = 'Rolle';
    protected static ?string $pluralModelLabel               = 'Rollen & Rechte';
    protected static ?int $navigationSort                    = 30;

    // ── Zugriff: nur Super-Admin Level 3 ────────────────────────────────────

    public static function canAccess(): bool
    {
        return auth()->user()?->can('admin.system.edit') ?? false;
    }

    public static function canDelete(Model $record): bool
    {
        return ! in_array($record->name, ['super_admin_level_1', 'super_admin_level_2', 'super_admin_level_3']);
    }

    // ── Nur globale Rollen (tenant_id = 0) ──────────────────────────────────

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('tenant_id', 0)
            ->where('guard_name', 'web');
    }

    // ── Permission-Gruppen für Admin ─────────────────────────────────────────

    public static function permissionGroups(): array
    {
        return [
            'perms_dashboard' => [
                'label' => '🏠 Dashboard',
                'perms' => [
                    'admin.dashboard.view' => 'Dashboard anzeigen',
                ],
            ],
            'perms_tenants' => [
                'label' => '🏢 Mandanten',
                'perms' => [
                    'admin.tenants.list'       => 'Liste anzeigen',
                    'admin.tenants.view-stats' => 'Statistiken einsehen',
                    'admin.tenants.view'       => 'Details einsehen',
                    'admin.tenants.create'     => 'Anlegen',
                    'admin.tenants.edit'       => 'Bearbeiten',
                    'admin.tenants.delete'     => 'Löschen',
                    'admin.tenants.archive'    => 'Archivieren',
                ],
            ],
            'perms_users' => [
                'label' => '👤 Benutzer',
                'perms' => [
                    'admin.users.list'   => 'Liste anzeigen',
                    'admin.users.view'   => 'Details einsehen',
                    'admin.users.create' => 'Anlegen',
                    'admin.users.edit'   => 'Bearbeiten',
                    'admin.users.delete' => 'Löschen',
                ],
            ],
            'perms_audit' => [
                'label' => '🔍 Audit-Log',
                'perms' => [
                    'admin.audit-log.view'         => 'Logs anzeigen',
                    'admin.audit-log.view-details'  => 'Details einsehen',
                ],
            ],
            'perms_system' => [
                'label' => '⚙️ System',
                'perms' => [
                    'admin.system.view' => 'System einsehen',
                    'admin.system.edit' => 'System bearbeiten (Rollen, Permissions)',
                ],
            ],
        ];
    }

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
                ->placeholder('z.B. super_admin_level_4')
                ->disabledOn('edit'),

            Tabs::make('Berechtigungen')
                ->columnSpanFull()
                ->tabs([

                    // ── Tab 1: Mandanten & Benutzer ───────────────────────
                    Tab::make('Mandanten & Benutzer')
                        ->icon('heroicon-o-building-office-2')
                        ->schema([
                            Grid::make(['default' => 1, 'md' => 2])->schema([
                                static::permCard('perms_tenants'),
                                static::permCard('perms_users'),
                            ]),
                        ]),

                    // ── Tab 2: Audit & System ─────────────────────────────
                    Tab::make('Audit & System')
                        ->icon('heroicon-o-shield-check')
                        ->schema([
                            Grid::make(['default' => 1, 'md' => 3])->schema([
                                static::permCard('perms_dashboard'),
                                static::permCard('perms_audit'),
                                static::permCard('perms_system'),
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
                    ->searchable()
                    ->weight('semibold'),

                TextColumn::make('permissions_count')
                    ->label('Berechtigungen')
                    ->getStateUsing(fn (Role $record): string => $record->permissions->count() . ' Rechte')
                    ->badge()
                    ->color('info'),

                TextColumn::make('users_count')
                    ->label('Benutzer')
                    ->getStateUsing(function (Role $record): string {
                        $count = \DB::table('model_has_roles')
                            ->where('role_id', $record->id)
                            ->where('tenant_id', 0)
                            ->count();
                        return $count . ' Benutzer';
                    })
                    ->badge()
                    ->color('success'),

                TextColumn::make('created_at')
                    ->label('Erstellt')
                    ->date('d.m.Y')
                    ->sortable(),
            ])
            ->recordActions([
                \Filament\Actions\EditAction::make()->label('Rechte bearbeiten'),
                \Filament\Actions\DeleteAction::make()
                    ->hidden(fn (Role $record): bool => in_array(
                        $record->name,
                        ['super_admin_level_1', 'super_admin_level_2', 'super_admin_level_3']
                    )),
            ])
            ->headerActions([
                \Filament\Actions\CreateAction::make()->label('Neue Admin-Rolle'),
            ]);
    }

    // ── Pages ────────────────────────────────────────────────────────────────

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListRoles::route('/'),
            'create' => Pages\CreateRole::route('/create'),
            'edit'   => Pages\EditRole::route('/{record}/edit'),
        ];
    }
}
