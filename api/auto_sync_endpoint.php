<?php
/**
 * Endpoint para sincronización automática
 * Se puede llamar desde JavaScript o cron jobs
 */
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Manejar preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../utils/auto_sync.php';

$action = $_GET['action'] ?? $_POST['action'] ?? 'status';

try {
    $manager = new AutoSyncManager();
    
    switch ($action) {
        case 'status':
            // Obtener estado de la sincronización automática
            $status = $manager->getStatus();
            jsonResponse([
                'success' => true,
                'data' => $status
            ]);
            break;
            
        case 'execute':
            // Ejecutar sincronización si es necesario
            $result = $manager->executeAutoSync();
            jsonResponse([
                'success' => true,
                'data' => $result
            ]);
            break;
            
        case 'force':
            // Forzar sincronización (solo para admins)
            if (!isLoggedIn() || !isAdmin()) {
                jsonResponse(['success' => false, 'error' => 'No autorizado'], 403);
            }
            
            // Eliminar el archivo de última sincronización para forzar
            $lastSyncFile = __DIR__ . '/../temp/last_sync.txt';
            if (file_exists($lastSyncFile)) {
                unlink($lastSyncFile);
            }
            
            $result = $manager->executeAutoSync();
            jsonResponse([
                'success' => true,
                'data' => $result,
                'forced' => true
            ]);
            break;
            
        case 'logs':
            // Obtener logs de sincronización (solo para admins)
            if (!isLoggedIn() || !isAdmin()) {
                jsonResponse(['success' => false, 'error' => 'No autorizado'], 403);
            }
            
            $logFile = __DIR__ . '/../temp/auto_sync.log';
            $logs = [];
            
            if (file_exists($logFile)) {
                $lines = file($logFile, FILE_IGNORE_NEW_LINES);
                $logs = array_map(function($line) {
                    return json_decode($line, true);
                }, array_slice($lines, -20)); // Últimas 20 entradas
            }
            
            jsonResponse([
                'success' => true,
                'data' => array_filter($logs) // Filtrar líneas inválidas
            ]);
            break;
            
        default:
            throw new Exception('Acción no válida');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    jsonResponse([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>