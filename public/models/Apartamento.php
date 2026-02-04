<?php
/**
 * Modelo Apartamento
 * Representa un apartamento turístico
 */

class Apartamento {
    private ?int $id;
    private string $n_registro;
    private string $nombre;
    private ?string $direccion;
    private ?string $codigo_postal;
    private string $provincia;
    private ?string $municipio;
    private ?string $localidad;
    private ?string $nucleo;
    private ?string $telefono_1;
    private ?string $telefono_2;
    private ?string $telefono_3;
    private ?string $email;
    private ?string $web;
    private bool $q_calidad;
    private int $plazas;
    private ?string $categoria;
    private ?string $especialidades;
    private ?float $gps_latitud;
    private ?float $gps_longitud;
    private bool $accesible;
    private ?string $fecha_sincronizacion;
    private bool $activo;

    public function __construct(array $data = []) {
        $this->id = $data['id'] ?? null;
        $this->n_registro = $data['n_registro'] ?? '';
        $this->nombre = $data['nombre'] ?? '';
        $this->direccion = $data['direccion'] ?? null;
        $this->codigo_postal = $data['codigo_postal'] ?? null;
        $this->provincia = $data['provincia'] ?? '';
        $this->municipio = $data['municipio'] ?? null;
        $this->localidad = $data['localidad'] ?? null;
        $this->nucleo = $data['nucleo'] ?? null;
        $this->telefono_1 = $data['telefono_1'] ?? null;
        $this->telefono_2 = $data['telefono_2'] ?? null;
        $this->telefono_3 = $data['telefono_3'] ?? null;
        $this->email = $data['email'] ?? null;
        $this->web = $data['web'] ?? null;
        $this->q_calidad = (bool)($data['q_calidad'] ?? false);
        $this->plazas = (int)($data['plazas'] ?? 0);
        $this->categoria = $data['categoria'] ?? null;
        $this->especialidades = $data['especialidades'] ?? null;
        $this->gps_latitud = isset($data['gps_latitud']) ? (float)$data['gps_latitud'] : null;
        $this->gps_longitud = isset($data['gps_longitud']) ? (float)$data['gps_longitud'] : null;
        $this->accesible = (bool)($data['accesible'] ?? false);
        $this->fecha_sincronizacion = $data['fecha_sincronizacion'] ?? null;
        $this->activo = (bool)($data['activo'] ?? true);
    }

    // Getters
    public function getId(): ?int { return $this->id; }
    public function getNRegistro(): string { return $this->n_registro; }
    public function getNombre(): string { return $this->nombre; }
    public function getDireccion(): ?string { return $this->direccion; }
    public function getCodigoPostal(): ?string { return $this->codigo_postal; }
    public function getProvincia(): string { return $this->provincia; }
    public function getMunicipio(): ?string { return $this->municipio; }
    public function getLocalidad(): ?string { return $this->localidad; }
    public function getNucleo(): ?string { return $this->nucleo; }
    public function getTelefono1(): ?string { return $this->telefono_1; }
    public function getTelefono2(): ?string { return $this->telefono_2; }
    public function getTelefono3(): ?string { return $this->telefono_3; }
    public function getEmail(): ?string { return $this->email; }
    public function getWeb(): ?string { return $this->web; }
    public function getQCalidad(): bool { return $this->q_calidad; }
    public function getPlazas(): int { return $this->plazas; }
    public function getCategoria(): ?string { return $this->categoria; }
    public function getEspecialidades(): ?string { return $this->especialidades; }
    public function getGpsLatitud(): ?float { return $this->gps_latitud; }
    public function getGpsLongitud(): ?float { return $this->gps_longitud; }
    public function isAccesible(): bool { return $this->accesible; }
    public function getFechaSincronizacion(): ?string { return $this->fecha_sincronizacion; }
    public function isActivo(): bool { return $this->activo; }

    // Setters
    public function setId(?int $id): void { $this->id = $id; }
    public function setNRegistro(string $n_registro): void { $this->n_registro = $n_registro; }
    public function setNombre(string $nombre): void { $this->nombre = $nombre; }
    public function setDireccion(?string $direccion): void { $this->direccion = $direccion; }
    public function setCodigoPostal(?string $cp): void { $this->codigo_postal = $cp; }
    public function setProvincia(string $provincia): void { $this->provincia = $provincia; }
    public function setMunicipio(?string $municipio): void { $this->municipio = $municipio; }
    public function setLocalidad(?string $localidad): void { $this->localidad = $localidad; }
    public function setNucleo(?string $nucleo): void { $this->nucleo = $nucleo; }
    public function setTelefono1(?string $tel): void { $this->telefono_1 = $tel; }
    public function setTelefono2(?string $tel): void { $this->telefono_2 = $tel; }
    public function setTelefono3(?string $tel): void { $this->telefono_3 = $tel; }
    public function setEmail(?string $email): void { $this->email = $email; }
    public function setWeb(?string $web): void { $this->web = $web; }
    public function setQCalidad(bool $q): void { $this->q_calidad = $q; }
    public function setPlazas(int $plazas): void { $this->plazas = $plazas; }
    public function setCategoria(?string $cat): void { $this->categoria = $cat; }
    public function setEspecialidades(?string $esp): void { $this->especialidades = $esp; }
    public function setGpsLatitud(?float $lat): void { $this->gps_latitud = $lat; }
    public function setGpsLongitud(?float $lng): void { $this->gps_longitud = $lng; }
    public function setAccesible(bool $acc): void { $this->accesible = $acc; }
    public function setActivo(bool $activo): void { $this->activo = $activo; }

    /**
     * Convierte el objeto a array
     */
    public function toArray(): array {
        return [
            'id' => $this->id,
            'n_registro' => $this->n_registro,
            'nombre' => $this->nombre,
            'direccion' => $this->direccion,
            'codigo_postal' => $this->codigo_postal,
            'provincia' => $this->provincia,
            'municipio' => $this->municipio,
            'localidad' => $this->localidad,
            'nucleo' => $this->nucleo,
            'telefono_1' => $this->telefono_1,
            'telefono_2' => $this->telefono_2,
            'telefono_3' => $this->telefono_3,
            'email' => $this->email,
            'web' => $this->web,
            'q_calidad' => $this->q_calidad,
            'plazas' => $this->plazas,
            'categoria' => $this->categoria,
            'especialidades' => $this->especialidades,
            'gps_latitud' => $this->gps_latitud,
            'gps_longitud' => $this->gps_longitud,
            'accesible' => $this->accesible,
            'fecha_sincronizacion' => $this->fecha_sincronizacion,
            'activo' => $this->activo
        ];
    }

    /**
     * Tiene coordenadas GPS válidas
     */
    public function tieneGPS(): bool {
        return $this->gps_latitud !== null && $this->gps_longitud !== null;
    }

    /**
     * Obtiene la dirección completa formateada
     */
    public function getDireccionCompleta(): string {
        $partes = array_filter([
            $this->direccion,
            $this->codigo_postal,
            $this->localidad ?? $this->municipio,
            $this->provincia
        ]);
        return implode(', ', $partes);
    }

    /**
     * Obtiene el primer teléfono disponible
     */
    public function getTelefonoPrincipal(): ?string {
        return $this->telefono_1 ?? $this->telefono_2 ?? $this->telefono_3;
    }
}