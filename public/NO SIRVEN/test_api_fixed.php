<?php
/**
 * Test del API corregido
 */

require_once 'config/config.php';

// Simular sesión de admin
session_start();
$_SESSION['usuario_id'] = 1;
$_SESSION['usuario_rol'] = 'admin';
$_SESSION['usuario_nombre'] = 'Test Admin';

echo "<h1>Test del API Corregido</h1>\n";

echo "<h2>1. Probar API Simple</h2>\n";
try {
    $url = 'api_simple_test.php';
    $result = file_get_contents($url);
    $data = json_decode($result, true);
    
    if ($data && $data['success']) {
        echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px;'>\n";
        echo "<h3>✅ API Simple: Funcionando</h3>\n";
        echo "<p>Apartamentos totales: <strong>" . $data['apartamentos']['total'] . "</strong></p>\n";
        echo "<p>Apartamentos ocupados: <strong>" . $data['apartamentos']['ocupados'] . "</strong></p>\n";
        echo "<p>Tasa ocupación: <strong>" . $data['apartamentos']['tasa_ocupacion'] . "%</strong></p>\n";
        echo "<h4>Debug info:</h4>\n";
        echo "<ul>\n";
        foreach ($data['debug'] as $debug) {
            echo "<li>$debug</li>\n";
        }
        echo "</ul>\n";
        echo "</div>\n";
    } else {
        echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px;'>\n";
        echo "<h3>❌ API Simple: Error</h3>\n";
        echo "<pre>" . htmlspecialchars($result) . "</pre>\n";
        echo "</div>\n";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>\n";
}

echo "<h2>2. Probar API Principal Corregido</h2>\n";
try {
    $url = 'api/admin.php?action=estadisticas';
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => 'Content-Type: application/json',
            'ignore_errors' => true
        ]
    ]);
    
    $result = file_get_contents($url, false, $context);
    $httpCode = $http_response_header[0] ?? 'Unknown';
    
    echo "<p><strong>HTTP Response:</strong> $httpCode</p>\n";
    
    if (strpos($httpCode, '200') !== false) {
        $data = json_decode($result, true);
        
        if ($data && $data['success']) {
            echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px;'>\n";
            echo "<h3>✅ API Principal: Funcionando</h3>\n";
            echo "<h4>Estadísticas de Apartamentos:</h4>\n";
            echo "<ul>\n";
            echo "<li>Total: <strong>" . $data['data']['apartamentos']['total'] . "</strong></li>\n";
            echo "<li>Ocupados: <strong>" . $data['data']['apartamentos']['ocupados'] . "</strong></li>\n";
            echo "<li>Disponibles: <strong>" . $data['data']['apartamentos']['disponibles'] . "</strong></li>\n";
            echo "<li>Tasa ocupación: <strong>" . $data['data']['apartamentos']['tasa_ocupacion'] . "%</strong></li>\n";
            echo "</ul>\n";
            
            echo "<h4>Estadísticas Mensuales:</h4>\n";
            echo "<ul>\n";
            echo "<li>Apartamentos ocupados este mes: <strong>" . $data['data']['apartamentos']['ocupacion_mes']['apartamentos_ocupados'] . "</strong></li>\n";
            echo "<li>Reservas este mes: <strong>" . $data['data']['apartamentos']['ocupacion_mes']['total_reservas'] . "</strong></li>\n";
            echo "</ul>\n";
            echo "</div>\n";
        } else {
            echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px;'>\n";
            echo "<h3>❌ API Principal: Error en respuesta</h3>\n";
            echo "<pre>" . htmlspecialchars($result) . "</pre>\n";
            echo "</div>\n";
        }
    } else {
        echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px;'>\n";
        echo "<h3>❌ API Principal: Error HTTP</h3>\n";
        echo "<p>Código: $httpCode</p>\n";
        echo "<pre>" . htmlspecialchars($result) . "</pre>\n";
        echo "</div>\n";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>\n";
}

echo "<h2>3. Comparación de Datos</h2>\n";
echo "<div style='background: #d1ecf1; padding: 15px; border-radius: 5px;'>\n";
echo "<h3>🔧 Cambios Implementados</h3>\n";
echo "<ol>\n";
echo "<li><strong>Consultas SQL directas:</strong> Eliminé la dependencia de métodos DAO complejos</li>\n";
echo "<li><strong>Manejo robusto de errores:</strong> Try-catch en cada operación crítica</li>\n";
echo "<li><strong>Valores por defecto:</strong> Si algo falla, se usan valores seguros (0)</li>\n";
echo "<li><strong>Logging de errores:</strong> Los errores se registran para debugging</li>\n";
echo "<li><strong>Simplificación:</strong> Eliminé cálculos complejos que podían fallar</li>\n";
echo "</ol>\n";
echo "</div>\n";

echo "<h2>4. Enlaces de Prueba</h2>\n";
echo "<ul>\n";
echo "<li><a href='views/admin.php' target='_blank'>🎯 Panel de Admin</a></li>\n";
echo "<li><a href='api/admin.php?action=estadisticas' target='_blank'>📊 API de Estadísticas (JSON)</a></li>\n";
echo "<li><a href='debug_api_error.php' target='_blank'>🔍 Diagnóstico Completo</a></li>\n";
echo "</ul>\n";

echo "<p><em>Test ejecutado: " . date('Y-m-d H:i:s') . "</em></p>\n";
?>