<x-filament-widgets::widget>
@if($contract)
@php
    $statusMap = [
        'draft'           => ['label' => 'Entwurf',          'color' => '#94a3b8', 'bg' => '#f1f5f9'],
        'sent'            => ['label' => 'Versendet',         'color' => '#d97706', 'bg' => '#fef3c7'],
        'employee_signed' => ['label' => 'MA unterschrieben', 'color' => '#2563eb', 'bg' => '#dbeafe'],
        'completed'       => ['label' => 'Abgeschlossen',     'color' => '#059669', 'bg' => '#d1fae5'],
        'cancelled'       => ['label' => 'Abgebrochen',       'color' => '#dc2626', 'bg' => '#fee2e2'],
    ];
    $s = $statusMap[$contract->status] ?? ['label' => $contract->status, 'color' => '#94a3b8', 'bg' => '#f1f5f9'];
@endphp

<x-filament::section>

    {{-- Status-Leiste --}}
    <div style="display:flex;flex-wrap:wrap;gap:16px;align-items:center;margin-bottom:20px;padding:16px 20px;background:#f8fafc;border-radius:10px;border:1px solid #e2e8f0;">

        <div style="flex:1;min-width:180px;">
            <p style="font-size:11px;color:#94a3b8;text-transform:uppercase;letter-spacing:0.5px;margin:0 0 3px;">Mitarbeiter</p>
            <p style="font-size:15px;font-weight:700;color:#1e293b;margin:0;">{{ $contract->employee->fullName() }}</p>
        </div>

        <div style="flex:1;min-width:140px;">
            <p style="font-size:11px;color:#94a3b8;text-transform:uppercase;letter-spacing:0.5px;margin:0 0 3px;">Vertragsart</p>
            <p style="font-size:14px;font-weight:600;color:#1e293b;margin:0;">{{ $contract->contractTypeLabel() }}</p>
        </div>

        <div>
            <p style="font-size:11px;color:#94a3b8;text-transform:uppercase;letter-spacing:0.5px;margin:0 0 3px;">Status</p>
            <span style="display:inline-block;padding:3px 12px;border-radius:12px;font-size:12px;font-weight:700;background:{{ $s['bg'] }};color:{{ $s['color'] }};">
                {{ $s['label'] }}
            </span>
        </div>

        @if($contract->is_uploaded)
        <div>
            <span style="display:inline-block;padding:3px 12px;border-radius:12px;font-size:12px;font-weight:700;background:#ede9fe;color:#7c3aed;">
                📎 Hochgeladen
            </span>
        </div>
        @endif

    </div>

    {{-- Unterschriften-Status --}}
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:20px;">

        {{-- Mitarbeiter --}}
        <div style="padding:14px 16px;border-radius:8px;border:1px solid {{ $contract->employee_signed_at ? '#6ee7b7' : '#e2e8f0' }};background:{{ $contract->employee_signed_at ? '#d1fae5' : '#fafafa' }};">
            <p style="font-size:11px;text-transform:uppercase;letter-spacing:0.5px;margin:0 0 4px;color:{{ $contract->employee_signed_at ? '#059669' : '#94a3b8' }};">Unterschrift Mitarbeiter</p>
            @if($contract->employee_signed_at)
                <p style="margin:0;font-size:13px;font-weight:600;color:#065f46;">
                    ✅ {{ $contract->employee_signed_at->format('d.m.Y H:i') }} Uhr
                </p>
            @else
                <p style="margin:0;font-size:13px;color:#94a3b8;">Ausstehend</p>
            @endif
        </div>

        {{-- Arbeitgeber --}}
        <div style="padding:14px 16px;border-radius:8px;border:1px solid {{ $contract->employer_signed_at ? '#6ee7b7' : '#fde68a' }};background:{{ $contract->employer_signed_at ? '#d1fae5' : '#fefce8' }};">
            <p style="font-size:11px;text-transform:uppercase;letter-spacing:0.5px;margin:0 0 4px;color:{{ $contract->employer_signed_at ? '#059669' : '#d97706' }};">Unterschrift Arbeitgeber</p>
            @if($contract->employer_signed_at)
                <p style="margin:0;font-size:13px;font-weight:600;color:#065f46;">
                    ✅ {{ $contract->employer_signed_at->format('d.m.Y H:i') }} Uhr
                </p>
                @if($contract->employer_signature)
                    <p style="margin:2px 0 0;font-size:11px;color:#047857;">{{ $contract->employer_signature }}</p>
                @endif
            @else
                <p style="margin:0;font-size:13px;color:#b45309;font-weight:600;">⏳ Ausstehend</p>
            @endif
        </div>

    </div>

    {{-- PDF-Vorschau --}}
    <div>
        <p style="font-size:12px;font-weight:600;text-transform:uppercase;letter-spacing:0.5px;color:#64748b;margin:0 0 10px;">Vertragsvorschau</p>
        @if($pdfUrl)
            <iframe
                src="{{ $pdfUrl }}"
                style="width:100%;height:850px;border:1px solid #e2e8f0;border-radius:8px;display:block;"
                title="Arbeitsvertrag Vorschau">
            </iframe>
        @else
            <div style="border:1px dashed #e2e8f0;border-radius:8px;padding:40px;text-align:center;color:#94a3b8;">
                <p style="margin:0;font-size:14px;">Kein PDF verfügbar — bitte PDF neu generieren.</p>
            </div>
        @endif
    </div>

</x-filament::section>
@endif
</x-filament-widgets::widget>
