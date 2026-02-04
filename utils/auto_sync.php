<?php
/**
 * Sistema de Sincronización Automática
 * Este script se ejecuta automáticamente para mantener los datos actualizados
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../services/ApiSyncService.php';
require_once __DIR__ . '/../utils/gps_generator.php';

class AutoSyncManager {
    private const SYNC_LOCK_FILE = __DIR__ . '/../temp/sync.lock';
    private const LAST_SYNC_FILE = __DIR__ . '/../temp/last_sync.txt';
    private const SYNC_INTERVAL = 86400; // 24 horas en segundos (1 día)
    private const SYNC_HOUR = 22; // Hora de sincronización (22:30 - después de que CyL actualice a las 22:00)
    private const SYNC_MINUTE = 30; // Minuto de sincronización
    
    public function __construct() {
        // Crear directorio temp si no existe
        $tempDir = dirname(self::SYNC_LOCK_FILE);
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }
    }
    
    /**
     * Verificar si es necesario ejecutar sincronización
     */
    public function needsSync(): bool {
        // Verificar si hay un lock activo
        if ($this->isLocked()) {
            return false;
        }
        
        // Verificar si ya se sincronizó hoy
        if (!file_exists(self::LAST_SYNC_FILE)) {
            return $this->isTimeToSync();
        }
        
        $lastSync = (int)file_get_contents(self::LAST_SYNC_FILE);
        $lastSyncDate = date('Y-m-d', $lastSync);
        $today = date('Y-m-d');
        
        // Si ya se sincronizó hoy, no es necesario
        if ($lastSyncDate === $today) {
            return false;
        }
        
        // Si no se ha sincronizado hoy, verificar si es la hora correcta
        return $this->isTimeToSync();
    }
    
    /**
     * Verificar si es la hora correcta para sincronizar
     */
    private function isTimeToSync(): bool {
        $currentHour = (int)date('H');
        $currentMinute = (int)date('i');
        
        // Sincronizar después de las 22:30 (cuando CyL ya ha actualizado sus datos)
        if ($currentHour > self::SYNC_HOUR) {
            return true;
        }
        
        if ($currentHour === self::SYNC_HOUR && $currentMinute >= self::SYNC_MINUTE) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Ejecutar sincronización automática
     */
    public function executeAutoSync(bool $silentMode = true): array {
        if (!$this->needsSync()) {
            return [
                'success' => true,
                'message' => 'Sincronización no necesaria',
                'skipped' => true
            ];
        }
        
        // Crear lock
        $this->createLock();
        
        try {
            $service = new ApiSyncService($silentMode);
            $resultado = $service->sincronizar();
            
            // Generar GPS si es necesario
            $gpsResultado = null;
            try {
                $verificacionGPS = GPSGenerator::verificarApartamentosSinGPS();
                if ($verificacionGPS['success'] && $verificacionGPS['necesita_generacion']) {
                    $gpsResultado = GPSGenerator::generarCoordenadasAutomaticamente();
                }
            } catch (Exception $e) {
                error_log("Error generando GPS automático: " . $e->getMessage());
            }
            
            // Actualizar timestamp de última sincronización
            file_put_contents(self::LAST_SYNC_FILE, time());
            
            // Log del resultado
            $this->logSyncResult($resultado, $gpsResultado);
            
            return [
                'success' => true,
                'sync_result' => $resultado,
                'gps_result' => $gpsResultado,
                'timestamp' => date('Y-m-d H:i:s')
            ];
            
        } catch (Exception $e) {
            error_log("Error en sincronización automática: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'timestamp' => date('Y-m-d H:i:s')
            ];
        } finally {
            $this->removeLock();
        }
    }
    
    /**
     * Crear archivo de lock
     */
    private function createLock(): void {
        file_put_contents(self::SYNC_LOCK_FILE, getmypid() . '|' . time());
    }
    
    /**
     * Eliminar archivo de lock
     */
    private function removeLock(): void {
        if (file_exists(self::SYNC_LOCK_FILE)) {
            unlink(self::SYNC_LOCK_FILE);
        }
    }
    
    /**
     * Verificar si hay un lock activo
     */
    private function isLocked(): bool {
        if (!file_exists(self::SYNC_LOCK_FILE)) {
            return false;
        }
        
        $lockContent = file_get_contents(self::SYNC_LOCK_FILE);
        list($pid, $timestamp) = explode('|', $lockContent);
        
        // Si el lock es muy antiguo (más de 30 minutos), considerarlo inválido
        if (time() - $timestamp > 1800) {
            $this->removeLock();
            return false;
        }
        
        return true;
    }
    
    /**
     * Registrar resultado de sincronización
     */
    private function logSyncResult($resultado, $gpsResultado = null): void {
        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'type' => 'auto_sync',
            'result' => $resultado,
            'gps_result' => $gpsResultado
        ];
        
        $logFile = __DIR__ . '/../temp/auto_sync.log';
        $logLine = json_encode($logEntry) . "\n";
        file_put_contents($logFile, $logLine, FILE_APPEND | LOCK_EX);
        
        // Mantener solo las últimas 100 líneas del log
        $this->trimLogFile($logFile, 100);
    }
    
    /**
     * Mantener el archivo de log con un tamaño manejable
     */
    private function trimLogFile(string $logFile, int $maxLines): void {
        if (!file_exists($logFile)) return;
        
        $lines = file($logFile, FILE_IGNORE_NEW_LINES);
        if (count($lines) > $maxLines) {
            $trimmedLines = array_slice($lines, -$maxLines);
            file_put_contents($logFile, implode("\n", $trimmedLines) . "\n");
        }
    }
    
    /**
     * Obtener estado de la sincronización automática
     */
    public function getStatus(): array {
        $lastSync = file_exists(self::LAST_SYNC_FILE) ? 
            (int)file_get_contents(self::LAST_SYNC_FILE) : null;
        
        // Calcular próxima sincronización
        $nextSync = $this->getNextSyncTime();
        
        return [
            'enabled' => true,
            'last_sync' => $lastSync ? date('Y-m-d H:i:s', $lastSync) : 'Nunca',
            'next_sync' => date('Y-m-d H:i:s', $nextSync),
            'sync_time' => sprintf('%02d:%02d', self::SYNC_HOUR, self::SYNC_MINUTE),
            'interval_hours' => 24,
            'is_locked' => $this->isLocked(),
            'needs_sync' => $this->needsSync(),
            'current_time' => date('Y-m-d H:i:s'),
            'is_time_to_sync' => $this->isTimeToSync()
        ];
    }
    
    /**
     * Calcular la próxima hora de sincronización
     */
    private function getNextSyncTime(): int {
        $now = time();
        $today = date('Y-m-d');
        $syncTimeToday = strtotime($today . ' ' . self::SYNC_HOUR . ':' . self::SYNC_MINUTE . ':00');
        
        // Si ya pasó la hora de hoy, programar para mañana
        if ($now >= $syncTimeToday) {
            return strtotime('+1 day', $syncTimeToday);
        }
        
        // Si aún no es la hora de hoy, usar la hora de hoy
        return $syncTimeToday;
    }
}

// Si se ejecuta directamente (desde cron o línea de comandos)
if (php_sapi_name() === 'cli' || (isset($_GET['auto']) && $_GET['auto'] === '1')) {
    $manager = new AutoSyncManager();
    // Para CLI y ejecución directa, usar modo no silencioso para mostrar progreso
    $result = $manager->executeAutoSync(false);
    
    if (php_sapi_name() === 'cli') {
        echo "Auto Sync Result: " . json_encode($result, JSON_PRETTY_PRINT) . "\n";
    } else {
        header('Content-Type: application/json');
        echo json_encode($result);
    }
}
?>