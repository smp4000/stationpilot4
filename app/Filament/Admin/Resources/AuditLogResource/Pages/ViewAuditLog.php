<?php

namespace App\Filament\Admin\Resources\AuditLogResource\Pages;

use App\Filament\Admin\Resources\AuditLogResource;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ViewAuditLog extends ViewRecord
{
    protected static string $resource = AuditLogResource::class;

    // Kein Edit-Button — Logs unveränderlich
    protected function getHeaderActions(): array
    {
        return [];
    }

    public function infolist(Schema $schema): Schema
    {
        $canViewDetails = auth()->user()?->can('admin.audit-log.view-details');

        return $schema->components([
            Section::make('Basis-Informationen')->schema([
                TextEntry::make('created_at')
                    ->label('Zeitpunkt')
                    ->dateTime('d.m.Y H:i:s'),

                TextEntry::make('action')
                    ->label('Aktion')
                    ->badge(),

                TextEntry::make('user.name')
                    ->label('Benutzer')
                    ->getStateUsing(fn($record) => $record->user?->name ?? '—'),

                TextEntry::make('user_type')
                    ->label('Benutzertyp')
                    ->placeholder('—'),

                TextEntry::make('auditable_type_short')
                    ->label('Objekt-Typ')
                    ->getStateUsing(fn($record) => $record->auditable_type_short),

                TextEntry::make('auditable_id')
                    ->label('Objekt-ID')
                    ->placeholder('—'),

                TextEntry::make('ip_address')
                    ->label('IP-Adresse')
                    ->placeholder('—')
                    ->copyable(),

                TextEntry::make('tenant.name')
                    ->label('Mandant')
                    ->placeholder('Global (Super-Admin)'),
            ])->columns(2),

            // Sensible Werte — nur für Level 3 Admin sichtbar
            Section::make('Datenwerte (DSGVO-geschützt)')
                ->description('Nur für autorisierte Administratoren sichtbar')
                ->schema([
                    TextEntry::make('old_values')
                        ->label('Alte Werte')
                        ->placeholder('—')
                        ->getStateUsing(fn($record) => $record->old_values
                            ? json_encode(json_decode($record->old_values), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
                            : '—'
                        )
                        ->fontFamily('mono'),

                    TextEntry::make('new_values')
                        ->label('Neue Werte')
                        ->placeholder('—')
                        ->getStateUsing(fn($record) => $record->new_values
                            ? json_encode(json_decode($record->new_values), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
                            : '—'
                        )
                        ->fontFamily('mono'),

                    TextEntry::make('reason')
                        ->label('Begründung')
                        ->placeholder('—'),
                ])
                ->visible($canViewDetails)
                ->columns(2),
        ]);
    }
}
