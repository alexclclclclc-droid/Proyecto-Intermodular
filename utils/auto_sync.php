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
    private const SYNC_INTERVAL = 3600; // 1 hora en segundos
    
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
        
        // Verificar tiempo desde última sincronización
        if (!file_exists(self::LAST_SYNC_FILE)) {
            return true;
        }
        
        $lastSync = (int)file_get_contents(self::LAST_SYNC_FILE);
        $timeSinceLastSync = time() - $lastSync;
        
        return $timeSinceLastSync >= self::SYNC_INTERVAL;
    }
    
    /**
     * Ejecutar sincronización automática
     */
    public function executeAutoSync(): array {
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
            $service = new ApiSyncService();
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
        
        $nextSync = $lastSync ? $lastSync + self::SYNC_INTERVAL : time();
        
        return [
            'enabled' => true,
            'last_sync' => $lastSync ? date('Y-m-d H:i:s', $lastSync) : 'Nunca',
            'next_sync' => date('Y-m-d H:i:s', $nextSync),
            'interval_hours' => self::SYNC_INTERVAL / 3600,
            'is_locked' => $this->isLocked(),
            'needs_sync' => $this->needsSync()
        ];
    }
}

// Si se ejecuta directamente (desde cron o línea de comandos)
if (php_sapi_name() === 'cli' || (isset($_GET['auto']) && $_GET['auto'] === '1')) {
    $manager = new AutoSyncManager();
    $result = $manager->executeAutoSync();
    
    if (php_sapi_name() === 'cli') {
        echo "Auto Sync Result: " . json_encode($result, JSON_PRETTY_PRINT) . "\n";
    } else {
        header('Content-Type: application/json');
        echo json_encode($result);
    }
}
?>