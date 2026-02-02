<?php
/**
 * Debug de la API de administrador
 * Para verificar por qué no aparecen los usuarios reales
 */

require_once 'config/config.php';

// Simular sesión de admin para las pruebas
session_start();
$_SESSION['usuario_id'] = 1;
$_SESSION['usuario_rol'] = 'admin';

echo "<h2>🔍 Debug API de Administrador</h2>";

try {
    echo "<h3>1. Probando conexión directa con UsuarioDAO</h3>";
    
    $usuarioDAO = new UsuarioDAO();
    $usuarios = $usuarioDAO->obtenerTodos();
    
    echo "<p><strong>Usuarios encontrados directamente:</strong> " . count($usuarios) . "</p>";
    
    if (count($usuarios) > 0) {
        echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>Email</th><th>Nombre</th><th>Rol</th><th>Activo</th></tr>";
        
        foreach ($usuarios as $usuario) {
            echo "<tr>";
            echo "<td>" . $usuario->getId() . "</td>";
            echo "<td>" . htmlspecialchars($usuario->getEmail()) . "</td>";
            echo "<td>" . htmlspecialchars($usuario->getNombreCompleto()) . "</td>";
            echo "<td>" . $usuario->getRol() . "</td>";
            echo "<td>" . ($usuario->isActivo() ? 'Sí' : 'No') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        echo "<h3>2. Probando toArray() de cada usuario</h3>";
        foreach ($usuarios as $usuario) {
            echo "<h4>Usuario: " . $usuario->getEmail() . "</h4>";
            echo "<pre>";
            print_r($usuario->toArray());
            echo "</pre>";
        }
    }
    
    echo "<h3>3. Simulando llamada a la API admin.php</h3>";
    
    // Simular la llamada que hace el JavaScript
    $_GET['action'] = 'usuarios_listar';
    
    ob_start();
    include 'api/admin.php';
    $apiResponse = ob_get_clean();
    
    echo "<h4>Respuesta de la API:</h4>";
    echo "<pre style='background: #f0f0f0; padding: 10px; border-radius: 5px;'>";
    echo htmlspecialchars($apiResponse);
    echo "</pre>";
    
    // Intentar decodificar JSON
    $decoded = json_decode($apiResponse, true);
    if ($decoded) {
        echo "<h4>JSON decodificado:</h4>";
        echo "<pre>";
        print_r($decoded);
        echo "</pre>";
        
        if (isset($decoded['success']) && $decoded['success'] && isset($decoded['data'])) {
            echo "<p><strong>✅ API funcionando correctamente</strong></p>";
            echo "<p>Usuarios en respuesta API: " . count($decoded['data']) . "</p>";
        } else {
            echo "<p><strong>❌ Error en API:</strong> " . ($decoded['error'] ?? 'Error desconocido') . "</p>";
        }
    } else {
        echo "<p><strong>❌ Error:</strong> La respuesta no es JSON válido</p>";
    }
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px;'>";
    echo "<h3>❌ Error:</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "<p><strong>Archivo:</strong> " . $e->getFile() . "</p>";
    echo "<p><strong>Línea:</strong> " . $e->getLine() . "</p>";
    echo "</div>";
}
?>