<?php
/**
 * Widget de Notificaciones - Muestra resumen de alertas
 * Este archivo puede ser incluido en cualquier página para mostrar notificaciones
 */

// Solo ejecutar si ya hay una conexión activa
if (!isset($conn)) {
    return;
}

// Contar pagos atrasados
$query_count_atrasados = "SELECT COUNT(*) as total 
FROM pagos_clientes 
WHERE estado = 'pendiente' 
AND fecha_programada < CURDATE()";
$result_count_atrasados = mysqli_query($conn, $query_count_atrasados);
$count_atrasados = mysqli_fetch_assoc($result_count_atrasados)['total'];

// Contar clientes finalizados en los últimos 7 días
$query_count_finalizados = "SELECT COUNT(DISTINCT c.id) as total
FROM clientes c
JOIN pagos_clientes pc ON c.id = pc.cliente_id
WHERE NOT EXISTS (
    SELECT 1 FROM pagos_clientes pc2 
    WHERE pc2.cliente_id = c.id AND pc2.estado = 'pendiente'
)
AND EXISTS (
    SELECT 1 FROM pagos_clientes pc3
    WHERE pc3.cliente_id = c.id 
    AND pc3.fecha_pago >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
)";
$result_count_finalizados = mysqli_query($conn, $query_count_finalizados);
$count_finalizados = mysqli_fetch_assoc($result_count_finalizados)['total'];

// Contar pagos próximos (próximos 3 días)
$query_count_proximos = "SELECT COUNT(*) as total 
FROM pagos_clientes 
WHERE estado = 'pendiente' 
AND fecha_programada BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 3 DAY)";
$result_count_proximos = mysqli_query($conn, $query_count_proximos);
$count_proximos = mysqli_fetch_assoc($result_count_proximos)['total'];

$total_notificaciones = $count_atrasados + $count_finalizados + $count_proximos;
?>

<!-- Widget de Notificaciones -->
<style>
.notification-dropdown {
    position: relative;
}

.notification-bell {
    position: relative;
    cursor: pointer;
    font-size: 1.5rem;
    padding: 0.5rem;
    border-radius: 50%;
    transition: all 0.3s ease;
}

.notification-bell:hover {
    background: rgba(255, 255, 255, 0.1);
}

.notification-badge-bell {
    position: absolute;
    top: 0;
    right: 0;
    background: #ef4444;
    color: white;
    border-radius: 50%;
    width: 18px;
    height: 18px;
    font-size: 0.7rem;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    animation: pulse-bell 2s infinite;
}

@keyframes pulse-bell {
    0%, 100% {
        transform: scale(1);
    }
    50% {
        transform: scale(1.2);
    }
}

.notification-dropdown-menu {
    position: absolute;
    top: 100%;
    right: 0;
    background: white;
    border-radius: 12px;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
    min-width: 350px;
    max-width: 400px;
    z-index: 1000;
    display: none;
    margin-top: 0.5rem;
    overflow: hidden;
}

.notification-dropdown.active .notification-dropdown-menu {
    display: block;
    animation: slideDown 0.3s ease;
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.notification-header-widget {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 1rem;
    font-weight: bold;
}

.notification-item-widget {
    padding: 1rem;
    border-bottom: 1px solid #e5e7eb;
    transition: background 0.2s ease;
    cursor: pointer;
}

.notification-item-widget:hover {
    background: #f9fafb;
}

.notification-item-widget:last-child {
    border-bottom: none;
}

.notification-footer-widget {
    padding: 0.75rem;
    text-align: center;
    background: #f9fafb;
    border-top: 1px solid #e5e7eb;
}
</style>

<div class="notification-dropdown" id="<?php echo isset($noti_id) ? $noti_id : 'notificationWidget'; ?>">
    <div class="notification-bell" onclick="toggleNotifications('<?php echo isset($noti_id) ? $noti_id : 'notificationWidget'; ?>')">
        🔔
        <?php if ($total_notificaciones > 0): ?>
            <span class="notification-badge-bell"><?php echo $total_notificaciones; ?></span>
        <?php endif; ?>
    </div>
    
    <div class="notification-dropdown-menu">
        <div class="notification-header-widget">
            📬 Notificaciones
            <?php if ($total_notificaciones > 0): ?>
                <span class="float-end badge bg-danger"><?php echo $total_notificaciones; ?></span>
            <?php endif; ?>
        </div>
        
        <div style="max-height: 400px; overflow-y: auto;">
            <?php if ($total_notificaciones > 0): ?>
                
                <!-- Pagos Atrasados -->
                <?php if ($count_atrasados > 0): ?>
                    <a href="dashboard.php#atrasados" class="text-decoration-none">
                        <div class="notification-item-widget">
                            <div class="d-flex align-items-center">
                                <div style="font-size: 2rem; margin-right: 1rem;">⚠️</div>
                                <div>
                                    <strong class="text-danger">Pagos Atrasados</strong>
                                    <p class="mb-0 text-muted small">
                                        Hay <?php echo $count_atrasados; ?> pago(s) atrasado(s)
                                    </p>
                                </div>
                            </div>
                        </div>
                    </a>
                <?php endif; ?>
                
                <!-- Pagos Próximos -->
                <?php if ($count_proximos > 0): ?>
                    <a href="index.php" class="text-decoration-none">
                        <div class="notification-item-widget">
                            <div class="d-flex align-items-center">
                                <div style="font-size: 2rem; margin-right: 1rem;">📅</div>
                                <div>
                                    <strong class="text-primary">Pagos Próximos</strong>
                                    <p class="mb-0 text-muted small">
                                        <?php echo $count_proximos; ?> pago(s) en los próximos 3 días
                                    </p>
                                </div>
                            </div>
                        </div>
                    </a>
                <?php endif; ?>
                
                <!-- Clientes Finalizados -->
                <?php if ($count_finalizados > 0): ?>
                    <a href="dashboard.php#finalizados" class="text-decoration-none">
                        <div class="notification-item-widget">
                            <div class="d-flex align-items-center">
                                <div style="font-size: 2rem; margin-right: 1rem;">🎉</div>
                                <div>
                                    <strong class="text-success">Pagos Finalizados</strong>
                                    <p class="mb-0 text-muted small">
                                        <?php echo $count_finalizados; ?> cliente(s) finalizó esta semana
                                    </p>
                                </div>
                            </div>
                        </div>
                    </a>
                <?php endif; ?>
                
            <?php else: ?>
                <div class="notification-item-widget text-center">
                    <div style="font-size: 3rem;">✅</div>
                    <p class="text-muted mb-0">No hay notificaciones nuevas</p>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="notification-footer-widget">
            <a href="dashboard.php" class="btn btn-sm btn-primary">
                Ver Dashboard Completo
            </a>
        </div>
    </div>
</div>

<!-- JS personalizado movido a javascript/notificaciones_widget.js -->

<script>
function toggleNotifications(widgetId) {
    var widget = document.getElementById(widgetId);
    widget.classList.toggle('active');
    // Cierra el menú si se hace click fuera
    document.addEventListener('click', function(e) {
        if (!widget.contains(e.target)) {
            widget.classList.remove('active');
        }
    }, { once: true });
}
</script>
