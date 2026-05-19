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
            <div id="station-overview-map" style="height:400px;border-radius:8px;z-index:0;"></div>
            <script>
                (function () {
                    var stationsData = @json($stations);

                    function initMap() {
                        var el = document.getElementById('station-overview-map');
                        if (!el || el._mapInit) return;
                        el._mapInit = true;

                        var map = L.map(el);
                        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                            attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
                        }).addTo(map);

                        var icon = L.divIcon({
                            html: '<div style="background:#003B95;width:28px;height:28px;border-radius:50% 50% 50% 0;transform:rotate(-45deg);border:3px solid white;box-shadow:0 2px 4px rgba(0,0,0,.3)"></div>',
                            iconSize: [28, 28], iconAnchor: [14, 28], popupAnchor: [0, -28], className: '',
                        });

                        var bounds = [];
                        stationsData.forEach(function (s) {
                            var lat = parseFloat(s.lat), lng = parseFloat(s.lng);
                            if (isNaN(lat) || isNaN(lng)) return;
                            bounds.push([lat, lng]);
                            var addr = (s.street + ' ' + (s.house_number || '')).trim() + ', ' + s.zip + ' ' + s.city;
                            L.marker([lat, lng], {icon: icon}).bindPopup(
                                '<strong style="color:#003B95">' + s.name + '</strong>' +
                                (s.brand ? '<br><small>' + s.brand + '</small>' : '') +
                                '<br><small>' + addr + '</small>'
                            ).addTo(map);
                        });

                        if (bounds.length === 1) map.setView(bounds[0], 14);
                        else if (bounds.length > 1) map.fitBounds(bounds, {padding: [40, 40]});
                    }

                    function loadLeaflet(callback) {
                        if (window.L) { callback(); return; }

                        var link = document.createElement('link');
                        link.rel = 'stylesheet';
                        link.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
                        link.crossOrigin = '';
                        document.head.appendChild(link);

                        var script = document.createElement('script');
                        script.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
                        script.crossOrigin = '';
                        script.onload = callback;
                        document.head.appendChild(script);
                    }

                    // Sofort initialisieren (div ist bereits im DOM)
                    loadLeaflet(initMap);

                    // Nach Livewire-Navigation erneut initialisieren
                    document.addEventListener('livewire:navigated', function () {
                        var el = document.getElementById('station-overview-map');
                        if (el) el._mapInit = false;
                        loadLeaflet(initMap);
                    });
                })();
            </script>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
