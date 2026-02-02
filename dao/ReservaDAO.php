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
                WHERE id_apartamento = ?
                AND estado IN ('pendiente', 'confirmada')
                AND (
                    (fecha_entrada <= ? AND fecha_salida > ?)
                    OR (fecha_entrada < ? AND fecha_salida >= ?)
                    OR (fecha_entrada >= ? AND fecha_salida <= ?)
                )";
        
        $params = [
            $idApartamento,
            $fechaEntrada, $fechaEntrada,  // entrada used twice
            $fechaSalida, $fechaSalida,    // salida used twice  
            $fechaEntrada, $fechaSalida    // entrada and salida for third condition
        ];

        if ($exceptoReservaId) {
            $sql .= " AND id != ?";
            $params[] = $exceptoReservaId;
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
     * Obtener reservas con filtros
     */
    public function obtenerConFiltros(array $filtros = []): array {
        $sql = "SELECT r.*, 
                       u.nombre as nombre_usuario, u.email as email_usuario,
                       a.nombre as nombre_apartamento, a.provincia as provincia_apartamento
                FROM {$this->table} r
                INNER JOIN usuarios u ON r.id_usuario = u.id
                INNER JOIN apartamentos a ON r.id_apartamento = a.id
                WHERE 1=1";
        $params = [];
        
        if (!empty($filtros['estado'])) {
            $sql .= " AND r.estado = :estado";
            $params[':estado'] = $filtros['estado'];
        }
        
        if (!empty($filtros['fecha_desde'])) {
            $sql .= " AND r.fecha_entrada >= :fecha_desde";
            $params[':fecha_desde'] = $filtros['fecha_desde'];
        }
        
        if (!empty($filtros['fecha_hasta'])) {
            $sql .= " AND r.fecha_salida <= :fecha_hasta";
            $params[':fecha_hasta'] = $filtros['fecha_hasta'];
        }
        
        if (!empty($filtros['usuario_email'])) {
            $sql .= " AND u.email LIKE :usuario_email";
            $params[':usuario_email'] = '%' . $filtros['usuario_email'] . '%';
        }
        
        $sql .= " ORDER BY r.fecha_reserva DESC";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        
        return array_map(fn($row) => new Reserva($row), $stmt->fetchAll());
    }

    /**
     * Obtener estadísticas de reservas por período
     */
    public function obtenerEstadisticasPeriodo(string $fechaInicio, string $fechaFin): array {
        $sql = "SELECT 
                    estado,
                    COUNT(*) as cantidad,
                    SUM(CASE WHEN precio_total IS NOT NULL THEN precio_total ELSE 0 END) as ingresos
                FROM {$this->table}
                WHERE fecha_reserva BETWEEN :inicio AND :fin
                GROUP BY estado";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ':inicio' => $fechaInicio,
            ':fin' => $fechaFin
        ]);
        
        return $stmt->fetchAll();
    }

    /**
     * Obtener reservas activas para una fecha específica
     */
    public function obtenerReservasActivas(string $fecha): array {
        $sql = "SELECT DISTINCT r.id_apartamento, r.id, r.fecha_entrada, r.fecha_salida, r.estado
                FROM {$this->table} r
                WHERE r.estado IN ('confirmada', 'pendiente')
                AND r.fecha_entrada <= :fecha
                AND r.fecha_salida > :fecha
                ORDER BY r.id_apartamento";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':fecha' => $fecha]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtener estadísticas de ocupación por período
     */
    public function obtenerEstadisticasOcupacion(string $fechaInicio, string $fechaFin): array {
        $sql = "SELECT 
                    COUNT(DISTINCT r.id_apartamento) as apartamentos_ocupados,
                    COUNT(r.id) as total_reservas,
                    SUM(DATEDIFF(r.fecha_salida, r.fecha_entrada)) as total_noches
                FROM {$this->table} r
                WHERE r.estado IN ('confirmada', 'completada')
                AND r.fecha_entrada <= :fecha_fin 
                AND r.fecha_salida >= :fecha_inicio";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ':fecha_inicio' => $fechaInicio,
            ':fecha_fin' => $fechaFin
        ]);
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Asegurar que siempre devolvemos valores válidos
        return [
            'apartamentos_ocupados' => (int)($result['apartamentos_ocupados'] ?? 0),
            'total_reservas' => (int)($result['total_reservas'] ?? 0),
            'total_noches' => (int)($result['total_noches'] ?? 0)
        ];
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