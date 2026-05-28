<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\TeamMemberResource\Pages;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Permission\PermissionRegistrar;

class TeamMemberResource extends Resource
{
    protected static ?string $model = User::class;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-user-group';

    protected static \UnitEnum|string|null $navigationGroup = 'Einstellungen';

    protected static ?string $navigationLabel = 'Team-Verwaltung';

    protected static ?string $modelLabel       = 'Team-Mitglied';
    protected static ?string $pluralModelLabel = 'Team-Verwaltung';

    protected static ?int $navigationSort = 90;

    // ── Zugriff: nur wer Partner.settings.edit darf ─────────────────────────

    public static function canAccess(): bool
    {
        return auth()->user()?->can('partner.settings.edit') ?? false;
    }

    public static function canCreate(): bool   { return false; }
    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool   { return false; }
    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool { return false; }

    // ── Nur Mitglieder des eigenen Mandanten anzeigen ───────────────────────

    public static function getEloquentQuery(): Builder
    {
        $tenantId  = session('tenant_id');
        $currentId = auth()->id();

        return parent::getEloquentQuery()
            ->where('tenant_id', $tenantId)
            ->where('id', '!=', $currentId)   // selbst nicht anzeigen
            ->whereNull('deleted_at');
    }

    // ── Form (nicht genutzt, nur wegen Interface) ────────────────────────────

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    // ── Tabelle ──────────────────────────────────────────────────────────────

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Name')
                    ->searchable(['first_name', 'last_name', 'company_name'])
                    ->sortable()
                    ->weight('semibold'),

                TextColumn::make('email')
                    ->label('E-Mail')
                    ->searchable()
                    ->copyable(),

                TextColumn::make('type')
                    ->label('Benutzertyp')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'partner'     => 'Partner',
                        'employee'    => 'Mitarbeiter',
                        'tax_advisor' => 'Steuerberater',
                        default       => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'partner'     => 'primary',
                        'employee'    => 'success',
                        'tax_advisor' => 'warning',
                        default       => 'gray',
                    }),

                TextColumn::make('spatie_role')
                    ->label('Rolle')
                    ->badge()
                    ->color('info')
                    ->getStateUsing(function (User $record): string {
                        $role = $record->getRoleNames()->first();
                        return match ($role) {
                            'partner_owner'   => '👑 Inhaber',
                            'partner_manager' => '🏢 Manager',
                            'station_manager' => '🏪 Stationsleiter',
                            'employee'        => '👤 Mitarbeiter',
                            'tax_advisor'     => '📊 Steuerberater',
                            default           => $role ?? '— keine —',
                        };
                    }),

                IconColumn::make('is_active')
                    ->label('Aktiv')
                    ->boolean(),

                TextColumn::make('last_login_at')
                    ->label('Letzter Login')
                    ->since()
                    ->placeholder('noch nie')
                    ->sortable(),
            ])
            ->defaultSort('type')
            ->recordActions([
                // ── Rolle ändern ───────────────────────────────────────────
                Action::make('rolle_aendern')
                    ->label('Rolle ändern')
                    ->icon('heroicon-o-shield-check')
                    ->color('primary')
                    ->modalHeading(fn (User $record): string => 'Rolle ändern — ' . $record->name)
                    ->modalDescription('Die Rolle bestimmt, welche Bereiche der Partner-Oberfläche dieser Benutzer sehen und bearbeiten darf.')
                    ->form(function (User $record): array {
                        $currentRole = $record->getRoleNames()->first() ?? '';
                        $isOwner     = auth()->user()?->hasRole('partner_owner');

                        $options = [
                            'partner_manager' => '🏢 Manager — alles außer Billing & Team-Verwaltung',
                            'station_manager' => '🏪 Stationsleiter — Mitarbeiter + Stationen einsehen, Schlüssel verwalten',
                            'employee'        => '👤 Mitarbeiter — nur Dashboard & eigene Daten',
                            'tax_advisor'     => '📊 Steuerberater — Mitarbeiter + Verträge lesen, Berichte exportieren',
                        ];

                        // Nur Inhaber können anderen den Inhaber-Status geben
                        if ($isOwner) {
                            $options = array_merge(['partner_owner' => '👑 Inhaber — Vollzugriff inkl. Team-Verwaltung'], $options);
                        }

                        return [
                            Select::make('role')
                                ->label('Neue Rolle')
                                ->options($options)
                                ->default($currentRole)
                                ->required()
                                ->native(false),
                        ];
                    })
                    ->action(function (User $record, array $data): void {
                        $tenantId = session('tenant_id');

                        // Team-Kontext setzen → wichtig für Spatie Teams-Mode
                        app(PermissionRegistrar::class)->setPermissionsTeamId($tenantId);

                        // Sicherheit: partner_owner nur vergeben wenn man selbst einer ist
                        if ($data['role'] === 'partner_owner' && ! auth()->user()?->hasRole('partner_owner')) {
                            Notification::make()
                                ->title('Keine Berechtigung')
                                ->body('Nur Inhaber können den Inhaber-Status vergeben.')
                                ->danger()->send();
                            return;
                        }

                        // Alte Rollen entfernen, neue zuweisen
                        $record->syncRoles([$data['role']]);

                        // Benutzertyp anpassen
                        $newType = match ($data['role']) {
                            'partner_owner', 'partner_manager' => 'partner',
                            'tax_advisor'                      => 'tax_advisor',
                            default                            => 'employee',
                        };
                        $record->update(['type' => $newType]);

                        app()[PermissionRegistrar::class]->forgetCachedPermissions();

                        Notification::make()
                            ->title('Rolle geändert')
                            ->body($record->name . ' hat jetzt die Rolle: ' . $data['role'])
                            ->success()->send();
                    }),

                // ── Aktivieren / Deaktivieren ──────────────────────────────
                Action::make('toggle_active')
                    ->label(fn (User $record): string => $record->is_active ? 'Deaktivieren' : 'Aktivieren')
                    ->icon(fn (User $record): string => $record->is_active ? 'heroicon-o-lock-closed' : 'heroicon-o-lock-open')
                    ->color(fn (User $record): string => $record->is_active ? 'danger' : 'success')
                    ->requiresConfirmation()
                    ->modalHeading(fn (User $record): string => $record->is_active ? 'Benutzer deaktivieren?' : 'Benutzer aktivieren?')
                    ->modalDescription(fn (User $record): string => $record->is_active
                        ? $record->name . ' kann sich danach nicht mehr einloggen.'
                        : $record->name . ' erhält wieder Zugang zum System.'
                    )
                    ->action(function (User $record): void {
                        $record->update(['is_active' => ! $record->is_active]);

                        Notification::make()
                            ->title($record->is_active ? 'Benutzer aktiviert' : 'Benutzer deaktiviert')
                            ->success()->send();
                    }),
            ]);
    }

    // ── Pages ────────────────────────────────────────────────────────────────

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTeamMembers::route('/'),
        ];
    }
}
