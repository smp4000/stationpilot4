<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\AuditLogResource\Pages;
use App\Models\AuditLog;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AuditLogResource extends Resource
{
    protected static ?string $model = AuditLog::class;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-shield-check';

    protected static \UnitEnum|string|null $navigationGroup = 'DSGVO & Audit';

    protected static ?int $navigationSort = 1;

    protected static ?string $label = 'Audit-Log';

    protected static ?string $pluralLabel = 'Audit-Logs';

    // ─────────────────────────────────────────────
    // Zugriffskontrolle — nur lesen
    // ─────────────────────────────────────────────

    public static function canAccess(): bool
    {
        return auth()->user()?->can('admin.audit-log.view') ?? false;
    }

    public static function canCreate(): bool   { return false; }
    public static function canEdit($r): bool   { return false; }
    public static function canDelete($r): bool { return false; }

    // ─────────────────────────────────────────────
    // Formular — nur für View-Page (read-only)
    // ─────────────────────────────────────────────

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    // ─────────────────────────────────────────────
    // Tabelle
    // ─────────────────────────────────────────────

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->label('Zeitpunkt')
                    ->dateTime('d.m.Y H:i:s')
                    ->sortable(),

                TextColumn::make('action')
                    ->label('Aktion')
                    ->badge()
                    ->color(fn(string $state) => match ($state) {
                        'login'        => 'info',
                        'logout'       => 'gray',
                        'login_failed' => 'danger',
                        'deleted'      => 'danger',
                        'created'      => 'success',
                        'updated'      => 'warning',
                        default        => 'gray',
                    })
                    ->formatStateUsing(fn($state) => match ($state) {
                        'login'        => 'Login',
                        'logout'       => 'Logout',
                        'login_failed' => 'Login fehlgeschlagen',
                        'created'      => 'Erstellt',
                        'updated'      => 'Bearbeitet',
                        'deleted'      => 'Gelöscht',
                        default        => $state,
                    }),

                TextColumn::make('user.name')
                    ->label('Benutzer')
                    ->placeholder('—')
                    ->getStateUsing(fn($record) => $record->user?->name ?? '—')
                    ->searchable(),

                TextColumn::make('auditable_type_short')
                    ->label('Objekt')
                    ->getStateUsing(fn($record) => $record->auditable_type_short)
                    ->placeholder('—'),

                TextColumn::make('tenant.name')
                    ->label('Mandant')
                    ->placeholder('Global')
                    ->sortable(),

                TextColumn::make('ip_address')
                    ->label('IP-Adresse')
                    ->placeholder('—')
                    ->copyable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('action')
                    ->label('Aktion')
                    ->options([
                        'login'        => 'Login',
                        'logout'       => 'Logout',
                        'login_failed' => 'Login fehlgeschlagen',
                        'created'      => 'Erstellt',
                        'updated'      => 'Bearbeitet',
                        'deleted'      => 'Gelöscht',
                    ]),

                SelectFilter::make('tenant_id')
                    ->label('Mandant')
                    ->relationship('tenant', 'name')
                    ->searchable()
                    ->preload(),

                Filter::make('created_at')
                    ->label('Zeitraum')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('from')
                            ->label('Von')
                            ->displayFormat('d.m.Y'),
                        \Filament\Forms\Components\DatePicker::make('until')
                            ->label('Bis')
                            ->displayFormat('d.m.Y'),
                    ])
                    ->query(function (Builder $query, array $data) {
                        return $query
                            ->when($data['from'],  fn($q, $d) => $q->whereDate('created_at', '>=', $d))
                            ->when($data['until'], fn($q, $d) => $q->whereDate('created_at', '<=', $d));
                    }),
            ])
            ->defaultSort('created_at', 'desc')
            ->poll('60s');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAuditLogs::route('/'),
            'view'  => Pages\ViewAuditLog::route('/{record}'),
        ];
    }
}
