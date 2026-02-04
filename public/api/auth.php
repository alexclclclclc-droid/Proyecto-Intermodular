<?php
/**
 * API REST para Autenticación
 * Login, registro y gestión de sesiones
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../dao/UsuarioDAO.php';

$usuarioDAO = new UsuarioDAO();
$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    switch ($action) {
        
        // Login de usuario
        case 'login':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                jsonResponse(['success' => false, 'error' => 'Método no permitido'], 405);
            }
            
            $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            
            $email = trim($input['email'] ?? '');
            $password = $input['password'] ?? '';
            
            if (empty($email) || empty($password)) {
                jsonResponse(['success' => false, 'error' => 'Email y contraseña son obligatorios'], 400);
            }
            
            $usuario = $usuarioDAO->verificarLogin($email, $password);
            
            if (!$usuario) {
                jsonResponse(['success' => false, 'error' => 'Credenciales incorrectas'], 401);
            }
            
            // Guardar en sesión
            $_SESSION['usuario_id'] = $usuario->getId();
            $_SESSION['usuario_nombre'] = $usuario->getNombre();
            $_SESSION['usuario_email'] = $usuario->getEmail();
            $_SESSION['usuario_rol'] = $usuario->getRol();
            
            jsonResponse([
                'success' => true,
                'message' => 'Login exitoso',
                'data' => $usuario->toSession()
            ]);
            break;

        // Registro de nuevo usuario
        case 'registro':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                jsonResponse(['success' => false, 'error' => 'Método no permitido'], 405);
            }
            
            $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            
            // Validar campos obligatorios
            $nombre = trim($input['nombre'] ?? '');
            $email = trim($input['email'] ?? '');
            $password = $input['password'] ?? '';
            $passwordConfirm = $input['password_confirm'] ?? '';
            
            $errores = [];
            
            if (empty($nombre) || strlen($nombre) < 2) {
                $errores[] = 'El nombre debe tener al menos 2 caracteres';
            }
            
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errores[] = 'Email no válido';
            }
            
            if (strlen($password) < 8) {
                $errores[] = 'La contraseña debe tener al menos 8 caracteres';
            }
            
            if ($password !== $passwordConfirm) {
                $errores[] = 'Las contraseñas no coinciden';
            }
            
            if (!empty($errores)) {
                jsonResponse(['success' => false, 'errors' => $errores], 400);
            }
            
            // Verificar si ya existe el email
            if ($usuarioDAO->existeEmail($email)) {
                jsonResponse(['success' => false, 'error' => 'Ya existe una cuenta con este email'], 409);
            }
            
            // Crear usuario
            $usuario = new Usuario([
                'nombre' => $nombre,
                'apellidos' => trim($input['apellidos'] ?? ''),
                'email' => $email,
                'password' => $password,
                'telefono' => trim($input['telefono'] ?? ''),
                'rol' => 'usuario'
            ]);
            
            $usuario->hashPassword();
            $usuario->generarTokenVerificacion();
            
            $id = $usuarioDAO->crear($usuario);
            
            if ($id) {
                jsonResponse([
                    'success' => true,
                    'message' => 'Cuenta creada correctamente',
                    'data' => ['id' => $id]
                ], 201);
            } else {
                jsonResponse(['success' => false, 'error' => 'Error al crear la cuenta'], 500);
            }
            break;

        // Cerrar sesión
        case 'logout':
            session_unset();
            session_destroy();
            jsonResponse([
                'success' => true,
                'message' => 'Sesión cerrada correctamente'
            ]);
            break;

        // Verificar estado de sesión
        case 'check':
            if (isLoggedIn()) {
                jsonResponse([
                    'success' => true,
                    'logged_in' => true,
                    'data' => [
                        'id' => $_SESSION['usuario_id'],
                        'nombre' => $_SESSION['usuario_nombre'],
                        'email' => $_SESSION['usuario_email'],
                        'rol' => $_SESSION['usuario_rol']
                    ]
                ]);
            } else {
                jsonResponse([
                    'success' => true,
                    'logged_in' => false
                ]);
            }
            break;

        // Actualizar perfil
        case 'actualizar':
            if (!isLoggedIn()) {
                jsonResponse(['success' => false, 'error' => 'No autorizado'], 401);
            }
            
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                jsonResponse(['success' => false, 'error' => 'Método no permitido'], 405);
            }
            
            $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            
            $usuario = $usuarioDAO->obtenerPorId($_SESSION['usuario_id']);
            if (!$usuario) {
                jsonResponse(['success' => false, 'error' => 'Usuario no encontrado'], 404);
            }
            
            // Actualizar campos permitidos
            if (!empty($input['nombre'])) {
                $usuario->setNombre(trim($input['nombre']));
            }
            if (isset($input['apellidos'])) {
                $usuario->setApellidos(trim($input['apellidos']));
            }
            if (isset($input['telefono'])) {
                $usuario->setTelefono(trim($input['telefono']));
            }
            
            // Si cambia el email, verificar que no exista
            if (!empty($input['email']) && $input['email'] !== $usuario->getEmail()) {
                if ($usuarioDAO->existeEmail($input['email'], $usuario->getId())) {
                    jsonResponse(['success' => false, 'error' => 'El email ya está en uso'], 409);
                }
                $usuario->setEmail(trim($input['email']));
            }
            
            if ($usuarioDAO->actualizar($usuario)) {
                $_SESSION['usuario_nombre'] = $usuario->getNombre();
                $_SESSION['usuario_email'] = $usuario->getEmail();
                
                jsonResponse([
                    'success' => true,
                    'message' => 'Perfil actualizado correctamente',
                    'data' => $usuario->toSession()
                ]);
            } else {
                jsonResponse(['success' => false, 'error' => 'Error al actualizar'], 500);
            }
            break;

        // Cambiar contraseña
        case 'cambiar_password':
            if (!isLoggedIn()) {
                jsonResponse(['success' => false, 'error' => 'No autorizado'], 401);
            }
            
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                jsonResponse(['success' => false, 'error' => 'Método no permitido'], 405);
            }
            
            $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            
            $passwordActual = $input['password_actual'] ?? '';
            $passwordNueva = $input['password_nueva'] ?? '';
            $passwordConfirm = $input['password_confirm'] ?? '';
            
            if (empty($passwordActual) || empty($passwordNueva)) {
                jsonResponse(['success' => false, 'error' => 'Todos los campos son obligatorios'], 400);
            }
            
            if (strlen($passwordNueva) < 8) {
                jsonResponse(['success' => false, 'error' => 'La nueva contraseña debe tener al menos 8 caracteres'], 400);
            }
            
            if ($passwordNueva !== $passwordConfirm) {
                jsonResponse(['success' => false, 'error' => 'Las contraseñas no coinciden'], 400);
            }
            
            $usuario = $usuarioDAO->obtenerPorId($_SESSION['usuario_id']);
            if (!$usuario->verificarPassword($passwordActual)) {
                jsonResponse(['success' => false, 'error' => 'La contraseña actual es incorrecta'], 401);
            }
            
            $nuevoHash = password_hash($passwordNueva, PASSWORD_BCRYPT, ['cost' => 12]);
            if ($usuarioDAO->actualizarPassword($usuario->getId(), $nuevoHash)) {
                jsonResponse(['success' => true, 'message' => 'Contraseña actualizada correctamente']);
            } else {
                jsonResponse(['success' => false, 'error' => 'Error al actualizar la contraseña'], 500);
            }
            break;

        default:
            jsonResponse(['success' => false, 'error' => 'Acción no válida'], 400);
    }

} catch (Exception $e) {
    error_log("Auth API Error: " . $e->getMessage());
    jsonResponse([
        'success' => false,
        'error' => DEBUG_MODE ? $e->getMessage() : 'Error interno del servidor'
    ], 500);
}