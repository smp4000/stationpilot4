<?php
namespace App\Filament\App\Pages;

use App\Models\Employee;
use App\Models\StationCredential;
use Filament\Pages\Page;

class MeineZugangsdaten extends Page
{
    protected string $view = 'filament.app.pages.meine-zugangsdaten';

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-lock-closed';

    protected static ?string $navigationLabel = 'Zugangsdaten';

    protected static ?string $title = 'Zugangsdaten';

    protected static ?string $slug = 'meine-zugangsdaten';

    protected static ?int $navigationSort = 11;

    public static function canAccess(): bool
    {
        return auth()->user()?->isEmployee() ?? false;
    }

    public function getEmployee(): ?Employee
    {
        return Employee::where('user_id', auth()->id())->first();
    }

    public function getCredentials(): \Illuminate\Support\Collection
    {
        $employee  = $this->getEmployee();
        if (! $employee) return collect();

        $activeStationId = session('active_station_id');

        return StationCredential::where('employee_id', $employee->id)
            ->where('is_active', true)
            ->where(function ($q) use ($activeStationId) {
                // Einträge ohne Stationszuordnung (gilt für alle Stationen)
                $q->doesntHave('stations');
                // … oder zur aktiven Station gehörend
                if ($activeStationId) {
                    $q->orWhereHas('stations', fn ($sq) => $sq->where('gas_stations.id', $activeStationId));
                }
            })
            ->with('stations')
            ->orderBy('type')
            ->orderBy('label')
            ->get();
    }

    // Revealed state per credential id
    public array $revealed = [];

    public function toggleReveal(int $credentialId): void
    {
        if (isset($this->revealed[$credentialId])) {
            unset($this->revealed[$credentialId]);
        } else {
            $this->revealed[$credentialId] = true;
        }
    }
}
