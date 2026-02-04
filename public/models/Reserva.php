<?php
/**
 * Modelo Reserva
 * Representa una reserva de apartamento
 */

class Reserva {
    private ?int $id;
    private int $id_usuario;
    private int $id_apartamento;
    private string $fecha_entrada;
    private string $fecha_salida;
    private int $num_huespedes;
    private ?float $precio_total;
    private string $estado;
    private ?string $notas;
    private ?string $fecha_reserva;
    private ?string $fecha_modificacion;

    // Datos relacionados (para joins)
    private ?string $nombre_usuario;
    private ?string $email_usuario;
    private ?string $nombre_apartamento;
    private ?string $provincia_apartamento;
    private ?string $municipio_apartamento;

    public const ESTADOS = ['pendiente', 'confirmada', 'cancelada', 'completada'];

    public function __construct(array $data = []) {
        $this->id = $data['id'] ?? null;
        $this->id_usuario = (int)($data['id_usuario'] ?? 0);
        $this->id_apartamento = (int)($data['id_apartamento'] ?? 0);
        $this->fecha_entrada = $data['fecha_entrada'] ?? '';
        $this->fecha_salida = $data['fecha_salida'] ?? '';
        $this->num_huespedes = (int)($data['num_huespedes'] ?? 1);
        $this->precio_total = isset($data['precio_total']) ? (float)$data['precio_total'] : null;
        $this->estado = $data['estado'] ?? 'pendiente';
        $this->notas = $data['notas'] ?? null;
        $this->fecha_reserva = $data['fecha_reserva'] ?? null;
        $this->fecha_modificacion = $data['fecha_modificacion'] ?? null;

        // Datos de joins
        $this->nombre_usuario = $data['nombre_usuario'] ?? null;
        $this->email_usuario = $data['email_usuario'] ?? null;
        $this->nombre_apartamento = $data['nombre_apartamento'] ?? null;
        $this->provincia_apartamento = $data['provincia_apartamento'] ?? null;
        $this->municipio_apartamento = $data['municipio_apartamento'] ?? null;
    }

    // Getters
    public function getId(): ?int { return $this->id; }
    public function getIdUsuario(): int { return $this->id_usuario; }
    public function getIdApartamento(): int { return $this->id_apartamento; }
    public function getFechaEntrada(): string { return $this->fecha_entrada; }
    public function getFechaSalida(): string { return $this->fecha_salida; }
    public function getNumHuespedes(): int { return $this->num_huespedes; }
    public function getPrecioTotal(): ?float { return $this->precio_total; }
    public function getEstado(): string { return $this->estado; }
    public function getNotas(): ?string { return $this->notas; }
    public function getFechaReserva(): ?string { return $this->fecha_reserva; }
    public function getFechaModificacion(): ?string { return $this->fecha_modificacion; }
    public function getNombreUsuario(): ?string { return $this->nombre_usuario; }
    public function getEmailUsuario(): ?string { return $this->email_usuario; }
    public function getNombreApartamento(): ?string { return $this->nombre_apartamento; }
    public function getProvinciaApartamento(): ?string { return $this->provincia_apartamento; }
    public function getMunicipioApartamento(): ?string { return $this->municipio_apartamento; }

    // Setters
    public function setId(?int $id): void { $this->id = $id; }
    public function setIdUsuario(int $id): void { $this->id_usuario = $id; }
    public function setIdApartamento(int $id): void { $this->id_apartamento = $id; }
    public function setFechaEntrada(string $fecha): void { $this->fecha_entrada = $fecha; }
    public function setFechaSalida(string $fecha): void { $this->fecha_salida = $fecha; }
    public function setNumHuespedes(int $num): void { $this->num_huespedes = $num; }
    public function setPrecioTotal(?float $precio): void { $this->precio_total = $precio; }
    public function setEstado(string $estado): void { 
        if (in_array($estado, self::ESTADOS)) {
            $this->estado = $estado; 
        }
    }
    public function setNotas(?string $notas): void { $this->notas = $notas; }

    /**
     * Calcula el número de noches
     */
    public function getNumNoches(): int {
        $entrada = new DateTime($this->fecha_entrada);
        $salida = new DateTime($this->fecha_salida);
        return $entrada->diff($salida)->days;
    }

    /**
     * Verifica si la reserva está activa
     */
    public function isActiva(): bool {
        return in_array($this->estado, ['pendiente', 'confirmada']);
    }

    /**
     * Verifica si se puede cancelar
     */
    public function sePuedeCancelar(): bool {
        if (!$this->isActiva()) return false;
        $hoy = new DateTime();
        $entrada = new DateTime($this->fecha_entrada);
        return $hoy < $entrada;
    }

    /**
     * Convierte a array
     */
    public function toArray(): array {
        return [
            'id' => $this->id,
            'id_usuario' => $this->id_usuario,
            'id_apartamento' => $this->id_apartamento,
            'fecha_entrada' => $this->fecha_entrada,
            'fecha_salida' => $this->fecha_salida,
            'num_huespedes' => $this->num_huespedes,
            'precio_total' => $this->precio_total,
            'estado' => $this->estado,
            'notas' => $this->notas,
            'fecha_reserva' => $this->fecha_reserva,
            'fecha_modificacion' => $this->fecha_modificacion,
            'num_noches' => $this->getNumNoches(),
            'nombre_usuario' => $this->nombre_usuario,
            'email_usuario' => $this->email_usuario,
            'nombre_apartamento' => $this->nombre_apartamento,
            'provincia_apartamento' => $this->provincia_apartamento,
            'municipio_apartamento' => $this->municipio_apartamento
        ];
    }

    /**
     * Obtiene el badge de estado para la UI
     */
    public function getBadgeEstado(): array {
        $badges = [
            'pendiente' => ['class' => 'badge-warning', 'texto' => 'Pendiente'],
            'confirmada' => ['class' => 'badge-success', 'texto' => 'Confirmada'],
            'cancelada' => ['class' => 'badge-danger', 'texto' => 'Cancelada'],
            'completada' => ['class' => 'badge-info', 'texto' => 'Completada']
        ];
        return $badges[$this->estado] ?? $badges['pendiente'];
    }
}