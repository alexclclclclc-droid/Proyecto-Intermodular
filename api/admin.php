<?php
/**
 * API REST para funciones de Administrador
 * Gestión de usuarios, estadísticas y operaciones administrativas
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../dao/UsuarioDAO.php';
require_once __DIR__ . '/../dao/ReservaDAO.php';
require_once __DIR__ . '/../dao/ApartamentoDAO.php';

// Verificar que el usuario sea administrador
if (!isLoggedIn() || !isAdmin()) {
    jsonResponse(['success' => false, 'error' => 'No autorizado'], 403);
}

$usuarioDAO = new UsuarioDAO();
$reservaDAO = new ReservaDAO();
$apartamentoDAO = new ApartamentoDAO();

// Get action from GET, POST, or JSON body
$action = $_GET['action'] ?? $_POST['action'] ?? '';

// If no action found in GET/POST, check JSON body
if (empty($action) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    if ($input && isset($input['action'])) {
        $action = $input['action'];
    }
}

// Debug logging (only in debug mode)
if (DEBUG_MODE) {
    error_log("Admin API - Method: " . $_SERVER['REQUEST_METHOD']);
    error_log("Admin API - Action: " . $action);
    error_log("Admin API - GET: " . print_r($_GET, true));
    error_log("Admin API - POST: " . print_r($_POST, true));
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $rawInput = file_get_contents('php://input');
        error_log("Admin API - Raw Input: " . $rawInput);
        $jsonInput = json_decode($rawInput, true);
        error_log("Admin API - JSON Input: " . print_r($jsonInput, true));
    }
}

try {
    switch ($action) {
        
        // Obtener estadísticas del dashboard
        case 'estadisticas':
            $stats = [
                'usuarios' => [
                    'total' => 0,
                    'activos' => 0,
                    'inactivos' => 0,
                    'por_rol' => []
                ],
                'reservas' => [
                    'total' => 0,
                    'por_estado' => [],
                    'este_mes' => 0
                ],
                'apartamentos' => [
                    'total' => 0,
                    'ocupados' => 0,
                    'tasa_ocupacion' => 0
                ],
                'sincronizacion' => [
                    'ultima' => null,
                    'estado' => 'pendiente'
                ]
            ];
            
            // Estadísticas de usuarios
            $usuarios = $usuarioDAO->obtenerTodos();
            $stats['usuarios']['total'] = count($usuarios);
            
            foreach ($usuarios as $usuario) {
                if ($usuario->isActivo()) {
                    $stats['usuarios']['activos']++;
                } else {
                    $stats['usuarios']['inactivos']++;
                }
                
                $rol = $usuario->getRol();
                if (!isset($stats['usuarios']['por_rol'][$rol])) {
                    $stats['usuarios']['por_rol'][$rol] = 0;
                }
                $stats['usuarios']['por_rol'][$rol]++;
            }
            
            // Estadísticas de reservas
            $reservas = $reservaDAO->obtenerTodas(1000, 0); // Obtener muchas para estadísticas
            $stats['reservas']['total'] = count($reservas);
            
            $estadosReserva = ['pendiente', 'confirmada', 'cancelada', 'completada'];
            foreach ($estadosReserva as $estado) {
                $stats['reservas']['por_estado'][$estado] = 0;
            }
            
            $mesActual = date('Y-m');
            foreach ($reservas as $reserva) {
                $estado = $reserva->getEstado();
                if (isset($stats['reservas']['por_estado'][$estado])) {
                    $stats['reservas']['por_estado'][$estado]++;
                }
                
                if (strpos($reserva->getFechaReserva(), $mesActual) === 0) {
                    $stats['reservas']['este_mes']++;
                }
            }
            
            // Estadísticas de apartamentos (versión simplificada y robusta)
            try {
                // Usar consulta SQL directa para mayor confiabilidad
                $db = Database::getInstance()->getConnection();
                
                // Contar apartamentos totales
                $stmt = $db->query("SELECT COUNT(*) FROM apartamentos WHERE activo = TRUE");
                $totalApartamentos = (int)$stmt->fetchColumn();
                $stats['apartamentos']['total'] = $totalApartamentos;
                
                // Calcular apartamentos ocupados para hoy
                $fechaHoy = date('Y-m-d');
                $stmt = $db->prepare("
                    SELECT COUNT(DISTINCT id_apartamento) 
                    FROM reservas 
                    WHERE estado IN ('confirmada', 'pendiente')
                    AND fecha_entrada <= ? 
                    AND fecha_salida > ?
                ");
                $stmt->execute([$fechaHoy, $fechaHoy]);
                $apartamentosOcupados = (int)$stmt->fetchColumn();
                
                $stats['apartamentos']['ocupados'] = $apartamentosOcupados;
                $stats['apartamentos']['disponibles'] = $totalApartamentos - $apartamentosOcupados;
                $stats['apartamentos']['tasa_ocupacion'] = $totalApartamentos > 0 
                    ? round(($apartamentosOcupados / $totalApartamentos) * 100, 1) 
                    : 0;
                
                // Estadísticas mensuales simplificadas
                $inicioMes = date('Y-m-01');
                $finMes = date('Y-m-t');
                
                $stmt = $db->prepare("
                    SELECT 
                        COUNT(DISTINCT id_apartamento) as apartamentos_ocupados,
                        COUNT(id) as total_reservas
                    FROM reservas 
                    WHERE estado IN ('confirmada', 'completada')
                    AND fecha_entrada <= ? 
                    AND fecha_salida >= ?
                ");
                $stmt->execute([$finMes, $inicioMes]);
                $estadisticasMes = $stmt->fetch(PDO::FETCH_ASSOC);
                
                $stats['apartamentos']['ocupacion_mes'] = [
                    'apartamentos_ocupados' => (int)($estadisticasMes['apartamentos_ocupados'] ?? 0),
                    'total_reservas' => (int)($estadisticasMes['total_reservas'] ?? 0),
                    'total_noches' => 0 // Simplificado por ahora
                ];
                
            } catch (Exception $e) {
                // Valores por defecto en caso de error
                $stats['apartamentos']['total'] = 0;
                $stats['apartamentos']['ocupados'] = 0;
                $stats['apartamentos']['disponibles'] = 0;
                $stats['apartamentos']['tasa_ocupacion'] = 0;
                $stats['apartamentos']['ocupacion_mes'] = [
                    'apartamentos_ocupados' => 0,
                    'total_reservas' => 0,
                    'total_noches' => 0
                ];
            }
            
            jsonResponse([
                'success' => true,
                'data' => $stats
            ]);
            break;

        // Gestión de usuarios - Listar todos
        case 'usuarios_listar':
            $filtros = [
                'rol' => $_GET['rol'] ?? '',
                'estado' => $_GET['estado'] ?? '',
                'email' => $_GET['email'] ?? ''
            ];
            
            $usuarios = $usuarioDAO->obtenerConFiltros($filtros);
            $usuariosArray = array_map(function($usuario) {
                return $usuario->toArray();
            }, $usuarios);
            
            jsonResponse([
                'success' => true,
                'data' => $usuariosArray,
                'filtros_aplicados' => array_filter($filtros)
            ]);
            break;

        // Gestión de usuarios - Ver detalle
        case 'usuario_detalle':
            $id = $_GET['id'] ?? null;
            if (!$id) {
                jsonResponse(['success' => false, 'error' => 'ID de usuario no proporcionado'], 400);
            }
            
            $usuario = $usuarioDAO->obtenerPorId($id);
            if (!$usuario) {
                jsonResponse(['success' => false, 'error' => 'Usuario no encontrado'], 404);
            }
            
            // Obtener estadísticas adicionales del usuario si es necesario
            $reservaDAO = new ReservaDAO();
            $totalReservas = $reservaDAO->contarReservasPorUsuario($id);
            
            $usuarioData = $usuario->toArray();
            $usuarioData['total_reservas'] = $totalReservas;
            
            jsonResponse([
                'success' => true,
                'data' => $usuarioData
            ]);
            break;

        // Gestión de usuarios - Cambiar estado
        case 'usuario_cambiar_estado':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                jsonResponse(['success' => false, 'error' => 'Método no permitido'], 405);
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input) {
                $input = $_POST; // Fallback to $_POST if JSON decode fails
            }
            
            $id = (int)($input['id'] ?? 0);
            $activo = (bool)($input['activo'] ?? false);
            
            if ($id <= 0) {
                jsonResponse(['success' => false, 'error' => 'ID de usuario inválido'], 400);
            }
            
            // No permitir desactivar el propio usuario
            if ($id === $_SESSION['usuario_id']) {
                jsonResponse(['success' => false, 'error' => 'No puedes cambiar tu propio estado'], 400);
            }
            
            if ($usuarioDAO->cambiarEstado($id, $activo)) {
                jsonResponse([
                    'success' => true,
                    'message' => $activo ? 'Usuario activado' : 'Usuario desactivado'
                ]);
            } else {
                jsonResponse(['success' => false, 'error' => 'Error al cambiar estado'], 500);
            }
            break;

        // Gestión de usuarios - Eliminar
        case 'usuario_eliminar':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                jsonResponse(['success' => false, 'error' => 'Método no permitido'], 405);
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input) {
                $input = $_POST; // Fallback to $_POST if JSON decode fails
            }
            
            $id = (int)($input['id'] ?? 0);
            
            if ($id <= 0) {
                jsonResponse(['success' => false, 'error' => 'ID de usuario inválido'], 400);
            }
            
            // No permitir eliminar el propio usuario
            if ($id === $_SESSION['usuario_id']) {
                jsonResponse(['success' => false, 'error' => 'No puedes eliminar tu propia cuenta'], 400);
            }
            
            if ($usuarioDAO->eliminar($id)) {
                jsonResponse([
                    'success' => true,
                    'message' => 'Usuario eliminado correctamente'
                ]);
            } else {
                jsonResponse(['success' => false, 'error' => 'Error al eliminar usuario'], 500);
            }
            break;

        // Gestión de reservas - Listar todas
        case 'reservas_listar':
            $filtros = [
                'estado' => $_GET['estado'] ?? '',
                'fecha_desde' => $_GET['fecha_desde'] ?? '',
                'fecha_hasta' => $_GET['fecha_hasta'] ?? '',
                'usuario_email' => $_GET['usuario_email'] ?? ''
            ];
            
            $reservas = $reservaDAO->obtenerConFiltros($filtros);
            $reservasArray = array_map(function($reserva) {
                $data = $reserva->toArray();
                // Asegurar que tenemos los campos necesarios para la UI
                $data['usuario_email'] = $data['email_usuario'] ?? 'N/A';
                $data['apartamento_nombre'] = $data['nombre_apartamento'] ?? 'N/A';
                $data['fecha_creacion'] = $data['fecha_reserva'] ?? date('Y-m-d H:i:s');
                return $data;
            }, $reservas);
            
            jsonResponse([
                'success' => true,
                'data' => $reservasArray,
                'filtros_aplicados' => array_filter($filtros)
            ]);
            break;

        // Gestión de usuarios - Cambiar rol
        case 'usuario_cambiar_rol':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                jsonResponse(['success' => false, 'error' => 'Método no permitido'], 405);
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input) {
                $input = $_POST; // Fallback to $_POST if JSON decode fails
            }
            
            $id = (int)($input['id'] ?? 0);
            $rol = $input['rol'] ?? '';
            
            if ($id <= 0) {
                jsonResponse(['success' => false, 'error' => 'ID de usuario inválido'], 400);
            }
            
            if (!in_array($rol, ['usuario', 'admin'])) {
                jsonResponse(['success' => false, 'error' => 'Rol inválido'], 400);
            }
            
            // No permitir cambiar el propio rol
            if ($id === $_SESSION['usuario_id']) {
                jsonResponse(['success' => false, 'error' => 'No puedes cambiar tu propio rol'], 400);
            }
            
            if ($usuarioDAO->cambiarRol($id, $rol)) {
                jsonResponse([
                    'success' => true,
                    'message' => 'Rol de usuario actualizado'
                ]);
            } else {
                jsonResponse(['success' => false, 'error' => 'Error al cambiar rol'], 500);
            }
            break;

        // Obtener detalle de reserva
        case 'reserva_detalle':
            $id = (int)($_GET['id'] ?? 0);
            
            if ($id <= 0) {
                jsonResponse(['success' => false, 'error' => 'ID de reserva inválido'], 400);
            }
            
            $reserva = $reservaDAO->obtenerPorId($id);
            if (!$reserva) {
                jsonResponse(['success' => false, 'error' => 'Reserva no encontrada'], 404);
            }
            
            jsonResponse([
                'success' => true,
                'data' => $reserva->toArray()
            ]);
            break;

        // Eliminar reserva
        case 'reserva_eliminar':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                jsonResponse(['success' => false, 'error' => 'Método no permitido'], 405);
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input) {
                $input = $_POST; // Fallback to $_POST if JSON decode fails
            }
            
            $id = (int)($input['id'] ?? 0);
            
            if ($id <= 0) {
                jsonResponse(['success' => false, 'error' => 'ID de reserva inválido'], 400);
            }
            
            if ($reservaDAO->eliminar($id)) {
                jsonResponse([
                    'success' => true,
                    'message' => 'Reserva eliminada correctamente'
                ]);
            } else {
                jsonResponse(['success' => false, 'error' => 'Error al eliminar reserva'], 500);
            }
            break;

        // Gestión de reservas - Cambiar estado
        case 'reserva_cambiar_estado':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                jsonResponse(['success' => false, 'error' => 'Método no permitido'], 405);
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input) {
                $input = $_POST; // Fallback to $_POST if JSON decode fails
            }
            
            $id = (int)($input['id'] ?? 0);
            $estado = $input['estado'] ?? '';
            
            if ($id <= 0) {
                jsonResponse(['success' => false, 'error' => 'ID de reserva inválido'], 400);
            }
            
            if (!in_array($estado, ['pendiente', 'confirmada', 'cancelada', 'completada'])) {
                jsonResponse(['success' => false, 'error' => 'Estado inválido'], 400);
            }
            
            if ($reservaDAO->actualizarEstado($id, $estado)) {
                jsonResponse([
                    'success' => true,
                    'message' => 'Estado de reserva actualizado'
                ]);
            } else {
                jsonResponse(['success' => false, 'error' => 'Error al actualizar estado'], 500);
            }
            break;

        case 'sync_estado':
            // Simular estado de sincronización
            $estado = [
                'ultima_sincronizacion' => date('Y-m-d H:i:s', strtotime('-2 hours')),
                'estado' => 'exitosa',
                'registros_procesados' => 1247,
                'registros_nuevos' => 23,
                'registros_actualizados' => 156,
                'errores' => 0,
                'en_progreso' => false
            ];
            
            jsonResponse([
                'success' => true,
                'data' => $estado
            ]);
            break;

        // Ejecutar sincronización
        case 'sync_ejecutar':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                jsonResponse(['success' => false, 'error' => 'Método no permitido'], 405);
            }
            
            // Simular ejecución de sincronización
            sleep(2); // Simular tiempo de procesamiento
            
            $resultado = [
                'success' => true,
                'timestamp' => date('Y-m-d H:i:s'),
                'procesados' => 1250,
                'nuevos' => 25,
                'actualizados' => 158,
                'errores' => 0,
                'mensaje' => 'Sincronización completada exitosamente'
            ];
            
            jsonResponse([
                'success' => true,
                'data' => $resultado
            ]);
            break;

        default:
            jsonResponse(['success' => false, 'error' => 'Acción no válida'], 400);
    }

} catch (Exception $e) {
    jsonResponse([
        'success' => false,
        'error' => DEBUG_MODE ? $e->getMessage() : 'Error interno del servidor'
    ], 500);
}