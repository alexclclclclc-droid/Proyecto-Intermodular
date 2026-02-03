<?php
/**
 * Utilidad para generar coordenadas GPS automáticamente
 * Se puede usar desde cualquier parte del sistema
 */

class GPSGenerator {
    
    // Coordenadas de las capitales de provincia de Castilla y León
    private static $coordenadasProvincias = [
        'Ávila' => ['lat' => 40.6566, 'lng' => -4.6813],
        'Burgos' => ['lat' => 42.3439, 'lng' => -3.6969],
        'León' => ['lat' => 42.5987, 'lng' => -5.5671],
        'Palencia' => ['lat' => 42.0098, 'lng' => -4.5288],
        'Salamanca' => ['lat' => 40.9701, 'lng' => -5.6635],
        'Segovia' => ['lat' => 40.9429, 'lng' => -4.1088],
        'Soria' => ['lat' => 41.7665, 'lng' => -2.4790],
        'Valladolid' => ['lat' => 41.6523, 'lng' => -4.7245],
        'Zamora' => ['lat' => 41.5034, 'lng' => -5.7467]
    ];
    
    /**
     * Generar coordenadas automáticamente para apartamentos sin GPS
     * @return array Resultado con éxito y número de apartamentos actualizados
     */
    public static function generarCoordenadasAutomaticamente() {
        try {
            $conn = Database::getInstance()->getConnection();
            
            // Obtener apartamentos sin GPS
            $stmt = $conn->query("
                SELECT id, nombre, municipio, provincia 
                FROM apartamentos 
                WHERE activo = 1 
                AND (gps_latitud IS NULL OR gps_longitud IS NULL OR gps_latitud = '' OR gps_longitud = '')
            ");
            
            $apartamentosSinGPS = $stmt->fetchAll();
            $actualizados = 0;
            
            foreach ($apartamentosSinGPS as $apt) {
                $coordenadas = self::generarCoordenadasPorProvincia($apt['provincia']);
                
                $updateStmt = $conn->prepare("
                    UPDATE apartamentos 
                    SET gps_latitud = :lat, gps_longitud = :lng 
                    WHERE id = :id
                ");
                
                if ($updateStmt->execute([
                    ':lat' => $coordenadas['lat'], 
                    ':lng' => $coordenadas['lng'], 
                    ':id' => $apt['id']
                ])) {
                    $actualizados++;
                }
            }
            
            return [
                'success' => true,
                'total_apartamentos' => count($apartamentosSinGPS),
                'actualizados' => $actualizados,
                'message' => "Coordenadas generadas para $actualizados apartamentos"
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Generar coordenadas para un apartamento específico
     * @param int $apartamentoId ID del apartamento
     * @return array Resultado con éxito y coordenadas generadas
     */
    public static function generarCoordenadasParaApartamento($apartamentoId) {
        try {
            $conn = Database::getInstance()->getConnection();
            
            // Obtener datos del apartamento
            $stmt = $conn->prepare("
                SELECT id, nombre, municipio, provincia 
                FROM apartamentos 
                WHERE id = :id AND activo = 1
            ");
            $stmt->execute([':id' => $apartamentoId]);
            $apartamento = $stmt->fetch();
            
            if (!$apartamento) {
                throw new Exception("Apartamento no encontrado");
            }
            
            $coordenadas = self::generarCoordenadasPorProvincia($apartamento['provincia']);
            
            $updateStmt = $conn->prepare("
                UPDATE apartamentos 
                SET gps_latitud = :lat, gps_longitud = :lng 
                WHERE id = :id
            ");
            
            $success = $updateStmt->execute([
                ':lat' => $coordenadas['lat'], 
                ':lng' => $coordenadas['lng'], 
                ':id' => $apartamentoId
            ]);
            
            return [
                'success' => $success,
                'coordenadas' => $coordenadas,
                'message' => $success ? "Coordenadas generadas para {$apartamento['nombre']}" : "Error al actualizar coordenadas"
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Verificar si hay apartamentos sin coordenadas GPS (con caché)
     * @return array Información sobre apartamentos sin GPS
     */
    public static function verificarApartamentosSinGPS() {
        static $cache = null;
        static $cacheTime = null;
        
        // Usar caché por 5 minutos para evitar consultas innecesarias
        if ($cache !== null && $cacheTime !== null && (time() - $cacheTime) < 300) {
            return $cache;
        }
        
        try {
            $conn = Database::getInstance()->getConnection();
            
            $stmt = $conn->query("
                SELECT COUNT(*) as total_sin_gps,
                       (SELECT COUNT(*) FROM apartamentos WHERE activo = 1) as total_apartamentos
                FROM apartamentos 
                WHERE activo = 1 
                AND (gps_latitud IS NULL OR gps_longitud IS NULL OR gps_latitud = '' OR gps_longitud = '')
            ");
            
            $resultado = $stmt->fetch();
            
            $cache = [
                'success' => true,
                'total_apartamentos' => $resultado['total_apartamentos'],
                'sin_gps' => $resultado['total_sin_gps'],
                'necesita_generacion' => $resultado['total_sin_gps'] > 0
            ];
            $cacheTime = time();
            
            return $cache;
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Generar coordenadas basadas en la provincia
     * @param string $provincia Nombre de la provincia
     * @return array Coordenadas lat/lng con variación aleatoria
     */
    private static function generarCoordenadasPorProvincia($provincia) {
        // Usar coordenadas de la provincia si existe, sino usar centro de CyL
        if (isset(self::$coordenadasProvincias[$provincia])) {
            $base = self::$coordenadasProvincias[$provincia];
        } else {
            $base = ['lat' => 41.6523, 'lng' => -4.7245]; // Centro de Castilla y León
        }
        
        // Agregar variación aleatoria pequeña para evitar superposición exacta
        $lat = $base['lat'] + (mt_rand(-100, 100) / 1000); // ±0.1 grados
        $lng = $base['lng'] + (mt_rand(-100, 100) / 1000); // ±0.1 grados
        
        return [
            'lat' => round($lat, 6),
            'lng' => round($lng, 6)
        ];
    }
    
    /**
     * Generar coordenadas automáticamente al insertar nuevos apartamentos
     * Esta función se puede llamar desde triggers o al insertar apartamentos
     * @param array $apartamentoData Datos del apartamento (debe incluir 'provincia')
     * @return array Coordenadas generadas
     */
    public static function generarCoordenadasParaNuevoApartamento($apartamentoData) {
        $provincia = isset($apartamentoData['provincia']) ? $apartamentoData['provincia'] : '';
        return self::generarCoordenadasPorProvincia($provincia);
    }
}
?>