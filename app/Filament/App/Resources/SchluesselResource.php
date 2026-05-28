<?php
namespace App\Filament\App\Resources;

use App\Filament\App\Resources\SchluesselResource\Pages;
use App\Filament\App\Resources\SchluesselResource\RelationManagers;
use App\Models\Key;
use App\Models\Station;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\EditAction;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\TernaryFilter;

class SchluesselResource extends Resource
{
    protected static ?string $model = Key::class;

    public static function shouldRegisterNavigation(): bool { return false; }

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-key';

    protected static ?string $navigationLabel = 'Schlüsselbuch';

    protected static \UnitEnum|string|null $navigationGroup = 'Personal';

    protected static ?string $modelLabel = 'Schlüssel';

    protected static ?string $pluralModelLabel = 'Schlüsselbuch';

    protected static ?int $navigationSort = 20;

    public static function canAccess(): bool
    {
        $user = auth()->user();
        return $user && ($user->isPartner() || $user->isTaxAdvisor());
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('type')
                ->label('Art')
                ->options(Key::typeOptions())
                ->default('schluessel')
                ->required(),

            TextInput::make('name')
                ->label('Bezeichnung')
                ->required()
                ->maxLength(255)
                ->placeholder('z.B. Haupteingang, Alarmchip Büro'),

            TextInput::make('key_number')
                ->label('Nummer / Seriennummer')
                ->maxLength(100)
                ->placeholder('z.B. K-001'),

            TextInput::make('copies_total')
                ->label('Anzahl Kopien')
                ->numeric()
                ->default(1)
                ->minValue(1),

            Select::make('station_id')
                ->label('Tankstelle')
                ->options(function () {
                    $user = auth()->user();
                    return Station::where('tenant_id', $user->tenant_id)
                        ->where('is_active', true)
                        ->pluck('name', 'id');
                })
                ->searchable()
                ->nullable()
                ->placeholder('Alle Tankstellen'),

            Toggle::make('is_active')
                ->label('Aktiv')
                ->default(true)
                ->columnSpan(2),

            Textarea::make('description')
                ->label('Beschreibung / Hinweise')
                ->rows(3)
                ->columnSpan(2),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('type')
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

                TextColumn::make('name')
                    ->label('Bezeichnung')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold'),

                TextColumn::make('key_number')
                    ->label('Nummer')
                    ->placeholder('—')
                    ->fontFamily('mono'),

                TextColumn::make('station.name')
                    ->label('Tankstelle')
                    ->placeholder('Alle')
                    ->sortable(),

                TextColumn::make('copies_total')
                    ->label('Kopien')
                    ->alignCenter(),

                TextColumn::make('activeHandovers.employee.first_name')
                    ->label('Vergeben an')
                    ->formatStateUsing(fn ($record) => $record->activeHandovers->map(
                        fn ($h) => $h->employee?->first_name . ' ' . $h->employee?->last_name
                    )->filter()->join(', '))
                    ->placeholder('—')
                    ->searchable(query: fn ($query, $search) => $query->whereHas(
                        'activeHandovers.employee',
                        fn ($q) => $q->where('first_name', 'like', "%{$search}%")
                                     ->orWhere('last_name',  'like', "%{$search}%")
                    )),

                TextColumn::make('active_handovers_count')
                    ->label('Anzahl')
                    ->counts('activeHandovers')
                    ->alignCenter()
                    ->badge()
                    ->color(fn ($state) => $state > 0 ? 'warning' : 'success'),

                IconColumn::make('is_active')
                    ->label('Aktiv')
                    ->boolean()
                    ->alignCenter(),
            ])
            ->filters([
                TernaryFilter::make('is_active')->label('Aktiv'),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('name');
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\HandoversRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListSchluessel::route('/'),
            'create' => Pages\CreateSchluessel::route('/create'),
            'edit'   => Pages\EditSchluessel::route('/{record}/edit'),
        ];
    }
}
