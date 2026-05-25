<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">Stationen-Karte</x-slot>
        @php $stations = $this->getStations(); @endphp

        @if($stations->isEmpty())
            <div style="display:flex;align-items:center;justify-content:center;height:8rem;color:#9ca3af;">
                <div style="text-align:center;">
                    <svg style="width:2rem;height:2rem;margin:0 auto 0.5rem;opacity:.4;" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z"/>
                    </svg>
                    <p style="font-size:.875rem;">Noch keine Stationen mit Koordinaten vorhanden.</p>
                </div>
            </div>
        @else
            {{-- position:relative + z-index:0 erzeugt einen eigenen Stacking-Context,
                 sodass Leaflet-Panes (z-index 200–700) darin eingeschlossen bleiben
                 und Filament-Modals (z-index ~50 auf Root-Ebene) darüber erscheinen. --}}
            <div style="position:relative;z-index:0;">
            <div wire:ignore x-data x-init="
                const stations = {{ json_encode($stations) }};
                const waitL = () => {
                    if (!window.L) { setTimeout(waitL, 50); return; }

                    const map = L.map($refs.mapEl);
                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        attribution: '© <a href=\'https://www.openstreetmap.org/copyright\'>OpenStreetMap</a>'
                    }).addTo(map);

                    const icon = L.divIcon({
                        html: '<div style=\'background:#003B95;width:26px;height:26px;border-radius:50% 50% 50% 0;transform:rotate(-45deg);border:3px solid white;box-shadow:0 2px 4px rgba(0,0,0,.35)\'></div>',
                        iconSize: [26,26], iconAnchor: [13,26], popupAnchor: [0,-26], className: '',
                    });

                    const bounds = [];
                    stations.forEach(s => {
                        const lat = parseFloat(s.lat), lng = parseFloat(s.lng);
                        if (isNaN(lat) || isNaN(lng)) return;
                        bounds.push([lat, lng]);
                        const addr = [s.street + ' ' + (s.house_number||''), s.zip + ' ' + s.city].join(', ').trim();
                        L.marker([lat, lng], {icon}).bindPopup(
                            '<strong style=\'color:#003B95\'>' + s.name + '</strong>' +
                            (s.brand ? '<br><small>' + s.brand + '</small>' : '') +
                            '<br><small>' + addr + '</small>'
                        ).addTo(map);
                    });

                    if (bounds.length === 1) map.setView(bounds[0], 14);
                    else if (bounds.length > 1) map.fitBounds(bounds, {padding: [60, 60]});
                    else map.setView([51.1657, 10.4515], 6);
                };
                waitL();
            ">
                <div x-ref="mapEl" style="height:400px;border-radius:8px;"></div>
            </div>
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
