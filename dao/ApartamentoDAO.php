<?php
/**
 * DAO para gestión de Apartamentos
 * Acceso a datos con PDO
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Apartamento.php';

class ApartamentoDAO {
    private PDO $conn;
    private string $table = 'apartamentos';

    public function __construct() {
        $this->conn = Database::getInstance()->getConnection();
    }

    /**
     * Insertar o actualizar apartamento (UPSERT)
     */
    public function upsert(Apartamento $apartamento): bool {
        $sql = "INSERT INTO {$this->table} 
                (n_registro, nombre, direccion, codigo_postal, 
                 provincia, municipio, localidad, nucleo, telefono_1, telefono_2, 
                 telefono_3, email, web, q_calidad, plazas, categoria,
                 especialidades, gps_latitud, gps_longitud, accesible, fecha_sincronizacion)
                VALUES 
                (:n_registro, :nombre, :direccion, :codigo_postal,
                 :provincia, :municipio, :localidad, :nucleo, :telefono_1, :telefono_2,
                 :telefono_3, :email, :web, :q_calidad, :plazas, :categoria,
                 :especialidades, :gps_latitud, :gps_longitud, :accesible, NOW())
                ON DUPLICATE KEY UPDATE
                nombre = VALUES(nombre),
                direccion = VALUES(direccion),
                codigo_postal = VALUES(codigo_postal),
                provincia = VALUES(provincia),
                municipio = VALUES(municipio),
                localidad = VALUES(localidad),
                nucleo = VALUES(nucleo),
                telefono_1 = VALUES(telefono_1),
                telefono_2 = VALUES(telefono_2),
                telefono_3 = VALUES(telefono_3),
                email = VALUES(email),
                web = VALUES(web),
                q_calidad = VALUES(q_calidad),
                plazas = VALUES(plazas),
                categoria = VALUES(categoria),
                especialidades = VALUES(especialidades),
                gps_latitud = VALUES(gps_latitud),
                gps_longitud = VALUES(gps_longitud),
                accesible = VALUES(accesible),
                fecha_sincronizacion = NOW()";

        $stmt = $this->conn->prepare($sql);
        
        return $stmt->execute([
            ':n_registro' => $apartamento->getNRegistro(),
            ':nombre' => $apartamento->getNombre(),
            ':direccion' => $apartamento->getDireccion(),
            ':codigo_postal' => $apartamento->getCodigoPostal(),
            ':provincia' => $apartamento->getProvincia(),
            ':municipio' => $apartamento->getMunicipio(),
            ':localidad' => $apartamento->getLocalidad(),
            ':nucleo' => $apartamento->getNucleo(),
            ':telefono_1' => $apartamento->getTelefono1(),
            ':telefono_2' => $apartamento->getTelefono2(),
            ':telefono_3' => $apartamento->getTelefono3(),
            ':email' => $apartamento->getEmail(),
            ':web' => $apartamento->getWeb(),
            ':q_calidad' => $apartamento->getQCalidad() ? 1 : 0,
            ':plazas' => $apartamento->getPlazas(),
            ':categoria' => $apartamento->getCategoria(),
            ':especialidades' => $apartamento->getEspecialidades(),
            ':gps_latitud' => $apartamento->getGpsLatitud(),
            ':gps_longitud' => $apartamento->getGpsLongitud(),
            ':accesible' => $apartamento->isAccesible() ? 1 : 0
        ]);
    }

    /**
     * Obtener todos los apartamentos con paginación
     */
    public function obtenerTodos(int $limit = 20, int $offset = 0): array {
        $sql = "SELECT * FROM {$this->table} 
                WHERE activo = TRUE 
                ORDER BY nombre ASC 
                LIMIT :limit OFFSET :offset";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        return array_map(fn($row) => new Apartamento($row), $stmt->fetchAll());
    }

    /**
     * Obtener apartamento por ID
     */
    public function obtenerPorId(int $id): ?Apartamento {
        $sql = "SELECT * FROM {$this->table} WHERE id = :id AND activo = TRUE";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        
        return $row ? new Apartamento($row) : null;
    }

    /**
     * Obtener por número de registro
     */
    public function obtenerPorNRegistro(string $n_registro): ?Apartamento {
        $sql = "SELECT * FROM {$this->table} WHERE n_registro = :n_registro";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':n_registro' => $n_registro]);
        $row = $stmt->fetch();
        
        return $row ? new Apartamento($row) : null;
    }

    /**
     * Buscar por provincia
     */
    public function buscarPorProvincia(string $provincia, int $limit = 50, int $offset = 0): array {
        $sql = "SELECT * FROM {$this->table} 
                WHERE provincia = :provincia AND activo = TRUE 
                ORDER BY municipio, nombre 
                LIMIT :limit OFFSET :offset";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':provincia', $provincia, PDO::PARAM_STR);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        return array_map(fn($row) => new Apartamento($row), $stmt->fetchAll());
    }

    /**
     * Búsqueda con filtros múltiples
     */
    public function buscar(array $filtros = [], int $limit = 20, int $offset = 0): array {
        $where = ['activo = TRUE'];
        $params = [];

        if (!empty($filtros['provincia'])) {
            $where[] = 'provincia = :provincia';
            $params[':provincia'] = $filtros['provincia'];
        }

        if (!empty($filtros['municipio'])) {
            $where[] = 'municipio = :municipio';
            $params[':municipio'] = $filtros['municipio'];
        }

        if (!empty($filtros['nombre'])) {
            $where[] = 'nombre LIKE :nombre';
            $params[':nombre'] = '%' . $filtros['nombre'] . '%';
        }

        if (!empty($filtros['capacidad_min'])) {
            $where[] = 'plazas >= :capacidad_min';
            $params[':capacidad_min'] = (int)$filtros['capacidad_min'];
        }

        if (!empty($filtros['accesible'])) {
            $where[] = 'accesible = TRUE';
        }

        if (!empty($filtros['q_calidad'])) {
            $where[] = 'q_calidad = TRUE';
        }

        $sql = "SELECT * FROM {$this->table} 
                WHERE " . implode(' AND ', $where) . "
                ORDER BY nombre ASC 
                LIMIT :limit OFFSET :offset";

        $stmt = $this->conn->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return array_map(fn($row) => new Apartamento($row), $stmt->fetchAll());
    }

    /**
     * Contar resultados de búsqueda
     */
    public function contarBusqueda(array $filtros = []): int {
        $where = ['activo = TRUE'];
        $params = [];

        if (!empty($filtros['provincia'])) {
            $where[] = 'provincia = :provincia';
            $params[':provincia'] = $filtros['provincia'];
        }

        if (!empty($filtros['municipio'])) {
            $where[] = 'municipio = :municipio';
            $params[':municipio'] = $filtros['municipio'];
        }

        if (!empty($filtros['nombre'])) {
            $where[] = 'nombre LIKE :nombre';
            $params[':nombre'] = '%' . $filtros['nombre'] . '%';
        }

        if (!empty($filtros['capacidad_min'])) {
            $where[] = 'plazas >= :capacidad_min';
            $params[':capacidad_min'] = (int)$filtros['capacidad_min'];
        }

        if (!empty($filtros['accesible'])) {
            $where[] = 'accesible = TRUE';
        }

        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE " . implode(' AND ', $where);
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);

        return (int)$stmt->fetchColumn();
    }

    /**
     * Obtener lista de provincias
     */
    public function obtenerProvincias(): array {
        $sql = "SELECT provincia, COUNT(*) as total, SUM(CASE WHEN gps_latitud IS NOT NULL AND gps_longitud IS NOT NULL THEN 1 ELSE 0 END) as con_gps 
                FROM {$this->table} 
                WHERE activo = TRUE AND provincia IS NOT NULL 
                GROUP BY provincia 
                ORDER BY provincia";
        $stmt = $this->conn->query($sql);
        return $stmt->fetchAll();
    }

    /**
     * Obtener municipios de una provincia
     */
    public function obtenerMunicipios(string $provincia): array {
        $sql = "SELECT DISTINCT municipio, COUNT(*) as total 
                FROM {$this->table} 
                WHERE provincia = :provincia AND activo = TRUE AND municipio IS NOT NULL
                GROUP BY municipio 
                ORDER BY municipio";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':provincia' => $provincia]);
        return $stmt->fetchAll();
    }

    /**
     * Contar total de apartamentos
     */
    public function contarTotal(): int {
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE activo = TRUE";
        return (int)$this->conn->query($sql)->fetchColumn();
    }

    /**
     * Contar apartamentos con coordenadas GPS
     */
    public function contarConGPS(): int {
        $sql = "SELECT COUNT(*) FROM {$this->table} 
                WHERE activo = TRUE 
                AND gps_latitud IS NOT NULL 
                AND gps_longitud IS NOT NULL";
        return (int)$this->conn->query($sql)->fetchColumn();
    }

    /**
     * Obtener estadísticas por provincia
     */
    public function obtenerEstadisticasPorProvincia(): array {
        $sql = "SELECT provincia, COUNT(*) as total_apartamentos, 
                       SUM(plazas) as plazas_totales,
                       SUM(CASE WHEN accesible = TRUE THEN 1 ELSE 0 END) as accesibles,
                       ROUND(AVG(plazas), 1) as media_plazas
                FROM {$this->table} 
                WHERE activo = TRUE
                GROUP BY provincia
                ORDER BY total_apartamentos DESC";
        return $this->conn->query($sql)->fetchAll();
    }

    /**
     * Buscar apartamentos cercanos a coordenadas
     */
    public function buscarCercanos(float $latitud, float $longitud, int $radioKm = 10): array {
        // Fórmula Haversine directamente en SQL
        $sql = "SELECT *, 
                (6371 * ACOS(
                    COS(RADIANS(:lat)) * COS(RADIANS(gps_latitud)) * 
                    COS(RADIANS(gps_longitud) - RADIANS(:lng)) + 
                    SIN(RADIANS(:lat2)) * SIN(RADIANS(gps_latitud))
                )) AS distancia
                FROM {$this->table}
                WHERE activo = TRUE 
                AND gps_latitud IS NOT NULL 
                AND gps_longitud IS NOT NULL
                HAVING distancia <= :radio
                ORDER BY distancia
                LIMIT 50";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ':lat' => $latitud,
            ':lng' => $longitud,
            ':lat2' => $latitud,
            ':radio' => $radioKm
        ]);
        return array_map(fn($row) => new Apartamento($row), $stmt->fetchAll());
    }

    /**
     * Obtener apartamentos para el mapa (solo con coordenadas)
     */
    public function obtenerParaMapa(array $filtros = []): array {
        $where = ['activo = TRUE', 'gps_latitud IS NOT NULL', 'gps_longitud IS NOT NULL'];
        $params = [];

        if (!empty($filtros['provincia'])) {
            $where[] = 'provincia = :provincia';
            $params[':provincia'] = $filtros['provincia'];
        }

        $sql = "SELECT id, nombre, provincia, municipio, localidad, nucleo, gps_latitud, gps_longitud, 
                       plazas, accesible 
                FROM {$this->table} 
                WHERE " . implode(' AND ', $where);
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Obtener apartamentos aleatorios destacados
     */
    public function obtenerDestacados(int $limit = 6): array {
        $sql = "SELECT * FROM {$this->table} 
                WHERE activo = TRUE AND gps_latitud IS NOT NULL 
                ORDER BY RAND() 
                LIMIT :limit";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return array_map(fn($row) => new Apartamento($row), $stmt->fetchAll());
    }
}