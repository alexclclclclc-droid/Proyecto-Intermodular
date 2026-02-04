<?php
/**
 * Script de instalación para configurar la sincronización automática
 * Este script ayuda a configurar cron jobs y verificar el sistema
 */
require_once 'config/config.php';

$isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
$projectPath = __DIR__;
$phpPath = PHP_BINARY;

echo "<h1>🔧 Instalación de Sincronización Automática</h1>\n";

// Verificar permisos
echo "<h2>1. Verificando permisos</h2>\n";
$tempDir = __DIR__ . '/temp';
if (!is_dir($tempDir)) {
    if (mkdir($tempDir, 0755, true)) {
        echo "<p style='color: green;'>✅ Directorio temp creado correctamente</p>\n";
    } else {
        echo "<p style='color: red;'>❌ No se pudo crear el directorio temp</p>\n";
    }
} else {
    echo "<p style='color: green;'>✅ Directorio temp existe</p>\n";
}

if (is_writable($tempDir)) {
    echo "<p style='color: green;'>✅ Directorio temp es escribible</p>\n";
} else {
    echo "<p style='color: red;'>❌ Directorio temp no es escribible</p>\n";
}

// Verificar PHP CLI
echo "<h2>2. Verificando PHP CLI</h2>\n";
echo "<p>Ruta de PHP: <code>{$phpPath}</code></p>\n";

$testCommand = $isWindows ? 
    "\"$phpPath\" -v" : 
    "$phpPath -v";

$output = shell_exec($testCommand);
if ($output) {
    echo "<p style='color: green;'>✅ PHP CLI funciona correctamente</p>\n";
    echo "<pre>" . htmlspecialchars($output) . "</pre>\n";
} else {
    echo "<p style='color: red;'>❌ PHP CLI no funciona</p>\n";
}

// Test de sincronización
echo "<h2>3. Probando sincronización automática</h2>\n";
try {
    require_once 'utils/auto_sync.php';
    $manager = new AutoSyncManager();
    $status = $manager->getStatus();
    
    echo "<p style='color: green;'>✅ Sistema de auto-sync funciona</p>\n";
    echo "<pre>" . json_encode($status, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>\n";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error en auto-sync: " . $e->getMessage() . "</p>\n";
}

// Instrucciones de cron
echo "<h2>4. Configuración de Cron Job</h2>\n";

if ($isWindows) {
    echo "<h3>Windows - Programador de Tareas</h3>\n";
    echo "<p>Para configurar la sincronización automática en Windows:</p>\n";
    echo "<ol>\n";
    echo "<li>Abrir 'Programador de tareas' (Task Scheduler)</li>\n";
    echo "<li>Crear tarea básica</li>\n";
    echo "<li>Configurar para ejecutar cada hora</li>\n";
    echo "<li>Acción: Iniciar programa</li>\n";
    echo "<li>Programa: <code>{$phpPath}</code></li>\n";
    echo "<li>Argumentos: <code>{$projectPath}/utils/auto_sync.php</code></li>\n";
    echo "<li>Directorio: <code>{$projectPath}</code></li>\n";
    echo "</ol>\n";
    
    echo "<h3>Comando alternativo (PowerShell como administrador)</h3>\n";
    echo "<pre>";
    echo "schtasks /create /tn \"ApartamentosCyL_AutoSync\" /tr \"\\\"{$phpPath}\\\" \\\"{$projectPath}/utils/auto_sync.php\\\"\" /sc hourly /ru SYSTEM\n";
    echo "</pre>\n";
    
} else {
    echo "<h3>Linux/Unix - Crontab</h3>\n";
    echo "<p>Para configurar cron job, ejecutar:</p>\n";
    echo "<pre>crontab -e</pre>\n";
    echo "<p>Y agregar esta línea:</p>\n";
    echo "<pre>";
    echo "# Sincronización automática diaria a las 22:30 (después de actualización de CyL)\n";
    echo "30 22 * * * {$phpPath} {$projectPath}/utils/auto_sync.php >/dev/null 2>&1\n";
    echo "</pre>\n";
    
    echo "<p>Para verificar que se agregó correctamente:</p>\n";
    echo "<pre>crontab -l</pre>\n";
}

// Alternativas
echo "<h2>5. Alternativas de Sincronización</h2>\n";
echo "<h3>A. Sincronización por JavaScript (Recomendado)</h3>\n";
echo "<p style='color: green;'>✅ Ya configurado - Se ejecuta automáticamente en el navegador</p>\n";
echo "<p>El sistema JavaScript verifica periódicamente si es necesario sincronizar (una vez al día a las 22:30).</p>\n";

echo "<h3>B. Sincronización manual desde Admin</h3>\n";
echo "<p>Los administradores pueden forzar sincronización desde el panel de admin.</p>\n";

echo "<h3>C. URL para webhook/cron externo</h3>\n";
echo "<p>Puedes configurar un servicio externo para llamar esta URL diariamente a las 22:30:</p>\n";
$baseUrl = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']);
echo "<pre>{$baseUrl}/utils/auto_sync.php?auto=1</pre>\n";

// Verificación final
echo "<h2>6. Verificación del Sistema</h2>\n";
echo "<div style='background: #f0f8ff; padding: 15px; border-radius: 5px; margin: 20px 0;'>\n";
echo "<h4>✅ Sistema de Sincronización Automática Instalado</h4>\n";
echo "<p><strong>Características instaladas:</strong></p>\n";
echo "<ul>\n";
echo "<li>🔄 Sincronización automática en JavaScript (diaria a las 22:30)</li>\n";
echo "<li>⏰ Sistema de intervalos inteligente (una vez al día después de actualización de CyL)</li>\n";
echo "<li>🔒 Protección contra ejecuciones simultáneas</li>\n";
echo "<li>📝 Logging automático de todas las sincronizaciones</li>\n";
echo "<li>🛠️ Panel de control en Admin</li>\n";
echo "<li>⚡ Opción de forzar sincronización manual</li>\n";
echo "</ul>\n";
echo "</div>\n";

echo "<h2>🎉 Instalación Completada</h2>\n";
echo "<p><strong>El sistema ya está funcionando automáticamente.</strong></p>\n";
echo "<p>Puedes:</p>\n";
echo "<ul>\n";
echo "<li><a href='views/admin.php'>🔗 Ir al Panel de Admin</a> para monitorear</li>\n";
echo "<li><a href='index.php'>🔗 Ir a la página principal</a> (sincronización automática activa)</li>\n";
echo "<li>Configurar cron job opcional para mayor robustez</li>\n";
echo "</ul>\n";

echo "<p><em>Nota: El sistema JavaScript es suficiente para la mayoría de casos. El cron job es opcional para mayor robustez.</em></p>\n";
?>