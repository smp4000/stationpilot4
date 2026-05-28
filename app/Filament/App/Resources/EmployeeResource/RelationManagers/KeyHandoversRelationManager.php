<?php
namespace App\Filament\App\Resources\EmployeeResource\RelationManagers;

use App\Models\Key;
use App\Models\Station;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Actions\Action;
use Filament\Actions\CreateAction as HeaderCreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class KeyHandoversRelationManager extends RelationManager
{
    protected static string $relationship = 'keyHandovers';

    protected static ?string $title = 'Schlüssel & Zugangsmedien';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('key_id')
                ->label('Schlüssel / Zugangsmedium')
                ->options(function () {
                    $tenantId = auth()->user()->tenant_id;
                    return Key::with('station')
                        ->where('tenant_id', $tenantId)
                        ->where('is_active', true)
                        ->get()
                        ->mapWithKeys(fn ($k) => [$k->id => $k->select_label]);
                })
                ->searchable()
                ->required()
                ->columnSpan(2)
                // ── Neuen Schlüssel direkt anlegen ──────────────────────
                ->createOptionForm([
                    Select::make('type')
                        ->label('Art')
                        ->options(Key::typeOptions())
                        ->default('schluessel')
                        ->required(),

                    TextInput::make('name')
                        ->label('Bezeichnung')
                        ->required()
                        ->maxLength(255)
                        ->placeholder('z.B. Haupteingang, Alarmchip'),

                    TextInput::make('key_number')
                        ->label('Nummer / Seriennummer')
                        ->maxLength(100),

                    Select::make('station_id')
                        ->label('Tankstelle (optional)')
                        ->options(function () {
                            $user = auth()->user();
                            return Station::where('tenant_id', $user->tenant_id)
                                ->where('is_active', true)
                                ->pluck('name', 'id');
                        })
                        ->nullable()
                        ->placeholder('Alle Tankstellen'),

                    TextInput::make('copies_total')
                        ->label('Anzahl')
                        ->numeric()
                        ->default(1),
                ])
                ->createOptionUsing(function (array $data) {
                    $data['tenant_id'] = auth()->user()->tenant_id;
                    return Key::create($data)->id;
                })
                ->createOptionModalHeading('Neuen Schlüssel / Chip anlegen'),

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
                TextColumn::make('key.type')
                    ->label('Art')
                    ->formatStateUsing(fn ($state) => Key::typeLabel($state))
                    ->badge()
                    ->color(fn ($state) => match($state) {
                        'schluessel' => 'info',
                        'chip'       => 'warning',
                        'karte'      => 'success',
                        'code'       => 'gray',
                        default      => 'gray',
                    }),

                TextColumn::make('key.name')
                    ->label('Bezeichnung')
                    ->weight('semibold'),

                TextColumn::make('key.key_number')
                    ->label('Nummer')
                    ->placeholder('—')
                    ->fontFamily('mono'),

                TextColumn::make('handed_out_at')
                    ->label('Ausgegeben')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),

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
                    ->modalDescription('Schlüssel / Chip als zurückgegeben markieren?')
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
