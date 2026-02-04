<?php
/**
 * Configuración general del proyecto
 * Apartamentos Turísticos de Castilla y León
 */

// Prevenir acceso directo
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__) . '/');
}

// URLs del proyecto
define('BASE_URL', 'http://localhost/apartamentos_cyl/');
define('PUBLIC_URL', BASE_URL . 'public/');

// API de datos abiertos de Castilla y León
define('API_BASE_URL', 'https://analisis.datosabiertos.jcyl.es/api/explore/v2.1/catalog/datasets/');
define('API_DATASET', 'registro-de-turismo-de-castilla-y-leon');
define('API_RECORDS_ENDPOINT', API_BASE_URL . API_DATASET . '/records');

// Configuración de la aplicación
define('APP_NAME', 'Apartamentos CyL');
define('APP_VERSION', '1.0.0');

// Zona horaria
date_default_timezone_set('Europe/Madrid');

// Configuración de errores (cambiar a 0 en producción)
define('DEBUG_MODE', true);
if (DEBUG_MODE) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    error_reporting(0);
}

// Configuración de sesiones
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    session_start();
}

// Autoload de clases
spl_autoload_register(function ($class) {
    $directories = ['models', 'dao', 'services', 'controllers'];
    foreach ($directories as $dir) {
        $file = ROOT_PATH . $dir . '/' . $class . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

// Incluir la configuración de la base de datos
require_once ROOT_PATH . 'config/database.php';

// Funciones de utilidad
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

function redirect($url) {
    header("Location: " . BASE_URL . $url);
    exit();
}

function isLoggedIn(): bool {
    return isset($_SESSION['usuario_id']);
}

function isAdmin(): bool {
    return isset($_SESSION['usuario_rol']) && $_SESSION['usuario_rol'] === 'admin';
}

function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit();
}

function generateCSRFToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCSRFToken($token): bool {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}