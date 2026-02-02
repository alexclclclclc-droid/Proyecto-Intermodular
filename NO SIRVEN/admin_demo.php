<?php
/**
 * Demo del Panel de Administrador
 * Para probar sin autenticación
 */

// Simular sesión de administrador
session_start();
$_SESSION['usuario_id'] = 1;
$_SESSION['usuario_email'] = 'admin@test.com';
$_SESSION['usuario_nombre'] = 'Administrador Demo';
$_SESSION['usuario_rol'] = 'admin';

// Incluir el panel de admin
include 'views/admin.php';
?>