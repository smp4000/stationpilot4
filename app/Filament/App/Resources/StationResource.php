<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\StationResource\Pages;
use App\Models\Brand;
use App\Models\FuelType;
use App\Models\GasStationBankAccount;
use App\Models\Station;
use App\Models\StationCompetitor;
use App\Services\BenzinpreisService;
use App\Services\OverpassService;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Actions;
use Filament\Actions\Action;
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
use Illuminate\Support\Str;

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

    public static function canAccess(): bool   { return auth()->user()?->can('partner.stations.list')   ?? false; }
    public static function canCreate(): bool   { return auth()->user()?->can('partner.stations.create') ?? false; }
    public static function canEdit($r): bool   { return auth()->user()?->can('partner.stations.edit')   ?? false; }
    public static function canDelete($r): bool { return auth()->user()?->can('partner.stations.delete') ?? false; }

    // ─────────────────────────────────────────────
    // Edit-Formular (Tabs)
    // ─────────────────────────────────────────────

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make('Station')->tabs([

                // Tab 1 nur beim Erstellen sichtbar — im Wizard übernommen
                Tab::make('Stationssuche')
                    ->icon('heroicon-o-magnifying-glass')
                    ->hidden(fn ($record) => $record !== null)
                    ->schema(static::osmSearchSchema()),

                ...static::stationDataTabs(),

            ])->columnSpanFull(),
        ]);
    }

    // ─────────────────────────────────────────────
    // OSM-Suche Schema (Create-Tab + Wizard Step 1)
    // ─────────────────────────────────────────────

    public static function osmSearchSchema(): array
    {
        return [
            Section::make('Tankstelle per PLZ suchen')
                ->icon('heroicon-o-map-pin')
                ->description('PLZ eingeben → automatische Suche via OpenStreetMap → Koordinaten + Stammdaten übernehmen.')
                ->schema([
                    // _osm_data_json enthält das komplette Ergebnis-Array als JSON
                    Hidden::make('_osm_data_json')->dehydrated(false),

                    TextInput::make('_search_zip')
                        ->label('PLZ')
                        ->placeholder('z. B. 36043')
                        ->maxLength(5)
                        ->live(debounce: 600)
                        ->dehydrated(false)
                        ->helperText('5-stellige PLZ eingeben — Suche startet automatisch.')
                        ->afterStateUpdated(function ($state, Set $set) {
                            $zip = trim($state ?? '');
                            if (! preg_match('/^\d{5}$/', $zip)) return;

                            $results = app(OverpassService::class)->searchFuelStationsByZip($zip);

                            if (empty($results)) {
                                Notification::make()->title("Keine Tankstellen für PLZ {$zip} gefunden")->warning()->send();
                                $set('_osm_data_json', null);
                                return;
                            }

                            // Ergebnisse als JSON speichern — der Select liest daraus direkt
                            $set('_osm_data_json', json_encode(array_values($results)));

                            Notification::make()->title(count($results) . ' Stationen in PLZ ' . $zip . ' gefunden')->info()->send();
                        }),

                    Select::make('_selected_station')
                        ->label('Gefundene Tankstellen')
                        ->live()
                        ->afterStateUpdated(function (Get $get, Set $set, $state) {
                            if ($state === null) return;
                            $json = $get('_osm_data_json');
                            if (! $json) return;

                            $details = json_decode($json, true)[$state] ?? null;
                            if (! $details) return;

                            // Brand per Slug oder Name aus DB suchen
                            $brandRaw = $details['brand'] ?? null;
                            if ($brandRaw) {
                                $brand = Brand::where('slug', Str::slug($brandRaw))->first()
                                      ?? Brand::whereRaw('LOWER(name) = ?', [strtolower($brandRaw)])->first();
                                if ($brand) $set('brand_id', $brand->id);
                            }

                            if (! empty($details['name']))         $set('name',         $details['name']);
                            if (! empty($details['street']))       $set('street',       $details['street']);
                            if (! empty($details['house_number'])) $set('house_number', $details['house_number']);
                            if (! empty($details['zip']))          $set('zip',          $details['zip']);
                            if (! empty($details['city']))         $set('city',         $details['city']);
                            if (! empty($details['lat']))          $set('latitude',     $details['lat']);
                            if (! empty($details['lng']))          $set('longitude',    $details['lng']);

                            if (! empty($details['opening_hours'])) {
                                $parsed = static::parseOsmOpeningHours($details['opening_hours']);
                                $set('is_24h', $parsed['is_24h']);
                                foreach ($parsed['hours'] as $day => $h) {
                                    $set("opening_hours.{$day}.open",      $h['open']);
                                    $set("opening_hours.{$day}.close",     $h['close']);
                                    $set("opening_hours.{$day}.is_closed", $h['is_closed']);
                                }
                            }

                            Notification::make()
                                ->title('Übernommen: ' . ($details['name'] ?: 'Station'))
                                ->body(new HtmlString('Lat: <b>' . ($details['lat'] ?? '—') . '</b> · Lng: <b>' . ($details['lng'] ?? '—') . '</b>'))
                                ->success()->send();
                        })
                        ->options(function (Get $get) {
                            $json = $get('_osm_data_json');
                            if (! $json) return [];
                            return collect(json_decode($json, true) ?? [])
                                ->mapWithKeys(function ($s, $i) {
                                    $street = trim(($s['street'] ?? '') . ' ' . ($s['house_number'] ?? ''));
                                    $place  = trim(($s['zip']    ?? '') . ' ' . ($s['city']         ?? ''));
                                    $addr   = collect([$street, $place])->filter()->implode(', ');
                                    if (! $addr) $addr = 'ca. ' . round($s['lat'], 4) . ', ' . round($s['lng'], 4);
                                    return [$i => $s['name'] . ' · ' . $addr];
                                })->toArray();
                        })
                        ->live()
                        ->dehydrated(false)
                        ->placeholder('Station auswählen oder überspringen…')
                        ->columnSpanFull(),

                ])->columns(2),
        ];
    }

    // ─────────────────────────────────────────────
    // Schema-Methoden (Edit-Tabs + Create-Wizard)
    // ─────────────────────────────────────────────

    public static function generalSchema(): array
    {
        return [
            Section::make('Kern-Daten')
                ->columns(2)
                ->schema([
                    TextInput::make('name')
                        ->label('Name der Tankstelle')
                        ->required()->maxLength(255),

                    Select::make('brand_id')
                        ->label('Marke')
                        ->options(Brand::selectOptions())
                        ->searchable()->nullable()->preload(),

                    TextInput::make('station_number')
                        ->label('Stationsnummer')
                        ->maxLength(50)
                        ->required(fn (Get $get): bool => (int) $get('brand_id') === 1) // Pflicht bei Aral
                        ->nullable(),

                    Toggle::make('is_active')
                        ->label('Station aktiv')
                        ->default(true),
                ]),

            Section::make('Organisation')
                ->columns(2)
                ->schema([
                    Select::make('sales_channel')
                        ->label('Vertriebskanal')
                        ->options([
                            'direkt'    => 'Direkt',
                            'franchise' => 'Franchise',
                            'partner'   => 'Partner',
                            'dealer'    => 'Dealer',
                        ])->nullable(),

                    Select::make('ownership_type')
                        ->label('Eigentumsverhältnis')
                        ->options(Station::ownershipTypeOptions())
                        ->nullable(),

                    TextInput::make('district')->label('Distrikt')->maxLength(100)->nullable(),
                    TextInput::make('district_description')->label('Distrikt-Beschreibung')->nullable(),
                    TextInput::make('region')->label('Bezirk')->maxLength(100)->nullable(),
                    TextInput::make('region_manager')->label('Bezirksleitung')->nullable(),

                    TextInput::make('station_number_fuel')
                        ->label('Tst.-Nr. Kraftstoff')->maxLength(50)->nullable(),
                    TextInput::make('station_number_shop')
                        ->label('Tst.-Nr. Shop')->maxLength(50)->nullable(),

                    Toggle::make('has_toll_terminal')->label('Mautstellenterminal vorhanden'),
                ]),
        ];
    }

    public static function addressSchema(): array
    {
        return [
            Section::make('Ansprechpartner')
                ->columns(3)
                ->schema([
                    Select::make('academic_title')
                        ->label('Akad. Grad')
                        ->options(['Dr.' => 'Dr.', 'Prof.' => 'Prof.', 'Dipl.-Ing.' => 'Dipl.-Ing.'])
                        ->nullable(),
                    TextInput::make('contact_first_name')->label('Vorname')->nullable(),
                    TextInput::make('contact_last_name')->label('Name')->nullable(),
                ]),

            Section::make()
                ->columns(2)
                ->schema([
                    TextInput::make('street')->label('Strasse')
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn(Get $get, Set $set) => static::geocode($get, $set)),
                    TextInput::make('house_number')->label('Hausnummer')
                        ->maxLength(20)->live(onBlur: true)
                        ->afterStateUpdated(fn(Get $get, Set $set) => static::geocode($get, $set)),
                    TextInput::make('zip')->label('PLZ')
                        ->maxLength(10)
                        ->live(debounce: 500)
                        ->afterStateUpdated(function ($state, Get $get, Set $set) {
                            // Sofort: Bundesland aus lokaler PLZ-Tabelle
                            $code = static::stateFromZip($state ?? '');
                            if ($code) $set('state', $code);
                            // Koordinaten geocodieren
                            static::geocode($get, $set);
                        }),
                    TextInput::make('city')->label('Stadt')
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn(Get $get, Set $set) => static::geocode($get, $set)),
                    TextInput::make('district_part')->label('Ortsteil')->maxLength(100)->nullable(),
                    Select::make('state')
                        ->label('Bundesland')
                        ->options(Station::stateOptions())
                        ->searchable()->nullable(),
                    Select::make('country')
                        ->label('Land')
                        ->options(['DE' => '🇩🇪 Deutschland', 'AT' => '🇦🇹 Österreich', 'CH' => '🇨🇭 Schweiz'])
                        ->default('DE'),
                ]),

            Section::make('Kontakt')
                ->columns(2)
                ->schema([
                    TextInput::make('phone')->label('Telefon')->tel()->maxLength(30)->nullable(),
                    TextInput::make('fax')->label('Fax')->maxLength(30)->nullable(),
                    TextInput::make('email')->label('E-Mail')->email()->nullable(),
                    TextInput::make('website')->label('Webseite')->url()->nullable(),
                    Placeholder::make('_salutation')
                        ->label('Anrede Anschrift')
                        ->content(function (Get $get) {
                            $name    = $get('name') ?? '';
                            $contact = trim(($get('contact_first_name') ?? '') . ' ' . ($get('contact_last_name') ?? ''));
                            return new HtmlString('<span style="font-size:14px;color:#374151;">' . e($name . ($contact ? ' ' . $contact : '')) . '</span>');
                        })->columnSpanFull(),
                ]),

            Section::make('Geschäftsdaten')
                ->columns(2)
                ->collapsed()
                ->schema([
                    TextInput::make('tax_id')->label('Steuernummer / USt-IdNr.')->maxLength(50)->nullable(),
                    TextInput::make('trade_register')->label('Handelsregisternummer')->maxLength(100)->nullable(),
                ]),
        ];
    }

    public static function openingHoursSchema(): array
    {
        return [
            Section::make('Öffnungszeiten')
                ->columns(1)
                ->schema([
                    Hidden::make('_backup_opening_hours')->dehydrated(false),

                    // ── Zeile 1: Toggle | Wochenstunden | Status ──────────
                    Grid::make(3)->schema([

                        Toggle::make('is_24h')
                            ->label('24 Stunden / Rund um die Uhr')
                            ->live()
                            ->afterStateUpdated(function ($state, Get $get, Set $set) {
                                $days = ['monday','tuesday','wednesday','thursday','friday','saturday','sunday'];
                                if ($state) {
                                    $backup = [];
                                    foreach ($days as $d) {
                                        $backup[$d] = [
                                            'open'      => $get("opening_hours.{$d}.open")      ?? '06:00',
                                            'close'     => $get("opening_hours.{$d}.close")     ?? '21:00',
                                            'is_closed' => $get("opening_hours.{$d}.is_closed") ?? false,
                                        ];
                                    }
                                    $set('_backup_opening_hours', json_encode($backup));
                                    foreach ($days as $d) {
                                        $set("opening_hours.{$d}.open",      '00:00');
                                        $set("opening_hours.{$d}.close",     '23:59');
                                        $set("opening_hours.{$d}.is_closed", false);
                                    }
                                } else {
                                    $backup = json_decode($get('_backup_opening_hours') ?? '[]', true);
                                    if (! empty($backup)) {
                                        foreach ($days as $d) {
                                            $set("opening_hours.{$d}.open",      $backup[$d]['open']      ?? '06:00');
                                            $set("opening_hours.{$d}.close",     $backup[$d]['close']     ?? '21:00');
                                            $set("opening_hours.{$d}.is_closed", $backup[$d]['is_closed'] ?? false);
                                        }
                                    }
                                    $set('_backup_opening_hours', null);
                                }
                            }),

                        // Wochenstunden-Badge — zwischen Toggle und Status
                        Placeholder::make('_weekly_hours')->hiddenLabel()
                            ->content(function (Get $get) {
                                if ($get('is_24h')) {
                                    return new HtmlString('<span style="display:inline-flex;align-items:center;gap:6px;padding:4px 12px;border-radius:9999px;background:#dbeafe;color:#1e3a8a;font-size:13px;font-weight:500">🕐 168 Std / Woche</span>');
                                }
                                $mins = 0;
                                foreach (['monday','tuesday','wednesday','thursday','friday','saturday','sunday'] as $day) {
                                    $h = $get("opening_hours.{$day}");
                                    if (! ($h['is_closed'] ?? false)) {
                                        [$oh,$om] = array_map('intval', explode(':', $h['open']  ?? '00:00'));
                                        [$ch,$cm] = array_map('intval', explode(':', $h['close'] ?? '00:00'));
                                        $d = ($ch * 60 + $cm) - ($oh * 60 + $om);
                                        if ($d > 0) $mins += $d;
                                    }
                                }
                                return new HtmlString('<span style="display:inline-flex;align-items:center;gap:6px;padding:4px 12px;border-radius:9999px;background:#dbeafe;color:#1e3a8a;font-size:13px;font-weight:500">🕐 ' . round($mins / 60) . ' Std / Woche</span>');
                            }),

                        // Geöffnet/Geschlossen-Badge
                        Placeholder::make('_open_status')->hiddenLabel()
                            ->content(function (Get $get) {
                                if ($get('is_24h')) {
                                    return new HtmlString('<span style="display:inline-flex;align-items:center;gap:5px;padding:3px 10px;border-radius:9999px;background:#d1fae5;color:#065f46;font-size:12px;font-weight:500"><span style="width:7px;height:7px;border-radius:50%;background:#10b981;flex-shrink:0"></span>24h geöffnet</span>');
                                }
                                $dayMap = [0=>'sunday',1=>'monday',2=>'tuesday',3=>'wednesday',4=>'thursday',5=>'friday',6=>'saturday'];
                                $h   = $get("opening_hours." . $dayMap[now()->dayOfWeek]);
                                $cur = now()->format('H:i');
                                if (! $h || ($h['is_closed'] ?? false) || $cur < ($h['open'] ?? '00:00') || $cur > ($h['close'] ?? '23:59')) {
                                    return new HtmlString('<span style="display:inline-flex;align-items:center;gap:5px;padding:3px 10px;border-radius:9999px;background:#ffe4e6;color:#9f1239;font-size:12px;font-weight:500"><span style="width:7px;height:7px;border-radius:50%;background:#f43f5e;flex-shrink:0"></span>Geschlossen</span>');
                                }
                                return new HtmlString('<span style="display:inline-flex;align-items:center;gap:5px;padding:3px 10px;border-radius:9999px;background:#d1fae5;color:#065f46;font-size:12px;font-weight:500"><span style="width:7px;height:7px;border-radius:50%;background:#10b981;flex-shrink:0"></span>Geöffnet bis ' . $h['close'] . '</span>');
                            }),

                    ])->columnSpanFull(),

                    // ── Zeile 2: Erstöffnungs-Daten ───────────────────────
                    Grid::make(4)->schema([
                        DatePicker::make('first_opening_ok')
                            ->label('Erstöffnung OK')
                            ->displayFormat('d.m.Y')->nullable(),
                        DatePicker::make('first_opening_dk')
                            ->label('Erstöffnung DK')
                            ->displayFormat('d.m.Y')->nullable(),
                    ])->columnSpanFull(),

                    // ── Spaltenheader (4/6 Breite → Inhalt links) ─────────
                    Grid::make(6)->schema([
                        Placeholder::make('_hdr_tag')->hiddenLabel()->columnSpan(1)
                            ->content(new HtmlString('<span style="font-size:11px;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:.05em">Tag</span>')),
                        Placeholder::make('_hdr_on')->hiddenLabel()->columnSpan(1)
                            ->content(new HtmlString('<span style="font-size:11px;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:.05em">Öffnet</span>')),
                        Placeholder::make('_hdr_off')->hiddenLabel()->columnSpan(1)
                            ->content(new HtmlString('<span style="font-size:11px;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:.05em">Schließt</span>')),
                        Placeholder::make('_hdr_cl')->hiddenLabel()->columnSpan(1)
                            ->content(new HtmlString('<span style="font-size:11px;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:.05em">Geschlossen</span>')),
                    ]),

                    // ── Tageszeilen (je 4/6 Breite, Tag eng am Input) ────

                    Grid::make(6)->schema([
                        Placeholder::make('_lmo')->hiddenLabel()->columnSpan(1)
                            ->content(new HtmlString('<b style="color:#374151">Montag</b>')),
                        TextInput::make('opening_hours.monday.open')->hiddenLabel()->type('time')->default('06:00')->live()->columnSpan(1)
                            ->disabled(fn(Get $g) => $g('is_24h') || $g('opening_hours.monday.is_closed')),
                        TextInput::make('opening_hours.monday.close')->hiddenLabel()->type('time')->default('21:00')->live()->columnSpan(1)
                            ->disabled(fn(Get $g) => $g('is_24h') || $g('opening_hours.monday.is_closed')),
                        Toggle::make('opening_hours.monday.is_closed')->hiddenLabel()->live()->columnSpan(1)
                            ->disabled(fn(Get $g) => $g('is_24h')),
                    ]),

                    Grid::make(6)->schema([
                        Placeholder::make('_ltu')->hiddenLabel()->columnSpan(1)
                            ->content(new HtmlString('<b style="color:#374151">Dienstag</b>')),
                        TextInput::make('opening_hours.tuesday.open')->hiddenLabel()->type('time')->default('06:00')->live()->columnSpan(1)
                            ->disabled(fn(Get $g) => $g('is_24h') || $g('opening_hours.tuesday.is_closed')),
                        TextInput::make('opening_hours.tuesday.close')->hiddenLabel()->type('time')->default('21:00')->live()->columnSpan(1)
                            ->disabled(fn(Get $g) => $g('is_24h') || $g('opening_hours.tuesday.is_closed')),
                        Toggle::make('opening_hours.tuesday.is_closed')->hiddenLabel()->live()->columnSpan(1)
                            ->disabled(fn(Get $g) => $g('is_24h')),
                    ]),

                    Grid::make(6)->schema([
                        Placeholder::make('_lwe')->hiddenLabel()->columnSpan(1)
                            ->content(new HtmlString('<b style="color:#374151">Mittwoch</b>')),
                        TextInput::make('opening_hours.wednesday.open')->hiddenLabel()->type('time')->default('06:00')->live()->columnSpan(1)
                            ->disabled(fn(Get $g) => $g('is_24h') || $g('opening_hours.wednesday.is_closed')),
                        TextInput::make('opening_hours.wednesday.close')->hiddenLabel()->type('time')->default('21:00')->live()->columnSpan(1)
                            ->disabled(fn(Get $g) => $g('is_24h') || $g('opening_hours.wednesday.is_closed')),
                        Toggle::make('opening_hours.wednesday.is_closed')->hiddenLabel()->live()->columnSpan(1)
                            ->disabled(fn(Get $g) => $g('is_24h')),
                    ]),

                    Grid::make(6)->schema([
                        Placeholder::make('_lth')->hiddenLabel()->columnSpan(1)
                            ->content(new HtmlString('<b style="color:#374151">Donnerstag</b>')),
                        TextInput::make('opening_hours.thursday.open')->hiddenLabel()->type('time')->default('06:00')->live()->columnSpan(1)
                            ->disabled(fn(Get $g) => $g('is_24h') || $g('opening_hours.thursday.is_closed')),
                        TextInput::make('opening_hours.thursday.close')->hiddenLabel()->type('time')->default('21:00')->live()->columnSpan(1)
                            ->disabled(fn(Get $g) => $g('is_24h') || $g('opening_hours.thursday.is_closed')),
                        Toggle::make('opening_hours.thursday.is_closed')->hiddenLabel()->live()->columnSpan(1)
                            ->disabled(fn(Get $g) => $g('is_24h')),
                    ]),

                    Grid::make(6)->schema([
                        Placeholder::make('_lfr')->hiddenLabel()->columnSpan(1)
                            ->content(new HtmlString('<b style="color:#374151">Freitag</b>')),
                        TextInput::make('opening_hours.friday.open')->hiddenLabel()->type('time')->default('06:00')->live()->columnSpan(1)
                            ->disabled(fn(Get $g) => $g('is_24h') || $g('opening_hours.friday.is_closed')),
                        TextInput::make('opening_hours.friday.close')->hiddenLabel()->type('time')->default('21:00')->live()->columnSpan(1)
                            ->disabled(fn(Get $g) => $g('is_24h') || $g('opening_hours.friday.is_closed')),
                        Toggle::make('opening_hours.friday.is_closed')->hiddenLabel()->live()->columnSpan(1)
                            ->disabled(fn(Get $g) => $g('is_24h')),
                    ]),

                    Grid::make(6)->schema([
                        Placeholder::make('_lsa')->hiddenLabel()->columnSpan(1)
                            ->content(new HtmlString('<b style="color:#374151">Samstag</b>')),
                        TextInput::make('opening_hours.saturday.open')->hiddenLabel()->type('time')->default('07:00')->live()->columnSpan(1)
                            ->disabled(fn(Get $g) => $g('is_24h') || $g('opening_hours.saturday.is_closed')),
                        TextInput::make('opening_hours.saturday.close')->hiddenLabel()->type('time')->default('21:00')->live()->columnSpan(1)
                            ->disabled(fn(Get $g) => $g('is_24h') || $g('opening_hours.saturday.is_closed')),
                        Toggle::make('opening_hours.saturday.is_closed')->hiddenLabel()->live()->columnSpan(1)
                            ->disabled(fn(Get $g) => $g('is_24h')),
                    ]),

                    Grid::make(6)->schema([
                        Placeholder::make('_lsu')->hiddenLabel()->columnSpan(1)
                            ->content(new HtmlString('<b style="color:#374151">Sonntag</b>')),
                        TextInput::make('opening_hours.sunday.open')->hiddenLabel()->type('time')->default('08:00')->live()->columnSpan(1)
                            ->disabled(fn(Get $g) => $g('is_24h') || $g('opening_hours.sunday.is_closed')),
                        TextInput::make('opening_hours.sunday.close')->hiddenLabel()->type('time')->default('21:00')->live()->columnSpan(1)
                            ->disabled(fn(Get $g) => $g('is_24h') || $g('opening_hours.sunday.is_closed')),
                        Toggle::make('opening_hours.sunday.is_closed')->hiddenLabel()->live()->columnSpan(1)
                            ->disabled(fn(Get $g) => $g('is_24h')),
                    ]),

                ]),
        ];
    }

    public static function shopBetriebSchema(): array
    {
        return [
            // ── Tankdetails ────────────────────────────────────────────
            Section::make('Tankdetails')
                ->icon('heroicon-m-fire')
                ->columns(2)
                ->schema([
                    TextInput::make('num_pumps')
                        ->label('Anzahl Zapfsäulen')
                        ->numeric()->nullable(),
                    Toggle::make('has_camera')
                        ->label('Videoüberwachung vorhanden')
                        ->helperText('Hat die Station eine Kamera/Videoüberwachung?'),
                    Select::make('fuel_types')
                        ->label('Kraftstoffarten')
                        ->multiple()
                        ->options(fn() => FuelType::selectOptions(grouped: true))
                        ->searchable()
                        ->nullable()
                        ->columnSpanFull(),
                ]),

            // ── Waschanlage ────────────────────────────────────────────
            Section::make('Waschanlage')
                ->icon('heroicon-m-sparkles')
                ->collapsible()
                ->columns(2)
                ->schema([
                    Toggle::make('has_car_wash')
                        ->label('Waschanlage vorhanden')
                        ->live()
                        ->columnSpanFull(),
                    Toggle::make('car_wash_details.drive_through')
                        ->label('Durchfahrthalle')
                        ->hidden(fn(Get $get) => ! $get('has_car_wash')),
                    Toggle::make('car_wash_details.underbody_wash')
                        ->label('Unterbodenwasche')
                        ->hidden(fn(Get $get) => ! $get('has_car_wash')),
                    TextInput::make('car_wash_details.brand')
                        ->label('Marke')
                        ->nullable()->placeholder('z. B. WasTec, Christ, Kärcher')
                        ->hidden(fn(Get $get) => ! $get('has_car_wash')),
                    Select::make('car_wash_details.type')
                        ->label('Anlagentyp')
                        ->options([
                            'portal'   => 'Portal',
                            'tunnel'   => 'Tunnel',
                            'sb'       => 'SB (Selbstbedienung)',
                            'combined' => 'Kombination',
                        ])
                        ->nullable()
                        ->hidden(fn(Get $get) => ! $get('has_car_wash')),
                    TextInput::make('car_wash_details.height')
                        ->label('Durchfahrthöhe (m)')
                        ->numeric()->step(0.1)->nullable()
                        ->hidden(fn(Get $get) => ! $get('has_car_wash')),
                    TextInput::make('car_wash_details.width')
                        ->label('Durchfahrtsbreite (m)')
                        ->numeric()->step(0.1)->nullable()
                        ->hidden(fn(Get $get) => ! $get('has_car_wash')),
                    Toggle::make('car_wash_details.has_ticket_system')
                        ->label('Ticketsystem')
                        ->hidden(fn(Get $get) => ! $get('has_car_wash')),
                    Toggle::make('car_wash_details.easy_carwash')
                        ->label('Easy-Carwash-Pro')
                        ->hidden(fn(Get $get) => ! $get('has_car_wash')),
                    Textarea::make('car_wash_details.notes')
                        ->label('Interne Notizen (Waschanlage)')
                        ->rows(3)->nullable()->columnSpanFull()
                        ->hidden(fn(Get $get) => ! $get('has_car_wash')),
                ]),

            // ── Shop-Details ───────────────────────────────────────────
            Section::make('Shop-Details')
                ->icon('heroicon-m-shopping-bag')
                ->collapsible()
                ->columns(2)
                ->schema([
                    Toggle::make('has_shop')
                        ->label('Shop vorhanden')
                        ->live()
                        ->columnSpanFull(),
                    TextInput::make('shop_size')
                        ->label('Shopgrösse')
                        ->nullable()->placeholder('z. B. G2, 120 m²')
                        ->hidden(fn(Get $get) => ! $get('has_shop')),
                    TextInput::make('shop_type')
                        ->label('Shoptyp')
                        ->nullable()->placeholder('z. B. REWE To Go, Jäger')
                        ->hidden(fn(Get $get) => ! $get('has_shop')),
                    Select::make('shop_class')
                        ->label('Shop Klasse')
                        ->options(['A' => 'Klasse A', 'B' => 'Klasse B', 'C' => 'Klasse C'])
                        ->nullable()
                        ->hidden(fn(Get $get) => ! $get('has_shop')),
                    DatePicker::make('shop_setup_date')
                        ->label('Bewirtschaftungsdatum Shop')
                        ->displayFormat('d.m.Y')->nullable()
                        ->hidden(fn(Get $get) => ! $get('has_shop')),
                    TextInput::make('nielsen_area')
                        ->label('Nielsen-Gebiet')->maxLength(10)->nullable()
                        ->hidden(fn(Get $get) => ! $get('has_shop')),
                    TextInput::make('price_region')
                        ->label('Preisregion')->nullable()
                        ->hidden(fn(Get $get) => ! $get('has_shop')),
                    TextInput::make('assortment_level')
                        ->label('Sortimentstufe')->nullable()
                        ->placeholder('z. B. Primum')
                        ->hidden(fn(Get $get) => ! $get('has_shop')),
                    TextInput::make('shop_partner')
                        ->label('Shop Partner')->nullable()
                        ->placeholder('z. B. News To Go, REWE To Go')
                        ->hidden(fn(Get $get) => ! $get('has_shop')),
                    TextInput::make('shop_operation_number')
                        ->label('Shop-Betriebsnummer')->maxLength(50)->nullable()
                        ->hidden(fn(Get $get) => ! $get('has_shop')),
                ]),

            // ── Services ───────────────────────────────────────────────
            Section::make('Services')
                ->columns(1)
                ->schema([
                    CheckboxList::make('services')
                        ->label('')
                        ->options([
                            'air'           => 'Luft-Tankstelle',
                            'vacuum'        => 'Staubsauger',
                            'water'         => 'Scheibenwaschwasser',
                            'tire_check'    => 'Reifendruck-Prüfung',
                            'ev_charging'   => 'E-Ladesäule',
                            'truck_parking' => 'LKW-Stellplatz',
                        ])
                        ->columns(3)
                        ->columnSpanFull(),
                ]),

            // ── Zusatzgeschäfte ────────────────────────────────────────
            Section::make('Zusatzgeschäfte')
                ->columns(1)
                ->schema([
                    CheckboxList::make('additional_businesses')
                        ->label('')
                        ->options([
                            'hermes'               => 'Hermes Paketshop',
                            'amazon_locker'        => 'Amazon Locker',
                            'masterbieter'         => 'Masterbieter',
                            'ups'                  => 'UPS Access Point',
                            'lotto'                => 'Lotto',
                            'autovermietung'       => 'Autovermietung',
                            'dhl'                  => 'DHL Paketshop',
                            'toto'                 => 'Toto',
                            'oelservice'           => 'Ölservice',
                            'dpd'                  => 'DPD Pickup',
                            'sportwetten'          => 'Sportwetten',
                            'gebrauchtwagenhandel' => 'Gebrauchtwagenhandel',
                            'ols'                  => 'OLS Paketshop',
                            'reformservice'        => 'Reformservice',
                            'tuev'                 => 'TÜVK / Dekra Prüfstelle',
                            'atm'                  => 'Geldautomat',
                            'backshop'             => 'Backshop',
                            'cafe'                 => 'Café / Restaurant',
                            'laundry'              => 'Waschsalon',
                            'tire_service'         => 'Reifenservice',
                            'oil_change'           => 'Ölwechsel',
                        ])
                        ->columns(3)
                        ->columnSpanFull(),
                    Textarea::make('notes')
                        ->label('Interne Notizen')
                        ->rows(4)->nullable()->columnSpanFull(),
                ]),
        ];
    }

    /**
     * IBAN → Bankname + BIC via openiban.com (kostenlos, kein API-Key nötig).
     * Wird nach IBAN-Eingabe und nach dem IBAN-Rechner aufgerufen.
     */
    public static function lookupIban(?string $iban, Set $set): void
    {
        if (! $iban || strlen($iban) < 15) return;

        $iban = strtoupper(preg_replace('/\s+/', '', $iban));

        // Nur deutsche IBANs (DE + 20 Stellen)
        if (! preg_match('/^DE\d{20}$/', $iban)) return;

        try {
            $response = Http::timeout(6)
                ->get("https://openiban.com/validate/{$iban}", [
                    'getBIC'          => 'true',
                    'validateBankCode' => 'true',
                ]);

            if ($response->ok()) {
                $data     = $response->json();
                $bankData = $data['bankData'] ?? [];

                if (! empty($bankData['name'])) {
                    $set('bank_name', $bankData['name']);
                }
                if (! empty($bankData['bic'])) {
                    $set('bic', $bankData['bic']);
                }
            }
        } catch (\Throwable) {
            // Netzwerkfehler — Felder bleiben leer, Nutzer füllt manuell aus
        }
    }

    public static function financeSchema(): array
    {
        return [

            // ── IBAN-Rechner — zentral oben ───────────────────────────
            Section::make()
                ->schema([
                    Placeholder::make('_iban_hint')
                        ->hiddenLabel()
                        ->content(new HtmlString('
                            <div style="text-align:center;padding:8px 0 2px">
                                <div style="font-size:15px;font-weight:600;color:#1e3a5f">🧮 IBAN-Rechner</div>
                                <div style="font-size:12px;color:#6b7280;margin-top:3px">
                                    BLZ + Kontonummer eingeben → IBAN wird berechnet, Bankname &amp; BIC automatisch ermittelt
                                    und direkt als neues Konto eingetragen.
                                </div>
                            </div>
                        ')),

                    Actions::make([
                        Action::make('iban_rechner_top')
                            ->label('IBAN berechnen & Konto hinzufügen')
                            ->icon('heroicon-o-calculator')
                            ->color('primary')
                            ->modalHeading('IBAN aus BLZ + Kontonummer berechnen')
                            ->modalWidth('md')
                            ->form([
                                Grid::make(2)->schema([
                                    TextInput::make('blz')
                                        ->label('Bankleitzahl (BLZ)')
                                        ->placeholder('z. B. 53060180')
                                        ->required()->maxLength(8),
                                    TextInput::make('kontonummer')
                                        ->label('Kontonummer')
                                        ->placeholder('z. B. 100250503')
                                        ->required()->maxLength(10),
                                ]),
                                Grid::make(2)->schema([
                                    TextInput::make('description')
                                        ->label('Beschreibung')
                                        ->placeholder('z. B. Hauptkonto, Lohnkonto …'),
                                    Select::make('account_type')
                                        ->label('Kontotyp')
                                        ->options(GasStationBankAccount::accountTypeOptions()),
                                ]),
                            ])
                            ->modalSubmitActionLabel('Berechnen & eintragen')
                            ->action(function (array $data, Get $get, Set $set) {
                                $blz   = str_pad(preg_replace('/\D/', '', $data['blz']), 8, '0', STR_PAD_LEFT);
                                $kto   = str_pad(preg_replace('/\D/', '', $data['kontonummer']), 10, '0', STR_PAD_LEFT);
                                $bban  = $blz . $kto;
                                $num   = $bban . '131400';
                                $check = str_pad((string)(98 - (int) bcmod($num, '97')), 2, '0', STR_PAD_LEFT);
                                $iban  = 'DE' . $check . $bban;

                                // Bank-Daten per API ermitteln
                                $bankName = '';
                                $bic      = '';
                                try {
                                    $resp = Http::timeout(6)
                                        ->get("https://openiban.com/validate/{$iban}?getBIC=true&validateBankCode=true");
                                    if ($resp->ok()) {
                                        $bd = $resp->json()['bankData'] ?? [];
                                        $bankName = $bd['name'] ?? '';
                                        $bic      = $bd['bic']  ?? '';
                                    }
                                } catch (\Throwable) {}

                                // Zur Kontenliste hinzufügen
                                $existing = array_values($get('bankAccounts') ?? []);
                                $set('bankAccounts', [...$existing, [
                                    'iban'         => $iban,
                                    'bank_name'    => $bankName,
                                    'bic'          => $bic,
                                    'description'  => $data['description'] ?? '',
                                    'account_type' => $data['account_type'] ?? null,
                                ]]);

                                Notification::make()
                                    ->title('Konto eingetragen' . ($bankName ? ': ' . $bankName : ''))
                                    ->body(new HtmlString('IBAN: <b>' . $iban . '</b>' . ($bic ? ' · BIC: <b>' . $bic . '</b>' : '')))
                                    ->success()->send();
                            }),
                    ])->alignCenter(),
                ])
                ->columnSpanFull(),

            // ── Bankkonten — Tabelle ──────────────────────────────────
            Repeater::make('bankAccounts')
                ->relationship('bankAccounts')
                ->label('Bankkonten')
                ->addActionLabel('+ Konto manuell hinzufügen')
                ->reorderable(false)
                ->schema([
                    TextInput::make('iban')
                        ->label('IBAN')
                        ->required()->password()->revealable()
                        ->helperText('Verschlüsselt')
                        ->live(debounce: 1200)
                        ->afterStateUpdated(fn(?string $state, Set $set) => static::lookupIban($state, $set)),
                    TextInput::make('bank_name')
                        ->label('Bankname')
                        ->placeholder('Automatisch')
                        ->nullable(),
                    TextInput::make('bic')
                        ->label('BIC')
                        ->placeholder('Automatisch')
                        ->nullable(),
                    TextInput::make('description')
                        ->label('Beschreibung')
                        ->placeholder('Hauptkonto …')
                        ->nullable(),
                    Select::make('account_type')
                        ->label('Kontotyp')
                        ->options(GasStationBankAccount::accountTypeOptions())
                        ->nullable(),
                ])
                ->columns(5)
                ->columnSpanFull(),
        ];
    }

    public static function wettbewerbSchema(): array
    {
        return [
            Section::make('Eigene Kraftstoffpreise')
                ->description('Aktuelle Preise Ihrer Tankstelle fuer den Kartenvergleich')
                ->icon('heroicon-o-currency-euro')
                ->columns(3)
                ->schema([
                    TextInput::make('price_super')
                        ->label('Super E5')->numeric()->step(0.001)->nullable()
                        ->suffix('€/L')->placeholder('z.B. 1.859'),
                    TextInput::make('price_e10')
                        ->label('Super E10')->numeric()->step(0.001)->nullable()
                        ->suffix('€/L')->placeholder('z.B. 1.799'),
                    TextInput::make('price_diesel')
                        ->label('Diesel')->numeric()->step(0.001)->nullable()
                        ->suffix('€/L')->placeholder('z.B. 1.699'),
                ]),

            Section::make('Preisdaten-Verknüpfung')
                ->icon('heroicon-o-link')
                ->collapsed()
                ->columns(2)
                ->schema([
                    TextInput::make('benzinpreis_hash')
                        ->label('benzinpreis-aktuell.de Hash')
                        ->nullable()
                        ->helperText('z. B. a1b2c3d4 — aus der URL: preise-t<strong>a1b2c3d4</strong>-slug')
                        ->extraInputAttributes(['style' => 'font-family: monospace']),

                    TextInput::make('benzinpreis_slug')
                        ->label('benzinpreis-aktuell.de Slug')
                        ->nullable()
                        ->helperText('z. B. jet-tankstelle-fulda — aus der URL: preise-thash-<strong>jet-tankstelle-fulda</strong>')
                        ->extraInputAttributes(['style' => 'font-family: monospace']),

                    Placeholder::make('_bp_station_info')
                        ->label('Verknüpfte Station')
                        ->columnSpanFull()
                        ->content(function ($record) {
                            $hash = $record?->benzinpreis_hash;
                            $slug = $record?->benzinpreis_slug ?? '';
                            if (! $hash) {
                                return new HtmlString('<span style="color:#9ca3af;font-size:13px;">— noch nicht verknüpft</span>');
                            }
                            $url     = 'https://www.benzinpreis-aktuell.de/preise-t' . $hash . '-' . $slug;
                            $cached  = \App\Models\BenzinpreisCache::find($hash);
                            $name    = $cached?->name  ? e($cached->name)  : null;
                            $brand   = $cached?->brand ? e($cached->brand) : null;
                            $e5      = $cached?->e5     ? number_format((float) $cached->e5,     3, ',', '.') . ' €' : null;
                            $diesel  = $cached?->diesel ? number_format((float) $cached->diesel, 3, ',', '.') . ' €' : null;
                            $updated = $cached?->fetched_at?->format('d.m.Y H:i');
                            $label   = $name ?? ($brand ? $brand . ' ' . $slug : $slug);
                            $prices  = collect(['E5' => $e5, 'Diesel' => $diesel])->filter()
                                ->map(fn ($v, $k) => "{$k}: <strong>{$v}</strong>")->implode(' &nbsp;·&nbsp; ');
                            return new HtmlString(
                                '<div style="font-size:13px;line-height:1.8;">'
                                . '<strong>' . $label . '</strong>'
                                . ($prices ? '<br><span style="color:#6b7280;">' . $prices . '</span>' : '')
                                . ($updated ? '<br><span style="color:#9ca3af;font-size:11px;">Letzter Abruf: ' . $updated . '</span>' : '')
                                . '<br><a href="' . e($url) . '" target="_blank" style="font-size:11px;color:#3b82f6;font-family:monospace;">'
                                . e($url) . '</a>'
                                . '</div>'
                            );
                        }),

                    Placeholder::make('prices_updated_at')
                        ->label('Letzte Aktualisierung')
                        ->content(fn($record) => $record?->prices_updated_at?->format('d.m.Y H:i') ?? '—'),

                    Actions::make([
                        Action::make('benzinpreis_discover')
                            ->label('Automatisch suchen')
                            ->icon('heroicon-o-magnifying-glass')
                            ->color('info')
                            ->visible(fn ($record) => $record !== null)
                            ->modalHeading('Hash & Slug automatisch ermitteln')
                            ->modalDescription('PLZ eingeben — alle Tankstellen in der Nähe werden von benzinpreis-aktuell.de abgerufen.')
                            ->modalWidth('lg')
                            ->form([
                                Hidden::make('_bp_json'),

                                TextInput::make('_bp_zip')
                                    ->label('Postleitzahl')
                                    ->placeholder('z. B. 36039')
                                    ->maxLength(5)
                                    ->live(debounce: 800)
                                    ->dehydrated(false)
                                    ->helperText('5-stellige PLZ — Suche startet automatisch nach der Eingabe.')
                                    ->afterStateUpdated(function ($state, Set $set) {
                                        $zip = trim($state ?? '');
                                        if (! preg_match('/^\d{5}$/', $zip)) {
                                            return;
                                        }

                                        try {
                                            $results = app(BenzinpreisService::class)->searchByPlz($zip);
                                        } catch (\Throwable) {
                                            Notification::make()->title('Suche auf benzinpreis-aktuell.de fehlgeschlagen')->warning()->send();
                                            return;
                                        }

                                        if (empty($results)) {
                                            Notification::make()->title("Keine Tankstellen für PLZ {$zip} gefunden")->warning()->send();
                                            $set('_bp_json', null);
                                            $set('_bp_selected', null);
                                            return;
                                        }

                                        $set('_bp_json', json_encode(array_values($results)));
                                        Notification::make()
                                            ->title(count($results) . ' Tankstellen für PLZ ' . $zip . ' gefunden')
                                            ->info()
                                            ->send();
                                    }),

                                Select::make('_bp_selected')
                                    ->label('Tankstelle auswählen')
                                    ->live()
                                    ->placeholder('Erst PLZ eingeben …')
                                    ->options(function (Get $get) {
                                        $json = $get('_bp_json');
                                        if (! $json) return [];
                                        return collect(json_decode($json, true) ?? [])
                                            ->mapWithKeys(function ($s, $i) {
                                                $label = collect([
                                                    $s['name'] ?? null,
                                                    $s['street'] ?? null,
                                                    $s['city'] ?? null,
                                                ])->filter()->implode(' · ');
                                                return [$i => $label];
                                            })->toArray();
                                    })
                                    ->helperText(function (Get $get) {
                                        $json = $get('_bp_json');
                                        $idx  = $get('_bp_selected');
                                        if (! $json || $idx === null) return null;
                                        $s = json_decode($json, true)[$idx] ?? null;
                                        if (! $s) return null;
                                        return new HtmlString(
                                            '<span style="font-family:monospace;font-size:11px;color:#6b7280;">'
                                            . 'hash: <strong>' . ($s['hash'] ?? '—') . '</strong>'
                                            . ' &nbsp;|&nbsp; slug: <strong>' . ($s['slug'] ?? '—') . '</strong>'
                                            . '</span>'
                                        );
                                    }),
                            ])
                            ->modalSubmitActionLabel('Übernehmen')
                            ->action(function (array $data, Set $set, $record) {
                                $idx  = $data['_bp_selected'] ?? null;
                                $json = $data['_bp_json'] ?? null;

                                if ($idx === null || ! $json) {
                                    Notification::make()->title('Bitte eine Tankstelle auswählen')->warning()->send();
                                    return;
                                }

                                $s = json_decode($json, true)[$idx] ?? null;
                                if (! $s || empty($s['hash'])) {
                                    Notification::make()->title('Ungültige Auswahl')->warning()->send();
                                    return;
                                }

                                $hash = $s['hash'];
                                $slug = $s['slug'] ?? '';

                                // Formularfelder setzen
                                $set('benzinpreis_hash', $hash);
                                $set('benzinpreis_slug', $slug);

                                // Koordinaten + Adresse von Benzinpreis holen
                                $details  = app(BenzinpreisService::class)->fetchStationDetails($hash, $slug);
                                $coordsUpdated = false;
                                $dbFields = [
                                    'benzinpreis_hash' => $hash,
                                    'benzinpreis_slug' => $slug,
                                ];

                                if ($details && $details['lat'] && $details['lng']) {
                                    $dbFields['latitude']  = $details['lat'];
                                    $dbFields['longitude'] = $details['lng'];
                                    $set('latitude',  (string) $details['lat']);
                                    $set('longitude', (string) $details['lng']);
                                    $coordsUpdated = true;

                                    // Adresse ergänzen wenn vorhanden und Hausnummer fehlt
                                    if ($record && ! empty($details['street'])) {
                                        $newStreet = trim($details['street'] . ' ' . ($details['house_number'] ?? ''));
                                        if ($newStreet && (! $record->street || ! preg_match('/\d/', $record->street))) {
                                            $dbFields['street'] = $newStreet;
                                            $set('street', $newStreet);
                                        }
                                    }
                                    if ($record && ! empty($details['zip']) && ! $record->zip) {
                                        $dbFields['zip'] = $details['zip'];
                                    }
                                }

                                // Direkt in DB speichern (damit der Wert nicht verloren geht)
                                if ($record) {
                                    $record->update($dbFields);
                                }

                                $body = $s['name'] ?? ($hash . '-' . $slug);
                                if ($coordsUpdated) {
                                    $body .= ' · Koordinaten aktualisiert (' . $details['lat'] . ', ' . $details['lng'] . ')';
                                }

                                Notification::make()
                                    ->title('Verknüpfung gespeichert')
                                    ->body($body)
                                    ->success()
                                    ->send();
                            }),
                    ])->columnSpanFull(),
                ]),
        ];
    }

    public static function fotosSchema(): array
    {
        return [
            Grid::make(2)->schema([
                Section::make('Logo / Titelfoto')
                    ->schema([
                        FileUpload::make('logo_path')
                            ->label('')
                            ->image()->disk('public')->directory('station-logos')
                            ->imageEditor()->maxSize(2048)->nullable(),
                        Placeholder::make('_logo_hint')->hiddenLabel()
                            ->content(new HtmlString('<p style="font-size:12px;color:#9ca3af;">Empfohlen: Quadratisch, max. 2 MB (JPG/PNG)</p>')),
                    ]),
                Section::make('Weitere Fotos')
                    ->schema([
                        FileUpload::make('photos')
                            ->label('')
                            ->image()->disk('public')->directory('station-photos')
                            ->multiple()->maxFiles(10)->maxSize(5120)->nullable(),
                        Placeholder::make('_photos_hint')->hiddenLabel()
                            ->content(new HtmlString('<p style="font-size:12px;color:#9ca3af;">Bis zu 10 Fotos, max. 5 MB je Bild</p>')),
                    ]),
            ]),
        ];
    }

    public static function mapSchema(): array
    {
        return [
            Section::make()
                ->columns(2)
                ->schema([
                    TextInput::make('latitude')
                        ->label('Breitengrad')
                        ->numeric()->step(0.00000001)->nullable(),
                    TextInput::make('longitude')
                        ->label('Laengengrad')
                        ->numeric()->step(0.00000001)->nullable(),
                    Actions::make([
                        Action::make('karte_from_address')
                            ->label('Von Adresse uebernehmen')
                            ->icon('heroicon-o-map-pin')->color('primary')
                            ->action(fn(Get $get, Set $set) => static::geocode($get, $set)),
                        Action::make('karte_refresh')
                            ->label('Aktualisieren')
                            ->icon('heroicon-o-arrow-path')->color('success')
                            ->action(fn() => null),
                    ])->columnSpanFull(),
                    Placeholder::make('_karte_preview')
                        ->label('')
                        ->content(function (Get $get, $record) {
                            $lat         = $get('latitude');
                            $lng         = $get('longitude');
                            $name        = $get('name') ?: 'Station';
                            // Wettbewerber aus DB laden inkl. Benzinpreis-Cache-Preise
                            $competitors = $record
                                ? $record->stationCompetitors()->with('benzinpreisCache')->get()
                                    ->map(fn ($c) => [
                                        'name'        => $c->name,
                                        'brand'       => $c->brand,
                                        'street'      => $c->street,
                                        'city'        => $c->city,
                                        'distance_km' => $c->distance_km,
                                        'lat'         => $c->lat,
                                        'lng'         => $c->lng,
                                        'price_super' => $c->benzinpreisCache?->e5    ? (float) $c->benzinpreisCache->e5    : null,
                                        'price_e10'   => $c->benzinpreisCache?->e10   ? (float) $c->benzinpreisCache->e10   : null,
                                        'price_diesel'=> $c->benzinpreisCache?->diesel ? (float) $c->benzinpreisCache->diesel : null,
                                    ])->toArray()
                                : [];
                            $priceSuper  = $get('price_super');
                            $priceE10    = $get('price_e10');
                            $priceDiesel = $get('price_diesel');

                            $tenantId      = session('tenant_id');
                            $otherStations = \App\Models\Station::where('tenant_id', $tenantId)
                                ->whereNotNull('latitude')->whereNotNull('longitude')
                                ->when($record?->id, fn($q) => $q->where('id', '!=', $record->id))
                                ->get(['id', 'name', 'street', 'house_number', 'city', 'latitude', 'longitude',
                                       'price_super', 'price_e10', 'price_diesel']);

                            if (! $lat || ! $lng) {
                                return new HtmlString('<p class="text-sm text-gray-400 py-4">Koordinaten eingeben oder "Von Adresse übernehmen" klicken.</p>');
                            }
                            return new HtmlString(
                                view('filament.app.components.station-map-preview', [
                                    'lat'           => $lat,
                                    'lng'           => $lng,
                                    'name'          => $name,
                                    'competitors'   => $competitors,
                                    'priceSuper'    => $priceSuper,
                                    'priceE10'      => $priceE10,
                                    'priceDiesel'   => $priceDiesel,
                                    'otherStations' => $otherStations,
                                ])->render()
                            );
                        })->columnSpanFull(),
                    Placeholder::make('_coords_hint')
                        ->hiddenLabel()
                        ->content(function (Get $get) {
                            $lat = $get('latitude');
                            $lng = $get('longitude');
                            if (! $lat || ! $lng) return new HtmlString('');
                            return new HtmlString('<p style="font-size:12px;color:#9ca3af;">Koordinaten: ' . round($lat, 7) . ', ' . round($lng, 7) . '</p>');
                        })->columnSpanFull(),
                ]),

            Section::make('System-Informationen')
                ->icon('heroicon-o-information-circle')
                ->collapsed()
                ->columns(2)
                ->schema([
                    TextInput::make('ulid')->label('ULID')->disabled()->dehydrated(false),
                    Placeholder::make('enriched_at')
                        ->label('Letzter Import')
                        ->content(fn($record) => $record?->enriched_at
                            ? \Carbon\Carbon::parse($record->enriched_at)->format('d.m.Y H:i') : '—'),
                    Placeholder::make('created_at')
                        ->label('Angelegt am')
                        ->content(fn($record) => $record?->created_at?->format('d.m.Y H:i') ?? '—'),
                ]),
        ];
    }

    // ─────────────────────────────────────────────
    // Tabs (Edit-Form + View)
    // ─────────────────────────────────────────────

    public static function stationDataTabs(): array
    {
        return [
            Tab::make('Adresse')->icon('heroicon-o-map-pin')->schema(static::addressSchema()),
            Tab::make('Allgemein')->icon('heroicon-o-building-storefront')->schema(static::generalSchema()),
            Tab::make('Finanzen')->icon('heroicon-o-banknotes')->schema(static::financeSchema()),
            Tab::make('Oeffnungszeiten')->icon('heroicon-o-clock')->schema(static::openingHoursSchema()),
            Tab::make('Shop & Betrieb')->icon('heroicon-o-shopping-bag')->schema(static::shopBetriebSchema()),
            Tab::make('Fotos')->icon('heroicon-o-photo')->schema(static::fotosSchema()),
            Tab::make('Wettbewerb')->icon('heroicon-o-scale')->schema(static::wettbewerbSchema()),
            Tab::make('Karte')->icon('heroicon-o-globe-europe-africa')->schema(static::mapSchema()),
        ];
    }

    // ─────────────────────────────────────────────
    // OSM Öffnungszeiten parsen
    // ─────────────────────────────────────────────

    public static function parseOsmOpeningHours(string $raw): array
    {
        $days = ['monday','tuesday','wednesday','thursday','friday','saturday','sunday'];
        $hours = [];
        foreach ($days as $d) {
            $hours[$d] = ['open' => '06:00', 'close' => '21:00', 'is_closed' => false];
        }

        $raw = trim($raw);
        if (preg_match('/^24\/7$/i', $raw)) {
            return ['is_24h' => true, 'hours' => $hours];
        }

        $dayMap   = ['mo'=>'monday','tu'=>'tuesday','we'=>'wednesday','th'=>'thursday','fr'=>'friday','sa'=>'saturday','su'=>'sunday'];
        $dayOrder = ['mo','tu','we','th','fr','sa','su'];

        foreach (preg_split('/\s*;\s*/', $raw) as $rule) {
            $rule = trim($rule);
            if (! $rule || ! preg_match('/^([A-Za-z,\-]+)\s+(.+)$/', $rule, $m)) continue;

            $daysPart = strtolower($m[1]);
            $timePart = trim($m[2]);
            $affected = [];

            foreach (preg_split('/,/', $daysPart) as $seg) {
                $seg = trim($seg);
                if (str_contains($seg, '-')) {
                    [$from, $to] = explode('-', $seg, 2);
                    $from = strtolower(substr(trim($from), 0, 2));
                    $to   = strtolower(substr(trim($to), 0, 2));
                    $in   = false;
                    foreach ($dayOrder as $dk) {
                        if ($dk === $from) $in = true;
                        if ($in) $affected[] = $dk;
                        if ($dk === $to) $in = false;
                    }
                } else {
                    $dk = strtolower(substr($seg, 0, 2));
                    if (isset($dayMap[$dk])) $affected[] = $dk;
                }
            }

            foreach ($affected as $dk) {
                if (! isset($dayMap[$dk])) continue;
                $d = $dayMap[$dk];
                if (in_array(strtolower($timePart), ['off', 'closed'], true)) {
                    $hours[$d]['is_closed'] = true;
                } elseif (preg_match('/^(\d{2}:\d{2})-(\d{2}:\d{2})$/', $timePart, $tm)) {
                    $hours[$d] = ['open' => $tm[1], 'close' => $tm[2], 'is_closed' => false];
                }
            }
        }

        $all24 = collect($hours)->every(fn($h) => ! ($h['is_closed'] ?? false) && $h['open'] === '00:00' && in_array($h['close'], ['23:59','24:00'], true));

        return ['is_24h' => $all24, 'hours' => $hours];
    }

    // ─────────────────────────────────────────────
    // Tabelle
    // ─────────────────────────────────────────────

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Name')->searchable()->sortable()->weight('medium'),

                TextColumn::make('brand.name')
                    ->label('Marke')->badge()->color('primary')->placeholder('—'),

                TextColumn::make('full_address')
                    ->label('Adresse')
                    ->getStateUsing(fn($record) => $record->full_address)
                    ->searchable(['street', 'zip', 'city'])->placeholder('—'),

                TextColumn::make('zip')->label('PLZ')->sortable()->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('ownership_type')->label('Typ')->badge()
                    ->color(fn(string $state = null) => match($state) {
                        'COCO' => 'success', 'CODO' => 'info', 'DODO' => 'warning', default => 'gray',
                    })->placeholder('—'),

                BooleanColumn::make('is_active')->label('Aktiv')->sortable(),

                BooleanColumn::make('has_coordinates')
                    ->label('Karte')
                    ->getStateUsing(fn($record) => $record->hasCoordinates())
                    ->trueIcon('heroicon-o-map-pin')->falseIcon('heroicon-o-x-mark')
                    ->trueColor('success')->falseColor('gray'),

                TextColumn::make('employees_count')
                    ->label('Mitarbeiter')->counts('employees')->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('is_active')->label('Aktiv'),
                TrashedFilter::make(),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make()->visible(fn() => auth()->user()?->can('partner.stations.edit')),
                DeleteAction::make()->visible(fn() => auth()->user()?->can('partner.stations.delete')),
                RestoreAction::make()->visible(fn() => auth()->user()?->can('partner.stations.delete')),
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
        $zip    = trim($get('zip')    ?? '');
        $city   = trim($get('city')   ?? '');
        $street = trim($get('street') ?? '');

        if (empty($zip) && empty($city)) return;

        $query = http_build_query([
            'q'              => trim($street . ', ' . $zip . ' ' . $city . ', ' . ($get('country') ?: 'DE')),
            'format'         => 'json',
            'limit'          => 1,
            'addressdetails' => 1,
        ]);

        try {
            $response = Http::withHeaders(['User-Agent' => 'Stationpilot/4.0 (contact@stationpilot.de)'])
                ->timeout(5)->get("https://nominatim.openstreetmap.org/search?{$query}");

            if ($response->successful() && ! empty($response->json())) {
                $data = $response->json()[0];
                $lat  = round((float) $data['lat'], 8);
                $lng  = round((float) $data['lon'], 8);
                $set('latitude',  $lat);
                $set('longitude', $lng);

                // Bundesland aus Nominatim-Adressdaten
                $stateName = $data['address']['state'] ?? null;
                if ($stateName) {
                    $code = static::stateNameToCode($stateName);
                    if ($code) $set('state', $code);
                }

                Notification::make()->title('Koordinaten aktualisiert')
                    ->body(new HtmlString('Lat: <b>' . $lat . '</b> · Lng: <b>' . $lng . '</b>'))
                    ->success()->send();
            } else {
                Notification::make()->title('Adresse nicht gefunden')->warning()->send();
            }
        } catch (\Throwable) {
            Notification::make()->title('Geocoding fehlgeschlagen')->danger()->send();
        }
    }

    /**
     * PLZ → Bundesland-Code (lokale Tabelle, kein HTTP-Call).
     * Für sofortige Rückmeldung beim Tippen.
     */
    public static function stateFromZip(string $zip): ?string
    {
        $zip = preg_replace('/\D/', '', $zip);
        if (strlen($zip) !== 5) return null;

        $p = substr($zip, 0, 2); // erste zwei Ziffern

        $map = [
            // Sachsen
            '01' => 'SN', '02' => 'SN', '04' => 'SN', '08' => 'SN', '09' => 'SN',
            // Brandenburg
            '03' => 'BB', '14' => 'BB', '15' => 'BB', '16' => 'BB',
            // Sachsen-Anhalt
            '06' => 'ST', '39' => 'ST',
            // Thüringen
            '07' => 'TH', '98' => 'TH', '99' => 'TH',
            // Berlin
            '10' => 'BE', '11' => 'BE', '12' => 'BE', '13' => 'BE',
            // Mecklenburg-Vorpommern
            '17' => 'MV', '18' => 'MV', '19' => 'MV',
            // Hamburg
            '20' => 'HH', '22' => 'HH',
            // Schleswig-Holstein
            '23' => 'SH', '24' => 'SH', '25' => 'SH',
            // Niedersachsen
            '21' => 'NI', '26' => 'NI', '27' => 'NI', '29' => 'NI',
            '30' => 'NI', '31' => 'NI', '37' => 'NI', '38' => 'NI', '49' => 'NI',
            // Bremen
            '28' => 'HB',
            // Nordrhein-Westfalen
            '32' => 'NW', '33' => 'NW', '40' => 'NW', '41' => 'NW', '42' => 'NW',
            '43' => 'NW', '44' => 'NW', '45' => 'NW', '46' => 'NW', '47' => 'NW',
            '48' => 'NW', '50' => 'NW', '51' => 'NW', '52' => 'NW', '53' => 'NW',
            '57' => 'NW', '58' => 'NW', '59' => 'NW',
            // Hessen
            '34' => 'HE', '35' => 'HE', '36' => 'HE', '60' => 'HE', '61' => 'HE',
            '63' => 'HE', '64' => 'HE', '65' => 'HE',
            // Rheinland-Pfalz
            '54' => 'RP', '55' => 'RP', '56' => 'RP', '67' => 'RP', '76' => 'RP',
            // Saarland
            '66' => 'SL',
            // Baden-Württemberg
            '68' => 'BW', '69' => 'BW', '70' => 'BW', '71' => 'BW', '72' => 'BW',
            '73' => 'BW', '74' => 'BW', '75' => 'BW', '77' => 'BW', '78' => 'BW',
            '79' => 'BW', '88' => 'BW', '89' => 'BW',
            // Bayern
            '80' => 'BY', '81' => 'BY', '82' => 'BY', '83' => 'BY', '84' => 'BY',
            '85' => 'BY', '86' => 'BY', '87' => 'BY', '90' => 'BY', '91' => 'BY',
            '92' => 'BY', '93' => 'BY', '94' => 'BY', '95' => 'BY', '96' => 'BY',
            '97' => 'BY',
        ];

        return $map[$p] ?? null;
    }

    /**
     * Nominatim-Staatsname → zweistelligen Bundesland-Code.
     */
    protected static function stateNameToCode(string $name): ?string
    {
        $map = [
            'Baden-Württemberg'      => 'BW',
            'Bayern'                 => 'BY',
            'Bavaria'                => 'BY',
            'Berlin'                 => 'BE',
            'Brandenburg'            => 'BB',
            'Bremen'                 => 'HB',
            'Hamburg'                => 'HH',
            'Hessen'                 => 'HE',
            'Hesse'                  => 'HE',
            'Mecklenburg-Vorpommern' => 'MV',
            'Niedersachsen'          => 'NI',
            'Lower Saxony'           => 'NI',
            'Nordrhein-Westfalen'    => 'NW',
            'North Rhine-Westphalia' => 'NW',
            'Rheinland-Pfalz'        => 'RP',
            'Rhineland-Palatinate'   => 'RP',
            'Saarland'               => 'SL',
            'Sachsen'                => 'SN',
            'Saxony'                 => 'SN',
            'Sachsen-Anhalt'         => 'ST',
            'Saxony-Anhalt'          => 'ST',
            'Schleswig-Holstein'     => 'SH',
            'Thüringen'              => 'TH',
            'Thuringia'              => 'TH',
        ];

        return $map[$name] ?? null;
    }
}
