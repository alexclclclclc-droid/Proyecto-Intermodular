<?php
/**
 * Script para sincronizar apartamentos desde la API de Castilla y León
 * VERSIÓN MEJORADA
 */

// Configurar tiempo de ejecución
set_time_limit(600); // 10 minutos
ini_set('memory_limit', '512M');
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../services/ApiSyncService.php';

// Headers para permitir streaming de output
header('Content-Type: text/html; charset=utf-8');
header('X-Accel-Buffering: no'); // Deshabilitar buffering en nginx

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sincronización de Apartamentos</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            max-width: 800px;
            margin: 40px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            border-bottom: 3px solid #007bff;
            padding-bottom: 10px;
        }
        .log-container {
            background: #1e1e1e;
            color: #d4d4d4;
            padding: 20px;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            max-height: 500px;
            overflow-y: auto;
            margin: 20px 0;
        }
        .log-entry {
            margin: 5px 0;
            line-height: 1.6;
        }
        .log-timestamp {
            color: #858585;
        }
        .log-info {
            color: #4ec9b0;
        }
        .log-success {
            color: #4caf50;
        }
        .log-warning {
            color: #ff9800;
        }
        .log-error {
            color: #f44336;
        }
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }
        .stat-card {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 6px;
            text-align: center;
        }
        .stat-value {
            font-size: 32px;
            font-weight: bold;
            color: #007bff;
        }
        .stat-label {
            font-size: 14px;
            color: #666;
            margin-top: 5px;
        }
        .progress-bar {
            width: 100%;
            height: 30px;
            background: #e0e0e0;
            border-radius: 15px;
            overflow: hidden;
            margin: 20px 0;
        }
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #007bff, #0056b3);
            transition: width 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 14px;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            font-weight: 500;
            transition: background 0.3s;
        }
        .btn:hover {
            background: #0056b3;
        }
        .status {
            padding: 10px 20px;
            border-radius: 4px;
            margin: 15px 0;
            font-weight: 500;
        }
        .status-running {
            background: #fff3cd;
            color: #856404;
            border-left: 4px solid #ffc107;
        }
        .status-success {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }
        .status-error {
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔄 Sincronización de Apartamentos Turísticos</h1>
        
        <div class="status status-running">
            <strong>⏳ Sincronización en progreso...</strong><br>
            Por favor, no cierre esta ventana hasta que finalice el proceso.
        </div>

        <div class="progress-bar">
            <div class="progress-fill" id="progress" style="width: 0%">0%</div>
        </div>
        
        <div class="stats">
            <div class="stat-card">
                <div class="stat-value" id="stat-procesados">0</div>
                <div class="stat-label">Procesados</div>
            </div>
            <div class="stat-card">
                <div class="stat-value" id="stat-nuevos">0</div>
                <div class="stat-label">Nuevos</div>
            </div>
            <div class="stat-card">
                <div class="stat-value" id="stat-actualizados">0</div>
                <div class="stat-label">Actualizados</div>
            </div>
            <div class="stat-card">
                <div class="stat-value" id="stat-errores">0</div>
                <div class="stat-label">Errores</div>
            </div>
        </div>
        
        <div class="log-container" id="log-container">
            <div class="log-entry log-info">Iniciando sincronización...</div>
        </div>
        
        <div id="final-status"></div>
    </div>

    <script>
        const logContainer = document.getElementById('log-container');
        const progress = document.getElementById('progress');
        const finalStatus = document.getElementById('final-status');
        
        function updateStats(stats) {
            document.getElementById('stat-procesados').textContent = stats.procesados || 0;
            document.getElementById('stat-nuevos').textContent = stats.nuevos || 0;
            document.getElementById('stat-actualizados').textContent = stats.actualizados || 0;
            document.getElementById('stat-errores').textContent = stats.errores || 0;
        }
        
        function updateProgress(current, total) {
            const percentage = total > 0 ? Math.round((current / total) * 100) : 0;
            progress.style.width = percentage + '%';
            progress.textContent = percentage + '%';
        }
        
        function addLogEntry(message, type = 'info') {
            const entry = document.createElement('div');
            entry.className = `log-entry log-${type}`;
            entry.textContent = message;
            logContainer.appendChild(entry);
            logContainer.scrollTop = logContainer.scrollHeight;
        }
        
        function showFinalStatus(success, result) {
            const statusClass = success ? 'status-success' : 'status-error';
            const icon = success ? '✅' : '❌';
            const title = success ? 'Sincronización completada' : 'Sincronización finalizada con errores';
            
            finalStatus.innerHTML = `
                <div class="status ${statusClass}">
                    <strong>${icon} ${title}</strong><br>
                    ${result.procesados} registros procesados<br>
                    ${result.nuevos} nuevos • ${result.actualizados} actualizados • ${result.errores} errores
                </div>
                <br>
                <a href="../views/mapa.php" class="btn">📍 Ver mapa de apartamentos</a>
                <a href="../views/apartamentos.php" class="btn">🏠 Ver lista de apartamentos</a>
            `;
        }
    </script>

<?php
// Iniciar sincronización
flush();
ob_flush();

try {
    $service = new ApiSyncService();
    
    // Callback para reportar progreso
    $currentProgress = 0;
    $totalApartments = 0;
    
    // Ejecutar sincronización
    $resultado = $service->sincronizar();
    
    // Mostrar resultados finales
    echo "<script>\n";
    echo "updateStats(" . json_encode([
        'procesados' => $resultado['procesados'],
        'nuevos' => $resultado['nuevos'],
        'actualizados' => $resultado['actualizados'],
        'errores' => $resultado['errores']
    ]) . ");\n";
    echo "updateProgress(100, 100);\n";
    echo "showFinalStatus(" . ($resultado['success'] ? 'true' : 'false') . ", " . json_encode($resultado) . ");\n";
    
    // Mostrar logs
    if (!empty($resultado['log'])) {
        foreach ($resultado['log'] as $logEntry) {
            $logType = 'info';
            if (stripos($logEntry, 'error') !== false) {
                $logType = 'error';
            } elseif (stripos($logEntry, 'completad') !== false) {
                $logType = 'success';
            } elseif (stripos($logEntry, 'warning') !== false || stripos($logEntry, 'advertencia') !== false) {
                $logType = 'warning';
            }
            
            echo "addLogEntry(" . json_encode($logEntry) . ", '{$logType}');\n";
        }
    }
    
    echo "</script>\n";
    
    // Resumen en consola PHP
    echo "\n<!-- Resumen de sincronización:\n";
    echo "Procesados: {$resultado['procesados']}\n";
    echo "Nuevos: {$resultado['nuevos']}\n";
    echo "Actualizados: {$resultado['actualizados']}\n";
    echo "Errores: {$resultado['errores']}\n";
    echo "-->\n";
    
} catch (Exception $e) {
    echo "<script>\n";
    echo "addLogEntry('ERROR FATAL: " . addslashes($e->getMessage()) . "', 'error');\n";
    echo "showFinalStatus(false, { procesados: 0, nuevos: 0, actualizados: 0, errores: 1 });\n";
    echo "</script>\n";
    
    error_log("Error en sincronización: " . $e->getMessage());
}
?>

</body>
</html>