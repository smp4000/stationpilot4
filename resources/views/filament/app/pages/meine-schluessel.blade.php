<x-filament-panels::page>

{{-- signature_pad via CDN --}}
<script src="https://cdn.jsdelivr.net/npm/signature_pad@4.2.0/dist/signature_pad.umd.min.js"></script>

{{-- ── Globale Hilfsfunktionen für alle Unterschriften-Pads ─────────────── --}}
<script>
window.__initSigPad = function(id) {
    var attempts = 0;
    function tryInit() {
        var wrap   = document.getElementById('sig-wrap-' + id);
        var canvas = document.getElementById('sig-canvas-' + id);
        if (!wrap || !canvas) {
            if (++attempts < 30) { setTimeout(tryInit, 60); }
            return;
        }
        // Sicherstellen dass SignaturePad geladen ist
        if (typeof SignaturePad === 'undefined') {
            if (++attempts < 30) { setTimeout(tryInit, 60); }
            return;
        }
        var rect = wrap.getBoundingClientRect();
        var w = rect.width  > 0 ? rect.width  : (wrap.offsetWidth  || 600);
        var h = rect.height > 0 ? rect.height : (wrap.offsetHeight || 180);
        if (w < 10 && ++attempts < 30) { setTimeout(tryInit, 60); return; }

        var ratio = Math.max(window.devicePixelRatio || 1, 1);
        canvas.width  = Math.round(w * ratio);
        canvas.height = Math.round(h * ratio);
        var ctx = canvas.getContext('2d');
        ctx.scale(ratio, ratio);

        // Vorhandene Instanz zerstören falls vorhanden
        if (window['__sigPad_' + id]) {
            try { window['__sigPad_' + id].off(); } catch(e) {}
        }
        window['__sigPad_' + id] = new SignaturePad(canvas, {
            backgroundColor: 'rgb(250,250,250)',
            penColor:        'rgb(15,23,42)',
            minWidth: 0.5,
            maxWidth: 2.5,
        });
    }
    tryInit();
};

window.sigClear = function(id) {
    var p = window['__sigPad_' + id];
    if (p) p.clear();
};

// $wire wird als Parameter übergeben damit Alpine-Scope erhalten bleibt
window.sigSubmit = function(id, wire) {
    var p = window['__sigPad_' + id];
    if (!p || p.isEmpty()) {
        alert('Bitte unterschreiben Sie zuerst im Feld.');
        return;
    }
    wire.saveSignature(p.toDataURL('image/png'));
};
</script>

@php
    $handovers  = $this->getHandovers();
    $history    = $this->getHistory();
    $signingId  = $this->signingId;
    $signingMode = $this->signingMode;
@endphp

{{-- ── Aktive Schlüssel ────────────────────────────────────────────────── --}}
<x-filament::section>
    <x-slot name="heading">Meine aktuellen Schlüssel</x-slot>
    <x-slot name="description">Bitte bestätigen Sie Empfang und Rückgabe mit Ihrer Unterschrift.</x-slot>

    @if ($handovers->isEmpty())
        <div style="text-align:center;padding:32px;color:#94a3b8;">
            <div style="font-size:32px;margin-bottom:8px;">🔑</div>
            <p style="margin:0;font-size:14px;">Ihnen sind aktuell keine Schlüssel zugewiesen.</p>
        </div>
    @else
        <div style="display:flex;flex-direction:column;gap:16px;">
            @foreach ($handovers as $h)
                @php
                    $needsReceipt = ! $h->employee_confirmed_at;
                    $needsReturn  = $h->employee_confirmed_at && ! $h->employee_returned_at;
                    $isSigning    = $signingId === $h->id;
                @endphp

                <div style="background:#f8fafc;border-radius:12px;border:2px solid {{ $needsReceipt ? '#fed7aa' : ($needsReturn ? '#bfdbfe' : '#d1fae5') }};overflow:hidden;">

                    {{-- Key-Info --}}
                    <div style="padding:16px 20px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
                        <div style="display:flex;align-items:center;gap:14px;">
                            <div style="width:44px;height:44px;border-radius:8px;background:{{ $needsReceipt ? '#fff7ed' : '#eff6ff' }};display:flex;align-items:center;justify-content:center;font-size:20px;flex-shrink:0;">
                                {{ $h->key->type === 'chip' ? '📡' : '🔑' }}
                            </div>
                            <div>
                                <p style="margin:0 0 2px;font-size:15px;font-weight:600;color:#1e293b;">{{ $h->key->name }}</p>
                                <p style="margin:0;font-size:12px;color:#64748b;">
                                    @if ($h->key->key_number)
                                        <span style="font-family:monospace;">Nr. {{ $h->key->key_number }}</span>
                                        @if ($h->key->station) · @endif
                                    @endif
                                    @if ($h->key->station) {{ $h->key->station->name }} · @endif
                                    Ausgegeben: {{ $h->handed_out_at->format('d.m.Y H:i') }} Uhr
                                </p>
                            </div>
                        </div>

                        {{-- Status & Buttons --}}
                        <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                            @if ($h->employee_confirmed_at)
                                <span style="background:#d1fae5;color:#065f46;padding:3px 10px;border-radius:5px;font-size:11px;font-weight:600;">
                                    ✓ Empfang {{ $h->employee_confirmed_at->format('d.m.Y') }}
                                </span>
                            @endif

                            @if ($needsReceipt && ! $isSigning)
                                <button wire:click="startSigning({{ $h->id }}, 'receipt')"
                                    style="background:#1e3a8a;color:#fff;padding:8px 16px;border-radius:8px;border:none;font-size:13px;font-weight:600;cursor:pointer;">
                                    ✍️ Empfang bestätigen
                                </button>
                            @endif

                            @if ($needsReturn && ! $isSigning)
                                <button wire:click="startSigning({{ $h->id }}, 'return')"
                                    style="background:#0f766e;color:#fff;padding:8px 16px;border-radius:8px;border:none;font-size:13px;font-weight:600;cursor:pointer;">
                                    ✍️ Rückgabe bestätigen
                                </button>
                            @endif
                        </div>
                    </div>

                    {{-- ── Unterschriften-Pad ────────────────────────────────── --}}
                    @if ($isSigning)
                        <div style="border-top:1px solid #e2e8f0;padding:20px;background:#fff;">

                            <p style="margin:0 0 12px;font-size:14px;font-weight:600;color:#1e293b;">
                                @if ($signingMode === 'receipt')
                                    ✍️ Bitte bestätigen Sie den Empfang von „{{ $h->key->name }}" mit Ihrer Unterschrift:
                                @else
                                    ✍️ Bitte bestätigen Sie die Rückgabe von „{{ $h->key->name }}" mit Ihrer Unterschrift:
                                @endif
                            </p>

                            {{-- Canvas-Bereich: wire:ignore verhindert dass Livewire das Canvas zerstört --}}
                            <div wire:ignore>
                                <div id="sig-wrap-{{ $h->id }}"
                                     style="position:relative;border:2px solid #cbd5e1;border-radius:8px;background:#fafafa;overflow:hidden;cursor:crosshair;height:180px;width:100%;">
                                    <canvas id="sig-canvas-{{ $h->id }}"
                                        style="position:absolute;top:0;left:0;width:100%;height:100%;touch-action:none;display:block;"></canvas>
                                    <div id="sig-hint-{{ $h->id }}"
                                         style="position:absolute;bottom:8px;left:50%;transform:translateX(-50%);pointer-events:none;color:#d1d5db;font-size:12px;font-style:italic;white-space:nowrap;z-index:0;">
                                        Hier unterschreiben
                                    </div>
                                </div>
                            </div>

                            <div style="display:flex;gap:10px;margin-top:12px;flex-wrap:wrap;">
                                <button onclick="window.sigClear({{ $h->id }})"
                                    style="background:#f1f5f9;color:#475569;padding:8px 16px;border-radius:8px;border:1px solid #e2e8f0;font-size:13px;cursor:pointer;">
                                    🗑 Löschen
                                </button>
                                <button
                                    x-data
                                    x-on:click="sigSubmit({{ $h->id }}, $wire)"
                                    style="background:#1e3a8a;color:#fff;padding:8px 20px;border-radius:8px;border:none;font-size:13px;font-weight:600;cursor:pointer;">
                                    ✓ Unterschrift speichern
                                </button>
                                <button wire:click="cancelSigning"
                                    style="background:#fff;color:#94a3b8;padding:8px 16px;border-radius:8px;border:1px solid #e2e8f0;font-size:13px;cursor:pointer;">
                                    Abbrechen
                                </button>
                            </div>
                        </div>

                    @endif

                </div>
            @endforeach
        </div>
    @endif
</x-filament::section>

{{-- ── Verlauf ─────────────────────────────────────────────────────────── --}}
@if ($history->isNotEmpty())
<x-filament::section>
    <x-slot name="heading">Verlauf</x-slot>
    <x-slot name="description">Zurückgegebene Schlüssel</x-slot>

    <div style="display:flex;flex-direction:column;gap:8px;">
        @foreach ($history as $h)
            <div style="background:#f8fafc;border-radius:8px;padding:14px 18px;border:1px solid #e2e8f0;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;">
                <div>
                    <p style="margin:0 0 2px;font-size:14px;font-weight:600;color:#475569;">{{ $h->key->name }}</p>
                    <p style="margin:0;font-size:12px;color:#94a3b8;">
                        Ausgegeben: {{ $h->handed_out_at->format('d.m.Y') }}
                        @if ($h->employee_returned_at)
                            · Rückgabe best.: {{ $h->employee_returned_at->format('d.m.Y') }}
                        @elseif ($h->returned_at)
                            · Zurückgegeben: {{ $h->returned_at->format('d.m.Y') }}
                        @endif
                    </p>
                </div>
                <div style="display:flex;align-items:center;gap:6px;">
                    @if ($h->receipt_signature)
                        <img src="{{ $h->receipt_signature }}"
                             style="height:36px;border:1px solid #e2e8f0;border-radius:4px;background:#fff;"
                             title="Empfangs-Unterschrift">
                    @endif
                    @if ($h->return_signature)
                        <img src="{{ $h->return_signature }}"
                             style="height:36px;border:1px solid #e2e8f0;border-radius:4px;background:#fff;"
                             title="Rückgabe-Unterschrift">
                    @endif
                    <span style="background:#e2e8f0;color:#64748b;padding:3px 10px;border-radius:4px;font-size:11px;">Abgeschlossen</span>
                </div>
            </div>
        @endforeach
    </div>
</x-filament::section>
@endif

<x-filament-actions::modals />
</x-filament-panels::page>
