<?php
/**
 * Script de sincronización con la API
 * Ejecutar: php api/sync.php
 * O acceder vía web: http://localhost/tu-proyecto/api/sync.php
 */

// Para la primera sincronización, permitir acceso sin login
// IMPORTANTE: Después de la primera carga, puedes borrar o comentar la línea siguiente
$permitirSinLogin = true;

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../services/ApiSyncService.php';
require_once __DIR__ . '/../utils/gps_generator.php';

// Si se ejecuta desde web, verificar permisos
if (php_sapi_name() !== 'cli') {
    header('Content-Type: application/json; charset=utf-8');
    
    // Verificar si requiere autenticación
    if (!$permitirSinLogin && (!isLoggedIn() || !isAdmin())) {
        jsonResponse(['success' => false, 'error' => 'No autorizado'], 403);
    }
}

try {
    echo "<pre>"; // Para mejor visualización en navegador
    echo "=== INICIANDO SINCRONIZACIÓN ===\n\n";
    
    $service = new ApiSyncService(false); // Modo no silencioso para mostrar progreso
    $resultado = $service->sincronizar();
    
    echo "\n=== SINCRONIZACIÓN COMPLETADA ===\n";
    echo "Procesados: {$resultado['procesados']}\n";
    echo "Nuevos: {$resultado['nuevos']}\n";
    echo "Actualizados: {$resultado['actualizados']}\n";
    echo "Errores: {$resultado['errores']}\n";
    echo "\n--- LOG ---\n";
    foreach ($resultado['log'] as $linea) {
        echo $linea . "\n";
    }
    
    // Generar coordenadas GPS automáticamente después de la sincronización
    echo "\n=== GENERANDO COORDENADAS GPS ===\n";
    try {
        $gpsResultado = GPSGenerator::generarCoordenadasAutomaticamente();
        if ($gpsResultado['success']) {
            echo "✅ {$gpsResultado['message']}\n";
        } else {
            echo "❌ Error generando GPS: {$gpsResultado['error']}\n";
        }
    } catch (Exception $e) {
        echo "❌ Error generando GPS: " . $e->getMessage() . "\n";
    }
    
    echo "</pre>";

} catch (Exception $e) {
    echo "<pre>";
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "</pre>";
}