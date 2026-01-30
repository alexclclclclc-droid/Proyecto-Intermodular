<?php
/**
 * Header común para todas las páginas
 */
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__) . '/');
}
require_once ROOT_PATH . 'config/config.php';

// Detectar si estamos en una subcarpeta (views) o en la raíz
$currentFile = basename($_SERVER['PHP_SELF']);
$inViews = strpos($_SERVER['PHP_SELF'], '/views/') !== false;

// Construir rutas relativas correctas
$basePath = $inViews ? '../' : './';
$viewsPath = $inViews ? './' : './views/';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Encuentra los mejores apartamentos turísticos de Castilla y León. Reserva tu alojamiento ideal entre cientos de opciones.">
    <title><?= isset($pageTitle) ? $pageTitle . ' | ' : '' ?>Apartamentos CyL</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500;600;700&family=Source+Sans+3:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Leaflet CSS (para mapas) -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    
    <!-- Estilos principales -->
    <link rel="stylesheet" href="<?= $basePath ?>public/css/styles.css">
    
    <?php if (isset($extraCSS)): ?>
        <?= $extraCSS ?>
    <?php endif; ?>
</head>
<body>
    <header class="header">
        <div class="container header-inner">
            <a href="<?= $basePath ?>index.php" class="logo">
                <img src="<?= $basePath ?>public/images/YAR.png" alt="Logo Apartamentos CyL" class="logo-img">
                <span>Apartamentos<span class="text-accent">CyL</span></span>
            </a>
            
            <button class="menu-toggle" aria-label="Menú">
                <span></span>
                <span></span>
                <span></span>
            </button>
            
            <nav class="nav">
                <a href="<?= $basePath ?>index.php" class="nav-link <?= $currentFile === 'index.php' ? 'active' : '' ?>">Inicio</a>
                <a href="<?= $viewsPath ?>apartamentos.php" class="nav-link <?= $currentFile === 'apartamentos.php' ? 'active' : '' ?>">Apartamentos</a>
                <a href="<?= $viewsPath ?>mapa.php" class="nav-link <?= $currentFile === 'mapa.php' ? 'active' : '' ?>">Mapa</a>
                <?php if (isLoggedIn()): ?>
                    <a href="<?= $viewsPath ?>mis-reservas.php" class="nav-link <?= $currentFile === 'mis-reservas.php' ? 'active' : '' ?>">Mis Reservas</a>
                <?php endif; ?>
            </nav>
            
            <div class="nav-actions">
                <!-- Botón login (visible si no está logueado) -->
                <button id="btn-login" class="btn btn-primary btn-sm" data-modal-open="modal-login" style="<?= isLoggedIn() ? 'display:none' : '' ?>">
                    Iniciar sesión
                </button>
                
                <!-- Menú usuario (visible si está logueado) -->
                <div id="user-menu" class="user-menu" style="<?= !isLoggedIn() ? 'display:none' : 'display:flex' ?>; align-items: center; gap: var(--space-md);">
                    <span id="user-name" style="font-weight: 500;"><?= isLoggedIn() ? htmlspecialchars($_SESSION['usuario_nombre']) : '' ?></span>
                    <button id="btn-logout" class="btn btn-ghost btn-sm">Salir</button>
                </div>
            </div>
        </div>
    </header>

    <!-- Modal Login -->
    <div id="modal-login" class="modal-overlay">
        <div class="modal">
            <div class="modal-header">
                <h3 class="modal-title">Iniciar sesión</h3>
                <button class="modal-close" data-modal-close>&times;</button>
            </div>
            <div class="modal-body">
                <form id="form-login">
                    <div class="form-group">
                        <label class="form-label form-label-required" for="login-email">Email</label>
                        <input type="email" id="login-email" name="email" class="form-input" 
                               placeholder="tu@email.com" data-validate="required|email">
                    </div>
                    <div class="form-group">
                        <label class="form-label form-label-required" for="login-password">Contraseña</label>
                        <input type="password" id="login-password" name="password" class="form-input" 
                               placeholder="••••••••" data-validate="required|min:8">
                    </div>
                    <button type="submit" class="btn btn-primary" style="width: 100%;">
                        Iniciar sesión
                    </button>
                </form>
                <p class="text-center mt-1">
                    <span class="text-muted">¿No tienes cuenta?</span>
                    <a href="#" onclick="AuthModule.closeModal('modal-login'); AuthModule.openModal('modal-registro'); return false;">
                        Regístrate
                    </a>
                </p>
            </div>
        </div>
    </div>

    <!-- Modal Registro -->
    <div id="modal-registro" class="modal-overlay">
        <div class="modal">
            <div class="modal-header">
                <h3 class="modal-title">Crear cuenta</h3>
                <button class="modal-close" data-modal-close>&times;</button>
            </div>
            <div class="modal-body">
                <form id="form-registro">
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label form-label-required" for="reg-nombre">Nombre</label>
                            <input type="text" id="reg-nombre" name="nombre" class="form-input" 
                                   placeholder="Tu nombre" data-validate="required|min:2">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="reg-apellidos">Apellidos</label>
                            <input type="text" id="reg-apellidos" name="apellidos" class="form-input" 
                                   placeholder="Tus apellidos">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label form-label-required" for="reg-email">Email</label>
                        <input type="email" id="reg-email" name="email" class="form-input" 
                               placeholder="tu@email.com" data-validate="required|email">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="reg-telefono">Teléfono</label>
                        <input type="tel" id="reg-telefono" name="telefono" class="form-input" 
                               placeholder="+34 600 000 000" data-validate="phone">
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label form-label-required" for="reg-password">Contraseña</label>
                            <input type="password" id="reg-password" name="password" class="form-input" 
                                   placeholder="Mín. 8 caracteres" data-validate="required|min:8">
                        </div>
                        <div class="form-group">
                            <label class="form-label form-label-required" for="reg-password2">Confirmar</label>
                            <input type="password" id="reg-password2" name="password_confirm" class="form-input" 
                                   placeholder="Repetir contraseña" data-validate="required|match:password">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary" style="width: 100%;">
                        Crear cuenta
                    </button>
                </form>
                <p class="text-center mt-1">
                    <span class="text-muted">¿Ya tienes cuenta?</span>
                    <a href="#" onclick="AuthModule.closeModal('modal-registro'); AuthModule.openModal('modal-login'); return false;">
                        Inicia sesión
                    </a>
                </p>
            </div>
        </div>
    </div>

    <!-- Modal Detalle Apartamento -->
    <div id="modal-detalle" class="modal-overlay">
        <div class="modal" style="max-width: 600px;">
            <div class="modal-header">
                <h3 class="modal-title">Detalle del apartamento</h3>
                <button class="modal-close" data-modal-close>&times;</button>
            </div>
            <div class="modal-body">
                <!-- Contenido dinámico -->
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-modal-close>Cerrar</button>
            </div>
        </div>
    </div>