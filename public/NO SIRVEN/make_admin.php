<?php
/**
 * Script para convertir un usuario en administrador
 */

require_once 'config/config.php';

$email = $_GET['email'] ?? '';

if (empty($email)) {
    echo "<h2>❌ Error</h2>";
    echo "<p>No se especificó un email.</p>";
    echo "<p><a href='check_users.php'>← Volver a la lista de usuarios</a></p>";
    exit;
}

try {
    $usuarioDAO = new UsuarioDAO();
    $usuario = $usuarioDAO->obtenerPorEmail($email);
    
    if (!$usuario) {
        echo "<h2>❌ Usuario no encontrado</h2>";
        echo "<p>No se encontró un usuario con el email: <strong>" . htmlspecialchars($email) . "</strong></p>";
        echo "<p><a href='check_users.php'>← Volver a la lista de usuarios</a></p>";
        exit;
    }
    
    if ($usuario->getRol() === 'admin') {
        echo "<h2>ℹ️ Usuario ya es administrador</h2>";
        echo "<p>El usuario <strong>" . htmlspecialchars($email) . "</strong> ya tiene rol de administrador.</p>";
    } else {
        // Actualizar rol a admin
        $conn = Database::getInstance()->getConnection();
        $sql = "UPDATE usuarios SET rol = 'admin' WHERE email = :email";
        $stmt = $conn->prepare($sql);
        $stmt->execute([':email' => $email]);
        
        echo "<h2>✅ Usuario convertido en administrador</h2>";
        echo "<p>El usuario <strong>" . htmlspecialchars($email) . "</strong> ahora es administrador.</p>";
    }
    
    echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; margin: 20px 0; border-radius: 5px;'>";
    echo "<h3>🚀 Acceder al Panel de Administrador</h3>";
    echo "<p>Ahora puedes acceder al panel de administrador de dos formas:</p>";
    echo "<ol>";
    echo "<li><strong>Con autenticación real:</strong> <a href='admin_real.php'>admin_real.php</a> (necesitas hacer login)</li>";
    echo "<li><strong>Demo directo:</strong> <a href='admin_demo.php'>admin_demo.php</a> (acceso directo)</li>";
    echo "</ol>";
    echo "</div>";
    
    echo "<p><a href='check_users.php'>← Volver a la lista de usuarios</a></p>";
    
} catch (Exception $e) {
    echo "<h2>❌ Error</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "<p><a href='check_users.php'>← Volver a la lista de usuarios</a></p>";
}
?>