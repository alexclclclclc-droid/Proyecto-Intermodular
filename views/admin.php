<?php
/**
 * Panel de Administrador
 * Interfaz completa para gestión del sistema
 */
define('ROOT_PATH', dirname(__DIR__) . '/');
require_once ROOT_PATH . 'config/config.php';

// Verificar que el usuario sea administrador
if (!isLoggedIn() || !isAdmin()) {
    redirect('index.php');
    exit();
}

$pageTitle = 'Panel de Administrador';
include ROOT_PATH . 'views/partials/header.php';
?>

<div class="admin-panel">
    <!-- Sidebar de navegación -->
    <aside class="admin-sidebar">
        <div class="admin-sidebar-header">
            <h2>Panel Admin</h2>
            <span class="admin-user-info"><?= htmlspecialchars($_SESSION['usuario_nombre']) ?></span>
        </div>
        
        <nav class="admin-nav">
            <button type="button" class="admin-nav-link active" data-section="dashboard">
                <span class="admin-nav-icon">📊</span>
                Dashboard
            </button>
            <button type="button" class="admin-nav-link" data-section="usuarios">
                <span class="admin-nav-icon">👥</span>
                Usuarios
            </button>
            <button type="button" class="admin-nav-link" data-section="reservas">
                <span class="admin-nav-icon">📅</span>
                Reservas
            </button>
            <button type="button" class="admin-nav-link" data-section="sincronizacion">
                <span class="admin-nav-icon">🔄</span>
                Sincronización
            </button>
        </nav>
        
        <div class="admin-sidebar-footer">
            <a href="../index.php" class="btn btn-secondary btn-sm">
                ← Volver al sitio
            </a>
        </div>
    </aside>

    <!-- Contenido principal -->
    <main class="admin-main">
        <!-- Dashboard Section -->
        <section id="section-dashboard" class="admin-section active">
            <div class="admin-section-header">
                <h1>Dashboard</h1>
                <p class="text-muted">Visión general del sistema</p>
            </div>
            
            <!-- Estadísticas principales -->
            <div class="admin-stats-grid">
                <div class="admin-stat-card">
                    <div class="admin-stat-icon">👥</div>
                    <div class="admin-stat-content">
                        <div class="admin-stat-value" id="stat-usuarios-total">---</div>
                        <div class="admin-stat-label">Usuarios Totales</div>
                    </div>
                </div>
                
                <div class="admin-stat-card">
                    <div class="admin-stat-icon">📅</div>
                    <div class="admin-stat-content">
                        <div class="admin-stat-value" id="stat-reservas-total">---</div>
                        <div class="admin-stat-label">Reservas Totales</div>
                    </div>
                </div>
                
                <div class="admin-stat-card">
                    <div class="admin-stat-icon">🏠</div>
                    <div class="admin-stat-content">
                        <div class="admin-stat-value" id="stat-apartamentos-total">---</div>
                        <div class="admin-stat-label">Apartamentos</div>
                    </div>
                </div>
                
                <div class="admin-stat-card">
                    <div class="admin-stat-icon">📈</div>
                    <div class="admin-stat-content">
                        <div class="admin-stat-value" id="stat-ocupacion">---</div>
                        <div class="admin-stat-label">Tasa Ocupación</div>
                    </div>
                </div>
            </div>
            
            <!-- Gráficos y detalles -->
            <div class="admin-dashboard-grid">
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h3>Usuarios por Rol</h3>
                    </div>
                    <div class="admin-card-body">
                        <div id="chart-usuarios-rol" class="admin-chart">
                            <!-- Contenido dinámico -->
                        </div>
                    </div>
                </div>
                
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h3>Reservas por Estado</h3>
                    </div>
                    <div class="admin-card-body">
                        <div id="chart-reservas-estado" class="admin-chart">
                            <!-- Contenido dinámico -->
                        </div>
                    </div>
                </div>
                
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h3>Actividad Reciente</h3>
                    </div>
                    <div class="admin-card-body">
                        <div id="actividad-reciente">
                            <div class="admin-activity-item">
                                <span class="admin-activity-icon">👤</span>
                                <span class="admin-activity-text">Nuevo usuario registrado</span>
                                <span class="admin-activity-time">Hace 2 horas</span>
                            </div>
                            <div class="admin-activity-item">
                                <span class="admin-activity-icon">📅</span>
                                <span class="admin-activity-text">Reserva confirmada</span>
                                <span class="admin-activity-time">Hace 3 horas</span>
                            </div>
                            <div class="admin-activity-item">
                                <span class="admin-activity-icon">🔄</span>
                                <span class="admin-activity-text">Sincronización completada</span>
                                <span class="admin-activity-time">Hace 5 horas</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Usuarios Section -->
        <section id="section-usuarios" class="admin-section">
            <div class="admin-section-header">
                <h1>Gestión de Usuarios</h1>
                <p class="text-muted">Administrar usuarios del sistema</p>
            </div>
            
            <!-- Filtros -->
            <div class="admin-filters">
                <div class="admin-filter-group">
                    <label for="filtro-usuario-rol">Rol:</label>
                    <select id="filtro-usuario-rol" class="form-input">
                        <option value="">Todos los roles</option>
                        <option value="usuario">Usuario</option>
                        <option value="admin">Administrador</option>
                    </select>
                </div>
                
                <div class="admin-filter-group">
                    <label for="filtro-usuario-estado">Estado:</label>
                    <select id="filtro-usuario-estado" class="form-input">
                        <option value="">Todos los estados</option>
                        <option value="1">Activo</option>
                        <option value="0">Inactivo</option>
                    </select>
                </div>
                
                <div class="admin-filter-group">
                    <label for="filtro-usuario-email">Email:</label>
                    <input type="text" id="filtro-usuario-email" class="form-input" placeholder="Buscar por email...">
                </div>
                
                <button id="btn-limpiar-filtros-usuarios" class="btn btn-secondary">
                    Limpiar filtros
                </button>
            </div>
            
            <!-- Tabla de usuarios -->
            <div class="admin-table-container">
                <table class="admin-table" id="tabla-usuarios">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Email</th>
                            <th>Nombre</th>
                            <th>Rol</th>
                            <th>Estado</th>
                            <th>Fecha Registro</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Contenido dinámico -->
                    </tbody>
                </table>
            </div>
        </section>

        <!-- Reservas Section -->
        <section id="section-reservas" class="admin-section">
            <div class="admin-section-header">
                <h1>Gestión de Reservas</h1>
                <p class="text-muted">Administrar reservas del sistema</p>
            </div>
            
            <!-- Filtros -->
            <div class="admin-filters">
                <div class="admin-filter-group">
                    <label for="filtro-reserva-estado">Estado:</label>
                    <select id="filtro-reserva-estado" class="form-input">
                        <option value="">Todos los estados</option>
                        <option value="pendiente">Pendiente</option>
                        <option value="confirmada">Confirmada</option>
                        <option value="cancelada">Cancelada</option>
                        <option value="completada">Completada</option>
                    </select>
                </div>
                
                <div class="admin-filter-group">
                    <label for="filtro-reserva-fecha-desde">Desde:</label>
                    <input type="date" id="filtro-reserva-fecha-desde" class="form-input">
                </div>
                
                <div class="admin-filter-group">
                    <label for="filtro-reserva-fecha-hasta">Hasta:</label>
                    <input type="date" id="filtro-reserva-fecha-hasta" class="form-input">
                </div>
                
                <button id="btn-limpiar-filtros-reservas" class="btn btn-secondary">
                    Limpiar filtros
                </button>
            </div>
            
            <!-- Tabla de reservas -->
            <div class="admin-table-container">
                <table class="admin-table" id="tabla-reservas">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Usuario</th>
                            <th>Apartamento</th>
                            <th>Entrada</th>
                            <th>Salida</th>
                            <th>Estado</th>
                            <th>Fecha Creación</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Contenido dinámico -->
                    </tbody>
                </table>
            </div>
        </section>

        <!-- Sincronización Section -->
        <section id="section-sincronizacion" class="admin-section">
            <div class="admin-section-header">
                <h1>Herramientas de Sincronización</h1>
                <p class="text-muted">Gestionar sincronización con APIs externas</p>
            </div>
            
            <!-- Estado actual -->
            <div class="admin-card">
                <div class="admin-card-header">
                    <h3>Estado de Sincronización</h3>
                </div>
                <div class="admin-card-body">
                    <div id="sync-status" class="admin-sync-status">
                        <!-- Contenido dinámico -->
                    </div>
                </div>
            </div>
            
            <!-- Controles de sincronización -->
            <div class="admin-card">
                <div class="admin-card-header">
                    <h3>Ejecutar Sincronización</h3>
                </div>
                <div class="admin-card-body">
                    <div class="admin-sync-controls">
                        <button id="btn-ejecutar-sync" class="btn btn-primary">
                            🔄 Ejecutar Sincronización Manual
                        </button>
                        
                        <div id="sync-progress" class="admin-sync-progress" style="display: none;">
                            <div class="admin-progress-bar">
                                <div class="admin-progress-fill"></div>
                            </div>
                            <p class="admin-progress-text">Sincronizando datos...</p>
                        </div>
                        
                        <div id="sync-result" class="admin-sync-result" style="display: none;">
                            <!-- Resultado de sincronización -->
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Historial -->
            <div class="admin-card">
                <div class="admin-card-header">
                    <h3>Historial de Sincronizaciones</h3>
                </div>
                <div class="admin-card-body">
                    <div id="sync-history">
                        <!-- Historial dinámico -->
                    </div>
                </div>
            </div>
        </section>
    </main>
</div>

<!-- Modal de confirmación -->
<div id="modal-confirmacion" class="modal-overlay">
    <div class="modal">
        <div class="modal-header">
            <h3 class="modal-title">Confirmar Acción</h3>
            <button class="modal-close" data-modal-close>&times;</button>
        </div>
        <div class="modal-body">
            <p id="confirmacion-mensaje">¿Estás seguro de realizar esta acción?</p>
        </div>
        <div class="modal-footer">
            <button id="btn-confirmar-accion" class="btn btn-danger">Confirmar</button>
            <button class="btn btn-secondary" data-modal-close>Cancelar</button>
        </div>
    </div>
</div>

<?php include ROOT_PATH . 'views/partials/footer.php'; ?>

<!-- Estilos específicos del panel admin -->
<style>
:root {
    --color-primary: #2563eb;
    --color-accent: #f59e0b;
    --color-bg-alt: #f8fafc;
    --color-border: #e2e8f0;
    --color-text: #1e293b;
    --color-text-muted: #64748b;
    --space-xs: 0.25rem;
    --space-sm: 0.5rem;
    --space-md: 1rem;
    --space-lg: 1.5rem;
    --space-xl: 2rem;
}

.admin-panel {
    display: flex;
    min-height: 100vh;
    background: var(--color-bg-alt);
}

.admin-sidebar {
    width: 280px;
    background: white;
    border-right: 1px solid var(--color-border);
    display: flex;
    flex-direction: column;
}

.admin-sidebar-header {
    padding: var(--space-lg);
    border-bottom: 1px solid var(--color-border);
}

.admin-sidebar-header h2 {
    margin: 0 0 var(--space-xs) 0;
    color: var(--color-primary);
}

.admin-user-info {
    font-size: 0.875rem;
    color: var(--color-text-muted);
}

.admin-nav {
    flex: 1;
    padding: var(--space-md) 0;
}

.admin-nav-link {
    display: flex;
    align-items: center;
    gap: var(--space-md);
    padding: var(--space-md) var(--space-lg);
    color: var(--color-text);
    text-decoration: none;
    transition: all 0.2s ease;
    background: none;
    border: none;
    width: 100%;
    text-align: left;
    cursor: pointer;
    font-size: 1rem;
    font-family: inherit;
}

.admin-nav-link:hover {
    background: var(--color-bg-alt);
    color: var(--color-primary);
}

.admin-nav-link.active {
    background: var(--color-primary);
    color: white;
    border-right: 3px solid var(--color-accent);
}

.admin-nav-icon {
    font-size: 1.25rem;
}

.admin-sidebar-footer {
    padding: var(--space-lg);
    border-top: 1px solid var(--color-border);
}

.admin-main {
    flex: 1;
    padding: var(--space-xl);
    overflow-y: auto;
}

.admin-section {
    display: none;
}

.admin-section.active {
    display: block !important;
}

.admin-section-header {
    margin-bottom: var(--space-xl);
}

.admin-section-header h1 {
    margin: 0 0 var(--space-xs) 0;
}

.admin-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: var(--space-lg);
    margin-bottom: var(--space-xl);
}

.admin-stat-card {
    background: white;
    padding: var(--space-lg);
    border-radius: 8px;
    border: 1px solid var(--color-border);
    display: flex;
    align-items: center;
    gap: var(--space-md);
}

.admin-stat-icon {
    font-size: 2rem;
    width: 60px;
    height: 60px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--color-bg-alt);
    border-radius: 50%;
}

.admin-stat-value {
    font-size: 2rem;
    font-weight: 700;
    color: var(--color-primary);
}

.admin-stat-label {
    font-size: 0.875rem;
    color: var(--color-text-muted);
}

.admin-dashboard-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
    gap: var(--space-lg);
}

.admin-card {
    background: white;
    border-radius: 8px;
    border: 1px solid var(--color-border);
    overflow: hidden;
}

.admin-card-header {
    padding: var(--space-lg);
    border-bottom: 1px solid var(--color-border);
    background: var(--color-bg-alt);
}

.admin-card-header h3 {
    margin: 0;
}

.admin-card-body {
    padding: var(--space-lg);
}

.admin-chart {
    min-height: 200px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--color-text-muted);
}

.admin-activity-item {
    display: flex;
    align-items: center;
    gap: var(--space-md);
    padding: var(--space-md) 0;
    border-bottom: 1px solid var(--color-border);
}

.admin-activity-item:last-child {
    border-bottom: none;
}

.admin-activity-icon {
    font-size: 1.25rem;
}

.admin-activity-text {
    flex: 1;
}

.admin-activity-time {
    font-size: 0.875rem;
    color: var(--color-text-muted);
}

.admin-filters {
    display: flex;
    gap: var(--space-lg);
    margin-bottom: var(--space-lg);
    padding: var(--space-lg);
    background: white;
    border-radius: 8px;
    border: 1px solid var(--color-border);
    flex-wrap: wrap;
}

.admin-filter-group {
    display: flex;
    flex-direction: column;
    gap: var(--space-xs);
}

.admin-filter-group label {
    font-size: 0.875rem;
    font-weight: 500;
    color: var(--color-text);
}

.admin-table-container {
    background: white;
    border-radius: 8px;
    border: 1px solid var(--color-border);
    overflow: hidden;
}

.admin-table {
    width: 100%;
    border-collapse: collapse;
}

.admin-table th,
.admin-table td {
    padding: var(--space-md);
    text-align: left;
    border-bottom: 1px solid var(--color-border);
}

.admin-table th {
    background: var(--color-bg-alt);
    font-weight: 600;
    color: var(--color-text);
}

.admin-table tbody tr:hover {
    background: var(--color-bg-alt);
}

.admin-sync-status {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: var(--space-md);
}

.admin-sync-info {
    text-align: center;
    padding: var(--space-md);
    background: var(--color-bg-alt);
    border-radius: 6px;
}

.admin-sync-info-value {
    font-size: 1.5rem;
    font-weight: 600;
    color: var(--color-primary);
}

.admin-sync-info-label {
    font-size: 0.875rem;
    color: var(--color-text-muted);
}

.admin-sync-controls {
    text-align: center;
}

.admin-progress-bar {
    width: 100%;
    height: 8px;
    background: var(--color-border);
    border-radius: 4px;
    overflow: hidden;
    margin: var(--space-md) 0;
}

.admin-progress-fill {
    height: 100%;
    background: var(--color-primary);
    width: 0%;
    transition: width 0.3s ease;
    animation: progress-animation 2s infinite;
}

@keyframes progress-animation {
    0% { width: 0%; }
    50% { width: 70%; }
    100% { width: 100%; }
}

.admin-progress-text {
    color: var(--color-text-muted);
    margin: 0;
}

.admin-sync-result {
    margin-top: var(--space-lg);
    padding: var(--space-md);
    border-radius: 6px;
}

.admin-sync-result.success {
    background: #dcfce7;
    color: #166534;
    border: 1px solid #bbf7d0;
}

.admin-sync-result.error {
    background: #fef2f2;
    color: #991b1b;
    border: 1px solid #fecaca;
}

/* Estados y badges */
.badge {
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-size: 0.75rem;
    font-weight: 500;
}

.badge-success {
    background: #dcfce7;
    color: #166534;
}

.badge-warning {
    background: #fef3c7;
    color: #92400e;
}

.badge-danger {
    background: #fef2f2;
    color: #991b1b;
}

.badge-info {
    background: #dbeafe;
    color: #1e40af;
}

/* Toast notifications */
.toast {
    position: fixed;
    top: 20px;
    right: 20px;
    background: white;
    border-radius: 8px;
    padding: var(--space-md);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    border-left: 4px solid var(--color-primary);
    display: flex;
    align-items: center;
    gap: var(--space-sm);
    z-index: 1000;
    transform: translateX(100%);
    transition: transform 0.3s ease;
    max-width: 400px;
}

.toast.show {
    transform: translateX(0);
}

.toast-success {
    border-left-color: #10b981;
}

.toast-error {
    border-left-color: #ef4444;
}

.toast-warning {
    border-left-color: #f59e0b;
}

.toast-info {
    border-left-color: #3b82f6;
}

.toast-icon {
    font-size: 1.25rem;
}

.toast-message {
    flex: 1;
    font-size: 0.875rem;
}

/* Modal styles */
.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    display: none;
    align-items: center;
    justify-content: center;
    z-index: 1000;
}

.modal-overlay.active {
    display: flex;
}

.modal {
    background: white;
    border-radius: 8px;
    max-width: 500px;
    width: 90%;
    max-height: 90vh;
    overflow-y: auto;
}

.modal-header {
    padding: var(--space-lg);
    border-bottom: 1px solid var(--color-border);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-title {
    margin: 0;
}

.modal-close {
    background: none;
    border: none;
    font-size: 1.5rem;
    cursor: pointer;
    color: var(--color-text-muted);
}

.modal-body {
    padding: var(--space-lg);
}

.modal-footer {
    padding: var(--space-lg);
    border-top: 1px solid var(--color-border);
    display: flex;
    gap: var(--space-md);
    justify-content: flex-end;
}

/* Responsive */
@media (max-width: 768px) {
    .admin-panel {
        flex-direction: column;
    }
    
    .admin-sidebar {
        width: 100%;
        position: relative;
    }
    
    .admin-main {
        padding: var(--space-md);
    }
    
    .admin-filters {
        flex-direction: column;
        gap: var(--space-md);
    }
    
    .admin-stats-grid {
        grid-template-columns: 1fr;
    }
    
    .admin-dashboard-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<!-- JavaScript del panel admin -->
<script>
console.log('Cargando JavaScript del panel admin...');

// Función simple para cambiar secciones
function cambiarSeccion(seccion) {
    console.log('Cambiando a sección:', seccion);
    
    // Ocultar todas las secciones
    const secciones = document.querySelectorAll('.admin-section');
    secciones.forEach(sec => {
        sec.classList.remove('active');
        sec.style.display = 'none';
    });
    
    // Desactivar todos los botones
    const botones = document.querySelectorAll('.admin-nav-link');
    botones.forEach(btn => {
        btn.classList.remove('active');
    });
    
    // Mostrar la sección seleccionada
    const seccionTarget = document.getElementById('section-' + seccion);
    if (seccionTarget) {
        seccionTarget.classList.add('active');
        seccionTarget.style.display = 'block';
        console.log('Sección mostrada:', seccionTarget);
    } else {
        console.error('No se encontró la sección:', 'section-' + seccion);
    }
    
    // Activar el botón correspondiente
    const botonTarget = document.querySelector('[data-section="' + seccion + '"]');
    if (botonTarget) {
        botonTarget.classList.add('active');
        console.log('Botón activado:', botonTarget);
    } else {
        console.error('No se encontró el botón:', seccion);
    }
    
    // Cargar datos según la sección
    cargarDatosSeccion(seccion);
}

// Función para cargar datos de cada sección
function cargarDatosSeccion(seccion) {
    console.log('Cargando datos para:', seccion);
    
    switch(seccion) {
        case 'dashboard':
            cargarDashboard();
            break;
        case 'usuarios':
            cargarUsuarios();
            break;
        case 'reservas':
            cargarReservas();
            break;
        case 'sincronizacion':
            cargarSincronizacion();
            break;
    }
}

// Funciones para cargar datos de cada sección
function cargarDashboard() {
    console.log('Cargando dashboard...');
    
    // Detectar la ruta base automáticamente
    const pathParts = window.location.pathname.split('/');
    const projectFolder = pathParts[1]; // Proyecto-Intermodular
    const apiUrl = `/${projectFolder}/api/admin.php?action=estadisticas`;
    
    console.log('URL de API Dashboard:', apiUrl);
    
    // Intentar cargar datos reales de la API
    fetch(apiUrl)
        .then(response => {
            console.log('Respuesta Dashboard:', response.status);
            return response.json();
        })
        .then(data => {
            console.log('Datos Dashboard:', data);
            
            if (data.success) {
                // Actualizar estadísticas con datos reales
                const elementos = {
                    'stat-usuarios-total': data.data.usuarios.total,
                    'stat-reservas-total': data.data.reservas.total,
                    'stat-apartamentos-total': data.data.apartamentos.total,
                    'stat-ocupacion': data.data.apartamentos.tasa_ocupacion + '%'
                };
                
                Object.entries(elementos).forEach(([id, valor]) => {
                    const elemento = document.getElementById(id);
                    if (elemento) {
                        elemento.textContent = valor;
                    }
                });
                
                mostrarToast('Dashboard cargado con datos reales', 'success');
            } else {
                throw new Error(data.error || 'Error al cargar estadísticas');
            }
        })
        .catch(error => {
            console.error('Error cargando dashboard:', error);
            mostrarToast('Error al cargar dashboard real, usando datos de ejemplo', 'warning');
            // Fallback a datos de ejemplo
            cargarDashboardEjemplo();
        });
}

function cargarDashboardEjemplo() {
    // Datos de ejemplo como fallback
    const stats = {
        usuarios: 25,
        reservas: 48,
        apartamentos: 150,
        ocupacion: 30
    };
    
    const elementos = {
        'stat-usuarios-total': stats.usuarios,
        'stat-reservas-total': stats.reservas,
        'stat-apartamentos-total': stats.apartamentos,
        'stat-ocupacion': stats.ocupacion + '%'
    };
    
    Object.entries(elementos).forEach(([id, valor]) => {
        const elemento = document.getElementById(id);
        if (elemento) {
            elemento.textContent = valor;
        }
    });
    
    mostrarToast('Dashboard cargado con datos de ejemplo', 'info');
}

function cargarUsuarios() {
    console.log('Cargando usuarios...');
    
    // Detectar la ruta base automáticamente
    const pathParts = window.location.pathname.split('/');
    const projectFolder = pathParts[1]; // Proyecto-Intermodular
    const apiUrl = `/${projectFolder}/api/admin.php?action=usuarios_listar`;
    
    console.log('URL de API:', apiUrl);
    
    // Intentar cargar datos reales de la API
    fetch(apiUrl)
        .then(response => {
            console.log('Respuesta recibida:', response.status);
            return response.json();
        })
        .then(data => {
            console.log('Datos recibidos:', data);
            
            if (data.success) {
                // Renderizar usuarios reales
                const tbody = document.querySelector('#tabla-usuarios tbody');
                if (tbody) {
                    tbody.innerHTML = data.data.map(usuario => `
                        <tr>
                            <td>${usuario.id}</td>
                            <td>${usuario.email}</td>
                            <td>${usuario.nombre} ${usuario.apellidos || ''}</td>
                            <td>
                                <span class="badge ${usuario.rol === 'admin' ? 'badge-info' : 'badge-success'}">
                                    ${usuario.rol}
                                </span>
                            </td>
                            <td>
                                <span class="badge ${usuario.activo ? 'badge-success' : 'badge-danger'}">
                                    ${usuario.activo ? 'Activo' : 'Inactivo'}
                                </span>
                            </td>
                            <td>${usuario.fecha_registro ? usuario.fecha_registro.split(' ')[0] : 'N/A'}</td>
                            <td>
                                <button class="btn btn-sm btn-warning">Editar</button>
                                <button class="btn btn-sm btn-danger">Eliminar</button>
                            </td>
                        </tr>
                    `).join('');
                }
                
                mostrarToast(`Usuarios cargados: ${data.data.length} encontrados`, 'success');
            } else {
                throw new Error(data.error || 'Error al cargar usuarios');
            }
        })
        .catch(error => {
            console.error('Error cargando usuarios:', error);
            mostrarToast('Error al cargar usuarios reales, usando datos de ejemplo', 'warning');
            // Fallback a datos de ejemplo
            cargarUsuariosEjemplo();
        });
}

function cargarUsuariosEjemplo() {
    const usuarios = [
        {
            id: 1,
            email: 'admin@apartamentoscyl.es',
            nombre: 'Administrador',
            apellidos: 'Sistema',
            rol: 'admin',
            activo: true,
            fecha_registro: '2024-01-15'
        },
        {
            id: 2,
            email: 'juan@test.com',
            nombre: 'Juan',
            apellidos: 'Pérez García',
            rol: 'usuario',
            activo: true,
            fecha_registro: '2024-02-01'
        },
        {
            id: 3,
            email: 'maria@test.com',
            nombre: 'María',
            apellidos: 'López Martín',
            rol: 'usuario',
            activo: true,
            fecha_registro: '2024-02-05'
        }
    ];
    
    const tbody = document.querySelector('#tabla-usuarios tbody');
    if (tbody) {
        tbody.innerHTML = usuarios.map(usuario => `
            <tr>
                <td>${usuario.id}</td>
                <td>${usuario.email}</td>
                <td>${usuario.nombre} ${usuario.apellidos}</td>
                <td>
                    <span class="badge ${usuario.rol === 'admin' ? 'badge-info' : 'badge-success'}">
                        ${usuario.rol}
                    </span>
                </td>
                <td>
                    <span class="badge ${usuario.activo ? 'badge-success' : 'badge-danger'}">
                        ${usuario.activo ? 'Activo' : 'Inactivo'}
                    </span>
                </td>
                <td>${usuario.fecha_registro}</td>
                <td>
                    <button class="btn btn-sm btn-warning">Editar</button>
                    <button class="btn btn-sm btn-danger">Eliminar</button>
                </td>
            </tr>
        `).join('');
    }
    
    mostrarToast('Usuarios cargados con datos de ejemplo', 'info');
}

function cargarReservas() {
    console.log('Cargando reservas...');
    
    // Detectar la ruta base automáticamente
    const pathParts = window.location.pathname.split('/');
    const projectFolder = pathParts[1]; // Proyecto-Intermodular
    const apiUrl = `/${projectFolder}/api/admin.php?action=reservas_listar`;
    
    console.log('URL de API Reservas:', apiUrl);
    
    // Intentar cargar datos reales de la API
    fetch(apiUrl)
        .then(response => {
            console.log('Respuesta Reservas:', response.status);
            return response.json();
        })
        .then(data => {
            console.log('Datos Reservas:', data);
            
            if (data.success) {
                // Renderizar reservas reales
                const tbody = document.querySelector('#tabla-reservas tbody');
                if (tbody) {
                    tbody.innerHTML = data.data.map(reserva => `
                        <tr>
                            <td>${reserva.id}</td>
                            <td>${reserva.usuario_email || 'N/A'}</td>
                            <td>${reserva.apartamento_nombre || 'N/A'}</td>
                            <td>${reserva.fecha_entrada}</td>
                            <td>${reserva.fecha_salida}</td>
                            <td>
                                <span class="badge ${getBadgeClass(reserva.estado)}">
                                    ${reserva.estado}
                                </span>
                            </td>
                            <td>${reserva.fecha_creacion ? reserva.fecha_creacion.split(' ')[0] : 'N/A'}</td>
                            <td>
                                <button class="btn btn-sm btn-info">Ver Detalle</button>
                            </td>
                        </tr>
                    `).join('');
                }
                
                mostrarToast(`Reservas cargadas: ${data.data.length} encontradas`, 'success');
            } else {
                throw new Error(data.error || 'Error al cargar reservas');
            }
        })
        .catch(error => {
            console.error('Error cargando reservas:', error);
            mostrarToast('Error al cargar reservas reales, usando datos de ejemplo', 'warning');
            // Fallback a datos de ejemplo
            cargarReservasEjemplo();
        });
}

function getBadgeClass(estado) {
    const classes = {
        'pendiente': 'badge-warning',
        'confirmada': 'badge-success',
        'cancelada': 'badge-danger',
        'completada': 'badge-info'
    };
    return classes[estado] || 'badge-secondary';
}

function cargarReservasEjemplo() {
    const reservas = [
        {
            id: 1,
            usuario_email: 'juan@test.com',
            apartamento_nombre: 'Apartamento Centro Salamanca',
            fecha_entrada: '2024-03-15',
            fecha_salida: '2024-03-18',
            estado: 'confirmada',
            fecha_creacion: '2024-02-20'
        },
        {
            id: 2,
            usuario_email: 'maria@test.com',
            apartamento_nombre: 'Casa Rural Valladolid',
            fecha_entrada: '2024-04-01',
            fecha_salida: '2024-04-05',
            estado: 'pendiente',
            fecha_creacion: '2024-02-25'
        }
    ];
    
    const tbody = document.querySelector('#tabla-reservas tbody');
    if (tbody) {
        tbody.innerHTML = reservas.map(reserva => `
            <tr>
                <td>${reserva.id}</td>
                <td>${reserva.usuario_email}</td>
                <td>${reserva.apartamento_nombre}</td>
                <td>${reserva.fecha_entrada}</td>
                <td>${reserva.fecha_salida}</td>
                <td>
                    <span class="badge ${getBadgeClass(reserva.estado)}">
                        ${reserva.estado}
                    </span>
                </td>
                <td>${reserva.fecha_creacion}</td>
                <td>
                    <button class="btn btn-sm btn-info">Ver Detalle</button>
                </td>
            </tr>
        `).join('');
    }
    
    mostrarToast('Reservas cargadas con datos de ejemplo', 'info');
}

function cargarSincronizacion() {
    console.log('Cargando sincronización...');
    
    const container = document.getElementById('sync-status');
    if (container) {
        container.innerHTML = `
            <div class="admin-sync-info">
                <div class="admin-sync-info-value">28/02/2024</div>
                <div class="admin-sync-info-label">Última Sincronización</div>
            </div>
            <div class="admin-sync-info">
                <div class="admin-sync-info-value">1,247</div>
                <div class="admin-sync-info-label">Registros Procesados</div>
            </div>
            <div class="admin-sync-info">
                <div class="admin-sync-info-value">23</div>
                <div class="admin-sync-info-label">Nuevos</div>
            </div>
            <div class="admin-sync-info">
                <div class="admin-sync-info-value">156</div>
                <div class="admin-sync-info-label">Actualizados</div>
            </div>
        `;
    }
    
    mostrarToast('Sincronización cargada con datos de ejemplo', 'info');
}

// Función para mostrar notificaciones
function mostrarToast(mensaje, tipo = 'info') {
    const toast = document.createElement('div');
    toast.className = `toast toast-${tipo}`;
    toast.innerHTML = `
        <span class="toast-icon">${tipo === 'info' ? 'ℹ' : '✓'}</span>
        <span class="toast-message">${mensaje}</span>
    `;
    
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.classList.add('show');
    }, 10);
    
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM cargado, inicializando panel admin...');
    
    // Verificar que estamos en la página correcta
    if (!document.querySelector('.admin-panel')) {
        console.log('No se encontró panel admin');
        return;
    }
    
    console.log('Panel admin encontrado, configurando eventos...');
    
    // Configurar eventos de navegación
    const botones = document.querySelectorAll('.admin-nav-link');
    console.log('Botones encontrados:', botones.length);
    
    botones.forEach((boton, index) => {
        const seccion = boton.getAttribute('data-section');
        console.log(`Configurando botón ${index}: ${seccion}`);
        
        boton.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            console.log('Click en botón:', seccion);
            cambiarSeccion(seccion);
        });
    });
    
    // Cargar dashboard por defecto
    setTimeout(() => {
        cambiarSeccion('dashboard');
    }, 100);
    
    console.log('Panel admin inicializado correctamente');
});
</script>