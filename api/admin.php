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
$action = $_GET['action'] ?? $_POST['action'] ?? '';

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
                
                if (strpos($reserva->getFechaCreacion(), $mesActual) === 0) {
                    $stats['reservas']['este_mes']++;
                }
            }
            
            // Estadísticas de apartamentos (simuladas por ahora)
            $stats['apartamentos']['total'] = 150; // Valor estimado
            $stats['apartamentos']['ocupados'] = 45;
            $stats['apartamentos']['tasa_ocupacion'] = round(($stats['apartamentos']['ocupados'] / $stats['apartamentos']['total']) * 100, 1);
            
            jsonResponse([
                'success' => true,
                'data' => $stats
            ]);
            break;

        // Gestión de usuarios - Listar todos
        case 'usuarios_listar':
            $usuarios = $usuarioDAO->obtenerTodos();
            $usuariosArray = array_map(function($usuario) {
                return $usuario->toArray();
            }, $usuarios);
            
            jsonResponse([
                'success' => true,
                'data' => $usuariosArray
            ]);
            break;

        // Gestión de usuarios - Cambiar estado
        case 'usuario_cambiar_estado':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                jsonResponse(['success' => false, 'error' => 'Método no permitido'], 405);
            }
            
            $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
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
            
            $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
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
            $reservas = $reservaDAO->obtenerTodas(1000, 0);
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
                'data' => $reservasArray
            ]);
            break;

        // Gestión de reservas - Cambiar estado
        case 'reserva_cambiar_estado':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                jsonResponse(['success' => false, 'error' => 'Método no permitido'], 405);
            }
            
            $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
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
    error_log("Admin API Error: " . $e->getMessage());
    jsonResponse([
        'success' => false,
        'error' => DEBUG_MODE ? $e->getMessage() : 'Error interno del servidor'
    ], 500);
}