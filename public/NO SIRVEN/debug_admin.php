<?php
/**
 * Debug script for admin panel functionality
 */

require_once 'config/config.php';

echo "<h1>Admin Panel Debug</h1>\n";

// Check session
echo "<h2>Session Information</h2>\n";
echo "<pre>";
echo "Session ID: " . session_id() . "\n";
echo "Session Status: " . session_status() . "\n";
echo "Session Data: " . print_r($_SESSION, true) . "\n";
echo "</pre>";

// Check if user is logged in and admin
echo "<h2>Authentication Status</h2>\n";
echo "<pre>";
echo "Is Logged In: " . (isLoggedIn() ? 'YES' : 'NO') . "\n";
echo "Is Admin: " . (isAdmin() ? 'YES' : 'NO') . "\n";
echo "</pre>";

// Test database connection
echo "<h2>Database Connection</h2>\n";
try {
    $db = Database::getInstance()->getConnection();
    echo "<pre>Database connection: SUCCESS</pre>\n";
    
    // Test user count
    $stmt = $db->query("SELECT COUNT(*) as total FROM usuarios");
    $userCount = $stmt->fetch()['total'];
    echo "<pre>Total users in database: $userCount</pre>\n";
    
    // Test reservation count
    $stmt = $db->query("SELECT COUNT(*) as total FROM reservas");
    $reservationCount = $stmt->fetch()['total'];
    echo "<pre>Total reservations in database: $reservationCount</pre>\n";
    
} catch (Exception $e) {
    echo "<pre>Database connection: ERROR - " . $e->getMessage() . "</pre>\n";
}

// Test admin API endpoints
echo "<h2>API Endpoint Tests</h2>\n";

// Simulate admin session
$_SESSION['usuario_id'] = 1;
$_SESSION['usuario_rol'] = 'admin';
$_SESSION['usuario_nombre'] = 'Debug Admin';

echo "<pre>Simulated admin session created</pre>\n";

// Test statistics endpoint
echo "<h3>Testing Statistics Endpoint</h3>\n";
try {
    ob_start();
    $_GET['action'] = 'estadisticas';
    include 'api/admin.php';
    $output = ob_get_clean();
    echo "<pre>Statistics API Response: " . htmlspecialchars($output) . "</pre>\n";
} catch (Exception $e) {
    echo "<pre>Statistics API Error: " . $e->getMessage() . "</pre>\n";
}

// Test users list endpoint
echo "<h3>Testing Users List Endpoint</h3>\n";
try {
    ob_start();
    $_GET['action'] = 'usuarios_listar';
    include 'api/admin.php';
    $output = ob_get_clean();
    echo "<pre>Users List API Response: " . htmlspecialchars($output) . "</pre>\n";
} catch (Exception $e) {
    echo "<pre>Users List API Error: " . $e->getMessage() . "</pre>\n";
}

// Test reservations list endpoint
echo "<h3>Testing Reservations List Endpoint</h3>\n";
try {
    ob_start();
    $_GET['action'] = 'reservas_listar';
    include 'api/admin.php';
    $output = ob_get_clean();
    echo "<pre>Reservations List API Response: " . htmlspecialchars($output) . "</pre>\n";
} catch (Exception $e) {
    echo "<pre>Reservations List API Error: " . $e->getMessage() . "</pre>\n";
}

echo "<h2>Debug Complete</h2>\n";
echo "<p><a href='views/admin.php'>Go to Admin Panel</a></p>\n";
?>