<?php

namespace App\Filament\App\Pages;

use App\Models\Employee;
use App\Models\Station;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

/**
 * Stationsauswahl für Mitarbeiter – muss vor jeder Arbeitssession gewählt werden.
 * Nur sichtbar für User vom Typ 'employee'.
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

    // ─── Stationen des Mitarbeiters ──────────────────────────────────────────

    public function getStations(): \Illuminate\Support\Collection
    {
        $employee = Employee::where('user_id', auth()->id())->first();
        if (! $employee) {
            return collect();
        }

        // Primärstation + zugewiesene Stationen
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

    // ─── Station aktivieren ──────────────────────────────────────────────────

    public function selectStation(string $stationId): void
    {
        $employee = Employee::where('user_id', auth()->id())->first();
        if (! $employee) {
            return;
        }

        // Prüfen ob Mitarbeiter dieser Station zugeordnet ist
        $allowed = $employee->stations()->where('stations.id', $stationId)->exists()
            || $employee->station_id === $stationId;

        if (! $allowed) {
            Notification::make()->title('Keine Berechtigung für diese Station.')->danger()->send();
            return;
        }

        session(['active_station_id' => $stationId]);

        $station = Station::find($stationId);

        Notification::make()
            ->title('Station gewählt: ' . $station?->name)
            ->success()
            ->send();

        $this->redirect(url('/app'));
    }

    public function clearStation(): void
    {
        session()->forget('active_station_id');
        Notification::make()->title('Station abgemeldet.')->info()->send();
    }
}
