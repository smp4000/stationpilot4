<?php
namespace App\Filament\App\Resources;

use App\Filament\App\Resources\CredentialTypeResource\Pages;
use App\Models\CredentialType;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CredentialTypeResource extends Resource
{
    protected static ?string $model = CredentialType::class;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-list-bullet';

    protected static ?string $navigationLabel = 'Zugangsdaten-Typen';

    protected static \UnitEnum|string|null $navigationGroup = 'Einstellungen';

    protected static ?string $modelLabel = 'Zugangsdaten-Typ';

    protected static ?string $pluralModelLabel = 'Zugangsdaten-Typen';

    protected static ?int $navigationSort = 10;

    public static function canAccess(): bool
    {
        return auth()->user()?->can('partner.settings.view') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('partner.settings.edit') ?? false;
    }

    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return auth()->user()?->can('partner.settings.edit') ?? false;
    }

    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return auth()->user()?->can('partner.settings.edit') ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('icon')
                ->label('Icon (Emoji)')
                ->default('🔑')
                ->required()
                ->maxLength(10)
                ->helperText('Ein Emoji als Symbol, z.B. 🖥️ 💳 📟 🔔 🔒 ⛽'),

            TextInput::make('name')
                ->label('Bezeichnung')
                ->required()
                ->maxLength(100)
                ->placeholder('z.B. Kasse, EC-Terminal, Alarmanlage'),

            TextInput::make('sort_order')
                ->label('Reihenfolge')
                ->numeric()
                ->default(0)
                ->helperText('Kleinere Zahl = weiter oben in der Liste'),

            Toggle::make('is_active')
                ->label('Aktiv')
                ->default(true),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('icon')
                    ->label('')
                    ->width('40px'),

                TextColumn::make('name')
                    ->label('Bezeichnung')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold'),

                TextColumn::make('sort_order')
                    ->label('Reihenfolge')
                    ->sortable()
                    ->alignCenter(),

                IconColumn::make('is_active')
                    ->label('Aktiv')
                    ->boolean()
                    ->alignCenter(),
            ])
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListCredentialTypes::route('/'),
            'create' => Pages\CreateCredentialType::route('/create'),
            'edit'   => Pages\EditCredentialType::route('/{record}/edit'),
        ];
    }
}
