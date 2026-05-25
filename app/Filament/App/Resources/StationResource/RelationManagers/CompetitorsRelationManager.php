<?php

namespace App\Filament\App\Resources\StationResource\RelationManagers;

use App\Models\BenzinpreisCache;
use App\Models\StationCompetitor;
use App\Services\BenzinpreisParser;
use App\Services\BenzinpreisService;
use App\Services\OverpassService;
use Illuminate\Support\HtmlString;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CompetitorsRelationManager extends RelationManager
{
    protected static string $relationship = 'stationCompetitors';

    protected static ?string $title = 'Wettbewerber';

    protected static ?string $modelLabel = 'Wettbewerber';

    protected static ?string $pluralModelLabel = 'Wettbewerber';

    // Aktionen auch auf der Ansehen-Seite erlauben
    public function isReadOnly(): bool
    {
        return false;
    }

    // ─────────────────────────────────────────────
    // Form (for CreateAction modal)
    // ─────────────────────────────────────────────

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->label('Name')
                ->required(),

            TextInput::make('brand')
                ->label('Marke')
                ->nullable(),

            Grid::make(2)->schema([
                TextInput::make('street')
                    ->label('Straße')
                    ->nullable(),
                TextInput::make('city')
                    ->label('Stadt')
                    ->nullable(),
            ]),

            TextInput::make('distance_km')
                ->label('Entfernung (km)')
                ->numeric()
                ->nullable()
                ->suffix('km'),

            Grid::make(2)->schema([
                TextInput::make('lat')
                    ->label('Breitengrad')
                    ->numeric()
                    ->nullable(),
                TextInput::make('lng')
                    ->label('Längengrad')
                    ->numeric()
                    ->nullable(),
            ]),

            Textarea::make('notes')
                ->label('Notizen')
                ->rows(2)
                ->nullable(),
        ]);
    }

    // ─────────────────────────────────────────────
    // Table
    // ─────────────────────────────────────────────

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->defaultSort('distance_km')
            ->columns([
                TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->weight('medium'),

                TextColumn::make('brand')
                    ->label('Marke')
                    ->badge()
                    ->color('primary')
                    ->placeholder('—'),

                TextColumn::make('street')
                    ->label('Adresse')
                    ->formatStateUsing(fn ($state, $record) => collect([$record->street, $record->city])->filter()->implode(', '))
                    ->placeholder('—'),

                TextColumn::make('distance_km')
                    ->label('Entfernung')
                    ->formatStateUsing(fn ($state) => $state !== null ? number_format((float) $state, 1, ',', '.') . ' km' : '—')
                    ->sortable(),

                TextColumn::make('benzinpreisCache.e5')
                    ->label('E5 (BP)')
                    ->formatStateUsing(fn ($state) => $state ? number_format((float) $state, 3, ',', '.') . ' €' : '—')
                    ->placeholder('—')
                    ->color('gray'),

                TextColumn::make('benzinpreisCache.diesel')
                    ->label('Diesel (BP)')
                    ->formatStateUsing(fn ($state) => $state ? number_format((float) $state, 3, ',', '.') . ' €' : '—')
                    ->placeholder('—')
                    ->color('gray'),

                TextColumn::make('notes')
                    ->label('Notizen')
                    ->limit(40)
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Manuell hinzufügen'),

                Action::make('search_osm')
                    ->label('Per OSM-Suche hinzufügen')
                    ->icon('heroicon-o-magnifying-glass')
                    ->color('info')
                    ->modalHeading('Wettbewerber per OpenStreetMap suchen')
                    ->modalWidth('xl')
                    ->form([
                        Hidden::make('_osm_json'),

                        TextInput::make('_zip')
                            ->label('PLZ')
                            ->placeholder('z. B. 36039')
                            ->maxLength(5)
                            ->live(debounce: 600)
                            ->dehydrated(false)
                            ->helperText('5-stellige PLZ eingeben — Suche startet automatisch.')
                            ->afterStateUpdated(function ($state, Set $set) {
                                $zip = trim($state ?? '');
                                if (! preg_match('/^\d{5}$/', $zip)) return;

                                try {
                                    $results = app(OverpassService::class)->searchFuelStationsByZip($zip);
                                } catch (\Throwable) {
                                    Notification::make()->title('OSM-Suche fehlgeschlagen')->warning()->send();
                                    return;
                                }

                                if (empty($results)) {
                                    Notification::make()->title("Keine Tankstellen für PLZ {$zip} gefunden")->warning()->send();
                                    $set('_osm_json', null);
                                    return;
                                }

                                $set('_osm_json', json_encode(array_values($results)));
                                Notification::make()->title(count($results) . ' Stationen in PLZ ' . $zip . ' gefunden')->info()->send();
                            }),

                        Select::make('_selected')
                            ->label('Tankstelle auswählen')
                            ->live()
                            ->placeholder('Erst PLZ eingeben …')
                            ->options(function (Get $get) {
                                $json = $get('_osm_json');
                                if (! $json) return [];
                                return collect(json_decode($json, true) ?? [])
                                    ->mapWithKeys(function ($s, $i) {
                                        $label = collect([
                                            $s['brand'] ?? null,
                                            $s['name'] ?? null,
                                            trim(($s['street'] ?? '') . ' ' . ($s['house_number'] ?? '')),
                                            trim(($s['zip'] ?? '') . ' ' . ($s['city'] ?? '')),
                                        ])->filter()->implode(' · ');
                                        return [$i => $label];
                                    })->toArray();
                            }),

                        Textarea::make('notes')
                            ->label('Notizen')
                            ->rows(2)
                            ->nullable(),
                    ])
                    ->modalSubmitActionLabel('Hinzufügen')
                    ->action(function (array $data, RelationManager $livewire) {
                        $idx  = $data['_selected'] ?? null;
                        $json = $data['_osm_json'] ?? null;
                        if ($idx === null || ! $json) {
                            Notification::make()->title('Bitte eine Tankstelle auswählen')->warning()->send();
                            return;
                        }

                        $s = json_decode($json, true)[$idx] ?? null;
                        if (! $s) return;

                        $station = $livewire->getOwnerRecord();

                        // Entfernung berechnen (Haversine)
                        $ownLat = (float) $station->latitude;
                        $ownLng = (float) $station->longitude;
                        $distKm = null;
                        if ($ownLat && $ownLng && ! empty($s['lat']) && ! empty($s['lng'])) {
                            $dLat   = deg2rad($s['lat'] - $ownLat);
                            $dLng   = deg2rad($s['lng'] - $ownLng);
                            $a      = sin($dLat / 2) ** 2 + cos(deg2rad($ownLat)) * cos(deg2rad($s['lat'])) * sin($dLng / 2) ** 2;
                            $distKm = round(6371 * 2 * atan2(sqrt($a), sqrt(1 - $a)), 1);
                        }

                        StationCompetitor::create([
                            'station_id'  => $station->id,
                            'tenant_id'   => $station->tenant_id,
                            'name'        => $s['name'] ?? ($s['brand'] ?? ''),
                            'brand'       => $s['brand'] ?? null,
                            'street'      => trim(($s['street'] ?? '') . ' ' . ($s['house_number'] ?? '')),
                            'city'        => $s['city'] ?? null,
                            'zip'         => $s['zip'] ?? null,
                            'lat'         => ! empty($s['lat']) ? (float) $s['lat'] : null,
                            'lng'         => ! empty($s['lng']) ? (float) $s['lng'] : null,
                            'distance_km' => $distKm,
                            'osm_id'      => $s['osm_id'] ?? null,
                            'notes'       => $data['notes'] ?? null,
                        ]);

                        Notification::make()
                            ->title('Wettbewerber hinzugefügt: ' . ($s['name'] ?? $s['brand'] ?? ''))
                            ->body($distKm ? $distKm . ' km Entfernung' : null)
                            ->success()
                            ->send();
                    }),

                Action::make('discover_bp')
                    ->label('Wettbewerber entdecken')
                    ->icon('heroicon-o-globe-alt')
                    ->color('warning')
                    ->modalHeading('Wettbewerber automatisch entdecken')
                    ->modalDescription(new \Illuminate\Support\HtmlString(
                        'Crawlt von der eigenen Stationsseite auf benzinpreis-aktuell.de outward und findet alle Nachbar-Tankstellen inkl. Entfernung.<br>'
                        . '<strong>Voraussetzung:</strong> Hash & Slug müssen unter „Preisdaten-Verknüpfung" gesetzt sein.<br>'
                        . '<span style="color:#92400e;">⏱ Kann 20–60 Sekunden dauern.</span>'
                    ))
                    ->modalWidth('sm')
                    ->requiresConfirmation()
                    ->modalSubmitActionLabel('Jetzt entdecken')
                    ->action(function (RelationManager $livewire) {
                        $station = $livewire->getOwnerRecord();

                        if (! $station->benzinpreis_hash) {
                            Notification::make()
                                ->title('Kein Hash konfiguriert')
                                ->body('Bitte zuerst Hash & Slug unter „Preisdaten-Verknüpfung" per „Automatisch suchen" setzen.')
                                ->warning()
                                ->send();
                            return;
                        }

                        try {
                            $discovered = app(BenzinpreisService::class)
                                ->discoverNeighbors($station, levels: 1, radiusKm: 15);
                        } catch (\Throwable $e) {
                            Notification::make()
                                ->title('Discovery fehlgeschlagen')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                            return;
                        }

                        if (empty($discovered)) {
                            Notification::make()
                                ->title('Keine Nachbarn gefunden')
                                ->body('Seite nicht erreichbar oder keine Stationen im 15-km-Umkreis.')
                                ->warning()
                                ->send();
                            return;
                        }

                        $added   = 0;
                        $skipped = 0;

                        foreach ($discovered as $s) {
                            // Skip if already exists (same lat/lng or same benzinpreis slug in notes)
                            $exists = \App\Models\StationCompetitor::where('station_id', $station->id)
                                ->where(function ($q) use ($s) {
                                    $q->where('benzinpreis_hash', $s['hash'])
                                      ->orWhere('notes', 'like', '%bp:' . $s['hash'] . '%');
                                    if ($s['lat'] && $s['lng']) {
                                        $q->orWhere(function ($q2) use ($s) {
                                            $q2->whereBetween('lat', [$s['lat'] - 0.001, $s['lat'] + 0.001])
                                               ->whereBetween('lng', [$s['lng'] - 0.001, $s['lng'] + 0.001]);
                                        });
                                    }
                                })
                                ->exists();

                            if ($exists) {
                                $skipped++;
                                continue;
                            }

                            \App\Models\StationCompetitor::create([
                                'station_id'  => $station->id,
                                'tenant_id'   => $station->tenant_id,
                                'name'        => $s['name'] ?: ($s['brand'] ?: $s['slug']),
                                'brand'       => $s['brand'] ?: null,
                                'street'      => $s['street'] ?: null,
                                'city'        => $s['city'] ?: null,
                                'zip'         => $s['zip'] ?: null,
                                'lat'         => $s['lat'],
                                'lng'         => $s['lng'],
                                'distance_km'      => $s['distance_km'],
                                'benzinpreis_hash' => $s['hash'],
                                'benzinpreis_slug' => $s['slug'] ?? null,
                                'notes'            => null,
                            ]);
                            $added++;
                        }

                        Notification::make()
                            ->title($added . ' Wettbewerber entdeckt und eingetragen')
                            ->body(
                                $skipped ? "{$skipped} bereits vorhandene übersprungen." : null
                            )
                            ->success()
                            ->send();
                    }),

                Action::make('fetch_competitor_prices')
                    ->label('Preise abrufen')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->action(function (RelationManager $livewire) {
                        $station     = $livewire->getOwnerRecord();
                        $competitors = $station->stationCompetitors()
                            ->whereNotNull('benzinpreis_hash')
                            ->where('benzinpreis_hash', '!=', '')
                            ->orderBy('distance_km')
                            ->get();

                        if ($competitors->isEmpty()) {
                            Notification::make()
                                ->title('Keine Wettbewerber mit Preisverknüpfung')
                                ->body('Erst „Wettbewerber entdecken" nutzen – dadurch werden Hashes automatisch gesetzt.')
                                ->warning()
                                ->send();
                            return;
                        }

                        $parser  = app(BenzinpreisParser::class);
                        $fetched = 0;
                        $skipped = 0;
                        $failed  = 0;
                        $now     = now();
                        $fmt     = fn ($v) => $v ? number_format((float) $v, 3, ',', '.') . '&nbsp;€' : '<em style="color:#9ca3af;">—</em>';
                        $rows    = [];

                        foreach ($competitors as $comp) {
                            $hash = $comp->benzinpreis_hash;
                            $slug = $comp->benzinpreis_slug ?? '';
                            $name = $comp->name ?: $hash;

                            // Cache-Check: wenn < 3h alt überspringen
                            $cached = BenzinpreisCache::find($hash);
                            if ($cached?->fetched_at && $cached->fetched_at->diffInMinutes($now) < 180) {
                                $skipped++;
                                $rows[] = [
                                    'name'   => e($name),
                                    'e5'     => $fmt($cached->e5),
                                    'diesel' => $fmt($cached->diesel),
                                    'status' => '<span style="color:#6b7280;font-size:10px;">Cache</span>',
                                ];
                                continue;
                            }

                            $stationData = $parser->fetchStation($hash, $slug);
                            if (! $stationData) {
                                $failed++;
                                $rows[] = [
                                    'name'   => e($name),
                                    'e5'     => '—',
                                    'diesel' => '—',
                                    'status' => '<span style="color:#ef4444;font-size:10px;">❌ Fehler</span>',
                                ];
                                usleep(300_000);
                                continue;
                            }

                            $prices = null;
                            $source = 'HTML';

                            if ($stationData['mts_uuid']) {
                                $api = $parser->fetchPriceByApi($stationData['mts_uuid']);
                                if ($api && ($api['e5'] || $api['diesel'])) {
                                    $prices = $api;
                                    $source = 'API';
                                }
                                usleep(200_000);
                            }

                            if (! $prices && ! empty($stationData['prices'])) {
                                $p      = $stationData['prices'];
                                $prices = [
                                    'e5'     => isset($p['benzin']) ? (float) $p['benzin'] : (isset($p['e5'])    ? (float) $p['e5']    : null),
                                    'e10'    => isset($p['e10'])    ? (float) $p['e10']   : null,
                                    'diesel' => isset($p['diesel']) ? (float) $p['diesel'] : null,
                                ];
                            }

                            if ($prices && ($prices['e5'] || $prices['diesel'])) {
                                BenzinpreisCache::updateOrCreate(
                                    ['hash' => $hash],
                                    [
                                        'slug'       => $slug ?: ($cached?->slug ?? $hash),
                                        'mts_uuid'   => $stationData['mts_uuid'] ?? $cached?->mts_uuid,
                                        'name'       => $stationData['name'] ?: ($cached?->name ?? $name),
                                        'e5'         => $prices['e5']     ?? null,
                                        'e10'        => $prices['e10']    ?? null,
                                        'diesel'     => $prices['diesel'] ?? null,
                                        'fetched_at' => $now,
                                    ]
                                );
                                $fetched++;
                                $rows[] = [
                                    'name'   => e($stationData['name'] ?: $name),
                                    'e5'     => $fmt($prices['e5'] ?? null),
                                    'diesel' => $fmt($prices['diesel'] ?? null),
                                    'status' => '<span style="color:#16a34a;font-size:10px;">✓ ' . $source . '</span>',
                                ];
                            } else {
                                $failed++;
                                $rows[] = [
                                    'name'   => e($name),
                                    'e5'     => '—',
                                    'diesel' => '—',
                                    'status' => '<span style="color:#f59e0b;font-size:10px;">⚠ Kein Preis</span>',
                                ];
                            }

                            usleep(400_000);
                        }

                        // Build result table
                        $tableHtml = '<table style="font-size:12px;border-collapse:collapse;width:100%;margin-top:6px;">'
                            . '<tr style="color:#6b7280;font-size:10px;">'
                            . '<th style="text-align:left;padding:2px 6px 2px 0;">Station</th>'
                            . '<th style="text-align:right;padding:2px 4px;">E5</th>'
                            . '<th style="text-align:right;padding:2px 4px;">Diesel</th>'
                            . '<th style="padding:2px 0 2px 6px;"></th>'
                            . '</tr>'
                            . implode('', array_map(
                                fn ($r) => '<tr>'
                                    . '<td style="padding:2px 6px 2px 0;max-width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">' . $r['name'] . '</td>'
                                    . '<td style="text-align:right;padding:2px 4px;white-space:nowrap;">' . $r['e5'] . '</td>'
                                    . '<td style="text-align:right;padding:2px 4px;white-space:nowrap;">' . $r['diesel'] . '</td>'
                                    . '<td style="padding:2px 0 2px 6px;">' . $r['status'] . '</td>'
                                    . '</tr>',
                                $rows
                            ))
                            . '</table>';

                        $summary = collect([
                            $fetched ? "<strong>{$fetched} aktualisiert</strong>" : null,
                            $skipped ? "{$skipped} aus Cache" : null,
                            $failed  ? "<span style='color:#ef4444;'>{$failed} fehlgeschlagen</span>" : null,
                        ])->filter()->implode(' · ');

                        Notification::make()
                            ->title('Wettbewerber-Preise abgerufen')
                            ->body(new HtmlString($summary . $tableHtml))
                            ->success()
                            ->persistent()
                            ->send();
                    }),
            ])
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
}
