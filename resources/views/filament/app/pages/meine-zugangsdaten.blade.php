<x-filament-panels::page>

@php
    $credentials     = $this->getCredentials();
    $revealed        = $this->revealed;
    $activeStationId = session('active_station_id');
    $activeStation   = $activeStationId ? \App\Models\Station::find($activeStationId) : null;

    $typeIcons = [
        'kasse'     => '🖥️',
        'ec_cash'   => '💳',
        'terminal'  => '📟',
        'alarm'     => '🔔',
        'tresor'    => '🔒',
        'sonstiges' => '🔑',
    ];
    $typeLabels = \App\Models\StationCredential::typeOptions();
@endphp

<x-filament::section>
    <x-slot name="heading">Meine Zugangsdaten</x-slot>
    <x-slot name="description">
        @if ($activeStation)
            ⛽ {{ $activeStation->name }} – nur Zugangsdaten dieser Station sowie stationsübergreifende Einträge
        @else
            Zugangsdaten für Kasse, EC-Cash, Terminals und weitere Geräte
        @endif
    </x-slot>

    @if ($credentials->isEmpty())
        <div style="text-align:center;padding:32px;color:#94a3b8;">
            <div style="font-size:32px;margin-bottom:8px;">🔒</div>
            <p style="margin:0;font-size:14px;">Es wurden noch keine Zugangsdaten für Sie hinterlegt.</p>
        </div>
    @else
        @php $grouped = $credentials->groupBy('type'); @endphp

        @foreach ($grouped as $type => $items)
            <div style="margin-bottom:24px;">
                <p style="font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;color:#94a3b8;margin:0 0 10px;">
                    {{ $typeIcons[$type] ?? '🔑' }} {{ $typeLabels[$type] ?? $type }}
                </p>
                <div style="display:flex;flex-direction:column;gap:8px;">
                    @foreach ($items as $cred)
                        @php $isRevealed = isset($revealed[$cred->id]); @endphp
                        <div style="background:#f8fafc;border-radius:10px;padding:16px 20px;border:1px solid #e2e8f0;">
                            <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;">
                                <div>
                                    <p style="margin:0 0 2px;font-size:14px;font-weight:600;color:#1e293b;">{{ $cred->label }}</p>
                                    @if ($cred->stations->isNotEmpty())
                                        <p style="margin:0;font-size:12px;color:#94a3b8;">⛽ {{ $cred->stations->pluck('name')->join(', ') }}</p>
                                    @endif
                                </div>
                                <button wire:click="toggleReveal({{ $cred->id }})"
                                    style="background:#e0e7ff;color:#3730a3;padding:5px 14px;border-radius:6px;border:none;font-size:12px;font-weight:600;cursor:pointer;white-space:nowrap;">
                                    {{ $isRevealed ? '🙈 Verbergen' : '👁 Anzeigen' }}
                                </button>
                            </div>

                            @if ($isRevealed)
                                <div style="margin-top:12px;display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:10px;">
                                    @if ($cred->username)
                                        <div style="background:#fff;border:1px solid #e2e8f0;border-radius:6px;padding:8px 12px;">
                                            <p style="margin:0 0 2px;font-size:10px;text-transform:uppercase;color:#94a3b8;font-weight:600;">Benutzername</p>
                                            <p style="margin:0;font-size:14px;font-family:monospace;color:#1e293b;">{{ $cred->username }}</p>
                                        </div>
                                    @endif
                                    @if ($cred->credential_value)
                                        <div style="background:#fff;border:1px solid #e2e8f0;border-radius:6px;padding:8px 12px;">
                                            <p style="margin:0 0 2px;font-size:10px;text-transform:uppercase;color:#94a3b8;font-weight:600;">Passwort</p>
                                            <p style="margin:0;font-size:14px;font-family:monospace;color:#1e293b;">{{ $cred->credential_value }}</p>
                                        </div>
                                    @endif
                                    @if ($cred->pin_value)
                                        <div style="background:#fff;border:1px solid #e2e8f0;border-radius:6px;padding:8px 12px;">
                                            <p style="margin:0 0 2px;font-size:10px;text-transform:uppercase;color:#94a3b8;font-weight:600;">PIN</p>
                                            <p style="margin:0;font-size:14px;font-family:monospace;color:#1e293b;letter-spacing:4px;">{{ $cred->pin_value }}</p>
                                        </div>
                                    @endif
                                    @if ($cred->notes)
                                        <div style="background:#fff7ed;border:1px solid #fed7aa;border-radius:6px;padding:8px 12px;grid-column:1/-1;">
                                            <p style="margin:0 0 2px;font-size:10px;text-transform:uppercase;color:#92400e;font-weight:600;">Hinweise</p>
                                            <p style="margin:0;font-size:13px;color:#78350f;">{{ $cred->notes }}</p>
                                        </div>
                                    @endif
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    @endif
</x-filament::section>

<x-filament-actions::modals />
</x-filament-panels::page>
