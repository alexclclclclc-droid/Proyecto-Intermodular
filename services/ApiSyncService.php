<?php
/**
 * Servicio de sincronización con la API de datos abiertos de Castilla y León
 * Gestiona la descarga e importación de apartamentos turísticos
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../dao/ApartamentoDAO.php';

class ApiSyncService {
    private string $apiUrl;
    private ApartamentoDAO $apartamentoDAO;
    private int $registrosProcesados = 0;
    private int $registrosNuevos = 0;
    private int $registrosActualizados = 0;
    private int $errores = 0;
    private array $log = [];

    public function __construct() {
        $this->apiUrl = 'https://analisis.datosabiertos.jcyl.es/api/explore/v2.1/catalog/datasets/registro-de-turismo-de-castilla-y-leon/records';
        $this->apartamentoDAO = new ApartamentoDAO();
    }

    /**
     * Ejecuta la sincronización completa
     */
    public function sincronizar(): array {
        $this->log("Iniciando sincronización con API de datos abiertos...");
        $inicio = microtime(true);

        try {
            // Obtener todos los apartamentos turísticos
            $apartamentos = $this->obtenerTodosLosRegistros();
            $total = count($apartamentos);
            $this->log("Obtenidos {$total} registros de la API");

            // Procesar cada registro
            foreach ($apartamentos as $index => $record) {
                $this->procesarRegistro($record);
                
                // Log de progreso cada 100 registros
                if (($index + 1) % 100 === 0) {
                    $this->log("Procesados " . ($index + 1) . "/{$total} registros...");
                }
            }

            $duracion = round(microtime(true) - $inicio, 2);
            $this->log("Sincronización completada en {$duracion} segundos");

        } catch (Exception $e) {
            $this->log("ERROR: " . $e->getMessage());
            $this->errores++;
        }

        return $this->getResultado();
    }

    /**
     * Obtiene todos los registros de la API con paginación
     */
    private function obtenerTodosLosRegistros(): array {
        $todos = [];
        $limit = 100;
        $offset = 0;
        $totalCount = null;

        do {
            $response = $this->llamarApi($limit, $offset);
            
            if ($totalCount === null) {
                $totalCount = $response['total_count'] ?? 0;
                $this->log("Total de registros en API: {$totalCount}");
            }

            if (!empty($response['results'])) {
                $todos = array_merge($todos, $response['results']);
            }

            $offset += $limit;
            
            // Pequeña pausa para no saturar la API
            usleep(100000); // 100ms

        } while ($offset < $totalCount);

        return $todos;
    }

    /**
     * Realiza llamada a la API
     */
    private function llamarApi(int $limit = 100, int $offset = 0): array {
        // Construir URL con parámetros
        $params = [
            'limit' => $limit,
            'offset' => $offset,
            'where' => 'tipo_establecimiento = "Apartamentos Turísticos"'
        ];

        $url = $this->apiUrl . '?' . http_build_query($params);
        
        $this->log("Llamando API: limit={$limit}, offset={$offset}");

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'User-Agent: ApartamentosCyL/1.0'
            ]
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new Exception("Error cURL: {$error}");
        }

        if ($httpCode !== 200) {
            // Intentar sin filtro si falla
            if ($httpCode === 400) {
                return $this->llamarApiSinFiltro($limit, $offset);
            }
            throw new Exception("Error HTTP: {$httpCode}");
        }

        $data = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Error JSON: " . json_last_error_msg());
        }

        return $data;
    }

    /**
     * Llamada a la API sin filtro (backup)
     */
    private function llamarApiSinFiltro(int $limit = 100, int $offset = 0): array {
        $params = [
            'limit' => $limit,
            'offset' => $offset
        ];

        $url = $this->apiUrl . '?' . http_build_query($params);

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'User-Agent: ApartamentosCyL/1.0'
            ]
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new Exception("Error cURL: {$error}");
        }

        if ($httpCode !== 200) {
            throw new Exception("Error HTTP: {$httpCode}");
        }

        $data = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Error JSON: " . json_last_error_msg());
        }

        return $data;
    }

    /**
     * Procesa un registro de la API
     */
    private function procesarRegistro(array $record): void {
        try {
            // Solo procesar apartamentos turísticos
            $tipo = $record['tipo_establecimiento'] ?? '';
            if (stripos($tipo, 'Apartamento') === false) {
                return;
            }

            $apartamento = $this->transformarRegistro($record);
            
            if ($apartamento === null) {
                $this->errores++;
                return;
            }

            // Verificar si existe
            $existente = $this->apartamentoDAO->obtenerPorNRegistro($apartamento->getNRegistro());
            
            // Guardar (insert o update)
            $this->apartamentoDAO->upsert($apartamento);
            
            if ($existente) {
                $this->registrosActualizados++;
            } else {
                $this->registrosNuevos++;
            }
            
            $this->registrosProcesados++;

        } catch (Exception $e) {
            $this->log("Error procesando registro: " . $e->getMessage());
            $this->errores++;
        }
    }

    /**
     * Transforma un registro de la API al modelo Apartamento
     */
    private function transformarRegistro(array $record): ?Apartamento {
        // El número de registro es obligatorio
        if (empty($record['n_registro'])) {
            return null;
        }

        // Extraer coordenadas GPS
        $latitud = null;
        $longitud = null;
        if (!empty($record['geo_point_2d'])) {
            $latitud = $record['geo_point_2d']['lat'] ?? null;
            $longitud = $record['geo_point_2d']['lon'] ?? null;
        }

        // Procesar accesibilidad
        $accesible = false;
        if (!empty($record['accesible_a_personas_con_discapacidad'])) {
            $valor = strtolower($record['accesible_a_personas_con_discapacidad']);
            $accesible = in_array($valor, ['sí', 'si', 'yes', 'true', '1', 's']);
        }

        // Procesar Q de calidad
        $qCalidad = false;
        if (!empty($record['q_de_calidad_turistica'])) {
            $valor = strtolower($record['q_de_calidad_turistica']);
            $qCalidad = in_array($valor, ['sí', 'si', 'yes', 'true', '1', 's']);
        }

        return new Apartamento([
            'n_registro' => trim($record['n_registro']),
            'tipo_establecimiento' => $record['tipo_establecimiento'] ?? 'Apartamentos Turísticos',
            'nombre' => trim($record['nombre'] ?? 'Sin nombre'),
            'direccion' => $record['direccion'] ?? null,
            'codigo_postal' => $record['c_postal'] ?? null,
            'provincia' => $record['provincia'] ?? 'Desconocida',
            'municipio' => $record['municipio'] ?? null,
            'localidad' => $record['localidad'] ?? null,
            'nucleo' => $record['nucleo'] ?? null,
            'telefono_1' => $this->limpiarTelefono($record['telefono_1'] ?? null),
            'telefono_2' => $this->limpiarTelefono($record['telefono_2'] ?? null),
            'telefono_3' => $this->limpiarTelefono($record['telefono_3'] ?? null),
            'email' => $this->limpiarEmail($record['email'] ?? null),
            'web' => $record['web'] ?? null,
            'q_calidad' => $qCalidad,
            'capacidad_alojamiento' => (int)($record['capacidad_alojamiento'] ?? 0),
            'categoria' => $record['categoria'] ?? null,
            'especialidades' => $record['especialidades'] ?? null,
            'gps_latitud' => $latitud,
            'gps_longitud' => $longitud,
            'accesible' => $accesible,
            'activo' => true
        ]);
    }

    /**
     * Limpia y valida un número de teléfono
     */
    private function limpiarTelefono(?string $telefono): ?string {
        if (empty($telefono)) return null;
        
        // Eliminar caracteres no numéricos excepto + al inicio
        $limpio = preg_replace('/[^0-9+]/', '', $telefono);
        
        return strlen($limpio) >= 9 ? $limpio : null;
    }

    /**
     * Limpia y valida un email
     */
    private function limpiarEmail(?string $email): ?string {
        if (empty($email)) return null;
        
        $email = strtolower(trim($email));
        return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null;
    }

    /**
     * Agrega mensaje al log
     */
    private function log(string $mensaje): void {
        $timestamp = date('Y-m-d H:i:s');
        $this->log[] = "[{$timestamp}] {$mensaje}";
        // También mostrar en tiempo real
        echo "[{$timestamp}] {$mensaje}\n";
        flush();
    }

    /**
     * Obtiene el resultado de la sincronización
     */
    public function getResultado(): array {
        return [
            'success' => $this->errores === 0,
            'procesados' => $this->registrosProcesados,
            'nuevos' => $this->registrosNuevos,
            'actualizados' => $this->registrosActualizados,
            'errores' => $this->errores,
            'log' => $this->log
        ];
    }
}