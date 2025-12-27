<?php
/**
 * Notificación de Estados de Pagos (Atrasados + Finalizados)
 *
 * - Se puede ejecutar por CLI (Programador de tareas / cron)
 * - También se puede abrir desde navegador para ver salida.
 */

require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/PHPMailer/email_config.php';

$es_cli = (php_sapi_name() === 'cli');

function mv_echo($text, $es_cli)
{
    if ($es_cli) {
        echo $text . "\n";
    } else {
        echo $text;
    }
}

if (!$es_cli) {
    echo '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title>Notificación Estados</title>';
    echo '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">';
    echo '<style>body{padding:2rem;background:#f3f4f6}.container{background:#fff;padding:2rem;border-radius:12px;max-width:1000px}</style>';
    echo '</head><body><div class="container">';
    echo '<h1 class="mb-3">Notificación de Estados</h1><hr>';
}

$destinatario = defined('EMAIL_TO') ? trim((string)EMAIL_TO) : '';
if ($destinatario === '' || !filter_var($destinatario, FILTER_VALIDATE_EMAIL)) {
    mv_echo('ERROR: EMAIL_TO no está configurado. Configurá el correo en PHPMailer/email_config.php (sección EDITAR AQUÍ).', $es_cli);
    if (!$es_cli) {
        echo '</div></body></html>';
    }
    exit;
}

mv_echo('Fecha: ' . date('Y-m-d H:i:s'), $es_cli);
mv_echo('Destinatario: ' . htmlspecialchars($destinatario), $es_cli);

// =========================
// ATRASADOS
// =========================
$query_atrasados = "SELECT DISTINCT
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
ORDER BY fecha_mas_antigua ASC";

$clientes_atrasados = [];
$resultado_atrasados = mysqli_query($conn, $query_atrasados);
if ($resultado_atrasados) {
    while ($row = mysqli_fetch_assoc($resultado_atrasados)) {
        $clientes_atrasados[] = $row;
    }
} else {
    mv_echo('WARN: No se pudo consultar atrasados: ' . mysqli_error($conn), $es_cli);
}

// =========================
// FINALIZADOS (últimos 7 días)
// Definición: cliente sin cuotas pendientes y con último pago reciente.
// =========================
$query_finalizados = "SELECT
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
HAVING MAX(pc.fecha_pago) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
ORDER BY fecha_ultimo_pago DESC";

$clientes_finalizados = [];
$resultado_finalizados = mysqli_query($conn, $query_finalizados);
if ($resultado_finalizados) {
    while ($row = mysqli_fetch_assoc($resultado_finalizados)) {
        $clientes_finalizados[] = $row;
    }
} else {
    mv_echo('WARN: No se pudo consultar finalizados: ' . mysqli_error($conn), $es_cli);
}

$total_atrasados = count($clientes_atrasados);
$total_finalizados = count($clientes_finalizados);

mv_echo('Atrasados (clientes): ' . $total_atrasados, $es_cli);
mv_echo('Finalizados (clientes últimos 7 días): ' . $total_finalizados, $es_cli);

if ($total_atrasados === 0 && $total_finalizados === 0) {
    mv_echo('No hay novedades (ni atrasados ni finalizados recientes). No se envía correo.', $es_cli);
    if (!$es_cli) {
        echo '<div class="alert alert-success">No hay novedades.</div>';
        echo '</div></body></html>';
    }
    mysqli_close($conn);
    exit;
}

// =========================
// ARMAR HTML
// =========================
$html = '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><style>'
    . 'body{font-family:Arial,sans-serif;color:#111} table{width:100%;border-collapse:collapse;margin-top:8px}'
    . 'th,td{border:1px solid #e5e7eb;padding:10px} th{background:#f3f4f6;text-align:left}'
    . 'h2{margin:0 0 6px 0} .muted{font-size:12px;color:#6b7280}'
    . '</style></head><body>'
    . '<h2>Estado de Pagos</h2>'
    . '<p>Resumen: <strong>Atrasados ' . $total_atrasados . '</strong> / <strong>Finalizados ' . $total_finalizados . '</strong> (últimos 7 días)</p>';

// Sección Atrasados
if ($total_atrasados > 0) {
    $filas_atrasados = '';
    foreach ($clientes_atrasados as $c) {
        $filas_atrasados .= '<tr>'
            . '<td>' . htmlspecialchars($c['nombre_completo']) . '</td>'
            . '<td>' . htmlspecialchars($c['telefono']) . '</td>'
            . '<td>' . htmlspecialchars($c['barrio']) . '</td>'
            . '<td style="text-align:center">' . (int)$c['pagos_atrasados'] . '</td>'
            . '<td style="text-align:right">$' . number_format((float)$c['monto_total_atrasado'], 0, ',', '.') . '</td>'
            . '<td>' . htmlspecialchars($c['fecha_mas_antigua']) . '</td>'
            . '</tr>';
    }

    $html .= '<hr><h2>Pagos Atrasados</h2>'
        . '<p>Total clientes con atrasos: <strong>' . $total_atrasados . '</strong></p>'
        . '<table><thead><tr>'
        . '<th>Cliente</th><th>Teléfono</th><th>Barrio</th><th>Atrasados</th><th>Monto</th><th>Más antiguo</th>'
        . '</tr></thead><tbody>' . $filas_atrasados . '</tbody></table>';
} else {
    $html .= '<hr><h2>Pagos Atrasados</h2><p>No hay pagos atrasados.</p>';
}

// Sección Finalizados
if ($total_finalizados > 0) {
    $filas_finalizados = '';
    foreach ($clientes_finalizados as $c) {
        $valor_total = isset($c['valor_total']) ? (float)$c['valor_total'] : 0;
        $filas_finalizados .= '<tr>'
            . '<td>' . htmlspecialchars($c['nombre_completo']) . '</td>'
            . '<td>' . htmlspecialchars($c['telefono']) . '</td>'
            . '<td>' . htmlspecialchars($c['barrio']) . '</td>'
            . '<td style="text-align:right">$' . number_format($valor_total, 0, ',', '.') . '</td>'
            . '<td>' . htmlspecialchars((string)$c['fecha_ultimo_pago']) . '</td>'
            . '</tr>';
    }

    $html .= '<hr><h2>Pagos Finalizados</h2>'
        . '<p>Total clientes finalizados (últimos 7 días): <strong>' . $total_finalizados . '</strong></p>'
        . '<table><thead><tr>'
        . '<th>Cliente</th><th>Teléfono</th><th>Barrio</th><th>Valor total</th><th>Último pago</th>'
        . '</tr></thead><tbody>' . $filas_finalizados . '</tbody></table>';
} else {
    $html .= '<hr><h2>Pagos Finalizados</h2><p>No hay finalizaciones recientes (últimos 7 días).</p>';
}

$html .= '<p class="muted" style="margin-top:16px">Este es un correo automático, por favor no responder.</p>'
    . '</body></html>';

$asunto = 'Estado de pagos: Atrasados (' . $total_atrasados . ') / Finalizados (' . $total_finalizados . ')';
$ok = mv_enviar_correo($destinatario, $asunto, $html);

if ($ok) {
    mv_echo('OK: correo enviado.', $es_cli);
    if (!$es_cli) {
        echo '<div class="alert alert-success">Correo enviado correctamente.</div>';
    }
} else {
    $detalle = isset($GLOBALS['MAILER_LAST_ERROR']) ? (string)$GLOBALS['MAILER_LAST_ERROR'] : '';
    mv_echo('ERROR: no se pudo enviar. ' . $detalle, $es_cli);
    if (!$es_cli) {
        echo '<div class="alert alert-danger">Error al enviar. ' . htmlspecialchars($detalle) . '</div>';
    }
}

if (!$es_cli) {
    echo '</div></body></html>';
}

mysqli_close($conn);
