<?php
/**
 * Página de listado de apartamentos con filtros
 */
define('ROOT_PATH', dirname(__DIR__) . '/');
require_once ROOT_PATH . 'config/config.php';

$pageTitle = 'Apartamentos';

// Obtener filtro inicial de URL si existe
$provinciaInicial = $_GET['provincia'] ?? '';

include ROOT_PATH . 'views/partials/header.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="/Proyecto-Intermodular/public/css/styles.css">
</head>
<body>
    
</body>
</html>
<main>
    <!-- Header de página -->
    <section style="background: linear-gradient(135deg, var(--color-primary-dark), var(--color-primary)); padding: var(--space-3xl) 0; color: white;">
        <div class="container">
            <h1 style="color: white; margin-bottom: var(--space-sm);">Apartamentos Turísticos</h1>
            <p style="opacity: 0.9; max-width: 600px;">
                Encuentra el apartamento perfecto para tu estancia en Castilla y León. 
                Utiliza los filtros para afinar tu búsqueda.
            </p>
        </div>
    </section>

    <section class="py-3">
        <div class="container">
            <!-- Filtros -->
            <div class="filters">
                <div class="filters-grid">
                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label" for="filtro-provincia">Provincia</label>
                        <select id="filtro-provincia" class="form-select">
                            <option value="">Todas las provincias</option>
                        </select>
                    </div>
                    
                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label" for="filtro-municipio">Municipio</label>
                        <select id="filtro-municipio" class="form-select" disabled>
                            <option value="">Todos los municipios</option>
                        </select>
                    </div>
                    
                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label" for="filtro-nombre">Buscar</label>
                        <input type="text" id="filtro-nombre" class="form-input" placeholder="Nombre del apartamento...">
                    </div>
                    
                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label" for="filtro-capacidad">Capacidad mín.</label>
                        <select id="filtro-capacidad" class="form-select">
                            <option value="">Cualquiera</option>
                            <option value="2">2+ personas</option>
                            <option value="4">4+ personas</option>
                            <option value="6">6+ personas</option>
                            <option value="8">8+ personas</option>
                            <option value="10">10+ personas</option>
                        </select>
                    </div>
                    
                    <div class="form-group" style="margin-bottom: 0; display: flex; align-items: flex-end;">
                        <label class="form-check" style="padding-bottom: var(--space-sm);">
                            <input type="checkbox" id="filtro-accesible">
                            <span>Solo accesibles</span>
                        </label>
                    </div>
                    
                    <div class="filters-actions" style="align-self: flex-end;">
                        <button id="btn-limpiar-filtros" class="btn btn-ghost">
                            Limpiar filtros
                        </button>
                    </div>
                </div>
            </div>

            <!-- Resultados -->
            <div class="results-header">
                <p id="results-count" class="results-count">Cargando...</p>
            </div>

            <!-- Grid de apartamentos -->
            <div class="results-grid" id="apartamentos-grid">
                <div class="loading" style="grid-column: 1/-1;">
                    <div class="spinner"></div>
                    <p>Cargando apartamentos...</p>
                </div>
            </div>

            <!-- Paginación -->
            <div id="pagination" class="pagination"></div>
        </div>
    </section>
</main>

<?php include ROOT_PATH . 'views/partials/footer.php'; ?>

<script>
// Si hay provincia en la URL, aplicarla al cargar
document.addEventListener('DOMContentLoaded', () => {
    const provinciaInicial = '<?= addslashes($provinciaInicial) ?>';
    if (provinciaInicial) {
        ApartamentosModule.currentFilters.provincia = provinciaInicial;
        // Esperar a que se carguen las provincias para seleccionar
        setTimeout(() => {
            const select = document.getElementById('filtro-provincia');
            if (select) {
                select.value = provinciaInicial;
                ApartamentosModule.loadMunicipios(provinciaInicial);
            }
        }, 500);
    }
});
</script>