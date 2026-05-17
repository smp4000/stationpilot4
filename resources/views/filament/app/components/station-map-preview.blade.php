@if($lat && $lng)
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin=""/>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
    <div class="station-preview-map"
         style="height:200px;border-radius:8px;border:1px solid #e5e7eb;z-index:0;"
         data-lat="{{ $lat }}"
         data-lng="{{ $lng }}"
         data-name="{{ $name }}"></div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.station-preview-map').forEach(function(el) {
                if (el._leaflet_id) return;
                const map = L.map(el).setView([parseFloat(el.dataset.lat), parseFloat(el.dataset.lng)], 15);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {attribution: '© OpenStreetMap'}).addTo(map);
                L.marker([parseFloat(el.dataset.lat), parseFloat(el.dataset.lng)])
                    .bindPopup(el.dataset.name).addTo(map).openPopup();
            });
        });
    </script>
@endif
