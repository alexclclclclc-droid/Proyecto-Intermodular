<?php
/**
 * Script de prueba para verificar el panel de administrador
 */
define('ROOT_PATH', __DIR__ . '/');
require_once ROOT_PATH . 'config/config.php';

echo "<h2>🧪 Test del Panel de Administrador</h2>";

// Verificar usuario admin
require_once ROOT_PATH . 'dao/UsuarioDAO.php';
$usuarioDAO = new UsuarioDAO();

$admin = $usuarioDAO->obtenerPorEmail('admin@apartamentoscyl.es');

if ($admin) {
    echo "✅ Usuario administrador encontrado:<br>";
    echo "- Email: " . $admin->getEmail() . "<br>";
    echo "- Rol: " . $admin->getRol() . "<br>";
    echo "- Activo: " . ($admin->isActivo() ? 'Sí' : 'No') . "<br>";
    echo "- Verificado: " . ($admin->isVerificado() ? 'Sí' : 'No') . "<br>";
    
    // Verificar contraseña
    if ($admin->verificarPassword('Admin123!')) {
        echo "✅ Contraseña correcta<br>";
    } else {
        echo "❌ Contraseña incorrecta<br>";
    }
} else {
    echo "❌ Usuario administrador no encontrado<br>";
}

echo "<br><h3>📋 Instrucciones para probar:</h3>";
echo "<ol>";
echo "<li>Ve a <a href='index.php'>la página principal</a></li>";
echo "<li>Haz clic en 'Iniciar sesión'</li>";
echo "<li>Usa estas credenciales:</li>";
echo "<ul>";
echo "<li><strong>Email:</strong> admin@apartamentoscyl.es</li>";
echo "<li><strong>Contraseña:</strong> Admin123!</li>";
echo "</ul>";
echo "<li>Una vez logueado, verás el enlace '🛠️ Panel Admin' en el menú</li>";
echo "<li>Haz clic para acceder al panel de administrador</li>";
echo "</ol>";

echo "<br><h3>🎯 Funcionalidades disponibles:</h3>";
echo "<ul>";
echo "<li>📊 <strong>Dashboard:</strong> Estadísticas generales del sistema</li>";
echo "<li>👥 <strong>Usuarios:</strong> Gestión completa de usuarios (activar/desactivar/eliminar)</li>";
echo "<li>📅 <strong>Reservas:</strong> Administración de todas las reservas</li>";
echo "<li>🔄 <strong>Sincronización:</strong> Herramientas de sincronización con APIs externas</li>";
echo "</ul>";

echo "<br><p><strong>🚀 ¡El panel de administrador está listo para usar!</strong></p>";
?>