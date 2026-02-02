<?php
/**
 * Controlador de Administrador
 * Maneja todas las operaciones del panel de administración
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../dao/UsuarioDAO.php';
require_once __DIR__ . '/../dao/ReservaDAO.php';
require_once __DIR__ . '/../dao/ApartamentoDAO.php';

class AdminController {
    private UsuarioDAO $usuarioDAO;
    private ReservaDAO $reservaDAO;
    private ApartamentoDAO $apartamentoDAO;

    public function __construct() {
        $this->usuarioDAO = new UsuarioDAO();
        $this->reservaDAO = new ReservaDAO();
        $this->apartamentoDAO = new ApartamentoDAO();
    }

    /**
     * Mostrar el panel de administrador
     */
    public function index(): void {
        // Verificar que el usuario sea administrador
        if (!isLoggedIn() || !isAdmin()) {
            redirect('index.php?login_required=1');
            return;
        }

        $pageTitle = 'Panel de Administrador';
        $data = [
            'pageTitle' => $pageTitle,
            'usuario' => [
                'nombre' => $_SESSION['usuario_nombre'] ?? 'Administrador',
                'email' => $_SESSION['usuario_email'] ?? ''
            ]
        ];

        $this->loadView('admin/panel', $data);
    }

    /**
     * API para obtener estadísticas del dashboard
     */
    public function estadisticas(): void {
        $this->requireAdmin();

        try {
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
                ]
            ];

            // Estadísticas de usuarios
            $usuarios = $this->usuarioDAO->obtenerTodos();
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
            $reservas = $this->reservaDAO->obtenerTodas(1000, 0);
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

                if (strpos($reserva->getFechaReserva() ?? '', $mesActual) === 0) {
                    $stats['reservas']['este_mes']++;
                }
            }

            // Estadísticas de apartamentos (simuladas por ahora)
            $stats['apartamentos']['total'] = 150;
            $stats['apartamentos']['ocupados'] = 45;
            $stats['apartamentos']['tasa_ocupacion'] = round(($stats['apartamentos']['ocupados'] / $stats['apartamentos']['total']) * 100, 1);

            $this->jsonResponse([
                'success' => true,
                'data' => $stats
            ]);

        } catch (Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'error' => DEBUG_MODE ? $e->getMessage() : 'Error interno del servidor'
            ], 500);
        }
    }

    /**
     * API para listar usuarios
     */
    public function usuariosListar(): void {
        $this->requireAdmin();

        try {
            $usuarios = $this->usuarioDAO->obtenerTodos();
            $usuariosArray = array_map(function($usuario) {
                return $usuario->toArray();
            }, $usuarios);

            $this->jsonResponse([
                'success' => true,
                'data' => $usuariosArray
            ]);

        } catch (Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'error' => DEBUG_MODE ? $e->getMessage() : 'Error al obtener usuarios'
            ], 500);
        }
    }

    /**
     * API para cambiar estado de usuario
     */
    public function usuarioCambiarEstado(): void {
        $this->requireAdmin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'error' => 'Método no permitido'], 405);
        }

        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $id = (int)($input['id'] ?? 0);
        $activo = (bool)($input['activo'] ?? false);

        if ($id <= 0) {
            $this->jsonResponse(['success' => false, 'error' => 'ID de usuario inválido'], 400);
        }

        // No permitir desactivar el propio usuario
        if ($id === $_SESSION['usuario_id']) {
            $this->jsonResponse(['success' => false, 'error' => 'No puedes cambiar tu propio estado'], 400);
        }

        try {
            if ($this->usuarioDAO->cambiarEstado($id, $activo)) {
                $this->jsonResponse([
                    'success' => true,
                    'message' => $activo ? 'Usuario activado' : 'Usuario desactivado'
                ]);
            } else {
                $this->jsonResponse(['success' => false, 'error' => 'Error al cambiar estado'], 500);
            }
        } catch (Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'error' => DEBUG_MODE ? $e->getMessage() : 'Error al cambiar estado'
            ], 500);
        }
    }

    /**
     * API para listar reservas
     */
    public function reservasListar(): void {
        $this->requireAdmin();

        try {
            $reservas = $this->reservaDAO->obtenerTodas(1000, 0);
            $reservasArray = array_map(function($reserva) {
                $data = $reserva->toArray();
                // Asegurar que tenemos los campos necesarios para la UI
                $data['usuario_email'] = $data['email_usuario'] ?? 'N/A';
                $data['apartamento_nombre'] = $data['nombre_apartamento'] ?? 'N/A';
                $data['fecha_creacion'] = $data['fecha_reserva'] ?? date('Y-m-d H:i:s');
                return $data;
            }, $reservas);

            $this->jsonResponse([
                'success' => true,
                'data' => $reservasArray
            ]);

        } catch (Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'error' => DEBUG_MODE ? $e->getMessage() : 'Error al obtener reservas'
            ], 500);
        }
    }

    /**
     * Verificar que el usuario sea administrador
     */
    private function requireAdmin(): void {
        if (!isLoggedIn() || !isAdmin()) {
            $this->jsonResponse(['success' => false, 'error' => 'No autorizado'], 403);
        }
    }

    /**
     * Cargar una vista
     */
    private function loadView(string $view, array $data = []): void {
        extract($data);
        $viewPath = __DIR__ . "/../views/{$view}.php";
        
        if (file_exists($viewPath)) {
            require $viewPath;
        } else {
            throw new Exception("Vista no encontrada: {$view}");
        }
    }

    /**
     * Respuesta JSON
     */
    private function jsonResponse(array $data, int $statusCode = 200): void {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit();
    }
}