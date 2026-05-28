@props(['agents' => []])

@if(count($agents) === 0)
    @php return; @endphp
@endif

<div
    x-data="{ open: false }"
    style="position:fixed;bottom:24px;right:24px;z-index:9999;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;"
>
    {{-- ── Popup ─────────────────────────────────────────────────────── --}}
    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 translate-y-2 scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 translate-y-0 scale-100"
        x-transition:leave-end="opacity-0 translate-y-2 scale-95"
        style="position:absolute;bottom:70px;right:0;width:300px;background:#fff;border-radius:16px;box-shadow:0 8px 32px rgba(0,0,0,0.18);overflow:hidden;"
        @click.outside="open = false"
    >
        {{-- Header --}}
        <div style="background:linear-gradient(135deg,#075e54,#128c7e);padding:16px 20px;display:flex;align-items:center;gap:12px;">
            <div style="background:rgba(255,255,255,0.15);border-radius:50%;width:42px;height:42px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="white">
                    <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                </svg>
            </div>
            <div>
                <p style="color:#fff;font-weight:700;font-size:15px;margin:0;">WhatsApp</p>
                <p style="color:rgba(255,255,255,0.75);font-size:12px;margin:0;">Wähle einen Ansprechpartner</p>
            </div>
        </div>

        {{-- Agenten --}}
        <div style="padding:8px 0;">
            @foreach($agents as $index => $agent)
                @php
                    $phone   = preg_replace('/[^0-9]/', '', $agent['phone'] ?? '');
                    $message = urlencode($agent['message'] ?? 'Hallo, ich habe eine Frage.');
                    $initial = mb_strtoupper(mb_substr($agent['name'] ?? '?', 0, 1));
                    $colors  = ['#1abc9c','#3498db','#9b59b6','#e74c3c','#f39c12','#2ecc71'];
                    $color   = $colors[$index % count($colors)];
                @endphp
                <a
                    href="https://wa.me/{{ $phone }}?text={{ $message }}"
                    target="_blank"
                    rel="noopener"
                    style="display:flex;align-items:center;gap:14px;padding:12px 20px;text-decoration:none;color:#1e293b;transition:background 0.15s;"
                    onmouseover="this.style.background='#f8fafc'"
                    onmouseout="this.style.background='transparent'"
                >
                    <div style="width:44px;height:44px;border-radius:50%;background:{{ $color }};display:flex;align-items:center;justify-content:center;flex-shrink:0;font-weight:700;font-size:16px;color:#fff;">
                        {{ $initial }}
                    </div>
                    <div style="flex:1;min-width:0;">
                        <p style="font-weight:600;font-size:14px;margin:0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                            {{ $agent['name'] }}
                        </p>
                        @if(!empty($agent['description']))
                        <p style="color:#64748b;font-size:12px;margin:2px 0 0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                            {{ $agent['description'] }}
                        </p>
                        @endif
                    </div>
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="#25D366">
                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                    </svg>
                </a>
                @if(!$loop->last)
                <div style="height:1px;background:#f1f5f9;margin:0 20px;"></div>
                @endif
            @endforeach
        </div>

        {{-- Footer --}}
        <div style="padding:10px 20px;border-top:1px solid #f1f5f9;text-align:center;">
            <p style="color:#94a3b8;font-size:11px;margin:0;">powered by WhatsApp</p>
        </div>
    </div>

    {{-- ── Floating Button ───────────────────────────────────────────── --}}
    <button
        @click="open = !open"
        style="width:56px;height:56px;border-radius:50%;background:#25D366;border:none;cursor:pointer;
               box-shadow:0 4px 16px rgba(37,211,102,0.5);display:flex;align-items:center;justify-content:center;
               transition:transform 0.2s,box-shadow 0.2s;"
        onmouseover="this.style.transform='scale(1.1)';this.style.boxShadow='0 6px 20px rgba(37,211,102,0.65)'"
        onmouseout="this.style.transform='scale(1)';this.style.boxShadow='0 4px 16px rgba(37,211,102,0.5)'"
    >
        {{-- Icon: WhatsApp wenn zu, X wenn offen --}}
        <span x-show="!open">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="white">
                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
            </svg>
        </span>
        <span x-show="open" x-cloak>
            <svg width="24" height="24" viewBox="0 0 24 24" fill="white">
                <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/>
            </svg>
        </span>
    </button>
</div>
