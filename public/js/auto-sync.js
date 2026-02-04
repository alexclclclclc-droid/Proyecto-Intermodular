/**
 * Sistema de Sincronización Automática en JavaScript
 * Se ejecuta en segundo plano para mantener los datos actualizados
 */

class AutoSyncManager {
    constructor() {
        this.isRunning = false;
        this.checkInterval = 30 * 60 * 1000; // 30 minutos (verificar más frecuentemente cerca de la hora de sync)
        this.retryDelay = 30 * 1000; // 30 segundos
        this.maxRetries = 3;
        
        this.init();
    }
    
    init() {
        // Iniciar verificación periódica
        this.startPeriodicCheck();
        
        // Verificar al cargar la página
        setTimeout(() => this.checkAndSync(), 2000);
        
        // Verificar cuando la página vuelve a estar visible
        document.addEventListener('visibilitychange', () => {
            if (!document.hidden) {
                setTimeout(() => this.checkAndSync(), 1000);
            }
        });
    }
    
    startPeriodicCheck() {
        setInterval(() => {
            if (!document.hidden && !this.isRunning) {
                // Verificar más frecuentemente cerca de la hora de sincronización (22:30)
                const now = new Date();
                const currentHour = now.getHours();
                const currentMinute = now.getMinutes();
                
                // Entre las 22:00 y 23:30, verificar cada 5 minutos
                if (currentHour === 22 || (currentHour === 23 && currentMinute <= 30)) {
                    this.checkAndSync();
                } else {
                    // Resto del día, verificar cada 30 minutos
                    this.checkAndSync();
                }
            }
        }, this.checkInterval);
        
        // Verificación adicional cada 5 minutos durante la ventana de sincronización
        setInterval(() => {
            if (!document.hidden && !this.isRunning) {
                const now = new Date();
                const currentHour = now.getHours();
                const currentMinute = now.getMinutes();
                
                // Solo durante la ventana de sincronización
                if (currentHour === 22 || (currentHour === 23 && currentMinute <= 30)) {
                    this.checkAndSync();
                }
            }
        }, 5 * 60 * 1000); // 5 minutos
    }
    
    async checkAndSync() {
        if (this.isRunning) return;
        
        try {
            this.isRunning = true;
            
            // Verificar estado
            const status = await this.getStatus();
            
            if (status.needs_sync && !status.is_locked) {
                console.log('🔄 Iniciando sincronización automática...');
                await this.executeSync();
            }
            
        } catch (error) {
            console.warn('Error en verificación de sincronización:', error);
        } finally {
            this.isRunning = false;
        }
    }
    
    async getStatus() {
        const response = await fetch('/api/auto_sync_endpoint.php?action=status');
        const data = await response.json();
        
        if (!data.success) {
            throw new Error(data.error || 'Error obteniendo estado');
        }
        
        return data.data;
    }
    
    async executeSync(retryCount = 0) {
        try {
            const response = await fetch('/api/auto_sync_endpoint.php?action=execute', {
                method: 'POST'
            });
            
            const data = await response.json();
            
            if (!data.success) {
                throw new Error(data.error || 'Error ejecutando sincronización');
            }
            
            const result = data.data;
            
            if (result.skipped) {
                console.log('ℹ️ Sincronización no necesaria');
                return;
            }
            
            if (result.success) {
                const syncResult = result.sync_result;
                if (syncResult.procesados > 0) {
                    console.log(`✅ Sincronización completada: ${syncResult.nuevos} nuevos, ${syncResult.actualizados} actualizados`);
                    
                    // Mostrar notificación discreta si hay cambios significativos
                    if (syncResult.nuevos > 0 || syncResult.actualizados > 10) {
                        this.showNotification(`Datos actualizados: ${syncResult.nuevos} nuevos apartamentos`);
                    }
                } else {
                    console.log('ℹ️ Sincronización completada - Sin cambios');
                }
            } else {
                throw new Error('Sincronización falló');
            }
            
        } catch (error) {
            console.warn(`Error en sincronización (intento ${retryCount + 1}):`, error);
            
            if (retryCount < this.maxRetries) {
                setTimeout(() => {
                    this.executeSync(retryCount + 1);
                }, this.retryDelay * (retryCount + 1));
            }
        }
    }
    
    showNotification(message) {
        // Solo mostrar si hay una función de toast disponible
        if (typeof showToast === 'function') {
            showToast(message, 'info');
        } else {
            // Crear notificación simple
            const notification = document.createElement('div');
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: #007bff;
                color: white;
                padding: 12px 20px;
                border-radius: 6px;
                font-size: 14px;
                z-index: 10000;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                transition: opacity 0.3s ease;
            `;
            notification.textContent = message;
            
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.style.opacity = '0';
                setTimeout(() => notification.remove(), 300);
            }, 4000);
        }
    }
    
    // Método público para forzar sincronización (solo para admins)
    async forceSync() {
        if (this.isRunning) return false;
        
        try {
            this.isRunning = true;
            console.log('🔄 Forzando sincronización...');
            
            const response = await fetch('/api/auto_sync_endpoint.php?action=force', {
                method: 'POST'
            });
            
            const data = await response.json();
            
            if (data.success) {
                console.log('✅ Sincronización forzada completada');
                return true;
            } else {
                throw new Error(data.error || 'Error forzando sincronización');
            }
            
        } catch (error) {
            console.error('Error forzando sincronización:', error);
            return false;
        } finally {
            this.isRunning = false;
        }
    }
}

// Inicializar automáticamente cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', () => {
    // Solo inicializar si no estamos en el panel de admin (para evitar conflictos)
    if (!document.querySelector('.admin-panel')) {
        window.autoSyncManager = new AutoSyncManager();
        
        // Agregar función global para forzar sincronización (debugging)
        window.forceSync = () => {
            if (window.autoSyncManager) {
                return window.autoSyncManager.forceSync();
            }
        };
    }
});