<?php
/**
 * API REST para Reservas
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../dao/ReservaDAO.php';
require_once __DIR__ . '/../dao/ApartamentoDAO.php';

$reservaDAO = new ReservaDAO();
$apartamentoDAO = new ApartamentoDAO();
$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    switch ($action) {
        
        // Crear nueva reserva
        case 'crear':
            if (!isLoggedIn()) {
                jsonResponse(['success' => false, 'error' => 'Debe iniciar sesión para reservar'], 401);
            }
            
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                jsonResponse(['success' => false, 'error' => 'Método no permitido'], 405);
            }
            
            $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            
            // Validar campos
            $idApartamento = (int)($input['id_apartamento'] ?? 0);
            $fechaEntrada = $input['fecha_entrada'] ?? '';
            $fechaSalida = $input['fecha_salida'] ?? '';
            $numHuespedes = (int)($input['num_huespedes'] ?? 1);
            
            $errores = [];
            
            if ($idApartamento <= 0) {
                $errores[] = 'Debe seleccionar un apartamento';
            }
            
            if (empty($fechaEntrada) || empty($fechaSalida)) {
                $errores[] = 'Las fechas son obligatorias';
            }
            
            // Validar fechas
            $hoy = new DateTime();
            $entrada = new DateTime($fechaEntrada);
            $salida = new DateTime($fechaSalida);
            
            if ($entrada < $hoy) {
                $errores[] = 'La fecha de entrada no puede ser anterior a hoy';
            }
            
            if ($salida <= $entrada) {
                $errores[] = 'La fecha de salida debe ser posterior a la entrada';
            }
            
            if ($numHuespedes < 1) {
                $errores[] = 'Debe indicar al menos 1 huésped';
            }
            
            if (!empty($errores)) {
                jsonResponse(['success' => false, 'errors' => $errores], 400);
            }
            
            // Verificar que existe el apartamento
            $apartamento = $apartamentoDAO->obtenerPorId($idApartamento);
            if (!$apartamento) {
                jsonResponse(['success' => false, 'error' => 'Apartamento no encontrado'], 404);
            }
            
            // Verificar capacidad
            if ($numHuespedes > $apartamento->getPlazas()) {
                jsonResponse([
                    'success' => false, 
                    'error' => "El apartamento tiene capacidad máxima de {$apartamento->getPlazas()} personas"
                ], 400);
            }
            
            // Verificar disponibilidad
            if (!$reservaDAO->verificarDisponibilidad($idApartamento, $fechaEntrada, $fechaSalida)) {
                jsonResponse([
                    'success' => false, 
                    'error' => 'El apartamento no está disponible en las fechas seleccionadas'
                ], 409);
            }
            
            // Crear reserva
            $reserva = new Reserva([
                'id_usuario' => $_SESSION['usuario_id'],
                'id_apartamento' => $idApartamento,
                'fecha_entrada' => $fechaEntrada,
                'fecha_salida' => $fechaSalida,
                'num_huespedes' => $numHuespedes,
                'estado' => 'pendiente',
                'notas' => trim($input['notas'] ?? '')
            ]);
            
            $id = $reservaDAO->crear($reserva);
            
            if ($id) {
                jsonResponse([
                    'success' => true,
                    'message' => 'Reserva creada correctamente',
                    'data' => ['id' => $id]
                ], 201);
            } else {
                jsonResponse(['success' => false, 'error' => 'Error al crear la reserva'], 500);
            }
            break;

        // Obtener reservas del usuario
        case 'mis_reservas':
            if (!isLoggedIn()) {
                jsonResponse(['success' => false, 'error' => 'No autorizado'], 401);
            }
            
            $reservas = $reservaDAO->obtenerPorUsuario($_SESSION['usuario_id']);
            jsonResponse([
                'success' => true,
                'data' => array_map(fn($r) => $r->toArray(), $reservas)
            ]);
            break;

        // Obtener detalle de una reserva
        case 'detalle':
            if (!isLoggedIn()) {
                jsonResponse(['success' => false, 'error' => 'No autorizado'], 401);
            }
            
            $id = (int)($_GET['id'] ?? 0);
            $reserva = $reservaDAO->obtenerPorId($id);
            
            if (!$reserva) {
                jsonResponse(['success' => false, 'error' => 'Reserva no encontrada'], 404);
            }
            
            // Solo el propietario o admin pueden ver
            if ($reserva->getIdUsuario() !== $_SESSION['usuario_id'] && !isAdmin()) {
                jsonResponse(['success' => false, 'error' => 'No autorizado'], 403);
            }
            
            jsonResponse([
                'success' => true,
                'data' => $reserva->toArray()
            ]);
            break;

        // Cancelar reserva
        case 'cancelar':
            if (!isLoggedIn()) {
                jsonResponse(['success' => false, 'error' => 'No autorizado'], 401);
            }
            
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                jsonResponse(['success' => false, 'error' => 'Método no permitido'], 405);
            }
            
            $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            $id = (int)($input['id'] ?? 0);
            
            if ($id <= 0) {
                jsonResponse(['success' => false, 'error' => 'ID de reserva inválido'], 400);
            }
            
            $reserva = $reservaDAO->obtenerPorId($id);
            if (!$reserva) {
                jsonResponse(['success' => false, 'error' => 'Reserva no encontrada'], 404);
            }
            
            // Verificar propiedad
            if ($reserva->getIdUsuario() !== $_SESSION['usuario_id'] && !isAdmin()) {
                jsonResponse(['success' => false, 'error' => 'No autorizado'], 403);
            }
            
            // Verificar si se puede cancelar
            if (!$reserva->sePuedeCancelar()) {
                jsonResponse(['success' => false, 'error' => 'Esta reserva no se puede cancelar'], 400);
            }
            
            if ($reservaDAO->cancelar($id, $_SESSION['usuario_id'])) {
                jsonResponse(['success' => true, 'message' => 'Reserva cancelada correctamente']);
            } else {
                jsonResponse(['success' => false, 'error' => 'Error al cancelar la reserva'], 500);
            }
            break;

        // Verificar disponibilidad
        case 'disponibilidad':
            $idApartamento = (int)($_GET['id_apartamento'] ?? 0);
            $fechaEntrada = $_GET['fecha_entrada'] ?? '';
            $fechaSalida = $_GET['fecha_salida'] ?? '';
            
            if ($idApartamento <= 0 || empty($fechaEntrada) || empty($fechaSalida)) {
                jsonResponse(['success' => false, 'error' => 'Parámetros incompletos'], 400);
            }
            
            $disponible = $reservaDAO->verificarDisponibilidad($idApartamento, $fechaEntrada, $fechaSalida);
            jsonResponse([
                'success' => true,
                'disponible' => $disponible
            ]);
            break;

        // Obtener fechas ocupadas de un apartamento
        case 'fechas_ocupadas':
            $idApartamento = (int)($_GET['id_apartamento'] ?? 0);
            
            if ($idApartamento <= 0) {
                jsonResponse(['success' => false, 'error' => 'ID de apartamento inválido'], 400);
            }
            
            $fechas = $reservaDAO->obtenerFechasOcupadas($idApartamento);
            jsonResponse([
                'success' => true,
                'data' => $fechas
            ]);
            break;

        // Admin: Listar todas las reservas
        case 'listar':
            if (!isAdmin()) {
                jsonResponse(['success' => false, 'error' => 'No autorizado'], 403);
            }
            
            $page = max(1, (int)($_GET['page'] ?? 1));
            $limit = 20;
            $offset = ($page - 1) * $limit;
            
            $reservas = $reservaDAO->obtenerTodas($limit, $offset);
            jsonResponse([
                'success' => true,
                'data' => array_map(fn($r) => $r->toArray(), $reservas)
            ]);
            break;

        // Admin: Cambiar estado
        case 'cambiar_estado':
            if (!isAdmin()) {
                jsonResponse(['success' => false, 'error' => 'No autorizado'], 403);
            }
            
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                jsonResponse(['success' => false, 'error' => 'Método no permitido'], 405);
            }
            
            $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            $id = (int)($input['id'] ?? 0);
            $estado = $input['estado'] ?? '';
            
            if (!in_array($estado, Reserva::ESTADOS)) {
                jsonResponse(['success' => false, 'error' => 'Estado no válido'], 400);
            }
            
            if ($reservaDAO->actualizarEstado($id, $estado)) {
                jsonResponse(['success' => true, 'message' => 'Estado actualizado']);
            } else {
                jsonResponse(['success' => false, 'error' => 'Error al actualizar'], 500);
            }
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