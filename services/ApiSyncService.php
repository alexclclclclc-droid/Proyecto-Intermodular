<?php
/**
 * Servicio de sincronización con la API de datos abiertos de Castilla y León
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

    public function sincronizar(): array {
        $this->log("Iniciando sincronización con API de datos abiertos...");
        $inicio = microtime(true);

        try {
            // Verificar conexión primero
            if (!$this->probarConexion()) {
                throw new Exception("No se pudo conectar con la API externa");
            }
            
            $apartamentos = $this->obtenerTodosLosRegistros();
            $total = count($apartamentos);
            $this->log("Obtenidos {$total} registros de la API");

            if ($total === 0) {
                $this->log("No se encontraron registros en la API externa");
                $duracion = round(microtime(true) - $inicio, 2);
                $this->log("Sincronización completada en {$duracion} segundos - Sin datos para procesar");
                return $this->getResultado();
            }

            foreach ($apartamentos as $index => $record) {
                $this->procesarRegistro($record);
                
                if (($index + 1) % 50 === 0) {
                    $this->log("Procesados " . ($index + 1) . "/{$total} registros...");
                }
            }

            $duracion = round(microtime(true) - $inicio, 2);
            
            if ($this->registrosProcesados === 0) {
                $this->log("Sincronización completada en {$duracion} segundos - No hay cambios nuevos");
            } else {
                $this->log("Sincronización completada en {$duracion} segundos");
            }

        } catch (Exception $e) {
            $this->log("ERROR: " . $e->getMessage());
            $this->errores++;
        }

        return $this->getResultado();
    }

    private function obtenerTodosLosRegistros(): array {
        $todos = [];
        $limit = 100;
        $offset = 0;
        $totalCount = null;

        do {
            $response = $this->llamarApi($limit, $offset);
            
            if ($totalCount === null) {
                $totalCount = $response['total_count'] ?? 0;
                $this->log("Total de apartamentos en API: {$totalCount}");
            }

            if (!empty($response['results'])) {
                $todos = array_merge($todos, $response['results']);
                $this->log("Descargados " . count($todos) . " de {$totalCount}...");
            }

            $offset += $limit;
            usleep(200000);

        } while ($offset < $totalCount);

        return $todos;
    }

    private function llamarApi(int $limit = 100, int $offset = 0): array {
        $url = $this->apiUrl . '?limit=' . $limit . '&offset=' . $offset . '&refine=establecimiento%3A%22Apartamentos%20Tur%C3%ADsticos%22';
        
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
            throw new Exception("Error HTTP: {$httpCode}");
        }

        $data = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Error JSON: " . json_last_error_msg());
        }

        return $data;
    }

    private function procesarRegistro(array $record): void {
        try {
            $apartamento = $this->transformarRegistro($record);
            
            if ($apartamento === null) {
                $this->errores++;
                return;
            }

            $existente = $this->apartamentoDAO->obtenerPorNRegistro($apartamento->getNRegistro());
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

    private function transformarRegistro(array $record): ?Apartamento {
        if (empty($record['n_registro'])) {
            return null;
        }

        $latitud = null;
        $longitud = null;
        if (!empty($record['geo_point_2d'])) {
            $latitud = $record['geo_point_2d']['lat'] ?? null;
            $longitud = $record['geo_point_2d']['lon'] ?? null;
        }

        $accesible = false;
        if (!empty($record['accesible_a_personas_con_discapacidad'])) {
            $valor = strtolower($record['accesible_a_personas_con_discapacidad']);
            $accesible = in_array($valor, ['sí', 'si', 'yes', 'true', '1', 's']);
        }

        $qCalidad = false;
        if (!empty($record['q_calidad'])) {
            $valor = strtolower($record['q_calidad']);
            $qCalidad = in_array($valor, ['sí', 'si', 'yes', 'true', '1', 's']);
        }

        return new Apartamento([
            'n_registro' => trim($record['n_registro']),
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
            'plazas' => (int)($record['plazas'] ?? 0),
            'categoria' => $record['categoria'] ?? null,
            'especialidades' => $record['especialidades'] ?? null,
            'gps_latitud' => $latitud,
            'gps_longitud' => $longitud,
            'accesible' => $accesible,
            'activo' => true
        ]);
    }

    private function limpiarTelefono(?string $telefono): ?string {
        if (empty($telefono)) return null;
        $limpio = preg_replace('/[^0-9+]/', '', $telefono);
        return strlen($limpio) >= 9 ? $limpio : null;
    }

    private function limpiarEmail(?string $email): ?string {
        if (empty($email)) return null;
        $email = strtolower(trim($email));
        return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null;
    }

    private function log(string $mensaje): void {
        $timestamp = date('Y-m-d H:i:s');
        $this->log[] = "[{$timestamp}] {$mensaje}";
        echo "[{$timestamp}] {$mensaje}\n";
        ob_flush();
        flush();
    }

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
    
    /**
     * Probar conexión con la API externa
     */
    public function probarConexion(): bool {
        try {
            $testUrl = $this->apiUrl . '?limit=1';
            
            $context = stream_context_create([
                'http' => [
                    'timeout' => 10,
                    'method' => 'GET',
                    'header' => 'User-Agent: ApartamentosCyL/1.0'
                ]
            ]);
            
            $response = @file_get_contents($testUrl, false, $context);
            
            if ($response === false) {
                return false;
            }
            
            $data = json_decode($response, true);
            
            // Verificar que la respuesta tenga la estructura esperada
            return isset($data['results']) && is_array($data['results']);
            
        } catch (Exception $e) {
            return false;
        }
    }
}