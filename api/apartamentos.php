<?php
/**
 * API REST para Apartamentos
 * Endpoints para comunicación asíncrona con fetch()
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Manejar preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../dao/ApartamentoDAO.php';
require_once __DIR__ . '/../utils/gps_generator.php';

// Generar coordenadas GPS automáticamente si es necesario (silencioso)
try {
    $verificacion = GPSGenerator::verificarApartamentosSinGPS();
    if ($verificacion['success'] && $verificacion['necesita_generacion']) {
        GPSGenerator::generarCoordenadasAutomaticamente();
    }
} catch (Exception $e) {
    // Error silencioso - no interrumpir la API
    error_log("Error generando GPS automáticamente en API: " . $e->getMessage());
}

$apartamentoDAO = new ApartamentoDAO();
$action = $_GET['action'] ?? 'listar';

try {
    switch ($action) {
        
        // Listar apartamentos con filtros y paginación
        case 'listar':
            $filtros = [
                'provincia' => $_GET['provincia'] ?? null,
                'municipio' => $_GET['municipio'] ?? null,
                'nombre' => $_GET['nombre'] ?? null,
                'capacidad_min' => $_GET['capacidad_min'] ?? null,
                'accesible' => isset($_GET['accesible']) && $_GET['accesible'] === '1',
                'q_calidad' => isset($_GET['q_calidad']) && $_GET['q_calidad'] === '1'
            ];
            
            // Limpiar filtros vacíos
            $filtros = array_filter($filtros, fn($v) => $v !== null && $v !== '');
            
            $page = max(1, (int)($_GET['page'] ?? 1));
            $limit = min(50, max(1, (int)($_GET['limit'] ?? 12)));
            $offset = ($page - 1) * $limit;
            
            $apartamentos = $apartamentoDAO->buscar($filtros, $limit, $offset);
            $total = $apartamentoDAO->contarBusqueda($filtros);
            $totalPages = ceil($total / $limit);
            
            jsonResponse([
                'success' => true,
                'data' => array_map(fn($a) => $a->toArray(), $apartamentos),
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => $total,
                    'total_pages' => $totalPages,
                    'has_more' => $page < $totalPages
                ]
            ]);
            break;

        // Obtener apartamento por ID
        case 'detalle':
            $id = (int)($_GET['id'] ?? 0);
            if ($id <= 0) {
                jsonResponse(['success' => false, 'error' => 'ID inválido'], 400);
            }
            
            $apartamento = $apartamentoDAO->obtenerPorId($id);
            if (!$apartamento) {
                jsonResponse(['success' => false, 'error' => 'Apartamento no encontrado'], 404);
            }
            
            jsonResponse([
                'success' => true,
                'data' => $apartamento->toArray()
            ]);
            break;

        // Obtener lista de provincias
        case 'provincias':
            $provincias = $apartamentoDAO->obtenerProvincias();
            jsonResponse([
                'success' => true,
                'data' => $provincias
            ]);
            break;

        // Obtener municipios de una provincia
        case 'municipios':
            $provincia = $_GET['provincia'] ?? '';
            if (empty($provincia)) {
                jsonResponse(['success' => false, 'error' => 'Provincia requerida'], 400);
            }
            
            $municipios = $apartamentoDAO->obtenerMunicipios($provincia);
            jsonResponse([
                'success' => true,
                'data' => $municipios
            ]);
            break;

        // Obtener datos para el mapa
        case 'mapa':
            $filtros = [
                'provincia' => $_GET['provincia'] ?? null
            ];
            $filtros = array_filter($filtros);
            
            $puntos = $apartamentoDAO->obtenerParaMapa($filtros);
            jsonResponse([
                'success' => true,
                'data' => $puntos,
                'total' => count($puntos)
            ]);
            break;

        // Obtener apartamentos destacados
        case 'destacados':
            $limit = min(12, max(1, (int)($_GET['limit'] ?? 6)));
            $apartamentos = $apartamentoDAO->obtenerDestacados($limit);
            jsonResponse([
                'success' => true,
                'data' => array_map(fn($a) => $a->toArray(), $apartamentos)
            ]);
            break;

        // Estadísticas por provincia
        case 'estadisticas':
            $estadisticas = $apartamentoDAO->obtenerEstadisticasPorProvincia();
            $total = $apartamentoDAO->contarTotal();
            jsonResponse([
                'success' => true,
                'data' => [
                    'total' => $total,
                    'por_provincia' => $estadisticas
                ]
            ]);
            break;

        // Buscar cercanos a coordenadas
        case 'cercanos':
            $lat = (float)($_GET['lat'] ?? 0);
            $lng = (float)($_GET['lng'] ?? 0);
            $radio = min(100, max(1, (int)($_GET['radio'] ?? 10)));
            
            if ($lat === 0.0 || $lng === 0.0) {
                jsonResponse(['success' => false, 'error' => 'Coordenadas inválidas'], 400);
            }
            
            $apartamentos = $apartamentoDAO->buscarCercanos($lat, $lng, $radio);
            jsonResponse([
                'success' => true,
                'data' => array_map(fn($a) => $a->toArray(), $apartamentos),
                'total' => count($apartamentos)
            ]);
            break;

        default:
            jsonResponse(['success' => false, 'error' => 'Acción no válida'], 400);
    }

} catch (Exception $e) {
    error_log("API Error: " . $e->getMessage());
    jsonResponse([
        'success' => false,
        'error' => DEBUG_MODE ? $e->getMessage() : 'Error interno del servidor'
    ], 500);
}