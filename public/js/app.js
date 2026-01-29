/**
 * Apartamentos Turísticos de Castilla y León
 * JavaScript Principal - Comunicación asíncrona con fetch()
 */

// ===================================
// CONFIGURACIÓN Y UTILIDADES
// ===================================

const API_BASE = './api';

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

/**
 * Muestra notificación toast
 */
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

/**
 * Formatea fecha para mostrar
 */
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('es-ES', {
        day: '2-digit',
        month: 'long',
        year: 'numeric'
    });
}

/**
 * Debounce para búsquedas
 */
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

/**
 * Escape HTML para prevenir XSS
 */
function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// ===================================
// GESTIÓN DE APARTAMENTOS
// ===================================

const ApartamentosModule = {
    currentPage: 1,
    currentFilters: {},
    isLoading: false,

    init() {
        this.bindEvents();
        this.loadProvincias();
    },

    bindEvents() {
        const provinciaSelect = document.getElementById('filtro-provincia');
        if (provinciaSelect) {
            provinciaSelect.addEventListener('change', (e) => {
                this.currentFilters.provincia = e.target.value;
                this.loadMunicipios(e.target.value);
                this.currentPage = 1;
                this.loadApartamentos();
            });
        }

        const municipioSelect = document.getElementById('filtro-municipio');
        if (municipioSelect) {
            municipioSelect.addEventListener('change', (e) => {
                this.currentFilters.municipio = e.target.value;
                this.currentPage = 1;
                this.loadApartamentos();
            });
        }

        const searchInput = document.getElementById('filtro-nombre');
        if (searchInput) {
            searchInput.addEventListener('input', debounce((e) => {
                this.currentFilters.nombre = e.target.value;
                this.currentPage = 1;
                this.loadApartamentos();
            }, 400));
        }

        const capacidadSelect = document.getElementById('filtro-capacidad');
        if (capacidadSelect) {
            capacidadSelect.addEventListener('change', (e) => {
                this.currentFilters.capacidad_min = e.target.value;
                this.currentPage = 1;
                this.loadApartamentos();
            });
        }

        const accesibleCheck = document.getElementById('filtro-accesible');
        if (accesibleCheck) {
            accesibleCheck.addEventListener('change', (e) => {
                this.currentFilters.accesible = e.target.checked ? '1' : '';
                this.currentPage = 1;
                this.loadApartamentos();
            });
        }

        const clearBtn = document.getElementById('btn-limpiar-filtros');
        if (clearBtn) {
            clearBtn.addEventListener('click', () => this.clearFilters());
        }
    },

    async loadProvincias() {
        try {
            const response = await apiRequest('apartamentos.php?action=provincias');
            const select = document.getElementById('filtro-provincia');
            
            if (select && response.data) {
                select.innerHTML = '<option value="">Todas las provincias</option>';
                response.data.forEach(item => {
                    select.innerHTML += `
                        <option value="${item.provincia}">
                            ${item.provincia} (${item.total})
                        </option>
                    `;
                });
            }
        } catch (error) {
            console.error('Error cargando provincias:', error);
        }
    },

    async loadMunicipios(provincia) {
        const select = document.getElementById('filtro-municipio');
        if (!select) return;

        if (!provincia) {
            select.innerHTML = '<option value="">Todos los municipios</option>';
            select.disabled = true;
            return;
        }

        try {
            const response = await apiRequest(`apartamentos.php?action=municipios&provincia=${encodeURIComponent(provincia)}`);
            
            select.innerHTML = '<option value="">Todos los municipios</option>';
            select.disabled = false;
            
            if (response.data) {
                response.data.forEach(item => {
                    select.innerHTML += `
                        <option value="${item.municipio}">
                            ${item.municipio} (${item.total})
                        </option>
                    `;
                });
            }
        } catch (error) {
            console.error('Error cargando municipios:', error);
        }
    },

    async loadApartamentos() {
        if (this.isLoading) return;
        this.isLoading = true;

        const container = document.getElementById('apartamentos-grid');
        const countEl = document.getElementById('results-count');
        
        if (!container) return;

        container.innerHTML = this.getLoadingHTML();

        try {
            const params = new URLSearchParams({
                action: 'listar',
                page: this.currentPage,
                limit: 12
            });

            Object.entries(this.currentFilters).forEach(([key, value]) => {
                if (value) params.append(key, value);
            });

            const response = await apiRequest(`apartamentos.php?${params.toString()}`);
            
            if (response.success && response.data) {
                this.renderApartamentos(response.data, container);
                this.renderPagination(response.pagination);
                
                if (countEl) {
                    countEl.innerHTML = `
                        Mostrando <strong>${response.data.length}</strong> de 
                        <strong>${response.pagination.total}</strong> apartamentos
                    `;
                }
            } else {
                container.innerHTML = this.getEmptyHTML();
            }
        } catch (error) {
            container.innerHTML = this.getErrorHTML(error.message);
            showToast('Error al cargar apartamentos', 'error');
        } finally {
            this.isLoading = false;
        }
    },

    renderApartamentos(apartamentos, container) {
        if (apartamentos.length === 0) {
            container.innerHTML = this.getEmptyHTML();
            return;
        }

        container.innerHTML = apartamentos.map(apt => this.getApartamentoCard(apt)).join('');
    },

    getApartamentoCard(apt) {
        const accesibleBadge = apt.accesible ? 
            '<span class="badge badge-accent">♿ Accesible</span>' : '';
        
        return `
            <article class="card" data-id="${apt.id}">
                <div class="card-image">
                    <span class="card-image-placeholder">🏠</span>
                    ${apt.capacidad_alojamiento > 6 ? '<span class="card-badge">Grande</span>' : ''}
                </div>
                <div class="card-body">
                    <h3 class="card-title">${escapeHtml(apt.nombre)}</h3>
                    <p class="card-subtitle">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                            <circle cx="12" cy="10" r="3"></circle>
                        </svg>
                        ${escapeHtml(apt.municipio || '')}${apt.municipio ? ', ' : ''}${escapeHtml(apt.provincia)}
                    </p>
                    <div class="card-meta">
                        <span class="card-meta-item">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                <circle cx="9" cy="7" r="4"></circle>
                            </svg>
                            ${apt.capacidad_alojamiento || '?'} plazas
                        </span>
                        ${accesibleBadge}
                    </div>
                </div>
                <div style="padding: var(--space-md) var(--space-lg); border-top: 1px solid var(--color-border);">
                    <button class="btn btn-primary btn-sm" onclick="ApartamentosModule.showDetail(${apt.id})">
                        Ver detalles
                    </button>
                </div>
            </article>
        `;
    },

    renderPagination(pagination) {
        const container = document.getElementById('pagination');
        if (!container || !pagination) return;

        const { page, total_pages } = pagination;
        
        if (total_pages <= 1) {
            container.innerHTML = '';
            return;
        }

        let html = '';
        
        html += `
            <button class="pagination-btn" 
                    onclick="ApartamentosModule.goToPage(${page - 1})"
                    ${page <= 1 ? 'disabled' : ''}>
                ←
            </button>
        `;

        const start = Math.max(1, page - 2);
        const end = Math.min(total_pages, page + 2);

        if (start > 1) {
            html += `<button class="pagination-btn" onclick="ApartamentosModule.goToPage(1)">1</button>`;
            if (start > 2) html += `<span style="padding: 0 8px;">...</span>`;
        }

        for (let i = start; i <= end; i++) {
            html += `
                <button class="pagination-btn ${i === page ? 'active' : ''}" 
                        onclick="ApartamentosModule.goToPage(${i})">
                    ${i}
                </button>
            `;
        }

        if (end < total_pages) {
            if (end < total_pages - 1) html += `<span style="padding: 0 8px;">...</span>`;
            html += `<button class="pagination-btn" onclick="ApartamentosModule.goToPage(${total_pages})">${total_pages}</button>`;
        }

        html += `
            <button class="pagination-btn" 
                    onclick="ApartamentosModule.goToPage(${page + 1})"
                    ${page >= total_pages ? 'disabled' : ''}>
                →
            </button>
        `;

        container.innerHTML = html;
    },

    goToPage(page) {
        this.currentPage = page;
        this.loadApartamentos();
        window.scrollTo({ top: 0, behavior: 'smooth' });
    },

    async showDetail(id) {
        try {
            const response = await apiRequest(`apartamentos.php?action=detalle&id=${id}`);
            
            if (response.success && response.data) {
                this.openDetailModal(response.data);
            }
        } catch (error) {
            showToast('Error al cargar detalles', 'error');
        }
    },

    openDetailModal(apt) {
        const modal = document.getElementById('modal-detalle');
        if (!modal) return;

        const content = modal.querySelector('.modal-body');
        content.innerHTML = `
            <div style="margin-bottom: var(--space-lg);">
                <h3 style="margin-bottom: var(--space-xs);">${escapeHtml(apt.nombre)}</h3>
                <p class="text-muted">${escapeHtml(apt.n_registro)}</p>
            </div>
            
            <div style="margin-bottom: var(--space-lg);">
                <h4 style="margin-bottom: var(--space-sm);">📍 Ubicación</h4>
                <p>${escapeHtml(apt.direccion || 'No disponible')}</p>
                <p>${escapeHtml(apt.codigo_postal || '')} ${escapeHtml(apt.localidad || apt.municipio || '')}</p>
                <p><strong>${escapeHtml(apt.provincia)}</strong></p>
            </div>
            
            <div style="margin-bottom: var(--space-lg);">
                <h4 style="margin-bottom: var(--space-sm);">ℹ️ Información</h4>
                <p><strong>Capacidad:</strong> ${apt.capacidad_alojamiento || 'No especificada'} plazas</p>
                <p><strong>Accesible:</strong> ${apt.accesible ? 'Sí' : 'No'}</p>
                ${apt.categoria ? `<p><strong>Categoría:</strong> ${escapeHtml(apt.categoria)}</p>` : ''}
            </div>
            
            ${apt.telefono_1 || apt.email ? `
            <div style="margin-bottom: var(--space-lg);">
                <h4 style="margin-bottom: var(--space-sm);">📞 Contacto</h4>
                ${apt.telefono_1 ? `<p><a href="tel:${apt.telefono_1}">${apt.telefono_1}</a></p>` : ''}
                ${apt.email ? `<p><a href="mailto:${apt.email}">${apt.email}</a></p>` : ''}
                ${apt.web ? `<p><a href="${apt.web}" target="_blank" rel="noopener">Sitio web</a></p>` : ''}
            </div>
            ` : ''}
            
            ${apt.gps_latitud && apt.gps_longitud ? `
            <div>
                <h4 style="margin-bottom: var(--space-sm);">🗺️ Ubicación</h4>
                <p>
                    <a href="https://www.google.com/maps?q=${apt.gps_latitud},${apt.gps_longitud}" 
                       target="_blank" rel="noopener" class="btn btn-secondary btn-sm">
                        Ver en Google Maps →
                    </a>
                </p>
            </div>
            ` : ''}
        `;

        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    },

    clearFilters() {
        this.currentFilters = {};
        this.currentPage = 1;
        
        document.querySelectorAll('.filters select').forEach(el => el.value = '');
        document.querySelectorAll('.filters input[type="text"]').forEach(el => el.value = '');
        document.querySelectorAll('.filters input[type="checkbox"]').forEach(el => el.checked = false);
        
        const municipioSelect = document.getElementById('filtro-municipio');
        if (municipioSelect) municipioSelect.disabled = true;
        
        this.loadApartamentos();
    },

    getLoadingHTML() {
        return `
            <div class="loading" style="grid-column: 1/-1;">
                <div class="spinner"></div>
                <p>Cargando apartamentos...</p>
            </div>
        `;
    },

    getEmptyHTML() {
        return `
            <div style="grid-column: 1/-1; text-align: center; padding: var(--space-3xl);">
                <p style="font-size: 3rem; margin-bottom: var(--space-md);">🏠</p>
                <h3>No se encontraron apartamentos</h3>
                <p class="text-muted">Prueba a modificar los filtros de búsqueda</p>
            </div>
        `;
    },

    getErrorHTML(message) {
        return `
            <div class="alert alert-error" style="grid-column: 1/-1;">
                <span>Error: ${escapeHtml(message)}</span>
            </div>
        `;
    }
};

// ===================================
// GESTIÓN DE AUTENTICACIÓN
// ===================================

const AuthModule = {
    async checkSession() {
        try {
            const response = await apiRequest('auth.php?action=check');
            this.updateUI(response.logged_in, response.data);
            return response;
        } catch (error) {
            this.updateUI(false);
            return { logged_in: false };
        }
    },

    updateUI(isLoggedIn, userData = null) {
        const loginBtn = document.getElementById('btn-login');
        const userMenu = document.getElementById('user-menu');
        const userName = document.getElementById('user-name');

        if (loginBtn) loginBtn.style.display = isLoggedIn ? 'none' : 'inline-flex';
        if (userMenu) userMenu.style.display = isLoggedIn ? 'flex' : 'none';
        if (userName && userData) userName.textContent = userData.nombre;
    },

    async login(email, password) {
        try {
            const response = await apiRequest('auth.php?action=login', {
                method: 'POST',
                body: JSON.stringify({ email, password })
            });

            if (response.success) {
                showToast('¡Bienvenido!', 'success');
                this.updateUI(true, response.data);
                this.closeModal('modal-login');
                return true;
            }
        } catch (error) {
            showToast(error.message || 'Error al iniciar sesión', 'error');
            return false;
        }
    },

    async register(formData) {
        try {
            const response = await apiRequest('auth.php?action=registro', {
                method: 'POST',
                body: JSON.stringify(formData)
            });

            if (response.success) {
                showToast('Cuenta creada correctamente', 'success');
                this.closeModal('modal-registro');
                this.openModal('modal-login');
                return true;
            }
        } catch (error) {
            showToast(error.message || 'Error al crear la cuenta', 'error');
            return false;
        }
    },

    async logout() {
        try {
            await apiRequest('auth.php?action=logout');
            showToast('Sesión cerrada', 'info');
            this.updateUI(false);
            window.location.reload();
        } catch (error) {
            console.error('Error en logout:', error);
        }
    },

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
// VALIDACIÓN DE FORMULARIOS
// ===================================

const ValidacionModule = {
    validateForm(form) {
        let isValid = true;
        const inputs = form.querySelectorAll('[data-validate]');

        inputs.forEach(input => {
            if (!this.validateField(input)) {
                isValid = false;
            }
        });

        return isValid;
    },

    validateField(input) {
        const rules = input.dataset.validate.split('|');
        const value = input.value.trim();
        let error = '';

        for (const rule of rules) {
            const [ruleName, ruleValue] = rule.split(':');

            switch (ruleName) {
                case 'required':
                    if (!value) error = 'Este campo es obligatorio';
                    break;
                case 'email':
                    if (value && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) {
                        error = 'Email no válido';
                    }
                    break;
                case 'min':
                    if (value && value.length < parseInt(ruleValue)) {
                        error = `Mínimo ${ruleValue} caracteres`;
                    }
                    break;
                case 'max':
                    if (value && value.length > parseInt(ruleValue)) {
                        error = `Máximo ${ruleValue} caracteres`;
                    }
                    break;
                case 'phone':
                    if (value && !/^[0-9+\s-]{9,}$/.test(value)) {
                        error = 'Teléfono no válido';
                    }
                    break;
                case 'match':
                    const matchInput = document.querySelector(`[name="${ruleValue}"]`);
                    if (matchInput && value !== matchInput.value) {
                        error = 'Los valores no coinciden';
                    }
                    break;
            }

            if (error) break;
        }

        this.showFieldError(input, error);
        return !error;
    },

    showFieldError(input, error) {
        const prevError = input.parentNode.querySelector('.form-error');
        if (prevError) prevError.remove();

        input.classList.toggle('is-invalid', !!error);

        if (error) {
            const errorEl = document.createElement('span');
            errorEl.className = 'form-error';
            errorEl.textContent = error;
            input.parentNode.appendChild(errorEl);
        }
    },

    bindRealTimeValidation(form) {
        const inputs = form.querySelectorAll('[data-validate]');
        
        inputs.forEach(input => {
            input.addEventListener('blur', () => this.validateField(input));
            input.addEventListener('input', debounce(() => {
                if (input.classList.contains('is-invalid')) {
                    this.validateField(input);
                }
            }, 300));
        });
    }
};

// ===================================
// INICIALIZACIÓN GLOBAL
// ===================================

document.addEventListener('DOMContentLoaded', () => {
    // Verificar sesión
    AuthModule.checkSession();

    // Inicializar módulo de apartamentos si existe el grid
    if (document.getElementById('apartamentos-grid')) {
        ApartamentosModule.init();
        ApartamentosModule.loadApartamentos();
    }

    // Bindear eventos de modales
    document.querySelectorAll('[data-modal-open]').forEach(btn => {
        btn.addEventListener('click', () => {
            AuthModule.openModal(btn.dataset.modalOpen);
        });
    });

    document.querySelectorAll('[data-modal-close]').forEach(btn => {
        btn.addEventListener('click', () => {
            const modal = btn.closest('.modal-overlay');
            if (modal) modal.classList.remove('active');
            document.body.style.overflow = '';
        });
    });

    // Cerrar modal al hacer click fuera
    document.querySelectorAll('.modal-overlay').forEach(overlay => {
        overlay.addEventListener('click', (e) => {
            if (e.target === overlay) {
                overlay.classList.remove('active');
                document.body.style.overflow = '';
            }
        });
    });

    // Formulario de login
    const loginForm = document.getElementById('form-login');
    if (loginForm) {
        ValidacionModule.bindRealTimeValidation(loginForm);
        loginForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            if (ValidacionModule.validateForm(loginForm)) {
                const formData = new FormData(loginForm);
                await AuthModule.login(formData.get('email'), formData.get('password'));
            }
        });
    }

    // Formulario de registro
    const registerForm = document.getElementById('form-registro');
    if (registerForm) {
        ValidacionModule.bindRealTimeValidation(registerForm);
        registerForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            if (ValidacionModule.validateForm(registerForm)) {
                const formData = Object.fromEntries(new FormData(registerForm));
                await AuthModule.register(formData);
            }
        });
    }

    // Menú móvil
    const menuToggle = document.querySelector('.menu-toggle');
    const nav = document.querySelector('.nav');
    if (menuToggle && nav) {
        menuToggle.addEventListener('click', () => {
            nav.classList.toggle('active');
        });
    }

    // Logout
    const logoutBtn = document.getElementById('btn-logout');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', (e) => {
            e.preventDefault();
            AuthModule.logout();
        });
    }
});

// Estilos para toast notifications
const toastStyles = document.createElement('style');
toastStyles.textContent = `
    .toast {
        position: fixed;
        bottom: 20px;
        right: 20px;
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 12px 20px;
        background: var(--color-surface, #fff);
        border-radius: 8px;
        box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1);
        transform: translateY(100px);
        opacity: 0;
        transition: all 0.3s ease;
        z-index: 3000;
    }
    .toast.show {
        transform: translateY(0);
        opacity: 1;
    }
    .toast-success { border-left: 4px solid #166534; }
    .toast-error { border-left: 4px solid #991b1b; }
    .toast-warning { border-left: 4px solid #854d0e; }
    .toast-info { border-left: 4px solid #1e40af; }
    .toast-icon { font-weight: bold; }
    .toast-success .toast-icon { color: #166534; }
    .toast-error .toast-icon { color: #991b1b; }
    .toast-warning .toast-icon { color: #854d0e; }
    .toast-info .toast-icon { color: #1e40af; }
`;
document.head.appendChild(toastStyles);