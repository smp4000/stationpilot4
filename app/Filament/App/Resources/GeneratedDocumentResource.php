<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\GeneratedDocumentResource\Pages;
use App\Models\Employee;
use App\Models\GeneratedDocument;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class GeneratedDocumentResource extends Resource
{
    protected static ?string $model = GeneratedDocument::class;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-document-check';
    protected static \UnitEnum|string|null   $navigationGroup = 'Personal';
    protected static ?string $navigationLabel = 'Versendete Dokumente';
    protected static ?string $modelLabel      = 'Dokument';
    protected static ?string $pluralModelLabel = 'Versendete Dokumente';
    protected static ?int    $navigationSort  = 5;

    public static function canAccess(): bool
    {
        return auth()->user()?->can('partner.documents.list') ?? false;
    }

    public static function canCreate(): bool { return false; }
    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool { return false; }
    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return auth()->user()?->can('partner.documents.delete') ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    // ─── Table ───────────────────────────────────────────────────────────────

    public static function table(Table $table): Table
    {
        $subTypeLabels = [
            'datenschutz'     => 'DSGVO-Einwilligung',
            'betriebsordnung' => 'Betriebsordnung',
            'nda'             => 'Geheimhaltung (NDA)',
            'uebergabe'       => 'Schlüsselübergabe',
        ];

        return $table
            ->columns([

                TextColumn::make('employee_name')
                    ->label('Mitarbeiter')
                    ->getStateUsing(function (GeneratedDocument $record): string {
                        $emp = Employee::find($record->related_id);
                        return $emp ? $emp->fullName() : '—';
                    })
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->whereHasMorph(
                            'related',
                            [Employee::class],
                            fn ($q) => $q->where('first_name', 'like', "%$search%")
                                        ->orWhere('last_name', 'like', "%$search%")
                        );
                    })
                    ->sortable(query: fn (Builder $q, string $dir): Builder => $q->orderBy('generated_at', $dir)),

                TextColumn::make('template.name')
                    ->label('Vorlage')
                    ->searchable()
                    ->weight('semibold'),

                TextColumn::make('template.sub_type')
                    ->label('Typ')
                    ->badge()
                    ->color('info')
                    ->formatStateUsing(function (?string $state) use ($subTypeLabels): string {
                        return $subTypeLabels[$state ?? ''] ?? ($state ?? '—');
                    }),

                TextColumn::make('generated_at')
                    ->label('Generiert am')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),

                IconColumn::make('signed_at')
                    ->label('Unterschrieben')
                    ->boolean()
                    ->getStateUsing(fn (GeneratedDocument $record): bool => (bool) $record->signed_at)
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-clock')
                    ->trueColor('success')
                    ->falseColor('warning'),

                TextColumn::make('signed_at')
                    ->label('Unterschrieben am')
                    ->dateTime('d.m.Y H:i')
                    ->placeholder('Ausstehend')
                    ->sortable(),

            ])
            ->filters([
                SelectFilter::make('signed')
                    ->label('Status')
                    ->options([
                        'signed'   => 'Unterschrieben',
                        'unsigned' => 'Ausstehend',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return match ($data['value'] ?? null) {
                            'signed'   => $query->whereNotNull('signed_at'),
                            'unsigned' => $query->whereNull('signed_at'),
                            default    => $query,
                        };
                    }),
            ])
            ->actions([

                Action::make('copy_link')
                    ->label('Link kopieren')
                    ->icon('heroicon-o-link')
                    ->color('gray')
                    ->visible(fn (GeneratedDocument $record): bool => !$record->signed_at && $record->sign_token !== null)
                    ->extraAttributes(fn (GeneratedDocument $record): array => [
                        'x-on:click' => 'navigator.clipboard.writeText("' . route('document.sign', $record->sign_token) . '"); $dispatch("filament-notification", {notification: {title: "Link kopiert!", color: "success"}})',
                    ]),

                Action::make('download')
                    ->label('PDF')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('gray')
                    ->url(fn (GeneratedDocument $record): string => route('pdf.document.download', $record->id))
                    ->openUrlInNewTab()
                    ->visible(fn (GeneratedDocument $record): bool => (bool) $record->pdf_path),

                \Filament\Actions\DeleteAction::make()
                    ->requiresConfirmation(),

            ])
            ->defaultSort('generated_at', 'desc')
            ->defaultPaginationPageOption(25);
    }

    // ─── Query ───────────────────────────────────────────────────────────────

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('tenant_id', session('tenant_id'))
            ->with(['template', 'generatedBy']);
    }

    // ─── Pages ───────────────────────────────────────────────────────────────

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListGeneratedDocuments::route('/'),
        ];
    }
}
