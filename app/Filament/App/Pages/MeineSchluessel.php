<?php
namespace App\Filament\App\Pages;

use App\Models\Employee;
use App\Models\KeyHandover;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class MeineSchluessel extends Page
{
    protected string $view = 'filament.app.pages.meine-schluessel';

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-key';

    protected static ?string $navigationLabel = 'Meine Schlüssel';

    protected static ?string $title = 'Meine Schlüssel';

    protected static ?string $slug = 'meine-schluessel';

    protected static ?int $navigationSort = 10;

    public static function canAccess(): bool
    {
        return auth()->user()?->isEmployee() ?? false;
    }

    // Welche Karte gerade im Unterzeichnungs-Modus ist
    public ?int   $signingId   = null;  // handover ID
    public string $signingMode = '';    // 'receipt' | 'return'

    public function getHandovers(): \Illuminate\Support\Collection
    {
        $employee = Employee::where('user_id', auth()->id())->first();
        if (! $employee) return collect();

        return KeyHandover::with(['key.station'])
            ->where('employee_id', $employee->id)
            ->where(function ($q) {
                $q->whereNull('returned_at')
                  ->whereNull('employee_returned_at');
            })
            ->orderByDesc('handed_out_at')
            ->get();
    }

    public function getHistory(): \Illuminate\Support\Collection
    {
        $employee = Employee::where('user_id', auth()->id())->first();
        if (! $employee) return collect();

        return KeyHandover::with(['key.station'])
            ->where('employee_id', $employee->id)
            ->where(function ($q) {
                $q->whereNotNull('returned_at')
                  ->orWhereNotNull('employee_returned_at');
            })
            ->orderByDesc('handed_out_at')
            ->limit(20)
            ->get();
    }

    // ── Unterzeichnung starten ───────────────────────────────────────────────

    public function startSigning(int $handoverId, string $mode): void
    {
        $this->signingId   = $handoverId;
        $this->signingMode = $mode;
        // Nach dem DOM-Update das Canvas initialisieren (läuft garantiert nach Livewire-Morph)
        $this->js("setTimeout(function(){ window.__initSigPad && window.__initSigPad($handoverId); }, 150);");
    }

    public function cancelSigning(): void
    {
        $this->signingId   = null;
        $this->signingMode = '';
    }

    // ── Unterschrift speichern ───────────────────────────────────────────────

    public function saveSignature(string $signatureData): void
    {
        if (! $this->signingId || empty($signatureData)) return;

        $employee = Employee::where('user_id', auth()->id())->first();
        if (! $employee) return;

        $handover = KeyHandover::where('id', $this->signingId)
            ->where('employee_id', $employee->id)
            ->first();

        if (! $handover) return;

        if ($this->signingMode === 'receipt') {
            $handover->update([
                'employee_confirmed_at' => now(),
                'receipt_signature'     => $signatureData,
            ]);
            Notification::make()
                ->title('Empfang bestätigt')
                ->body('Ihre Unterschrift für „' . $handover->key->name . '" wurde gespeichert.')
                ->success()
                ->send();
        } elseif ($this->signingMode === 'return') {
            $handover->update([
                'employee_returned_at' => now(),
                'return_signature'     => $signatureData,
            ]);
            Notification::make()
                ->title('Rückgabe bestätigt')
                ->body('Sie haben „' . $handover->key->name . '" zurückgegeben.')
                ->success()
                ->send();
        }

        $this->signingId   = null;
        $this->signingMode = '';
    }
}
