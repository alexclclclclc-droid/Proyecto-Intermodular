<?php
/**
 * Script para configurar la base de datos automáticamente
 * Ejecutar una sola vez para crear las tablas y datos iniciales
 */

// Configuración de la base de datos
$host = 'localhost';
$username = 'root';
$password = '';
$charset = 'utf8mb4';

try {
    echo "🔧 Configurando base de datos...\n\n";
    
    // Conectar sin especificar base de datos para crearla
    $dsn = "mysql:host={$host};charset={$charset}";
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    
    // Crear base de datos
    echo "📁 Creando base de datos 'apartamentos_cyl'...\n";
    $pdo->exec("CREATE DATABASE IF NOT EXISTS apartamentos_cyl CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE apartamentos_cyl");
    echo "✅ Base de datos creada\n\n";
    
    // Leer y ejecutar el script SQL
    $sqlFile = __DIR__ . '/setup_database.sql';
    if (!file_exists($sqlFile)) {
        throw new Exception("No se encontró el archivo setup_database.sql");
    }
    
    $sql = file_get_contents($sqlFile);
    
    // Dividir en statements individuales
    $statements = array_filter(
        array_map('trim', explode(';', $sql)),
        function($stmt) {
            return !empty($stmt) && !preg_match('/^--/', $stmt);
        }
    );
    
    echo "📋 Ejecutando " . count($statements) . " comandos SQL...\n";
    
    foreach ($statements as $statement) {
        if (trim($statement)) {
            try {
                $pdo->exec($statement);
            } catch (PDOException $e) {
                // Ignorar errores de DROP TABLE si no existe
                if (strpos($e->getMessage(), "doesn't exist") === false) {
                    echo "⚠️  Error en: " . substr($statement, 0, 50) . "...\n";
                    echo "   " . $e->getMessage() . "\n";
                }
            }
        }
    }
    
    echo "✅ Comandos SQL ejecutados\n\n";
    
    // Verificar que todo se creó correctamente
    echo "🔍 Verificando instalación...\n";
    
    $tables = ['usuarios', 'apartamentos', 'reservas', 'favoritos'];
    foreach ($tables as $table) {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM $table");
        $count = $stmt->fetch()['count'];
        echo "   📊 Tabla '$table': $count registros\n";
    }
    
    echo "\n🎉 ¡Base de datos configurada correctamente!\n\n";
    echo "📝 Credenciales de administrador:\n";
    echo "   Email: admin@apartamentoscyl.es\n";
    echo "   Password: Admin123!\n\n";
    echo "🚀 Ya puedes usar el panel de administrador\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "\n🔧 Verifica que:\n";
    echo "   - MySQL esté ejecutándose\n";
    echo "   - Las credenciales sean correctas\n";
    echo "   - El usuario tenga permisos para crear bases de datos\n";
}
?>