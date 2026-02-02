<?php
/**
 * Direct API test to debug admin functionality
 */

// Simulate admin session
session_start();
$_SESSION['usuario_id'] = 1;
$_SESSION['usuario_rol'] = 'admin';
$_SESSION['usuario_nombre'] = 'Test Admin';

echo "<h1>Direct API Test</h1>\n";

// Test 1: Test role change via direct API call
echo "<h2>Test 1: Change User Role (Direct)</h2>\n";

// Simulate POST request with JSON data
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['CONTENT_TYPE'] = 'application/json';

// Create JSON input
$testData = [
    'action' => 'usuario_cambiar_rol',
    'id' => 2,
    'rol' => 'usuario'
];

// Simulate the JSON input stream
$jsonData = json_encode($testData);
file_put_contents('php://temp/maxmemory:1048576', $jsonData);

echo "<pre>Test Data: " . htmlspecialchars($jsonData) . "</pre>\n";

// Capture output
ob_start();

try {
    // Temporarily override php://input for testing
    $tempFile = tempnam(sys_get_temp_dir(), 'api_test');
    file_put_contents($tempFile, $jsonData);
    
    // Mock the input stream
    $originalInput = 'php://input';
    
    // Include the API file
    include 'api/admin.php';
    
    unlink($tempFile);
    
} catch (Exception $e) {
    echo "<pre>Exception: " . $e->getMessage() . "</pre>\n";
}

$output = ob_get_clean();
echo "<pre>API Response: " . htmlspecialchars($output) . "</pre>\n";

// Test 2: Test with cURL simulation
echo "<h2>Test 2: cURL Simulation</h2>\n";

$url = 'http://localhost/apartamentos_cyl/api/admin.php';
$data = json_encode([
    'action' => 'usuario_cambiar_estado',
    'id' => 2,
    'activo' => false
]);

echo "<pre>URL: $url</pre>\n";
echo "<pre>Data: " . htmlspecialchars($data) . "</pre>\n";

// Test 3: Simple action test
echo "<h2>Test 3: Simple GET Action</h2>\n";

$_SERVER['REQUEST_METHOD'] = 'GET';
$_GET['action'] = 'estadisticas';

ob_start();
try {
    include 'api/admin.php';
} catch (Exception $e) {
    echo "<pre>Exception: " . $e->getMessage() . "</pre>\n";
}
$output = ob_get_clean();
echo "<pre>Statistics Response: " . htmlspecialchars($output) . "</pre>\n";

echo "<h2>Test Complete</h2>\n";
?>