<?php
/**
 * Test final del dashboard con estadísticas reales
 */

require_once 'config/config.php';

// Simular sesión de admin
session_start();
$_SESSION['usuario_id'] = 1;
$_SESSION['usuario_rol'] = 'admin';
$_SESSION['usuario_nombre'] = 'Test Admin';

echo "<h1>Test Final - Dashboard con Estadísticas Reales</h1>\n";

// Test del API completo
echo "<h2>Estadísticas del Dashboard</h2>\n";

try {
    $url = 'api/admin.php?action=estadisticas';
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => 'Content-Type: application/json'
        ]
    ]);
    
    $result = file_get_contents($url, false, $context);
    $data = json_decode($result, true);
    
    if ($data && $data['success']) {
        echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; margin: 10px 0;'>\n";
        echo "<h3>✅ API funcionando correctamente</h3>\n";
        echo "</div>\n";
        
        // Mostrar estadísticas de usuarios
        echo "<h3>👥 Usuarios</h3>\n";
        echo "<ul>\n";
        echo "<li>Total: <strong>" . $data['data']['usuarios']['total'] . "</strong></li>\n";
        echo "<li>Activos: <strong>" . $data['data']['usuarios']['activos'] . "</strong></li>\n";
        echo "<li>Inactivos: <strong>" . $data['data']['usuarios']['inactivos'] . "</strong></li>\n";
        echo "</ul>\n";
        
        // Mostrar estadísticas de reservas
        echo "<h3>📅 Reservas</h3>\n";
        echo "<ul>\n";
        echo "<li>Total: <strong>" . $data['data']['reservas']['total'] . "</strong></li>\n";
        echo "<li>Este mes: <strong>" . $data['data']['reservas']['este_mes'] . "</strong></li>\n";
        echo "</ul>\n";
        
        // Mostrar estadísticas de apartamentos (DATOS REALES)
        echo "<h3>🏠 Apartamentos (DATOS REALES)</h3>\n";
        echo "<div style='background: #fff3cd; padding: 15px; border-radius: 5px; margin: 10px 0;'>\n";
        echo "<ul>\n";
        echo "<li>Total: <strong>" . $data['data']['apartamentos']['total'] . "</strong> (desde base de datos)</li>\n";
        echo "<li>Ocupados hoy: <strong>" . $data['data']['apartamentos']['ocupados'] . "</strong> (calculado en tiempo real)</li>\n";
        
        if (isset($data['data']['apartamentos']['disponibles'])) {
            echo "<li>Disponibles: <strong>" . $data['data']['apartamentos']['disponibles'] . "</strong></li>\n";
        }
        
        echo "<li>Tasa de ocupación: <strong>" . $data['data']['apartamentos']['tasa_ocupacion'] . "%</strong> (calculada en tiempo real)</li>\n";
        echo "</ul>\n";
        echo "</div>\n";
        
        // Mostrar estadísticas adicionales si existen
        if (isset($data['data']['apartamentos']['ocupacion_mes'])) {
            echo "<h4>📊 Estadísticas del Mes Actual</h4>\n";
            echo "<ul>\n";
            echo "<li>Apartamentos ocupados este mes: <strong>" . $data['data']['apartamentos']['ocupacion_mes']['apartamentos_ocupados'] . "</strong></li>\n";
            echo "<li>Total reservas este mes: <strong>" . $data['data']['apartamentos']['ocupacion_mes']['total_reservas'] . "</strong></li>\n";
            echo "<li>Total noches reservadas: <strong>" . $data['data']['apartamentos']['ocupacion_mes']['total_noches'] . "</strong></li>\n";
            echo "</ul>\n";
        }
        
        // Mostrar reservas por estado
        if (isset($data['data']['reservas']['por_estado'])) {
            echo "<h4>📈 Reservas por Estado</h4>\n";
            echo "<ul>\n";
            foreach ($data['data']['reservas']['por_estado'] as $estado => $cantidad) {
                echo "<li>" . ucfirst($estado) . ": <strong>$cantidad</strong></li>\n";
            }
            echo "</ul>\n";
        }
        
        // Mostrar usuarios por rol
        if (isset($data['data']['usuarios']['por_rol'])) {
            echo "<h4>👤 Usuarios por Rol</h4>\n";
            echo "<ul>\n";
            foreach ($data['data']['usuarios']['por_rol'] as $rol => $cantidad) {
                echo "<li>" . ucfirst($rol) . ": <strong>$cantidad</strong></li>\n";
            }
            echo "</ul>\n";
        }
        
    } else {
        echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px; margin: 10px 0;'>\n";
        echo "<h3>❌ Error en el API</h3>\n";
        echo "<p>Error: " . ($data['error'] ?? 'Error desconocido') . "</p>\n";
        echo "</div>\n";
    }
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px; margin: 10px 0;'>\n";
    echo "<h3>❌ Excepción al probar API</h3>\n";
    echo "<p>Error: " . $e->getMessage() . "</p>\n";
    echo "</div>\n";
}

echo "<h2>Comparación: Antes vs Ahora</h2>\n";
echo "<div style='display: flex; gap: 20px;'>\n";

echo "<div style='flex: 1; background: #f8d7da; padding: 15px; border-radius: 5px;'>\n";
echo "<h3>❌ ANTES (Datos Simulados)</h3>\n";
echo "<ul>\n";
echo "<li>Total apartamentos: <strong>150</strong> (valor fijo)</li>\n";
echo "<li>Ocupados: <strong>45</strong> (valor fijo)</li>\n";
echo "<li>Tasa ocupación: <strong>30%</strong> (calculado con valores fijos)</li>\n";
echo "</ul>\n";
echo "</div>\n";

echo "<div style='flex: 1; background: #d4edda; padding: 15px; border-radius: 5px;'>\n";
echo "<h3>✅ AHORA (Datos Reales)</h3>\n";
echo "<ul>\n";
echo "<li>Total apartamentos: <strong>Desde base de datos</strong></li>\n";
echo "<li>Ocupados: <strong>Calculado en tiempo real</strong></li>\n";
echo "<li>Tasa ocupación: <strong>Calculada dinámicamente</strong></li>\n";
echo "<li>Disponibles: <strong>Calculado automáticamente</strong></li>\n";
echo "<li>Estadísticas mensuales: <strong>Incluidas</strong></li>\n";
echo "</ul>\n";
echo "</div>\n";

echo "</div>\n";

echo "<h2>Enlaces de Prueba</h2>\n";
echo "<ul>\n";
echo "<li><a href='views/admin.php' target='_blank'>🎯 Ir al Panel de Admin</a></li>\n";
echo "<li><a href='test_apartamentos_stats.php' target='_blank'>🔍 Test Detallado de Apartamentos</a></li>\n";
echo "<li><a href='api/admin.php?action=estadisticas' target='_blank'>📊 Ver API de Estadísticas (JSON)</a></li>\n";
echo "</ul>\n";

echo "<div style='background: #d1ecf1; padding: 15px; border-radius: 5px; margin: 20px 0;'>\n";
echo "<h3>🎉 Resumen de Mejoras Implementadas</h3>\n";
echo "<ol>\n";
echo "<li><strong>Apartamentos totales:</strong> Ahora se obtienen de la base de datos usando <code>ApartamentoDAO->contarTotal()</code></li>\n";
echo "<li><strong>Apartamentos ocupados:</strong> Se calculan en tiempo real basado en reservas activas para la fecha actual</li>\n";
echo "<li><strong>Tasa de ocupación:</strong> Se calcula dinámicamente como porcentaje real</li>\n";
echo "<li><strong>Apartamentos disponibles:</strong> Se calcula automáticamente (total - ocupados)</li>\n";
echo "<li><strong>Estadísticas mensuales:</strong> Se incluyen datos de ocupación del mes actual</li>\n";
echo "<li><strong>Actividad reciente:</strong> Se actualiza con información real de apartamentos</li>\n";
echo "</ol>\n";
echo "</div>\n";

echo "<p><em>Fecha y hora del test: " . date('Y-m-d H:i:s') . "</em></p>\n";
?>