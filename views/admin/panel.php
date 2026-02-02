<?php
/**
 * Vista del Panel de Administrador
 * Interfaz completa para gestión del sistema
 */

$pageTitle = $pageTitle ?? 'Panel de Administrador';
include __DIR__ . '/../partials/header.php';
?>

<div class="admin-panel">
    <!-- Sidebar de navegación -->
    <aside class="admin-sidebar">
        <div class="admin-sidebar-header">
            <h2>Panel Admin</h2>
            <span class="admin-user-info"><?= htmlspecialchars($usuario['nombre'] ?? 'Administrador') ?></span>
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
            <a href="<?= BASE_URL ?>index.php" class="btn btn-secondary btn-sm">
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

<?php include __DIR__ . '/../partials/footer.php'; ?>

<!-- Estilos específicos del panel admin -->
<link rel="stylesheet" href="<?= PUBLIC_URL ?>css/admin.css">

<!-- JavaScript del panel admin -->
<script src="<?= PUBLIC_URL ?>js/admin.js"></script>