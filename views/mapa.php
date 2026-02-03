<?php
/**
 * Página de mapa interactivo - NUEVA IMPLEMENTACIÓN
 */
define('ROOT_PATH', dirname(__DIR__) . '/');
require_once ROOT_PATH . 'config/config.php';
require_once ROOT_PATH . 'utils/gps_generator.php';

// Generar coordenadas GPS automáticamente si es necesario
try {
    $verificacion = GPSGenerator::verificarApartamentosSinGPS();
    if ($verificacion['success'] && $verificacion['necesita_generacion']) {
        GPSGenerator::generarCoordenadasAutomaticamente();
    }
} catch (Exception $e) {
    // Error silencioso - no interrumpir la carga de la página
    error_log("Error generando GPS automáticamente: " . $e->getMessage());
}

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
        border: 1px solid var(--color-border);
    }
    
    .mapa-controls {
        display: flex;
        gap: var(--space-md);
        flex-wrap: wrap;
        align-items: center;
    }
    
    .mapa-info {
        display: flex;
        align-items: center;
        gap: var(--space-sm);
    }
    
    .badge {
        padding: 4px 12px;
        border-radius: var(--radius-sm);
        font-size: 0.875rem;
        font-weight: 500;
    }
    
    .badge-primary {
        background-color: var(--color-primary);
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
    
    .loading-spinner {
        display: inline-block;
        width: 16px;
        height: 16px;
        border: 2px solid #f3f3f3;
        border-top: 2px solid var(--color-primary);
        border-radius: 50%;
        animation: spin 1s linear infinite;
    }
    
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    
    /* Animación de pulso para estados de carga */
    @keyframes pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.5; }
    }
    
    .loading-text {
        animation: pulse 1.5s ease-in-out infinite;
    }
    
    /* Estilos para alertas */
    .alert {
        padding: var(--space-sm) var(--space-md);
        border-radius: var(--radius-md);
        border: 1px solid;
        font-size: 0.9rem;
    }
    
    .alert-success {
        background-color: rgba(34, 197, 94, 0.1);
        border-color: rgba(34, 197, 94, 0.3);
        color: rgb(21, 128, 61);
    }
    
    .alert-error {
        background-color: rgba(239, 68, 68, 0.1);
        border-color: rgba(239, 68, 68, 0.3);
        color: rgb(153, 27, 27);
    }
    
    .alert-warning {
        background-color: rgba(245, 158, 11, 0.1);
        border-color: rgba(245, 158, 11, 0.3);
        color: rgb(146, 64, 14);
    }
    
    .map-loading {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        height: 100%;
        background: #f8f9fa;
        border-radius: var(--radius-lg);
    }
    
    .map-error {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        height: 100%;
        background: #f8f9fa;
        border-radius: var(--radius-lg);
        text-align: center;
        padding: 2rem;
    }
    
    /* Estilos para marcadores personalizados */
    .custom-marker {
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
        cursor: pointer;
        transition: transform 0.2s ease;
    }
    
    .custom-marker:hover {
        transform: scale(1.1);
    }
    
    /* Estilos para popups */
    .leaflet-popup-content {
        margin: 12px 16px;
        line-height: 1.4;
    }
    
    .leaflet-popup-content h4 {
        margin: 0 0 8px 0;
        color: var(--color-primary-dark);
        font-size: 1.1rem;
    }
    
    .leaflet-popup-content p {
        margin: 4px 0;
        font-size: 0.9rem;
    }
    
    .popup-buttons {
        margin-top: 12px;
        display: flex;
        gap: 8px;
    }
    
    .popup-buttons .btn {
        flex: 1;
        font-size: 0.8rem;
        padding: 6px 12px;
    }
    
    /* Responsive */
    @media (max-width: 768px) {
        .mapa-controls {
            flex-direction: column;
            align-items: stretch;
        }
        
        .mapa-controls select {
            width: 100%;
        }
        
        #mapa-container {
            height: calc(100vh - var(--header-height) - 200px);
            min-height: 400px;
        }
    }
</style>';

$extraJS = <<<'EOD'
<link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.4.1/dist/MarkerCluster.css" />
<link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.4.1/dist/MarkerCluster.Default.css" />
<script src="https://unpkg.com/leaflet.markercluster@1.4.1/dist/leaflet.markercluster.js"></script>

<script>
// Función auxiliar para mostrar notificaciones (fallback si showToast no está disponible)
function safeShowToast(message, type = 'info') {
    if (typeof showToast === 'function') {
        showToast(message, type);
    } else {
        console.log(`Toast (${type}): ${message}`);
        alert(message); // Fallback básico
    }
}

// Verificar conectividad básica
async function checkConnectivity() {
    try {
        const response = await fetch('api/apartamentos.php?action=provincias', {
            method: 'HEAD',
            timeout: 5000
        });
        return response.ok;
    } catch (error) {
        console.error('Error de conectividad:', error);
        return false;
    }
}
let map = null;
let markerCluster = null;
let apartamentos = [];
let currentFilter = '';

// Función de diagnóstico para debugging (disponible en consola)
window.diagnosticarMapa = function() {
    console.log('=== DIAGNÓSTICO DEL MAPA ===');
    console.log('1. Leaflet disponible:', typeof L !== 'undefined');
    console.log('2. MarkerCluster disponible:', typeof L !== 'undefined' && typeof L.markerClusterGroup !== 'undefined');
    console.log('3. apiRequest disponible:', typeof apiRequest !== 'undefined');
    console.log('4. escapeHtml disponible:', typeof escapeHtml !== 'undefined');
    console.log('5. Mapa inicializado:', map !== null);
    console.log('6. Cluster inicializado:', markerCluster !== null);
    console.log('7. Apartamentos cargados:', apartamentos.length);
    console.log('8. Filtro actual:', currentFilter);
    
    // Verificar rutas de API
    console.log('9. Ruta actual:', window.location.pathname);
    console.log('10. API Base calculada:', typeof API_BASE !== 'undefined' ? API_BASE : 'No definida');
    
    if (map) {
        console.log('11. Centro del mapa:', map.getCenter());
        console.log('12. Zoom del mapa:', map.getZoom());
    }
    
    if (markerCluster) {
        console.log('13. Marcadores en cluster:', markerCluster.getLayers().length);
    }
    
    console.log('=== FIN DIAGNÓSTICO ===');
    
    // Test de conectividad
    checkConnectivity().then(connected => {
        console.log('14. Conectividad API:', connected ? 'OK' : 'ERROR');
    });
    
    // Test manual de API
    console.log('=== TEST MANUAL DE API ===');
    apiRequest('apartamentos.php?action=provincias')
        .then(response => {
            console.log('✅ Test provincias exitoso:', response);
        })
        .catch(error => {
            console.error('❌ Test provincias falló:', error);
        });
        
    apiRequest('apartamentos.php?action=mapa')
        .then(response => {
            console.log('✅ Test mapa exitoso:', response);
        })
        .catch(error => {
            console.error('❌ Test mapa falló:', error);
        });
};

// Inicializar el mapa
function initMap() {
    console.log('🗺️ Inicializando mapa...');
    
    try {
        console.log('📍 Creando mapa centrado en Castilla y León...');
        // Crear el mapa centrado en Castilla y León
        map = L.map('mapa-apartamentos').setView([41.6523, -4.7245], 7);
        console.log('✅ Mapa creado correctamente');
        
        console.log('🗺️ Agregando capa de tiles...');
        // Agregar capa de tiles
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors',
            maxZoom: 18
        }).addTo(map);
        console.log('✅ Tiles agregados correctamente');
        
        console.log('🔗 Inicializando cluster de marcadores...');
        // Inicializar cluster de marcadores
        markerCluster = L.markerClusterGroup({
            maxClusterRadius: 50,
            spiderfyOnMaxZoom: true,
            showCoverageOnHover: false,
            zoomToBoundsOnClick: true
        });
        
        map.addLayer(markerCluster);
        console.log('✅ Cluster inicializado y agregado al mapa');
        
        console.log('✅ Mapa inicializado correctamente');
        
        // Cargar datos iniciales
        console.log('📊 Cargando datos iniciales...');
        loadProvincias();
        loadApartamentos();
        
    } catch (error) {
        console.error('❌ Error inicializando mapa:', error);
        showMapError('Error al inicializar el mapa: ' + error.message);
    }
}

// Mostrar estado de carga
function showMapLoading() {
    const container = document.getElementById('mapa-apartamentos');
    container.innerHTML = `
        <div class="map-loading">
            <div class="loading-spinner"></div>
            <p style="margin-top: 1rem;">Cargando mapa...</p>
        </div>
    `;
}

// Mostrar error en el mapa
function showMapError(message) {
    const container = document.getElementById('mapa-apartamentos');
    container.innerHTML = `
        <div class="map-error">
            <div style="font-size: 3rem; margin-bottom: 1rem;">🗺️</div>
            <h3 style="color: var(--color-error); margin-bottom: 0.5rem;">Error al cargar el mapa</h3>
            <p style="color: var(--color-text-muted); margin-bottom: 1.5rem;">${message}</p>
            <button onclick="location.reload()" class="btn btn-primary">Reintentar</button>
        </div>
    `;
}

// Cargar lista de provincias
async function loadProvincias() {
    const select = document.getElementById('provincia-select');
    
    try {
        console.log('📋 Cargando provincias...');
        console.log('🌐 URL de la API:', 'apartamentos.php?action=provincias');
        
        const response = await apiRequest('apartamentos.php?action=provincias');
        console.log('📋 Provincias recibidas:', response);
        
        if (response.success && response.data) {
            select.innerHTML = '<option value="">Todas las provincias</option>';
            
            response.data.forEach(provincia => {
                const option = document.createElement('option');
                option.value = provincia.provincia;
                option.textContent = `${provincia.provincia} (${provincia.total})`;
                select.appendChild(option);
            });
            
            console.log(`✅ Provincias cargadas: ${response.data.length}`);
        } else {
            console.error('❌ Error en respuesta de provincias:', response);
            select.innerHTML = '<option value="">Error al cargar provincias</option>';
            safeShowToast('Error al cargar provincias', 'error');
        }
    } catch (error) {
        console.error('❌ Error cargando provincias:', error);
        select.innerHTML = '<option value="">Error al cargar provincias</option>';
        safeShowToast('Error al cargar provincias: ' + error.message, 'error');
    }
}

// Cargar apartamentos para el mapa
async function loadApartamentos(provincia = '') {
    console.log('🏠 Cargando apartamentos para provincia:', provincia || 'Todas');
    
    updateMapInfo('Cargando apartamentos...', 'loading');
    
    try {
        let url = 'apartamentos.php?action=mapa';
        if (provincia) {
            url += '&provincia=' + encodeURIComponent(provincia);
        }
        
        console.log('🌐 Petición a:', url);
        const response = await apiRequest(url);
        console.log('🏠 Respuesta recibida:', response);
        
        if (response.success && response.data) {
            apartamentos = response.data;
            console.log(`✅ Apartamentos cargados: ${apartamentos.length}`);
            
            if (apartamentos.length === 0) {
                console.warn('⚠️ No se encontraron apartamentos');
                updateMapInfo('No se encontraron apartamentos', 'warning');
            } else {
                displayApartamentosOnMap();
                updateMapInfo(`${apartamentos.length} apartamentos encontrados`, 'success');
            }
        } else {
            console.error('❌ Respuesta de error:', response);
            throw new Error(response.error || 'Error al cargar apartamentos');
        }
    } catch (error) {
        console.error('❌ Error cargando apartamentos:', error);
        updateMapInfo('Error al cargar apartamentos', 'error');
        safeShowToast('Error al cargar apartamentos: ' + error.message, 'error');
    }
}

// Mostrar apartamentos en el mapa
function displayApartamentosOnMap() {
    if (!map || !markerCluster) {
        console.error('Mapa o cluster no inicializados');
        return;
    }
    
    console.log('Mostrando apartamentos en el mapa:', apartamentos.length);
    
    // Limpiar marcadores existentes
    markerCluster.clearLayers();
    
    let markersCreated = 0;
    let apartamentosSinGPS = 0;
    let coordenadasInvalidas = 0;
    
    apartamentos.forEach((apt, index) => {
        console.log(`Procesando apartamento ${index + 1}:`, apt);
        
        if (apt.gps_latitud && apt.gps_longitud) {
            const lat = parseFloat(apt.gps_latitud);
            const lng = parseFloat(apt.gps_longitud);
            
            console.log(`Coordenadas: ${lat}, ${lng}`);
            
            // Validar coordenadas
            if (!isNaN(lat) && !isNaN(lng) && 
                lat >= -90 && lat <= 90 && 
                lng >= -180 && lng <= 180 &&
                lat !== 0 && lng !== 0) {
                
                const marker = createMarker(apt, lat, lng);
                if (marker) {
                    markerCluster.addLayer(marker);
                    markersCreated++;
                    console.log(`Marcador creado para: ${apt.nombre}`);
                } else {
                    console.warn(`No se pudo crear marcador para: ${apt.nombre}`);
                }
            } else {
                coordenadasInvalidas++;
                console.warn(`Coordenadas inválidas para ${apt.nombre}: ${lat}, ${lng}`);
            }
        } else {
            apartamentosSinGPS++;
            console.warn(`Sin GPS: ${apt.nombre}`);
        }
    });
    
    console.log(`Resumen: Marcadores creados: ${markersCreated}, Sin GPS: ${apartamentosSinGPS}, Coordenadas inválidas: ${coordenadasInvalidas}`);
    
    // Ajustar vista del mapa
    if (markersCreated > 0) {
        try {
            const bounds = markerCluster.getBounds();
            if (bounds.isValid()) {
                map.fitBounds(bounds, { padding: [20, 20] });
                console.log('Vista del mapa ajustada a los marcadores');
            } else {
                console.warn('Bounds no válidos, manteniendo vista actual');
            }
        } catch (e) {
            console.warn('Error ajustando vista:', e);
        }
    } else {
        console.warn('No hay marcadores para mostrar');
    }
    
    // Actualizar información
    let infoText = `${markersCreated} apartamentos`;
    if (apartamentosSinGPS > 0) {
        infoText += ` (+${apartamentosSinGPS} sin GPS)`;
    }
    if (coordenadasInvalidas > 0) {
        infoText += ` (+${coordenadasInvalidas} GPS inválido)`;
    }
    updateMapInfo(infoText, markersCreated > 0 ? 'success' : 'warning');
}

// Crear marcador para un apartamento
function createMarker(apt, lat, lng) {
    try {
        console.log(`Creando marcador para ${apt.nombre} en [${lat}, ${lng}]`);
        
        // Crear icono personalizado
        const icon = L.divIcon({
            className: 'custom-marker',
            html: '🏠',
            iconSize: [30, 30],
            iconAnchor: [15, 15],
            popupAnchor: [0, -15]
        });
        
        // Crear marcador
        const marker = L.marker([lat, lng], { icon: icon });
        
        // Crear contenido del popup
        const popupContent = createPopupContent(apt);
        marker.bindPopup(popupContent, {
            maxWidth: 300,
            className: 'custom-popup'
        });
        
        console.log(`Marcador creado exitosamente para ${apt.nombre}`);
        return marker;
        
    } catch (error) {
        console.error(`Error creando marcador para ${apt.nombre}:`, error);
        return null;
    }
}

// Crear contenido del popup
function createPopupContent(apt) {
    const nombre = escapeHtml(apt.nombre || 'Sin nombre');
    const municipio = escapeHtml(apt.municipio || '');
    const provincia = escapeHtml(apt.provincia || '');
    const plazas = apt.plazas || '?';
    const accesible = apt.accesible ? '<p style="color: var(--color-success);">♿ Accesible</p>' : '';
    
    return `
        <div style="min-width: 200px;">
            <h4>${nombre}</h4>
            <p>📍 ${municipio}, ${provincia}</p>
            <p>👥 ${plazas} plazas</p>
            ${accesible}
            <div class="popup-buttons">
                <button onclick="showApartmentDetail(${apt.id})" class="btn btn-primary btn-sm">
                    Ver detalles
                </button>
                <button onclick="openReservation(${apt.id})" class="btn btn-accent btn-sm">
                    Reservar
                </button>
            </div>
        </div>
    `;
}

// Actualizar información del mapa
function updateMapInfo(text, type = 'primary') {
    const infoEl = document.getElementById('map-info');
    if (infoEl) {
        infoEl.textContent = text;
        infoEl.className = `badge badge-${type}`;
        
        if (type === 'loading') {
            infoEl.innerHTML = '<span class="loading-spinner"></span> ' + text;
        }
    }
}

// Manejar cambio de provincia
function handleProvinceChange(event) {
    const provincia = event.target.value;
    currentFilter = provincia;
    loadApartamentos(provincia);
}

// Mostrar detalles del apartamento
async function showApartmentDetail(apartmentId) {
    console.log('🏠 Mostrando detalles del apartamento:', apartmentId);
    
    try {
        // Mostrar loading en el modal
        const modal = document.getElementById('modal-detalle');
        const modalBody = modal.querySelector('.modal-body');
        
        modalBody.innerHTML = `
            <div style="text-align: center; padding: 2rem;">
                <div class="loading-spinner"></div>
                <p style="margin-top: 1rem;">Cargando detalles...</p>
            </div>
        `;
        
        // Mostrar modal
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
        
        // Cargar datos del apartamento
        const response = await apiRequest(`apartamentos.php?action=detalle&id=${apartmentId}`);
        
        if (response.success && response.data) {
            const apt = response.data;
            console.log('✅ Detalles cargados:', apt);
            
            // Crear contenido del modal
            modalBody.innerHTML = `
                <div style="margin-bottom: var(--space-lg);">
                    <h3 style="margin-bottom: var(--space-xs); color: var(--color-primary);">${escapeHtml(apt.nombre)}</h3>
                    <p class="text-muted" style="font-size: 0.9rem;">Registro: ${escapeHtml(apt.n_registro)}</p>
                </div>
                
                <div style="margin-bottom: var(--space-lg);">
                    <h4 style="margin-bottom: var(--space-sm); display: flex; align-items: center; gap: 8px;">
                        📍 Ubicación
                    </h4>
                    <div style="background: var(--color-surface-alt); padding: var(--space-md); border-radius: var(--radius-md);">
                        <p style="margin: 0;"><strong>${escapeHtml(apt.direccion || 'Dirección no disponible')}</strong></p>
                        <p style="margin: 4px 0 0 0; color: var(--color-text-muted);">
                            ${escapeHtml(apt.codigo_postal || '')} ${escapeHtml(apt.localidad || apt.municipio || '')}
                        </p>
                        <p style="margin: 4px 0 0 0; font-weight: 500; color: var(--color-primary);">
                            ${escapeHtml(apt.provincia)}
                        </p>
                    </div>
                </div>
                
                <div style="margin-bottom: var(--space-lg);">
                    <h4 style="margin-bottom: var(--space-sm); display: flex; align-items: center; gap: 8px;">
                        ℹ️ Información del Alojamiento
                    </h4>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: var(--space-md);">
                        <div style="background: var(--color-surface-alt); padding: var(--space-md); border-radius: var(--radius-md); text-align: center;">
                            <div style="font-size: 2rem; margin-bottom: 8px;">👥</div>
                            <div style="font-weight: 600; color: var(--color-primary);">${apt.plazas || 'No especificada'}</div>
                            <div style="font-size: 0.9rem; color: var(--color-text-muted);">Plazas</div>
                        </div>
                        <div style="background: var(--color-surface-alt); padding: var(--space-md); border-radius: var(--radius-md); text-align: center;">
                            <div style="font-size: 2rem; margin-bottom: 8px;">${apt.accesible ? '♿' : '🚶'}</div>
                            <div style="font-weight: 600; color: ${apt.accesible ? 'var(--color-success)' : 'var(--color-text-muted)'};">${apt.accesible ? 'Sí' : 'No'}</div>
                            <div style="font-size: 0.9rem; color: var(--color-text-muted);">Accesible</div>
                        </div>
                        ${apt.categoria ? `
                        <div style="background: var(--color-surface-alt); padding: var(--space-md); border-radius: var(--radius-md); text-align: center;">
                            <div style="font-size: 2rem; margin-bottom: 8px;">⭐</div>
                            <div style="font-weight: 600; color: var(--color-primary);">${escapeHtml(apt.categoria)}</div>
                            <div style="font-size: 0.9rem; color: var(--color-text-muted);">Categoría</div>
                        </div>
                        ` : ''}
                    </div>
                </div>
                
                ${apt.telefono_1 || apt.email || apt.web ? `
                <div style="margin-bottom: var(--space-lg);">
                    <h4 style="margin-bottom: var(--space-sm); display: flex; align-items: center; gap: 8px;">
                        📞 Información de Contacto
                    </h4>
                    <div style="background: var(--color-surface-alt); padding: var(--space-md); border-radius: var(--radius-md);">
                        ${apt.telefono_1 ? `
                        <p style="margin: 0 0 8px 0;">
                            <strong>Teléfono:</strong> 
                            <a href="tel:${apt.telefono_1}" style="color: var(--color-primary);">${apt.telefono_1}</a>
                        </p>
                        ` : ''}
                        ${apt.email ? `
                        <p style="margin: 0 0 8px 0;">
                            <strong>Email:</strong> 
                            <a href="mailto:${apt.email}" style="color: var(--color-primary);">${apt.email}</a>
                        </p>
                        ` : ''}
                        ${apt.web ? `
                        <p style="margin: 0;">
                            <strong>Web:</strong> 
                            <a href="${apt.web}" target="_blank" rel="noopener" style="color: var(--color-primary);">
                                Sitio web oficial →
                            </a>
                        </p>
                        ` : ''}
                    </div>
                </div>
                ` : ''}
            `;
            
            // Configurar botón de reserva
            const btnReservar = document.getElementById('btn-reservar-apartamento');
            if (btnReservar) {
                btnReservar.style.display = 'inline-flex';
                btnReservar.onclick = () => openReservation(apartmentId);
            }
            
        } else {
            throw new Error(response.error || 'Error al cargar detalles');
        }
        
    } catch (error) {
        console.error('❌ Error mostrando detalles:', error);
        
        const modal = document.getElementById('modal-detalle');
        const modalBody = modal.querySelector('.modal-body');
        
        modalBody.innerHTML = `
            <div style="text-align: center; padding: 2rem;">
                <div style="font-size: 3rem; margin-bottom: 1rem;">⚠️</div>
                <h3 style="color: var(--color-error); margin-bottom: 0.5rem;">Error al cargar detalles</h3>
                <p style="color: var(--color-text-muted); margin-bottom: 1.5rem;">${error.message}</p>
                <button onclick="showApartmentDetail(${apartmentId})" class="btn btn-primary">
                    Reintentar
                </button>
            </div>
        `;
        
        safeShowToast('Error al cargar detalles del apartamento', 'error');
    }
}

// Abrir reserva
async function openReservation(apartmentId) {
    console.log('📅 Abriendo reserva para apartamento:', apartmentId);
    
    // Verificar si el usuario está logueado
    if (!isUserLoggedIn()) {
        safeShowToast('Debe iniciar sesión para hacer una reserva', 'warning');
        // Abrir modal de login
        const loginModal = document.getElementById('modal-login');
        if (loginModal) {
            loginModal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }
        return;
    }
    
    try {
        // Cargar datos del apartamento para la reserva
        console.log('🔄 Cargando datos del apartamento...');
        const response = await apiRequest(`apartamentos.php?action=detalle&id=${apartmentId}`);
        
        if (response.success && response.data) {
            const apt = response.data;
            console.log('✅ Datos del apartamento cargados para reserva');
            
            // Cerrar modal de detalles si está abierto
            const detalleModal = document.getElementById('modal-detalle');
            if (detalleModal && detalleModal.classList.contains('active')) {
                detalleModal.classList.remove('active');
            }
            
            // Abrir modal de reserva
            showReservationModal(apt);
            
        } else {
            throw new Error(response.error || 'Error al cargar datos del apartamento');
        }
        
    } catch (error) {
        console.error('❌ Error abriendo reserva:', error);
        safeShowToast('Error al abrir reserva: ' + error.message, 'error');
    }
}

// Verificar si el usuario está logueado
function isUserLoggedIn() {
    // Verificar si hay elementos del menú de usuario visibles
    const userMenu = document.getElementById('user-menu');
    const loginBtn = document.getElementById('btn-login');
    
    if (userMenu && loginBtn) {
        return userMenu.style.display !== 'none' && loginBtn.style.display === 'none';
    }
    
    // Fallback: verificar si hay nombre de usuario
    const userName = document.getElementById('user-name');
    return userName && userName.textContent.trim() !== '';
}

// Mostrar modal de reserva
function showReservationModal(apartamento) {
    console.log('📅 Mostrando modal de reserva para:', apartamento.nombre);
    
    const modal = document.getElementById('modal-reserva');
    const apartamentoInfo = document.getElementById('reserva-apartamento-info');
    const idApartamentoInput = document.getElementById('reserva-id-apartamento');
    
    if (!modal || !apartamentoInfo || !idApartamentoInput) {
        console.error('❌ Elementos del modal de reserva no encontrados');
        safeShowToast('Error: Modal de reserva no disponible', 'error');
        return;
    }
    
    // Configurar información del apartamento
    apartamentoInfo.innerHTML = `
        <div style="display: flex; align-items: center; gap: var(--space-md);">
            <div style="font-size: 2rem;">🏠</div>
            <div style="flex: 1;">
                <h4 style="margin: 0 0 4px 0; color: var(--color-primary);">${escapeHtml(apartamento.nombre)}</h4>
                <p style="margin: 0; color: var(--color-text-muted); font-size: 0.9rem;">
                    📍 ${escapeHtml(apartamento.municipio || '')}, ${escapeHtml(apartamento.provincia)}
                </p>
                <p style="margin: 4px 0 0 0; font-weight: 500;">
                    👥 Capacidad: ${apartamento.plazas || '?'} personas
                    ${apartamento.accesible ? ' • ♿ Accesible' : ''}
                </p>
            </div>
        </div>
    `;
    
    // Configurar ID del apartamento
    idApartamentoInput.value = apartamento.id;
    
    // Configurar fechas mínimas
    const today = new Date().toISOString().split('T')[0];
    const tomorrow = new Date();
    tomorrow.setDate(tomorrow.getDate() + 1);
    const tomorrowStr = tomorrow.toISOString().split('T')[0];
    
    const fechaEntrada = document.getElementById('reserva-fecha-entrada');
    const fechaSalida = document.getElementById('reserva-fecha-salida');
    
    if (fechaEntrada && fechaSalida) {
        fechaEntrada.min = today;
        fechaSalida.min = tomorrowStr;
        
        // Limpiar fechas anteriores
        fechaEntrada.value = '';
        fechaSalida.value = '';
    }
    
    // Configurar número máximo de huéspedes
    const numHuespedes = document.getElementById('reserva-num-huespedes');
    if (numHuespedes && apartamento.plazas) {
        // Limpiar opciones existentes
        numHuespedes.innerHTML = '<option value="">Seleccionar...</option>';
        
        // Agregar opciones hasta la capacidad máxima
        for (let i = 1; i <= Math.min(apartamento.plazas, 12); i++) {
            const option = document.createElement('option');
            option.value = i;
            option.textContent = i === 1 ? '1 huésped' : `${i} huéspedes`;
            numHuespedes.appendChild(option);
        }
    }
    
    // Limpiar notas
    const notas = document.getElementById('reserva-notas');
    if (notas) {
        notas.value = '';
    }
    
    // Resetear botón de confirmación
    const btnConfirmar = document.getElementById('btn-confirmar-reserva');
    if (btnConfirmar) {
        btnConfirmar.disabled = true;
        btnConfirmar.querySelector('.btn-text').textContent = 'Confirmar reserva';
        btnConfirmar.querySelector('.btn-loading').style.display = 'none';
    }
    
    // Ocultar información de disponibilidad
    const disponibilidadInfo = document.getElementById('disponibilidad-info');
    if (disponibilidadInfo) {
        disponibilidadInfo.style.display = 'none';
    }
    
    // Mostrar modal
    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
    
    console.log('✅ Modal de reserva configurado y mostrado');
}

// Limpiar filtros
function clearFilters() {
    const select = document.getElementById('provincia-select');
    if (select) {
        select.value = '';
        currentFilter = '';
        loadApartamentos();
    }
}

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    console.log('🗺️ DOM listo, inicializando mapa...');
    console.log('💡 Tip: Ejecuta diagnosticarMapa() en la consola para información de debugging');
    
    // Mostrar estado de carga inicial
    showMapLoading();
    
    // Verificar dependencias paso a paso
    console.log('🔍 Verificando dependencias...');
    
    if (typeof L === 'undefined') {
        console.error('❌ Leaflet no está disponible');
        showMapError('Error: Leaflet no se ha cargado correctamente. Verifica tu conexión a internet.');
        return;
    }
    console.log('✅ Leaflet disponible');
    
    if (typeof apiRequest === 'undefined') {
        console.error('❌ apiRequest no está disponible');
        showMapError('Error: Funciones de API no disponibles. Recarga la página.');
        return;
    }
    console.log('✅ apiRequest disponible');
    
    if (typeof escapeHtml === 'undefined') {
        console.error('❌ escapeHtml no está disponible');
        showMapError('Error: Funciones de seguridad no disponibles. Recarga la página.');
        return;
    }
    console.log('✅ escapeHtml disponible');
    
    // Esperar un poco para que MarkerCluster se cargue
    console.log('⏳ Esperando MarkerCluster...');
    setTimeout(() => {
        if (typeof L.markerClusterGroup === 'undefined') {
            console.error('❌ MarkerCluster no está disponible');
            showMapError('Error: Plugin de clustering no disponible. Recarga la página.');
            return;
        }
        console.log('✅ MarkerCluster disponible');
        
        console.log('🚀 Todas las dependencias están disponibles, inicializando mapa...');
        initMap();
        
        // Configurar eventos del formulario de reserva
        setupReservationForm();
        
        // Configurar eventos de modales
        setupModalEvents();
    }, 500);
});

// Configurar eventos de modales
function setupModalEvents() {
    console.log('🔧 Configurando eventos de modales...');
    
    // Cerrar modales con botones de cierre
    document.querySelectorAll('[data-modal-close]').forEach(btn => {
        btn.addEventListener('click', function() {
            const modal = this.closest('.modal-overlay');
            if (modal) {
                modal.classList.remove('active');
                document.body.style.overflow = '';
            }
        });
    });
    
    // Cerrar modales haciendo clic fuera
    document.querySelectorAll('.modal-overlay').forEach(modal => {
        modal.addEventListener('click', function(e) {
            if (e.target === this) {
                this.classList.remove('active');
                document.body.style.overflow = '';
            }
        });
    });
    
    // Cerrar modales con Escape
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const activeModal = document.querySelector('.modal-overlay.active');
            if (activeModal) {
                activeModal.classList.remove('active');
                document.body.style.overflow = '';
            }
        }
    });
    
    console.log('✅ Eventos de modales configurados');
}

// Configurar eventos del formulario de reserva
function setupReservationForm() {
    console.log('📅 Configurando formulario de reserva...');
    
    const form = document.getElementById('form-reserva');
    const fechaEntrada = document.getElementById('reserva-fecha-entrada');
    const fechaSalida = document.getElementById('reserva-fecha-salida');
    const numHuespedes = document.getElementById('reserva-num-huespedes');
    const btnConfirmar = document.getElementById('btn-confirmar-reserva');
    
    if (!form) {
        console.warn('⚠️ Formulario de reserva no encontrado');
        return;
    }
    
    // Validar fechas cuando cambian
    if (fechaEntrada && fechaSalida) {
        fechaEntrada.addEventListener('change', validateReservationForm);
        fechaSalida.addEventListener('change', validateReservationForm);
    }
    
    if (numHuespedes) {
        numHuespedes.addEventListener('change', validateReservationForm);
    }
    
    // Manejar envío del formulario
    form.addEventListener('submit', handleReservationSubmit);
    
    console.log('✅ Formulario de reserva configurado');
}

// Validar formulario de reserva
function validateReservationForm() {
    const fechaEntrada = document.getElementById('reserva-fecha-entrada');
    const fechaSalida = document.getElementById('reserva-fecha-salida');
    const numHuespedes = document.getElementById('reserva-num-huespedes');
    const btnConfirmar = document.getElementById('btn-confirmar-reserva');
    const disponibilidadInfo = document.getElementById('disponibilidad-info');
    
    if (!fechaEntrada || !fechaSalida || !numHuespedes || !btnConfirmar) return;
    
    const entrada = fechaEntrada.value;
    const salida = fechaSalida.value;
    const huespedes = numHuespedes.value;
    
    // Validar que todos los campos estén completos
    const isValid = entrada && salida && huespedes && 
                   new Date(entrada) < new Date(salida) &&
                   new Date(entrada) >= new Date().setHours(0,0,0,0);
    
    btnConfirmar.disabled = !isValid;
    
    // Si las fechas son válidas, verificar disponibilidad
    if (isValid && disponibilidadInfo) {
        checkAvailability();
    } else if (disponibilidadInfo) {
        disponibilidadInfo.style.display = 'none';
    }
}

// Verificar disponibilidad
async function checkAvailability() {
    const idApartamento = document.getElementById('reserva-id-apartamento').value;
    const fechaEntrada = document.getElementById('reserva-fecha-entrada').value;
    const fechaSalida = document.getElementById('reserva-fecha-salida').value;
    const disponibilidadInfo = document.getElementById('disponibilidad-info');
    
    if (!idApartamento || !fechaEntrada || !fechaSalida || !disponibilidadInfo) return;
    
    try {
        disponibilidadInfo.innerHTML = `
            <div style="display: flex; align-items: center; gap: 8px;">
                <div class="loading-spinner" style="width: 16px; height: 16px;"></div>
                <span>Verificando disponibilidad...</span>
            </div>
        `;
        disponibilidadInfo.className = 'alert';
        disponibilidadInfo.style.display = 'block';
        
        const response = await apiRequest(
            `reservas.php?action=disponibilidad&id_apartamento=${idApartamento}&fecha_entrada=${fechaEntrada}&fecha_salida=${fechaSalida}`
        );
        
        if (response.success) {
            if (response.disponible) {
                disponibilidadInfo.innerHTML = `
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <span style="color: var(--color-success);">✅</span>
                        <span><strong>Disponible</strong> - Puedes reservar estas fechas</span>
                    </div>
                `;
                disponibilidadInfo.className = 'alert alert-success';
            } else {
                disponibilidadInfo.innerHTML = `
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <span style="color: var(--color-error);">❌</span>
                        <span><strong>No disponible</strong> - Estas fechas ya están ocupadas</span>
                    </div>
                `;
                disponibilidadInfo.className = 'alert alert-error';
                
                const btnConfirmar = document.getElementById('btn-confirmar-reserva');
                if (btnConfirmar) btnConfirmar.disabled = true;
            }
        } else {
            throw new Error(response.error || 'Error verificando disponibilidad');
        }
        
    } catch (error) {
        console.error('❌ Error verificando disponibilidad:', error);
        disponibilidadInfo.innerHTML = `
            <div style="display: flex; align-items: center; gap: 8px;">
                <span style="color: var(--color-warning);">⚠️</span>
                <span>No se pudo verificar disponibilidad</span>
            </div>
        `;
        disponibilidadInfo.className = 'alert alert-warning';
    }
}

// Manejar envío del formulario de reserva
async function handleReservationSubmit(event) {
    event.preventDefault();
    
    console.log('📅 Enviando reserva...');
    
    const form = event.target;
    const btnConfirmar = document.getElementById('btn-confirmar-reserva');
    const btnText = btnConfirmar.querySelector('.btn-text');
    const btnLoading = btnConfirmar.querySelector('.btn-loading');
    
    // Mostrar estado de carga
    btnConfirmar.disabled = true;
    btnText.style.display = 'none';
    btnLoading.style.display = 'inline-flex';
    
    try {
        // Recopilar datos del formulario
        const formData = new FormData(form);
        const reservaData = {
            id_apartamento: formData.get('id_apartamento'),
            fecha_entrada: formData.get('fecha_entrada'),
            fecha_salida: formData.get('fecha_salida'),
            num_huespedes: formData.get('num_huespedes'),
            notas: formData.get('notas') || ''
        };
        
        console.log('📤 Datos de reserva:', reservaData);
        
        // Enviar reserva
        const response = await apiRequest('reservas.php?action=crear', {
            method: 'POST',
            body: JSON.stringify(reservaData)
        });
        
        if (response.success) {
            console.log('✅ Reserva creada exitosamente');
            
            // Cerrar modal
            const modal = document.getElementById('modal-reserva');
            modal.classList.remove('active');
            document.body.style.overflow = '';
            
            // Mostrar mensaje de éxito
            safeShowToast('¡Reserva creada exitosamente!', 'success');
            
            // Opcional: redirigir a mis reservas después de un momento
            setTimeout(() => {
                if (confirm('¿Desea ver sus reservas?')) {
                    window.location.href = 'mis-reservas.php';
                }
            }, 2000);
            
        } else {
            throw new Error(response.error || 'Error al crear la reserva');
        }
        
    } catch (error) {
        console.error('❌ Error creando reserva:', error);
        safeShowToast('Error al crear reserva: ' + error.message, 'error');
        
    } finally {
        // Restaurar botón
        btnConfirmar.disabled = false;
        btnText.style.display = 'inline';
        btnLoading.style.display = 'none';
    }
}
</script>
EOD;

include ROOT_PATH . 'views/partials/header.php';
?>

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
                        Explora todos los apartamentos turísticos de Castilla y León
                    </p>
                </div>
                <div class="mapa-controls">
                    <select id="provincia-select" class="form-select" onchange="handleProvinceChange(event)" style="min-width: 200px;">
                        <option value="">Cargando provincias...</option>
                    </select>
                    <button onclick="clearFilters()" class="btn btn-secondary btn-sm">
                        Limpiar filtros
                    </button>
                    <div class="mapa-info">
                        <span id="map-info" class="badge badge-primary">Inicializando...</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Contenedor del mapa -->
    <section class="py-2">
        <div class="container">
            <div id="mapa-container">
                <div id="mapa-apartamentos"></div>
            </div>
        </div>
    </section>
</main>

<?php include ROOT_PATH . 'views/partials/footer.php'; ?>