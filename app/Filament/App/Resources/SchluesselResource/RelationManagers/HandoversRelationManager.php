<?php
namespace App\Filament\App\Resources\SchluesselResource\RelationManagers;

use App\Models\Employee;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Actions\Action;
use Filament\Actions\CreateAction as HeaderCreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class HandoversRelationManager extends RelationManager
{
    protected static string $relationship = 'handovers';

    protected static ?string $title = 'Übergaben';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('employee_id')
                ->label('Mitarbeiter')
                ->options(function () {
                    $tenantId = auth()->user()->tenant_id;
                    return Employee::where('tenant_id', $tenantId)
                        ->where('status', 'active')
                        ->get()
                        ->mapWithKeys(fn ($e) => [$e->id => $e->first_name . ' ' . $e->last_name]);
                })
                ->searchable()
                ->required()
                ->columnSpan(2),

            DateTimePicker::make('handed_out_at')
                ->label('Ausgegeben am')
                ->required()
                ->default(now())
                ->native(false),

            DateTimePicker::make('returned_at')
                ->label('Zurückgegeben am')
                ->nullable()
                ->native(false),

            Textarea::make('notes')
                ->label('Notizen')
                ->rows(2)
                ->columnSpan(2),
        ])->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('employee.first_name')
                    ->label('Mitarbeiter')
                    ->formatStateUsing(fn ($record) => $record->employee->first_name . ' ' . $record->employee->last_name)
                    ->searchable(),

                TextColumn::make('handed_out_at')
                    ->label('Ausgegeben am')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),

                TextColumn::make('handedOutBy.first_name')
                    ->label('Ausgegeben von')
                    ->formatStateUsing(fn ($record) => $record->handedOutBy?->first_name . ' ' . $record->handedOutBy?->last_name)
                    ->placeholder('—'),

                TextColumn::make('returned_at')
                    ->label('Zurückgegeben')
                    ->dateTime('d.m.Y H:i')
                    ->placeholder('—')
                    ->color(fn ($state) => $state ? 'success' : 'warning'),

                TextColumn::make('employee_confirmed_at')
                    ->label('Empfang ✍️')
                    ->formatStateUsing(fn ($record) => $record->employee_confirmed_at
                        ? '✓ ' . $record->employee_confirmed_at->format('d.m.Y')
                        : '—')
                    ->color(fn ($record) => $record->employee_confirmed_at ? 'success' : 'warning')
                    ->tooltip(fn ($record) => $record->receipt_signature ? 'Unterschrift vorhanden' : null),

                TextColumn::make('employee_returned_at')
                    ->label('Rückgabe ✍️')
                    ->formatStateUsing(fn ($record) => $record->employee_returned_at
                        ? '✓ ' . $record->employee_returned_at->format('d.m.Y')
                        : '—')
                    ->color(fn ($record) => $record->employee_returned_at ? 'success' : 'gray')
                    ->tooltip(fn ($record) => $record->return_signature ? 'Unterschrift vorhanden' : null),
            ])
            ->headerActions([
                HeaderCreateAction::make()
                    ->label('Ausgeben')
                    ->modalHeading('Schlüssel / Chip ausgeben')
                    ->mutateFormDataUsing(function (array $data) {
                        $data['handed_out_by'] = auth()->id();
                        if (empty($data['handed_out_at'])) {
                            $data['handed_out_at'] = now();
                        }
                        return $data;
                    }),
            ])
            ->actions([
                Action::make('return')
                    ->label('Rückgabe vermerken')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('success')
                    ->visible(fn ($record) => $record->returned_at === null && $record->employee_returned_at === null)
                    ->requiresConfirmation()
                    ->modalHeading('Rückgabe vermerken')
                    ->modalDescription('Als zurückgegeben markieren?')
                    ->action(fn ($record) => $record->update([
                        'returned_at' => now(),
                        'returned_to' => auth()->id(),
                    ])),
                EditAction::make()->modalHeading('Übergabe bearbeiten'),
                DeleteAction::make(),
            ])
            ->defaultSort('handed_out_at', 'desc');
    }
}
