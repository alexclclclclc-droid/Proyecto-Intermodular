<?php
/**
 * Script de sincronización con la API
 * Ejecutar: php api/sync.php
 * O acceder vía web (solo admin)
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../services/ApiSyncService.php';

// Si se ejecuta desde web, verificar admin
if (php_sapi_name() !== 'cli') {
    header('Content-Type: application/json; charset=utf-8');
    
    if (!isLoggedIn() || !isAdmin()) {
        jsonResponse(['success' => false, 'error' => 'No autorizado'], 403);
    }
}

try {
    $service = new ApiSyncService();
    $resultado = $service->sincronizar();
    
    if (php_sapi_name() === 'cli') {
        // Salida para consola
        echo "\n=== SINCRONIZACIÓN COMPLETADA ===\n";
        echo "Procesados: {$resultado['procesados']}\n";
        echo "Nuevos: {$resultado['nuevos']}\n";
        echo "Actualizados: {$resultado['actualizados']}\n";
        echo "Errores: {$resultado['errores']}\n";
        echo "\n--- LOG ---\n";
        foreach ($resultado['log'] as $linea) {
            echo $linea . "\n";
        }
    } else {
        // Salida JSON para web
        jsonResponse([
            'success' => $resultado['success'],
            'data' => $resultado
        ]);
    }

} catch (Exception $e) {
    if (php_sapi_name() === 'cli') {
        echo "ERROR: " . $e->getMessage() . "\n";
        exit(1);
    } else {
        jsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
    }
}
