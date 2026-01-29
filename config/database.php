<?php
/**
 * Configuración de conexión a la base de datos
 * Patrón Singleton para gestión de conexiones PDO
 */

class Database {
    private static $instance = null;
    private $conn;
    
    private $host = 'localhost';
    private $db_name = 'apartamentos_cyl';
    private $username = 'root';
    private $password = '';
    private $charset = 'utf8mb4';

    private function __construct() {
        try {
            $dsn = "mysql:host={$this->host};dbname={$this->db_name};charset={$this->charset}";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            $this->conn = new PDO($dsn, $this->username, $this->password, $options);
        } catch (PDOException $e) {
            error_log("Error de conexión: " . $e->getMessage());
            throw new Exception("Error al conectar con la base de datos");
        }
    }

    /**
     * Obtiene la instancia única de la conexión
     */
    public static function getInstance(): Database {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Obtiene la conexión PDO
     */
    public function getConnection(): PDO {
        return $this->conn;
    }

    // Prevenir clonación
    private function __clone() {}

    // Prevenir deserialización
    public function __wakeup() {
        throw new Exception("No se puede deserializar un singleton");
    }
}