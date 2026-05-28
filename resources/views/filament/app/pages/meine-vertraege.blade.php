<x-filament-panels::page>

@php
    $contracts = $this->getContracts();

    $typeLabels = [
        'unbefristet' => 'Unbefristet',
        'befristet'   => 'Befristet',
        'minijob'     => 'Minijob',
    ];
    $statusMap = [
        'draft'           => ['label' => 'Entwurf',              'color' => '#94a3b8', 'bg' => '#f1f5f9'],
        'sent'            => ['label' => 'Versendet',             'color' => '#d97706', 'bg' => '#fef3c7'],
        'employee_signed' => ['label' => 'Von mir unterschrieben','color' => '#2563eb', 'bg' => '#dbeafe'],
        'completed'       => ['label' => 'Abgeschlossen',         'color' => '#059669', 'bg' => '#d1fae5'],
        'cancelled'       => ['label' => 'Storniert',             'color' => '#dc2626', 'bg' => '#fee2e2'],
    ];
@endphp

@if($contracts->isEmpty())
    <x-filament::section>
        <div style="text-align:center;padding:40px 20px;color:#94a3b8;">
            <svg style="width:48px;height:48px;margin:0 auto 12px;display:block;color:#cbd5e1;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            <p style="font-size:15px;font-weight:600;margin-bottom:6px;color:#64748b;">Keine Verträge vorhanden</p>
            <p style="font-size:13px;color:#94a3b8;">Deine Arbeitsverträge erscheinen hier, sobald sie erstellt wurden.</p>
        </div>
    </x-filament::section>
@else
    <div style="display:flex;flex-direction:column;gap:16px;">
        @foreach($contracts as $contract)
        @php
            $s = $statusMap[$contract->status] ?? ['label' => $contract->status, 'color' => '#94a3b8', 'bg' => '#f1f5f9'];
            $d = $contract->contract_data ?? [];
        @endphp

        <x-filament::section>
            {{-- Header-Zeile --}}
            <div style="display:flex;flex-wrap:wrap;align-items:center;gap:12px;margin-bottom:16px;">
                <div style="flex:1;min-width:180px;">
                    <p style="font-size:15px;font-weight:700;color:#1e293b;margin:0;">
                        {{ $typeLabels[$contract->contract_type] ?? $contract->contract_type }}
                    </p>
                    @if(!empty($d['employment_start']))
                    <p style="font-size:12px;color:#64748b;margin:2px 0 0;">
                        ab {{ \Carbon\Carbon::parse($d['employment_start'])->format('d.m.Y') }}
                        @if(!empty($d['employment_end']))
                            bis {{ \Carbon\Carbon::parse($d['employment_end'])->format('d.m.Y') }}
                        @endif
                    </p>
                    @endif
                </div>

                <span style="padding:3px 14px;border-radius:12px;font-size:12px;font-weight:700;background:{{ $s['bg'] }};color:{{ $s['color'] }};">
                    {{ $s['label'] }}
                </span>

                @if($contract->pdf_path)
                <a href="{{ route('employee.contract.pdf', $contract->id) }}"
                   target="_blank"
                   style="display:inline-flex;align-items:center;gap:6px;padding:6px 14px;background:#1e3a5f;color:#fff;border-radius:7px;font-size:12px;font-weight:600;text-decoration:none;">
                    <svg style="width:14px;height:14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                    </svg>
                    PDF öffnen
                </a>
                @endif
            </div>

            {{-- Unterschriften-Status --}}
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
                <div style="padding:10px 14px;border-radius:8px;border:1px solid {{ $contract->employee_signed_at ? '#6ee7b7' : '#e2e8f0' }};background:{{ $contract->employee_signed_at ? '#d1fae5' : '#fafafa' }};">
                    <p style="font-size:10px;text-transform:uppercase;letter-spacing:0.5px;margin:0 0 3px;color:{{ $contract->employee_signed_at ? '#059669' : '#94a3b8' }};">Deine Unterschrift</p>
                    @if($contract->employee_signed_at)
                        <p style="margin:0;font-size:12px;font-weight:600;color:#065f46;">
                            ✅ {{ $contract->employee_signed_at->format('d.m.Y') }}
                        </p>
                    @else
                        <p style="margin:0;font-size:12px;color:#94a3b8;">Ausstehend</p>
                    @endif
                </div>

                <div style="padding:10px 14px;border-radius:8px;border:1px solid {{ $contract->employer_signed_at ? '#6ee7b7' : '#fde68a' }};background:{{ $contract->employer_signed_at ? '#d1fae5' : '#fefce8' }};">
                    <p style="font-size:10px;text-transform:uppercase;letter-spacing:0.5px;margin:0 0 3px;color:{{ $contract->employer_signed_at ? '#059669' : '#d97706' }};">Unterschrift Arbeitgeber</p>
                    @if($contract->employer_signed_at)
                        <p style="margin:0;font-size:12px;font-weight:600;color:#065f46;">
                            ✅ {{ $contract->employer_signed_at->format('d.m.Y') }}
                        </p>
                    @else
                        <p style="margin:0;font-size:12px;color:#b45309;font-weight:600;">⏳ Ausstehend</p>
                    @endif
                </div>
            </div>

            {{-- Signing-Link falls noch nicht unterschrieben --}}
            @if($contract->status === 'sent' && !$contract->employee_signed_at && $contract->employee_sign_token)
            <div style="margin-top:12px;padding:10px 14px;background:#eff6ff;border-radius:8px;border:1px solid #bfdbfe;">
                <p style="margin:0 0 6px;font-size:12px;color:#1d4ed8;font-weight:600;">Vertrag noch nicht unterschrieben</p>
                <a href="{{ route('contract.sign', $contract->employee_sign_token) }}"
                   style="font-size:12px;color:#2563eb;text-decoration:underline;">
                    Jetzt digital unterschreiben →
                </a>
            </div>
            @endif
        </x-filament::section>
        @endforeach
    </div>
@endif

</x-filament-panels::page>
