<?php
/**
 * Panel de Administrador con autenticación real
 * Requiere login y permisos de administrador
 */

define('ROOT_PATH', dirname(__FILE__) . '/');
require_once ROOT_PATH . 'config/config.php';

// Verificar que el usuario esté logueado
if (!isLoggedIn()) {
    // Redirigir al login
    header('Location: index.php?login_required=1');
    exit();
}

// Verificar que el usuario sea administrador
if (!isAdmin()) {
    echo "<h2>❌ Acceso Denegado</h2>";
    echo "<p>No tienes permisos de administrador.</p>";
    echo "<p>Tu rol actual: <strong>" . ($_SESSION['usuario_rol'] ?? 'No definido') . "</strong></p>";
    echo "<p><a href='index.php'>← Volver al inicio</a></p>";
    exit();
}

// Si llegamos aquí, el usuario es administrador válido
echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 10px; margin: 10px 0; border-radius: 5px;'>";
echo "✅ Acceso autorizado como administrador: <strong>" . htmlspecialchars($_SESSION['usuario_email']) . "</strong>";
echo "</div>";

// Incluir el panel de admin
include 'views/admin.php';
?>