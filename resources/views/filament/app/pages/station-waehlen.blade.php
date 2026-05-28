<x-filament-panels::page>

@php
    $stations      = $this->getStations();
    $activeStation = $this->getActiveStation();
    $pendingId     = $this->pendingStationId;
    $pending       = $pendingId ? $stations->firstWhere('id', $pendingId) : null;
@endphp

{{-- ── Bestätigungs-Dialog beim Stationswechsel ── --}}
@if ($pending)
    <div style="background:#fff;border:2px solid #f97316;border-radius:12px;padding:24px;margin-bottom:20px;">
        <div style="display:flex;align-items:flex-start;gap:14px;">
            <span style="font-size:28px;flex-shrink:0;">🔄</span>
            <div style="flex:1;">
                <p style="margin:0 0 6px;font-size:16px;font-weight:700;color:#9a3412;">Station wechseln?</p>
                <p style="margin:0 0 4px;font-size:14px;color:#475569;">
                    Sie sind aktuell angemeldet bei:
                    <strong>{{ $activeStation?->name }}</strong>
                </p>
                <p style="margin:0 0 16px;font-size:14px;color:#475569;">
                    Neue Station: <strong style="color:#1e3a8a;">{{ $pending->name }}</strong>
                </p>
                <p style="margin:0 0 16px;font-size:13px;color:#f97316;background:#fff7ed;padding:10px 14px;border-radius:6px;">
                    ⚠️ Bitte stellen Sie sicher, dass alle offenen Vorgänge an der aktuellen Station abgeschlossen sind, bevor Sie wechseln.
                </p>
                <div style="display:flex;gap:10px;">
                    <button wire:click="confirmSwitch"
                        style="background:#1e3a8a;color:#fff;padding:10px 20px;border-radius:8px;border:none;font-size:14px;font-weight:600;cursor:pointer;">
                        ✓ Ja, Station wechseln
                    </button>
                    <button wire:click="cancelSwitch"
                        style="background:#f1f5f9;color:#475569;padding:10px 20px;border-radius:8px;border:1px solid #e2e8f0;font-size:14px;cursor:pointer;">
                        Abbrechen
                    </button>
                </div>
            </div>
        </div>
    </div>
@endif

{{-- ── Aktive Schicht ── --}}
@if ($activeStation)
    <x-filament::section>
        <x-slot name="heading">Aktive Tankstelle</x-slot>
        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
            <div style="display:flex;align-items:center;gap:12px;">
                <div style="width:44px;height:44px;border-radius:8px;background:linear-gradient(135deg,#1e3a8a,#2563eb);display:flex;align-items:center;justify-content:center;">
                    <span style="font-size:20px;">⛽</span>
                </div>
                <div>
                    <p style="margin:0;font-size:16px;font-weight:700;color:#1e293b;">{{ $activeStation->name }}</p>
                    <p style="margin:0;font-size:13px;color:#64748b;">{{ $activeStation->city ?? '' }}</p>
                </div>
            </div>
            <button wire:click="clearStation"
                style="display:inline-flex;align-items:center;gap:6px;background:#fff;border:1px solid #e2e8f0;color:#ef4444;padding:8px 16px;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;">
                ✕ Tankstelle abmelden
            </button>
        </div>
    </x-filament::section>
@endif

{{-- ── Stationsauswahl ── --}}
<x-filament::section>
    <x-slot name="heading">
        @if (! $activeStation)
            Wo arbeitest du heute? <span style="font-size:13px;font-weight:400;color:#94a3b8;">– Bitte Station wählen um zu starten</span>
        @else
            Station wechseln
        @endif
    </x-slot>

    @if ($stations->isEmpty())
        <p style="color:#94a3b8;font-size:14px;padding:12px 0;">
            Ihnen sind noch keine Stationen zugewiesen. Bitte wenden Sie sich an Ihren Vorgesetzten.
        </p>
    @else
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:16px;margin-top:8px;">
            @foreach ($stations as $station)
                @php
                    $isActive  = $activeStation?->id === $station->id;
                    $isPending = $pendingId === $station->id;
                @endphp
                <button
                    wire:click="selectStation('{{ $station->id }}')"
                    @if($isActive) disabled @endif
                    style="
                        text-align:left;padding:20px;border-radius:12px;width:100%;cursor:{{ $isActive ? 'default' : 'pointer' }};
                        border:2px solid {{ $isActive ? '#2563eb' : ($isPending ? '#f97316' : '#e2e8f0') }};
                        background:{{ $isActive ? '#eff6ff' : ($isPending ? '#fff7ed' : '#fff') }};
                        opacity:{{ $isActive ? '0.8' : '1' }};
                    "
                >
                    <div style="display:flex;align-items:center;gap:12px;">
                        <div style="width:40px;height:40px;border-radius:8px;flex-shrink:0;display:flex;align-items:center;justify-content:center;font-size:18px;
                            background:{{ $isActive ? 'linear-gradient(135deg,#1e3a8a,#2563eb)' : '#f1f5f9' }};">
                            ⛽
                        </div>
                        <div>
                            <p style="margin:0 0 2px;font-size:15px;font-weight:600;color:{{ $isActive ? '#1e3a8a' : '#1e293b' }};">
                                {{ $station->name }}
                            </p>
                            <p style="margin:0;font-size:12px;color:#94a3b8;">{{ $station->city ?? '—' }}</p>
                        </div>
                        @if ($isActive)
                            <span style="margin-left:auto;background:#2563eb;color:#fff;font-size:10px;padding:2px 8px;border-radius:4px;">Aktiv</span>
                        @endif
                    </div>
                </button>
            @endforeach
        </div>
    @endif
</x-filament::section>

<x-filament-actions::modals />

</x-filament-panels::page>
