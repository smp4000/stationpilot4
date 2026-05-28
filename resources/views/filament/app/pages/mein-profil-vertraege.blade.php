@php
    $typeLabels = [
        'unbefristet' => 'Unbefristet',
        'befristet'   => 'Befristet',
        'minijob'     => 'Minijob',
    ];
    $statusMap = [
        'draft'           => ['label' => 'Entwurf',               'color' => '#94a3b8', 'bg' => '#f1f5f9'],
        'sent'            => ['label' => 'Versendet',              'color' => '#d97706', 'bg' => '#fef3c7'],
        'employee_signed' => ['label' => 'Von mir unterschrieben', 'color' => '#2563eb', 'bg' => '#dbeafe'],
        'completed'       => ['label' => 'Abgeschlossen',          'color' => '#059669', 'bg' => '#d1fae5'],
        'cancelled'       => ['label' => 'Storniert',              'color' => '#dc2626', 'bg' => '#fee2e2'],
    ];
@endphp

@if($contracts->isEmpty())
    <div style="text-align:center;padding:32px 20px;color:#94a3b8;">
        <p style="font-size:14px;font-weight:600;margin-bottom:4px;color:#64748b;">Keine Verträge vorhanden</p>
        <p style="font-size:12px;">Deine Arbeitsverträge erscheinen hier, sobald sie erstellt wurden.</p>
    </div>
@else
    <div style="display:flex;flex-direction:column;gap:12px;">
        @foreach($contracts as $contract)
        @php
            $s = $statusMap[$contract->status] ?? ['label' => $contract->status, 'color' => '#94a3b8', 'bg' => '#f1f5f9'];
            $d = $contract->contract_data ?? [];
        @endphp

        <div style="border:1px solid #e2e8f0;border-radius:10px;padding:14px 16px;background:#fafafa;">

            {{-- Kopfzeile --}}
            <div style="display:flex;flex-wrap:wrap;align-items:center;gap:10px;margin-bottom:12px;">
                <div style="flex:1;min-width:160px;">
                    <p style="font-size:14px;font-weight:700;color:#1e293b;margin:0;">
                        {{ $typeLabels[$contract->contract_type] ?? $contract->contract_type }}
                    </p>
                    @if(!empty($d['employment_start']))
                    <p style="font-size:11px;color:#64748b;margin:2px 0 0;">
                        ab {{ \Carbon\Carbon::parse($d['employment_start'])->format('d.m.Y') }}
                        @if(!empty($d['employment_end']))
                            &nbsp;bis&nbsp;{{ \Carbon\Carbon::parse($d['employment_end'])->format('d.m.Y') }}
                        @endif
                    </p>
                    @endif
                </div>

                <span style="padding:2px 12px;border-radius:20px;font-size:11px;font-weight:700;background:{{ $s['bg'] }};color:{{ $s['color'] }};">
                    {{ $s['label'] }}
                </span>

                @if($contract->pdf_path)
                <a href="{{ route('employee.contract.pdf', $contract->id) }}"
                   target="_blank"
                   style="display:inline-flex;align-items:center;gap:5px;padding:5px 12px;background:#1e3a5f;color:#fff;border-radius:6px;font-size:11px;font-weight:600;text-decoration:none;">
                    <svg style="width:12px;height:12px;flex-shrink:0;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                    </svg>
                    PDF öffnen
                </a>
                @endif
            </div>

            {{-- Unterschriften --}}
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;">
                <div style="padding:8px 12px;border-radius:6px;border:1px solid {{ $contract->employee_signed_at ? '#6ee7b7' : '#e2e8f0' }};background:{{ $contract->employee_signed_at ? '#d1fae5' : '#f8fafc' }};">
                    <p style="font-size:10px;text-transform:uppercase;letter-spacing:0.4px;margin:0 0 2px;color:{{ $contract->employee_signed_at ? '#059669' : '#94a3b8' }};">Deine Unterschrift</p>
                    <p style="margin:0;font-size:11px;font-weight:600;color:{{ $contract->employee_signed_at ? '#065f46' : '#94a3b8' }};">
                        {{ $contract->employee_signed_at ? '✅ ' . $contract->employee_signed_at->format('d.m.Y') : 'Ausstehend' }}
                    </p>
                </div>
                <div style="padding:8px 12px;border-radius:6px;border:1px solid {{ $contract->employer_signed_at ? '#6ee7b7' : '#fde68a' }};background:{{ $contract->employer_signed_at ? '#d1fae5' : '#fefce8' }};">
                    <p style="font-size:10px;text-transform:uppercase;letter-spacing:0.4px;margin:0 0 2px;color:{{ $contract->employer_signed_at ? '#059669' : '#d97706' }};">Arbeitgeber</p>
                    <p style="margin:0;font-size:11px;font-weight:600;color:{{ $contract->employer_signed_at ? '#065f46' : '#b45309' }};">
                        {{ $contract->employer_signed_at ? '✅ ' . $contract->employer_signed_at->format('d.m.Y') : '⏳ Ausstehend' }}
                    </p>
                </div>
            </div>

            {{-- Signing-Link --}}
            @if($contract->status === 'sent' && !$contract->employee_signed_at && $contract->employee_sign_token)
            <div style="margin-top:10px;padding:8px 12px;background:#eff6ff;border-radius:6px;border:1px solid #bfdbfe;">
                <p style="margin:0 0 4px;font-size:11px;color:#1d4ed8;font-weight:600;">Vertrag noch nicht unterschrieben</p>
                <a href="{{ route('contract.sign', $contract->employee_sign_token) }}"
                   style="font-size:11px;color:#2563eb;text-decoration:underline;">
                    Jetzt digital unterschreiben →
                </a>
            </div>
            @endif

        </div>
        @endforeach
    </div>
@endif
