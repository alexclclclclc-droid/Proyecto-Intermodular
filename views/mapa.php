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
    .custom-popup .leaflet-popup-content {
        margin: 12px 16px;
        line-height: 1.4;
    }
    .custom-popup .leaflet-popup-content-wrapper {
        border-radius: var(--radius-lg);
        box-shadow: var(--shadow-lg);
    }
    .custom-popup .leaflet-popup-tip {
        background: white;
    }
    .custom-marker {
        filter: drop-shadow(0 2px 4px rgba(0,0,0,0.2));
    }
    
    /* Estilos personalizados para clusters */
    .marker-cluster {
        background-clip: padding-box;
        border-radius: 50%;
        border: 2px solid rgba(255,255,255,0.8);
        box-shadow: 0 2px 8px rgba(0,0,0,0.3);
    }
    .marker-cluster div {
        width: 36px;
        height: 36px;
        margin-left: 2px;
        margin-top: 2px;
        text-align: center;
        border-radius: 50%;
        font: 12px "Helvetica Neue", Arial, Helvetica, sans-serif;
        font-weight: bold;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .marker-cluster-small {
        background-color: var(--color-primary);
    }
    .marker-cluster-small div {
        background-color: var(--color-primary);
        color: white;
    }
    .marker-cluster-medium {
        background-color: var(--color-accent);
    }
    .marker-cluster-medium div {
        background-color: var(--color-accent);
        color: white;
    }
    .marker-cluster-large {
        background-color: var(--color-error);
    }
    .marker-cluster-large div {
        background-color: var(--color-error);
        color: white;
    }
    
    /* Estilos para estados de carga y filtros */
    .badge.loading {
        position: relative;
        overflow: hidden;
    }
    .badge.loading::after {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
        animation: loading-shimmer 1.5s infinite;
    }
    @keyframes loading-shimmer {
        0% { left: -100%; }
        100% { left: 100%; }
    }
    
    .mapa-controls select:focus {
        outline: 2px solid var(--color-primary);
        outline-offset: 2px;
    }
    
    .mapa-controls .badge {
        transition: all 0.3s ease;
    }
    
    .badge-error {
        background-color: var(--color-error);
        color: white;
    }
    
    .badge-warning {
        background-color: var(--color-warning);
        color: var(--color-text-dark);
    }
    
    .badge-muted {
        background-color: var(--color-text-muted);
        color: white;
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
    <link rel="stylesheet" href="/PruebaFinal/public/css/styles.css">
    <!-- Leaflet MarkerCluster CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.4.1/dist/MarkerCluster.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.4.1/dist/MarkerCluster.Default.css" />
</head>
<body>
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

<!-- Leaflet MarkerCluster JS -->
<script src="https://unpkg.com/leaflet.markercluster@1.4.1/dist/leaflet.markercluster.js"></script>

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

    // Inicializar cluster de marcadores
    markerCluster = L.markerClusterGroup({
        // Configuración del clustering
        maxClusterRadius: 50, // Radio máximo para agrupar marcadores
        spiderfyOnMaxZoom: true, // Expandir cluster al hacer zoom máximo
        showCoverageOnHover: false, // No mostrar área de cobertura al hover
        zoomToBoundsOnClick: true, // Hacer zoom al cluster al hacer click
        // Personalizar iconos de cluster
        iconCreateFunction: function(cluster) {
            const count = cluster.getChildCount();
            let className = 'marker-cluster-small';
            
            if (count > 10) {
                className = 'marker-cluster-large';
            } else if (count > 5) {
                className = 'marker-cluster-medium';
            }
            
            return new L.DivIcon({
                html: `<div><span>${count}</span></div>`,
                className: `marker-cluster ${className}`,
                iconSize: new L.Point(40, 40)
            });
        }
    });

    // Agregar cluster al mapa
    map.addLayer(markerCluster);

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
            // Limpiar opciones existentes excepto la primera
            select.innerHTML = '<option value="">Todas las provincias</option>';
            
            response.data.forEach(p => {
                select.innerHTML += `<option value="${p.provincia}">${p.provincia} (${p.total})</option>`;
            });
        }
        
        // Evento de cambio mejorado
        select.addEventListener('change', async (e) => {
            const selectedProvincia = e.target.value;
            
            // Mostrar indicador de carga
            const countEl = document.getElementById('mapa-count');
            if (countEl) {
                countEl.textContent = 'Filtrando...';
                countEl.className = 'badge badge-accent loading';
            }
            
            try {
                // Recargar marcadores con filtro
                await loadMapMarkers(selectedProvincia);
                
                // Actualizar texto del select para mostrar selección actual
                updateFilterDisplay(selectedProvincia);
                
            } catch (error) {
                console.error('Error filtering map:', error);
                if (countEl) {
                    countEl.textContent = 'Error al filtrar';
                    countEl.className = 'badge badge-error';
                }
            }
        });
    } catch (e) {
        console.error('Error loading provinces:', e);
        showToast('Error al cargar provincias', 'error');
    }
}

// Actualizar visualización del filtro
function updateFilterDisplay(selectedProvincia) {
    const select = document.getElementById('filtro-mapa-provincia');
    const countEl = document.getElementById('mapa-count');
    
    if (select && countEl) {
        // Remover clase de carga
        countEl.className = 'badge badge-accent';
        
        // Actualizar título de la sección si es necesario
        const headerTitle = document.querySelector('h1');
        if (headerTitle) {
            if (selectedProvincia) {
                headerTitle.textContent = `Mapa de apartamentos - ${selectedProvincia}`;
            } else {
                headerTitle.textContent = 'Mapa de apartamentos';
            }
        }
    }
}

// Función para limpiar filtros
function clearMapFilters() {
    const select = document.getElementById('filtro-mapa-provincia');
    if (select) {
        select.value = '';
        loadMapMarkers('');
        updateFilterDisplay('');
    }
}

// Función para obtener estado actual del filtro
function getCurrentFilter() {
    const select = document.getElementById('filtro-mapa-provincia');
    return select ? select.value : '';
}

// Cargar marcadores en el mapa
async function loadMapMarkers(provincia = '') {
    const countEl = document.getElementById('mapa-count');
    countEl.textContent = 'Cargando...';
    countEl.className = 'badge badge-accent loading';

    // Limpiar marcadores anteriores del cluster
    markerCluster.clearLayers();
    markers = [];

    try {
        let url = 'apartamentos.php?action=mapa';
        if (provincia) url += `&provincia=${encodeURIComponent(provincia)}`;

        const response = await apiRequest(url);

        if (response.success && response.data) {
            let markersCreated = 0;
            let apartamentosWithoutGPS = 0;
            
            response.data.forEach(apt => {
                if (apt.gps_latitud && apt.gps_longitud) {
                    // Crear marcador personalizado
                    const marker = L.marker([parseFloat(apt.gps_latitud), parseFloat(apt.gps_longitud)], {
                        icon: L.divIcon({
                            className: 'custom-marker',
                            html: `<div style="
                                background: var(--color-primary); 
                                width: 30px; 
                                height: 30px; 
                                border-radius: 50%; 
                                display: flex; 
                                align-items: center; 
                                justify-content: center; 
                                color: white; 
                                font-size: 14px; 
                                box-shadow: 0 2px 8px rgba(0,0,0,0.3);
                                border: 2px solid white;
                            ">🏠</div>`,
                            iconSize: [30, 30],
                            iconAnchor: [15, 15],
                            popupAnchor: [0, -15]
                        })
                    });

                    // Crear popup con información completa
                    const popupContent = `
                        <div style="min-width: 200px; max-width: 250px;">
                            <h4 style="margin: 0 0 8px 0; color: var(--color-primary-dark); font-size: 1.1rem;">
                                ${escapeHtml(apt.nombre)}
                            </h4>
                            <p style="margin: 4px 0; font-size: 0.9rem; color: var(--color-text-muted);">
                                📍 ${escapeHtml(apt.municipio || '')}, ${escapeHtml(apt.provincia)}
                            </p>
                            ${apt.localidad ? `<p style="margin: 4px 0; font-size: 0.85rem;">📌 ${escapeHtml(apt.localidad)}</p>` : ''}
                            ${apt.nucleo ? `<p style="margin: 4px 0; font-size: 0.85rem;">🏘️ ${escapeHtml(apt.nucleo)}</p>` : ''}
                            <p style="margin: 4px 0; font-size: 0.9rem;">
                                👥 ${apt.plazas || '?'} plazas
                            </p>
                            ${apt.accesible ? '<p style="margin: 4px 0; font-size: 0.85rem; color: var(--color-success);">♿ Accesible</p>' : ''}
                            <div style="margin-top: 12px; display: flex; gap: 8px;">
                                <button onclick="ApartamentosModule.showDetail(${apt.id})" 
                                        class="btn btn-primary btn-sm" style="flex: 1; font-size: 0.8rem;">
                                    Ver detalles
                                </button>
                                <button onclick="openReservaFromMap(${apt.id})" 
                                        class="btn btn-accent btn-sm" style="flex: 1; font-size: 0.8rem;">
                                    📅 Reservar
                                </button>
                            </div>
                        </div>
                    `;

                    marker.bindPopup(popupContent, {
                        maxWidth: 300,
                        className: 'custom-popup'
                    });

                    // Agregar marcador al cluster en lugar de directamente al mapa
                    markerCluster.addLayer(marker);
                    markers.push(marker);
                    markersCreated++;
                } else {
                    apartamentosWithoutGPS++;
                }
            });

            // Actualizar contador con información detallada
            updateMarkerCount(markersCreated, apartamentosWithoutGPS, provincia);

            // Ajustar vista si hay marcadores
            if (markers.length > 0) {
                // Usar los bounds del cluster para mejor ajuste
                const bounds = markerCluster.getBounds();
                if (bounds.isValid()) {
                    map.fitBounds(bounds.pad(0.1));
                } else {
                    // Fallback si no hay bounds válidos
                    const group = new L.featureGroup(markers);
                    map.fitBounds(group.getBounds().pad(0.1));
                }
            } else {
                // Si no hay marcadores, centrar en Castilla y León
                map.setView([41.6523, -4.7245], 7);
            }
            
            // Actualizar sincronización con filtros
            syncFilterState(provincia, response.data.length);
            
        } else {
            throw new Error(response.error || 'Error al cargar datos del mapa');
        }
    } catch (e) {
        console.error('Error cargando marcadores:', e);
        countEl.textContent = 'Error al cargar';
        countEl.className = 'badge badge-error';
        
        // Mostrar mensaje de error en el mapa
        const errorPopup = L.popup()
            .setLatLng([41.6523, -4.7245])
            .setContent(`
                <div style="text-align: center; padding: 10px;">
                    <h4 style="color: var(--color-error); margin: 0 0 8px 0;">Error al cargar apartamentos</h4>
                    <p style="margin: 0; font-size: 0.9rem;">${e.message}</p>
                    <button onclick="loadMapMarkers('${provincia}')" class="btn btn-primary btn-sm" style="margin-top: 8px;">
                        Reintentar
                    </button>
                </div>
            `)
            .openOn(map);
    }
}

// Actualizar contador de marcadores con información detallada
function updateMarkerCount(markersCreated, apartamentosWithoutGPS, provincia) {
    const countEl = document.getElementById('mapa-count');
    if (!countEl) return;
    
    countEl.className = 'badge badge-accent';
    
    if (markersCreated === 0) {
        if (apartamentosWithoutGPS > 0) {
            countEl.textContent = `${apartamentosWithoutGPS} apartamentos sin GPS`;
            countEl.className = 'badge badge-warning';
        } else {
            countEl.textContent = 'No hay apartamentos';
            countEl.className = 'badge badge-muted';
        }
    } else {
        let text = `${markersCreated} apartamento${markersCreated !== 1 ? 's' : ''}`;
        
        if (provincia) {
            text += ` en ${provincia}`;
        }
        
        if (apartamentosWithoutGPS > 0) {
            text += ` (+${apartamentosWithoutGPS} sin GPS)`;
        }
        
        countEl.textContent = text;
    }
}

// Sincronizar estado del filtro con los datos mostrados
function syncFilterState(selectedProvincia, totalApartamentos) {
    const select = document.getElementById('filtro-mapa-provincia');
    if (!select) return;
    
    // Asegurar que el select refleje el filtro actual
    if (select.value !== selectedProvincia) {
        select.value = selectedProvincia;
    }
    
    // Actualizar opciones del select con conteos actualizados si es necesario
    if (!selectedProvincia) {
        // Si no hay filtro, podríamos actualizar los conteos totales
        // Esto es opcional y podría requerir una llamada adicional a la API
    }
    
    // Disparar evento personalizado para notificar cambios
    const event = new CustomEvent('mapFilterChanged', {
        detail: {
            provincia: selectedProvincia,
            totalApartamentos: totalApartamentos,
            markersShown: markers.length
        }
    });
    document.dispatchEvent(event);
}

// Función para abrir reserva desde el mapa
async function openReservaFromMap(apartamentoId) {
    try {
        // Obtener datos del apartamento
        const response = await apiRequest(`apartamentos.php?action=detalle&id=${apartamentoId}`);
        
        if (response.success && response.data) {
            // Usar el módulo de reservas existente
            ReservaModule.showReservaForm(response.data);
        } else {
            showToast('Error al cargar datos del apartamento', 'error');
        }
    } catch (error) {
        console.error('Error opening reservation from map:', error);
        showToast('Error al abrir reserva', 'error');
    }
}

// Iniciar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', initMapa);
</script>
