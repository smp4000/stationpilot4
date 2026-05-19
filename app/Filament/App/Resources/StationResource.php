<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\StationResource\Pages;
use App\Jobs\EnrichStationJob;
use App\Models\Station;
use App\Services\BenzinpreisService;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Schemas\Components\Actions;
use Filament\Actions\Action;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\BooleanColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\HtmlString;

class StationResource extends Resource
{
    protected static ?string $model = Station::class;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-building-storefront';

    protected static \UnitEnum|string|null $navigationGroup = 'Tankstellen';

    protected static ?int $navigationSort = 1;

    protected static ?string $label = 'Tankstelle';

    protected static ?string $pluralLabel = 'Tankstellen';

    // ─────────────────────────────────────────────
    // Zugriffskontrolle
    // ─────────────────────────────────────────────

    public static function canAccess(): bool   { return auth()->user()?->can('partner.stations.list') ?? false; }
    public static function canCreate(): bool   { return auth()->user()?->can('partner.stations.create') ?? false; }
    public static function canEdit($r): bool   { return auth()->user()?->can('partner.stations.edit') ?? false; }
    public static function canDelete($r): bool { return auth()->user()?->can('partner.stations.delete') ?? false; }

    // ─────────────────────────────────────────────
    // Formular
    // ─────────────────────────────────────────────

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make('Station')->tabs([

                // ── Tab 1: Stationssuche (nur beim Erstellen) ─────
                Tab::make('Stationssuche')
                    ->icon('heroicon-o-magnifying-glass')
                    ->hidden(fn ($record) => $record !== null)
                    ->schema([
                        Section::make('Tankstelle per PLZ suchen')
                            ->icon('heroicon-o-map-pin')
                            ->description('PLZ eingeben → Umkreissuche auf benzinpreis-aktuell.de → Station auswählen → Koordinaten & Daten übernehmen.')
                            ->schema([
                                TextInput::make('_search_zip')
                                    ->label('PLZ')
                                    ->placeholder('z. B. 36039')
                                    ->maxLength(5)
                                    ->live()
                                    ->dehydrated(false)
                                    ->helperText('5-stellige PLZ eingeben — Suche startet automatisch'),

                                Actions::make([
                                    Action::make('search_bp')
                                        ->label('Suchen')
                                        ->icon('heroicon-o-magnifying-glass')
                                        ->color('primary')
                                        ->action(function (Get $get, Set $set) {
                                            $zip = trim($get('_search_zip') ?? '');

                                            if (! preg_match('/^\d{5}$/', $zip)) {
                                                Notification::make()
                                                    ->title('Ungültige PLZ')
                                                    ->body('Bitte eine gültige 5-stellige PLZ eingeben.')
                                                    ->warning()
                                                    ->send();
                                                return;
                                            }

                                            // Umkreis aus Settings (Standard 20 km — später konfigurierbar)
                                            $radius  = 20;
                                            $results = app(BenzinpreisService::class)->searchByPlz($zip, $radius);

                                            if (empty($results)) {
                                                Notification::make()
                                                    ->title('Keine Tankstellen gefunden')
                                                    ->body("Für PLZ {$zip} wurden im {$radius}-km-Umkreis keine Tankstellen gefunden.")
                                                    ->warning()
                                                    ->send();
                                                return;
                                            }

                                            // Dropdown-Optionen: Schlüssel = "hash:slug"
                                            $options = collect($results)
                                                ->mapWithKeys(fn($s) => [
                                                    $s['hash'] . ':' . $s['slug'] => $s['name']
                                                        . ' · ' . $s['street']
                                                        . ', ' . $s['city'],
                                                ])
                                                ->toArray();

                                            $set('_search_results', $options);

                                            Notification::make()
                                                ->title(count($results) . ' Tankstellen in ' . $zip . ' (Umkreis ' . $radius . ' km)')
                                                ->body(count($results) . ' Ergebnisse geladen — bitte Tankstelle auswählen.')
                                                ->info()
                                                ->send();
                                        }),
                                ])->columnSpanFull(),

                                Select::make('_selected_station')
                                    ->label('Gefundene Tankstellen')
                                    ->options(fn(Get $get) => $get('_search_results') ?? [])
                                    ->live()
                                    ->searchable()
                                    ->dehydrated(false)
                                    ->placeholder('Station auswählen oder überspringen…')
                                    ->columnSpanFull(),

                                Actions::make([
                                    Action::make('import_bp')
                                        ->label('Koordinaten & Daten übernehmen')
                                        ->icon('heroicon-o-arrow-down-tray')
                                        ->color('success')
                                        ->visible(fn(Get $get) => $get('_selected_station') !== null)
                                        ->action(function (Get $get, Set $set) {
                                            $key = $get('_selected_station');
                                            if (! $key || ! str_contains($key, ':')) {
                                                return;
                                            }

                                            [$hash, $slug] = explode(':', $key, 2);

                                            $details = app(BenzinpreisService::class)->fetchStationDetails($hash, $slug);

                                            if (! $details) {
                                                Notification::make()
                                                    ->title('Fehler beim Laden der Station')
                                                    ->body('Die Detailseite konnte nicht geladen werden.')
                                                    ->danger()
                                                    ->send();
                                                return;
                                            }

                                            // Stammdaten
                                            if ($details['name'])  $set('name', $details['name']);
                                            if ($details['brand']) $set('brand', $details['brand']);

                                            // Adresse
                                            if ($details['street'])       $set('street', $details['street']);
                                            if ($details['house_number']) $set('house_number', $details['house_number']);
                                            if ($details['zip'])          $set('zip', $details['zip']);
                                            if ($details['city'])         $set('city', $details['city']);

                                            // Koordinaten (in Tab "Adresse & Karte" ausgegraut anzeigen)
                                            if ($details['lat']) $set('lat', $details['lat']);
                                            if ($details['lng']) $set('lng', $details['lng']);

                                            $lat = $details['lat'] ?? '—';
                                            $lng = $details['lng'] ?? '—';

                                            Notification::make()
                                                ->title('Übernommen: ' . ($details['name'] ?: 'Station'))
                                                ->body(new \Illuminate\Support\HtmlString(
                                                    'Breitengrad: <b>' . $lat . '</b><br>' .
                                                    'Längengrad: <b>' . $lng . '</b>'
                                                ))
                                                ->success()
                                                ->send();
                                        }),
                                ])->columnSpanFull(),
                            ])->columns(2),
                    ]),

                // ── Tab 2: Stammdaten ─────────────────────────
                Tab::make('Stammdaten')
                    ->icon('heroicon-o-building-storefront')
                    ->schema([
                        TextInput::make('name')
                            ->label('Name der Tankstelle')
                            ->required()
                            ->maxLength(255),

                        Select::make('brand')
                            ->label('Marke')
                            ->options([
                                'Aral'          => 'Aral',
                                'Shell'         => 'Shell',
                                'BP'            => 'BP',
                                'Esso'          => 'Esso',
                                'Total'         => 'Total / TotalEnergies',
                                'Jet'           => 'JET',
                                'Agip'          => 'Agip / ENI',
                                'Westfalen'     => 'Westfalen',
                                'HEM'           => 'HEM',
                                'Freie Station' => 'Freie Tankstelle',
                                'Sonstige'      => 'Sonstige',
                            ])
                            ->searchable()
                            ->nullable(),

                        TextInput::make('station_number')
                            ->label('Stationsnummer')
                            ->maxLength(50)
                            ->nullable(),

                        Toggle::make('is_active')
                            ->label('Station aktiv')
                            ->default(true),
                    ])->columns(2),

                // ── Tab 2: Adresse + Karte ────────────────────
                Tab::make('Adresse & Karte')
                    ->icon('heroicon-o-map-pin')
                    ->schema([
                        TextInput::make('street')
                            ->label('Straße')
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn(Get $get, Set $set) => static::geocode($get, $set)),

                        TextInput::make('house_number')
                            ->label('Hausnummer')
                            ->maxLength(20)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn(Get $get, Set $set) => static::geocode($get, $set)),

                        TextInput::make('zip')
                            ->label('PLZ')
                            ->maxLength(10)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn(Get $get, Set $set) => static::geocode($get, $set)),

                        TextInput::make('city')
                            ->label('Stadt')
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn(Get $get, Set $set) => static::geocode($get, $set)),

                        Select::make('country')
                            ->label('Land')
                            ->options([
                                'DE' => '🇩🇪 Deutschland',
                                'AT' => '🇦🇹 Österreich',
                                'CH' => '🇨🇭 Schweiz',
                            ])
                            ->default('DE'),

                        TextInput::make('lat')
                            ->label('Breitengrad')
                            ->numeric()
                            ->step(0.00000001)
                            ->nullable()
                            ->readOnly()
                            ->helperText('Aus PLZ-Suche übernommen (OpenStreetMap)'),

                        TextInput::make('lng')
                            ->label('Längengrad')
                            ->numeric()
                            ->step(0.00000001)
                            ->nullable()
                            ->readOnly()
                            ->helperText('Aus PLZ-Suche übernommen (OpenStreetMap)'),

                        Placeholder::make('map_preview')
                            ->label('Karten-Vorschau')
                            ->content(function (Get $get) {
                                $lat  = $get('lat');
                                $lng  = $get('lng');
                                $name = $get('name') ?: 'Station';

                                if (! $lat || ! $lng) {
                                    return new HtmlString(
                                        '<p class="text-sm text-gray-400">Adresse eingeben um die Karte zu laden.</p>'
                                    );
                                }

                                return new HtmlString(
                                    view('filament.app.components.station-map-preview', compact('lat', 'lng', 'name'))->render()
                                );
                            })
                            ->columnSpanFull(),
                    ])->columns(2),

                // ── Tab 3: Öffnungszeiten ─────────────────────
                Tab::make('Öffnungszeiten')
                    ->icon('heroicon-o-clock')
                    ->schema([
                        Select::make('opening_hours_preset')
                            ->label('Schnellauswahl')
                            ->options([
                                '24h'    => '24 Stunden',
                                '6_22'   => 'Standard (06–22 Uhr)',
                                '7_21'   => 'Kurz (07–21 Uhr)',
                                'custom' => 'Benutzerdefiniert',
                            ])
                            ->live()
                            ->dehydrated(false)
                            ->afterStateUpdated(function ($state, Set $set) {
                                $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
                                $h = match ($state) {
                                    '24h'  => ['open' => '00:00', 'close' => '23:59', 'is_closed' => false],
                                    '6_22' => ['open' => '06:00', 'close' => '22:00', 'is_closed' => false],
                                    '7_21' => ['open' => '07:00', 'close' => '21:00', 'is_closed' => false],
                                    default => null,
                                };
                                if ($h) {
                                    foreach ($days as $day) {
                                        $set("opening_hours.{$day}.open", $h['open']);
                                        $set("opening_hours.{$day}.close", $h['close']);
                                        $set("opening_hours.{$day}.is_closed", $h['is_closed']);
                                    }
                                }
                            })
                            ->columnSpanFull(),

                        ...collect([
                            'monday'    => 'Montag',
                            'tuesday'   => 'Dienstag',
                            'wednesday' => 'Mittwoch',
                            'thursday'  => 'Donnerstag',
                            'friday'    => 'Freitag',
                            'saturday'  => 'Samstag',
                            'sunday'    => 'Sonntag',
                        ])->map(fn($label, $day) => Grid::make(4)->schema([
                            Placeholder::make("{$day}_label")
                                ->label('')
                                ->content($label)
                                ->columnSpan(1),

                            Toggle::make("opening_hours.{$day}.is_closed")
                                ->label('Geschlossen')
                                ->live()
                                ->columnSpan(1),

                            TextInput::make("opening_hours.{$day}.open")
                                ->label('Öffnet')
                                ->type('time')
                                ->default('06:00')
                                ->visible(fn(Get $g) => ! $g("opening_hours.{$day}.is_closed"))
                                ->columnSpan(1),

                            TextInput::make("opening_hours.{$day}.close")
                                ->label('Schließt')
                                ->type('time')
                                ->default('22:00')
                                ->visible(fn(Get $g) => ! $g("opening_hours.{$day}.is_closed"))
                                ->columnSpan(1),
                        ])->columnSpanFull())->values()->all(),
                    ])->columns(1),

                // ── Tab 4: Station-Details ────────────────────
                Tab::make('Station-Details')
                    ->icon('heroicon-o-cog-6-tooth')
                    ->schema([
                        TextInput::make('tank_count')
                            ->label('Anzahl Tanks')
                            ->numeric()
                            ->nullable(),

                        TextInput::make('dispenser_count')
                            ->label('Anzahl Zapfsäulen')
                            ->numeric()
                            ->nullable(),

                        Toggle::make('has_car_wash')
                            ->label('Waschanlage vorhanden')
                            ->live(),

                        TextInput::make('wash_model')
                            ->label('Waschanlage-Typ')
                            ->visible(fn(Get $g) => $g('has_car_wash'))
                            ->nullable(),

                        Toggle::make('has_bistro')
                            ->label('Bistro / Café vorhanden'),

                        Toggle::make('has_shop')
                            ->label('Shop vorhanden'),
                    ])->columns(2),

                // ── Tab 5: Bankverbindung ─────────────────────
                Tab::make('Bankverbindung')
                    ->icon('heroicon-o-banknotes')
                    ->schema([
                        Section::make()
                            ->description('Bankdaten werden verschlüsselt gespeichert (DSGVO).')
                            ->icon('heroicon-o-shield-check')
                            ->schema([
                                TextInput::make('bank_name')
                                    ->label('Bank')
                                    ->nullable(),

                                TextInput::make('account_holder')
                                    ->label('Kontoinhaber')
                                    ->nullable(),

                                TextInput::make('iban')
                                    ->label('IBAN')
                                    ->maxLength(34)
                                    ->nullable()
                                    ->password()
                                    ->revealable()
                                    ->helperText('Wird verschlüsselt gespeichert'),

                                TextInput::make('bic')
                                    ->label('BIC / SWIFT')
                                    ->maxLength(11)
                                    ->nullable()
                                    ->helperText('Wird verschlüsselt gespeichert'),
                            ])->columns(2),
                    ]),

                // ── Tab 6: Mitarbeiter ────────────────────────
                Tab::make('Mitarbeiter')
                    ->icon('heroicon-o-users')
                    ->schema([
                        Placeholder::make('employees_info')
                            ->label('')
                            ->content(new HtmlString(
                                '<div class="flex gap-3 p-4 rounded-lg bg-blue-50 border border-blue-200">
                                    <div>
                                        <p class="font-medium text-blue-800">Mitarbeiter-Zuweisung</p>
                                        <p class="text-sm text-blue-600 mt-1">Wird im Mitarbeiter-Modul verwaltet (Prompt 09).</p>
                                    </div>
                                </div>'
                            ))
                            ->columnSpanFull(),
                    ]),

                // ── Tab 7: System ─────────────────────────────
                Tab::make('System')
                    ->icon('heroicon-o-information-circle')
                    ->schema([

                        // ── System-Infos ──────────────────────────
                        Section::make('System-Informationen')
                            ->icon('heroicon-o-information-circle')
                            ->collapsed()
                            ->schema([
                                TextInput::make('ulid')
                                    ->label('Öffentliche ID (ULID)')
                                    ->disabled()
                                    ->dehydrated(false),

                                TextInput::make('benzinpreis_slug')
                                    ->label('BenzinpreisService Slug')
                                    ->nullable()
                                    ->helperText('Verknüpfung mit benzinpreis.de'),

                                TextInput::make('enriched_at')
                                    ->label('Letzter Daten-Import')
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->formatStateUsing(fn($state) => $state
                                        ? \Carbon\Carbon::parse($state)->format('d.m.Y H:i')
                                        : 'Nicht importiert'),

                                TextInput::make('created_at')
                                    ->label('Angelegt am')
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->formatStateUsing(fn($state) => $state
                                        ? \Carbon\Carbon::parse($state)->format('d.m.Y H:i')
                                        : '—'),
                            ])->columns(2),
                    ]),

            ])->columnSpanFull(),
        ]);
    }

    // ─────────────────────────────────────────────
    // Tabelle
    // ─────────────────────────────────────────────

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),

                TextColumn::make('brand')
                    ->label('Marke')
                    ->badge()
                    ->color('primary')
                    ->placeholder('—'),

                TextColumn::make('full_address')
                    ->label('Adresse')
                    ->getStateUsing(fn($record) => $record->full_address)
                    ->searchable(['street', 'zip', 'city'])
                    ->placeholder('—'),

                TextColumn::make('employees_count')
                    ->label('Mitarbeiter')
                    ->counts('employees')
                    ->sortable(),

                BooleanColumn::make('is_active')
                    ->label('Aktiv')
                    ->sortable(),

                BooleanColumn::make('has_coordinates')
                    ->label('Karte')
                    ->getStateUsing(fn($record) => $record->hasCoordinates())
                    ->trueIcon('heroicon-o-map-pin')
                    ->falseIcon('heroicon-o-x-mark')
                    ->trueColor('success')
                    ->falseColor('gray'),
            ])
            ->filters([
                TernaryFilter::make('is_active')->label('Aktiv'),
                TrashedFilter::make(),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make()
                    ->visible(fn() => auth()->user()?->can('partner.stations.edit')),
                DeleteAction::make()
                    ->visible(fn() => auth()->user()?->can('partner.stations.delete')),
                RestoreAction::make()
                    ->visible(fn() => auth()->user()?->can('partner.stations.delete')),
            ])
            ->defaultSort('name');
    }

    // ─────────────────────────────────────────────
    // Pages
    // ─────────────────────────────────────────────

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListStations::route('/'),
            'create' => Pages\CreateStation::route('/create'),
            'edit'   => Pages\EditStation::route('/{record}/edit'),
            'view'   => Pages\ViewStation::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->withoutGlobalScopes([SoftDeletingScope::class]);
    }

    // ─────────────────────────────────────────────
    // Geocoding
    // ─────────────────────────────────────────────

    protected static function geocode(Get $get, Set $set): void
    {
        $zip  = $get('zip') ?? '';
        $city = $get('city') ?? '';

        if (empty($zip) && empty($city)) {
            return;
        }

        $query = http_build_query([
            'q'      => trim(($get('street') ?? '') . ', ' . $zip . ' ' . $city . ', ' . ($get('country') ?: 'DE')),
            'format' => 'json',
            'limit'  => 1,
        ]);

        try {
            $response = Http::withHeaders(['User-Agent' => 'Stationpilot/4.0 (contact@stationpilot.de)'])
                ->timeout(5)
                ->get("https://nominatim.openstreetmap.org/search?{$query}");

            if ($response->successful() && ! empty($response->json())) {
                $set('lat', round((float) $response->json()[0]['lat'], 8));
                $set('lng', round((float) $response->json()[0]['lon'], 8));
            }
        } catch (\Throwable) {}
    }
}
