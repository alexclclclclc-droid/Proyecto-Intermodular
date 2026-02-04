<?php
/**
 * Test script para verificar las estadísticas de apartamentos
 */

require_once 'config/config.php';
require_once 'dao/ApartamentoDAO.php';
require_once 'dao/ReservaDAO.php';

// Simular sesión de admin
session_start();
$_SESSION['usuario_id'] = 1;
$_SESSION['usuario_rol'] = 'admin';
$_SESSION['usuario_nombre'] = 'Test Admin';

echo "<h1>Test de Estadísticas de Apartamentos</h1>\n";

$apartamentoDAO = new ApartamentoDAO();
$reservaDAO = new ReservaDAO();

// Test 1: Contar apartamentos totales
echo "<h2>1. Total de Apartamentos</h2>\n";
try {
    $totalApartamentos = $apartamentoDAO->contarTotal();
    echo "<p>Total de apartamentos en la base de datos: <strong>$totalApartamentos</strong></p>\n";
} catch (Exception $e) {
    echo "<p style='color: red;'>Error al contar apartamentos: " . $e->getMessage() . "</p>\n";
}

// Test 2: Obtener reservas activas para hoy
echo "<h2>2. Reservas Activas Hoy</h2>\n";
$fechaHoy = date('Y-m-d');
echo "<p>Fecha de hoy: <strong>$fechaHoy</strong></p>\n";

try {
    $reservasActivas = $reservaDAO->obtenerReservasActivas($fechaHoy);
    echo "<p>Reservas activas encontradas: <strong>" . count($reservasActivas) . "</strong></p>\n";
    
    if (!empty($reservasActivas)) {
        echo "<h3>Detalles de reservas activas:</h3>\n";
        echo "<ul>\n";
        foreach ($reservasActivas as $reserva) {
            echo "<li>Apartamento ID: {$reserva['id_apartamento']}, Reserva ID: {$reserva['id']}, Entrada: {$reserva['fecha_entrada']}, Salida: {$reserva['fecha_salida']}</li>\n";
        }
        echo "</ul>\n";
        
        // Apartamentos únicos ocupados
        $apartamentosOcupados = array_unique(array_column($reservasActivas, 'id_apartamento'));
        echo "<p>Apartamentos únicos ocupados: <strong>" . count($apartamentosOcupados) . "</strong></p>\n";
        echo "<p>IDs de apartamentos ocupados: " . implode(', ', $apartamentosOcupados) . "</p>\n";
    } else {
        echo "<p>No hay reservas activas para hoy.</p>\n";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>Error al obtener reservas activas: " . $e->getMessage() . "</p>\n";
}

// Test 3: Calcular tasa de ocupación
echo "<h2>3. Cálculo de Tasa de Ocupación</h2>\n";
try {
    $totalApartamentos = $apartamentoDAO->contarTotal();
    $reservasActivas = $reservaDAO->obtenerReservasActivas($fechaHoy);
    $apartamentosOcupados = count(array_unique(array_column($reservasActivas, 'id_apartamento')));
    
    $tasaOcupacion = $totalApartamentos > 0 
        ? round(($apartamentosOcupados / $totalApartamentos) * 100, 1) 
        : 0;
    
    echo "<p>Total apartamentos: <strong>$totalApartamentos</strong></p>\n";
    echo "<p>Apartamentos ocupados: <strong>$apartamentosOcupados</strong></p>\n";
    echo "<p>Tasa de ocupación: <strong>$tasaOcupacion%</strong></p>\n";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error al calcular tasa de ocupación: " . $e->getMessage() . "</p>\n";
}

// Test 4: Probar el API de estadísticas completo
echo "<h2>4. Test del API de Estadísticas</h2>\n";
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
        echo "<p>API de estadísticas funcionando correctamente.</p>\n";
        echo "<h3>Estadísticas de apartamentos del API:</h3>\n";
        echo "<ul>\n";
        echo "<li>Total: " . $data['data']['apartamentos']['total'] . "</li>\n";
        echo "<li>Ocupados: " . $data['data']['apartamentos']['ocupados'] . "</li>\n";
        echo "<li>Tasa ocupación: " . $data['data']['apartamentos']['tasa_ocupacion'] . "%</li>\n";
        echo "</ul>\n";
    } else {
        echo "<p style='color: red;'>Error en el API: " . ($data['error'] ?? 'Error desconocido') . "</p>\n";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error al probar API: " . $e->getMessage() . "</p>\n";
}

// Test 5: Mostrar algunas reservas para contexto
echo "<h2>5. Contexto - Todas las Reservas</h2>\n";
try {
    $todasReservas = $reservaDAO->obtenerTodas(10, 0);
    echo "<p>Total de reservas en el sistema: <strong>" . count($todasReservas) . "</strong></p>\n";
    
    if (!empty($todasReservas)) {
        echo "<h3>Últimas 10 reservas:</h3>\n";
        echo "<table border='1' style='border-collapse: collapse;'>\n";
        echo "<tr><th>ID</th><th>Apartamento</th><th>Estado</th><th>Entrada</th><th>Salida</th><th>Fecha Reserva</th></tr>\n";
        foreach ($todasReservas as $reserva) {
            echo "<tr>";
            echo "<td>" . $reserva->getId() . "</td>";
            echo "<td>" . $reserva->getIdApartamento() . "</td>";
            echo "<td>" . $reserva->getEstado() . "</td>";
            echo "<td>" . $reserva->getFechaEntrada() . "</td>";
            echo "<td>" . $reserva->getFechaSalida() . "</td>";
            echo "<td>" . $reserva->getFechaReserva() . "</td>";
            echo "</tr>\n";
        }
        echo "</table>\n";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>Error al obtener reservas: " . $e->getMessage() . "</p>\n";
}

echo "<h2>Test Completado</h2>\n";
echo "<p><a href='views/admin.php'>Ir al Panel de Admin</a></p>\n";
?>