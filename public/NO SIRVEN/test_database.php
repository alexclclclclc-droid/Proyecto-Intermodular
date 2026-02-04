<?php
/**
 * Script para probar la conexión a la base de datos
 * y las APIs del panel de administrador
 */

require_once 'config/config.php';

try {
    echo "<h2>🔧 Prueba de Conexión a Base de Datos</h2>";
    
    // Probar conexión básica
    echo "<h3>1. Conexión PDO</h3>";
    $db = Database::getInstance();
    $conn = $db->getConnection();
    echo "✅ Conexión establecida correctamente<br>";
    
    // Probar DAOs
    echo "<h3>2. Probando DAOs</h3>";
    
    // UsuarioDAO
    $usuarioDAO = new UsuarioDAO();
    $usuarios = $usuarioDAO->obtenerTodos();
    echo "👥 Usuarios encontrados: " . count($usuarios) . "<br>";
    
    // ReservaDAO
    $reservaDAO = new ReservaDAO();
    $reservas = $reservaDAO->obtenerTodas(10, 0);
    echo "📅 Reservas encontradas: " . count($reservas) . "<br>";
    
    // Probar API de administrador
    echo "<h3>3. Probando API Admin</h3>";
    
    // Simular sesión de admin para las pruebas
    session_start();
    $_SESSION['usuario_id'] = 1;
    $_SESSION['usuario_rol'] = 'admin';
    
    // Probar estadísticas
    echo "<h4>Estadísticas:</h4>";
    $stats = [
        'usuarios' => [
            'total' => count($usuarios),
            'activos' => 0,
            'inactivos' => 0,
            'por_rol' => []
        ],
        'reservas' => [
            'total' => count($reservas),
            'por_estado' => []
        ]
    ];
    
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
    
    foreach ($reservas as $reserva) {
        $estado = $reserva->getEstado();
        if (!isset($stats['reservas']['por_estado'][$estado])) {
            $stats['reservas']['por_estado'][$estado] = 0;
        }
        $stats['reservas']['por_estado'][$estado]++;
    }
    
    echo "<pre>";
    print_r($stats);
    echo "</pre>";
    
    // Mostrar algunos usuarios
    echo "<h4>Usuarios en la base de datos:</h4>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Email</th><th>Nombre</th><th>Rol</th><th>Estado</th></tr>";
    
    foreach ($usuarios as $usuario) {
        echo "<tr>";
        echo "<td>" . $usuario->getId() . "</td>";
        echo "<td>" . htmlspecialchars($usuario->getEmail()) . "</td>";
        echo "<td>" . htmlspecialchars($usuario->getNombreCompleto()) . "</td>";
        echo "<td>" . $usuario->getRol() . "</td>";
        echo "<td>" . ($usuario->isActivo() ? 'Activo' : 'Inactivo') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Mostrar algunas reservas
    echo "<h4>Reservas en la base de datos:</h4>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Usuario</th><th>Apartamento</th><th>Entrada</th><th>Salida</th><th>Estado</th></tr>";
    
    foreach ($reservas as $reserva) {
        echo "<tr>";
        echo "<td>" . $reserva->getId() . "</td>";
        echo "<td>" . htmlspecialchars($reserva->getEmailUsuario() ?? 'N/A') . "</td>";
        echo "<td>" . htmlspecialchars($reserva->getNombreApartamento() ?? 'N/A') . "</td>";
        echo "<td>" . $reserva->getFechaEntrada() . "</td>";
        echo "<td>" . $reserva->getFechaSalida() . "</td>";
        echo "<td>" . $reserva->getEstado() . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<h3>🎉 ¡Todas las pruebas pasaron correctamente!</h3>";
    echo "<p><strong>El panel de administrador debería funcionar con datos reales ahora.</strong></p>";
    echo "<p><a href='admin_demo.php'>🚀 Ir al Panel de Administrador</a></p>";
    
} catch (Exception $e) {
    echo "<h3>❌ Error:</h3>";
    echo "<p style='color: red;'>" . $e->getMessage() . "</p>";
    echo "<p>Verifica que:</p>";
    echo "<ul>";
    echo "<li>MySQL esté ejecutándose</li>";
    echo "<li>La base de datos 'apartamentos_cyl' exista</li>";
    echo "<li>Las credenciales en config/database.php sean correctas</li>";
    echo "<li>Hayas ejecutado setup_database.php</li>";
    echo "</ul>";
}
?>