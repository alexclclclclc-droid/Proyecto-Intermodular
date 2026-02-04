<?php
/**
 * Script de diagnóstico para identificar el error HTTP 500
 */

// Habilitar reporte de errores
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'config/config.php';
require_once 'dao/UsuarioDAO.php';
require_once 'dao/ReservaDAO.php';
require_once 'dao/ApartamentoDAO.php';

// Simular sesión de admin
session_start();
$_SESSION['usuario_id'] = 1;
$_SESSION['usuario_rol'] = 'admin';
$_SESSION['usuario_nombre'] = 'Debug Admin';

echo "<h1>Diagnóstico del Error API</h1>\n";

echo "<h2>1. Verificar Conexión a Base de Datos</h2>\n";
try {
    $db = Database::getInstance()->getConnection();
    echo "<p style='color: green;'>✅ Conexión a base de datos: OK</p>\n";
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error de conexión: " . $e->getMessage() . "</p>\n";
    exit;
}

echo "<h2>2. Verificar DAOs</h2>\n";
try {
    $usuarioDAO = new UsuarioDAO();
    echo "<p style='color: green;'>✅ UsuarioDAO: OK</p>\n";
    
    $reservaDAO = new ReservaDAO();
    echo "<p style='color: green;'>✅ ReservaDAO: OK</p>\n";
    
    $apartamentoDAO = new ApartamentoDAO();
    echo "<p style='color: green;'>✅ ApartamentoDAO: OK</p>\n";
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error creando DAOs: " . $e->getMessage() . "</p>\n";
    exit;
}

echo "<h2>3. Probar Métodos Individuales</h2>\n";

// Test 1: Contar apartamentos
echo "<h3>3.1 Contar Apartamentos</h3>\n";
try {
    $totalApartamentos = $apartamentoDAO->contarTotal();
    echo "<p style='color: green;'>✅ Total apartamentos: $totalApartamentos</p>\n";
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error contando apartamentos: " . $e->getMessage() . "</p>\n";
}

// Test 2: Obtener reservas activas
echo "<h3>3.2 Reservas Activas</h3>\n";
try {
    $fechaHoy = date('Y-m-d');
    $reservasActivas = $reservaDAO->obtenerReservasActivas($fechaHoy);
    echo "<p style='color: green;'>✅ Reservas activas para $fechaHoy: " . count($reservasActivas) . "</p>\n";
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error obteniendo reservas activas: " . $e->getMessage() . "</p>\n";
}

// Test 3: Estadísticas de ocupación mensual
echo "<h3>3.3 Estadísticas Mensuales</h3>\n";
try {
    $inicioMes = date('Y-m-01');
    $finMes = date('Y-m-t');
    $estadisticasMes = $reservaDAO->obtenerEstadisticasOcupacion($inicioMes, $finMes);
    echo "<p style='color: green;'>✅ Estadísticas mensuales ($inicioMes a $finMes): " . print_r($estadisticasMes, true) . "</p>\n";
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error obteniendo estadísticas mensuales: " . $e->getMessage() . "</p>\n";
}

echo "<h2>4. Probar API Completa</h2>\n";
try {
    // Simular la lógica del API
    $stats = [
        'usuarios' => ['total' => 0, 'activos' => 0, 'inactivos' => 0, 'por_rol' => []],
        'reservas' => ['total' => 0, 'por_estado' => [], 'este_mes' => 0],
        'apartamentos' => ['total' => 0, 'ocupados' => 0, 'tasa_ocupacion' => 0],
        'sincronizacion' => ['ultima' => null, 'estado' => 'pendiente']
    ];
    
    // Estadísticas de usuarios
    $usuarios = $usuarioDAO->obtenerTodos();
    $stats['usuarios']['total'] = count($usuarios);
    
    foreach ($usuarios as $usuario) {
        if ($usuario->isActivo()) {
            $stats['usuarios']['activos']++;
        } else {
            $stats['usuarios']['inactivos']++;
        }
        
        $rol = $usuario->getRol();
        if (!isset($stats['usuarios']['por_rol'][$rol])) {
            $stats['usuarios']['por_rol'][$rol] = 0;
        }
        $stats['usuarios']['por_rol'][$rol]++;
    }
    
    // Estadísticas de reservas
    $reservas = $reservaDAO->obtenerTodas(1000, 0);
    $stats['reservas']['total'] = count($reservas);
    
    $estadosReserva = ['pendiente', 'confirmada', 'cancelada', 'completada'];
    foreach ($estadosReserva as $estado) {
        $stats['reservas']['por_estado'][$estado] = 0;
    }
    
    $mesActual = date('Y-m');
    foreach ($reservas as $reserva) {
        $estado = $reserva->getEstado();
        if (isset($stats['reservas']['por_estado'][$estado])) {
            $stats['reservas']['por_estado'][$estado]++;
        }
        
        if (strpos($reserva->getFechaReserva(), $mesActual) === 0) {
            $stats['reservas']['este_mes']++;
        }
    }
    
    // Estadísticas de apartamentos (la parte problemática)
    echo "<h3>4.1 Procesando Apartamentos...</h3>\n";
    
    try {
        $stats['apartamentos']['total'] = $apartamentoDAO->contarTotal();
        echo "<p>Total apartamentos: " . $stats['apartamentos']['total'] . "</p>\n";
    } catch (Exception $e) {
        echo "<p style='color: red;'>Error al contar apartamentos: " . $e->getMessage() . "</p>\n";
        $stats['apartamentos']['total'] = 0;
    }
    
    $fechaHoy = date('Y-m-d');
    $apartamentosOcupados = 0;
    
    try {
        $reservasActivas = $reservaDAO->obtenerReservasActivas($fechaHoy);
        if (is_array($reservasActivas) && !empty($reservasActivas)) {
            $apartamentosOcupados = count(array_unique(array_column($reservasActivas, 'id_apartamento')));
        }
        echo "<p>Apartamentos ocupados: $apartamentosOcupados</p>\n";
    } catch (Exception $e) {
        echo "<p style='color: red;'>Error al obtener reservas activas: " . $e->getMessage() . "</p>\n";
        $apartamentosOcupados = 0;
    }
    
    $stats['apartamentos']['ocupados'] = $apartamentosOcupados;
    $stats['apartamentos']['disponibles'] = $stats['apartamentos']['total'] - $apartamentosOcupados;
    $stats['apartamentos']['tasa_ocupacion'] = $stats['apartamentos']['total'] > 0 
        ? round(($apartamentosOcupados / $stats['apartamentos']['total']) * 100, 1) 
        : 0;
    
    echo "<p style='color: green;'>✅ API simulada ejecutada correctamente</p>\n";
    echo "<pre>" . json_encode($stats, JSON_PRETTY_PRINT) . "</pre>\n";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error en API simulada: " . $e->getMessage() . "</p>\n";
    echo "<p>Stack trace: " . $e->getTraceAsString() . "</p>\n";
}

echo "<h2>5. Probar API Real</h2>\n";
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
    
    echo "<p>HTTP Response: $httpCode</p>\n";
    echo "<p>Response Body:</p>\n";
    echo "<pre>" . htmlspecialchars($result) . "</pre>\n";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error probando API real: " . $e->getMessage() . "</p>\n";
}

echo "<h2>Diagnóstico Completado</h2>\n";
?>