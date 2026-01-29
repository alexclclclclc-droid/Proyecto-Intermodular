<?php
/**
 * Modelo Usuario
 * Representa un usuario del sistema
 */

class Usuario {
    private ?int $id;
    private string $nombre;
    private ?string $apellidos;
    private string $email;
    private string $password;
    private ?string $telefono;
    private string $rol;
    private bool $activo;
    private bool $verificado;
    private ?string $token_verificacion;
    private ?string $fecha_registro;
    private ?string $ultimo_acceso;

    public function __construct(array $data = []) {
        $this->id = $data['id'] ?? null;
        $this->nombre = $data['nombre'] ?? '';
        $this->apellidos = $data['apellidos'] ?? null;
        $this->email = $data['email'] ?? '';
        $this->password = $data['password'] ?? '';
        $this->telefono = $data['telefono'] ?? null;
        $this->rol = $data['rol'] ?? 'usuario';
        $this->activo = (bool)($data['activo'] ?? true);
        $this->verificado = (bool)($data['verificado'] ?? false);
        $this->token_verificacion = $data['token_verificacion'] ?? null;
        $this->fecha_registro = $data['fecha_registro'] ?? null;
        $this->ultimo_acceso = $data['ultimo_acceso'] ?? null;
    }

    // Getters
    public function getId(): ?int { return $this->id; }
    public function getNombre(): string { return $this->nombre; }
    public function getApellidos(): ?string { return $this->apellidos; }
    public function getEmail(): string { return $this->email; }
    public function getPassword(): string { return $this->password; }
    public function getTelefono(): ?string { return $this->telefono; }
    public function getRol(): string { return $this->rol; }
    public function isActivo(): bool { return $this->activo; }
    public function isVerificado(): bool { return $this->verificado; }
    public function getTokenVerificacion(): ?string { return $this->token_verificacion; }
    public function getFechaRegistro(): ?string { return $this->fecha_registro; }
    public function getUltimoAcceso(): ?string { return $this->ultimo_acceso; }

    // Setters
    public function setId(?int $id): void { $this->id = $id; }
    public function setNombre(string $nombre): void { $this->nombre = $nombre; }
    public function setApellidos(?string $apellidos): void { $this->apellidos = $apellidos; }
    public function setEmail(string $email): void { $this->email = $email; }
    public function setPassword(string $password): void { $this->password = $password; }
    public function setTelefono(?string $telefono): void { $this->telefono = $telefono; }
    public function setRol(string $rol): void { $this->rol = $rol; }
    public function setActivo(bool $activo): void { $this->activo = $activo; }
    public function setVerificado(bool $verificado): void { $this->verificado = $verificado; }
    public function setTokenVerificacion(?string $token): void { $this->token_verificacion = $token; }

    /**
     * Hashea la contraseña
     */
    public function hashPassword(): void {
        $this->password = password_hash($this->password, PASSWORD_BCRYPT, ['cost' => 12]);
    }

    /**
     * Verifica si la contraseña coincide
     */
    public function verificarPassword(string $password): bool {
        return password_verify($password, $this->password);
    }

    /**
     * Genera un token de verificación
     */
    public function generarTokenVerificacion(): string {
        $this->token_verificacion = bin2hex(random_bytes(32));
        return $this->token_verificacion;
    }

    /**
     * Obtiene el nombre completo
     */
    public function getNombreCompleto(): string {
        return trim($this->nombre . ' ' . ($this->apellidos ?? ''));
    }

    /**
     * Verifica si es administrador
     */
    public function isAdmin(): bool {
        return $this->rol === 'admin';
    }

    /**
     * Convierte a array (sin contraseña)
     */
    public function toArray(): array {
        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'apellidos' => $this->apellidos,
            'email' => $this->email,
            'telefono' => $this->telefono,
            'rol' => $this->rol,
            'activo' => $this->activo,
            'verificado' => $this->verificado,
            'fecha_registro' => $this->fecha_registro,
            'ultimo_acceso' => $this->ultimo_acceso
        ];
    }

    /**
     * Datos para guardar en sesión
     */
    public function toSession(): array {
        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'apellidos' => $this->apellidos,
            'email' => $this->email,
            'rol' => $this->rol
        ];
    }
}