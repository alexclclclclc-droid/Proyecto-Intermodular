<?php
/**
 * Script de configuración del mapa para el equipo
 * Ejecutar después de hacer pull del repositorio
 */
require_once 'config/config.php';

echo "<h1>🗺️ Configuración del Mapa - Setup para Equipo</h1>";
echo "<p>Este script verifica y configura todo lo necesario para que el mapa funcione.</p>";

// Verificar conexión a base de datos
echo "<h2>1. 🔌 Verificando Conexión a Base de Datos</h2>";
try {
    $conn = Database::getInstance()->getConnection();
    echo "<p>✅ Conexión a base de datos: <strong>OK</strong></p>";
    
    // Verificar tabla apartamentos
    $stmt = $conn->query("SHOW TABLES LIKE 'apartamentos'");
    if ($stmt->rowCount() > 0) {
        echo "<p>✅ Tabla 'apartamentos': <strong>Existe</strong></p>";
    } else {
        echo "<p>❌ Tabla 'apartamentos': <strong>No existe</strong></p>";
        echo "<p>🔧 <strong>Solución:</strong> Ejecuta el script de creación de base de datos</p>";
    }
    
} catch (Exception $e) {
    echo "<p>❌ Error de conexión: " . $e->getMessage() . "</p>";
    echo "<p>🔧 <strong>Solución:</strong> Verifica la configuración en config/database.php</p>";
}

// Verificar datos en apartamentos
echo "<h2>2. 🏠 Verificando Datos de Apartamentos</h2>";
try {
    $conn = Database::getInstance()->getConnection();
    
    $stmt = $conn->query("SELECT COUNT(*) as total FROM apartamentos WHERE activo = 1");
    $total = $stmt->fetchColumn();
    echo "<p>📊 Total apartamentos activos: <strong>$total</strong></p>";
    
    if ($total == 0) {
        echo "<p>⚠️ <strong>PROBLEMA:</strong> No hay apartamentos en la base de datos</p>";
        echo "<p>🔧 <strong>Solución:</strong> <a href='#sincronizar'>Sincronizar datos</a></p>";
    }
    
    $stmt = $conn->query("SELECT COUNT(*) as con_gps FROM apartamentos WHERE activo = 1 AND gps_latitud IS NOT NULL AND gps_longitud IS NOT NULL AND gps_latitud != '' AND gps_longitud != ''");
    $conGps = $stmt->fetchColumn();
    echo "<p>📍 Con coordenadas GPS: <strong>$conGps</strong></p>";
    
    if ($conGps == 0 && $total > 0) {
        echo "<p>⚠️ <strong>PROBLEMA:</strong> Los apartamentos no tienen coordenadas GPS</p>";
        echo "<p>🔧 <strong>Solución:</strong> <a href='#generar-gps'>Generar coordenadas GPS</a></p>";
    }
    
} catch (Exception $e) {
    echo "<p>❌ Error verificando datos: " . $e->getMessage() . "</p>";
}

// Verificar APIs
echo "<h2>3. 🌐 Verificando APIs</h2>";
$baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']);

$apiTests = [
    'Provincias' => 'api/apartamentos.php?action=provincias',
    'Mapa' => 'api/apartamentos.php?action=mapa'
];

foreach ($apiTests as $name => $endpoint) {
    echo "<p><strong>$name:</strong> ";
    
    if (function_exists('curl_init')) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $baseUrl . '/' . $endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_HEADER, false);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode == 200) {
            $data = json_decode($response, true);
            if ($data && isset($data['success']) && $data['success']) {
                $count = isset($data['data']) ? count($data['data']) : 0;
                echo "✅ OK ($count elementos)";
            } else {
                echo "❌ Error en respuesta JSON";
            }
        } else {
            echo "❌ HTTP $httpCode";
        }
    } else {
        echo "⚠️ cURL no disponible";
    }
    
    echo " - <a href='$endpoint' target='_blank'>Probar manualmente</a></p>";
}

// Verificar archivos críticos
echo "<h2>4. 📁 Verificando Archivos Críticos</h2>";
$archivos = [
    'views/mapa.php' => 'Página principal del mapa',
    'api/apartamentos.php' => 'API de apartamentos',
    'dao/ApartamentoDAO.php' => 'DAO de apartamentos',
    'public/js/app.js' => 'JavaScript principal'
];

foreach ($archivos as $archivo => $descripcion) {
    if (file_exists($archivo)) {
        echo "<p>✅ <strong>$descripcion:</strong> $archivo</p>";
    } else {
        echo "<p>❌ <strong>$descripcion:</strong> $archivo <em>(FALTA)</em></p>";
    }
}

// Sección de soluciones
echo "<h2>🔧 Soluciones Automáticas</h2>";

// Botón para sincronizar datos
echo "<div id='sincronizar'>";
echo "<h3>📥 Sincronizar Datos desde API Externa</h3>";
echo "<p>Si no tienes apartamentos en tu base de datos:</p>";
echo "<p><a href='api/sync.php' class='btn btn-primary' target='_blank'>🔄 Sincronizar Apartamentos</a></p>";
echo "</div>";

// Botón para generar GPS
echo "<div id='generar-gps'>";
echo "<h3>📍 Generar Coordenadas GPS</h3>";
echo "<p>Si tienes apartamentos pero sin coordenadas GPS:</p>";
echo "<form method='post' style='margin: 10px 0;'>";
echo "<button type='submit' name='generar_gps' class='btn btn-success'>🗺️ Generar Coordenadas GPS</button>";
echo "</form>";
echo "</div>";

// Procesar generación de GPS
if (isset($_POST['generar_gps'])) {
    echo "<h3>🎯 Generando Coordenadas GPS...</h3>";
    
    // Coordenadas de las capitales de provincia
    $coordenadasProvincias = [
        'Ávila' => ['lat' => 40.6566, 'lng' => -4.6813],
        'Burgos' => ['lat' => 42.3439, 'lng' => -3.6969],
        'León' => ['lat' => 42.5987, 'lng' => -5.5671],
        'Palencia' => ['lat' => 42.0098, 'lng' => -4.5288],
        'Salamanca' => ['lat' => 40.9701, 'lng' => -5.6635],
        'Segovia' => ['lat' => 40.9429, 'lng' => -4.1088],
        'Soria' => ['lat' => 41.7665, 'lng' => -2.4790],
        'Valladolid' => ['lat' => 41.6523, 'lng' => -4.7245],
        'Zamora' => ['lat' => 41.5034, 'lng' => -5.7467]
    ];
    
    try {
        $conn = Database::getInstance()->getConnection();
        
        // Obtener apartamentos sin GPS
        $stmt = $conn->query("
            SELECT id, nombre, municipio, provincia 
            FROM apartamentos 
            WHERE activo = 1 
            AND (gps_latitud IS NULL OR gps_longitud IS NULL OR gps_latitud = '' OR gps_longitud = '')
        ");
        
        $apartamentosSinGPS = $stmt->fetchAll();
        $actualizados = 0;
        
        foreach ($apartamentosSinGPS as $apt) {
            $provincia = $apt['provincia'];
            
            if (isset($coordenadasProvincias[$provincia])) {
                $base = $coordenadasProvincias[$provincia];
            } else {
                $base = ['lat' => 41.6523, 'lng' => -4.7245]; // Centro de CyL
            }
            
            // Variación aleatoria pequeña
            $lat = $base['lat'] + (mt_rand(-100, 100) / 1000);
            $lng = $base['lng'] + (mt_rand(-100, 100) / 1000);
            
            $updateStmt = $conn->prepare("
                UPDATE apartamentos 
                SET gps_latitud = :lat, gps_longitud = :lng 
                WHERE id = :id
            ");
            
            if ($updateStmt->execute([':lat' => $lat, ':lng' => $lng, ':id' => $apt['id']])) {
                $actualizados++;
            }
        }
        
        echo "<p>✅ <strong>Coordenadas generadas para $actualizados apartamentos</strong></p>";
        echo "<p>🎉 <strong>¡El mapa ya debería funcionar!</strong></p>";
        echo "<p><a href='views/mapa.php' class='btn btn-primary'>🗺️ Probar el mapa</a></p>";
        
    } catch (Exception $e) {
        echo "<p>❌ Error generando coordenadas: " . $e->getMessage() . "</p>";
    }
}

// Test final
echo "<h2>5. 🧪 Test Final</h2>";
echo "<p>Una vez completados los pasos anteriores:</p>";
echo "<p><a href='views/mapa.php' class='btn btn-primary' target='_blank'>🗺️ Probar el Mapa</a></p>";

// Instrucciones para el equipo
echo "<h2>📋 Instrucciones para el Equipo</h2>";
echo "<ol>";
echo "<li><strong>Hacer pull</strong> del repositorio</li>";
echo "<li><strong>Ejecutar este script</strong> (setup_mapa_equipo.php)</li>";
echo "<li><strong>Seguir las soluciones</strong> que aparezcan en rojo</li>";
echo "<li><strong>Probar el mapa</strong> con el botón de arriba</li>";
echo "</ol>";

echo "<h2>🆘 Si Sigue Sin Funcionar</h2>";
echo "<ul>";
echo "<li>Verifica que tu servidor web esté ejecutándose</li>";
echo "<li>Revisa la consola del navegador (F12) para errores JavaScript</li>";
echo "<li>Asegúrate de que las rutas en config/config.php sean correctas</li>";
echo "<li>Verifica que tengas permisos de escritura en la base de datos</li>";
echo "</ul>";

// Estilos
echo "<style>
body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
.btn { 
    display: inline-block; 
    padding: 10px 20px; 
    margin: 5px;
    text-decoration: none; 
    border-radius: 5px; 
    border: none;
    cursor: pointer;
    font-size: 14px;
}
.btn-primary { background: #007bff; color: white; }
.btn-success { background: #28a745; color: white; }
.btn:hover { opacity: 0.8; }
h1 { color: #333; }
h2 { color: #666; border-bottom: 2px solid #eee; padding-bottom: 5px; }
h3 { color: #888; }
</style>";
?>