<?php
session_start();

// Verificar que el usuario esté autenticado
if (!isset($_SESSION['usuario'])) {
    header('Location: login.php');
    exit;
}

include("conexion.php");

// 📊 ESTADÍSTICAS GENERALES
$query_total_clientes = "SELECT COUNT(*) as total FROM clientes";
$resultado_total = mysqli_query($conn, $query_total_clientes);
$total_clientes = mysqli_fetch_assoc($resultado_total)['total'];

$query_valor_total = "SELECT SUM(valor_total) as total FROM clientes";
$resultado_valor = mysqli_query($conn, $query_valor_total);
$valor_total_prestado = mysqli_fetch_assoc($resultado_valor)['total'] ?? 0;

// Calcular dinero pendiente y cobrado
$query_pagos = "SELECT 
    SUM(CASE WHEN estado = 'pagado' THEN monto ELSE 0 END) as cobrado,
    SUM(CASE WHEN estado = 'pendiente' THEN monto ELSE 0 END) as pendiente
FROM pagos_clientes";
$resultado_pagos = mysqli_query($conn, $query_pagos);
$pagos_data = mysqli_fetch_assoc($resultado_pagos);
$dinero_cobrado = $pagos_data['cobrado'] ?? 0;
$dinero_pendiente = $pagos_data['pendiente'] ?? 0;

// Clientes con pagos finalizados
$query_finalizados = "SELECT COUNT(DISTINCT pc.cliente_id) as total
FROM pagos_clientes pc
WHERE NOT EXISTS (
    SELECT 1 FROM pagos_clientes pc2 
    WHERE pc2.cliente_id = pc.cliente_id AND pc2.estado = 'pendiente'
)";
$resultado_finalizados = mysqli_query($conn, $query_finalizados);
$clientes_finalizados = mysqli_fetch_assoc($resultado_finalizados)['total'];

// Clientes activos (con pagos pendientes)
$clientes_activos = $total_clientes - $clientes_finalizados;

// Pagos próximos (siguiente semana)
$query_proximos = "SELECT COUNT(*) as total 
FROM pagos_clientes 
WHERE estado = 'pendiente' 
AND fecha_programada BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)";
$resultado_proximos = mysqli_query($conn, $query_proximos);
$pagos_proximos = mysqli_fetch_assoc($resultado_proximos)['total'];

// Pagos atrasados
$query_atrasados = "SELECT COUNT(*) as total 
FROM pagos_clientes 
WHERE estado = 'pendiente' 
AND fecha_programada < CURDATE()";
$resultado_atrasados = mysqli_query($conn, $query_atrasados);
$pagos_atrasados = mysqli_fetch_assoc($resultado_atrasados)['total'];

// Top 5 clientes con mayor deuda pendiente
$query_top_deuda = "SELECT c.nombre_completo, c.telefono, SUM(pc.monto) as deuda_total
FROM clientes c
JOIN pagos_clientes pc ON c.id = pc.cliente_id
WHERE pc.estado = 'pendiente'
GROUP BY c.id
ORDER BY deuda_total DESC
LIMIT 5";
$resultado_top_deuda = mysqli_query($conn, $query_top_deuda);

// 🔔 NOTIFICACIONES - Clientes con pagos atrasados
$query_atrasados_detalle = "SELECT DISTINCT
    c.id,
    c.nombre_completo,
    c.telefono,
    c.barrio,
    COUNT(pc.id) as pagos_atrasados,
    SUM(pc.monto) as monto_total_atrasado,
    MIN(pc.fecha_programada) as fecha_mas_antigua
FROM clientes c
JOIN pagos_clientes pc ON c.id = pc.cliente_id
WHERE pc.estado = 'pendiente' AND pc.fecha_programada < CURDATE()
GROUP BY c.id
ORDER BY fecha_mas_antigua ASC
LIMIT 10";
$resultado_atrasados_detalle = mysqli_query($conn, $query_atrasados_detalle);

// 🔔 NOTIFICACIONES - Clientes que finalizaron sus pagos (últimos 30 días)
$query_finalizados_recientes = "SELECT 
    c.id,
    c.nombre_completo,
    c.telefono,
    c.barrio,
    c.valor_total,
    MAX(pc.fecha_pago) as fecha_ultimo_pago
FROM clientes c
JOIN pagos_clientes pc ON c.id = pc.cliente_id
WHERE NOT EXISTS (
    SELECT 1 FROM pagos_clientes pc2 
    WHERE pc2.cliente_id = c.id AND pc2.estado = 'pendiente'
)
GROUP BY c.id
HAVING MAX(pc.fecha_pago) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
ORDER BY fecha_ultimo_pago DESC
LIMIT 10";
$resultado_finalizados_recientes = mysqli_query($conn, $query_finalizados_recientes);

include("includes/header.php");
?>
<main>

    <div class="container my-5">

        <div class="d-flex align-items-center justify-content-between mb-4 page-title-banner" style="background: linear-gradient(135deg, #2563eb 0%, #1486e2 100%); border-radius: 1rem; padding: 1.2rem 1rem; box-shadow: 0 2px 12px rgba(0,0,0,0.08);">
            <i class="bi bi-bar-chart-line display-4 text-white flex-shrink-0" style="text-shadow: 0 2px 8px #0002;"></i>
            <h1 class="mb-0 text-white fw-bold text-uppercase flex-grow-1 text-center">Estadísticas</h1>
            <i class="bi bi-bar-chart-line display-4 text-white flex-shrink-0" style="text-shadow: 0 2px 8px #0002;"></i>
        </div>
        <!-- TARJETAS DE ESTADÍSTICAS -->
        <div class="row mb-4 g-4">
            <!-- Total Clientes -->
            <div class="col-md-6 col-lg-3">
                <div class="stat-card hover-lift" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                    <div class="stat-icon">👥</div>
                    <div class="stat-value"><?php echo $total_clientes; ?></div>
                    <div class="stat-label">Total Clientes</div>
                    <hr style="border-color: rgba(255,255,255,0.3); margin: 1rem 0;">
                    <div class="d-flex justify-content-between">
                        <small><i class="bi bi-check-lg me-1"></i>Finalizados: <?php echo $clientes_finalizados; ?></small>
                        <small>⏳ Activos: <?php echo $clientes_activos; ?></small>
                    </div>
                </div>
            </div>

            <!-- Dinero Total Prestado -->
            <div class="col-md-6 col-lg-3">
                <div class="stat-card hover-lift" style="background: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%);">
                    <div class="stat-icon">💰</div>
                    <div class="stat-value">$<?php echo number_format($valor_total_prestado, 0, ',', '.'); ?></div>
                    <div class="stat-label">Total Prestado</div>
                    <hr style="border-color: rgba(255,255,255,0.3); margin: 1rem 0;">
                    <small>Capital en circulación</small>
                </div>
            </div>

            <!-- Dinero Cobrado -->
            <div class="col-md-6 col-lg-3">
                <div class="stat-card hover-lift" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);">
                    <div class="stat-icon"><i class="bi bi-check-circle-fill"></i></div>
                    <div class="stat-value">$<?php echo number_format($dinero_cobrado, 0, ',', '.'); ?></div>
                    <div class="stat-label">Dinero Cobrado</div>
                    <hr style="border-color: rgba(255,255,255,0.3); margin: 1rem 0;">
                    <small>
                        <?php
                        $porcentaje_cobrado = $valor_total_prestado > 0 ? ($dinero_cobrado / $valor_total_prestado * 100) : 0;
                        echo number_format($porcentaje_cobrado, 1) . '% del total';
                        ?>
                    </small>
                </div>
            </div>

            <!-- Dinero Pendiente -->
            <div class="col-md-6 col-lg-3">
                <div class="stat-card hover-lift" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);">
                    <div class="stat-icon">⏳</div>
                    <div class="stat-value">$<?php echo number_format($dinero_pendiente, 0, ',', '.'); ?></div>
                    <div class="stat-label">Dinero Pendiente</div>
                    <hr style="border-color: rgba(255,255,255,0.3); margin: 1rem 0;">
                    <small>
                        <?php
                        $porcentaje_pendiente = $valor_total_prestado > 0 ? ($dinero_pendiente / $valor_total_prestado * 100) : 0;
                        echo number_format($porcentaje_pendiente, 1) . '% del total';
                        ?>
                    </small>
                </div>
            </div>
        </div>

        <!-- ALERTAS -->
        <div class="row mb-4 g-4">
            <div class="col-md-6">
                <div class="alert alert-info alert-permanent animate-slide-in">
                    <div class="d-flex align-items-center">
                        <div class="fs-2 me-3">📅</div>
                        <div>
                            <h6 class="mb-1 fw-bold">Pagos Próximos (7 días)</h6>
                            <p class="mb-0">Hay <strong><?php echo $pagos_proximos; ?> pagos</strong> programados para esta semana</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="alert alert-danger alert-permanent animate-slide-in">
                    <div class="d-flex align-items-center">
                        <div class="fs-2 me-3">⚠️</div>
                        <div>
                            <h6 class="mb-1 fw-bold">Pagos Atrasados</h6>
                            <p class="mb-0">Hay <strong><?php echo $pagos_atrasados; ?> pagos atrasados</strong> que requieren seguimiento</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 🔔 SISTEMA DE NOTIFICACIONES -->
        <div class="row mb-4 g-4" id="notificaciones">
            <!-- NOTIFICACIONES: Pagos Atrasados -->
            <div class="col-lg-6" id="atrasados">
                <div class="card hover-lift" style="border-left: 4px solid #ef4444;">
                    <div class="card-header" style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); color: white;">
                        <h5 class="mb-0">
                            <i class="bi bi-exclamation-triangle-fill"></i> Clientes con Pagos Atrasados
                            <span class="badge bg-white text-danger float-end"><?php echo mysqli_num_rows($resultado_atrasados_detalle); ?></span>
                        </h5>
                    </div>
                    <div class="card-body p-0" style="max-height: 400px; overflow-y: auto;">
                        <?php if (mysqli_num_rows($resultado_atrasados_detalle) > 0): ?>
                            <div class="list-group list-group-flush">
                                <?php while ($atrasado = mysqli_fetch_assoc($resultado_atrasados_detalle)):
                                    $dias_atraso = (strtotime('today') - strtotime($atrasado['fecha_mas_antigua'])) / 86400;
                                ?>
                                    <div class="list-group-item list-group-item-action">
                                        <div class="d-flex w-100 justify-content-between align-items-start">
                                            <div>
                                                <h6 class="mb-1">
                                                    <strong><?php echo htmlspecialchars($atrasado['nombre_completo']); ?></strong>
                                                </h6>
                                                <p class="mb-1 text-muted">
                                                    <small>
                                                        📞 <?php echo htmlspecialchars($atrasado['telefono']); ?> |
                                                        📍 <?php echo htmlspecialchars($atrasado['barrio']); ?>
                                                    </small>
                                                </p>
                                                <div class="mt-2">
                                                    <span class="badge bg-danger">
                                                        <?php echo $atrasado['pagos_atrasados']; ?> pago(s) atrasado(s)
                                                    </span>
                                                    <span class="badge bg-warning text-dark">
                                                        <?php echo round($dias_atraso); ?> días de atraso
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="text-end">
                                                <h5 class="mb-0 text-danger">
                                                    $<?php echo number_format($atrasado['monto_total_atrasado'], 0, ',', '.'); ?>
                                                </h5>
                                                <small class="text-muted">Deuda</small>
                                                <div class="mt-2">
                                                    <a href="ver.php?id=<?php echo $atrasado['id']; ?>"
                                                        class="btn btn-sm btn-outline-primary"
                                                        title="Ver detalles">
                                                        👁️ Ver
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-5">
                                <div class="fs-1 mb-3"><i class="bi bi-check-circle-fill"></i></div>
                                <h6 class="text-muted">¡Excelente! No hay pagos atrasados</h6>
                                <p class="text-muted mb-0">Todos los clientes están al día</p>
                            </div>
                        <?php endif; ?>
                    </div>
                    <?php if (mysqli_num_rows($resultado_atrasados_detalle) > 0): ?>
                        <div class="card-footer text-center">
                            <a href="index.php?estado=atrasado" class="btn btn-sm btn-danger">
                                Ver todos los clientes con pagos atrasados →
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- NOTIFICACIONES: Pagos Finalizados -->
            <div class="col-lg-6" id="finalizados">
                <div class="card hover-lift" style="border-left: 4px solid #10b981;">
                    <div class="card-header" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white;">
                        <h5 class="mb-0">
                            <i class="bi bi-check-circle-fill"></i> Clientes que Finalizaron sus Pagos
                            <span class="badge bg-white text-success float-end"><?php echo mysqli_num_rows($resultado_finalizados_recientes); ?></span>
                        </h5>
                    </div>
                    <div class="card-body p-0" style="max-height: 400px; overflow-y: auto;">
                        <?php if (mysqli_num_rows($resultado_finalizados_recientes) > 0): ?>
                            <div class="list-group list-group-flush">
                                <?php while ($finalizado = mysqli_fetch_assoc($resultado_finalizados_recientes)):
                                    $dias_desde_finalizacion = (strtotime('today') - strtotime($finalizado['fecha_ultimo_pago'])) / 86400;
                                ?>
                                    <div class="list-group-item list-group-item-action">
                                        <div class="d-flex w-100 justify-content-between align-items-start">
                                            <div>
                                                <h6 class="mb-1">
                                                    <strong><?php echo htmlspecialchars($finalizado['nombre_completo']); ?></strong>
                                                </h6>
                                                <p class="mb-1 text-muted">
                                                    <small>
                                                        📞 <?php echo htmlspecialchars($finalizado['telefono']); ?> |
                                                        📍 <?php echo htmlspecialchars($finalizado['barrio']); ?>
                                                    </small>
                                                </p>
                                                <div class="mt-2">
                                                    <span class="badge bg-success">
                                                        <i class="bi bi-check-lg me-1"></i>Pagos completados
                                                    </span>
                                                    <span class="badge bg-info text-dark">
                                                        Hace <?php echo round($dias_desde_finalizacion); ?> día(s)
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="text-end">
                                                <h5 class="mb-0 text-success">
                                                    $<?php echo number_format($finalizado['valor_total'], 0, ',', '.'); ?>
                                                </h5>
                                                <small class="text-muted">Total cobrado</small>
                                                <div class="mt-2">
                                                    <a href="ver.php?id=<?php echo $finalizado['id']; ?>"
                                                        class="btn btn-sm btn-outline-success"
                                                        title="Ver detalles">
                                                        👁️ Ver
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-5">
                                <div class="fs-1 mb-3">📋</div>
                                <h6 class="text-muted">No hay finalizaciones recientes</h6>
                                <p class="text-muted mb-0">Últimos 30 días</p>
                            </div>
                        <?php endif; ?>
                    </div>
                    <?php if (mysqli_num_rows($resultado_finalizados_recientes) > 0): ?>
                        <div class="card-footer text-center">
                            <a href="index.php?estado=finalizado" class="btn btn-sm btn-success">
                                Ver todos los clientes finalizados →
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- BARRA DE PROGRESO GENERAL -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card hover-lift">
                    <div class="card-body p-4">
                        <h5 class="fw-bold mb-4">📊 Progreso de Cobros</h5>
                        <div class="progress mb-3">
                            <div class="progress-bar" role="progressbar"
                                style="width: <?php echo $porcentaje_cobrado; ?>%; background: linear-gradient(90deg, #10b981 0%, #059669 100%);"
                                aria-valuenow="<?php echo $porcentaje_cobrado; ?>"
                                aria-valuemin="0" aria-valuemax="100">
                                <strong><?php echo number_format($porcentaje_cobrado, 1); ?>% Cobrado</strong>
                            </div>
                            <div class="progress-bar" role="progressbar"
                                style="width: <?php echo $porcentaje_pendiente; ?>%; background: linear-gradient(90deg, #f59e0b 0%, #d97706 100%);"
                                aria-valuenow="<?php echo $porcentaje_pendiente; ?>"
                                aria-valuemin="0" aria-valuemax="100">
                                <strong><?php echo number_format($porcentaje_pendiente, 1); ?>% Pendiente</strong>
                            </div>
                        </div>
                        <div class="d-flex justify-content-between text-muted">
                            <span><strong>Cobrado:</strong> $<?php echo number_format($dinero_cobrado, 0, ',', '.'); ?></span>
                            <span><strong>Pendiente:</strong> $<?php echo number_format($dinero_pendiente, 0, ',', '.'); ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- TOP 5 CLIENTES CON MAYOR DEUDA -->
        <div class="row">
            <div class="col-12">
                <div class="card hover-lift">
                    <div class="card-header">
                        <h5 class="mb-0">Top 5 - Clientes con Mayor Deuda Pendiente</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table mb-0">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Nombre</th>
                                        <th>Teléfono</th>
                                        <th>Deuda Pendiente</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    if (mysqli_num_rows($resultado_top_deuda) > 0) {
                                        $posicion = 1;
                                        while ($row = mysqli_fetch_assoc($resultado_top_deuda)):
                                    ?>
                                            <tr>
                                                <td>
                                                    <div class="badge bg-primary"><?php echo $posicion++; ?></div>
                                                </td>
                                                <td><strong><?php echo htmlspecialchars($row['nombre_completo']); ?></strong></td>
                                                <td><?php echo htmlspecialchars($row['telefono']); ?></td>
                                                <td><strong class="text-danger fs-5">$<?php echo number_format($row['deuda_total'], 2, ',', '.'); ?></strong></td>
                                            </tr>
                                    <?php
                                        endwhile;
                                    } else {
                                        echo '<tr><td colspan="4" class="text-center text-muted py-4"><i class="bi bi-check-lg me-1"></i>No hay deudas pendientes</td></tr>';
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- BOTONES DE ACCIÓN -->
        <div class="row mt-5">
            <div class="col-12 text-center">
                <a href="index.php" class="btn btn-primary btn-lg me-2">Volver al Inicio</a>
                <a href="exportar_excel.php" class="btn btn-success btn-lg">Exportar a Excel</a>
            </div>
        </div>
    </div>
</main>

<footer class="footer mt-5">
    <div class="container text-center">
        <p class="mb-2 text-muted">
            <strong>Mujeres Virtuosas S.A</strong> © 2025 - Todos los derechos reservados
        </p>
        <p class="mb-0">
            <small class="text-muted">Sistema de Gestión de Créditos</small>
        </p>
    </div>
</footer>