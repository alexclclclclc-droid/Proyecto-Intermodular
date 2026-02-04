<?php
/**
 * Test script to verify admin API functionality
 */

require_once 'config/config.php';

// Simulate admin session for testing
$_SESSION['usuario_id'] = 1;
$_SESSION['usuario_rol'] = 'admin';
$_SESSION['usuario_nombre'] = 'Test Admin';

echo "<h1>Testing Admin API Endpoints</h1>\n";

// Test 1: Get statistics
echo "<h2>1. Testing Statistics Endpoint</h2>\n";
$url = 'api/admin.php?action=estadisticas';
$context = stream_context_create([
    'http' => [
        'method' => 'GET',
        'header' => 'Content-Type: application/json'
    ]
]);

$result = file_get_contents($url, false, $context);
echo "<pre>Statistics Result: " . htmlspecialchars($result) . "</pre>\n";

// Test 2: List users
echo "<h2>2. Testing Users List Endpoint</h2>\n";
$url = 'api/admin.php?action=usuarios_listar';
$result = file_get_contents($url, false, $context);
echo "<pre>Users Result: " . htmlspecialchars($result) . "</pre>\n";

// Test 3: List reservations
echo "<h2>3. Testing Reservations List Endpoint</h2>\n";
$url = 'api/admin.php?action=reservas_listar';
$result = file_get_contents($url, false, $context);
echo "<pre>Reservations Result: " . htmlspecialchars($result) . "</pre>\n";

// Test 4: Test POST endpoint (change user role)
echo "<h2>4. Testing POST Endpoint (Change User Role)</h2>\n";
$postData = json_encode([
    'action' => 'usuario_cambiar_rol',
    'id' => 2,
    'rol' => 'usuario'
]);

$context = stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => 'Content-Type: application/json',
        'content' => $postData
    ]
]);

$result = file_get_contents('api/admin.php', false, $context);
echo "<pre>Change Role Result: " . htmlspecialchars($result) . "</pre>\n";

echo "<h2>Test Complete</h2>\n";
?>