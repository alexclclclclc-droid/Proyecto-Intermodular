<?php
/**
 * DAO para gestión de Usuarios
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Usuario.php';

class UsuarioDAO {
    private PDO $conn;
    private string $table = 'usuarios';

    public function __construct() {
        $this->conn = Database::getInstance()->getConnection();
    }

    /**
     * Crear nuevo usuario
     */
    public function crear(Usuario $usuario): int {
        $sql = "INSERT INTO {$this->table} 
                (nombre, apellidos, email, password, telefono, rol, token_verificacion)
                VALUES (:nombre, :apellidos, :email, :password, :telefono, :rol, :token)";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ':nombre' => $usuario->getNombre(),
            ':apellidos' => $usuario->getApellidos(),
            ':email' => $usuario->getEmail(),
            ':password' => $usuario->getPassword(),
            ':telefono' => $usuario->getTelefono(),
            ':rol' => $usuario->getRol(),
            ':token' => $usuario->getTokenVerificacion()
        ]);

        return (int)$this->conn->lastInsertId();
    }

    /**
     * Obtener usuario por ID
     */
    public function obtenerPorId(int $id): ?Usuario {
        $sql = "SELECT * FROM {$this->table} WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();

        return $row ? new Usuario($row) : null;
    }

    /**
     * Obtener usuario por email
     */
    public function obtenerPorEmail(string $email): ?Usuario {
        $sql = "SELECT * FROM {$this->table} WHERE email = :email";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':email' => $email]);
        $row = $stmt->fetch();

        return $row ? new Usuario($row) : null;
    }

    /**
     * Verificar login
     */
    public function verificarLogin(string $email, string $password): ?Usuario {
        $usuario = $this->obtenerPorEmail($email);
        
        if ($usuario && $usuario->isActivo() && $usuario->verificarPassword($password)) {
            $this->actualizarUltimoAcceso($usuario->getId());
            return $usuario;
        }
        
        return null;
    }

    /**
     * Actualizar último acceso
     */
    public function actualizarUltimoAcceso(int $id): void {
        $sql = "UPDATE {$this->table} SET ultimo_acceso = NOW() WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':id' => $id]);
    }

    /**
     * Actualizar usuario
     */
    public function actualizar(Usuario $usuario): bool {
        $sql = "UPDATE {$this->table} SET 
                nombre = :nombre,
                apellidos = :apellidos,
                email = :email,
                telefono = :telefono
                WHERE id = :id";

        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            ':nombre' => $usuario->getNombre(),
            ':apellidos' => $usuario->getApellidos(),
            ':email' => $usuario->getEmail(),
            ':telefono' => $usuario->getTelefono(),
            ':id' => $usuario->getId()
        ]);
    }

    /**
     * Actualizar contraseña
     */
    public function actualizarPassword(int $id, string $passwordHash): bool {
        $sql = "UPDATE {$this->table} SET password = :password WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([':password' => $passwordHash, ':id' => $id]);
    }

    /**
     * Verificar email (activar cuenta)
     */
    public function verificarEmail(string $token): bool {
        $sql = "UPDATE {$this->table} 
                SET verificado = TRUE, token_verificacion = NULL 
                WHERE token_verificacion = :token AND activo = TRUE";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':token' => $token]);
        
        return $stmt->rowCount() > 0;
    }

    /**
     * Verificar si existe email
     */
    public function existeEmail(string $email, ?int $exceptoId = null): bool {
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE email = :email";
        $params = [':email' => $email];

        if ($exceptoId) {
            $sql .= " AND id != :id";
            $params[':id'] = $exceptoId;
        }

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);

        return (int)$stmt->fetchColumn() > 0;
    }

    /**
     * Obtener todos los usuarios (admin)
     */
    public function obtenerTodos(): array {
        $sql = "SELECT id, nombre, apellidos, email, telefono, rol, activo, 
                       verificado, fecha_registro, ultimo_acceso 
                FROM {$this->table} 
                ORDER BY fecha_registro DESC";
        $stmt = $this->conn->query($sql);
        
        return array_map(fn($row) => new Usuario($row), $stmt->fetchAll());
    }

    /**
     * Cambiar estado activo
     */
    public function cambiarEstado(int $id, bool $activo): bool {
        $sql = "UPDATE {$this->table} SET activo = :activo WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([':activo' => $activo ? 1 : 0, ':id' => $id]);
    }

    /**
     * Eliminar usuario
     */
    public function eliminar(int $id): bool {
        $sql = "DELETE FROM {$this->table} WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }

    /**
     * Obtener usuarios con filtros
     */
    public function obtenerConFiltros(array $filtros = []): array {
        $sql = "SELECT id, nombre, apellidos, email, telefono, rol, activo, 
                       verificado, fecha_registro, ultimo_acceso 
                FROM {$this->table} WHERE 1=1";
        $params = [];
        
        if (!empty($filtros['rol'])) {
            $sql .= " AND rol = :rol";
            $params[':rol'] = $filtros['rol'];
        }
        
        if (isset($filtros['estado']) && $filtros['estado'] !== '') {
            $sql .= " AND activo = :activo";
            $params[':activo'] = (int)$filtros['estado'];
        }
        
        if (!empty($filtros['email'])) {
            $sql .= " AND email LIKE :email";
            $params[':email'] = '%' . $filtros['email'] . '%';
        }
        
        $sql .= " ORDER BY fecha_registro DESC";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        
        return array_map(fn($row) => new Usuario($row), $stmt->fetchAll());
    }

    /**
     * Cambiar rol de usuario
     */
    public function cambiarRol(int $id, string $rol): bool {
        if (!in_array($rol, ['usuario', 'admin'])) {
            return false;
        }
        
        $sql = "UPDATE {$this->table} SET rol = :rol WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([':rol' => $rol, ':id' => $id]);
    }
}