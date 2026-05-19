import './bootstrap';

import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

// Leaflet global verfügbar machen (für Blade-Widget-Scripts)
window.L = L;

// Standard-Marker-Icons fixen (Vite verschiebt Assets)
delete L.Icon.Default.prototype._getIconUrl;
L.Icon.Default.mergeOptions({
    iconRetinaUrl: new URL('leaflet/dist/images/marker-icon-2x.png', import.meta.url).href,
    iconUrl:       new URL('leaflet/dist/images/marker-icon.png',   import.meta.url).href,
    shadowUrl:     new URL('leaflet/dist/images/marker-shadow.png', import.meta.url).href,
});
