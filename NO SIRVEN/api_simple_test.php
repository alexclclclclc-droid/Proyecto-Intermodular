<?php
/**
 * API simplificada para probar estadísticas paso a paso
 */

header('Content-Type: application/json; charset=utf-8');

// Habilitar reporte de errores
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/dao/UsuarioDAO.php';
require_once __DIR__ . '/dao/ReservaDAO.php';
require_once __DIR__ . '/dao/ApartamentoDAO.php';

// Simular sesión de admin
session_start();
$_SESSION['usuario_id'] = 1;
$_SESSION['usuario_rol'] = 'admin';
$_SESSION['usuario_nombre'] = 'Test Admin';

try {
    $usuarioDAO = new UsuarioDAO();
    $reservaDAO = new ReservaDAO();
    $apartamentoDAO = new ApartamentoDAO();
    
    $stats = [
        'usuarios' => ['total' => 0, 'activos' => 0, 'inactivos' => 0, 'por_rol' => []],
        'reservas' => ['total' => 0, 'por_estado' => [], 'este_mes' => 0],
        'apartamentos' => ['total' => 0, 'ocupados' => 0, 'disponibles' => 0, 'tasa_ocupacion' => 0],
        'debug' => []
    ];
    
    // Paso 1: Usuarios
    $stats['debug'][] = 'Paso 1: Obteniendo usuarios...';
    $usuarios = $usuarioDAO->obtenerTodos();
    $stats['usuarios']['total'] = count($usuarios);
    $stats['debug'][] = 'Usuarios obtenidos: ' . count($usuarios);
    
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
    
    // Paso 2: Reservas
    $stats['debug'][] = 'Paso 2: Obteniendo reservas...';
    $reservas = $reservaDAO->obtenerTodas(1000, 0);
    $stats['reservas']['total'] = count($reservas);
    $stats['debug'][] = 'Reservas obtenidas: ' . count($reservas);
    
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
    
    // Paso 3: Apartamentos (versión simple)
    $stats['debug'][] = 'Paso 3: Contando apartamentos...';
    
    // Método simple: contar directamente en la base de datos
    $db = Database::getInstance()->getConnection();
    $stmt = $db->query("SELECT COUNT(*) FROM apartamentos WHERE activo = TRUE");
    $totalApartamentos = (int)$stmt->fetchColumn();
    
    $stats['apartamentos']['total'] = $totalApartamentos;
    $stats['debug'][] = 'Total apartamentos: ' . $totalApartamentos;
    
    // Paso 4: Apartamentos ocupados (versión simple)
    $stats['debug'][] = 'Paso 4: Calculando ocupación...';
    $fechaHoy = date('Y-m-d');
    
    $stmt = $db->prepare("
        SELECT COUNT(DISTINCT id_apartamento) 
        FROM reservas 
        WHERE estado IN ('confirmada', 'pendiente')
        AND fecha_entrada <= ? 
        AND fecha_salida > ?
    ");
    $stmt->execute([$fechaHoy, $fechaHoy]);
    $apartamentosOcupados = (int)$stmt->fetchColumn();
    
    $stats['apartamentos']['ocupados'] = $apartamentosOcupados;
    $stats['apartamentos']['disponibles'] = $totalApartamentos - $apartamentosOcupados;
    $stats['apartamentos']['tasa_ocupacion'] = $totalApartamentos > 0 
        ? round(($apartamentosOcupados / $totalApartamentos) * 100, 1) 
        : 0;
    
    $stats['debug'][] = 'Apartamentos ocupados: ' . $apartamentosOcupados;
    $stats['debug'][] = 'Tasa ocupación: ' . $stats['apartamentos']['tasa_ocupacion'] . '%';
    
    $stats['success'] = true;
    $stats['message'] = 'Estadísticas obtenidas correctamente';
    
    echo json_encode($stats, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
        'line' => $e->getLine(),
        'file' => $e->getFile()
    ], JSON_PRETTY_PRINT);
}
?>