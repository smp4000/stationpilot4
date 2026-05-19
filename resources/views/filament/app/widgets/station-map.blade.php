<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">Stationen-Karte</x-slot>
        @php $stations = $this->getStations(); @endphp

        @if($stations->isEmpty())
            <div class="flex items-center justify-center h-32 text-gray-400">
                <div class="text-center">
                    <x-heroicon-o-map-pin class="w-8 h-8 mx-auto mb-2 opacity-40"/>
                    <p class="text-sm">Noch keine Stationen mit Koordinaten vorhanden.</p>
                </div>
            </div>
        @else
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
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
