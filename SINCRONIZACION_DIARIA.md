# Sincronización Automática - Sistema Diario

## Configuración Actualizada

El sistema de sincronización automática ahora funciona **una vez al día** en lugar de cada hora, optimizado para la actualización de datos de Castilla y León.

### ⏰ Horario de Sincronización

- **Hora de sincronización**: 22:30 (10:30 PM)
- **Frecuencia**: Una vez al día
- **Razón**: La API de Castilla y León se actualiza a las 22:00, por lo que sincronizamos 30 minutos después

### 🔄 Funcionamiento

1. **API de CyL se actualiza**: 22:00 horas
2. **Nuestro sistema sincroniza**: 22:30 horas  
3. **Datos frescos disponibles**: A partir de las 22:30

### 📱 Verificación Inteligente

El sistema JavaScript verifica:
- **Horario normal**: Cada 30 minutos
- **Ventana de sincronización (22:00-23:30)**: Cada 5 minutos
- **Al cargar página**: Verificación inmediata
- **Al volver a la pestaña**: Verificación tras 1 segundo

## Archivos Modificados

### 1. `utils/auto_sync.php`
- ✅ Cambiado `SYNC_INTERVAL` de 3600 (1 hora) a 86400 (24 horas)
- ✅ Agregado `SYNC_HOUR = 22` y `SYNC_MINUTE = 30`
- ✅ Nueva función `isTimeToSync()` que verifica la hora correcta
- ✅ Lógica mejorada en `needsSync()` para verificar si ya se sincronizó hoy
- ✅ Función `getNextSyncTime()` para calcular próxima sincronización

### 2. `public/js/auto-sync.js`
- ✅ Cambiado intervalo de verificación de 5 minutos a 30 minutos
- ✅ Verificación más frecuente (5 min) durante ventana de sincronización
- ✅ Lógica inteligente para verificar solo cuando es necesario

### 3. `install_cron.php`
- ✅ Actualizado cron job de `0 * * * *` a `30 22 * * *`
- ✅ Documentación actualizada para reflejar el nuevo horario

## Estado del Sistema

### ✅ Ventajas del Nuevo Sistema

1. **Eficiencia**: No desperdicia recursos sincronizando datos que no han cambiado
2. **Actualidad**: Sincroniza justo después de que CyL actualice sus datos
3. **Rendimiento**: Menos carga en el servidor y en la API externa
4. **Inteligente**: Verifica la hora correcta antes de sincronizar

### 📊 Información de Estado

El método `getStatus()` ahora incluye:
- `sync_time`: Hora configurada de sincronización (22:30)
- `interval_hours`: 24 horas
- `is_time_to_sync`: Si es el momento correcto para sincronizar
- `current_time`: Hora actual del sistema

## Configuración de Cron

### Linux/Unix
```bash
# Sincronización diaria a las 22:30
30 22 * * * /usr/bin/php /ruta/al/proyecto/utils/auto_sync.php >/dev/null 2>&1
```

### Windows (Programador de Tareas)
- **Frecuencia**: Diaria
- **Hora**: 22:30
- **Programa**: `php.exe`
- **Argumentos**: `ruta\al\proyecto\utils\auto_sync.php`

## Monitoreo

### Panel de Administración
- Muestra próxima sincronización programada
- Indica si es el momento de sincronizar
- Permite forzar sincronización manual cuando sea necesario

### Logs
- Todas las sincronizaciones se registran con timestamp
- Información detallada sobre apartamentos procesados
- Errores y advertencias para debugging

## Compatibilidad

✅ **Totalmente compatible** con:
- Sistema de sincronización manual desde admin
- Scripts de desarrollo (`sync.php`, `sync_improved.php`)
- Modo silencioso para usuarios finales
- Sistema de locks para prevenir ejecuciones simultáneas

## Resultado

🎯 **Objetivo cumplido**: El sistema ahora sincroniza una vez al día a las 22:30, optimizando recursos y garantizando datos actualizados después de que Castilla y León actualice su API.

## Verificación del Cambio

Para verificar que el sistema funciona correctamente:

1. **Comprobar estado actual**:
   ```bash
   php utils/auto_sync.php
   ```

2. **Ver información en panel de admin**:
   - Ir a `/views/admin.php`
   - Sección "Sincronización Automática"
   - Verificar que muestra "Próxima sincronización: [fecha] 22:30:00"

3. **Verificar logs**:
   - Revisar `temp/auto_sync.log` para ver sincronizaciones
   - Comprobar que no se ejecutan múltiples veces por día

## Configuración Recomendada

### Para Producción
- ✅ Usar cron job diario a las 22:30
- ✅ Mantener sistema JavaScript como respaldo
- ✅ Monitorear logs regularmente

### Para Desarrollo
- ✅ Usar sincronización manual desde admin cuando sea necesario
- ✅ Scripts `sync.php` y `sync_improved.php` para pruebas
- ✅ Verificar funcionamiento con diferentes horarios

## Próximos Pasos

1. **Monitorear** el sistema durante una semana para verificar funcionamiento
2. **Ajustar** horarios si es necesario basado en observaciones
3. **Documentar** cualquier comportamiento inesperado
4. **Optimizar** según patrones de uso reales