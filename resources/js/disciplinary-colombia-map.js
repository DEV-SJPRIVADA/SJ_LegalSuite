import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

const ZOOM_MUNICIPALITIES = 8;

/** Algunas builds/contextos de Leaflet no exponen `bringToFront` en todas las capas; evitar TypeError en consola. */
function safeBringToFront(layer) {
    if (!layer) {
        return;
    }
    const fn = layer.bringToFront;
    if (typeof fn !== 'function') {
        return;
    }
    try {
        fn.call(layer);
    } catch {
        //
    }
}

/**
 * @param {string|null} pathOrUrl
 */
function resolveFetchUrl(pathOrUrl) {
    const s = (pathOrUrl || '').trim();
    if (!s) {
        throw new Error('Geo URL vacía');
    }
    if (/^https?:\/\//i.test(s)) {
        return s;
    }

    return new URL(s, window.location.origin).href;
}

function neonDeptStyle(dark) {
    return dark
        ? {
              color: '#22d3ee',
              weight: 0.65,
              opacity: 0.92,
              fillColor: '#020617',
              fillOpacity: 0.42,
              lineCap: 'round',
              lineJoin: 'round',
          }
        : {
              color: '#6366f1',
              weight: 0.55,
              opacity: 0.88,
              fillColor: '#eef2ff',
              fillOpacity: 0.55,
              lineCap: 'round',
              lineJoin: 'round',
          };
}

function neonMunStyle(dark) {
    return dark
        ? {
              color: '#e879f9',
              weight: 0.32,
              opacity: 0.82,
              fillColor: '#020617',
              fillOpacity: 0.06,
              lineCap: 'round',
              lineJoin: 'round',
          }
        : {
              color: '#a855f7',
              weight: 0.28,
              opacity: 0.78,
              fillColor: '#faf5ff',
              fillOpacity: 0.12,
              lineCap: 'round',
              lineJoin: 'round',
          };
}

function countPinIcon(count, dark) {
    const glow = dark
        ? 'filter:drop-shadow(0 0 6px rgba(34,211,238,0.85));'
        : 'filter:drop-shadow(0 0 4px rgba(99,102,241,0.55));';
    const bubble = dark
        ? 'border:1px solid rgba(34,211,238,0.85);background:rgba(2,6,23,0.92);color:#ecfeff;'
        : 'border:1px solid rgba(79,70,229,0.75);background:#fff;color:#312e81;';
    const pin = dark ? '#67e8f9' : '#4f46e5';

    const html = `
<div class="disciplinary-colombia-map-pin" style="display:flex;flex-direction:column;align-items:center;transform:translateY(-4px);${glow}">
  <div style="${bubble}min-width:2rem;padding:2px 8px 3px;border-radius:999px;font:700 12px/1.2 ui-sans-serif,system-ui,sans-serif;letter-spacing:0.02em;text-align:center;white-space:nowrap;">
    ${Number(count)}
  </div>
  <svg width="22" height="14" viewBox="0 0 22 14" aria-hidden="true" style="margin-top:-1px">
    <path d="M11 14 L2 4 Q11 -2 20 4 Z" fill="${pin}" stroke="${dark ? 'rgba(34,211,238,0.5)' : 'rgba(79,70,229,0.45)'}" stroke-width="0.5"/>
  </svg>
</div>`;

    return L.divIcon({
        className: 'disciplinary-colombia-map-pin-wrap',
        html,
        iconSize: [48, 52],
        iconAnchor: [24, 52],
    });
}

function escapeHtml(s) {
    return String(s)
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;');
}

/**
 * @param {HTMLElement} el
 */
export async function mountDisciplinaryColombiaMap(el) {
    if (el.dataset.colombiaMapMounted === '1') {
        return;
    }
    // Evita dos montajes en paralelo (p. ej. DOMContentLoaded + otro hook): el segundo L.map() rompe el contenedor.
    if (el.dataset.colombiaMapMounting === '1') {
        return;
    }
    el.dataset.colombiaMapMounting = '1';

    const dark = el.getAttribute('data-chart-dark') === '1';
    const deptGeoUrl = resolveFetchUrl(el.getAttribute('data-geo-dept'));
    const munGeoUrl = resolveFetchUrl(el.getAttribute('data-geo-mun'));
    let pins = [];
    try {
        pins = JSON.parse(el.getAttribute('data-pins') || '[]');
    } catch {
        pins = [];
    }

    let map;
    try {
        map = L.map(el, {
            zoomControl: true,
            attributionControl: true,
            scrollWheelZoom: true,
            minZoom: 5,
            maxZoom: 12,
        });

        const tileUrl = dark
            ? 'https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png'
            : 'https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png';

        L.tileLayer(tileUrl, {
            maxZoom: 19,
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OSM</a> · CARTO · Límites GADM',
            subdomains: 'abcd',
        }).addTo(map);

        const deptRes = await fetch(deptGeoUrl, { credentials: 'same-origin' });
        if (!deptRes.ok) {
            throw new Error(`Geo departamentos: HTTP ${deptRes.status}`);
        }
        const deptGeo = await deptRes.json();
        L.geoJSON(deptGeo, {
            style: () => neonDeptStyle(dark),
            interactive: false,
        }).addTo(map);

        /** @type {L.GeoJSON | null} */
        let munLayer = null;
        /** @type {object | null} */
        let munGeoCache = null;

        const markers = [];
        for (const p of pins) {
            const lat = p.lat;
            const lon = p.lon;
            if (typeof lat !== 'number' || typeof lon !== 'number' || Number.isNaN(lat) || Number.isNaN(lon)) {
                continue;
            }
            const m = L.marker([lat, lon], {
                icon: countPinIcon(p.count, dark),
                title: `${p.label} — ${p.count} caso(s)`,
            });
            m.bindTooltip(`<strong>${escapeHtml(p.label)}</strong><br>${p.count} caso(s)`, {
                direction: 'top',
                offset: [0, -36],
                opacity: dark ? 0.95 : 0.98,
            });
            m.addTo(map);
            markers.push(m);
        }

        if (markers.length) {
            const g = L.featureGroup(markers);
            map.fitBounds(g.getBounds().pad(0.18));
        } else {
            map.setView([4.65, -74.1], 5.4);
        }

        const syncMunLayer = async () => {
            try {
                const z = map.getZoom();
                if (z >= ZOOM_MUNICIPALITIES) {
                    if (!munGeoCache) {
                        const r = await fetch(munGeoUrl, { credentials: 'same-origin' });
                        if (!r.ok) {
                            throw new Error(`Geo municipios: HTTP ${r.status}`);
                        }
                        munGeoCache = await r.json();
                    }
                    if (!munLayer && munGeoCache) {
                        munLayer = L.geoJSON(munGeoCache, {
                            style: () => neonMunStyle(dark),
                            interactive: false,
                        }).addTo(map);
                        safeBringToFront(munLayer);
                    }
                    markers.forEach((mk) => safeBringToFront(mk));
                } else if (munLayer) {
                    map.removeLayer(munLayer);
                    munLayer = null;
                }
            } catch (e) {
                console.warn('[disciplinary-colombia-map] capa municipios', e);
            }
        };

        map.on('zoomend', () => {
            void syncMunLayer();
        });

        await syncMunLayer().catch(() => {});

        const fixSize = () => {
            try {
                map.invalidateSize(true);
            } catch {
                //
            }
        };
        requestAnimationFrame(fixSize);
        setTimeout(fixSize, 120);
        setTimeout(fixSize, 400);

        el.dataset.colombiaMapMounted = '1';
        el.__disciplinaryColombiaLeafletMap = map;

        const teardown = () => {
            try {
                map.remove();
            } catch {
                //
            }
            el.dataset.colombiaMapMounted = '0';
            delete el.dataset.colombiaMapMounting;
            delete el.__disciplinaryColombiaLeafletMap;
        };

        window.__disciplinaryColombiaMapTeardown = teardown;
    } catch (err) {
        console.error('[disciplinary-colombia-map]', err);
        try {
            map?.remove();
        } catch {
            //
        }
        delete el.__disciplinaryColombiaLeafletMap;
        el.innerHTML =
            '<p class="p-4 text-sm text-amber-100/95 dark:text-amber-200/90">No se pudo cargar el mapa. En el servidor ejecute <code class="rounded bg-black/30 px-1 font-mono text-xs">php artisan geo:download-colombia-gadm</code> y recargue. Los datos se sirven por la ruta <code class="rounded bg-black/30 px-1">disciplinary/map-geo/…</code> (requiere sesión iniciada).</p>';
    } finally {
        delete el.dataset.colombiaMapMounting;
    }
}
