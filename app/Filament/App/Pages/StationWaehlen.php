<?php

namespace App\Filament\App\Pages;

use App\Models\Employee;
use App\Models\Station;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

/**
 * Stationsauswahl für Mitarbeiter.
 * Erste Anmeldung: Station wählen.
 * Während der Schicht: Station wechseln mit Bestätigung.
 */
class StationWaehlen extends Page
{
    protected string $view = 'filament.app.pages.station-waehlen';

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-map-pin';

    protected static ?string $navigationLabel = 'Station wählen';

    protected static ?string $title = 'Station wählen';

    protected static ?string $slug = 'station-waehlen';

    protected static ?int $navigationSort = 0;

    public static function canAccess(): bool
    {
        return auth()->user()?->isEmployee() ?? false;
    }

    // Warte auf Bestätigung beim Stationswechsel
    public ?string $pendingStationId = null;

    // ─── Stationen ──────────────────────────────────────────────────────────

    public function getStations(): \Illuminate\Support\Collection
    {
        $employee = Employee::where('user_id', auth()->id())->first();
        if (! $employee) {
            return collect();
        }

        $stations = $employee->stations()->where('is_active', true)->get();

        if ($employee->station && ! $stations->contains('id', $employee->station->id)) {
            $stations->prepend($employee->station);
        }

        return $stations;
    }

    public function getActiveStation(): ?Station
    {
        $id = session('active_station_id');
        return $id ? Station::find($id) : null;
    }

    // ─── Station wählen / wechseln ───────────────────────────────────────────

    public function selectStation(string $stationId): void
    {
        $employee = Employee::where('user_id', auth()->id())->first();
        if (! $employee) return;

        $allowed = $employee->stations()->where('gas_stations.id', $stationId)->exists()
            || $employee->station_id === $stationId;

        if (! $allowed) {
            Notification::make()->title('Keine Berechtigung für diese Station.')->danger()->send();
            return;
        }

        $activeId = session('active_station_id');

        // Wenn bereits eine andere Station aktiv → Bestätigung anfordern
        if ($activeId && $activeId !== $stationId) {
            $this->pendingStationId = $stationId;
            return; // Blade zeigt jetzt den Bestätigungsdialog
        }

        $this->doSelectStation($stationId);
    }

    public function confirmSwitch(): void
    {
        if (! $this->pendingStationId) return;
        $this->doSelectStation($this->pendingStationId);
        $this->pendingStationId = null;
    }

    public function cancelSwitch(): void
    {
        $this->pendingStationId = null;
    }

    private function doSelectStation(string $stationId): void
    {
        $station = Station::find($stationId);

        session(['active_station_id' => $stationId]);

        Notification::make()
            ->title('⛽ ' . $station?->name)
            ->body('Tankstelle ausgewählt.')
            ->success()
            ->send();

        $this->redirect(url('/app'));
    }

    // ─── Station verlassen ──────────────────────────────────────────────────

    public function clearStation(): void
    {
        $station = $this->getActiveStation();
        session()->forget('active_station_id');

        Notification::make()
            ->title('Tankstelle abgemeldet')
            ->body($station ? 'Sie haben ' . $station->name . ' verlassen.' : '')
            ->info()
            ->send();
    }
}
