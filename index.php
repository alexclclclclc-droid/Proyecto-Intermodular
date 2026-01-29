<?php
/**
 * Página principal - Apartamentos Turísticos de Castilla y León
 */
define('ROOT_PATH', __DIR__ . '/');
require_once ROOT_PATH . 'config/config.php';

$pageTitle = 'Inicio';
include ROOT_PATH . 'views/partials/header.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="/PruebaIntermodular/public/css/styles.css">
</head>
<body>
    
</body>
</html>
<main>
    <!-- Hero Section -->
    <section class="hero">
        <div class="container hero-content">
            <span class="hero-badge">
                🏆 Datos abiertos de Castilla y León
            </span>
            <h1>Descubre el alojamiento perfecto en <span class="text-accent">Castilla y León</span></h1>
            <p class="hero-subtitle">
                Explora cientos de apartamentos turísticos en las 9 provincias castellanoleonesas. 
                Desde las murallas de Ávila hasta las riberas del Duero, tu próxima escapada te espera.
            </p>
            <div class="hero-actions">
                <a href="views/apartamentos.php" class="btn btn-accent btn-lg">
                    Explorar apartamentos
                </a>
                <a href="views/mapa.php" class="btn btn-secondary btn-lg" style="border-color: white; color: white;">
                    Ver en mapa
                </a>
            </div>
            <div class="hero-stats" id="hero-stats">
                <div class="hero-stat">
                    <div class="hero-stat-value" id="stat-total">---</div>
                    <div class="hero-stat-label">Apartamentos</div>
                </div>
                <div class="hero-stat">
                    <div class="hero-stat-value">9</div>
                    <div class="hero-stat-label">Provincias</div>
                </div>
                <div class="hero-stat">
                    <div class="hero-stat-value" id="stat-municipios">---</div>
                    <div class="hero-stat-label">Municipios</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Provincias Section -->
    <section class="py-4">
        <div class="container">
            <div class="text-center mb-3">
                <h2>Explora por provincia</h2>
                <p class="text-muted" style="max-width: 600px; margin: var(--space-md) auto 0;">
                    Cada rincón de Castilla y León tiene algo especial que ofrecer. 
                    Elige tu destino y encuentra el apartamento ideal.
                </p>
            </div>
            
            <div class="provincias-grid" id="provincias-grid">
                <!-- Se carga dinámicamente -->
                <div class="loading" style="grid-column: 1/-1;">
                    <div class="spinner"></div>
                </div>
            </div>
        </div>
    </section>

    <!-- Apartamentos destacados -->
    <section class="py-4" style="background: var(--color-bg-alt);">
        <div class="container">
            <div class="text-center mb-3">
                <h2>Apartamentos destacados</h2>
                <p class="text-muted">Descubre algunas de nuestras opciones más populares</p>
            </div>
            
            <div class="results-grid" id="destacados-grid">
                <!-- Se carga dinámicamente -->
                <div class="loading" style="grid-column: 1/-1;">
                    <div class="spinner"></div>
                </div>
            </div>
            
            <div class="text-center mt-2">
                <a href="views/apartamentos.php" class="btn btn-primary btn-lg">
                    Ver todos los apartamentos
                </a>
            </div>
        </div>
    </section>
