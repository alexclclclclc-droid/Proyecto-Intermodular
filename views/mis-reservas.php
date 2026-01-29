<?php
/**
 * Página de reservas del usuario
 */
define('ROOT_PATH', dirname(__DIR__) . '/');
require_once ROOT_PATH . 'config/config.php';

// Verificar login
if (!isLoggedIn()) {
    header('Location: ' . BASE_URL);
    exit();
}

$pageTitle = 'Mis Reservas';
include ROOT_PATH . 'views/partials/header.php';
?>

<main>
    <!-- Header de página -->
    <section style="background: linear-gradient(135deg, var(--color-primary-dark), var(--color-primary)); padding: var(--space-3xl) 0; color: white;">
        <div class="container">
            <h1 style="color: white; margin-bottom: var(--space-sm);">Mis Reservas</h1>
            <p style="opacity: 0.9;">
                Gestiona todas tus reservas de apartamentos desde aquí.
            </p>
        </div>
    </section>

    <section class="py-3">
        <div class="container container-narrow">
            <div id="mis-reservas">
                <div class="loading">
                    <div class="spinner"></div>
                    <p>Cargando tus reservas...</p>
                </div>
            </div>
        </div>
    </section>
</main>

<?php include ROOT_PATH . 'views/partials/footer.php'; ?>

<script>
// Cargar reservas del usuario
async function loadMisReservas() {
    const container = document.getElementById('mis-reservas');
    
    try {
        const response = await apiRequest('reservas.php?action=mis_reservas');
        
        if (response.success && response.data) {
            if (response.data.length === 0) {
                container.innerHTML = `
                    <div class="text-center py-3">
                        <p style="font-size: 3rem; margin-bottom: var(--space-md);">📅</p>
                        <h3>No tienes reservas</h3>
                        <p class="text-muted mb-2">Explora nuestros apartamentos y haz tu primera reserva</p>
                        <a href="apartamentos.php" class="btn btn-primary">Ver apartamentos</a>
                    </div>
                `;
                return;
            }
            
            container.innerHTML = response.data.map(r => {
                const badgeClass = {
                    'pendiente': 'badge-warning',
                    'confirmada': 'badge-success',
                    'cancelada': 'badge-danger',
                    'completada': 'badge-info'
                }[r.estado] || 'badge-info';
                
                return `
                    <div class="card" style="margin-bottom: var(--space-lg);">
                        <div class="card-body">
                            <div style="display: flex; justify-content: space-between; align-items: start; flex-wrap: wrap; gap: var(--space-md);">
                                <div>
                                    <h4 style="margin-bottom: var(--space-xs);">${escapeHtml(r.nombre_apartamento)}</h4>
                                    <p class="text-muted">📍 ${escapeHtml(r.provincia_apartamento || '')}</p>
                                </div>
                                <span class="badge ${badgeClass}">${r.estado}</span>
                            </div>
                            <div style="display: flex; gap: var(--space-xl); margin-top: var(--space-md); flex-wrap: wrap;">
                                <span>📅 ${formatDate(r.fecha_entrada)} - ${formatDate(r.fecha_salida)}</span>
                                <span>👥 ${r.num_huespedes} huésped${r.num_huespedes > 1 ? 'es' : ''}</span>
                                <span>🌙 ${r.num_noches} noche${r.num_noches > 1 ? 's' : ''}</span>
                            </div>
                            ${['pendiente', 'confirmada'].includes(r.estado) ? `
                                <div style="margin-top: var(--space-lg); padding-top: var(--space-md); border-top: 1px solid var(--color-border);">
                                    <button class="btn btn-secondary btn-sm" onclick="cancelarReserva(${r.id})">
                                        Cancelar reserva
                                    </button>
                                </div>
                            ` : ''}
                        </div>
                    </div>
                `;
            }).join('');
        }
    } catch (e) {
        container.innerHTML = `<div class="alert alert-error">Error al cargar las reservas</div>`;
    }
}

// Cancelar reserva
async function cancelarReserva(id) {
    if (!confirm('¿Estás seguro de que quieres cancelar esta reserva?')) return;
    
    try {
        const response = await apiRequest('reservas.php?action=cancelar', {
            method: 'POST',
            body: JSON.stringify({ id })
        });
        
        if (response.success) {
            showToast('Reserva cancelada correctamente', 'success');
            loadMisReservas();
        }
    } catch (e) {
        showToast(e.message || 'Error al cancelar', 'error');
    }
}

// Iniciar
document.addEventListener('DOMContentLoaded', loadMisReservas);
</script>