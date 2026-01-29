<?php
/**
 * Página de mapa interactivo
 */
define('ROOT_PATH', dirname(__DIR__) . '/');
require_once ROOT_PATH . 'config/config.php';

$pageTitle = 'Mapa';

$extraCSS = '<style>
    #mapa-container {
        height: calc(100vh - var(--header-height) - 120px);
        min-height: 500px;
    }
    #mapa-apartamentos {
        height: 100%;
        width: 100%;
        border-radius: var(--radius-lg);
    }
    .mapa-controls {
        display: flex;
        gap: var(--space-md);
        flex-wrap: wrap;
        align-items: center;
    }
    .leaflet-popup-content h4 {
        margin: 0 0 8px 0;
        color: var(--color-primary-dark);
    }
    .leaflet-popup-content p {
        margin: 4px 0;
        font-size: 0.9rem;
    }
</style>';

include ROOT_PATH . 'views/partials/header.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="/Proyecto-Intermodular/public/css/styles.css">
</head>
<body>
    
</body>
</html>
<main>
    <!-- Header de página -->
    <section style="background: linear-gradient(135deg, var(--color-primary-dark), var(--color-primary)); padding: var(--space-xl) 0; color: white;">
        <div class="container">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: var(--space-lg);">
                <div>
                    <h1 style="color: white; margin-bottom: var(--space-xs); font-size: clamp(1.5rem, 4vw, 2rem);">
                        Mapa de apartamentos
                    </h1>
                    <p style="opacity: 0.9;">
                        Visualiza todos los apartamentos en el mapa interactivo
                    </p>
                </div>
                <div class="mapa-controls">
                    <select id="filtro-mapa-provincia" class="form-select" style="min-width: 200px;">
                        <option value="">Todas las provincias</option>
                    </select>
                    <span id="mapa-count" class="badge badge-accent" style="font-size: 0.9rem; padding: 8px 16px;">
                        Cargando...
                    </span>
                </div>
            </div>
        </div>
    </section>

    <section class="py-2">
        <div class="container">
            <div id="mapa-container">
                <div id="mapa-apartamentos"></div>
            </div>
        </div>
    </section>
</main>

<?php include ROOT_PATH . 'views/partials/footer.php'; ?>

<script>
let map;
let markers = [];
let markerCluster;

// Inicializar mapa
function initMapa() {
    // Centro de Castilla y León
    map = L.map('mapa-apartamentos').setView([41.6523, -4.7245], 7);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
        maxZoom: 18
    }).addTo(map);

    // Cargar marcadores
    loadMapMarkers();
    loadProvinciasSelect();
}

// Cargar provincias en el select
async function loadProvinciasSelect() {
    try {
        const response = await apiRequest('apartamentos.php?action=provincias');
        const select = document.getElementById('filtro-mapa-provincia');
        
        if (response.success && response.data) {
            response.data.forEach(p => {
                select.innerHTML += `<option value="${p.provincia}">${p.provincia} (${p.total})</option>`;
            });
        }
        
        // Evento de cambio
        select.addEventListener('change', (e) => {
            loadMapMarkers(e.target.value);
        });
    } catch (e) {
        console.error('Error:', e);
    }
}

// Cargar marcadores en el mapa
async function loadMapMarkers(provincia = '') {
    const countEl = document.getElementById('mapa-count');
    countEl.textContent = 'Cargando...';

    // Limpiar marcadores anteriores
    markers.forEach(m => map.removeLayer(m));
    markers = [];

    try {
        let url = 'apartamentos.php?action=mapa';
        if (provincia) url += `&provincia=${encodeURIComponent(provincia)}`;

        const response = await apiRequest(url);

        if (response.success && response.data) {
            const iconDefault = L.divIcon({
                className: 'custom-marker',
                html: '<div style="background: var(--color-primary); width: 30px; height: 30px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 14px; box-shadow: 0 2px 8px rgba(0,0,0,0.3);">🏠</div>',
                iconSize: [30, 30],
                iconAnchor: [15, 15]
            });

            response.data.forEach(apt => {
                if (apt.gps_latitud && apt.gps_longitud) {
                    const marker = L.marker([apt.gps_latitud, apt.gps_longitud])
                        .addTo(map)
                        .bindPopup(`
                            <div style="min-width: 200px;">
                                <h4>${escapeHtml(apt.nombre)}</h4>
                                <p>📍 ${escapeHtml(apt.municipio || '')}, ${escapeHtml(apt.provincia)}</p>
                                <p>👥 ${apt.capacidad_alojamiento || '?'} plazas</p>
                                ${apt.accesible ? '<p>♿ Accesible</p>' : ''}
                                <button onclick="ApartamentosModule.showDetail(${apt.id})" 
                                        class="btn btn-primary btn-sm" style="margin-top: 8px; width: 100%;">
                                    Ver detalles
                                </button>
                            </div>
                        `);
                    markers.push(marker);
                }
            });

            countEl.textContent = `${response.data.length} apartamentos`;

            // Ajustar vista si hay marcadores
            if (markers.length > 0) {
                const group = new L.featureGroup(markers);
                map.fitBounds(group.getBounds().pad(0.1));
            }
        }
    } catch (e) {
        console.error('Error cargando marcadores:', e);
        countEl.textContent = 'Error al cargar';
    }
}

// Iniciar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', initMapa);
</script>