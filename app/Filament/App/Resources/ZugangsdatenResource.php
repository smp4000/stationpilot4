<?php
namespace App\Filament\App\Resources;

use App\Filament\App\Resources\ZugangsdatenResource\Pages;
use App\Models\Employee;
use App\Models\StationCredential;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;

class ZugangsdatenResource extends Resource
{
    protected static ?string $model = StationCredential::class;

    public static function shouldRegisterNavigation(): bool { return false; }

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-lock-closed';

    protected static ?string $navigationLabel = 'Zugangsdaten';

    protected static \UnitEnum|string|null $navigationGroup = 'Personal';

    protected static ?string $modelLabel = 'Zugangsdaten';

    protected static ?string $pluralModelLabel = 'Zugangsdaten';

    protected static ?int $navigationSort = 21;

    public static function canAccess(): bool
    {
        $user = auth()->user();
        return $user && ($user->isPartner() || $user->isTaxAdvisor());
    }

    public static function form(Schema $schema): Schema
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

            Select::make('type')
                ->label('Art / Gerät')
                ->options(StationCredential::typeOptions())
                ->required(),

            TextInput::make('label')
                ->label('Bezeichnung')
                ->required()
                ->maxLength(255)
                ->placeholder('z.B. Kasse 1, EC-Terminal Eingang'),

            Select::make('stations')
                ->label('Tankstellen (optional – leer = alle)')
                ->relationship(
                    'stations',
                    'name',
                    fn ($query) => $query
                        ->where('gas_stations.tenant_id', auth()->user()->tenant_id)
                        ->where('gas_stations.is_active', true)
                )
                ->multiple()
                ->searchable()
                ->preload()
                ->nullable()
                ->placeholder('Gilt für alle Tankstellen')
                ->columnSpan(2),

            TextInput::make('username')
                ->label('Benutzername / Login')
                ->maxLength(255)
                ->nullable(),

            TextInput::make('credential_value')
                ->label('Passwort')
                ->password()
                ->revealable()
                ->nullable()
                ->maxLength(500),

            TextInput::make('pin_value')
                ->label('PIN')
                ->password()
                ->revealable()
                ->nullable()
                ->maxLength(50),

            Toggle::make('is_active')
                ->label('Aktiv')
                ->default(true),

            Textarea::make('notes')
                ->label('Notizen')
                ->rows(3)
                ->nullable()
                ->columnSpan(2),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('employee.first_name')
                    ->label('Mitarbeiter')
                    ->formatStateUsing(fn ($record) => $record->employee?->first_name . ' ' . $record->employee?->last_name)
                    ->sortable()
                    ->searchable(),

                TextColumn::make('type')
                    ->label('Art')
                    ->badge()
                    ->color('info'),

                TextColumn::make('label')
                    ->label('Bezeichnung')
                    ->searchable()
                    ->weight('semibold'),

                TextColumn::make('stations.name')
                    ->label('Tankstellen')
                    ->listWithLineBreaks()
                    ->limitList(2)
                    ->placeholder('Alle'),

                TextColumn::make('username')
                    ->label('Benutzername')
                    ->placeholder('—'),

                TextColumn::make('credential_value')
                    ->label('Passwort')
                    ->formatStateUsing(fn ($state) => $state ? '••••••••' : '—'),

                TextColumn::make('pin_value')
                    ->label('PIN')
                    ->formatStateUsing(fn ($state) => $state ? '••••' : '—'),

                IconColumn::make('is_active')
                    ->label('Aktiv')
                    ->boolean()
                    ->alignCenter(),
            ])
            ->filters([
                SelectFilter::make('employee_id')
                    ->label('Mitarbeiter')
                    ->options(function () {
                        $tenantId = auth()->user()->tenant_id;
                        return Employee::where('tenant_id', $tenantId)
                            ->get()
                            ->mapWithKeys(fn ($e) => [$e->id => $e->first_name . ' ' . $e->last_name]);
                    })
                    ->searchable(),
                SelectFilter::make('type')
                    ->label('Art')
                    ->options(StationCredential::typeOptions()),
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
            ->defaultSort('employee_id');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListZugangsdaten::route('/'),
            'create' => Pages\CreateZugangsdaten::route('/create'),
            'edit'   => Pages\EditZugangsdaten::route('/{record}/edit'),
        ];
    }
}
