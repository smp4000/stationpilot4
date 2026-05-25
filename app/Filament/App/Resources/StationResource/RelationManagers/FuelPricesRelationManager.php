<?php

namespace App\Filament\App\Resources\StationResource\RelationManagers;

use App\Models\StationFuelPrice;
use App\Services\BenzinpreisParser;
use App\Services\BenzinpreisService;
use Illuminate\Support\HtmlString;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class FuelPricesRelationManager extends RelationManager
{
    protected static string $relationship = 'fuelPrices';

    protected static ?string $title = 'Kraftstoffpreise';

    protected static ?string $modelLabel = 'Preis';

    protected static ?string $pluralModelLabel = 'Preise';

    public function isReadOnly(): bool
    {
        return false;
    }

    // ─────────────────────────────────────────────
    // Form
    // ─────────────────────────────────────────────

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            DateTimePicker::make('recorded_at')
                ->label('Zeitpunkt')
                ->required()
                ->default(now())
                ->displayFormat('d.m.Y H:i'),

            TextInput::make('e5')
                ->label('Super E5 (€/L)')
                ->numeric()
                ->step(0.001)
                ->nullable(),

            TextInput::make('e10')
                ->label('Super E10 (€/L)')
                ->numeric()
                ->step(0.001)
                ->nullable(),

            TextInput::make('diesel')
                ->label('Diesel (€/L)')
                ->numeric()
                ->step(0.001)
                ->nullable(),

            TextInput::make('lpg')
                ->label('LPG (€/L)')
                ->numeric()
                ->step(0.001)
                ->nullable(),

            Select::make('source')
                ->label('Quelle')
                ->options(StationFuelPrice::sourceOptions())
                ->default('manual')
                ->required(),

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
        $station = $this->getOwnerRecord();
        $addressParts = collect([
            $station?->street,
            trim(($station?->zip ?? '') . ' ' . ($station?->city ?? '')),
        ])->filter()->implode(' · ');

        return $table
            ->recordTitleAttribute('recorded_at')
            ->defaultSort('recorded_at', 'desc')
            ->heading($station?->name ?? 'Kraftstoffpreise')
            ->description($addressParts ?: null)
            ->columns([
                TextColumn::make('recorded_at')
                    ->label('Zeitpunkt')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),

                TextColumn::make('e5')
                    ->label('E5')
                    ->formatStateUsing(fn ($state) => $state ? number_format((float) $state, 3, ',', '.') . ' €' : '–'),

                TextColumn::make('e10')
                    ->label('E10')
                    ->formatStateUsing(fn ($state) => $state ? number_format((float) $state, 3, ',', '.') . ' €' : '–'),

                TextColumn::make('diesel')
                    ->label('Diesel')
                    ->formatStateUsing(fn ($state) => $state ? number_format((float) $state, 3, ',', '.') . ' €' : '–'),

                TextColumn::make('lpg')
                    ->label('LPG')
                    ->formatStateUsing(fn ($state) => $state ? number_format((float) $state, 3, ',', '.') . ' €' : '–'),

                TextColumn::make('source')
                    ->label('Quelle')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'api'     => 'success',
                        'scraper' => 'info',
                        'import'  => 'warning',
                        default   => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => StationFuelPrice::sourceOptions()[$state] ?? $state),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Manuell eintragen'),

                Action::make('fetch_prices')
                    ->label('Jetzt abrufen')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->action(function () {
                        $station = $this->getOwnerRecord();
                        $hash    = $station->benzinpreis_hash;
                        $slug    = $station->benzinpreis_slug ?? '';

                        // ── Kein Hash gesetzt ──────────────────────────────
                        if (! $hash) {
                            Notification::make()
                                ->title('Kein Hash konfiguriert')
                                ->body('Bitte zuerst per „Automatisch suchen" oder manuell den benzinpreis-aktuell.de Hash eintragen.')
                                ->warning()
                                ->persistent()
                                ->send();
                            return;
                        }

                        $url    = 'https://www.benzinpreis-aktuell.de/preise-t' . $hash . '-' . $slug;
                        $parser = app(BenzinpreisParser::class);

                        // ── Schritt 1: Seite laden ─────────────────────────
                        $html = $parser->fetchUrl($url, 12);

                        if (! $html) {
                            Notification::make()
                                ->title('Seite nicht erreichbar')
                                ->body(new HtmlString(
                                    '<code style="font-size:11px;word-break:break-all;">' . e($url) . '</code><br>'
                                    . '<span style="color:#ef4444;">Keine Antwort vom Server — SSL-Problem oder URL falsch?</span>'
                                ))
                                ->danger()
                                ->persistent()
                                ->send();
                            return;
                        }

                        // ── Schritt 2: Station parsen ──────────────────────
                        $stationData = $parser->parseStationPage($html);
                        $mtsUuid     = $stationData['mts_uuid'] ?? null;
                        $htmlPrices  = $stationData['prices'] ?? [];

                        // ── Schritt 3: JSON-API versuchen ──────────────────
                        $apiPrices = null;
                        if ($mtsUuid) {
                            $apiPrices = $parser->fetchPriceByApi($mtsUuid);
                        }

                        // ── Schritt 4: Preis-Array zusammenbauen ───────────
                        $prices = null;
                        $source = null;

                        if ($apiPrices && ($apiPrices['e5'] || $apiPrices['diesel'])) {
                            $prices = $apiPrices;
                            $source = 'api';
                        } elseif (! empty($htmlPrices)) {
                            $prices = [
                                'e5'     => isset($htmlPrices['benzin']) ? (float) $htmlPrices['benzin']
                                          : (isset($htmlPrices['e5'])    ? (float) $htmlPrices['e5']    : null),
                                'e10'    => isset($htmlPrices['e10'])    ? (float) $htmlPrices['e10']   : null,
                                'diesel' => isset($htmlPrices['diesel']) ? (float) $htmlPrices['diesel']: null,
                            ];
                            $source = 'scraper';
                        }

                        // ── Debug-Notification ─────────────────────────────
                        $fmt = fn ($v) => $v ? number_format((float) $v, 3, ',', '.') . ' €' : '<em>—</em>';

                        $rows = [
                            ['🔗 URL',        '<code style="font-size:10px;word-break:break-all;">' . e($url) . '</code>'],
                            ['📄 Seitenname', e($stationData['name'] ?: '—')],
                            ['🔑 MTS-UUID',   $mtsUuid ? '<code>' . e($mtsUuid) . '</code>' : '<span style="color:#f59e0b;">nicht gefunden</span>'],
                            ['🌐 HTML E5',    $fmt($htmlPrices['benzin'] ?? $htmlPrices['e5'] ?? null)],
                            ['🌐 HTML E10',   $fmt($htmlPrices['e10'] ?? null)],
                            ['🌐 HTML Diesel',$fmt($htmlPrices['diesel'] ?? null)],
                            ['⚡ API E5',     $apiPrices ? $fmt($apiPrices['e5']) : '<em>—</em>'],
                            ['⚡ API E10',    $apiPrices ? $fmt($apiPrices['e10']) : '<em>—</em>'],
                            ['⚡ API Diesel', $apiPrices ? $fmt($apiPrices['diesel']) : '<em>—</em>'],
                            ['💾 Quelle',     $source ? '<strong>' . $source . '</strong>' : '<span style="color:#ef4444;">kein Preis ermittelt</span>'],
                        ];

                        $tableHtml = '<table style="font-size:12px;border-collapse:collapse;width:100%;">'
                            . implode('', array_map(
                                fn ($r) => '<tr><td style="padding:2px 8px 2px 0;color:#6b7280;white-space:nowrap;">' . $r[0] . '</td>'
                                         . '<td style="padding:2px 0;">' . $r[1] . '</td></tr>',
                                $rows
                            ))
                            . '</table>';

                        if ($prices && ($prices['e5'] || $prices['diesel'])) {
                            // Speichern
                            $record = StationFuelPrice::create([
                                'station_id'  => $station->id,
                                'e5'          => $prices['e5']     ?? null,
                                'e10'         => $prices['e10']    ?? null,
                                'diesel'      => $prices['diesel'] ?? null,
                                'source'      => $source,
                                'recorded_at' => now(),
                            ]);
                            $station->update([
                                'price_super'       => $prices['e5']     ?? $station->price_super,
                                'price_e10'         => $prices['e10']    ?? $station->price_e10,
                                'price_diesel'      => $prices['diesel'] ?? $station->price_diesel,
                                'prices_updated_at' => now(),
                            ]);

                            Notification::make()
                                ->title('✅ Preise gespeichert')
                                ->body(new HtmlString($tableHtml))
                                ->success()
                                ->persistent()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('⚠️ Kein Preis ermittelt')
                                ->body(new HtmlString($tableHtml))
                                ->warning()
                                ->persistent()
                                ->send();
                        }
                    }),
            ])
            ->actions([
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
