<?php
/**
 * API de Sincronización para Panel de Administrador
 * Maneja todas las operaciones de sincronización con funcionalidad completa
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
require_once __DIR__ . '/../services/ApiSyncService.php';
require_once __DIR__ . '/../utils/gps_generator.php';

// Verificar que el usuario sea administrador
if (!isLoggedIn() || !isAdmin()) {
    jsonResponse(['success' => false, 'error' => 'No autorizado'], 403);
}

$action = $_GET['action'] ?? $_POST['action'] ?? 'status';

try {
    switch ($action) {
        
        case 'status':
            // Obtener estado actual de sincronización
            $conn = Database::getInstance()->getConnection();
            
            // Estadísticas generales
            $stmt = $conn->query("SELECT COUNT(*) as total FROM apartamentos WHERE activo = 1");
            $totalApartamentos = $stmt->fetchColumn();
            
            $stmt = $conn->query("SELECT COUNT(*) as total FROM apartamentos WHERE activo = 1 AND fecha_sincronizacion IS NOT NULL");
            $apartamentosSincronizados = $stmt->fetchColumn();
            
            $stmt = $conn->query("SELECT COUNT(*) as total FROM apartamentos WHERE activo = 1 AND gps_latitud IS NOT NULL AND gps_longitud IS NOT NULL");
            $apartamentosConGPS = $stmt->fetchColumn();
            
            // Última sincronización
            $stmt = $conn->query("SELECT MAX(fecha_sincronizacion) as ultima_sync FROM apartamentos WHERE fecha_sincronizacion IS NOT NULL");
            $ultimaSync = $stmt->fetchColumn();
            
            // Verificar estado de GPS
            $verificacionGPS = GPSGenerator::verificarApartamentosSinGPS();
            
            jsonResponse([
                'success' => true,
                'data' => [
                    'total_apartamentos' => $totalApartamentos,
                    'apartamentos_sincronizados' => $apartamentosSincronizados,
                    'apartamentos_con_gps' => $apartamentosConGPS,
                    'apartamentos_sin_gps' => $verificacionGPS['sin_gps'] ?? 0,
                    'ultima_sincronizacion' => $ultimaSync,
                    'necesita_gps' => $verificacionGPS['necesita_generacion'] ?? false,
                    'porcentaje_sincronizado' => $totalApartamentos > 0 ? round(($apartamentosSincronizados / $totalApartamentos) * 100, 1) : 0,
                    'porcentaje_gps' => $totalApartamentos > 0 ? round(($apartamentosConGPS / $totalApartamentos) * 100, 1) : 0
                ]
            ]);
            break;
            
        case 'execute':
            // Ejecutar sincronización completa
            $service = new ApiSyncService(false); // Modo no silencioso para admin
            
            // Capturar la salida para el log
            ob_start();
            $resultado = $service->sincronizar();
            $logOutput = ob_get_clean();
            
            // Verificar si no hay nada que sincronizar
            if ($resultado['procesados'] === 0 && $resultado['errores'] === 0) {
                jsonResponse([
                    'success' => true,
                    'data' => [
                        'sync_result' => $resultado,
                        'gps_result' => null,
                        'log_output' => $logOutput,
                        'timestamp' => date('Y-m-d H:i:s'),
                        'message' => 'No hay datos nuevos para sincronizar. Todos los apartamentos están actualizados.'
                    ]
                ]);
                break;
            }
            
            // Generar coordenadas GPS automáticamente después de la sincronización
            $gpsResultado = null;
            try {
                $verificacionGPS = GPSGenerator::verificarApartamentosSinGPS();
                if ($verificacionGPS['success'] && $verificacionGPS['necesita_generacion']) {
                    $gpsResultado = GPSGenerator::generarCoordenadasAutomaticamente();
                }
            } catch (Exception $e) {
                $gpsResultado = ['success' => false, 'error' => $e->getMessage()];
            }
            
            // Registrar en historial
            registrarSincronizacion($resultado, $gpsResultado);
            
            jsonResponse([
                'success' => true,
                'data' => [
                    'sync_result' => $resultado,
                    'gps_result' => $gpsResultado,
                    'log_output' => $logOutput,
                    'timestamp' => date('Y-m-d H:i:s')
                ]
            ]);
            break;
            
        case 'history':
            // Obtener historial de sincronizaciones
            $historial = obtenerHistorialSincronizacion();
            
            jsonResponse([
                'success' => true,
                'data' => $historial
            ]);
            break;
            
        case 'test_connection':
            // Probar conexión con la API externa
            $service = new ApiSyncService(false); // Modo no silencioso para admin
            $conexionOk = $service->probarConexion();
            
            jsonResponse([
                'success' => true,
                'data' => [
                    'connection_ok' => $conexionOk,
                    'timestamp' => date('Y-m-d H:i:s')
                ]
            ]);
            break;
            
        case 'generate_gps':
            // Generar coordenadas GPS manualmente
            $resultado = GPSGenerator::generarCoordenadasAutomaticamente();
            
            jsonResponse([
                'success' => $resultado['success'],
                'data' => $resultado
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

/**
 * Registrar sincronización en historial
 */
function registrarSincronizacion($resultado, $gpsResultado = null) {
    try {
        $conn = Database::getInstance()->getConnection();
        
        $stmt = $conn->prepare("
            INSERT INTO sync_history 
            (fecha, procesados, nuevos, actualizados, errores, gps_generados, detalles) 
            VALUES (NOW(), :procesados, :nuevos, :actualizados, :errores, :gps_generados, :detalles)
        ");
        
        $gpsGenerados = $gpsResultado && $gpsResultado['success'] ? $gpsResultado['actualizados'] : 0;
        $detalles = json_encode([
            'sync' => $resultado,
            'gps' => $gpsResultado
        ]);
        
        $stmt->execute([
            ':procesados' => $resultado['procesados'],
            ':nuevos' => $resultado['nuevos'],
            ':actualizados' => $resultado['actualizados'],
            ':errores' => $resultado['errores'],
            ':gps_generados' => $gpsGenerados,
            ':detalles' => $detalles
        ]);
        
    } catch (Exception $e) {
        // Error silencioso - no interrumpir la sincronización
        error_log("Error registrando historial de sincronización: " . $e->getMessage());
    }
}

/**
 * Obtener historial de sincronizaciones
 */
function obtenerHistorialSincronizacion($limit = 10) {
    try {
        $conn = Database::getInstance()->getConnection();
        
        $stmt = $conn->prepare("
            SELECT fecha, procesados, nuevos, actualizados, errores, gps_generados, detalles
            FROM sync_history 
            ORDER BY fecha DESC 
            LIMIT :limit
        ");
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (Exception $e) {
        // Si no existe la tabla, crearla
        crearTablaSyncHistory();
        return [];
    }
}

/**
 * Crear tabla de historial si no existe
 */
function crearTablaSyncHistory() {
    try {
        $conn = Database::getInstance()->getConnection();
        
        $sql = "
            CREATE TABLE IF NOT EXISTS sync_history (
                id INT AUTO_INCREMENT PRIMARY KEY,
                fecha DATETIME NOT NULL,
                procesados INT DEFAULT 0,
                nuevos INT DEFAULT 0,
                actualizados INT DEFAULT 0,
                errores INT DEFAULT 0,
                gps_generados INT DEFAULT 0,
                detalles TEXT,
                INDEX idx_fecha (fecha)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ";
        
        $conn->exec($sql);
        
    } catch (Exception $e) {
        error_log("Error creando tabla sync_history: " . $e->getMessage());
    }
}
?>