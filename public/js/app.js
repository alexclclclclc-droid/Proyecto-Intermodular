/**
 * Apartamentos Turísticos de Castilla y León
 * JavaScript Principal - Comunicación asíncrona con fetch()
 */

// ===================================
// CONFIGURACIÓN Y UTILIDADES
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

// Helper para obtener la ruta correcta a index.php
function getIndexPath() {
    const path = window.location.pathname;
    const parts = path.split('/').filter(p => p);
    
    if (parts.includes('views')) {
        if (parts.includes('admin')) {
            return '../../index.php';
        }
        return '../index.php';
    }
    return './index.php';
}

// ===================================
// MAPEO DE IMÁGENES DE PROVINCIAS
// ===================================

// Función para obtener la ruta base correcta de las imágenes (dinámica)
const getImageBasePath = () => {
    const path = window.location.pathname;
    // Si estamos en views/, usar ../public/images/
    // Si estamos en raíz, usar ./public/images/
    return path.includes('/views/') ? '../public/images' : './public/images';
};

// Imágenes de monumentos para las provincias (rutas dinámicas)
const getProvinciaImages = () => {
    const basePath = getImageBasePath();
    return {
        'Ávila': `${basePath}/MurallaÁvila.webp`,
        'Burgos': `${basePath}/CatedralBurgos.webp`,
        'León': `${basePath}/CatedralLeon.webp`,
        'Palencia': `${basePath}/FromistaPalencia.webp`,
        'Salamanca': `${basePath}/UniversidadSalamanca.webp`,
        'Segovia': `${basePath}/AcueductoSegovia.webp`,
        'Soria': `${basePath}/CatedralSoria.webp`,
        'Valladolid': `${basePath}/MuseoValladolid.webp`,
        'Zamora': `${basePath}/CastilloZamora.webp`
    };
};

// Crear el objeto de imágenes al cargar
const provinciaImages = getProvinciaImages();

// Función helper para obtener imagen por provincia
function getProvinciaImage(provincia) {
    const basePath = getImageBasePath();
    return provinciaImages[provincia] || `${basePath}/default-placeholder.svg`;
}

// Función para crear elemento de imagen optimizado
function createOptimizedImage(src, alt, className = '', size = 'medium') {
    const img = document.createElement('img');
    img.src = src;
    img.alt = alt;
    img.className = className;
    img.loading = 'lazy';
    
    // Tamaños predefinidos
    const sizes = {
        small: { width: '24px', height: '24px' },
        medium: { width: '32px', height: '32px' },
        large: { width: '48px', height: '48px' }
    };
    
    if (sizes[size]) {
        img.style.width = sizes[size].width;
        img.style.height = sizes[size].height;
        img.style.objectFit = 'cover';
        img.style.borderRadius = '4px';
    }
    
    // Manejo de errores
    img.onerror = function() {
        this.src = '../public/images/default-placeholder.svg';
        console.warn(`Error cargando imagen de provincia: ${src}`);
    };
    
    return img;
}


/**
 * Realiza peticiones a la API con manejo robusto de errores
 */
async function apiRequest(endpoint, options = {}) {
    const defaultOptions = {
        headers: {
            'Content-Type': 'application/json',
        },
        timeout: 10000, // 10 segundos por defecto
        retries: 2, // 2 reintentos por defecto
        retryDelay: 1000 // 1 segundo entre reintentos
    };

    const config = { ...defaultOptions, ...options };
    const { timeout, retries, retryDelay, ...fetchOptions } = config;
    
    // Función para realizar una petición individual
    const makeRequest = async () => {
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), timeout);
        
        try {
            const response = await fetch(`${API_BASE}/${endpoint}`, {
                ...fetchOptions,
                signal: controller.signal
            });
            
            clearTimeout(timeoutId);
            
            // Verificar si la respuesta es JSON válida
            let data;
            const contentType = response.headers.get('content-type');
            if (contentType && contentType.includes('application/json')) {
                try {
                    data = await response.json();
                } catch (jsonError) {
                    throw new Error('Respuesta del servidor no es JSON válido');
                }
            } else {
                // Si no es JSON, intentar obtener texto para mejor debugging
                const text = await response.text();
                throw new Error(`Respuesta inesperada del servidor: ${text.substring(0, 100)}`);
            }
            
            if (!response.ok) {
                const errorMessage = data.error || `Error HTTP ${response.status}: ${response.statusText}`;
                const error = new Error(errorMessage);
                error.status = response.status;
                error.data = data;
                throw error;
            }
            
            return data;
            
        } catch (error) {
            clearTimeout(timeoutId);
            
            // Clasificar tipos de error para mejor manejo
            if (error.name === 'AbortError') {
                const timeoutError = new Error(`Timeout: La petición tardó más de ${timeout}ms`);
                timeoutError.type = 'timeout';
                throw timeoutError;
            }
            
            if (error instanceof TypeError && error.message.includes('fetch')) {
                const networkError = new Error('Error de red: Verifica tu conexión a internet');
                networkError.type = 'network';
                throw networkError;
            }
            
            // Re-lanzar otros errores con información adicional
            error.endpoint = endpoint;
            error.timestamp = new Date().toISOString();
            throw error;
        }
    };
    
    // Lógica de reintentos
    let lastError;
    for (let attempt = 0; attempt <= retries; attempt++) {
        try {
            const result = await makeRequest();
            
            // Si es un reintento exitoso, mostrar notificación
            if (attempt > 0) {
                console.log(`Petición exitosa después de ${attempt} reintento(s)`);
                showToast('Conexión restablecida', 'success');
            }
            
            return result;
            
        } catch (error) {
            lastError = error;
            
            // No reintentar en ciertos casos
            if (error.status === 401 || error.status === 403 || error.status === 404) {
                break;
            }
            
            // Si no es el último intento, esperar antes del siguiente
            if (attempt < retries) {
                console.log(`Reintentando petición en ${retryDelay}ms (intento ${attempt + 1}/${retries})`);
                await new Promise(resolve => setTimeout(resolve, retryDelay));
                // Incrementar delay exponencialmente
                retryDelay *= 1.5;
            }
        }
    }
    
    // Si llegamos aquí, todos los intentos fallaron
    console.error('API Error después de reintentos:', lastError);
    
    // Mostrar error apropiado al usuario
    if (lastError.type === 'timeout') {
        showToast('La petición tardó demasiado. Inténtalo de nuevo.', 'error');
    } else if (lastError.type === 'network') {
        showToast('Error de conexión. Verifica tu internet.', 'error');
    } else if (lastError.status >= 500) {
        showToast('Error del servidor. Inténtalo más tarde.', 'error');
    } else if (lastError.status === 401) {
        showToast('Sesión expirada. Inicia sesión de nuevo.', 'warning');
        // Opcional: redirigir al login
        setTimeout(() => {
            window.location.reload();
        }, 2000);
    }
    
    throw lastError;
}

/**
 * Versión simplificada de apiRequest para casos que no necesitan reintentos
 */
async function apiRequestSimple(endpoint, options = {}) {
    return apiRequest(endpoint, { ...options, retries: 0 });
}

/**
 * Versión de apiRequest optimizada para operaciones críticas
 */
async function apiRequestCritical(endpoint, options = {}) {
    return apiRequest(endpoint, { 
        ...options, 
        timeout: 15000, // Timeout más largo
        retries: 3, // Más reintentos
        retryDelay: 2000 // Delay más largo
    });
}

// ===================================
// GESTIÓN DE ESTADOS DE UI
// ===================================

/**
 * Gestiona estados de carga y UI de forma consistente
 */
const UIStateManager = {
    loadingElements: new Map(),
    
    /**
     * Muestra indicador de carga en un elemento
     */
    showLoading(element, message = 'Cargando...') {
        if (!element) return;
        
        // Guardar estado original
        if (!this.loadingElements.has(element)) {
            this.loadingElements.set(element, {
                originalContent: element.innerHTML,
                originalDisabled: element.disabled,
                originalClassName: element.className
            });
        }
        
        // Aplicar estado de carga
        if (element.tagName === 'BUTTON') {
            element.disabled = true;
            element.innerHTML = `
                <span class="loading-spinner"></span>
                ${message}
            `;
            element.classList.add('loading');
        } else {
            element.innerHTML = `
                <div class="loading-container">
                    <div class="loading-spinner"></div>
                    <span class="loading-text">${message}</span>
                </div>
            `;
            element.classList.add('loading');
        }
    },
    
    /**
     * Oculta indicador de carga y restaura estado original
     */
    hideLoading(element) {
        if (!element || !this.loadingElements.has(element)) return;
        
        const originalState = this.loadingElements.get(element);
        
        // Restaurar estado original
        element.innerHTML = originalState.originalContent;
        element.disabled = originalState.originalDisabled;
        element.className = originalState.originalClassName;
        
        // Limpiar del mapa
        this.loadingElements.delete(element);
    },
    
    /**
     * Muestra estado de error en un elemento
     */
    showError(element, message = 'Error al cargar') {
        if (!element) return;
        
        element.innerHTML = `
            <div class="error-container">
                <span class="error-icon">⚠️</span>
                <span class="error-text">${message}</span>
                <button class="btn btn-sm btn-secondary retry-btn" onclick="location.reload()">
                    Reintentar
                </button>
            </div>
        `;
        element.classList.add('error-state');
    },
    
    /**
     * Muestra estado vacío en un elemento
     */
    showEmpty(element, message = 'No hay datos disponibles', icon = '📭') {
        if (!element) return;
        
        element.innerHTML = `
            <div class="empty-container">
                <span class="empty-icon">${icon}</span>
                <span class="empty-text">${message}</span>
            </div>
        `;
        element.classList.add('empty-state');
    },
    
    /**
     * Limpia todos los estados especiales de un elemento
     */
    clearState(element) {
        if (!element) return;
        
        element.classList.remove('loading', 'error-state', 'empty-state');
        this.hideLoading(element);
    }
};

/**
 * Wrapper para operaciones asíncronas con manejo automático de UI
 */
async function withLoadingState(element, asyncOperation, loadingMessage = 'Cargando...') {
    try {
        UIStateManager.showLoading(element, loadingMessage);
        const result = await asyncOperation();
        UIStateManager.hideLoading(element);
        return result;
    } catch (error) {
        UIStateManager.hideLoading(element);
        UIStateManager.showError(element, error.message || 'Error al procesar');
        throw error;
    }
}

/**
 * Debounce mejorado con cancelación
 */
function debounceWithCancel(func, wait) {
    let timeout;
    let cancelled = false;
    
    const debounced = function executedFunction(...args) {
        if (cancelled) return;
        
        const later = () => {
            clearTimeout(timeout);
            if (!cancelled) func(...args);
        };
        
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
    
    debounced.cancel = () => {
        cancelled = true;
        clearTimeout(timeout);
    };
    
    debounced.flush = () => {
        if (timeout) {
            clearTimeout(timeout);
            func();
        }
    };
    
    return debounced;
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
        
        const provinciaImage = getProvinciaImage(apt.provincia);
        
        return `
            <article class="card" data-id="${apt.id}">
                <div class="card-image">
                    <img src="${provinciaImage}" 
                         alt="Monumento de ${apt.provincia}" 
                         class="card-image-monument"
                         loading="lazy"
                         onerror="this.src='../public/images/default-placeholder.svg'">
                    ${apt.plazas > 6 ? '<span class="card-badge">Grande</span>' : ''}
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
                            ${apt.plazas || '?'} plazas
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
                <p><strong>Capacidad:</strong> ${apt.plazas || 'No especificada'} plazas</p>
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

        // Mostrar/ocultar botón de reserva según estado de autenticación
        const btnReservar = document.getElementById('btn-reservar-apartamento');
        if (btnReservar) {
            btnReservar.style.display = 'inline-flex';
            btnReservar.onclick = () => ReservaModule.showReservaForm(apt);
        }

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
        // Elementos móviles
        const loginBtn = document.getElementById('btn-login');
        const userMenu = document.getElementById('user-menu');
        const userName = document.getElementById('user-name');
        
        // Elementos desktop
        const loginBtnDesktop = document.getElementById('btn-login-desktop');
        const userMenuDesktop = document.getElementById('user-menu-desktop');
        const userNameDesktop = document.getElementById('user-name-desktop');

        // Actualizar elementos móviles
        if (loginBtn) loginBtn.style.display = isLoggedIn ? 'none' : 'inline-flex';
        if (userMenu) userMenu.style.display = isLoggedIn ? 'flex' : 'none';
        if (userName && userData) userName.textContent = userData.nombre;
        
        // Actualizar elementos desktop
        if (loginBtnDesktop) loginBtnDesktop.style.display = isLoggedIn ? 'none' : 'inline-flex';
        if (userMenuDesktop) userMenuDesktop.style.display = isLoggedIn ? 'flex' : 'none';
        if (userNameDesktop && userData) userNameDesktop.textContent = userData.nombre;
    },

    async login(email, password) {
        try {
            const response = await apiRequest('auth.php?action=login', {
                method: 'POST',
                body: JSON.stringify({ email, password })
            });

            if (response.success) {
                showToast('¡Bienvenido!', 'success');
                this.closeModal('modal-login');
                // Recargar la página para actualizar el menú con los enlaces de admin/reservas
                setTimeout(() => {
                    window.location.reload();
                }, 500);
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
                showToast('Cuenta creada correctamente. Iniciando sesión...', 'success');
                this.closeModal('modal-registro');
                // Hacer login automático
                await this.login(formData.email, formData.password);
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
            // Redirigir a index.php usando la función helper
            window.location.href = getIndexPath();
        } catch (error) {
            console.error('Error en logout:', error);
            // Incluso si hay error, redirigir a index
            window.location.href = getIndexPath();
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
// GESTIÓN DE RESERVAS
// ===================================

const ReservaModule = {
    currentApartamento: null,
    isCheckingAvailability: false,

    async showReservaForm(apartamento) {
        // Verificar autenticación
        const authStatus = await AuthModule.checkSession();
        if (!authStatus.logged_in) {
            AuthModule.closeModal('modal-detalle');
            AuthModule.openModal('modal-login');
            showToast('Debes iniciar sesión para reservar', 'warning');
            return;
        }

        this.currentApartamento = apartamento;
        
        // Actualizar información del apartamento en el modal
        const infoContainer = document.getElementById('reserva-apartamento-info');
        if (infoContainer) {
            infoContainer.innerHTML = `
                <h4>${escapeHtml(apartamento.nombre)}</h4>
                <p>📍 ${escapeHtml(apartamento.municipio || '')}, ${escapeHtml(apartamento.provincia)}</p>
                <p>👥 Capacidad: ${apartamento.plazas || '?'} plazas</p>
            `;
        }

        // Establecer ID del apartamento
        const idInput = document.getElementById('reserva-id-apartamento');
        if (idInput) {
            idInput.value = apartamento.id;
        }

        // Configurar límite de huéspedes
        this.updateGuestOptions(apartamento.plazas);

        // Limpiar formulario
        this.resetForm();

        // Cerrar modal de detalles y abrir modal de reserva
        AuthModule.closeModal('modal-detalle');
        AuthModule.openModal('modal-reserva');
    },

    updateGuestOptions(maxCapacity) {
        const select = document.getElementById('reserva-num-huespedes');
        if (!select || !maxCapacity) return;

        // Limpiar opciones existentes
        select.innerHTML = '<option value="">Seleccionar...</option>';

        // Agregar opciones hasta la capacidad máxima
        for (let i = 1; i <= Math.min(maxCapacity, 12); i++) {
            const option = document.createElement('option');
            option.value = i;
            option.textContent = `${i} huésped${i > 1 ? 'es' : ''}`;
            select.appendChild(option);
        }
    },

    resetForm() {
        const form = document.getElementById('form-reserva');
        if (form) {
            form.reset();
        }

        // Limpiar mensajes de disponibilidad
        this.hideAvailabilityInfo();

        // Deshabilitar botón de confirmar
        const btnConfirmar = document.getElementById('btn-confirmar-reserva');
        if (btnConfirmar) {
            btnConfirmar.disabled = true;
        }

        // Establecer fechas mínimas
        const today = new Date().toISOString().split('T')[0];
        const tomorrow = new Date(Date.now() + 24 * 60 * 60 * 1000).toISOString().split('T')[0];
        
        const fechaEntrada = document.getElementById('reserva-fecha-entrada');
        const fechaSalida = document.getElementById('reserva-fecha-salida');
        
        if (fechaEntrada) {
            fechaEntrada.min = today;
        }
        if (fechaSalida) {
            fechaSalida.min = tomorrow;
        }
    },

    async checkDisponibilidad(apartamentoId, fechaEntrada, fechaSalida) {
        if (this.isCheckingAvailability) return;
        
        if (!apartamentoId || !fechaEntrada || !fechaSalida) {
            this.hideAvailabilityInfo();
            return;
        }

        // Validar que la fecha de salida sea posterior a la entrada
        if (new Date(fechaSalida) <= new Date(fechaEntrada)) {
            this.showAvailabilityInfo('La fecha de salida debe ser posterior a la fecha de entrada', 'error');
            return;
        }

        this.isCheckingAvailability = true;
        this.showAvailabilityInfo('Verificando disponibilidad...', 'checking');

        try {
            const response = await apiRequest(
                `reservas.php?action=disponibilidad&id_apartamento=${apartamentoId}&fecha_entrada=${fechaEntrada}&fecha_salida=${fechaSalida}`
            );

            if (response.success) {
                if (response.disponible) {
                    this.showAvailabilityInfo('✓ Apartamento disponible para las fechas seleccionadas', 'success');
                    this.enableConfirmButton();
                } else {
                    this.showAvailabilityInfo('✕ El apartamento no está disponible en estas fechas. Por favor, selecciona otras fechas.', 'error');
                    this.disableConfirmButton();
                    // Sugerir fechas alternativas
                    this.suggestAlternativeDates(apartamentoId);
                }
            } else {
                throw new Error(response.error || 'Error al verificar disponibilidad');
            }
        } catch (error) {
            console.error('Error checking availability:', error);
            this.showAvailabilityInfo('Error al verificar disponibilidad. Inténtalo de nuevo.', 'error');
            this.disableConfirmButton();
        } finally {
            this.isCheckingAvailability = false;
        }
    },

    showAvailabilityInfo(message, type) {
        const container = document.getElementById('disponibilidad-info');
        if (!container) return;

        container.textContent = message;
        container.className = `alert disponibilidad-info ${type}`;
        container.style.display = 'block';
    },

    hideAvailabilityInfo() {
        const container = document.getElementById('disponibilidad-info');
        if (container) {
            container.style.display = 'none';
        }
    },

    enableConfirmButton() {
        const btn = document.getElementById('btn-confirmar-reserva');
        if (btn) {
            btn.disabled = false;
        }
    },

    disableConfirmButton() {
        const btn = document.getElementById('btn-confirmar-reserva');
        if (btn) {
            btn.disabled = true;
        }
    },

    async submitReserva(formData) {
        const btn = document.getElementById('btn-confirmar-reserva');
        if (!btn) return;

        // Mostrar estado de carga
        btn.classList.add('loading');
        btn.disabled = true;

        try {
            const response = await apiRequest('reservas.php?action=crear', {
                method: 'POST',
                body: JSON.stringify(formData)
            });

            if (response.success) {
                showToast('¡Reserva creada correctamente!', 'success');
                AuthModule.closeModal('modal-reserva');
                
                // Redirigir a mis reservas después de un breve delay
                setTimeout(() => {
                    window.location.href = './mis-reservas.php';
                }, 1500);
            } else {
                if (response.errors && Array.isArray(response.errors)) {
                    response.errors.forEach(error => showToast(error, 'error'));
                } else {
                    showToast(response.error || 'Error al crear la reserva', 'error');
                }
            }
        } catch (error) {
            console.error('Error submitting reservation:', error);
            showToast(error.message || 'Error al procesar la reserva', 'error');
        } finally {
            // Quitar estado de carga
            btn.classList.remove('loading');
            btn.disabled = false;
        }
    },

    handleReservaResponse(response) {
        if (response.success) {
            showToast('¡Reserva creada correctamente!', 'success');
            AuthModule.closeModal('modal-reserva');
            
            // Redirigir a mis reservas
            setTimeout(() => {
                window.location.href = './mis-reservas.php';
            }, 1500);
        } else {
            if (response.errors && Array.isArray(response.errors)) {
                response.errors.forEach(error => showToast(error, 'error'));
            } else {
                showToast(response.error || 'Error al crear la reserva', 'error');
            }
        }
    },

    async suggestAlternativeDates(apartamentoId) {
        try {
            // Obtener fechas ocupadas para sugerir alternativas
            const response = await apiRequest(`reservas.php?action=fechas_ocupadas&id_apartamento=${apartamentoId}`);
            
            if (response.success && response.data) {
                // Lógica simple para sugerir fechas alternativas
                const today = new Date();
                const suggestions = [];
                
                // Sugerir próximas 3 semanas disponibles
                for (let i = 1; i <= 21; i += 7) {
                    const startDate = new Date(today.getTime() + i * 24 * 60 * 60 * 1000);
                    const endDate = new Date(startDate.getTime() + 3 * 24 * 60 * 60 * 1000);
                    
                    const startStr = startDate.toISOString().split('T')[0];
                    const endStr = endDate.toISOString().split('T')[0];
                    
                    // Verificar si estas fechas están libres
                    const isOccupied = response.data.some(ocupada => {
                        return (startStr >= ocupada.fecha_entrada && startStr < ocupada.fecha_salida) ||
                               (endStr > ocupada.fecha_entrada && endStr <= ocupada.fecha_salida) ||
                               (startStr <= ocupada.fecha_entrada && endStr >= ocupada.fecha_salida);
                    });
                    
                    if (!isOccupied) {
                        suggestions.push({
                            entrada: startStr,
                            salida: endStr,
                            formatted: `${formatDate(startStr)} - ${formatDate(endStr)}`
                        });
                    }
                    
                    if (suggestions.length >= 2) break;
                }
                
                if (suggestions.length > 0) {
                    const suggestionHtml = suggestions.map(s => 
                        `<button class="btn btn-ghost btn-sm" onclick="ReservaModule.applySuggestedDates('${s.entrada}', '${s.salida}')" style="margin: 2px;">
                            ${s.formatted}
                        </button>`
                    ).join('');
                    
                    this.showAvailabilityInfo(
                        `✕ No disponible en estas fechas. Prueba estas alternativas: ${suggestionHtml}`, 
                        'error'
                    );
                }
            }
        } catch (error) {
            console.error('Error getting alternative dates:', error);
        }
    },

    applySuggestedDates(fechaEntrada, fechaSalida) {
        const entradaInput = document.getElementById('reserva-fecha-entrada');
        const salidaInput = document.getElementById('reserva-fecha-salida');
        
        if (entradaInput && salidaInput) {
            entradaInput.value = fechaEntrada;
            salidaInput.value = fechaSalida;
            
            // Verificar disponibilidad de las nuevas fechas
            if (this.currentApartamento) {
                this.checkDisponibilidad(this.currentApartamento.id, fechaEntrada, fechaSalida);
            }
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
                    const form = input.closest('form');
                    const matchInput = form ? form.querySelector(`[name="${ruleValue}"]`) : document.querySelector(`[name="${ruleValue}"]`);
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
        const urlParams = new URLSearchParams(window.location.search);
        if (!urlParams.has('provincia') && !urlParams.has('municipio')) {
            ApartamentosModule.loadApartamentos();
        }
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

    // Formulario de reserva
    const reservaForm = document.getElementById('form-reserva');
    if (reservaForm) {
        ValidacionModule.bindRealTimeValidation(reservaForm);
        
        // Event listeners para verificación de disponibilidad
        const fechaEntrada = document.getElementById('reserva-fecha-entrada');
        const fechaSalida = document.getElementById('reserva-fecha-salida');
        const apartamentoId = document.getElementById('reserva-id-apartamento');

        const checkAvailability = debounce(() => {
            if (apartamentoId && fechaEntrada && fechaSalida) {
                ReservaModule.checkDisponibilidad(
                    apartamentoId.value,
                    fechaEntrada.value,
                    fechaSalida.value
                );
            }
        }, 500);

        if (fechaEntrada) {
            fechaEntrada.addEventListener('change', () => {
                // Actualizar fecha mínima de salida
                if (fechaSalida && fechaEntrada.value) {
                    const nextDay = new Date(fechaEntrada.value);
                    nextDay.setDate(nextDay.getDate() + 1);
                    fechaSalida.min = nextDay.toISOString().split('T')[0];
                    
                    // Si la fecha de salida es anterior, limpiarla
                    if (fechaSalida.value && new Date(fechaSalida.value) <= new Date(fechaEntrada.value)) {
                        fechaSalida.value = '';
                    }
                }
                checkAvailability();
            });
        }

        if (fechaSalida) {
            fechaSalida.addEventListener('change', checkAvailability);
        }

        // Submit del formulario
        reservaForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            if (ValidacionModule.validateForm(reservaForm)) {
                const formData = Object.fromEntries(new FormData(reservaForm));
                await ReservaModule.submitReserva(formData);
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

    // Logout - manejar tanto móvil como desktop
    const logoutBtn = document.getElementById('btn-logout');
    const logoutBtnDesktop = document.getElementById('btn-logout-desktop');
    
    if (logoutBtn) {
        logoutBtn.addEventListener('click', (e) => {
            e.preventDefault();
            AuthModule.logout();
        });
    }
    
    if (logoutBtnDesktop) {
        logoutBtnDesktop.addEventListener('click', (e) => {
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
// ===================================
// OPTIMIZACIONES DE RENDIMIENTO
// ===================================

/**
 * Módulo de optimización de rendimiento
 */
const PerformanceOptimizer = {
    // Cache para peticiones API
    apiCache: new Map(),
    cacheTimeout: 5 * 60 * 1000, // 5 minutos
    
    // Intersection Observer para lazy loading
    intersectionObserver: null,
    
    // Debounced functions cache
    debouncedFunctions: new Map(),
    
    /**
     * Inicializar optimizaciones
     */
    init() {
        this.setupIntersectionObserver();
        this.setupPerformanceMonitoring();
        this.optimizeImages();
        this.preloadCriticalResources();
    },
    
    /**
     * Cache para peticiones API con TTL
     */
    async cachedApiRequest(endpoint, options = {}) {
        const cacheKey = `${endpoint}_${JSON.stringify(options)}`;
        const cached = this.apiCache.get(cacheKey);
        
        if (cached && Date.now() - cached.timestamp < this.cacheTimeout) {
            return cached.data;
        }
        
        try {
            const data = await apiRequest(endpoint, options);
            this.apiCache.set(cacheKey, {
                data,
                timestamp: Date.now()
            });
            return data;
        } catch (error) {
            // Si hay error y tenemos cache, usar cache aunque esté expirado
            if (cached) {
                console.warn('Using expired cache due to API error');
                return cached.data;
            }
            throw error;
        }
    },
    
    /**
     * Limpiar cache expirado
     */
    cleanExpiredCache() {
        const now = Date.now();
        for (const [key, value] of this.apiCache.entries()) {
            if (now - value.timestamp > this.cacheTimeout) {
                this.apiCache.delete(key);
            }
        }
    },
    
    /**
     * Configurar Intersection Observer para lazy loading
     */
    setupIntersectionObserver() {
        if (!('IntersectionObserver' in window)) return;
        
        this.intersectionObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const element = entry.target;
                    element.classList.add('loaded');
                    
                    // Si es una imagen, cargar src desde data-src
                    if (element.tagName === 'IMG' && element.dataset.src) {
                        element.src = element.dataset.src;
                        element.removeAttribute('data-src');
                    }
                    
                    // Si es un componente, ejecutar función de carga
                    if (element.dataset.lazyLoad) {
                        const loadFunction = window[element.dataset.lazyLoad];
                        if (typeof loadFunction === 'function') {
                            loadFunction(element);
                        }
                    }
                    
                    this.intersectionObserver.unobserve(element);
                }
            });
        }, {
            rootMargin: '50px',
            threshold: 0.1
        });
        
        // Observar elementos lazy-load existentes
        document.querySelectorAll('.lazy-load').forEach(el => {
            this.intersectionObserver.observe(el);
        });
    },
    
    /**
     * Observar nuevo elemento para lazy loading
     */
    observeElement(element) {
        if (this.intersectionObserver && element) {
            element.classList.add('lazy-load');
            this.intersectionObserver.observe(element);
        }
    },
    
    /**
     * Configurar monitoreo de rendimiento
     */
    setupPerformanceMonitoring() {
        if (!('PerformanceObserver' in window)) return;
        
        // Monitorear métricas de rendimiento
        try {
            const observer = new PerformanceObserver((list) => {
                list.getEntries().forEach(entry => {
                    if (entry.entryType === 'navigation') {
                        console.log('Page Load Time:', entry.loadEventEnd - entry.loadEventStart);
                    } else if (entry.entryType === 'paint') {
                        console.log(`${entry.name}:`, entry.startTime);
                    }
                });
            });
            
            observer.observe({ entryTypes: ['navigation', 'paint'] });
        } catch (e) {
            console.warn('Performance monitoring not available');
        }
    },
    
    /**
     * Optimizar imágenes
     */
    optimizeImages() {
        // Lazy loading para imágenes
        document.querySelectorAll('img[data-src]').forEach(img => {
            this.observeElement(img);
        });
        
        // Preload para imágenes críticas
        document.querySelectorAll('img[data-preload]').forEach(img => {
            const link = document.createElement('link');
            link.rel = 'preload';
            link.as = 'image';
            link.href = img.src || img.dataset.src;
            document.head.appendChild(link);
        });
    },
    
    /**
     * Precargar recursos críticos
     */
    preloadCriticalResources() {
        // Precargar datos críticos
        const criticalEndpoints = ['apartamentos.php?action=provincias'];
        
        criticalEndpoints.forEach(endpoint => {
            // Precargar en idle time
            if ('requestIdleCallback' in window) {
                requestIdleCallback(() => {
                    this.cachedApiRequest(endpoint).catch(() => {
                        // Ignorar errores en precarga
                    });
                });
            }
        });
    },
    
    /**
     * Debounce optimizado con cache
     */
    getDebounced(key, func, wait) {
        if (!this.debouncedFunctions.has(key)) {
            this.debouncedFunctions.set(key, debounceWithCancel(func, wait));
        }
        return this.debouncedFunctions.get(key);
    },
    
    /**
     * Throttle para eventos de scroll/resize
     */
    throttle(func, limit) {
        let inThrottle;
        return function() {
            const args = arguments;
            const context = this;
            if (!inThrottle) {
                func.apply(context, args);
                inThrottle = true;
                setTimeout(() => inThrottle = false, limit);
            }
        };
    },
    
    /**
     * Optimizar rendimiento del mapa
     */
    optimizeMapPerformance(map) {
        if (!map) return;
        
        // Throttle para eventos de mapa
        const throttledMoveEnd = this.throttle(() => {
            // Lógica para cuando el mapa deja de moverse
            console.log('Map movement ended');
        }, 300);
        
        map.on('moveend', throttledMoveEnd);
        
        // Optimizar clustering
        if (window.markerCluster) {
            // Configurar clustering para mejor rendimiento
            window.markerCluster.options.disableClusteringAtZoom = 15;
            window.markerCluster.options.maxClusterRadius = 40;
        }
    },
    
    /**
     * Limpiar recursos no utilizados
     */
    cleanup() {
        this.cleanExpiredCache();
        
        // Limpiar debounced functions no utilizadas
        this.debouncedFunctions.clear();
        
        // Desconectar observers
        if (this.intersectionObserver) {
            this.intersectionObserver.disconnect();
        }
    }
};

/**
 * Utilidades de rendimiento
 */
const PerfUtils = {
    /**
     * Medir tiempo de ejecución de una función
     */
    async measureTime(name, asyncFn) {
        const start = performance.now();
        try {
            const result = await asyncFn();
            const end = performance.now();
            console.log(`${name} took ${end - start} milliseconds`);
            return result;
        } catch (error) {
            const end = performance.now();
            console.log(`${name} failed after ${end - start} milliseconds`);
            throw error;
        }
    },
    
    /**
     * Batch de operaciones DOM
     */
    batchDOMUpdates(updates) {
        return new Promise(resolve => {
            requestAnimationFrame(() => {
                updates.forEach(update => update());
                resolve();
            });
        });
    },
    
    /**
     * Verificar si el dispositivo tiene recursos limitados
     */
    isLowEndDevice() {
        // Heurísticas para detectar dispositivos de gama baja
        const connection = navigator.connection || navigator.mozConnection || navigator.webkitConnection;
        const memory = navigator.deviceMemory;
        const cores = navigator.hardwareConcurrency;
        
        return (
            (memory && memory < 4) ||
            (cores && cores < 4) ||
            (connection && (connection.effectiveType === 'slow-2g' || connection.effectiveType === '2g'))
        );
    },
    
    /**
     * Configuración adaptativa basada en el dispositivo
     */
    getAdaptiveConfig() {
        const isLowEnd = this.isLowEndDevice();
        
        return {
            // Reducir animaciones en dispositivos de gama baja
            enableAnimations: !isLowEnd,
            // Reducir número de elementos mostrados
            itemsPerPage: isLowEnd ? 6 : 12,
            // Reducir calidad de imágenes
            imageQuality: isLowEnd ? 'low' : 'high',
            // Reducir frecuencia de actualizaciones
            updateInterval: isLowEnd ? 2000 : 1000,
            // Configuración de mapa
            mapMaxZoom: isLowEnd ? 16 : 18,
            clusterRadius: isLowEnd ? 60 : 40
        };
    }
};

// Inicializar optimizaciones cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', () => {
    PerformanceOptimizer.init();
    
    // Limpiar cache periódicamente
    setInterval(() => {
        PerformanceOptimizer.cleanExpiredCache();
    }, 10 * 60 * 1000); // Cada 10 minutos
    
    // Cleanup al cerrar la página
    window.addEventListener('beforeunload', () => {
        PerformanceOptimizer.cleanup();
    });
});

// Exponer utilidades globalmente para uso en otros módulos
window.PerformanceOptimizer = PerformanceOptimizer;
window.PerfUtils = PerfUtils;