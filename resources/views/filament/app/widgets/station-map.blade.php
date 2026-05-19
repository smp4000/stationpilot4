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
            <div
                wire:ignore
                x-data="stationMap({{ json_encode($stations) }})"
                x-init="init()"
            >
                <div x-ref="mapEl" style="height:400px;border-radius:8px;"></div>
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>

<script>
function stationMap(stations) {
    return {
        init() {
            const el = this.$refs.mapEl;
            if (!el || el._mapInit) return;

            const waitForLeaflet = () => {
                if (!window.L) { setTimeout(waitForLeaflet, 50); return; }

                el._mapInit = true;
                const map = L.map(el);

                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
                }).addTo(map);

                const icon = L.divIcon({
                    html: '<div style="background:#003B95;width:28px;height:28px;border-radius:50% 50% 50% 0;transform:rotate(-45deg);border:3px solid white;box-shadow:0 2px 4px rgba(0,0,0,.3)"></div>',
                    iconSize: [28, 28], iconAnchor: [14, 28], popupAnchor: [0, -28], className: '',
                });

                const bounds = [];
                stations.forEach(s => {
                    const lat = parseFloat(s.lat), lng = parseFloat(s.lng);
                    if (isNaN(lat) || isNaN(lng)) return;
                    bounds.push([lat, lng]);
                    const addr = (s.street + ' ' + (s.house_number || '')).trim() + ', ' + s.zip + ' ' + s.city;
                    L.marker([lat, lng], {icon}).bindPopup(
                        `<strong style="color:#003B95">${s.name}</strong>` +
                        (s.brand ? `<br><small>${s.brand}</small>` : '') +
                        `<br><small>${addr}</small>`
                    ).addTo(map);
                });

                if (bounds.length === 1) map.setView(bounds[0], 14);
                else if (bounds.length > 1) map.fitBounds(bounds, {padding: [40, 40]});
            };

            waitForLeaflet();
        }
    };
}
</script>
