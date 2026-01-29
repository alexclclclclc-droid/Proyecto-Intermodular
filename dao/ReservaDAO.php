<?php
/**
 * DAO para gestión de Reservas
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Reserva.php';

class ReservaDAO {
    private PDO $conn;
    private string $table = 'reservas';

    public function __construct() {
        $this->conn = Database::getInstance()->getConnection();
    }

    /**
     * Crear nueva reserva
     */
    public function crear(Reserva $reserva): int {
        $sql = "INSERT INTO {$this->table} 
                (id_usuario, id_apartamento, fecha_entrada, fecha_salida, 
                 num_huespedes, precio_total, estado, notas)
                VALUES 
                (:id_usuario, :id_apartamento, :fecha_entrada, :fecha_salida,
                 :num_huespedes, :precio_total, :estado, :notas)";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ':id_usuario' => $reserva->getIdUsuario(),
            ':id_apartamento' => $reserva->getIdApartamento(),
            ':fecha_entrada' => $reserva->getFechaEntrada(),
            ':fecha_salida' => $reserva->getFechaSalida(),
            ':num_huespedes' => $reserva->getNumHuespedes(),
            ':precio_total' => $reserva->getPrecioTotal(),
            ':estado' => $reserva->getEstado(),
            ':notas' => $reserva->getNotas()
        ]);

        return (int)$this->conn->lastInsertId();
    }

    /**
     * Obtener reserva por ID
     */
    public function obtenerPorId(int $id): ?Reserva {
        $sql = "SELECT r.*, 
                       u.nombre as nombre_usuario, u.email as email_usuario,
                       a.nombre as nombre_apartamento, a.provincia as provincia_apartamento,
                       a.municipio as municipio_apartamento
                FROM {$this->table} r
                INNER JOIN usuarios u ON r.id_usuario = u.id
                INNER JOIN apartamentos a ON r.id_apartamento = a.id
                WHERE r.id = :id";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();

        return $row ? new Reserva($row) : null;
    }

    /**
     * Obtener reservas de un usuario
     */
    public function obtenerPorUsuario(int $idUsuario): array {
        $sql = "SELECT r.*, 
                       a.nombre as nombre_apartamento, 
                       a.provincia as provincia_apartamento,
                       a.municipio as municipio_apartamento
                FROM {$this->table} r
                INNER JOIN apartamentos a ON r.id_apartamento = a.id
                WHERE r.id_usuario = :id_usuario
                ORDER BY r.fecha_entrada DESC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':id_usuario' => $idUsuario]);

        return array_map(fn($row) => new Reserva($row), $stmt->fetchAll());
    }

    /**
     * Obtener reservas de un apartamento
     */
    public function obtenerPorApartamento(int $idApartamento): array {
        $sql = "SELECT r.*, 
                       u.nombre as nombre_usuario, u.email as email_usuario
                FROM {$this->table} r
                INNER JOIN usuarios u ON r.id_usuario = u.id
                WHERE r.id_apartamento = :id_apartamento
                ORDER BY r.fecha_entrada";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':id_apartamento' => $idApartamento]);

        return array_map(fn($row) => new Reserva($row), $stmt->fetchAll());
    }

    /**
     * Verificar disponibilidad
     */
    public function verificarDisponibilidad(int $idApartamento, string $fechaEntrada, string $fechaSalida, ?int $exceptoReservaId = null): bool {
        $sql = "SELECT COUNT(*) FROM {$this->table}
                WHERE id_apartamento = :id_apartamento
                AND estado IN ('pendiente', 'confirmada')
                AND (
                    (fecha_entrada <= :entrada AND fecha_salida > :entrada)
                    OR (fecha_entrada < :salida AND fecha_salida >= :salida)
                    OR (fecha_entrada >= :entrada AND fecha_salida <= :salida)
                )";
        
        $params = [
            ':id_apartamento' => $idApartamento,
            ':entrada' => $fechaEntrada,
            ':salida' => $fechaSalida
        ];

        if ($exceptoReservaId) {
            $sql .= " AND id != :excepto_id";
            $params[':excepto_id'] = $exceptoReservaId;
        }

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);

        return (int)$stmt->fetchColumn() === 0;
    }

    /**
     * Obtener fechas ocupadas de un apartamento
     */
    public function obtenerFechasOcupadas(int $idApartamento): array {
        $sql = "SELECT fecha_entrada, fecha_salida 
                FROM {$this->table}
                WHERE id_apartamento = :id_apartamento
                AND estado IN ('pendiente', 'confirmada')
                AND fecha_salida >= CURDATE()";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':id_apartamento' => $idApartamento]);

        return $stmt->fetchAll();
    }

    /**
     * Actualizar estado de reserva
     */
    public function actualizarEstado(int $id, string $estado): bool {
        if (!in_array($estado, Reserva::ESTADOS)) {
            return false;
        }

        $sql = "UPDATE {$this->table} SET estado = :estado WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([':estado' => $estado, ':id' => $id]);
    }

    /**
     * Cancelar reserva
     */
    public function cancelar(int $id, int $idUsuario): bool {
        $sql = "UPDATE {$this->table} 
                SET estado = 'cancelada' 
                WHERE id = :id AND id_usuario = :id_usuario 
                AND estado IN ('pendiente', 'confirmada')";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':id' => $id, ':id_usuario' => $idUsuario]);
        
        return $stmt->rowCount() > 0;
    }

    /**
     * Obtener todas las reservas (admin)
     */
    public function obtenerTodas(int $limit = 50, int $offset = 0): array {
        $sql = "SELECT r.*, 
                       u.nombre as nombre_usuario, u.email as email_usuario,
                       a.nombre as nombre_apartamento, a.provincia as provincia_apartamento
                FROM {$this->table} r
                INNER JOIN usuarios u ON r.id_usuario = u.id
                INNER JOIN apartamentos a ON r.id_apartamento = a.id
                ORDER BY r.fecha_reserva DESC
                LIMIT :limit OFFSET :offset";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return array_map(fn($row) => new Reserva($row), $stmt->fetchAll());
    }

    /**
     * Contar reservas por estado
     */
    public function contarPorEstado(): array {
        $sql = "SELECT estado, COUNT(*) as total FROM {$this->table} GROUP BY estado";
        return $this->conn->query($sql)->fetchAll();
    }

    /**
     * Estadísticas generales
     */
    public function obtenerEstadisticas(): array {
        $sql = "SELECT 
                    COUNT(*) as total_reservas,
                    SUM(CASE WHEN estado = 'confirmada' THEN 1 ELSE 0 END) as confirmadas,
                    SUM(CASE WHEN estado = 'pendiente' THEN 1 ELSE 0 END) as pendientes,
                    SUM(CASE WHEN estado = 'cancelada' THEN 1 ELSE 0 END) as canceladas,
                    SUM(CASE WHEN estado = 'completada' THEN 1 ELSE 0 END) as completadas,
                    SUM(CASE WHEN estado IN ('confirmada', 'completada') THEN precio_total ELSE 0 END) as ingresos_total
                FROM {$this->table}";
        
        return $this->conn->query($sql)->fetch();
    }

    /**
     * Eliminar reserva
     */
    public function eliminar(int $id): bool {
        $sql = "DELETE FROM {$this->table} WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }
}