@php
    $mapId         = 'smap_' . substr(md5(uniqid()), 0, 8);
    $competitors   = is_array($competitors ?? null) ? array_values($competitors) : [];
    $otherStations = $otherStations ?? collect();

    // Format price helper
    $fmt = fn($v) => $v !== null && $v !== '' ? number_format((float)$v, 3, ',', '.') : '–';

    // Pre-encode JSON for JS — avoids Blade @json() multi-line parse errors
    $otherStationsJson = $otherStations->values()->map(fn($s) => [
        'name' => $s->name,
        'lat'  => (float) $s->latitude,
        'lng'  => (float) $s->longitude,
        'city' => $s->city,
    ])->toJson(JSON_UNESCAPED_UNICODE);

    $competitorsJson = json_encode(
        array_values(array_map(fn($c) => [
            'name'        => $c['name'] ?? '',
            'brand'       => $c['brand'] ?? '',
            'street'      => $c['street'] ?? '',
            'city'        => $c['city'] ?? '',
            'distance_km' => isset($c['distance_km']) ? (float) $c['distance_km'] : null,
            'lat'         => isset($c['lat'])  && $c['lat']  ? (float) $c['lat']  : null,
            'lng'         => isset($c['lng'])  && $c['lng']  ? (float) $c['lng']  : null,
        ], $competitors)),
        JSON_UNESCAPED_UNICODE
    );
@endphp

<div class="station-map-wrapper" style="display:flex;gap:0;border:1px solid #e5e7eb;border-radius:10px;overflow:hidden;background:#fff;">

    {{-- ──────────────── MAP COLUMN ──────────────── --}}
    <div style="flex:1 1 0;min-width:0;position:relative;">

        {{-- click hint --}}
        <div style="position:absolute;top:8px;left:50%;transform:translateX(-50%);z-index:999;
                    background:rgba(255,255,255,.9);border-radius:20px;padding:4px 14px;
                    font-size:12px;color:#374151;box-shadow:0 1px 4px rgba(0,0,0,.15);white-space:nowrap;">
            🖱 Klicken Sie auf die Karte, um die Position zu setzen
        </div>

        <div id="{{ $mapId }}" style="height:460px;width:100%;"></div>

        {{-- legend --}}
        <div style="position:absolute;bottom:8px;left:8px;z-index:999;
                    background:rgba(255,255,255,.92);border-radius:8px;padding:6px 10px;
                    font-size:11px;color:#374151;box-shadow:0 1px 4px rgba(0,0,0,.12);line-height:1.8;">
            <span style="display:inline-block;width:12px;height:12px;border-radius:50%;
                         background:#2563eb;border:2px solid #1e40af;margin-right:4px;vertical-align:middle;"></span>Diese Station
            <br>
            <span style="display:inline-block;width:12px;height:12px;border-radius:50%;
                         background:#f97316;border:2px solid #c2410c;margin-right:4px;vertical-align:middle;"></span>Eigene weitere
            <br>
            <span style="display:inline-block;width:12px;height:12px;border-radius:50%;
                         background:#ef4444;border:2px solid #991b1b;margin-right:4px;vertical-align:middle;"></span>Wettbewerber
        </div>
    </div>

    {{-- ──────────────── RIGHT SIDEBAR ──────────────── --}}
    <div style="width:270px;flex-shrink:0;border-left:1px solid #e5e7eb;overflow-y:auto;max-height:460px;font-size:13px;">

        {{-- OWN STATION --}}
        <div style="padding:10px 12px;background:#eff6ff;border-bottom:1px solid #dbeafe;">
            <div style="font-weight:700;color:#1e40af;font-size:12px;text-transform:uppercase;letter-spacing:.04em;margin-bottom:4px;">
                🔵 Diese Station
            </div>
            <div style="font-weight:600;color:#1e3a5f;line-height:1.3;">{{ $name }}</div>
            <div style="color:#6b7280;font-size:11px;margin-top:2px;">
                {{ number_format((float)$lat, 6, '.', '') }}, {{ number_format((float)$lng, 6, '.', '') }}
            </div>
            @if($priceSuper || $priceE10 || $priceDiesel)
            <div style="margin-top:5px;display:flex;gap:6px;flex-wrap:wrap;">
                @if($priceSuper)
                <span style="background:#dbeafe;color:#1e40af;border-radius:4px;padding:1px 6px;font-size:11px;">
                    Super {{ $fmt($priceSuper) }}
                </span>
                @endif
                @if($priceE10)
                <span style="background:#dcfce7;color:#166534;border-radius:4px;padding:1px 6px;font-size:11px;">
                    E10 {{ $fmt($priceE10) }}
                </span>
                @endif
                @if($priceDiesel)
                <span style="background:#fef9c3;color:#854d0e;border-radius:4px;padding:1px 6px;font-size:11px;">
                    DK {{ $fmt($priceDiesel) }}
                </span>
                @endif
            </div>
            @endif
        </div>

        {{-- OTHER OWN STATIONS --}}
        @if($otherStations->count())
        <div style="padding:8px 12px 4px;border-bottom:1px solid #f3f4f6;">
            <div style="font-weight:700;color:#9a3412;font-size:11px;text-transform:uppercase;letter-spacing:.04em;margin-bottom:6px;">
                🟠 {{ $otherStations->count() }} Weitere Stationen
            </div>
            @foreach($otherStations as $st)
            <div style="padding:5px 0;border-bottom:1px solid #f9fafb;" class="smap-other-row"
                 data-lat="{{ $st->latitude }}" data-lng="{{ $st->longitude }}">
                <div style="font-weight:600;color:#374151;font-size:12px;">{{ $st->name }}</div>
                <div style="color:#9ca3af;font-size:11px;">
                    {{ $st->street }} {{ $st->house_number ?? '' }}, {{ $st->city }}
                </div>
                <div style="color:#f97316;font-size:11px;" class="smap-dist" data-lat="{{ $st->latitude }}" data-lng="{{ $st->longitude }}">
                    Entfernung wird berechnet…
                </div>
            </div>
            @endforeach
        </div>
        @endif

        {{-- MANUAL COMPETITORS --}}
        @if(count($competitors))
        <div style="padding:8px 12px 4px;">
            <div style="font-weight:700;color:#991b1b;font-size:11px;text-transform:uppercase;letter-spacing:.04em;margin-bottom:6px;">
                🔴 {{ count($competitors) }} Wettbewerber
            </div>
            @foreach($competitors as $ci => $c)
            <div class="smap-comp-row"
                 style="padding:5px 0;border-bottom:1px solid #f9fafb;{{ !empty($c['lat']) && !empty($c['lng']) ? 'cursor:pointer;' : '' }}"
                 @if(!empty($c['lat']) && !empty($c['lng']))
                     data-lat="{{ $c['lat'] }}" data-lng="{{ $c['lng'] }}" data-idx="{{ $ci }}"
                 @endif>
                <div style="font-weight:600;color:#374151;font-size:12px;">
                    {{ $c['name'] ?? '–' }}
                    @if(!empty($c['brand']) && trim($c['brand']) !== trim($c['name'] ?? ''))
                        <span style="color:#9ca3af;">({{ $c['brand'] }})</span>
                    @endif
                </div>
                @if(!empty($c['street']))
                <div style="color:#9ca3af;font-size:11px;">{{ $c['street'] }}, {{ $c['city'] ?? '' }}</div>
                @endif
                @if(!empty($c['distance_km']))
                <div style="color:#ef4444;font-size:11px;">{{ number_format((float)$c['distance_km'], 1, ',', '.') }} km</div>
                @endif
            </div>
            @endforeach
        </div>
        @endif

        @if($otherStations->isEmpty() && empty($competitors))
        <div style="padding:16px;color:#9ca3af;font-size:12px;text-align:center;">
            Keine weiteren Stationen vorhanden.
        </div>
        @endif
    </div>
</div>

{{-- ──────────────── PRICE COMPARISON TABLE ──────────────── --}}
@php
    $hasAnyPrice = $priceSuper || $priceE10 || $priceDiesel || $otherStations->count() || count($competitors);
@endphp
@if($hasAnyPrice)
<div style="margin-top:12px;border:1px solid #e5e7eb;border-radius:8px;overflow:hidden;">
    <div style="background:#f9fafb;padding:8px 14px;font-weight:700;font-size:12px;
                color:#374151;border-bottom:1px solid #e5e7eb;text-transform:uppercase;letter-spacing:.04em;">
        Preisvergleich
    </div>
    <table style="width:100%;border-collapse:collapse;font-size:12px;">
        <thead>
            <tr style="background:#f3f4f6;">
                <th style="padding:6px 10px;text-align:left;color:#6b7280;font-weight:600;">#</th>
                <th style="padding:6px 10px;text-align:left;color:#6b7280;font-weight:600;">Station</th>
                <th style="padding:6px 10px;text-align:right;color:#6b7280;font-weight:600;">KM</th>
                <th style="padding:6px 10px;text-align:right;color:#6b7280;font-weight:600;">Super</th>
                <th style="padding:6px 10px;text-align:right;color:#6b7280;font-weight:600;">E10</th>
                <th style="padding:6px 10px;text-align:right;color:#6b7280;font-weight:600;">Diesel</th>
            </tr>
        </thead>
        <tbody>
            <tr style="background:#eff6ff;border-top:1px solid #e5e7eb;">
                <td style="padding:6px 10px;color:#1e40af;">●</td>
                <td style="padding:6px 10px;font-weight:600;color:#1e3a5f;">{{ $name }}</td>
                <td style="padding:6px 10px;text-align:right;color:#6b7280;">–</td>
                <td style="padding:6px 10px;text-align:right;color:#1e40af;">{{ $fmt($priceSuper) }}</td>
                <td style="padding:6px 10px;text-align:right;color:#166534;">{{ $fmt($priceE10) }}</td>
                <td style="padding:6px 10px;text-align:right;color:#854d0e;">{{ $fmt($priceDiesel) }}</td>
            </tr>
            @foreach($otherStations as $i => $st)
            <tr style="border-top:1px solid #f3f4f6;" class="smap-price-row" data-lat="{{ $st->latitude }}" data-lng="{{ $st->longitude }}">
                <td style="padding:6px 10px;color:#f97316;">●</td>
                <td style="padding:6px 10px;color:#374151;">{{ $st->name }}</td>
                <td style="padding:6px 10px;text-align:right;color:#6b7280;" class="smap-dist-cell" data-lat="{{ $st->latitude }}" data-lng="{{ $st->longitude }}">–</td>
                <td style="padding:6px 10px;text-align:right;color:#1e40af;">{{ $st->price_super  ? $fmt($st->price_super)  : '–' }}</td>
                <td style="padding:6px 10px;text-align:right;color:#166534;">{{ $st->price_e10    ? $fmt($st->price_e10)    : '–' }}</td>
                <td style="padding:6px 10px;text-align:right;color:#854d0e;">{{ $st->price_diesel ? $fmt($st->price_diesel) : '–' }}</td>
            </tr>
            @endforeach
            @foreach($competitors as $i => $c)
            @php
                $cSuper  = $c['price_super']  ?? null;
                $cE10    = $c['price_e10']    ?? null;
                $cDiesel = $c['price_diesel'] ?? null;
                // Highlight competitor rows where their price is LOWER than own
                $superLow  = $cSuper  && $priceSuper  && $cSuper  < $priceSuper;
                $e10Low    = $cE10    && $priceE10    && $cE10    < $priceE10;
                $dieselLow = $cDiesel && $priceDiesel && $cDiesel < $priceDiesel;
            @endphp
            <tr style="border-top:1px solid #f3f4f6;background:#fff5f5;">
                <td style="padding:6px 10px;color:#ef4444;">●</td>
                <td style="padding:6px 10px;color:#374151;">
                    {{ $c['name'] ?? '–' }}
                    @if(!empty($c['brand']) && trim($c['brand']) !== trim($c['name'] ?? ''))
                        <span style="color:#9ca3af;">({{ $c['brand'] }})</span>
                    @endif
                </td>
                <td style="padding:6px 10px;text-align:right;color:#6b7280;">
                    @if(!empty($c['distance_km'])) {{ number_format((float)$c['distance_km'], 1, ',', '.') }} @else – @endif
                </td>
                <td style="padding:6px 10px;text-align:right;{{ $superLow  ? 'color:#dc2626;font-weight:600;' : 'color:#6b7280;' }}">
                    {{ $cSuper  ? $fmt($cSuper)  : '–' }}
                </td>
                <td style="padding:6px 10px;text-align:right;{{ $e10Low    ? 'color:#dc2626;font-weight:600;' : 'color:#6b7280;' }}">
                    {{ $cE10    ? $fmt($cE10)    : '–' }}
                </td>
                <td style="padding:6px 10px;text-align:right;{{ $dieselLow ? 'color:#dc2626;font-weight:600;' : 'color:#6b7280;' }}">
                    {{ $cDiesel ? $fmt($cDiesel) : '–' }}
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

{{-- ──────────────── LEAFLET SCRIPT ──────────────── --}}
<script>
(function() {
    const MAP_ID   = '{{ $mapId }}';
    const OWN_LAT  = parseFloat('{{ $lat }}');
    const OWN_LNG  = parseFloat('{{ $lng }}');
    const OWN_NAME = @json($name);

    const OTHER_STATIONS = {!! $otherStationsJson !!};

    const COMPETITORS = {!! $competitorsJson !!};

    // Haversine distance in km
    function haversine(lat1, lng1, lat2, lng2) {
        const R = 6371;
        const dLat = (lat2 - lat1) * Math.PI / 180;
        const dLng = (lng2 - lng1) * Math.PI / 180;
        const a = Math.sin(dLat/2)**2 + Math.cos(lat1*Math.PI/180)*Math.cos(lat2*Math.PI/180)*Math.sin(dLng/2)**2;
        return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
    }

    function formatDist(d) { return d < 1 ? (d*1000).toFixed(0)+' m' : d.toFixed(1)+' km'; }

    function makeCircleIcon(color, border, label) {
        const svg = `<svg xmlns="http://www.w3.org/2000/svg" width="36" height="44" viewBox="0 0 36 44">
            <circle cx="18" cy="18" r="14" fill="${color}" stroke="${border}" stroke-width="2.5" opacity=".95"/>
            <text x="18" y="23" text-anchor="middle" font-size="12" font-weight="bold" fill="#fff" font-family="sans-serif">${label}</text>
            <line x1="18" y1="32" x2="18" y2="44" stroke="${border}" stroke-width="2"/>
        </svg>`;
        return L.divIcon({
            html: svg,
            className: '',
            iconSize: [36, 44],
            iconAnchor: [18, 44],
            popupAnchor: [0, -44],
        });
    }

    function makePriceIcon(color, border, priceLabel) {
        const svg = `<svg xmlns="http://www.w3.org/2000/svg" width="80" height="48" viewBox="0 0 80 48">
            <rect x="1" y="1" width="78" height="30" rx="6" fill="${color}" stroke="${border}" stroke-width="1.5" opacity=".95"/>
            <text x="40" y="21" text-anchor="middle" font-size="12" font-weight="bold" fill="#fff" font-family="sans-serif">${priceLabel}</text>
            <line x1="40" y1="32" x2="40" y2="48" stroke="${border}" stroke-width="2"/>
        </svg>`;
        return L.divIcon({
            html: svg,
            className: '',
            iconSize: [80, 48],
            iconAnchor: [40, 48],
            popupAnchor: [0, -48],
        });
    }

    function initMap() {
        const el = document.getElementById(MAP_ID);
        if (!el || el._leafletInit) return;

        // Check Leaflet is loaded
        if (typeof L === 'undefined') {
            setTimeout(initMap, 200);
            return;
        }

        el._leafletInit = true;

        const allBounds = [[OWN_LAT, OWN_LNG]];
        const map = L.map(el, { zoomControl: true }).setView([OWN_LAT, OWN_LNG], 13);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
            maxZoom: 19,
        }).addTo(map);

        // Own station marker (blue, fixed — nicht verschiebbar)
        const ownIcon = makeCircleIcon('#2563eb', '#1e40af', '★');
        const ownMarker = L.marker([OWN_LAT, OWN_LNG], { icon: ownIcon, draggable: false })
            .addTo(map)
            .bindPopup(`<b>${OWN_NAME}</b><br>Diese Station`);

        // Klick auf Karte → Fokus auf Hauptstation (kein Setzen der Position)
        map.on('click', function() {
            map.setView([OWN_LAT, OWN_LNG], 15, { animate: true });
            ownMarker.openPopup();
        });

        function updateDistances(fromLat, fromLng) {
            document.querySelectorAll('.smap-dist').forEach(function(el) {
                const toLat = parseFloat(el.dataset.lat);
                const toLng = parseFloat(el.dataset.lng);
                if (!isNaN(toLat) && !isNaN(toLng)) {
                    el.textContent = formatDist(haversine(fromLat, fromLng, toLat, toLng));
                }
            });
            document.querySelectorAll('.smap-dist-cell').forEach(function(el) {
                const toLat = parseFloat(el.dataset.lat);
                const toLng = parseFloat(el.dataset.lng);
                if (!isNaN(toLat) && !isNaN(toLng)) {
                    el.textContent = formatDist(haversine(fromLat, fromLng, toLat, toLng));
                }
            });
        }

        // Other own stations (orange markers)
        const otherMarkers = [];
        OTHER_STATIONS.forEach(function(st) {
            if (!st.lat || !st.lng) return;
            const dist = haversine(OWN_LAT, OWN_LNG, st.lat, st.lng);
            const icon = makeCircleIcon('#f97316', '#c2410c', '●');
            const marker = L.marker([st.lat, st.lng], { icon, draggable: false })
                .addTo(map)
                .bindPopup(`<b>${st.name}</b><br>${st.city}<br>${formatDist(dist)} entfernt`);
            otherMarkers.push({ lat: st.lat, lng: st.lng, marker });
            if (dist <= 5) allBounds.push([st.lat, st.lng]);   // nur nahe Stationen in fitBounds
        });

        // Competitors (red markers — wenn Koordinaten vorhanden)
        const compMarkers = [];
        COMPETITORS.forEach(function(c, idx) {
            if (!c.name || !c.lat || !c.lng) return;
            const dist = c.distance_km != null
                ? c.distance_km.toFixed(1) + ' km'
                : formatDist(haversine(OWN_LAT, OWN_LNG, c.lat, c.lng));
            const distKm = c.distance_km != null ? c.distance_km : haversine(OWN_LAT, OWN_LNG, c.lat, c.lng);
            const icon = makeCircleIcon('#ef4444', '#991b1b', String(idx + 1));
            const addr = [c.street, c.city].filter(Boolean).join(', ');
            const marker = L.marker([c.lat, c.lng], { icon, draggable: false })
                .addTo(map)
                .bindPopup(
                    `<b style="color:#991b1b">${c.name}</b>` +
                    (c.brand ? ` <span style="color:#9ca3af">(${c.brand})</span>` : '') +
                    (addr ? `<br><small>${addr}</small>` : '') +
                    `<br><small>${dist} entfernt</small>`
                );
            compMarkers.push({ idx, lat: c.lat, lng: c.lng, marker });
            if (distKm <= 5) allBounds.push([c.lat, c.lng]);   // nur nahe Wettbewerber in fitBounds
        });

        // Karte zentrieren: fitBounds nur wenn nahe Marker vorhanden, sonst einfach Hauptstation
        if (allBounds.length > 1) {
            map.fitBounds(allBounds, { padding: [60, 60], maxZoom: 15 });
        } else {
            map.setView([OWN_LAT, OWN_LNG], 15);
        }

        // ── Sidebar-Klick → Karte fokussiert ──────────────────────────
        document.querySelectorAll('.smap-other-row[data-lat]').forEach(function(row) {
            const lat = parseFloat(row.dataset.lat);
            const lng = parseFloat(row.dataset.lng);
            if (isNaN(lat) || isNaN(lng)) return;
            row.style.cursor = 'pointer';
            row.addEventListener('click', function() {
                map.setView([lat, lng], 16, { animate: true });
                otherMarkers.forEach(function(m) {
                    if (Math.abs(m.lat - lat) < 0.0001 && Math.abs(m.lng - lng) < 0.0001) {
                        m.marker.openPopup();
                    }
                });
            });
        });

        document.querySelectorAll('.smap-comp-row[data-lat]').forEach(function(row) {
            const lat = parseFloat(row.dataset.lat);
            const lng = parseFloat(row.dataset.lng);
            const idx = parseInt(row.dataset.idx);
            if (isNaN(lat) || isNaN(lng)) return;
            row.addEventListener('click', function() {
                map.setView([lat, lng], 16, { animate: true });
                compMarkers.forEach(function(m) {
                    if (m.idx === idx) m.marker.openPopup();
                });
            });
        });

        // Initiale Entfernungsberechnung
        updateDistances(OWN_LAT, OWN_LNG);

        // ── invalidateSize-Strategie ──────────────────────────────────
        // Problem: Karte ist beim Init im versteckten Tab → Leaflet misst
        // width: 0 → nur Tile-Bruchteil oben-links wird geladen.
        // Lösung 1: ResizeObserver → feuert sobald Container seine echte
        //           Breite bekommt (Tab-Aktivierung, Accordion, etc.)
        // Lösung 2: IntersectionObserver → feuert wenn Container sichtbar
        // Lösung 3: Fallback-Timeouts für ältere Browser

        function fixSize() { map.invalidateSize({ pan: false }); }

        if (window.ResizeObserver) {
            const ro = new ResizeObserver(function(entries) {
                const w = entries[0].contentRect.width;
                if (w > 0) fixSize();
            });
            ro.observe(el);
        }

        if (window.IntersectionObserver) {
            const io = new IntersectionObserver(function(entries) {
                if (entries[0].isIntersecting) { fixSize(); }
            }, { threshold: 0.05 });
            io.observe(el);
        }

        // Fallback
        setTimeout(fixSize, 200);
        setTimeout(fixSize, 600);
        setTimeout(fixSize, 1500);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initMap);
    } else {
        initMap();
    }

    // Also init when Livewire re-renders
    document.addEventListener('livewire:navigated', initMap);
    document.addEventListener('livewire:morph.updated', function() {
        setTimeout(initMap, 100);
    });
})();
</script>
