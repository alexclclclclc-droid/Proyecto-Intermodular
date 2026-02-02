<?php
/**
 * Archivo de prueba para el panel de administrador
 * Simula un usuario administrador logueado
 */

session_start();

// Simular usuario administrador logueado
$_SESSION['usuario_id'] = 1;
$_SESSION['usuario_email'] = 'admin@test.com';
$_SESSION['usuario_nombre'] = 'Administrador';
$_SESSION['usuario_rol'] = 'admin';

// Redirigir al panel de admin
header('Location: views/admin.php');
exit();