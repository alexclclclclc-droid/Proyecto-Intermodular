<?php
/**
 * API para manejo de coordenadas GPS
 */
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/config.php';
require_once '../utils/gps_generator.php';

try {
    $action = $_GET['action'] ?? '';
    
    switch ($action) {
        case 'generar':
            // Generar coordenadas para todos los apartamentos sin GPS
            $resultado = GPSGenerator::generarCoordenadasAutomaticamente();
            echo json_encode($resultado);
            break;
            
        case 'generar_apartamento':
            // Generar coordenadas para un apartamento específico
            $id = $_GET['id'] ?? 0;
            if (!$id) {
                throw new Exception('ID de apartamento requerido');
            }
            
            $resultado = GPSGenerator::generarCoordenadasParaApartamento($id);
            echo json_encode($resultado);
            break;
            
        case 'verificar':
            // Verificar cuántos apartamentos necesitan coordenadas
            $resultado = GPSGenerator::verificarApartamentosSinGPS();
            echo json_encode($resultado);
            break;
            
        case 'auto_generar':
            // Verificar y generar automáticamente si es necesario
            $verificacion = GPSGenerator::verificarApartamentosSinGPS();
            
            if ($verificacion['success'] && $verificacion['necesita_generacion']) {
                $resultado = GPSGenerator::generarCoordenadasAutomaticamente();
                echo json_encode([
                    'success' => true,
                    'auto_generated' => true,
                    'verificacion' => $verificacion,
                    'generacion' => $resultado
                ]);
            } else {
                echo json_encode([
                    'success' => true,
                    'auto_generated' => false,
                    'verificacion' => $verificacion,
                    'message' => 'No se necesita generar coordenadas'
                ]);
            }
            break;
            
        default:
            throw new Exception('Acción no válida');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>