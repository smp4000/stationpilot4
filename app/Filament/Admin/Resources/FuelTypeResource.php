<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\FuelTypeResource\Pages;
use App\Models\FuelType;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\BooleanColumn;
use Filament\Tables\Columns\ColorColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class FuelTypeResource extends Resource
{
    protected static ?string $model = FuelType::class;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-fire';

    protected static \UnitEnum|string|null $navigationGroup = 'Stammdaten';

    protected static ?int $navigationSort = 10;

    protected static ?string $label = 'Kraftstoffsorte';

    protected static ?string $pluralLabel = 'Kraftstoffsorten';

    // ─────────────────────────────────────────────
    // Formular
    // ─────────────────────────────────────────────

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Kraftstoffsorte')
                ->columns(2)
                ->schema([
                    TextInput::make('name')
                        ->label('Name')
                        ->required()
                        ->maxLength(100)
                        ->live(onBlur: true)
                        ->afterStateUpdated(function ($state, callable $set) {
                            $set('slug', Str::slug($state));
                        }),

                    TextInput::make('slug')
                        ->label('Slug (Schlüssel)')
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->maxLength(80)
                        ->helperText('Wird automatisch aus dem Namen generiert. Einmal gesetzt nicht mehr ändern.'),

                    Select::make('category')
                        ->label('Kategorie')
                        ->options(FuelType::categoryOptions())
                        ->required(),

                    TextInput::make('brand')
                        ->label('Marke')
                        ->nullable()
                        ->placeholder('z. B. Aral, Shell — leer = markenunabhängig'),

                    ColorPicker::make('color')
                        ->label('Farbe')
                        ->nullable(),

                    TextInput::make('sort_order')
                        ->label('Reihenfolge')
                        ->numeric()
                        ->default(0),

                    Textarea::make('description')
                        ->label('Beschreibung')
                        ->rows(2)
                        ->nullable()
                        ->columnSpanFull(),

                    Toggle::make('is_active')
                        ->label('Aktiv')
                        ->default(true),
                ]),
        ]);
    }

    // ─────────────────────────────────────────────
    // Tabelle
    // ─────────────────────────────────────────────

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ColorColumn::make('color')
                    ->label('')
                    ->width('2rem'),

                TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),

                TextColumn::make('slug')
                    ->label('Slug')
                    ->searchable()
                    ->fontFamily('mono')
                    ->color('gray'),

                TextColumn::make('category')
                    ->label('Kategorie')
                    ->badge()
                    ->formatStateUsing(fn($state) => FuelType::categoryOptions()[$state] ?? $state)
                    ->color(fn($state) => match($state) {
                        'standard'   => 'success',
                        'premium'    => 'warning',
                        'alternativ' => 'info',
                        'elektro'    => 'primary',
                        default      => 'gray',
                    })
                    ->sortable(),

                TextColumn::make('brand')
                    ->label('Marke')
                    ->placeholder('—')
                    ->sortable(),

                BooleanColumn::make('is_active')
                    ->label('Aktiv')
                    ->sortable(),

                TextColumn::make('sort_order')
                    ->label('#')
                    ->sortable()
                    ->alignRight(),
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->filters([
                SelectFilter::make('category')
                    ->label('Kategorie')
                    ->options(FuelType::categoryOptions()),

                SelectFilter::make('is_active')
                    ->label('Status')
                    ->options([1 => 'Aktiv', 0 => 'Inaktiv']),
            ])
            ->actions([
                EditAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    // ─────────────────────────────────────────────
    // Seiten
    // ─────────────────────────────────────────────

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListFuelTypes::route('/'),
            'create' => Pages\CreateFuelType::route('/create'),
            'edit'   => Pages\EditFuelType::route('/{record}/edit'),
        ];
    }
}
