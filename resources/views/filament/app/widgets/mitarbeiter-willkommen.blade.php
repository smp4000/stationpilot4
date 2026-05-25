<x-filament-widgets::widget>
@php
    $employee = $this->getEmployee();
    $user     = auth()->user();
    $hour     = now()->hour;
    $greeting = match(true) {
        $hour < 12 => 'Guten Morgen',
        $hour < 18 => 'Guten Tag',
        default    => 'Guten Abend',
    };
@endphp

<x-filament::section>

    {{-- Header --}}
    <div style="background:linear-gradient(135deg,#1e3a8a 0%,#2563eb 100%);border-radius:12px;padding:24px 28px;margin-bottom:16px;">
        <div style="display:flex;align-items:center;gap:16px;">
            <div style="width:52px;height:52px;border-radius:50%;background:rgba(255,255,255,0.2);display:flex;align-items:center;justify-content:center;font-size:18px;font-weight:700;color:#fff;flex-shrink:0;">
                {{ strtoupper(substr($user->first_name ?? '?', 0, 1)) }}{{ strtoupper(substr($user->last_name ?? '', 0, 1)) }}
            </div>
            <div>
                <p style="color:rgba(255,255,255,0.7);font-size:13px;margin:0 0 2px;">{{ $greeting }},</p>
                <h2 style="color:#fff;font-size:20px;font-weight:700;margin:0 0 2px;">{{ $user->first_name }} {{ $user->last_name }}</h2>
                @if ($employee?->station)
                    <p style="color:rgba(255,255,255,0.7);font-size:13px;margin:0;">
                        📍 {{ $employee->station->name }}
                    </p>
                @endif
            </div>
        </div>
    </div>

    {{-- Info-Kacheln --}}
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:12px;">

        <div style="background:#f8fafc;border-radius:8px;padding:12px 16px;text-align:center;border:1px solid #e2e8f0;">
            <p style="font-size:11px;color:#94a3b8;text-transform:uppercase;letter-spacing:0.5px;margin:0 0 4px;">Position</p>
            <p style="font-size:14px;font-weight:600;color:#1e293b;margin:0;">{{ $employee?->position ?: '—' }}</p>
        </div>

        <div style="background:#f8fafc;border-radius:8px;padding:12px 16px;text-align:center;border:1px solid #e2e8f0;">
            <p style="font-size:11px;color:#94a3b8;text-transform:uppercase;letter-spacing:0.5px;margin:0 0 4px;">Beschäftigungsart</p>
            <p style="font-size:14px;font-weight:600;color:#1e293b;margin:0;">
                {{ match($employee?->employment_type ?? '') {
                    'vollzeit'   => 'Vollzeit',
                    'teilzeit'   => 'Teilzeit',
                    'minijob'    => 'Minijob',
                    'aushilfe'   => 'Aushilfe',
                    'ausbildung' => 'Ausbildung',
                    default      => '—',
                } }}
            </p>
        </div>

        <div style="background:#f8fafc;border-radius:8px;padding:12px 16px;text-align:center;border:1px solid #e2e8f0;">
            <p style="font-size:11px;color:#94a3b8;text-transform:uppercase;letter-spacing:0.5px;margin:0 0 4px;">Eintrittsdatum</p>
            <p style="font-size:14px;font-weight:600;color:#1e293b;margin:0;">
                {{ $employee?->employment_start?->format('d.m.Y') ?? '—' }}
            </p>
        </div>

        <div style="background:#f8fafc;border-radius:8px;padding:12px 16px;text-align:center;border:1px solid #e2e8f0;">
            <p style="font-size:11px;color:#94a3b8;text-transform:uppercase;letter-spacing:0.5px;margin:0 0 4px;">Personalnummer</p>
            <p style="font-size:14px;font-weight:600;color:#1e293b;margin:0;font-family:monospace;">
                {{ $employee?->personnel_number ?: '—' }}
            </p>
        </div>

    </div>

    {{-- Passwort-Hinweis --}}
    @if ($user->must_change_password)
        <div style="margin-top:16px;background:#fff7ed;border-left:4px solid #f97316;border-radius:0 8px 8px 0;padding:12px 16px;display:flex;align-items:center;gap:10px;">
            <span style="font-size:18px;">⚠️</span>
            <p style="margin:0;font-size:13px;color:#9a3412;">
                Bitte ändern Sie Ihr Passwort unter
                <a href="{{ \App\Filament\App\Pages\MeinProfil::getUrl() }}" style="font-weight:600;color:#c2410c;">Mein Profil</a>.
            </p>
        </div>
    @endif

</x-filament::section>

</x-filament-widgets::widget>
