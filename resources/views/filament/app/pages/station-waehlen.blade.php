<x-filament-panels::page>

@php
    $stations      = $this->getStations();
    $activeStation = $this->getActiveStation();
@endphp

{{-- Aktive Station --}}
@if ($activeStation)
    <x-filament::section>
        <x-slot name="heading">Aktive Station</x-slot>
        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
            <div style="display:flex;align-items:center;gap:12px;">
                <div style="width:44px;height:44px;border-radius:50%;background:linear-gradient(135deg,#1e3a8a,#2563eb);display:flex;align-items:center;justify-content:center;">
                    <span style="color:#fff;font-size:18px;">⛽</span>
                </div>
                <div>
                    <p style="margin:0;font-size:16px;font-weight:700;color:#1e293b;">{{ $activeStation->name }}</p>
                    <p style="margin:0;font-size:13px;color:#64748b;">{{ $activeStation->city ?? '' }} · Aktive Schicht</p>
                </div>
            </div>
            <div style="display:flex;gap:8px;">
                <x-filament::button
                    wire:click="clearStation"
                    color="gray"
                    size="sm"
                    icon="heroicon-o-x-mark">
                    Schicht beenden
                </x-filament::button>
            </div>
        </div>
    </x-filament::section>
@endif

{{-- Stationsauswahl --}}
<x-filament::section>
    <x-slot name="heading">
        {{ $activeStation ? 'Station wechseln' : 'Wo arbeitest du heute?' }}
    </x-slot>

    @if ($stations->isEmpty())
        <p style="color:#94a3b8;font-size:14px;">Ihnen sind noch keine Stationen zugewiesen. Bitte wenden Sie sich an Ihren Vorgesetzten.</p>
    @else
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:16px;margin-top:4px;">
            @foreach ($stations as $station)
                @php $isActive = $activeStation?->id === $station->id; @endphp
                <button
                    wire:click="selectStation('{{ $station->id }}')"
                    style="
                        text-align:left;
                        padding:20px;
                        border-radius:12px;
                        border:2px solid {{ $isActive ? '#2563eb' : '#e2e8f0' }};
                        background:{{ $isActive ? '#eff6ff' : '#ffffff' }};
                        cursor:pointer;
                        transition:all .15s;
                        width:100%;
                    "
                    onmouseover="this.style.borderColor='#2563eb';this.style.background='#eff6ff';"
                    onmouseout="this.style.borderColor='{{ $isActive ? '#2563eb' : '#e2e8f0' }}';this.style.background='{{ $isActive ? '#eff6ff' : '#ffffff' }}';"
                >
                    <div style="display:flex;align-items:flex-start;gap:12px;">
                        <div style="width:40px;height:40px;border-radius:8px;background:{{ $isActive ? 'linear-gradient(135deg,#1e3a8a,#2563eb)' : '#f1f5f9' }};display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <span style="font-size:18px;">⛽</span>
                        </div>
                        <div>
                            <p style="margin:0 0 2px;font-size:15px;font-weight:600;color:{{ $isActive ? '#1e3a8a' : '#1e293b' }};">
                                {{ $station->name }}
                                @if ($isActive)
                                    <span style="font-size:11px;background:#2563eb;color:#fff;padding:2px 6px;border-radius:4px;margin-left:4px;">Aktiv</span>
                                @endif
                            </p>
                            <p style="margin:0;font-size:12px;color:#94a3b8;">
                                {{ $station->city ?? ($station->address ?? '—') }}
                            </p>
                        </div>
                    </div>
                </button>
            @endforeach
        </div>
    @endif
</x-filament::section>

<x-filament-actions::modals />

</x-filament-panels::page>
