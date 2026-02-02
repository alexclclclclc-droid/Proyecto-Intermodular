<?php
/**
 * Script para verificar usuarios en la base de datos
 */

require_once 'config/config.php';

try {
    echo "<h2>👥 Usuarios en la Base de Datos</h2>";
    
    $usuarioDAO = new UsuarioDAO();
    $usuarios = $usuarioDAO->obtenerTodos();
    
    echo "<p>Total de usuarios encontrados: <strong>" . count($usuarios) . "</strong></p>";
    
    if (count($usuarios) > 0) {
        echo "<table border='1' cellpadding='10' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr style='background-color: #f0f0f0;'>";
        echo "<th>ID</th><th>Email</th><th>Nombre</th><th>Rol</th><th>Estado</th><th>Verificado</th><th>Fecha Registro</th>";
        echo "</tr>";
        
        foreach ($usuarios as $usuario) {
            $bgColor = $usuario->getRol() === 'admin' ? '#e8f5e8' : '#f8f8f8';
            echo "<tr style='background-color: {$bgColor};'>";
            echo "<td>" . $usuario->getId() . "</td>";
            echo "<td><strong>" . htmlspecialchars($usuario->getEmail()) . "</strong></td>";
            echo "<td>" . htmlspecialchars($usuario->getNombreCompleto()) . "</td>";
            echo "<td><span style='padding: 3px 8px; border-radius: 3px; background: " . 
                 ($usuario->getRol() === 'admin' ? '#007bff; color: white' : '#28a745; color: white') . ";'>" . 
                 $usuario->getRol() . "</span></td>";
            echo "<td>" . ($usuario->isActivo() ? '✅ Activo' : '❌ Inactivo') . "</td>";
            echo "<td>" . ($usuario->isVerificado() ? '✅ Verificado' : '❌ No verificado') . "</td>";
            echo "<td>" . ($usuario->getFechaRegistro() ?? 'N/A') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Verificar si hay administradores
        $admins = array_filter($usuarios, fn($u) => $u->getRol() === 'admin');
        
        if (count($admins) === 0) {
            echo "<div style='background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; margin: 20px 0; border-radius: 5px;'>";
            echo "<h3>⚠️ No hay administradores</h3>";
            echo "<p>Para acceder al panel de administrador, necesitas convertir un usuario en administrador.</p>";
            echo "<p><a href='make_admin.php?email=" . urlencode('camiloyyeiner@gmail.com') . "' style='background: #007bff; color: white; padding: 8px 15px; text-decoration: none; border-radius: 3px;'>Hacer administrador a camiloyyeiner@gmail.com</a></p>";
            echo "</div>";
        } else {
            echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; margin: 20px 0; border-radius: 5px;'>";
            echo "<h3>✅ Administradores encontrados</h3>";
            echo "<p>Puedes acceder al panel con cualquiera de estos usuarios administradores:</p>";
            foreach ($admins as $admin) {
                echo "<li><strong>" . htmlspecialchars($admin->getEmail()) . "</strong></li>";
            }
            echo "</div>";
        }
        
    } else {
        echo "<p style='color: red;'>No se encontraron usuarios en la base de datos.</p>";
        echo "<p><a href='setup_database.php'>Ejecutar configuración de base de datos</a></p>";
    }
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; margin: 20px 0; border-radius: 5px;'>";
    echo "<h3>❌ Error de conexión</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "<p>Verifica que:</p>";
    echo "<ul>";
    echo "<li>MySQL esté ejecutándose</li>";
    echo "<li>La base de datos 'apartamentos_cyl' exista</li>";
    echo "<li>Las credenciales en config/database.php sean correctas</li>";
    echo "</ul>";
    echo "</div>";
}
?>