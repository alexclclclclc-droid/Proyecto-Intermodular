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
                
                <div class="admin-filter-group">
                    <label for="filtro-reserva-usuario-email">Email Usuario:</label>
                    <input type="text" id="filtro-reserva-usuario-email" class="form-input" placeholder="Buscar por email...">
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
                <p class="text-muted">Gestionar sincronización con APIs externas y coordenadas GPS</p>
            </div>
            
            <!-- Estado actual -->
            <div class="admin-card">
                <div class="admin-card-header">
                    <h3>Estado de Sincronización</h3>
                    <button onclick="cargarEstadoSincronizacion()" class="btn btn-secondary btn-sm">
                        🔄 Actualizar
                    </button>
                </div>
                <div class="admin-card-body">
                    <div id="sync-status" class="admin-sync-status">
                        <!-- Contenido dinámico -->
                    </div>
                </div>
            </div>
            
            <!-- Estado de Sincronización Automática -->
            <div class="admin-card">
                <div class="admin-card-header">
                    <h3>Sincronización Automática</h3>
                    <button onclick="cargarEstadoAutoSync()" class="btn btn-secondary btn-sm">
                        🔄 Actualizar
                    </button>
                </div>
                <div class="admin-card-body">
                    <div id="auto-sync-status" class="admin-sync-status">
                        <!-- Contenido dinámico -->
                    </div>
                    <div style="margin-top: 15px; text-align: center;">
                        <button onclick="forzarSincronizacionAuto()" class="btn btn-warning btn-sm">
                            ⚡ Forzar Sincronización
                        </button>
                        <button onclick="verLogsAutoSync()" class="btn btn-info btn-sm">
                            📋 Ver Logs
                        </button>
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
                        <div class="sync-buttons-grid">
                            <button id="btn-ejecutar-sync" class="btn btn-primary">
                                🔄 Sincronización Completa
                            </button>
                            <button onclick="probarConexionAPI()" class="btn btn-secondary">
                                🔌 Probar Conexión API
                            </button>
                            <button onclick="generarGPSManual()" class="btn btn-info">
                                🗺️ Generar GPS
                            </button>
                        </div>
                        
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
                    <button onclick="cargarHistorialSincronizacion()" class="btn btn-secondary btn-sm">
                        🔄 Actualizar
                    </button>
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

<!-- Modal de confirmación/detalle -->
<div id="modal-confirmacion" class="modal-overlay">
    <div class="modal" style="max-width: 600px;">
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

<!-- Modal para editar usuario -->
<div id="modal-editar-usuario" class="modal-overlay">
    <div class="modal" style="max-width: 500px;">
        <div class="modal-header">
            <h3 class="modal-title">Editar Usuario</h3>
            <button class="modal-close" data-modal-close>&times;</button>
        </div>
        <div class="modal-body">
            <form id="form-editar-usuario">
                <input type="hidden" id="edit-usuario-id">
                
                <div class="form-group">
                    <label class="form-label" for="edit-usuario-nombre">Nombre:</label>
                    <input type="text" id="edit-usuario-nombre" class="form-input" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="edit-usuario-apellidos">Apellidos:</label>
                    <input type="text" id="edit-usuario-apellidos" class="form-input">
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="edit-usuario-email">Email:</label>
                    <input type="email" id="edit-usuario-email" class="form-input" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="edit-usuario-telefono">Teléfono:</label>
                    <input type="tel" id="edit-usuario-telefono" class="form-input">
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="edit-usuario-rol">Rol:</label>
                    <select id="edit-usuario-rol" class="form-select">
                        <option value="usuario">Usuario</option>
                        <option value="admin">Administrador</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">
                        <input type="checkbox" id="edit-usuario-activo"> Usuario activo
                    </label>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button id="btn-guardar-usuario" class="btn btn-primary">Guardar Cambios</button>
            <button class="btn btn-secondary" data-modal-close>Cancelar</button>
        </div>
    </div>
</div>

<?php include ROOT_PATH . 'views/partials/footer.php'; ?>

<!-- JavaScript principal (incluye apiRequest) -->
<script src="../public/js/app.js"></script>

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

.sync-result-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(100px, 1fr));
    gap: var(--space-md);
    margin: var(--space-md) 0;
}

.sync-stat {
    text-align: center;
    padding: var(--space-sm);
    background: rgba(255, 255, 255, 0.5);
    border-radius: var(--radius-md);
}

.sync-stat strong {
    display: block;
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--color-primary);
}

.sync-stat span {
    font-size: 0.875rem;
    color: var(--color-text-muted);
}

.admin-sync-warning {
    grid-column: 1 / -1;
    margin-top: var(--space-md);
}

.admin-empty-state {
    text-align: center;
    padding: var(--space-xl);
    color: var(--color-text-muted);
}

.admin-empty-icon {
    font-size: 3rem;
    margin-bottom: var(--space-md);
}

.admin-empty-state h3 {
    margin-bottom: var(--space-sm);
    color: var(--color-text);
}

.status-success {
    color: var(--color-success);
    font-weight: 500;
}

.status-error {
    color: var(--color-error);
    font-weight: 500;
}

.loading-spinner {
    display: inline-block;
    width: 20px;
    height: 20px;
    border: 2px solid #f3f3f3;
    border-top: 2px solid var(--color-primary);
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.sync-buttons-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: var(--space-md);
    margin-bottom: var(--space-lg);
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
    
    .admin-table-container {
        overflow-x: auto;
    }
    
    .admin-table {
        min-width: 800px;
    }
}

/* Mejoras adicionales */
.form-group {
    margin-bottom: var(--space-md);
}

.form-label {
    display: block;
    margin-bottom: var(--space-xs);
    font-weight: 500;
    color: var(--color-text);
}

.form-input, .form-select {
    width: 100%;
    padding: var(--space-sm);
    border: 1px solid var(--color-border);
    border-radius: 4px;
    font-size: 0.875rem;
    transition: border-color 0.2s ease;
}

.form-input:focus, .form-select:focus {
    outline: none;
    border-color: var(--color-primary);
    box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.1);
}

.btn {
    display: inline-flex;
    align-items: center;
    gap: var(--space-xs);
    padding: var(--space-sm) var(--space-md);
    border: none;
    border-radius: 4px;
    font-size: 0.875rem;
    font-weight: 500;
    text-decoration: none;
    cursor: pointer;
    transition: all 0.2s ease;
}

.btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.btn-primary {
    background: var(--color-primary);
    color: white;
}

.btn-primary:hover:not(:disabled) {
    background: #1d4ed8;
}

.btn-secondary {
    background: #6b7280;
    color: white;
}

.btn-secondary:hover:not(:disabled) {
    background: #4b5563;
}

.btn-success {
    background: #10b981;
    color: white;
}

.btn-success:hover:not(:disabled) {
    background: #059669;
}

.btn-warning {
    background: var(--color-accent);
    color: white;
}

.btn-warning:hover:not(:disabled) {
    background: #d97706;
}

.btn-danger {
    background: #ef4444;
    color: white;
}

.btn-danger:hover:not(:disabled) {
    background: #dc2626;
}

.btn-info {
    background: #3b82f6;
    color: white;
}

.btn-info:hover:not(:disabled) {
    background: #2563eb;
}

.btn-sm {
    padding: 0.25rem 0.5rem;
    font-size: 0.75rem;
}

.btn-ghost {
    background: transparent;
    color: var(--color-text);
    border: 1px solid var(--color-border);
}

.btn-ghost:hover:not(:disabled) {
    background: var(--color-bg-alt);
}

/* Loading states */
.loading-spinner {
    width: 16px;
    height: 16px;
    border: 2px solid transparent;
    border-top: 2px solid currentColor;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

/* Mejoras para tablas */
.admin-table tbody tr:hover {
    background: var(--color-bg-alt);
}

.admin-table select {
    border: 1px solid var(--color-border);
    border-radius: 4px;
    padding: 0.25rem 0.5rem;
    font-size: 0.75rem;
}

/* Estados de filtros */
.admin-filters.has-filters {
    border-left: 4px solid var(--color-primary);
}

.filter-indicator {
    display: inline-block;
    width: 8px;
    height: 8px;
    background: var(--color-primary);
    border-radius: 50%;
    margin-left: var(--space-xs);
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
    const projectFolder = pathParts[1];
    const apiUrl = `/${projectFolder}/api/admin.php?action=estadisticas`;
    
    console.log('URL de API Dashboard:', apiUrl);
    
    // Intentar cargar datos reales de la API
    fetch(apiUrl)
        .then(response => {
            console.log('Respuesta Dashboard:', response.status);
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
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
                        console.log(`Actualizado ${id}: ${valor}`);
                    }
                });
                
                // Actualizar gráficos con datos reales
                actualizarGraficos(data.data);
                
                mostrarToast('Dashboard cargado con datos reales', 'success');
            } else {
                throw new Error(data.error || 'Error al cargar estadísticas');
            }
        })
        .catch(error => {
            console.error('Error cargando dashboard:', error);
            mostrarToast('Error al cargar dashboard: ' + error.message, 'error');
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

function actualizarGraficos(data) {
    // Actualizar gráfico de usuarios por rol
    const chartUsuarios = document.getElementById('chart-usuarios-rol');
    if (chartUsuarios && data.usuarios.por_rol) {
        const roles = Object.entries(data.usuarios.por_rol);
        chartUsuarios.innerHTML = roles.map(([rol, cantidad]) => `
            <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                <span style="text-transform: capitalize;">${rol}:</span>
                <strong>${cantidad}</strong>
            </div>
        `).join('');
    }
    
    // Actualizar gráfico de reservas por estado
    const chartReservas = document.getElementById('chart-reservas-estado');
    if (chartReservas && data.reservas.por_estado) {
        const estados = Object.entries(data.reservas.por_estado);
        chartReservas.innerHTML = estados.map(([estado, cantidad]) => `
            <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                <span style="text-transform: capitalize;">${estado}:</span>
                <strong>${cantidad}</strong>
            </div>
        `).join('');
    }
    
    // Actualizar actividad reciente con datos reales
    const actividadContainer = document.getElementById('actividad-reciente');
    if (actividadContainer) {
        const actividades = [];
        
        if (data.usuarios.total > 0) {
            actividades.push({
                icon: '👤',
                texto: `${data.usuarios.total} usuarios registrados`,
                tiempo: 'Datos actuales'
            });
        }
        
        if (data.reservas.total > 0) {
            actividades.push({
                icon: '📅',
                texto: `${data.reservas.total} reservas en el sistema`,
                tiempo: 'Datos actuales'
            });
        }
        
        if (data.apartamentos && data.apartamentos.ocupados > 0) {
            actividades.push({
                icon: '🏠',
                texto: `${data.apartamentos.ocupados} apartamentos ocupados hoy`,
                tiempo: 'Hoy'
            });
        }
        
        if (data.apartamentos && data.apartamentos.disponibles > 0) {
            actividades.push({
                icon: '🏡',
                texto: `${data.apartamentos.disponibles} apartamentos disponibles`,
                tiempo: 'Hoy'
            });
        }
        
        if (data.reservas.este_mes > 0) {
            actividades.push({
                icon: '📈',
                texto: `${data.reservas.este_mes} reservas este mes`,
                tiempo: 'Mes actual'
            });
        }
        
        // Si no hay actividades, mostrar mensaje por defecto
        if (actividades.length === 0) {
            actividades.push({
                icon: '📊',
                texto: 'Sistema inicializado',
                tiempo: 'Hoy'
            });
        }
        
        // Mostrar solo las primeras 4 actividades para no sobrecargar
        const actividadesLimitadas = actividades.slice(0, 4);
        
        actividadContainer.innerHTML = actividadesLimitadas.map(actividad => `
            <div class="admin-activity-item">
                <span class="admin-activity-icon">${actividad.icon}</span>
                <span class="admin-activity-text">${actividad.texto}</span>
                <span class="admin-activity-time">${actividad.tiempo}</span>
            </div>
        `).join('');
    }
}

function cargarUsuarios() {
    console.log('Cargando usuarios...');
    
    // Detectar la ruta base automáticamente
    const pathParts = window.location.pathname.split('/');
    const projectFolder = pathParts[1];
    
    // Obtener filtros
    const filtros = {
        rol: document.getElementById('filtro-usuario-rol')?.value || '',
        estado: document.getElementById('filtro-usuario-estado')?.value || '',
        email: document.getElementById('filtro-usuario-email')?.value || ''
    };
    
    // Construir URL con filtros
    const params = new URLSearchParams({ action: 'usuarios_listar' });
    Object.entries(filtros).forEach(([key, value]) => {
        if (value) params.append(key, value);
    });
    
    const apiUrl = `/${projectFolder}/api/admin.php?${params.toString()}`;
    console.log('URL de API Usuarios:', apiUrl);
    
    // Mostrar indicador de carga
    const tbody = document.querySelector('#tabla-usuarios tbody');
    if (tbody) {
        tbody.innerHTML = '<tr><td colspan="7" style="text-align: center; padding: 20px;">Cargando usuarios...</td></tr>';
    }
    
    // Intentar cargar datos reales de la API
    fetch(apiUrl)
        .then(response => {
            console.log('Respuesta Usuarios:', response.status);
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('Datos Usuarios:', data);
            
            if (data.success) {
                // Renderizar usuarios reales
                if (tbody) {
                    if (data.data.length === 0) {
                        tbody.innerHTML = '<tr><td colspan="7" style="text-align: center; padding: 20px; color: #666;">No se encontraron usuarios con los filtros aplicados</td></tr>';
                    } else {
                        tbody.innerHTML = data.data.map(usuario => `
                            <tr>
                                <td>${usuario.id}</td>
                                <td>${usuario.email}</td>
                                <td>${usuario.nombre} ${usuario.apellidos || ''}</td>
                                <td>
                                    <select class="form-select" style="width: auto;" onchange="cambiarRolUsuario(${usuario.id}, this.value)">
                                        <option value="">Cambiar rol...</option>
                                        <option value="usuario" ${usuario.rol === 'usuario' ? 'selected' : ''}>Usuario</option>
                                        <option value="admin" ${usuario.rol === 'admin' ? 'selected' : ''}>Admin</option>
                                    </select>
                                </td>
                                <td>
                                    <button class="btn btn-sm ${usuario.activo ? 'btn-success' : 'btn-danger'}" 
                                            onclick="cambiarEstadoUsuario(${usuario.id}, ${!usuario.activo})" 
                                            title="Click para ${usuario.activo ? 'desactivar' : 'activar'} usuario">
                                        ${usuario.activo ? 'Activo' : 'Inactivo'}
                                    </button>
                                </td>
                                <td>${usuario.fecha_registro ? usuario.fecha_registro.split(' ')[0] : 'N/A'}</td>
                                <td>
                                    <button class="btn btn-sm btn-info" onclick="verDetalleUsuario(${usuario.id})">Ver</button>
                                    <button class="btn btn-sm btn-danger" onclick="eliminarUsuario(${usuario.id})" 
                                            ${usuario.id === getCurrentUserId() ? 'disabled title="No puedes eliminarte a ti mismo"' : ''}>
                                        Eliminar
                                    </button>
                                </td>
                            </tr>
                        `).join('');
                    }
                }
                
                const filtrosAplicados = data.filtros_aplicados || {};
                const numFiltros = Object.keys(filtrosAplicados).length;
                const mensaje = numFiltros > 0 
                    ? `Usuarios cargados: ${data.data.length} encontrados (${numFiltros} filtro(s) aplicado(s))`
                    : `Usuarios cargados: ${data.data.length} encontrados`;
                
                mostrarToast(mensaje, 'success');
            } else {
                throw new Error(data.error || 'Error al cargar usuarios');
            }
        })
        .catch(error => {
            console.error('Error cargando usuarios:', error);
            mostrarToast('Error al cargar usuarios: ' + error.message, 'error');
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
    const projectFolder = pathParts[1];
    
    // Obtener filtros
    const filtros = {
        estado: document.getElementById('filtro-reserva-estado')?.value || '',
        fecha_desde: document.getElementById('filtro-reserva-fecha-desde')?.value || '',
        fecha_hasta: document.getElementById('filtro-reserva-fecha-hasta')?.value || '',
        usuario_email: document.getElementById('filtro-reserva-usuario-email')?.value || ''
    };
    
    // Construir URL con filtros
    const params = new URLSearchParams({ action: 'reservas_listar' });
    Object.entries(filtros).forEach(([key, value]) => {
        if (value) params.append(key, value);
    });
    
    const apiUrl = `/${projectFolder}/api/admin.php?${params.toString()}`;
    console.log('URL de API Reservas:', apiUrl);
    
    // Mostrar indicador de carga
    const tbody = document.querySelector('#tabla-reservas tbody');
    if (tbody) {
        tbody.innerHTML = '<tr><td colspan="8" style="text-align: center; padding: 20px;">Cargando reservas...</td></tr>';
    }
    
    // Intentar cargar datos reales de la API
    fetch(apiUrl)
        .then(response => {
            console.log('Respuesta Reservas:', response.status);
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('Datos Reservas:', data);
            
            if (data.success) {
                // Renderizar reservas reales
                if (tbody) {
                    if (data.data.length === 0) {
                        tbody.innerHTML = '<tr><td colspan="8" style="text-align: center; padding: 20px; color: #666;">No se encontraron reservas con los filtros aplicados</td></tr>';
                    } else {
                        tbody.innerHTML = data.data.map(reserva => `
                            <tr>
                                <td>${reserva.id}</td>
                                <td>${reserva.usuario_email || 'N/A'}</td>
                                <td>${reserva.apartamento_nombre || 'N/A'}</td>
                                <td>${reserva.fecha_entrada}</td>
                                <td>${reserva.fecha_salida}</td>
                                <td>
                                    <select class="form-select" style="width: auto;" onchange="cambiarEstadoReserva(${reserva.id}, this.value)">
                                        <option value="">Cambiar estado...</option>
                                        <option value="pendiente" ${reserva.estado === 'pendiente' ? 'selected' : ''}>Pendiente</option>
                                        <option value="confirmada" ${reserva.estado === 'confirmada' ? 'selected' : ''}>Confirmada</option>
                                        <option value="cancelada" ${reserva.estado === 'cancelada' ? 'selected' : ''}>Cancelada</option>
                                        <option value="completada" ${reserva.estado === 'completada' ? 'selected' : ''}>Completada</option>
                                    </select>
                                </td>
                                <td>${reserva.fecha_creacion ? reserva.fecha_creacion.split(' ')[0] : 'N/A'}</td>
                                <td>
                                    <button class="btn btn-sm btn-info" onclick="verDetalleReserva(${reserva.id})">Ver</button>
                                    <button class="btn btn-sm btn-danger" onclick="eliminarReserva(${reserva.id})">Eliminar</button>
                                </td>
                            </tr>
                        `).join('');
                    }
                }
                
                const filtrosAplicados = data.filtros_aplicados || {};
                const numFiltros = Object.keys(filtrosAplicados).length;
                const mensaje = numFiltros > 0 
                    ? `Reservas cargadas: ${data.data.length} encontradas (${numFiltros} filtro(s) aplicado(s))`
                    : `Reservas cargadas: ${data.data.length} encontradas`;
                
                mostrarToast(mensaje, 'success');
            } else {
                throw new Error(data.error || 'Error al cargar reservas');
            }
        })
        .catch(error => {
            console.error('Error cargando reservas:', error);
            mostrarToast('Error al cargar reservas: ' + error.message, 'error');
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
    
    // Cargar estado actual
    cargarEstadoSincronizacion();
    
    // Cargar estado de auto-sync
    cargarEstadoAutoSync();
    
    // Cargar historial
    cargarHistorialSincronizacion();
    
    // Configurar eventos del botón de sincronización
    const btnEjecutarSync = document.getElementById('btn-ejecutar-sync');
    if (btnEjecutarSync) {
        btnEjecutarSync.addEventListener('click', ejecutarSincronizacion);
    }
}

async function cargarEstadoSincronizacion() {
    const container = document.getElementById('sync-status');
    if (!container) return;
    
    try {
        container.innerHTML = `
            <div class="admin-sync-info">
                <div class="admin-sync-info-value">
                    <div class="loading-spinner"></div>
                </div>
                <div class="admin-sync-info-label">Cargando...</div>
            </div>
        `;
        
        const response = await apiRequest('admin_sync.php?action=status');
        
        if (response.success) {
            const data = response.data;
            const ultimaSync = data.ultima_sincronizacion ? 
                new Date(data.ultima_sincronizacion).toLocaleString('es-ES') : 
                'Nunca';
            
            container.innerHTML = `
                <div class="admin-sync-info">
                    <div class="admin-sync-info-value">${ultimaSync}</div>
                    <div class="admin-sync-info-label">Última Sincronización</div>
                </div>
                <div class="admin-sync-info">
                    <div class="admin-sync-info-value">${data.total_apartamentos}</div>
                    <div class="admin-sync-info-label">Total Apartamentos</div>
                </div>
                <div class="admin-sync-info">
                    <div class="admin-sync-info-value">${data.apartamentos_sincronizados}</div>
                    <div class="admin-sync-info-label">Sincronizados</div>
                </div>
                <div class="admin-sync-info">
                    <div class="admin-sync-info-value">${data.apartamentos_con_gps}</div>
                    <div class="admin-sync-info-label">Con GPS</div>
                </div>
                <div class="admin-sync-info">
                    <div class="admin-sync-info-value">${data.porcentaje_sincronizado}%</div>
                    <div class="admin-sync-info-label">% Sincronizado</div>
                </div>
                <div class="admin-sync-info">
                    <div class="admin-sync-info-value">${data.porcentaje_gps}%</div>
                    <div class="admin-sync-info-label">% Con GPS</div>
                </div>
            `;
            
            // Mostrar advertencias si es necesario
            if (data.apartamentos_sin_gps > 0) {
                const warning = document.createElement('div');
                warning.className = 'admin-sync-warning';
                warning.innerHTML = `
                    <div class="alert alert-warning">
                        ⚠️ <strong>${data.apartamentos_sin_gps} apartamentos sin coordenadas GPS</strong>
                        <button onclick="generarGPSManual()" class="btn btn-sm btn-warning" style="margin-left: 10px;">
                            🗺️ Generar GPS
                        </button>
                    </div>
                `;
                container.appendChild(warning);
            }
            
        } else {
            throw new Error(response.error || 'Error cargando estado');
        }
        
    } catch (error) {
        console.error('Error cargando estado de sincronización:', error);
        container.innerHTML = `
            <div class="admin-sync-info">
                <div class="admin-sync-info-value">❌</div>
                <div class="admin-sync-info-label">Error cargando estado</div>
            </div>
        `;
        mostrarToast('Error cargando estado de sincronización', 'error');
    }
}

async function cargarEstadoAutoSync() {
    const container = document.getElementById('auto-sync-status');
    if (!container) return;
    
    try {
        container.innerHTML = `
            <div class="admin-sync-info">
                <div class="admin-sync-info-value">
                    <div class="loading-spinner"></div>
                </div>
                <div class="admin-sync-info-label">Cargando...</div>
            </div>
        `;
        
        const response = await apiRequest('auto_sync_endpoint.php?action=status');
        
        if (response.success) {
            const data = response.data;
            
            container.innerHTML = `
                <div class="admin-sync-info">
                    <div class="admin-sync-info-value">${data.enabled ? '✅' : '❌'}</div>
                    <div class="admin-sync-info-label">Estado</div>
                </div>
                <div class="admin-sync-info">
                    <div class="admin-sync-info-value">${data.last_sync}</div>
                    <div class="admin-sync-info-label">Última Ejecución</div>
                </div>
                <div class="admin-sync-info">
                    <div class="admin-sync-info-value">${data.next_sync}</div>
                    <div class="admin-sync-info-label">Próxima Ejecución</div>
                </div>
                <div class="admin-sync-info">
                    <div class="admin-sync-info-value">${data.interval_hours}h</div>
                    <div class="admin-sync-info-label">Intervalo</div>
                </div>
                <div class="admin-sync-info">
                    <div class="admin-sync-info-value">${data.needs_sync ? '⏰' : '✅'}</div>
                    <div class="admin-sync-info-label">${data.needs_sync ? 'Pendiente' : 'Actualizado'}</div>
                </div>
                <div class="admin-sync-info">
                    <div class="admin-sync-info-value">${data.is_locked ? '🔒' : '🔓'}</div>
                    <div class="admin-sync-info-label">${data.is_locked ? 'Ejecutando' : 'Disponible'}</div>
                </div>
            `;
            
        } else {
            throw new Error(response.error || 'Error cargando estado auto-sync');
        }
        
    } catch (error) {
        console.error('Error cargando estado de auto-sync:', error);
        container.innerHTML = `
            <div class="admin-sync-info">
                <div class="admin-sync-info-value">❌</div>
                <div class="admin-sync-info-label">Error cargando estado</div>
            </div>
        `;
        mostrarToast('Error cargando estado de sincronización automática', 'error');
    }
}

async function forzarSincronizacionAuto() {
    try {
        mostrarToast('Forzando sincronización automática...', 'info');
        
        const response = await apiRequest('auto_sync_endpoint.php?action=force', {
            method: 'POST'
        });
        
        if (response.success) {
            const result = response.data;
            
            if (result.skipped) {
                mostrarToast('Sincronización no necesaria - datos actualizados', 'info');
            } else if (result.success) {
                const syncResult = result.sync_result;
                mostrarToast(`Sincronización forzada completada: ${syncResult.nuevos} nuevos, ${syncResult.actualizados} actualizados`, 'success');
            } else {
                mostrarToast('Sincronización completada con errores', 'warning');
            }
            
            // Recargar estados
            setTimeout(() => {
                cargarEstadoSincronizacion();
                cargarEstadoAutoSync();
            }, 1000);
            
        } else {
            throw new Error(response.error || 'Error forzando sincronización');
        }
        
    } catch (error) {
        console.error('Error forzando sincronización:', error);
        mostrarToast('Error forzando sincronización: ' + error.message, 'error');
    }
}

async function verLogsAutoSync() {
    try {
        const response = await apiRequest('auto_sync_endpoint.php?action=logs');
        
        if (response.success) {
            const logs = response.data;
            
            let logHtml = '<h4>📋 Logs de Sincronización Automática</h4>';
            
            if (logs.length === 0) {
                logHtml += '<p>No hay logs disponibles.</p>';
            } else {
                logHtml += '<div style="max-height: 400px; overflow-y: auto; font-family: monospace; font-size: 12px;">';
                
                logs.reverse().forEach(log => {
                    const timestamp = new Date(log.timestamp).toLocaleString('es-ES');
                    const result = log.result;
                    const status = result.success ? '✅' : '❌';
                    
                    logHtml += `
                        <div style="border-bottom: 1px solid #eee; padding: 10px; margin-bottom: 5px;">
                            <strong>${status} ${timestamp}</strong><br>
                            Procesados: ${result.procesados} | Nuevos: ${result.nuevos} | Actualizados: ${result.actualizados} | Errores: ${result.errores}
                        </div>
                    `;
                });
                
                logHtml += '</div>';
            }
            
            // Mostrar en modal
            const modal = document.getElementById('modal-confirmacion');
            if (modal) {
                const titulo = modal.querySelector('.modal-title');
                const mensaje = modal.querySelector('#confirmacion-mensaje');
                const btnConfirmar = modal.querySelector('#btn-confirmar-accion');
                
                titulo.textContent = 'Logs de Sincronización Automática';
                mensaje.innerHTML = logHtml;
                btnConfirmar.textContent = 'Cerrar';
                btnConfirmar.className = 'btn btn-secondary';
                btnConfirmar.onclick = () => modal.classList.remove('active');
                
                modal.classList.add('active');
            }
            
        } else {
            throw new Error(response.error || 'Error obteniendo logs');
        }
        
    } catch (error) {
        console.error('Error obteniendo logs:', error);
        mostrarToast('Error obteniendo logs: ' + error.message, 'error');
    }
}

async function cargarHistorialSincronizacion() {
    const container = document.getElementById('sync-history');
    if (!container) return;
    
    try {
        container.innerHTML = '<div class="loading-spinner"></div> Cargando historial...';
        
        const response = await apiRequest('admin_sync.php?action=history');
        
        if (response.success && response.data.length > 0) {
            container.innerHTML = `
                <div class="admin-table-container">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Procesados</th>
                                <th>Nuevos</th>
                                <th>Actualizados</th>
                                <th>GPS Generados</th>
                                <th>Errores</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${response.data.map(item => `
                                <tr>
                                    <td>${new Date(item.fecha).toLocaleString('es-ES')}</td>
                                    <td>${item.procesados}</td>
                                    <td><span class="badge badge-success">${item.nuevos}</span></td>
                                    <td><span class="badge badge-info">${item.actualizados}</span></td>
                                    <td><span class="badge badge-primary">${item.gps_generados || 0}</span></td>
                                    <td>${item.errores > 0 ? `<span class="badge badge-error">${item.errores}</span>` : '0'}</td>
                                    <td>
                                        ${item.errores === 0 ? 
                                            '<span class="status-success">✅ Exitosa</span>' : 
                                            '<span class="status-error">❌ Con errores</span>'
                                        }
                                    </td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                </div>
            `;
        } else {
            container.innerHTML = `
                <div class="admin-empty-state">
                    <div class="admin-empty-icon">📋</div>
                    <h3>Sin historial de sincronizaciones</h3>
                    <p>No se han ejecutado sincronizaciones anteriormente</p>
                </div>
            `;
        }
        
    } catch (error) {
        console.error('Error cargando historial:', error);
        container.innerHTML = `
            <div class="alert alert-error">
                ❌ Error cargando historial de sincronizaciones
            </div>
        `;
    }
}

async function ejecutarSincronizacion() {
    const btnEjecutar = document.getElementById('btn-ejecutar-sync');
    const progressContainer = document.getElementById('sync-progress');
    const resultContainer = document.getElementById('sync-result');
    
    if (!btnEjecutar || !progressContainer || !resultContainer) return;
    
    try {
        // Mostrar confirmación
        if (!confirm('¿Estás seguro de ejecutar la sincronización? Este proceso puede tardar varios minutos.')) {
            return;
        }
        
        // Mostrar estado de carga
        btnEjecutar.disabled = true;
        btnEjecutar.innerHTML = '⏳ Sincronizando...';
        progressContainer.style.display = 'block';
        resultContainer.style.display = 'none';
        
        // Animar barra de progreso
        const progressFill = progressContainer.querySelector('.admin-progress-fill');
        if (progressFill) {
            progressFill.style.width = '0%';
            progressFill.style.transition = 'width 30s linear';
            setTimeout(() => {
                progressFill.style.width = '90%';
            }, 100);
        }
        
        // Ejecutar sincronización
        let response;
        try {
            response = await apiRequest('admin_sync.php?action=execute', {
                method: 'POST'
            });
        } catch (apiError) {
            // Manejar errores específicos de la API
            if (apiError.message && apiError.message.includes('Assignment to constant')) {
                throw new Error('No hay datos nuevos para sincronizar. Todos los apartamentos están actualizados.');
            }
            throw apiError;
        }
        
        // Completar barra de progreso
        if (progressFill) {
            progressFill.style.width = '100%';
        }
        
        setTimeout(() => {
            progressContainer.style.display = 'none';
            
            if (response.success) {
                const syncResult = response.data.sync_result;
                const gpsResult = response.data.gps_result;
                
                // Verificar si no hay nada que sincronizar
                if (syncResult.procesados === 0 && syncResult.errores === 0) {
                    resultContainer.className = 'admin-sync-result success';
                    resultContainer.innerHTML = `
                        <h4>ℹ️ Sincronización Completada</h4>
                        <p>No hay datos nuevos para sincronizar. Todos los apartamentos están actualizados.</p>
                        <div class="sync-result-stats">
                            <div class="sync-stat">
                                <strong>0</strong>
                                <span>Nuevos</span>
                            </div>
                            <div class="sync-stat">
                                <strong>0</strong>
                                <span>Actualizados</span>
                            </div>
                            <div class="sync-stat">
                                <strong>0</strong>
                                <span>Errores</span>
                            </div>
                        </div>
                    `;
                    
                    mostrarToast('No hay datos nuevos para sincronizar', 'info');
                } else {
                    // Mostrar resultados normales
                    resultContainer.className = 'admin-sync-result success';
                    resultContainer.innerHTML = `
                        <h4>✅ Sincronización Completada</h4>
                        <div class="sync-result-stats">
                            <div class="sync-stat">
                                <strong>${syncResult.procesados}</strong>
                                <span>Procesados</span>
                            </div>
                            <div class="sync-stat">
                                <strong>${syncResult.nuevos}</strong>
                                <span>Nuevos</span>
                            </div>
                            <div class="sync-stat">
                                <strong>${syncResult.actualizados}</strong>
                                <span>Actualizados</span>
                            </div>
                            ${gpsResult && gpsResult.success ? `
                            <div class="sync-stat">
                                <strong>${gpsResult.actualizados || 0}</strong>
                                <span>GPS Generados</span>
                            </div>
                            ` : ''}
                            <div class="sync-stat">
                                <strong>${syncResult.errores}</strong>
                                <span>Errores</span>
                            </div>
                        </div>
                        ${syncResult.errores > 0 ? `
                        <div class="alert alert-warning" style="margin-top: 15px;">
                            ⚠️ Se encontraron ${syncResult.errores} errores durante la sincronización
                        </div>
                        ` : ''}
                    `;
                    
                    mostrarToast('Sincronización completada exitosamente', 'success');
                }
                
                // Recargar estado y historial
                setTimeout(() => {
                    cargarEstadoSincronizacion();
                    cargarHistorialSincronizacion();
                }, 1000);
                
            } else {
                throw new Error(response.error || 'Error en la sincronización');
            }
            
            resultContainer.style.display = 'block';
            
        }, 1000);
        
    } catch (error) {
        console.error('Error ejecutando sincronización:', error);
        
        progressContainer.style.display = 'none';
        
        // Manejar diferentes tipos de errores
        let errorMessage = error.message;
        let isNoDataError = false;
        
        if (errorMessage.includes('No hay datos nuevos') || 
            errorMessage.includes('Assignment to constant') ||
            errorMessage.includes('todos los apartamentos están actualizados')) {
            errorMessage = 'No hay datos nuevos para sincronizar. Todos los apartamentos están actualizados.';
            isNoDataError = true;
        }
        
        resultContainer.className = isNoDataError ? 'admin-sync-result success' : 'admin-sync-result error';
        resultContainer.innerHTML = `
            <h4>${isNoDataError ? 'ℹ️ Sin Cambios' : '❌ Error en la Sincronización'}</h4>
            <p>${errorMessage}</p>
            ${!isNoDataError ? `
            <button onclick="ejecutarSincronizacion()" class="btn btn-primary btn-sm">
                🔄 Reintentar
            </button>
            ` : ''}
        `;
        resultContainer.style.display = 'block';
        
        mostrarToast(isNoDataError ? 'No hay datos nuevos para sincronizar' : 'Error ejecutando sincronización: ' + errorMessage, isNoDataError ? 'info' : 'error');
        
    } finally {
        // Restaurar botón
        btnEjecutar.disabled = false;
        btnEjecutar.innerHTML = '🔄 Sincronización Completa';
    }
}

async function generarGPSManual() {
    try {
        const response = await apiRequest('admin_sync.php?action=generate_gps', {
            method: 'POST'
        });
        
        if (response.success) {
            mostrarToast(`GPS generado para ${response.data.actualizados} apartamentos`, 'success');
            cargarEstadoSincronizacion(); // Recargar estado
        } else {
            throw new Error(response.data.error || 'Error generando GPS');
        }
        
    } catch (error) {
        console.error('Error generando GPS:', error);
        mostrarToast('Error generando coordenadas GPS: ' + error.message, 'error');
    }
}

async function probarConexionAPI() {
    try {
        mostrarToast('Probando conexión con la API externa...', 'info');
        
        const response = await apiRequest('admin_sync.php?action=test_connection');
        
        if (response.success) {
            if (response.data.connection_ok) {
                mostrarToast('✅ Conexión con la API externa exitosa', 'success');
            } else {
                mostrarToast('❌ No se pudo conectar con la API externa', 'error');
            }
        } else {
            throw new Error(response.error || 'Error probando conexión');
        }
        
    } catch (error) {
        console.error('Error probando conexión:', error);
        mostrarToast('Error probando conexión: ' + error.message, 'error');
    }
}

// Función para mostrar notificaciones
function mostrarToast(mensaje, tipo = 'info') {
    console.log(`Mostrando toast [${tipo}]: ${mensaje}`);
    
    const toast = document.createElement('div');
    toast.className = `toast toast-${tipo}`;
    
    const iconMap = {
        'info': 'ℹ️',
        'success': '✅',
        'error': '❌',
        'warning': '⚠️'
    };
    
    toast.innerHTML = `
        <span class="toast-icon">${iconMap[tipo] || 'ℹ️'}</span>
        <span class="toast-message">${mensaje}</span>
    `;
    
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.classList.add('show');
    }, 10);
    
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => {
            if (toast.parentNode) {
                toast.remove();
            }
        }, 300);
    }, 4000);
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
    
    // Configurar eventos de filtros para usuarios
    const filtroUsuarioRol = document.getElementById('filtro-usuario-rol');
    const filtroUsuarioEstado = document.getElementById('filtro-usuario-estado');
    const filtroUsuarioEmail = document.getElementById('filtro-usuario-email');
    const btnLimpiarUsuarios = document.getElementById('btn-limpiar-filtros-usuarios');
    
    if (filtroUsuarioRol) {
        filtroUsuarioRol.addEventListener('change', () => {
            console.log('Filtro rol cambiado:', filtroUsuarioRol.value);
            cargarUsuarios();
        });
    }
    
    if (filtroUsuarioEstado) {
        filtroUsuarioEstado.addEventListener('change', () => {
            console.log('Filtro estado cambiado:', filtroUsuarioEstado.value);
            cargarUsuarios();
        });
    }
    
    if (filtroUsuarioEmail) {
        // Debounce para el filtro de email
        let timeoutEmail;
        filtroUsuarioEmail.addEventListener('input', () => {
            clearTimeout(timeoutEmail);
            timeoutEmail = setTimeout(() => {
                console.log('Filtro email cambiado:', filtroUsuarioEmail.value);
                cargarUsuarios();
            }, 500);
        });
    }
    
    if (btnLimpiarUsuarios) {
        btnLimpiarUsuarios.addEventListener('click', () => {
            if (filtroUsuarioRol) filtroUsuarioRol.value = '';
            if (filtroUsuarioEstado) filtroUsuarioEstado.value = '';
            if (filtroUsuarioEmail) filtroUsuarioEmail.value = '';
            cargarUsuarios();
        });
    }
    
    // Configurar eventos de filtros para reservas
    const filtroReservaEstado = document.getElementById('filtro-reserva-estado');
    const filtroReservaFechaDesde = document.getElementById('filtro-reserva-fecha-desde');
    const filtroReservaFechaHasta = document.getElementById('filtro-reserva-fecha-hasta');
    const filtroReservaUsuarioEmail = document.getElementById('filtro-reserva-usuario-email');
    const btnLimpiarReservas = document.getElementById('btn-limpiar-filtros-reservas');
    
    if (filtroReservaEstado) {
        filtroReservaEstado.addEventListener('change', () => {
            console.log('Filtro estado reserva cambiado:', filtroReservaEstado.value);
            cargarReservas();
        });
    }
    
    if (filtroReservaFechaDesde) {
        filtroReservaFechaDesde.addEventListener('change', () => {
            console.log('Filtro fecha desde cambiado:', filtroReservaFechaDesde.value);
            cargarReservas();
        });
    }
    
    if (filtroReservaFechaHasta) {
        filtroReservaFechaHasta.addEventListener('change', () => {
            console.log('Filtro fecha hasta cambiado:', filtroReservaFechaHasta.value);
            cargarReservas();
        });
    }
    
    if (filtroReservaUsuarioEmail) {
        // Debounce para el filtro de email de usuario
        let timeoutUsuarioEmail;
        filtroReservaUsuarioEmail.addEventListener('input', () => {
            clearTimeout(timeoutUsuarioEmail);
            timeoutUsuarioEmail = setTimeout(() => {
                console.log('Filtro email usuario cambiado:', filtroReservaUsuarioEmail.value);
                cargarReservas();
            }, 500);
        });
    }
    
    if (btnLimpiarReservas) {
        btnLimpiarReservas.addEventListener('click', () => {
            if (filtroReservaEstado) filtroReservaEstado.value = '';
            if (filtroReservaFechaDesde) filtroReservaFechaDesde.value = '';
            if (filtroReservaFechaHasta) filtroReservaFechaHasta.value = '';
            if (filtroReservaUsuarioEmail) filtroReservaUsuarioEmail.value = '';
            cargarReservas();
        });
    }
    
    // Cargar dashboard por defecto
    setTimeout(() => {
        cambiarSeccion('dashboard');
    }, 100);
    
    console.log('Panel admin inicializado correctamente');
    
    // Add debug functions to window for testing
    window.debugAdmin = {
        testCambiarRolUsuario: function(id = 2, rol = 'usuario') {
            console.log('Testing cambiarRolUsuario with id:', id, 'rol:', rol);
            cambiarRolUsuario(id, rol);
        },
        testCambiarEstadoUsuario: function(id = 2, estado = false) {
            console.log('Testing cambiarEstadoUsuario with id:', id, 'estado:', estado);
            cambiarEstadoUsuario(id, estado);
        },
        testCambiarEstadoReserva: function(id = 1, estado = 'confirmada') {
            console.log('Testing cambiarEstadoReserva with id:', id, 'estado:', estado);
            cambiarEstadoReserva(id, estado);
        },
        testToast: function(mensaje = 'Test message', tipo = 'info') {
            console.log('Testing toast with mensaje:', mensaje, 'tipo:', tipo);
            mostrarToast(mensaje, tipo);
        },
        recargarUsuarios: function() {
            console.log('Recargando usuarios...');
            cargarUsuarios();
        },
        recargarReservas: function() {
            console.log('Recargando reservas...');
            cargarReservas();
        },
        testApiUrl: function() {
            const pathParts = window.location.pathname.split('/');
            const projectFolder = pathParts[1];
            const apiUrl = `/${projectFolder}/api/admin.php`;
            console.log('Current URL:', window.location.href);
            console.log('Path parts:', pathParts);
            console.log('Project folder:', projectFolder);
            console.log('API URL:', apiUrl);
            return apiUrl;
        },
        testDebugApi: async function() {
            const pathParts = window.location.pathname.split('/');
            const projectFolder = pathParts[1];
            const debugUrl = `/${projectFolder}/debug_admin_api.php`;
            console.log('Testing debug API at:', debugUrl);
            
            try {
                const response = await fetch(debugUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        action: 'test_action',
                        test_data: 'hello world'
                    })
                });
                
                const data = await response.json();
                console.log('Debug API response:', data);
                return data;
            } catch (error) {
                console.error('Debug API error:', error);
                return error;
            }
        }
    };
    
    console.log('Debug functions added to window.debugAdmin');
    console.log('Available debug functions:', Object.keys(window.debugAdmin));
});

// Funciones adicionales para administración
function verDetalleReserva(id) {
    console.log('Ver detalle de reserva:', id);
    
    const pathParts = window.location.pathname.split('/');
    const projectFolder = pathParts[1];
    const apiUrl = `/${projectFolder}/api/admin.php?action=reserva_detalle&id=${id}`;
    
    fetch(apiUrl)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                mostrarModalDetalleReserva(data.data);
            } else {
                mostrarToast('Error al cargar detalle: ' + data.error, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            mostrarToast('Error al cargar detalle de reserva', 'error');
        });
}

function mostrarModalDetalleReserva(reserva) {
    const modal = document.getElementById('modal-confirmacion');
    if (!modal) return;
    
    // Cambiar el contenido del modal para mostrar detalles
    const titulo = modal.querySelector('.modal-title');
    const mensaje = modal.querySelector('#confirmacion-mensaje');
    const btnConfirmar = modal.querySelector('#btn-confirmar-accion');
    
    titulo.textContent = 'Detalle de Reserva #' + reserva.id;
    mensaje.innerHTML = `
        <div style="text-align: left;">
            <p><strong>Usuario:</strong> ${reserva.nombre_usuario || 'N/A'} (${reserva.email_usuario || 'N/A'})</p>
            <p><strong>Apartamento:</strong> ${reserva.nombre_apartamento || 'N/A'}</p>
            <p><strong>Provincia:</strong> ${reserva.provincia_apartamento || 'N/A'}</p>
            <p><strong>Fechas:</strong> ${reserva.fecha_entrada} al ${reserva.fecha_salida}</p>
            <p><strong>Huéspedes:</strong> ${reserva.num_huespedes}</p>
            <p><strong>Estado:</strong> <span class="badge ${getBadgeClass(reserva.estado)}">${reserva.estado}</span></p>
            <p><strong>Fecha de reserva:</strong> ${reserva.fecha_reserva || 'N/A'}</p>
            ${reserva.notas ? `<p><strong>Notas:</strong> ${reserva.notas}</p>` : ''}
            ${reserva.precio_total ? `<p><strong>Precio total:</strong> €${reserva.precio_total}</p>` : ''}
        </div>
    `;
    
    btnConfirmar.textContent = 'Cerrar';
    btnConfirmar.className = 'btn btn-secondary';
    btnConfirmar.onclick = () => {
        modal.classList.remove('active');
        // Restaurar el modal original
        titulo.textContent = 'Confirmar Acción';
        btnConfirmar.textContent = 'Confirmar';
        btnConfirmar.className = 'btn btn-danger';
    };
    
    modal.classList.add('active');
}

function cambiarEstadoReserva(id, nuevoEstado) {
    if (!nuevoEstado || nuevoEstado === '') {
        console.log('No se seleccionó un estado válido');
        return;
    }
    
    console.log('Cambiar estado de reserva:', id, 'a', nuevoEstado);
    
    const pathParts = window.location.pathname.split('/');
    const projectFolder = pathParts[1];
    const apiUrl = `/${projectFolder}/api/admin.php`;
    
    console.log('URL de API:', apiUrl);
    
    const requestData = {
        action: 'reserva_cambiar_estado',
        id: parseInt(id),
        estado: nuevoEstado
    };
    
    console.log('Datos de la petición:', requestData);
    
    fetch(apiUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(requestData)
    })
    .then(response => {
        console.log('Respuesta HTTP:', response.status, response.statusText);
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        return response.json();
    })
    .then(data => {
        console.log('Respuesta de la API:', data);
        if (data.success) {
            mostrarToast('Estado de reserva actualizado: ' + data.message, 'success');
            cargarReservas(); // Recargar la tabla
        } else {
            mostrarToast('Error al cambiar estado: ' + data.error, 'error');
        }
    })
    .catch(error => {
        console.error('Error en la petición:', error);
        mostrarToast('Error al cambiar estado: ' + error.message, 'error');
    });
}

function eliminarReserva(id) {
    if (!confirm('¿Estás seguro de que quieres eliminar esta reserva? Esta acción no se puede deshacer.')) {
        return;
    }
    
    const pathParts = window.location.pathname.split('/');
    const projectFolder = pathParts[1];
    const apiUrl = `/${projectFolder}/api/admin.php`;
    
    fetch(apiUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'reserva_eliminar',
            id: id
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            mostrarToast('Reserva eliminada correctamente', 'success');
            cargarReservas(); // Recargar la tabla
        } else {
            mostrarToast('Error al eliminar reserva: ' + data.error, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        mostrarToast('Error al eliminar reserva', 'error');
    });
}

function cambiarRolUsuario(id, nuevoRol) {
    if (!nuevoRol || nuevoRol === '') {
        console.log('No se seleccionó un rol válido');
        return;
    }
    
    console.log('Cambiar rol de usuario:', id, 'a', nuevoRol);
    
    const pathParts = window.location.pathname.split('/');
    const projectFolder = pathParts[1];
    const apiUrl = `/${projectFolder}/api/admin.php`;
    
    console.log('URL de API:', apiUrl);
    
    const requestData = {
        action: 'usuario_cambiar_rol',
        id: parseInt(id),
        rol: nuevoRol
    };
    
    console.log('Datos de la petición:', requestData);
    
    fetch(apiUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(requestData)
    })
    .then(response => {
        console.log('Respuesta HTTP:', response.status, response.statusText);
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        return response.json();
    })
    .then(data => {
        console.log('Respuesta de la API:', data);
        if (data.success) {
            mostrarToast('Rol de usuario actualizado: ' + data.message, 'success');
            cargarUsuarios(); // Recargar la tabla
        } else {
            mostrarToast('Error al cambiar rol: ' + data.error, 'error');
        }
    })
    .catch(error => {
        console.error('Error en la petición:', error);
        mostrarToast('Error al cambiar rol: ' + error.message, 'error');
    });
}

function cambiarEstadoUsuario(id, nuevoEstado) {
    console.log('Cambiar estado de usuario:', id, 'a', nuevoEstado ? 'activo' : 'inactivo');
    
    const pathParts = window.location.pathname.split('/');
    const projectFolder = pathParts[1];
    const apiUrl = `/${projectFolder}/api/admin.php`;
    
    console.log('URL de API:', apiUrl);
    
    const requestData = {
        action: 'usuario_cambiar_estado',
        id: parseInt(id),
        activo: Boolean(nuevoEstado)
    };
    
    console.log('Datos de la petición:', requestData);
    
    fetch(apiUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(requestData)
    })
    .then(response => {
        console.log('Respuesta HTTP:', response.status, response.statusText);
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        return response.json();
    })
    .then(data => {
        console.log('Respuesta de la API:', data);
        if (data.success) {
            mostrarToast('Estado de usuario actualizado: ' + data.message, 'success');
            cargarUsuarios(); // Recargar la tabla
        } else {
            mostrarToast('Error al cambiar estado: ' + data.error, 'error');
        }
    })
    .catch(error => {
        console.error('Error en la petición:', error);
        mostrarToast('Error al cambiar estado: ' + error.message, 'error');
    });
}

function verDetalleUsuario(id) {
    console.log('Ver detalle de usuario:', id);
    
    const pathParts = window.location.pathname.split('/');
    const projectFolder = pathParts[1];
    const apiUrl = `/${projectFolder}/api/admin.php?action=usuario_detalle&id=${id}`;
    
    fetch(apiUrl)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data) {
                const usuario = data.data;
                
                // Crear contenido del modal
                const modalContent = `
                    <div style="padding: 20px;">
                        <h3 style="margin-bottom: 20px; color: #1e293b;">Detalles del Usuario</h3>
                        
                        <div style="display: grid; gap: 15px;">
                            <div style="border-bottom: 1px solid #e2e8f0; padding-bottom: 10px;">
                                <strong style="color: #64748b; font-size: 0.875rem;">ID:</strong>
                                <p style="margin: 5px 0 0 0; font-size: 1rem;">${usuario.id}</p>
                            </div>
                            
                            <div style="border-bottom: 1px solid #e2e8f0; padding-bottom: 10px;">
                                <strong style="color: #64748b; font-size: 0.875rem;">Nombre Completo:</strong>
                                <p style="margin: 5px 0 0 0; font-size: 1rem;">${usuario.nombre} ${usuario.apellidos || ''}</p>
                            </div>
                            
                            <div style="border-bottom: 1px solid #e2e8f0; padding-bottom: 10px;">
                                <strong style="color: #64748b; font-size: 0.875rem;">Email:</strong>
                                <p style="margin: 5px 0 0 0; font-size: 1rem;">${usuario.email}</p>
                            </div>
                            
                            ${usuario.telefono ? `
                            <div style="border-bottom: 1px solid #e2e8f0; padding-bottom: 10px;">
                                <strong style="color: #64748b; font-size: 0.875rem;">Teléfono:</strong>
                                <p style="margin: 5px 0 0 0; font-size: 1rem;">${usuario.telefono}</p>
                            </div>
                            ` : ''}
                            
                            <div style="border-bottom: 1px solid #e2e8f0; padding-bottom: 10px;">
                                <strong style="color: #64748b; font-size: 0.875rem;">Rol:</strong>
                                <p style="margin: 5px 0 0 0;">
                                    <span class="badge ${usuario.rol === 'admin' ? 'badge-info' : 'badge-success'}">
                                        ${usuario.rol === 'admin' ? 'Administrador' : 'Usuario'}
                                    </span>
                                </p>
                            </div>
                            
                            <div style="border-bottom: 1px solid #e2e8f0; padding-bottom: 10px;">
                                <strong style="color: #64748b; font-size: 0.875rem;">Estado:</strong>
                                <p style="margin: 5px 0 0 0;">
                                    <span class="badge ${usuario.activo ? 'badge-success' : 'badge-danger'}">
                                        ${usuario.activo ? 'Activo' : 'Inactivo'}
                                    </span>
                                </p>
                            </div>
                            
                            <div style="border-bottom: 1px solid #e2e8f0; padding-bottom: 10px;">
                                <strong style="color: #64748b; font-size: 0.875rem;">Fecha de Registro:</strong>
                                <p style="margin: 5px 0 0 0; font-size: 1rem;">${new Date(usuario.fecha_registro).toLocaleString('es-ES')}</p>
                            </div>
                            
                            ${usuario.ultima_sesion ? `
                            <div style="border-bottom: 1px solid #e2e8f0; padding-bottom: 10px;">
                                <strong style="color: #64748b; font-size: 0.875rem;">Última Sesión:</strong>
                                <p style="margin: 5px 0 0 0; font-size: 1rem;">${new Date(usuario.ultima_sesion).toLocaleString('es-ES')}</p>
                            </div>
                            ` : ''}
                            
                            ${usuario.total_reservas !== undefined ? `
                            <div style="padding-bottom: 10px;">
                                <strong style="color: #64748b; font-size: 0.875rem;">Total de Reservas:</strong>
                                <p style="margin: 5px 0 0 0; font-size: 1.5rem; color: #2563eb; font-weight: 600;">${usuario.total_reservas || 0}</p>
                            </div>
                            ` : ''}
                        </div>
                        
                        <div style="margin-top: 20px; text-align: right;">
                            <button class="btn btn-secondary" onclick="cerrarModalDetalleUsuario()">Cerrar</button>
                        </div>
                    </div>
                `;
                
                // Crear o actualizar modal
                let modal = document.getElementById('modal-detalle-usuario');
                if (!modal) {
                    modal = document.createElement('div');
                    modal.id = 'modal-detalle-usuario';
                    modal.className = 'modal-overlay';
                    modal.innerHTML = `
                        <div class="modal" style="max-width: 600px;">
                            <div class="modal-header">
                                <h3 class="modal-title">Información del Usuario</h3>
                                <button class="modal-close" onclick="cerrarModalDetalleUsuario()">&times;</button>
                            </div>
                            <div class="modal-body" id="modal-detalle-usuario-body">
                            </div>
                        </div>
                    `;
                    document.body.appendChild(modal);
                }
                
                // Actualizar contenido
                document.getElementById('modal-detalle-usuario-body').innerHTML = modalContent;
                
                // Mostrar modal
                modal.classList.add('active');
                document.body.style.overflow = 'hidden';
                
            } else {
                mostrarToast('Error al cargar detalles del usuario: ' + (data.error || 'Datos no encontrados'), 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            mostrarToast('Error al cargar detalles del usuario', 'error');
        });
}

function cerrarModalDetalleUsuario() {
    const modal = document.getElementById('modal-detalle-usuario');
    if (modal) {
        modal.classList.remove('active');
        document.body.style.overflow = '';
    }
}

function eliminarUsuario(id) {
    if (!confirm('¿Estás seguro de que quieres eliminar este usuario? Esta acción no se puede deshacer y eliminará también todas sus reservas.')) {
        return;
    }
    
    const pathParts = window.location.pathname.split('/');
    const projectFolder = pathParts[1];
    const apiUrl = `/${projectFolder}/api/admin.php`;
    
    fetch(apiUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'usuario_eliminar',
            id: id
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            mostrarToast('Usuario eliminado correctamente', 'success');
            cargarUsuarios(); // Recargar la tabla
        } else {
            mostrarToast('Error al eliminar usuario: ' + data.error, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        mostrarToast('Error al eliminar usuario', 'error');
    });
}

function getCurrentUserId() {
    // Obtener el ID del usuario actual desde PHP
    return <?= $_SESSION['usuario_id'] ?? 1 ?>;
}

// Funciones para gestión de modales
function cerrarModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('active');
    }
}

function abrirModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('active');
    }
}

// Configurar eventos de cierre de modales
document.addEventListener('click', function(e) {
    if (e.target.matches('[data-modal-close]')) {
        const modal = e.target.closest('.modal-overlay');
        if (modal) {
            modal.classList.remove('active');
        }
    }
    
    if (e.target.matches('.modal-overlay')) {
        e.target.classList.remove('active');
    }
});

// Cerrar modales con Escape
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const modalesActivos = document.querySelectorAll('.modal-overlay.active');
        modalesActivos.forEach(modal => {
            modal.classList.remove('active');
        });
    }
});
</script>