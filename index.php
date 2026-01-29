<?php
/**
 * Página principal - Apartamentos Turísticos de Castilla y León
 */
define('ROOT_PATH', __DIR__ . '/');
require_once ROOT_PATH . 'config/config.php';

$pageTitle = 'Inicio';
include ROOT_PATH . 'views/partials/header.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="/PruebaIntermodular/public/css/styles.css">
</head>
<body>
    
</body>
</html>
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
                <a href="views/apartamentos.php" class="btn btn-accent btn-lg">
                    Explorar apartamentos
                </a>
                <a href="views/mapa.php" class="btn btn-secondary btn-lg" style="border-color: white; color: white;">
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

    <!-- Apartamentos destacados -->
    <section class="py-4" style="background: var(--color-bg-alt);">
        <div class="container">
            <div class="text-center mb-3">
                <h2>Apartamentos destacados</h2>
                <p class="text-muted">Descubre algunas de nuestras opciones más populares</p>
            </div>
            
            <div class="results-grid" id="destacados-grid">
                <!-- Se carga dinámicamente -->
                <div class="loading" style="grid-column: 1/-1;">
                    <div class="spinner"></div>
                </div>
            </div>
            
            <div class="text-center mt-2">
                <a href="views/apartamentos.php" class="btn btn-primary btn-lg">
                    Ver todos los apartamentos
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

<script>
// Cargar estadísticas
async function loadStats() {
    try {
        const response = await apiRequest('apartamentos.php?action=estadisticas');
        if (response.success && response.data) {
            document.getElementById('stat-total').textContent = response.data.total.toLocaleString('es-ES');
            document.getElementById('stat-municipios').textContent = response.data.por_provincia.length > 50 ? '200+' : '100+';
        }
    } catch (e) {
        console.error('Error cargando stats:', e);
    }
}

// Cargar provincias
async function loadProvincias() {
    const container = document.getElementById('provincias-grid');
    try {
        const response = await apiRequest('apartamentos.php?action=provincias');
        if (response.success && response.data) {
            const iconos = {
                'Ávila': '🏔️',
                'Burgos': '🏰',
                'León': '👑',
                'Palencia': '⛪',
                'Salamanca': '🎓',
                'Segovia': '🏛️',
                'Soria': '🌲',
                'Valladolid': '🍷',
                'Zamora': '🌉'
            };
            
            container.innerHTML = response.data.map(p => `
                <a href="views/apartamentos.php?provincia=${encodeURIComponent(p.provincia)}" class="provincia-card">
                    <span class="provincia-icon">${iconos[p.provincia] || '🏠'}</span>
                    <h3>${escapeHtml(p.provincia)}</h3>
                    <span class="provincia-count">${p.total} apartamentos</span>
                </a>
            `).join('');
        }
    } catch (e) {
        container.innerHTML = '<p class="text-muted" style="grid-column:1/-1; text-align:center;">Error al cargar provincias</p>';
    }
}

// Cargar destacados
async function loadDestacados() {
    const container = document.getElementById('destacados-grid');
    try {
        const response = await apiRequest('apartamentos.php?action=destacados&limit=6');
        if (response.success && response.data) {
            container.innerHTML = response.data.map(apt => `
                <article class="card">
                    <div class="card-image">
                        <span class="card-image-placeholder">🏠</span>
                    </div>
                    <div class="card-body">
                        <h3 class="card-title">${escapeHtml(apt.nombre)}</h3>
                        <p class="card-subtitle">
                            📍 ${escapeHtml(apt.municipio || apt.provincia)}
                        </p>
                        <div class="card-meta">
                            <span class="card-meta-item">👥 ${apt.capacidad_alojamiento || '?'} plazas</span>
                            ${apt.accesible ? '<span class="badge badge-accent">♿ Accesible</span>' : ''}
                        </div>
                    </div>
                    <div style="padding: var(--space-md) var(--space-lg); border-top: 1px solid var(--color-border);">
                        <button class="btn btn-primary btn-sm" onclick="ApartamentosModule.showDetail(${apt.id})">
                            Ver detalles
                        </button>
                    </div>
                </article>
            `).join('');
        }
    } catch (e) {
        container.innerHTML = '<p class="text-muted" style="grid-column:1/-1; text-align:center;">Error al cargar apartamentos</p>';
    }
}

// Iniciar
document.addEventListener('DOMContentLoaded', () => {
    loadStats();
    loadProvincias();
    loadDestacados();
});
</script>