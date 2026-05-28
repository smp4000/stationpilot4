<?php
namespace App\Filament\App\Resources\EmployeeResource\RelationManagers;

use App\Models\StationCredential;
use Filament\Actions\CreateAction as HeaderCreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Table;

class CredentialsRelationManager extends RelationManager
{
    protected static string $relationship = 'credentials';

    protected static ?string $title = 'Zugangsdaten';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('type')
                ->label('Art / Gerät')
                ->options(StationCredential::typeOptions())
                ->required(),

            TextInput::make('label')
                ->label('Bezeichnung')
                ->required()
                ->maxLength(255)
                ->placeholder('z.B. Kasse 1, EC-Terminal'),

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
                ->rows(2)
                ->nullable()
                ->columnSpan(2),
        ])->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('type')
                    ->label('Art')
                    ->badge()
                    ->color('info'),

                TextColumn::make('label')
                    ->label('Bezeichnung')
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
            ->headerActions([
                HeaderCreateAction::make()
                    ->label('Zugangsdaten anlegen')
                    ->mutateFormDataUsing(function (array $data) {
                        $data['tenant_id'] = auth()->user()->tenant_id;
                        return $data;
                    }),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->defaultSort('type');
    }
}
