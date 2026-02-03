# 🔄 Sistema de Sincronización Automática

Este documento describe el sistema de sincronización automática implementado para mantener los datos de apartamentos actualizados sin intervención manual.

## 📋 Características

### ✅ Sincronización Automática
- **JavaScript en tiempo real**: Verifica cada 5 minutos si es necesario sincronizar
- **Intervalo inteligente**: Solo sincroniza si han pasado más de 1 hora desde la última vez
- **Ejecución en segundo plano**: No bloquea la navegación del usuario
- **Protección contra duplicados**: Sistema de locks para evitar ejecuciones simultáneas

### ✅ Panel de Control Admin
- **Estado en tiempo real**: Visualización del estado de sincronización
- **Forzar sincronización**: Opción para ejecutar sincronización inmediata
- **Logs detallados**: Historial de todas las sincronizaciones
- **Estadísticas completas**: Apartamentos procesados, nuevos, actualizados, errores

### ✅ Múltiples Métodos de Ejecución
1. **JavaScript automático** (Recomendado)
2. **Panel de admin manual**
3. **Cron job opcional**
4. **Webhook/URL externa**

## 🚀 Funcionamiento

### Flujo Automático
1. **Carga de página**: El script JavaScript se inicia automáticamente
2. **Verificación periódica**: Cada 5 minutos verifica si es necesario sincronizar
3. **Condiciones para sincronizar**:
   - Han pasado más de 1 hora desde la última sincronización
   - No hay otra sincronización en curso
   - La página está visible (no en segundo plano)
4. **Ejecución silenciosa**: Se ejecuta sin interrumpir al usuario
5. **Notificaciones discretas**: Solo muestra notificaciones si hay cambios significativos

### Protecciones Implementadas
- **Sistema de locks**: Evita ejecuciones simultáneas
- **Timeouts**: Los locks antiguos se eliminan automáticamente
- **Reintentos**: Sistema de reintentos con delay exponencial
- **Logs rotativos**: Los logs se mantienen con tamaño controlado

## 📁 Archivos del Sistema

### Archivos Principales
- `utils/auto_sync.php` - Clase principal de sincronización automática
- `api/auto_sync_endpoint.php` - API REST para control de sincronización
- `public/js/auto-sync.js` - Cliente JavaScript para sincronización automática
- `install_cron.php` - Script de instalación y configuración

### Archivos de Estado
- `temp/sync.lock` - Archivo de lock para evitar ejecuciones simultáneas
- `temp/last_sync.txt` - Timestamp de la última sincronización
- `temp/auto_sync.log` - Log de todas las sincronizaciones automáticas

## 🔧 Configuración

### Parámetros Configurables
```php
// En utils/auto_sync.php
private const SYNC_INTERVAL = 3600; // 1 hora en segundos
```

```javascript
// En public/js/auto-sync.js
this.checkInterval = 5 * 60 * 1000; // 5 minutos
this.retryDelay = 30 * 1000; // 30 segundos
this.maxRetries = 3;
```

### Configuración de Cron Job (Opcional)

#### Linux/Unix
```bash
# Editar crontab
crontab -e

# Agregar línea (ejecutar cada hora)
0 * * * * /usr/bin/php /ruta/al/proyecto/utils/auto_sync.php >/dev/null 2>&1
```

#### Windows
```powershell
# Crear tarea programada (ejecutar como administrador)
schtasks /create /tn "ApartamentosCyL_AutoSync" /tr "\"C:\xampp\php\php.exe\" \"C:\xampp\htdocs\proyecto\utils\auto_sync.php\"" /sc hourly /ru SYSTEM
```

## 🛠️ Panel de Control Admin

### Funciones Disponibles
1. **Estado de Sincronización**: Información en tiempo real
2. **Sincronización Automática**: Estado del sistema automático
3. **Forzar Sincronización**: Ejecutar sincronización inmediata
4. **Ver Logs**: Historial detallado de sincronizaciones
5. **Estadísticas**: Apartamentos procesados, nuevos, actualizados

### Acceso
- Ir a `views/admin.php`
- Sección "Sincronización"
- Panel "Sincronización Automática"

## 📊 API Endpoints

### GET/POST `/api/auto_sync_endpoint.php`

#### Acciones Disponibles
- `?action=status` - Obtener estado del sistema
- `?action=execute` - Ejecutar sincronización si es necesario
- `?action=force` - Forzar sincronización (solo admins)
- `?action=logs` - Obtener logs (solo admins)

#### Ejemplo de Respuesta
```json
{
  "success": true,
  "data": {
    "enabled": true,
    "last_sync": "2024-02-03 14:30:00",
    "next_sync": "2024-02-03 15:30:00",
    "interval_hours": 1,
    "is_locked": false,
    "needs_sync": false
  }
}
```

## 🔍 Monitoreo y Debugging

### Logs del Sistema
```bash
# Ver logs de sincronización automática
tail -f temp/auto_sync.log
```

### Debugging en JavaScript
```javascript
// En la consola del navegador
console.log(window.autoSyncManager);

// Forzar sincronización manual
window.forceSync();
```

### Verificar Estado
```php
// Verificar estado programáticamente
require_once 'utils/auto_sync.php';
$manager = new AutoSyncManager();
$status = $manager->getStatus();
print_r($status);
```

## ⚠️ Solución de Problemas

### Problema: Sincronización no se ejecuta
**Solución**:
1. Verificar que JavaScript esté habilitado
2. Comprobar logs en `temp/auto_sync.log`
3. Verificar permisos de escritura en directorio `temp/`
4. Revisar consola del navegador para errores

### Problema: Locks permanentes
**Solución**:
```php
// Eliminar lock manualmente
unlink('temp/sync.lock');
```

### Problema: Logs muy grandes
**Solución**: Los logs se rotan automáticamente, pero puedes limpiarlos manualmente:
```bash
rm temp/auto_sync.log
```

## 🎯 Ventajas del Sistema

### Para Usuarios
- ✅ Datos siempre actualizados
- ✅ Sin interrupciones en la navegación
- ✅ Carga rápida de páginas
- ✅ Notificaciones discretas

### Para Administradores
- ✅ Control total desde panel admin
- ✅ Logs detallados de todas las operaciones
- ✅ Estadísticas en tiempo real
- ✅ Capacidad de forzar sincronización

### Para Desarrolladores
- ✅ Sistema robusto con protecciones
- ✅ API REST completa
- ✅ Fácil configuración y mantenimiento
- ✅ Múltiples métodos de ejecución

## 📈 Rendimiento

- **Impacto mínimo**: Solo se ejecuta cuando es necesario
- **Ejecución rápida**: Promedio 2-5 segundos por sincronización
- **Memoria eficiente**: Limpieza automática de recursos
- **Red optimizada**: Solo descarga datos cuando hay cambios

## 🔐 Seguridad

- **Autenticación**: Funciones admin requieren login
- **Validación**: Todos los inputs son validados
- **Logs seguros**: No se almacenan datos sensibles
- **Timeouts**: Prevención de ejecuciones colgadas

---

## 🚀 Instalación Rápida

1. **Ejecutar instalador**:
   ```
   http://tu-dominio/install_cron.php
   ```

2. **Verificar funcionamiento**:
   - Ir al panel de admin
   - Sección "Sincronización"
   - Verificar estado "Sincronización Automática"

3. **¡Listo!** El sistema ya está funcionando automáticamente.

---

*El sistema de sincronización automática mantiene tus datos actualizados sin intervención manual, proporcionando una experiencia fluida tanto para usuarios como administradores.*