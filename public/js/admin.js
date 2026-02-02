/**
 * Panel de Administrador - JavaScript
 * Gestión completa del sistema de apartamentos
 */

// ===================================
// FUNCIONES DE UTILIDAD
// ===================================

// Detectar la ruta base automáticamente - Versión mejorada
const getBasePath = () => {
    const path = window.location.pathname;
    const parts = path.split('/').filter(p => p);
    
    // Detectar si estamos en subcarpetas
    if (parts.includes('views')) {
        if (parts.includes('admin')) {
            return '../../api';  // Desde views/admin/
        }
        return '../api';  // Desde views/
    }
    
    // Desde raíz del proyecto
    return './api';
};
const API_BASE = getBasePath();

/**
 * Realiza peticiones a la API con manejo de errores
 */
async function apiRequest(endpoint, options = {}) {
    const defaultOptions = {
        headers: {
            'Content-Type': 'application/json',
        },
    };

    const config = { ...defaultOptions, ...options };
    
    try {
        const response = await fetch(`${API_BASE}/${endpoint}`, config);
        const data = await response.json();
        
        if (!response.ok) {
            throw new Error(data.error || `Error HTTP: ${response.status}`);
        }
        
        return data;
    } catch (error) {
        console.error('API Error:', error);
        throw error;
    }
}

function formatDate(dateString) {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    return date.toLocaleDateString('es-ES', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric'
    });
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function showToast(message, type = 'info') {
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.innerHTML = `
        <span class="toast-icon">${getToastIcon(type)}</span>
        <span class="toast-message">${message}</span>
    `;
    
    document.body.appendChild(toast);
    
    requestAnimationFrame(() => {
        toast.classList.add('show');
    });
    
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 300);
    }, 4000);
}

function getToastIcon(type) {
    const icons = {
        success: '✓',
        error: '✕',
        warning: '⚠',
        info: 'ℹ'
    };
    return icons[type] || icons.info;
}

function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// ===================================
// MÓDULO DE AUTENTICACIÓN (SIMPLIFICADO)
// ===================================

const AuthModule = {
    openModal(id) {
        const modal = document.getElementById(id);
        if (modal) {
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }
    },

    closeModal(id) {
        const modal = document.getElementById(id);
        if (modal) {
            modal.classList.remove('active');
            document.body.style.overflow = '';
        }
    }
};

// ===================================
// MÓDULO PRINCIPAL DE ADMINISTRACIÓN
// ===================================

const AdminModule = {
    currentSection: 'dashboard',
    refreshInterval: null,
    
    init() {
        console.log('Inicializando AdminModule'); // Debug
        this.bindEvents();
        this.loadSection('dashboard');
        this.startAutoRefresh();
    },

    bindEvents() {
        console.log('Vinculando eventos'); // Debug
        
        // Navegación entre secciones
        document.querySelectorAll('.admin-nav-link').forEach((link, index) => {
            console.log(`Vinculando evento para botón ${index}:`, link.dataset.section); // Debug
            
            link.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                const section = link.dataset.section;
                console.log('Click en sección:', section); // Debug
                if (section) {
                    this.loadSection(section);
                }
            });
        });

        // Modal de confirmación
        document.getElementById('btn-confirmar-accion').addEventListener('click', () => {
            if (this.pendingAction) {
                this.pendingAction();
                this.pendingAction = null;
                AuthModule.closeModal('modal-confirmacion');
            }
        });

        // Cerrar modal con botones de cerrar
        document.querySelectorAll('[data-modal-close]').forEach(btn => {
            btn.addEventListener('click', () => {
                AuthModule.closeModal('modal-confirmacion');
            });
        });

        // Cerrar modal al hacer clic fuera
        document.getElementById('modal-confirmacion').addEventListener('click', (e) => {
            if (e.target.classList.contains('modal-overlay')) {
                AuthModule.closeModal('modal-confirmacion');
            }
        });
    },

    loadSection(section) {
        console.log('Cargando sección:', section); // Debug
        
        // Actualizar navegación
        document.querySelectorAll('.admin-nav-link').forEach(link => {
            link.classList.remove('active');
        });
        
        const activeLink = document.querySelector(`[data-section="${section}"]`);
        if (activeLink) {
            activeLink.classList.add('active');
        }

        // Mostrar sección
        document.querySelectorAll('.admin-section').forEach(sec => {
            sec.classList.remove('active');
            sec.style.display = 'none';
        });
        
        const targetSection = document.getElementById(`section-${section}`);
        if (targetSection) {
            targetSection.classList.add('active');
            targetSection.style.display = 'block';
        }

        this.currentSection = section;

        // Cargar datos de la sección
        switch (section) {
            case 'dashboard':
                this.loadDashboard();
                break;
            case 'usuarios':
                this.loadUsuarios();
                break;
            case 'reservas':
                this.loadReservas();
                break;
            case 'sincronizacion':
                this.loadSincronizacion();
                break;
        }
    },

    startAutoRefresh() {
        // Actualizar dashboard cada 5 minutos
        this.refreshInterval = setInterval(() => {
            if (this.currentSection === 'dashboard') {
                this.loadDashboard();
            }
        }, 5 * 60 * 1000);
    },

    showConfirmation(message, action) {
        document.getElementById('confirmacion-mensaje').textContent = message;
        this.pendingAction = action;
        AuthModule.openModal('modal-confirmacion');
    }
};

// ===================================
// SERVICIO DE API ADMINISTRATIVA
// ===================================

const AdminApiService = {
    async request(endpoint, options = {}) {
        const defaultOptions = {
            headers: {
                'Content-Type': 'application/json',
            },
        };

        const config = { ...defaultOptions, ...options };
        
        try {
            const response = await fetch(`${API_BASE}/admin.php?action=${endpoint}`, config);
            const data = await response.json();
            
            if (!response.ok) {
                throw new Error(data.error || `Error HTTP: ${response.status}`);
            }
            
            return data;
        } catch (error) {
            console.error('Admin API Error:', error);
            throw error;
        }
    },

    // Estadísticas
    async getEstadisticas() {
        return this.request('estadisticas');
    },

    // Usuarios
    async getUsuarios() {
        return this.request('usuarios_listar');
    },

    async cambiarEstadoUsuario(id, activo) {
        return this.request('usuario_cambiar_estado', {
            method: 'POST',
            body: JSON.stringify({ id, activo })
        });
    },

    async eliminarUsuario(id) {
        return this.request('usuario_eliminar', {
            method: 'POST',
            body: JSON.stringify({ id })
        });
    },

    // Reservas
    async getReservas() {
        return this.request('reservas_listar');
    },

    async cambiarEstadoReserva(id, estado) {
        return this.request('reserva_cambiar_estado', {
            method: 'POST',
            body: JSON.stringify({ id, estado })
        });
    },

    // Sincronización
    async getEstadoSync() {
        return this.request('sync_estado');
    },

    async ejecutarSync() {
        return this.request('sync_ejecutar', {
            method: 'POST'
        });
    }
};

// ===================================
// COMPONENTE DASHBOARD
// ===================================

const DashboardComponent = {
    async load() {
        try {
            // Intentar cargar datos reales de la API
            const response = await AdminApiService.getEstadisticas();
            
            if (response.success) {
                this.renderEstadisticas(response.data);
                this.renderGraficos(response.data);
            }
        } catch (error) {
            console.error('Error cargando dashboard:', error);
            // Si falla la API, mostrar datos de ejemplo
            this.loadMockData();
        }
    },

    loadMockData() {
        const mockData = {
            usuarios: {
                total: 25,
                activos: 22,
                inactivos: 3,
                por_rol: {
                    'usuario': 23,
                    'admin': 2
                }
            },
            reservas: {
                total: 48,
                por_estado: {
                    'pendiente': 8,
                    'confirmada': 15,
                    'cancelada': 5,
                    'completada': 20
                },
                este_mes: 12
            },
            apartamentos: {
                total: 150,
                ocupados: 45,
                tasa_ocupacion: 30.0
            }
        };
        
        this.renderEstadisticas(mockData);
        this.renderGraficos(mockData);
        showToast('Mostrando datos de ejemplo', 'info');
    },

    renderEstadisticas(data) {
        document.getElementById('stat-usuarios-total').textContent = data.usuarios.total.toLocaleString();
        document.getElementById('stat-reservas-total').textContent = data.reservas.total.toLocaleString();
        document.getElementById('stat-apartamentos-total').textContent = data.apartamentos.total.toLocaleString();
        document.getElementById('stat-ocupacion').textContent = data.apartamentos.tasa_ocupacion + '%';
    },

    renderGraficos(data) {
        // Gráfico de usuarios por rol
        const usuariosRolContainer = document.getElementById('chart-usuarios-rol');
        usuariosRolContainer.innerHTML = '';
        
        Object.entries(data.usuarios.por_rol).forEach(([rol, cantidad]) => {
            const item = document.createElement('div');
            item.className = 'admin-chart-item';
            item.innerHTML = `
                <div class="admin-chart-bar">
                    <div class="admin-chart-fill" style="width: ${(cantidad / data.usuarios.total) * 100}%"></div>
                </div>
                <span class="admin-chart-label">${rol}: ${cantidad}</span>
            `;
            usuariosRolContainer.appendChild(item);
        });

        // Gráfico de reservas por estado
        const reservasEstadoContainer = document.getElementById('chart-reservas-estado');
        reservasEstadoContainer.innerHTML = '';
        
        Object.entries(data.reservas.por_estado).forEach(([estado, cantidad]) => {
            const item = document.createElement('div');
            item.className = 'admin-chart-item';
            item.innerHTML = `
                <div class="admin-chart-bar">
                    <div class="admin-chart-fill" style="width: ${(cantidad / data.reservas.total) * 100}%"></div>
                </div>
                <span class="admin-chart-label">${estado}: ${cantidad}</span>
            `;
            reservasEstadoContainer.appendChild(item);
        });
    }
};

// ===================================
// COMPONENTE GESTIÓN DE USUARIOS
// ===================================

const UserManagementComponent = {
    usuarios: [],
    filtros: {},

    async load() {
        try {
            const response = await AdminApiService.getUsuarios();
            
            if (response.success) {
                this.usuarios = response.data;
                this.renderUsuarios();
                this.bindEvents();
            }
        } catch (error) {
            console.error('Error cargando usuarios:', error);
            // Si falla la API, mostrar datos de ejemplo
            this.loadMockData();
        }
    },

    loadMockData() {
        this.usuarios = [
            {
                id: 1,
                email: 'admin@apartamentoscyl.es',
                nombre: 'Administrador',
                apellidos: 'Sistema',
                rol: 'admin',
                activo: true,
                fecha_registro: '2024-01-15 10:30:00'
            },
            {
                id: 2,
                email: 'juan@test.com',
                nombre: 'Juan',
                apellidos: 'Pérez García',
                rol: 'usuario',
                activo: true,
                fecha_registro: '2024-02-01 14:20:00'
            },
            {
                id: 3,
                email: 'maria@test.com',
                nombre: 'María',
                apellidos: 'López Martín',
                rol: 'usuario',
                activo: true,
                fecha_registro: '2024-02-05 09:15:00'
            },
            {
                id: 4,
                email: 'carlos@test.com',
                nombre: 'Carlos',
                apellidos: 'Ruiz Sánchez',
                rol: 'usuario',
                activo: false,
                fecha_registro: '2024-01-28 16:45:00'
            }
        ];
        
        this.renderUsuarios();
        this.bindEvents();
        showToast('Mostrando datos de ejemplo de usuarios', 'info');
    },

    bindEvents() {
        // Filtros
        document.getElementById('filtro-usuario-rol').addEventListener('change', (e) => {
            this.filtros.rol = e.target.value;
            this.renderUsuarios();
        });

        document.getElementById('filtro-usuario-estado').addEventListener('change', (e) => {
            this.filtros.estado = e.target.value;
            this.renderUsuarios();
        });

        document.getElementById('filtro-usuario-email').addEventListener('input', debounce((e) => {
            this.filtros.email = e.target.value.toLowerCase();
            this.renderUsuarios();
        }, 300));

        document.getElementById('btn-limpiar-filtros-usuarios').addEventListener('click', () => {
            this.limpiarFiltros();
        });
    },

    renderUsuarios() {
        const tbody = document.querySelector('#tabla-usuarios tbody');
        const usuariosFiltrados = this.filtrarUsuarios();
        
        tbody.innerHTML = usuariosFiltrados.map(usuario => `
            <tr>
                <td>${usuario.id}</td>
                <td>${escapeHtml(usuario.email)}</td>
                <td>${escapeHtml(usuario.nombre)} ${escapeHtml(usuario.apellidos || '')}</td>
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
                <td>${formatDate(usuario.fecha_registro)}</td>
                <td>
                    <button class="btn btn-sm ${usuario.activo ? 'btn-warning' : 'btn-success'}" 
                            onclick="UserManagementComponent.cambiarEstado(${usuario.id}, ${!usuario.activo})">
                        ${usuario.activo ? 'Desactivar' : 'Activar'}
                    </button>
                    <button class="btn btn-sm btn-danger" 
                            onclick="UserManagementComponent.eliminar(${usuario.id})">
                        Eliminar
                    </button>
                </td>
            </tr>
        `).join('');
    },

    filtrarUsuarios() {
        return this.usuarios.filter(usuario => {
            if (this.filtros.rol && usuario.rol !== this.filtros.rol) return false;
            if (this.filtros.estado !== '' && usuario.activo !== (this.filtros.estado === '1')) return false;
            if (this.filtros.email && !usuario.email.toLowerCase().includes(this.filtros.email)) return false;
            return true;
        });
    },

    async cambiarEstado(id, activo) {
        const accion = activo ? 'activar' : 'desactivar';
        
        AdminModule.showConfirmation(
            `¿Estás seguro de ${accion} este usuario?`,
            async () => {
                try {
                    const response = await AdminApiService.cambiarEstadoUsuario(id, activo);
                    
                    if (response.success) {
                        showToast(response.message, 'success');
                        this.load(); // Recargar lista
                    }
                } catch (error) {
                    showToast(error.message || 'Error al cambiar estado', 'error');
                }
            }
        );
    },

    async eliminar(id) {
        AdminModule.showConfirmation(
            '¿Estás seguro de eliminar este usuario? Esta acción no se puede deshacer.',
            async () => {
                try {
                    const response = await AdminApiService.eliminarUsuario(id);
                    
                    if (response.success) {
                        showToast(response.message, 'success');
                        this.load(); // Recargar lista
                    }
                } catch (error) {
                    showToast(error.message || 'Error al eliminar usuario', 'error');
                }
            }
        );
    },

    limpiarFiltros() {
        this.filtros = {};
        document.getElementById('filtro-usuario-rol').value = '';
        document.getElementById('filtro-usuario-estado').value = '';
        document.getElementById('filtro-usuario-email').value = '';
        this.renderUsuarios();
    }
};

// ===================================
// COMPONENTE GESTIÓN DE RESERVAS
// ===================================

const ReservationManagementComponent = {
    reservas: [],
    filtros: {},

    async load() {
        try {
            const response = await AdminApiService.getReservas();
            
            if (response.success) {
                this.reservas = response.data;
                this.renderReservas();
                this.bindEvents();
            }
        } catch (error) {
            console.error('Error cargando reservas:', error);
            // Si falla la API, mostrar datos de ejemplo
            this.loadMockData();
        }
    },

    loadMockData() {
        this.reservas = [
            {
                id: 1,
                usuario_email: 'juan@test.com',
                apartamento_nombre: 'Apartamento Centro Salamanca',
                fecha_entrada: '2024-03-15',
                fecha_salida: '2024-03-18',
                estado: 'confirmada',
                fecha_creacion: '2024-02-20 10:30:00'
            },
            {
                id: 2,
                usuario_email: 'maria@test.com',
                apartamento_nombre: 'Casa Rural Valladolid',
                fecha_entrada: '2024-04-01',
                fecha_salida: '2024-04-05',
                estado: 'pendiente',
                fecha_creacion: '2024-02-25 14:20:00'
            },
            {
                id: 3,
                usuario_email: 'carlos@test.com',
                apartamento_nombre: 'Apartamento León Centro',
                fecha_entrada: '2024-02-20',
                fecha_salida: '2024-02-22',
                estado: 'completada',
                fecha_creacion: '2024-02-10 09:15:00'
            },
            {
                id: 4,
                usuario_email: 'juan@test.com',
                apartamento_nombre: 'Apartamento Centro Salamanca',
                fecha_entrada: '2024-05-10',
                fecha_salida: '2024-05-12',
                estado: 'cancelada',
                fecha_creacion: '2024-02-28 16:45:00'
            }
        ];
        
        this.renderReservas();
        this.bindEvents();
        showToast('Mostrando datos de ejemplo de reservas', 'info');
    },

    bindEvents() {
        // Filtros
        document.getElementById('filtro-reserva-estado').addEventListener('change', (e) => {
            this.filtros.estado = e.target.value;
            this.renderReservas();
        });

        document.getElementById('filtro-reserva-fecha-desde').addEventListener('change', (e) => {
            this.filtros.fechaDesde = e.target.value;
            this.renderReservas();
        });

        document.getElementById('filtro-reserva-fecha-hasta').addEventListener('change', (e) => {
            this.filtros.fechaHasta = e.target.value;
            this.renderReservas();
        });

        document.getElementById('btn-limpiar-filtros-reservas').addEventListener('click', () => {
            this.limpiarFiltros();
        });
    },

    renderReservas() {
        const tbody = document.querySelector('#tabla-reservas tbody');
        const reservasFiltradas = this.filtrarReservas();
        
        tbody.innerHTML = reservasFiltradas.map(reserva => `
            <tr>
                <td>${reserva.id}</td>
                <td>${escapeHtml(reserva.usuario_email || 'N/A')}</td>
                <td>${escapeHtml(reserva.apartamento_nombre || 'N/A')}</td>
                <td>${formatDate(reserva.fecha_entrada)}</td>
                <td>${formatDate(reserva.fecha_salida)}</td>
                <td>
                    <select class="form-input" onchange="ReservationManagementComponent.cambiarEstado(${reserva.id}, this.value)">
                        <option value="pendiente" ${reserva.estado === 'pendiente' ? 'selected' : ''}>Pendiente</option>
                        <option value="confirmada" ${reserva.estado === 'confirmada' ? 'selected' : ''}>Confirmada</option>
                        <option value="cancelada" ${reserva.estado === 'cancelada' ? 'selected' : ''}>Cancelada</option>
                        <option value="completada" ${reserva.estado === 'completada' ? 'selected' : ''}>Completada</option>
                    </select>
                </td>
                <td>${formatDate(reserva.fecha_creacion)}</td>
                <td>
                    <button class="btn btn-sm btn-info" onclick="ReservationManagementComponent.verDetalle(${reserva.id})">
                        Ver Detalle
                    </button>
                </td>
            </tr>
        `).join('');
    },

    filtrarReservas() {
        return this.reservas.filter(reserva => {
            if (this.filtros.estado && reserva.estado !== this.filtros.estado) return false;
            if (this.filtros.fechaDesde && reserva.fecha_entrada < this.filtros.fechaDesde) return false;
            if (this.filtros.fechaHasta && reserva.fecha_entrada > this.filtros.fechaHasta) return false;
            return true;
        });
    },

    async cambiarEstado(id, nuevoEstado) {
        try {
            const response = await AdminApiService.cambiarEstadoReserva(id, nuevoEstado);
            
            if (response.success) {
                showToast('Estado actualizado correctamente', 'success');
                // Actualizar en la lista local
                const reserva = this.reservas.find(r => r.id === id);
                if (reserva) {
                    reserva.estado = nuevoEstado;
                }
            }
        } catch (error) {
            showToast(error.message || 'Error al cambiar estado', 'error');
            this.renderReservas(); // Revertir cambio visual
        }
    },

    verDetalle(id) {
        const reserva = this.reservas.find(r => r.id === id);
        if (reserva) {
            alert(`Detalle de reserva #${id}\n\nUsuario: ${reserva.usuario_email}\nFechas: ${reserva.fecha_entrada} - ${reserva.fecha_salida}\nEstado: ${reserva.estado}`);
        }
    },

    limpiarFiltros() {
        this.filtros = {};
        document.getElementById('filtro-reserva-estado').value = '';
        document.getElementById('filtro-reserva-fecha-desde').value = '';
        document.getElementById('filtro-reserva-fecha-hasta').value = '';
        this.renderReservas();
    }
};

// ===================================
// COMPONENTE SINCRONIZACIÓN
// ===================================

const SyncToolsComponent = {
    async load() {
        this.loadEstado();
        this.bindEvents();
    },

    bindEvents() {
        document.getElementById('btn-ejecutar-sync').addEventListener('click', () => {
            this.ejecutarSincronizacion();
        });
    },

    async loadEstado() {
        try {
            const response = await AdminApiService.getEstadoSync();
            
            if (response.success) {
                this.renderEstado(response.data);
            }
        } catch (error) {
            console.error('Error cargando estado de sync:', error);
        }
    },

    renderEstado(data) {
        const container = document.getElementById('sync-status');
        container.innerHTML = `
            <div class="admin-sync-info">
                <div class="admin-sync-info-value">${formatDate(data.ultima_sincronizacion)}</div>
                <div class="admin-sync-info-label">Última Sincronización</div>
            </div>
            <div class="admin-sync-info">
                <div class="admin-sync-info-value">${data.registros_procesados.toLocaleString()}</div>
                <div class="admin-sync-info-label">Registros Procesados</div>
            </div>
            <div class="admin-sync-info">
                <div class="admin-sync-info-value">${data.registros_nuevos}</div>
                <div class="admin-sync-info-label">Nuevos</div>
            </div>
            <div class="admin-sync-info">
                <div class="admin-sync-info-value">${data.registros_actualizados}</div>
                <div class="admin-sync-info-label">Actualizados</div>
            </div>
        `;
    },

    async ejecutarSincronizacion() {
        const btnEjecutar = document.getElementById('btn-ejecutar-sync');
        const progressContainer = document.getElementById('sync-progress');
        const resultContainer = document.getElementById('sync-result');
        
        // Mostrar progreso
        btnEjecutar.disabled = true;
        progressContainer.style.display = 'block';
        resultContainer.style.display = 'none';
        
        try {
            const response = await AdminApiService.ejecutarSync();
            
            if (response.success) {
                // Mostrar resultado exitoso
                resultContainer.className = 'admin-sync-result success';
                resultContainer.innerHTML = `
                    <h4>✅ Sincronización Completada</h4>
                    <p><strong>Procesados:</strong> ${response.data.procesados}</p>
                    <p><strong>Nuevos:</strong> ${response.data.nuevos}</p>
                    <p><strong>Actualizados:</strong> ${response.data.actualizados}</p>
                    <p><strong>Errores:</strong> ${response.data.errores}</p>
                    <p><em>${response.data.mensaje}</em></p>
                `;
                
                showToast('Sincronización completada exitosamente', 'success');
                this.loadEstado(); // Actualizar estado
            }
        } catch (error) {
            // Mostrar error
            resultContainer.className = 'admin-sync-result error';
            resultContainer.innerHTML = `
                <h4>❌ Error en Sincronización</h4>
                <p>${error.message}</p>
            `;
            
            showToast('Error en la sincronización', 'error');
        } finally {
            // Ocultar progreso y mostrar resultado
            progressContainer.style.display = 'none';
            resultContainer.style.display = 'block';
            btnEjecutar.disabled = false;
        }
    }
};

// ===================================
// FUNCIONES PRINCIPALES DEL MÓDULO
// ===================================

AdminModule.loadDashboard = () => DashboardComponent.load();
AdminModule.loadUsuarios = () => UserManagementComponent.load();
AdminModule.loadReservas = () => ReservationManagementComponent.load();
AdminModule.loadSincronizacion = () => SyncToolsComponent.load();

// ===================================
// ESTILOS ADICIONALES PARA GRÁFICOS
// ===================================

const chartStyles = document.createElement('style');
chartStyles.textContent = `
    .admin-chart-item {
        display: flex;
        align-items: center;
        gap: var(--space-md);
        margin-bottom: var(--space-md);
    }
    
    .admin-chart-bar {
        flex: 1;
        height: 20px;
        background: var(--color-border);
        border-radius: 10px;
        overflow: hidden;
    }
    
    .admin-chart-fill {
        height: 100%;
        background: var(--color-primary);
        transition: width 0.3s ease;
    }
    
    .admin-chart-label {
        font-size: 0.875rem;
        color: var(--color-text);
        min-width: 100px;
    }
`;
document.head.appendChild(chartStyles);

// ===================================
// INICIALIZACIÓN
// ===================================

document.addEventListener('DOMContentLoaded', () => {
    console.log('DOM cargado, verificando panel admin...');
    
    // Solo inicializar si estamos en la página de admin
    if (document.querySelector('.admin-panel')) {
        console.log('Panel admin encontrado, inicializando...');
        
        // Esperar un poco para asegurar que todo esté cargado
        setTimeout(() => {
            AdminModule.init();
        }, 100);
    } else {
        console.log('No se encontró panel admin en esta página');
    }
});