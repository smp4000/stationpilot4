<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\GoPilotRollenResource\Pages;
use App\Support\RolePermissions;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rules\Unique;
use Spatie\Permission\Models\Role;

/**
 * Rollen für die GoPilot Android-App (scope "gopilot", employee.* Permissions).
 * Steuert, welche Bereiche/Kacheln einem Mitarbeiter in der App angezeigt werden.
 */
class GoPilotRollenResource extends Resource
{
    protected static ?string $model = Role::class;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-device-phone-mobile';
    protected static \UnitEnum|string|null $navigationGroup  = 'Einstellungen';
    protected static ?string $navigationLabel                = 'GoPilot-Rollen';
    protected static ?string $modelLabel                     = 'GoPilot-Rolle';
    protected static ?string $pluralModelLabel               = 'GoPilot-Rollen';
    protected static ?int $navigationSort                    = 96;

    // ── Nur Partner mit settings.edit ───────────────────────────────────────

    public static function canAccess(): bool
    {
        return auth()->user()?->can('partner.settings.edit') ?? false;
    }

    public static function canDelete(Model $record): bool
    {
        return ! static::isBuiltIn($record->name);
    }

    // ── Nur GoPilot-Rollen des eigenen Mandanten ────────────────────────────

    public static function getEloquentQuery(): Builder
    {
        $tenantId = (int) session('tenant_id', 0);
        return parent::getEloquentQuery()
            ->where('tenant_id', $tenantId)
            ->where('guard_name', 'web')
            ->where('scope', RolePermissions::SCOPE_GOPILOT);
    }

    // ── Hilfsmethoden ────────────────────────────────────────────────────────

    public static function isBuiltIn(string $name): bool
    {
        return in_array($name, RolePermissions::BUILTIN_GOPILOT_ROLES);
    }

    /** GoPilot-Permission-Gruppen (employee.*) aus dem zentralen Katalog. */
    public static function permissionGroups(): array
    {
        return RolePermissions::gopilotGroups();
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
                ->unique(
                    table: 'roles',
                    column: 'name',
                    ignoreRecord: true,
                    modifyRuleUsing: fn (Unique $rule): Unique => $rule
                        ->where('tenant_id', (int) session('tenant_id', 0))
                        ->where('guard_name', 'web'),
                )
                ->placeholder('z.B. Aushilfe-Shop, Nachtschicht')
                ->helperText('Standard-Rollen können nicht umbenannt werden.')
                ->disabledOn('edit')
                ->dehydrated(fn (string $operation): bool => $operation === 'create'),

            Section::make('📱 Sichtbare Bereiche in der GoPilot App')
                ->description('Bestimmt, welche Kacheln und Menüpunkte ein Mitarbeiter mit dieser Rolle in der App sieht.')
                ->schema([
                    Grid::make(['default' => 1, 'md' => 2])->schema([
                        static::permCard('perms_gopilot_station'),
                        static::permCard('perms_gopilot_shop'),
                        static::permCard('perms_gopilot_bistro'),
                        static::permCard('perms_gopilot_keys'),
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
                    ->formatStateUsing(fn (string $state): string => RolePermissions::roleLabel($state))
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
                    ->label('Mitarbeiter')
                    ->getStateUsing(function (Role $record): string {
                        $tenantId = (int) session('tenant_id', 0);
                        $count = \DB::table('model_has_roles')
                            ->where('role_id', $record->id)
                            ->where('tenant_id', $tenantId)
                            ->count();
                        return $count . ' Mitarbeiter';
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
                    ->label('Neue GoPilot-Rolle erstellen'),
            ]);
    }

    // ── Pages ────────────────────────────────────────────────────────────────

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListGoPilotRollen::route('/'),
            'create' => Pages\CreateGoPilotRolle::route('/create'),
            'edit'   => Pages\EditGoPilotRolle::route('/{record}/edit'),
        ];
    }
}
