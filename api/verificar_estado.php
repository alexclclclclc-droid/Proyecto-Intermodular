<?php
/**
 * Script de verificación del estado de la base de datos
 * Verifica apartamentos, coordenadas GPS y distribución por provincias
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../dao/ApartamentoDAO.php';

header('Content-Type: text/html; charset=utf-8');

$apartamentoDAO = new ApartamentoDAO();

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificación Base de Datos - Apartamentos</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            max-width: 1200px;
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
            margin-bottom: 30px;
        }
        h2 {
            color: #555;
            margin-top: 30px;
            border-bottom: 2px solid #ddd;
            padding-bottom: 8px;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .stat-card.success {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
        }
        .stat-card.warning {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }
        .stat-card.info {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }
        .stat-value {
            font-size: 48px;
            font-weight: bold;
            margin: 10px 0;
        }
        .stat-label {
            font-size: 14px;
            opacity: 0.9;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        th {
            background: #007bff;
            color: white;
            padding: 12px;
            text-align: left;
            font-weight: 600;
        }
        td {
            padding: 12px;
            border-bottom: 1px solid #ddd;
        }
        tr:hover {
            background: #f8f9fa;
        }
        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }
        .badge-success {
            background: #d4edda;
            color: #155724;
        }
        .badge-warning {
            background: #fff3cd;
            color: #856404;
        }
        .badge-danger {
            background: #f8d7da;
            color: #721c24;
        }
        .progress-bar {
            width: 100%;
            height: 10px;
            background: #e0e0e0;
            border-radius: 5px;
            overflow: hidden;
            margin-top: 5px;
        }
        .progress-fill {
            height: 100%;
            background: #007bff;
            transition: width 0.3s ease;
        }
        .alert {
            padding: 15px 20px;
            border-radius: 6px;
            margin: 20px 0;
        }
        .alert-info {
            background: #d1ecf1;
            color: #0c5460;
            border-left: 4px solid #17a2b8;
        }
        .alert-warning {
            background: #fff3cd;
            color: #856404;
            border-left: 4px solid #ffc107;
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 5px;
            transition: background 0.3s;
        }
        .btn:hover {
            background: #0056b3;
        }
        .btn-success {
            background: #28a745;
        }
        .btn-success:hover {
            background: #218838;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Verificación de Base de Datos - Apartamentos Turísticos</h1>

        <?php
        try {
            // Estadísticas generales
            $totalApartamentos = $apartamentoDAO->contarTotal();
            $conGPS = $apartamentoDAO->contarConGPS();
            $sinGPS = $totalApartamentos - $conGPS;
            $porcentajeGPS = $totalApartamentos > 0 ? round(($conGPS / $totalApartamentos) * 100, 1) : 0;
            
            // Obtener provincias
            $provincias = $apartamentoDAO->obtenerProvincias();
            $numProvincias = count($provincias);
        ?>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-label">Total Apartamentos</div>
                <div class="stat-value"><?= number_format($totalApartamentos) ?></div>
            </div>
            <div class="stat-card success">
                <div class="stat-label">Con GPS</div>
                <div class="stat-value"><?= number_format($conGPS) ?></div>
                <small><?= $porcentajeGPS ?>%</small>
            </div>
            <div class="stat-card <?= $sinGPS > 0 ? 'warning' : 'success' ?>">
                <div class="stat-label">Sin GPS</div>
                <div class="stat-value"><?= number_format($sinGPS) ?></div>
            </div>
            <div class="stat-card info">
                <div class="stat-label">Provincias</div>
                <div class="stat-value"><?= $numProvincias ?></div>
            </div>
        </div>

        <?php if ($totalApartamentos === 0): ?>
            <div class="alert alert-warning">
                <strong>⚠️ Atención:</strong> No hay apartamentos en la base de datos. 
                <br><br>
                <strong>Acción requerida:</strong> Ejecuta la sincronización para cargar datos desde la API.
                <br><br>
                <a href="sync_improved.php" class="btn btn-success">🔄 Sincronizar Apartamentos</a>
            </div>
        <?php elseif ($porcentajeGPS < 50): ?>
            <div class="alert alert-warning">
                <strong>⚠️ Advertencia:</strong> Menos del 50% de los apartamentos tienen coordenadas GPS.
                Esto puede afectar la visualización en el mapa.
                <br><br>
                <a href="sync_improved.php" class="btn">🔄 Volver a Sincronizar</a>
            </div>
        <?php else: ?>
            <div class="alert alert-success">
                <strong>✅ Estado óptimo:</strong> <?= $porcentajeGPS ?>% de los apartamentos tienen coordenadas GPS válidas.
                El mapa funcionará correctamente.
            </div>
        <?php endif; ?>

        <h2>📊 Distribución por Provincia</h2>
        
        <?php if (!empty($provincias)): ?>
            <table>
                <thead>
                    <tr>
                        <th>Provincia</th>
                        <th>Total Apartamentos</th>
                        <th>Con GPS</th>
                        <th>% GPS</th>
                        <th>Progreso</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($provincias as $prov): 
                        $gpsCount = $prov['con_gps'] ?? 0;
                        $totalProv = $prov['total'];
                        $percentage = $totalProv > 0 ? round(($gpsCount / $totalProv) * 100) : 0;
                        $badgeClass = $percentage >= 80 ? 'badge-success' : ($percentage >= 50 ? 'badge-warning' : 'badge-danger');
                    ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($prov['provincia']) ?></strong></td>
                            <td><?= number_format($totalProv) ?></td>
                            <td><?= number_format($gpsCount) ?></td>
                            <td>
                                <span class="badge <?= $badgeClass ?>"><?= $percentage ?>%</span>
                            </td>
                            <td>
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: <?= $percentage ?>%"></div>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="alert alert-info">
                No hay datos de provincias disponibles.
            </div>
        <?php endif; ?>

        <h2>🗺️ Verificación del Mapa</h2>
        
        <div class="alert alert-info">
            <strong>ℹ️ Requisitos para el mapa:</strong>
            <ul>
                <li>✅ Apartamentos en la base de datos: <strong><?= $totalApartamentos > 0 ? 'SÍ' : 'NO' ?></strong></li>
                <li>✅ Apartamentos con GPS: <strong><?= $conGPS > 0 ? 'SÍ' : 'NO' ?></strong></li>
                <li>✅ Provincias disponibles: <strong><?= $numProvincias > 0 ? 'SÍ' : 'NO' ?></strong></li>
                <li>✅ Porcentaje GPS adecuado (>50%): <strong><?= $porcentajeGPS >= 50 ? 'SÍ' : 'NO' ?></strong></li>
            </ul>
        </div>

        <?php if ($totalApartamentos > 0 && $conGPS > 0): ?>
            <div class="alert alert-success">
                <strong>✅ El mapa está listo para usar</strong>
                <br><br>
                <a href="../views/mapa.php" class="btn btn-success">🗺️ Abrir Mapa de Apartamentos</a>
                <a href="../views/apartamentos.php" class="btn">🏠 Ver Lista de Apartamentos</a>
            </div>
        <?php endif; ?>

        <h2>🔧 Acciones Disponibles</h2>
        
        <div style="margin: 20px 0;">
            <a href="sync_improved.php" class="btn">🔄 Sincronizar Apartamentos</a>
            <a href="apartamentos.php?action=provincias" class="btn">📋 Ver API Provincias</a>
            <a href="apartamentos.php?action=mapa" class="btn">🗺️ Ver API Mapa</a>
            <a href="javascript:location.reload()" class="btn">🔃 Actualizar Estado</a>
        </div>

        <?php
        } catch (Exception $e) {
            echo '<div class="alert alert-warning">';
            echo '<strong>⚠️ Error al verificar la base de datos:</strong><br>';
            echo htmlspecialchars($e->getMessage());
            echo '</div>';
        }
        ?>

        <hr style="margin: 40px 0;">
        
        <p style="color: #666; font-size: 14px; text-align: center;">
            <strong>Última verificación:</strong> <?= date('d/m/Y H:i:s') ?>
        </p>
    </div>
</body>
</html>