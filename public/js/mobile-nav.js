/**
 * Mobile Navigation Handler
 * Maneja la navegación móvil y mejoras de UX para dispositivos táctiles
 */

class MobileNavigation {
    constructor() {
        this.menuToggle = null;
        this.nav = null;
        this.isOpen = false;
        
        this.init();
    }
    
    init() {
        // Esperar a que el DOM esté listo
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => this.setup());
        } else {
            this.setup();
        }
    }
    
    setup() {
        this.menuToggle = document.querySelector('.menu-toggle');
        this.nav = document.querySelector('.nav');
        
        if (!this.menuToggle || !this.nav) {
            return; // No hay navegación móvil en esta página
        }
        
        this.bindEvents();
        this.setupAccessibility();
    }
    
    bindEvents() {
        // Toggle del menú
        this.menuToggle.addEventListener('click', (e) => {
            e.preventDefault();
            this.toggleMenu();
        });
        
        // Cerrar menú al hacer clic en un enlace
        const navLinks = this.nav.querySelectorAll('.nav-link');
        navLinks.forEach(link => {
            link.addEventListener('click', () => {
                this.closeMenu();
            });
        });
        
        // Cerrar menú al hacer clic fuera
        document.addEventListener('click', (e) => {
            if (this.isOpen && 
                !this.nav.contains(e.target) && 
                !this.menuToggle.contains(e.target)) {
                this.closeMenu();
            }
        });
        
        // Cerrar menú con Escape
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.isOpen) {
                this.closeMenu();
                this.menuToggle.focus();
            }
        });
        
        // Manejar cambios de tamaño de ventana
        window.addEventListener('resize', () => {
            if (window.innerWidth >= 768 && this.isOpen) {
                this.closeMenu();
            }
        });
    }
    
    setupAccessibility() {
        // Configurar ARIA attributes
        this.menuToggle.setAttribute('aria-expanded', 'false');
        this.menuToggle.setAttribute('aria-controls', 'mobile-nav');
        this.menuToggle.setAttribute('aria-label', 'Abrir menú de navegación');
        
        this.nav.setAttribute('id', 'mobile-nav');
        this.nav.setAttribute('aria-hidden', 'true');
    }
    
    toggleMenu() {
        if (this.isOpen) {
            this.closeMenu();
        } else {
            this.openMenu();
        }
    }
    
    openMenu() {
        this.isOpen = true;
        this.nav.classList.add('active');
        this.menuToggle.classList.add('active');
        
        // Actualizar ARIA attributes
        this.menuToggle.setAttribute('aria-expanded', 'true');
        this.menuToggle.setAttribute('aria-label', 'Cerrar menú de navegación');
        this.nav.setAttribute('aria-hidden', 'false');
        
        // Enfocar el primer enlace del menú
        const firstLink = this.nav.querySelector('.nav-link');
        if (firstLink) {
            setTimeout(() => firstLink.focus(), 100);
        }
        
        // Prevenir scroll del body
        document.body.style.overflow = 'hidden';
    }
    
    closeMenu() {
        this.isOpen = false;
        this.nav.classList.remove('active');
        this.menuToggle.classList.remove('active');
        
        // Actualizar ARIA attributes
        this.menuToggle.setAttribute('aria-expanded', 'false');
        this.menuToggle.setAttribute('aria-label', 'Abrir menú de navegación');
        this.nav.setAttribute('aria-hidden', 'true');
        
        // Restaurar scroll del body
        document.body.style.overflow = '';
    }
}

// Inicializar navegación móvil
new MobileNavigation();

/**
 * Utilidades adicionales para dispositivos móviles
 */

// Detectar si es un dispositivo táctil
const isTouchDevice = () => {
    return 'ontouchstart' in window || navigator.maxTouchPoints > 0;
};

// Mejorar el scroll en iOS
if (isTouchDevice()) {
    document.addEventListener('DOMContentLoaded', () => {
        // Agregar clase para dispositivos táctiles
        document.body.classList.add('touch-device');
        
        // Mejorar scroll en elementos con overflow
        const scrollableElements = document.querySelectorAll('.modal-body, .admin-table-container');
        scrollableElements.forEach(element => {
            element.style.webkitOverflowScrolling = 'touch';
        });
    });
}

// Prevenir zoom en inputs en iOS
if (/iPad|iPhone|iPod/.test(navigator.userAgent)) {
    document.addEventListener('DOMContentLoaded', () => {
        const inputs = document.querySelectorAll('input, select, textarea');
        inputs.forEach(input => {
            if (input.style.fontSize === '' || parseFloat(input.style.fontSize) < 16) {
                input.style.fontSize = '16px';
            }
        });
    });
}

// Mejorar performance en dispositivos móviles
if (window.innerWidth <= 768) {
    // Reducir animaciones en dispositivos móviles lentos
    const reducedMotionQuery = window.matchMedia('(prefers-reduced-motion: reduce)');
    
    if (reducedMotionQuery.matches) {
        document.documentElement.style.setProperty('--transition-fast', '0ms');
        document.documentElement.style.setProperty('--transition-base', '0ms');
        document.documentElement.style.setProperty('--transition-slow', '0ms');
    }
}

// Exportar para uso global si es necesario
window.MobileNavigation = MobileNavigation;
window.isTouchDevice = isTouchDevice;