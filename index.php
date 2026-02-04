<?php
/**
 * Página principal - Apartamentos Turísticos de Castilla y León
 */
define('ROOT_PATH', __DIR__ . '/');
require_once ROOT_PATH . 'config/config.php';
require_once ROOT_PATH . 'utils/gps_generator.php';
require_once ROOT_PATH . 'utils/auto_sync.php';

// Ejecutar sincronización automática si es necesario (en segundo plano)
try {
    $autoSync = new AutoSyncManager();
    if ($autoSync->needsSync()) {
        // Ejecutar en segundo plano para no bloquear la carga de la página
        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        }
        $autoSync->executeAutoSync();
    }
} catch (Exception $e) {
    // Error silencioso - no interrumpir la carga de la página
    error_log("Error en auto-sync: " . $e->getMessage());
}

// Generar coordenadas GPS automáticamente si es necesario (silencioso)
try {
    $verificacion = GPSGenerator::verificarApartamentosSinGPS();
    if ($verificacion['success'] && $verificacion['necesita_generacion']) {
        GPSGenerator::generarCoordenadasAutomaticamente();
    }
} catch (Exception $e) {
    // Error silencioso - no interrumpir la carga de la página
    error_log("Error generando GPS automáticamente en index: " . $e->getMessage());
}

$pageTitle = 'Inicio';
include ROOT_PATH . 'views/partials/header.php';
?>
<main>
    <!-- Hero Section -->
    <section class="hero">
        <div class="container hero-content">
            <span class="hero-badge">
                🏆 Datos abiertos de Castilla y León
            </span>
            <h1>Descubre el alojamiento perfecto en <span class="text-accent">Castilla y León</span></h1>
            <p class="hero-subtitle">
                Explora cientos de apartamentos turísticos en las 9 provincias castellanoleonesas. 
                Desde las murallas de Ávila hasta las riberas del Duero, tu próxima escapada te espera.
            </p>
            <div class="hero-actions">
                <a href="./views/apartamentos.php" class="btn btn-accent btn-lg">
                    Explorar apartamentos
                </a>
                <a href="./views/mapa.php" class="btn btn-secondary btn-lg" style="border-color: white; color: white;">
                    Ver en mapa
                </a>
            </div>
            <div class="hero-stats" id="hero-stats">
                <div class="hero-stat">
                    <div class="hero-stat-value" id="stat-total">---</div>
                    <div class="hero-stat-label">Apartamentos</div>
                </div>
                <div class="hero-stat">
                    <div class="hero-stat-value">9</div>
                    <div class="hero-stat-label">Provincias</div>
                </div>
                <div class="hero-stat">
                    <div class="hero-stat-value" id="stat-municipios">---</div>
                    <div class="hero-stat-label">Municipios</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Provincias Section -->
    <section class="py-4">
        <div class="container">
            <div class="text-center mb-3">
                <h2>Explora por provincia</h2>
                <p class="text-muted" style="max-width: 600px; margin: var(--space-md) auto 0;">
                    Cada rincón de Castilla y León tiene algo especial que ofrecer. 
                    Elige tu destino y encuentra el apartamento ideal.
                </p>
            </div>
            
            <div class="provincias-grid" id="provincias-grid">
                <!-- Se carga dinámicamente -->
                <div class="loading" style="grid-column: 1/-1;">
                    <div class="spinner"></div>
                </div>
            </div>
        </div>
    </section>

    <!-- Apartamentos por Provincia - Gráfica Circular -->
    <section class="py-4" style="background: var(--color-bg-alt);">
        <div class="container">
            <div class="text-center mb-3">
                <h2>Distribución de Apartamentos por Provincia</h2>
                <p class="text-muted">Visualización en tiempo real de los apartamentos turísticos en Castilla y León</p>
            </div>
            
            <div class="chart-container">
                <div class="chart-wrapper">
                    <canvas id="provinciaChart" width="400" height="400"></canvas>
                    <div id="chart-loading" class="chart-loading">
                        <div class="spinner"></div>
                        <p>Cargando datos...</p>
                    </div>
                </div>
                
                <div class="chart-stats" id="chart-stats">
                    <div class="chart-stat-item">
                        <div class="chart-stat-value" id="chart-total">---</div>
                        <div class="chart-stat-label">Total Apartamentos</div>
                    </div>
                    <div class="chart-stat-item">
                        <div class="chart-stat-value" id="chart-provincias">9</div>
                        <div class="chart-stat-label">Provincias</div>
                    </div>
                    <div class="chart-stat-item">
                        <div class="chart-stat-value" id="chart-updated">---</div>
                        <div class="chart-stat-label">Última Actualización</div>
                    </div>
                </div>
            </div>
            
            <div class="chart-legend" id="chart-legend">
                <!-- Se genera dinámicamente -->
            </div>
            
            <div class="text-center mt-2">
                <a href="./views/apartamentos.php" class="btn btn-primary btn-lg">
                    Ver todos los apartamentos
                </a>
                <a href="./views/mapa.php" class="btn btn-secondary btn-lg">
                    Ver en mapa interactivo
                </a>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="py-4" style="background: linear-gradient(135deg, var(--color-primary-dark), var(--color-primary)); color: white;">
        <div class="container text-center">
            <h2 style="color: white; margin-bottom: var(--space-md);">¿Listo para tu próxima escapada?</h2>
            <p style="max-width: 600px; margin: 0 auto var(--space-xl); opacity: 0.9;">
                Regístrate gratis y guarda tus apartamentos favoritos, recibe alertas de disponibilidad 
                y gestiona tus reservas fácilmente.
            </p>
            <button class="btn btn-accent btn-lg" data-modal-open="modal-registro">
                Crear cuenta gratis
            </button>
        </div>
    </section>
</main>

<?php include ROOT_PATH . 'views/partials/footer.php'; ?>

<!-- Chart.js para gráficas -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<!-- Cargar app.js ANTES que el script inline -->
<script src="./public/js/app.js"></script>
<script>
// Definir provinciaColors localmente para la gráfica de este archivo
// (Las demás definiciones como provinciaImages están en app.js)
const provinciaColors = {
    'Ávila': '#FF6384',
    'Burgos': '#36A2EB', 
    'León': '#FFCE56',
    'Palencia': '#4BC0C0',
    'Salamanca': '#9966FF',
    'Segovia': '#FF9F40',
    'Soria': '#FF6384',
    'Valladolid': '#C9CBCF',
    'Zamora': '#4BC0C0'
};
</script>



<!-- Estilos para la gráfica -->
<style>
.chart-container {
    display: grid;
    grid-template-columns: 1fr auto;
    gap: var(--space-xl);
    align-items: center;
    max-width: 900px;
    margin: 0 auto;
    padding: var(--space-xl);
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
}

.chart-wrapper {
    position: relative;
    width: 400px;
    height: 400px;
    margin: 0 auto;
}

.chart-loading {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    text-align: center;
    z-index: 10;
}

.chart-loading.hidden {
    display: none;
}

.chart-stats {
    display: flex;
    flex-direction: column;
    gap: var(--space-lg);
    min-width: 200px;
}

.chart-stat-item {
    text-align: center;
    padding: var(--space-md);
    background: var(--color-bg-alt);
    border-radius: 8px;
    border-left: 4px solid var(--color-primary);
}

.chart-stat-value {
    font-size: 2rem;
    font-weight: 700;
    color: var(--color-primary);
    margin-bottom: var(--space-xs);
}

.chart-stat-label {
    font-size: 0.875rem;
    color: var(--color-text-muted);
    font-weight: 500;
}

.chart-legend {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: var(--space-md);
    margin-top: var(--space-xl);
    max-width: 900px;
    margin-left: auto;
    margin-right: auto;
}

.legend-item {
    display: flex;
    align-items: center;
    gap: var(--space-sm);
    padding: var(--space-sm) var(--space-md);
    background: white;
    border-radius: 6px;
    border: 1px solid var(--color-border);
    transition: all 0.2s ease;
    cursor: pointer;
}

.legend-item:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.legend-color {
    width: 16px;
    height: 16px;
    border-radius: 50%;
    flex-shrink: 0;
}

.legend-image {
    width: 32px;
    height: 32px;
    object-fit: cover;
    border-radius: 4px;
    flex-shrink: 0;
    border: 1px solid var(--color-border);
}

.legend-text {
    flex: 1;
    font-size: 0.875rem;
    font-weight: 500;
}

.legend-count {
    font-size: 0.875rem;
    color: var(--color-text-muted);
    font-weight: 600;
}

.provincia-icon-img {
    width: 64px;
    height: 64px;
    object-fit: cover;
    border-radius: 8px;
    margin-bottom: var(--space-md);
    border: 2px solid var(--color-border);
    transition: all 0.2s ease;
}

.provincia-card:hover .provincia-icon-img {
    transform: scale(1.05);
    border-color: var(--color-primary);
}

.chart-update-indicator {
    position: absolute;
    top: 10px;
    right: 10px;
    background: var(--color-success);
    color: white;
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 500;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.chart-update-indicator.show {
    opacity: 1;
}

/* Responsive */
@media (max-width: 768px) {
    .chart-container {
        grid-template-columns: 1fr;
        gap: var(--space-lg);
        padding: var(--space-lg);
    }
    
    .chart-wrapper {
        width: 300px;
        height: 300px;
    }
    
    .chart-stats {
        flex-direction: row;
        justify-content: space-around;
        min-width: auto;
    }
    
    .chart-stat-item {
        flex: 1;
        margin: 0 var(--space-xs);
    }
    
    .chart-stat-value {
        font-size: 1.5rem;
    }
    
    .chart-legend {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
// Variables globales para la gráfica
let provinciaChart = null;
let chartData = null;
let lastUpdateTime = null;


// NOTA: Las siguientes definiciones ya están en app.js y no se duplican aquí:
// - provinciaColors (objeto con colores para cada provincia)
// - provinciaImages (objeto con rutas de imágenes de provincias)
// - getProvinciaImage(provincia) (función helper para obtener imagen)
// - createOptimizedImage(...) (función para crear elementos img optimizados)
// Esto evita el error: "Identifier has already been declared"


// Detectar la ruta base para las peticiones API desde index.php
const getApiBasePath = () => {
    const path = window.location.pathname;
    const parts = path.split('/').filter(p => p);
    
    console.log('🔍 Debug getApiBasePath desde index.php:');
    console.log('  - pathname:', path);
    console.log('  - parts:', parts);
    
    // Si estamos en /SoloPrueba/ o /SoloPrueba/index.php
    if (parts.length > 0 && parts[0] === 'SoloPrueba') {
        console.log('  - Detectado: /SoloPrueba/ -> ./api');
        return './api';
    }
    
    // Si estamos en la raíz del servidor
    console.log('  - Detectado: raíz -> ./api');
    return './api';
};

// Función simple para peticiones API (solo para index.php)
async function simpleApiRequest(endpoint) {
    const apiBase = getApiBasePath();
    const fullUrl = `${apiBase}/${endpoint}`;
    console.log('🌐 Petición desde index.php a:', fullUrl);
    
    try {
        const response = await fetch(fullUrl);
        console.log('📡 Respuesta recibida:', response.status, response.statusText);
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        
        const data = await response.json();
        console.log('📄 Datos recibidos:', data);
        return data;
    } catch (error) {
        console.error('❌ Error en petición:', error);
        throw error;
    }
}

// Cargar estadísticas
async function loadStats() {
    console.log('📊 Iniciando carga de estadísticas...');
    try {
        const response = await simpleApiRequest('apartamentos.php?action=estadisticas');
        console.log('📊 Respuesta estadísticas:', response);
        if (response.success && response.data) {
            document.getElementById('stat-total').textContent = response.data.total.toLocaleString('es-ES');
            document.getElementById('stat-municipios').textContent = response.data.por_provincia.length > 50 ? '200+' : '100+';
            console.log('✅ Estadísticas cargadas correctamente');
        } else {
            console.error('❌ Error en respuesta de estadísticas:', response);
            document.getElementById('stat-total').textContent = 'Error';
            document.getElementById('stat-municipios').textContent = 'Error';
        }
    } catch (e) {
        console.error('❌ Error cargando stats:', e);
        document.getElementById('stat-total').textContent = 'Error';
        document.getElementById('stat-municipios').textContent = 'Error';
    }
}

// Cargar provincias
async function loadProvincias() {
    console.log('🏛️ Iniciando carga de provincias...');
    const container = document.getElementById('provincias-grid');
    try {
        const response = await simpleApiRequest('apartamentos.php?action=provincias');
        console.log('🏛️ Respuesta provincias:', response);
        if (response.success && response.data) {
            container.innerHTML = response.data.map(p => {
                const imageSrc = getProvinciaImage(p.provincia);
                return `
                    <a href="./views/apartamentos.php?provincia=${encodeURIComponent(p.provincia)}" class="provincia-card">
                        <img src="${imageSrc}" 
                             alt="Monumento de ${p.provincia}" 
                             class="provincia-icon-img"
                             loading="lazy"
                             onerror="this.src='./public/images/default-placeholder.svg'">
                        <h3>${escapeHtml(p.provincia)}</h3>
                        <span class="provincia-count">${p.total} apartamentos</span>
                    </a>
                `;
            }).join('');
            console.log('✅ Provincias cargadas correctamente:', response.data.length);
        } else {
            console.error('❌ Error en respuesta de provincias:', response);
            container.innerHTML = '<p class="text-muted" style="grid-column:1/-1; text-align:center;">Error al cargar provincias</p>';
        }
    } catch (e) {
        console.error('❌ Error cargando provincias:', e);
        container.innerHTML = '<p class="text-muted" style="grid-column:1/-1; text-align:center;">Error al cargar provincias</p>';
    }
}

// Cargar y crear gráfica circular
async function loadChart() {
    console.log('📈 Iniciando carga de gráfica...');
    const loadingEl = document.getElementById('chart-loading');
    const canvas = document.getElementById('provinciaChart');
    
    try {
        // Mostrar loading
        loadingEl.classList.remove('hidden');
        
        const response = await simpleApiRequest('apartamentos.php?action=provincias');
        console.log('📈 Respuesta gráfica:', response);
        
        if (response.success && response.data) {
            chartData = response.data;
            console.log('📈 Datos para gráfica:', chartData);
            
            // Preparar datos para Chart.js
            const labels = chartData.map(p => p.provincia);
            const data = chartData.map(p => p.total);
            const colors = chartData.map(p => provinciaColors[p.provincia] || '#999999');
            
            console.log('📈 Labels:', labels);
            console.log('📈 Data:', data);
            
            // Destruir gráfica anterior si existe
            if (provinciaChart) {
                provinciaChart.destroy();
            }
            
            // Crear nueva gráfica
            const ctx = canvas.getContext('2d');
            provinciaChart = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: labels,
                    datasets: [{
                        data: data,
                        backgroundColor: colors,
                        borderColor: colors.map(color => color + '80'),
                        borderWidth: 2,
                        hoverBorderWidth: 3,
                        hoverOffset: 10
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        legend: {
                            display: false // Usaremos nuestra propia leyenda
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const provincia = context.label;
                                    const valor = context.parsed;
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const porcentaje = ((valor / total) * 100).toFixed(1);
                                    return `${provincia}: ${valor} apartamentos (${porcentaje}%)`;
                                }
                            },
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            titleColor: 'white',
                            bodyColor: 'white',
                            borderColor: 'rgba(255, 255, 255, 0.2)',
                            borderWidth: 1
                        }
                    },
                    animation: {
                        animateRotate: true,
                        animateScale: true,
                        duration: 1000
                    },
                    cutout: '50%'
                }
            });
            
            console.log('✅ Gráfica creada correctamente');
            
            // Actualizar estadísticas
            updateChartStats();
            
            // Crear leyenda personalizada
            createCustomLegend();
            
            // Ocultar loading
            loadingEl.classList.add('hidden');
            
            // Mostrar indicador de actualización
            showUpdateIndicator();
            
        } else {
            throw new Error('No se pudieron cargar los datos: ' + JSON.stringify(response));
        }
        
    } catch (error) {
        console.error('❌ Error cargando gráfica:', error);
        loadingEl.innerHTML = `
            <div style="color: var(--color-error);">
                <span style="font-size: 2rem;">⚠️</span>
                <p>Error al cargar la gráfica</p>
                <p style="font-size: 0.8em; color: #666;">${error.message}</p>
                <button onclick="loadChart()" class="btn btn-sm btn-secondary">Reintentar</button>
            </div>
        `;
    }
}

// Actualizar estadísticas de la gráfica
function updateChartStats() {
    if (!chartData) return;
    
    const total = chartData.reduce((sum, p) => sum + p.total, 0);
    const provincias = chartData.length;
    
    document.getElementById('chart-total').textContent = total.toLocaleString('es-ES');
    document.getElementById('chart-provincias').textContent = provincias;
    document.getElementById('chart-updated').textContent = new Date().toLocaleTimeString('es-ES', {
        hour: '2-digit',
        minute: '2-digit'
    });
    
    lastUpdateTime = new Date();
}

// Crear leyenda personalizada
function createCustomLegend() {
    if (!chartData) return;
    
    const legendContainer = document.getElementById('chart-legend');
    const total = chartData.reduce((sum, p) => sum + p.total, 0);
    
    legendContainer.innerHTML = chartData.map(provincia => {
        const porcentaje = ((provincia.total / total) * 100).toFixed(1);
        const color = provinciaColors[provincia.provincia] || '#999999';
        const imageSrc = getProvinciaImage(provincia.provincia);
        
        return `
            <div class="legend-item" onclick="goToProvincia('${provincia.provincia}')">
                <div class="legend-color" style="background-color: ${color}"></div>
                <img src="${imageSrc}" 
                     alt="Monumento de ${provincia.provincia}" 
                     class="legend-image"
                     loading="lazy"
                     onerror="this.src='./public/images/default-placeholder.svg'">
                <span class="legend-text">${provincia.provincia}</span>
                <span class="legend-count">${provincia.total} (${porcentaje}%)</span>
            </div>
        `;
    }).join('');
}

// Mostrar indicador de actualización
function showUpdateIndicator() {
    const indicator = document.createElement('div');
    indicator.className = 'chart-update-indicator';
    indicator.textContent = '✓ Actualizado';
    
    const chartWrapper = document.querySelector('.chart-wrapper');
    chartWrapper.appendChild(indicator);
    
    setTimeout(() => indicator.classList.add('show'), 100);
    setTimeout(() => {
        indicator.classList.remove('show');
        setTimeout(() => indicator.remove(), 300);
    }, 2000);
}

// Ir a página de provincia
function goToProvincia(provincia) {
    window.location.href = `./views/apartamentos.php?provincia=${encodeURIComponent(provincia)}`;
}

// Actualizar gráfica automáticamente cuando hay cambios
function updateChartOnSync() {
    // Esta función se llamará cuando el sistema de auto-sync detecte cambios
    console.log('🔄 Actualizando gráfica por cambios en la API...');
    loadChart();
}

// Verificar actualizaciones periódicamente
function startChartUpdateChecker() {
    setInterval(async () => {
        try {
            // Verificar si hay cambios comparando con timestamp
            const response = await simpleApiRequest('apartamentos.php?action=provincias');
            if (response.success && response.data) {
                // Comparar datos actuales con los cargados
                const currentData = JSON.stringify(response.data);
                const loadedData = JSON.stringify(chartData);
                
                if (currentData !== loadedData) {
                    console.log('📊 Detectados cambios en datos de provincias, actualizando gráfica...');
                    updateChartOnSync();
                }
            }
        } catch (error) {
            console.warn('Error verificando actualizaciones de gráfica:', error);
        }
    }, 30000); // Verificar cada 30 segundos
}

// Integración con el sistema de auto-sync
function setupAutoSyncIntegration() {
    // Escuchar eventos del sistema de auto-sync
    if (window.autoSyncManager) {
        // Sobrescribir el método de notificación para incluir actualización de gráfica
        const originalShowNotification = window.autoSyncManager.showNotification;
        window.autoSyncManager.showNotification = function(message) {
            originalShowNotification.call(this, message);
            
            // Si hay cambios significativos, actualizar gráfica
            if (message.includes('nuevos apartamentos') || message.includes('actualizados')) {
                setTimeout(updateChartOnSync, 1000);
            }
        };
    }
    
    // Escuchar eventos personalizados del auto-sync
    document.addEventListener('apartamentosUpdated', (event) => {
        console.log('📊 Evento apartamentosUpdated recibido:', event.detail);
        
        // Actualizar gráfica si hay cambios significativos
        if (event.detail && (event.detail.nuevos > 0 || event.detail.actualizados > 0)) {
            setTimeout(updateChartOnSync, 500);
        }
    });
    
    // También escuchar eventos de sincronización manual desde el admin
    document.addEventListener('syncCompleted', (event) => {
        console.log('📊 Evento syncCompleted recibido:', event.detail);
        setTimeout(updateChartOnSync, 500);
    });
}

// Inicializar todo
document.addEventListener('DOMContentLoaded', () => {
    console.log('🚀 Inicializando index.php...');
    
    // Cargar datos inmediatamente
    loadStats();
    loadProvincias();
    loadChart();
    
    // Configurar actualizaciones automáticas después de un delay
    setTimeout(() => {
        startChartUpdateChecker();
        setupAutoSyncIntegration();
    }, 2000);
});

// Actualizar gráfica cuando la página vuelve a estar visible
document.addEventListener('visibilitychange', () => {
    if (!document.hidden && lastUpdateTime) {
        const timeSinceUpdate = Date.now() - lastUpdateTime.getTime();
        // Si han pasado más de 5 minutos, actualizar
        if (timeSinceUpdate > 5 * 60 * 1000) {
            updateChartOnSync();
        }
    }
});

// Función global para forzar actualización (debugging)
window.updateChart = updateChartOnSync;

// Función helper local para escape HTML (por si app.js no está cargado)
function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
</script>