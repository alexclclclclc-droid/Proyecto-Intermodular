<?php
// Detectar si estamos en una subcarpeta (views) o en la raíz
$currentFile = basename($_SERVER['PHP_SELF']);
$inViews = strpos($_SERVER['PHP_SELF'], '/views/') !== false;

// Construir rutas relativas correctas
$basePath = $inViews ? '../' : './';
$viewsPath = $inViews ? './' : './views/';
?>
    <footer class="footer">
        <div class="container">
            <div class="footer-grid">
                <div class="footer-section">
                    <h4>Apartamentos CyL</h4>
                    <p>
                        Tu portal para encontrar el alojamiento perfecto en Castilla y León. 
                        Datos actualizados desde el portal de datos abiertos de la Junta.
                    </p>
                </div>
                
                <div class="footer-section">
                    <h4>Explorar</h4>
                    <ul class="footer-links">
                        <li><a href="<?= $basePath ?>index.php">Inicio</a></li>
                        <li><a href="<?= $viewsPath ?>apartamentos.php">Buscar apartamentos</a></li>
                        <li><a href="<?= $viewsPath ?>mapa.php">Ver mapa</a></li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h4>Provincias</h4>
                    <ul class="footer-links">
                        <li><a href="<?= $viewsPath ?>apartamentos.php?provincia=Ávila">Ávila</a></li>
                        <li><a href="<?= $viewsPath ?>apartamentos.php?provincia=Burgos">Burgos</a></li>
                        <li><a href="<?= $viewsPath ?>apartamentos.php?provincia=León">León</a></li>
                        <li><a href="<?= $viewsPath ?>apartamentos.php?provincia=Salamanca">Salamanca</a></li>
                        <li><a href="<?= $viewsPath ?>apartamentos.php?provincia=Valladolid">Valladolid</a></li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h4>Datos Abiertos</h4>
                    <p>
                        Este proyecto utiliza datos del 
                        <a href="https://datosabiertos.jcyl.es" target="_blank" rel="noopener" style="color: var(--color-accent);">
                            Portal de Datos Abiertos de Castilla y León
                        </a>
                    </p>
                    <p style="margin-top: var(--space-md);">
                        <a href="https://datosabiertos.jcyl.es/web/es/concurso-datos-abiertos" target="_blank" rel="noopener" class="btn btn-sm" style="background: rgba(255,255,255,0.1); color: white;">
                            Concurso Datos Abiertos 2025
                        </a>
                    </p>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p>&copy; <?= date('Y') ?> Apartamentos CyL - Proyecto Intermodular DAW</p>
            </div>
        </div>
    </footer>

    <!-- Leaflet JS (para mapas) -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    
    <!-- JavaScript principal -->
    <script src="<?= $basePath ?>public/js/app.js"></script>
    
    <?php if (isset($extraJS)): ?>
        <?= $extraJS ?>
    <?php endif; ?>
</body>
</html>