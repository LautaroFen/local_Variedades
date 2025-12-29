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

// Tomar la fecha "oficial" desde MySQL (evita desfasajes de timezone entre PHP y DB)
$db_today = null;
$db_two_days_ago = null;
$res_now = mysqli_query($conn, "SELECT CURDATE() AS today, DATE_SUB(CURDATE(), INTERVAL 2 DAY) AS two_days_ago");
if ($res_now) {
    $row_now = mysqli_fetch_assoc($res_now);
    $db_today = isset($row_now['today']) ? (string)$row_now['today'] : null;
    $db_two_days_ago = isset($row_now['two_days_ago']) ? (string)$row_now['two_days_ago'] : null;
}
if (!$db_today || !$db_two_days_ago) {
    // Fallback: si falla, usar fecha del servidor (menos confiable)
    $db_today = date('Y-m-d');
    $db_two_days_ago = date('Y-m-d', strtotime('-2 days'));
}
mv_echo('DB CURDATE(): ' . $db_today, $es_cli);

// =========================
// CAMBIOS DE ESTADO (solo cuando cambia a ATRASADO o FINALIZADO)
//
// Para detectar transiciones, guardamos el último estado visto por cliente.
// IMPORTANTE: en la primera ejecución, se inicializa el cache sin enviar correos
// (para evitar spam de todos los atrasados/finalizados históricos).
// =========================

$create_cache_sql = "CREATE TABLE IF NOT EXISTS mv_estado_clientes (
    cliente_id INT(11) NOT NULL,
    estado ENUM('pendiente','atrasado','finalizado') NOT NULL,
    notificado_atrasado_at DATETIME NULL,
    notificado_finalizado_at DATETIME NULL,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (cliente_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if (!mysqli_query($conn, $create_cache_sql)) {
    mv_echo('ERROR: No se pudo crear/verificar tabla mv_estado_clientes: ' . mysqli_error($conn), $es_cli);
    if (!$es_cli) {
        echo '</div></body></html>';
    }
    exit;
}

// Si la tabla ya existía de antes, intentar agregar columnas (compatibilidad)
$prevReportMode = mysqli_report(MYSQLI_REPORT_OFF);
@mysqli_query($conn, "ALTER TABLE mv_estado_clientes ADD COLUMN notificado_atrasado_at DATETIME NULL");
@mysqli_query($conn, "ALTER TABLE mv_estado_clientes ADD COLUMN notificado_finalizado_at DATETIME NULL");
mysqli_report($prevReportMode);

// Cargar estados previos
$previos = [];
$noti_prev = [];
$res_prev = mysqli_query($conn, "SELECT cliente_id, estado FROM mv_estado_clientes");
$res_prev = mysqli_query($conn, "SELECT cliente_id, estado, notificado_atrasado_at, notificado_finalizado_at FROM mv_estado_clientes");
if ($res_prev) {
    while ($row = mysqli_fetch_assoc($res_prev)) {
        $cid = (int)$row['cliente_id'];
        $previos[$cid] = (string)$row['estado'];
        $noti_prev[$cid] = [
            'atrasado' => isset($row['notificado_atrasado_at']) ? (string)$row['notificado_atrasado_at'] : '',
            'finalizado' => isset($row['notificado_finalizado_at']) ? (string)$row['notificado_finalizado_at'] : '',
        ];
    }
}
$cache_vacio = (count($previos) === 0);

// Estado actual por cliente (derivado de pagos_clientes)
$query_estado_actual = "SELECT
    c.id,
    c.nombre_completo,
    c.telefono,
    c.barrio,
    c.valor_total,
    COUNT(pc.id) AS total_cuotas,
    SUM(CASE WHEN pc.estado = 'pendiente' THEN 1 ELSE 0 END) AS pendientes,
    SUM(CASE WHEN pc.estado = 'pendiente' AND pc.fecha_programada < CURDATE() THEN 1 ELSE 0 END) AS atrasadas,
    SUM(CASE WHEN pc.estado = 'pendiente' AND pc.fecha_programada < CURDATE() THEN pc.monto ELSE 0 END) AS monto_total_atrasado,
    MIN(CASE WHEN pc.estado = 'pendiente' AND pc.fecha_programada < CURDATE() THEN pc.fecha_programada ELSE NULL END) AS fecha_mas_antigua,
    MAX(pc.fecha_pago) AS fecha_ultimo_pago
FROM clientes c
LEFT JOIN pagos_clientes pc ON c.id = pc.cliente_id
GROUP BY c.id
ORDER BY c.id DESC";

$clientes_atrasados = [];
$clientes_finalizados = [];
$inicializados = 0;
$actualizados = 0;

$res_actual = mysqli_query($conn, $query_estado_actual);
if (!$res_actual) {
    mv_echo('ERROR: No se pudo consultar estado actual: ' . mysqli_error($conn), $es_cli);
    if (!$es_cli) {
        echo '</div></body></html>';
    }
    exit;
}

// Prepared statement para upsert del cache
$stmt_upsert = mysqli_prepare($conn, "INSERT INTO mv_estado_clientes (cliente_id, estado) VALUES (?, ?)
    ON DUPLICATE KEY UPDATE
        estado = VALUES(estado),
        updated_at = CURRENT_TIMESTAMP,
        notificado_atrasado_at = IF(VALUES(estado) = 'atrasado', notificado_atrasado_at, NULL),
        notificado_finalizado_at = IF(VALUES(estado) = 'finalizado', notificado_finalizado_at, NULL)");
if (!$stmt_upsert) {
    mv_echo('ERROR: No se pudo preparar upsert de cache: ' . mysqli_error($conn), $es_cli);
    if (!$es_cli) {
        echo '</div></body></html>';
    }
    exit;
}

while ($c = mysqli_fetch_assoc($res_actual)) {
    $id = (int)$c['id'];
    $total = (int)$c['total_cuotas'];
    $pendientes = (int)$c['pendientes'];
    $atrasadas = (int)$c['atrasadas'];

    // Determinar estado actual
    if ($total > 0 && $pendientes === 0) {
        $estado_actual = 'finalizado';
    } elseif ($atrasadas > 0) {
        $estado_actual = 'atrasado';
    } else {
        $estado_actual = 'pendiente';
    }

    $estado_previo = $previos[$id] ?? null;

    // Si es la primera vez que lo vemos, inicializamos cache.
    // Además, si el estado es reciente y todavía no se notificó, permitimos un "catch-up".
    if ($estado_previo === null) {
        mysqli_stmt_bind_param($stmt_upsert, 'is', $id, $estado_actual);
        mysqli_stmt_execute($stmt_upsert);
        $inicializados++;

        if ($estado_actual === 'finalizado') {
            $fechaUlt = isset($c['fecha_ultimo_pago']) ? (string)$c['fecha_ultimo_pago'] : '';
            if ($fechaUlt !== '' && $fechaUlt >= $db_two_days_ago) {
                $clientes_finalizados[] = $c;
            }
        } elseif ($estado_actual === 'atrasado') {
            $fechaMasAnt = isset($c['fecha_mas_antigua']) ? (string)$c['fecha_mas_antigua'] : '';
            if ($fechaMasAnt !== '' && $fechaMasAnt >= $db_two_days_ago) {
                $clientes_atrasados[] = $c;
            }
        }

        continue;
    }

    // Si cambia de estado, o si está en atrasado/finalizado y nunca se notificó, se notifica.
    $ya_notificado_atrasado = isset($noti_prev[$id]['atrasado']) && trim((string)$noti_prev[$id]['atrasado']) !== '';
    $ya_notificado_finalizado = isset($noti_prev[$id]['finalizado']) && trim((string)$noti_prev[$id]['finalizado']) !== '';

    $deberia_notificar = false;
    if ($estado_actual === 'atrasado') {
        $fechaMasAnt = isset($c['fecha_mas_antigua']) ? (string)$c['fecha_mas_antigua'] : '';
        if (($estado_previo !== 'atrasado' || !$ya_notificado_atrasado) && $fechaMasAnt !== '' && $fechaMasAnt >= $db_two_days_ago) {
            $clientes_atrasados[] = $c;
            $deberia_notificar = true;
        }
    } elseif ($estado_actual === 'finalizado') {
        $fechaUlt = isset($c['fecha_ultimo_pago']) ? (string)$c['fecha_ultimo_pago'] : '';
        if (($estado_previo !== 'finalizado' || !$ya_notificado_finalizado) && $fechaUlt !== '' && $fechaUlt >= $db_two_days_ago) {
            $clientes_finalizados[] = $c;
            $deberia_notificar = true;
        }
    }

    if ($estado_previo !== $estado_actual) {
        mysqli_stmt_bind_param($stmt_upsert, 'is', $id, $estado_actual);
        mysqli_stmt_execute($stmt_upsert);
        $actualizados++;
    }
}

mysqli_stmt_close($stmt_upsert);

$total_atrasados = count($clientes_atrasados);
$total_finalizados = count($clientes_finalizados);

mv_echo('Cache inicializados (sin notificar): ' . $inicializados, $es_cli);
mv_echo('Cache actualizados (cambios detectados): ' . $actualizados, $es_cli);
mv_echo('Nuevos ATRASADOS: ' . $total_atrasados, $es_cli);
mv_echo('Nuevos FINALIZADOS: ' . $total_finalizados, $es_cli);

if ($total_atrasados === 0 && $total_finalizados === 0) {
    mv_echo('No hay cambios a ATRASADO/FINALIZADO. No se envía correo.', $es_cli);
    if (!$es_cli) {
        echo '<div class="alert alert-success">No hay novedades.</div>';
        echo '</div></body></html>';
    }
    mysqli_close($conn);
    exit;
}

// =========================
// ENVIAR CORREOS POR SEPARADO
// =========================

$ok_atrasados = null;
$ok_finalizados = null;

$baseCss = 'body{font-family:Arial,sans-serif;color:#111} table{width:100%;border-collapse:collapse;margin-top:8px}'
    . 'th,td{border:1px solid #e5e7eb;padding:10px} th{background:#f3f4f6;text-align:left}'
    . 'h2{margin:0 0 6px 0} .muted{font-size:12px;color:#6b7280}';

if ($total_atrasados > 0) {
    $filas_atrasados = '';
    foreach ($clientes_atrasados as $c) {
        $filas_atrasados .= '<tr>'
            . '<td>' . htmlspecialchars($c['nombre_completo']) . '</td>'
            . '<td>' . htmlspecialchars($c['telefono']) . '</td>'
            . '<td>' . htmlspecialchars($c['barrio']) . '</td>'
            . '<td style="text-align:center">' . (int)$c['atrasadas'] . '</td>'
            . '<td style="text-align:right">$' . number_format((float)$c['monto_total_atrasado'], 0, ',', '.') . '</td>'
            . '<td>' . htmlspecialchars((string)$c['fecha_mas_antigua']) . '</td>'
            . '</tr>';
    }

    $html_atrasados = '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><style>'
        . $baseCss
        . '</style></head><body>'
        . '<h2>Nuevos Pagos Atrasados</h2>'
        . '<p>Fecha: <strong>' . htmlspecialchars($db_today) . '</strong></p>'
        . '<p>Nuevos clientes que pasaron a atrasados: <strong>' . $total_atrasados . '</strong></p>'
        . '<table><thead><tr>'
        . '<th>Cliente</th><th>Teléfono</th><th>Barrio</th><th>Atrasados</th><th>Monto</th><th>Más antiguo</th>'
        . '</tr></thead><tbody>' . $filas_atrasados . '</tbody></table>'
        . '<p class="muted" style="margin-top:16px">Este es un correo automático, por favor no responder.</p>'
        . '</body></html>';

    $asunto_atrasados = 'Pagos atrasados: ' . $total_atrasados . ' nuevo(s)';
    $ok_atrasados = mv_enviar_correo($destinatario, $asunto_atrasados, $html_atrasados);

    if ($ok_atrasados) {
        foreach ($clientes_atrasados as $c) {
            $cid = (int)$c['id'];
            @mysqli_query($conn, "UPDATE mv_estado_clientes SET notificado_atrasado_at = NOW() WHERE cliente_id = $cid");
        }
        mv_echo('OK: correo ATRASADOS enviado.', $es_cli);
        if (!$es_cli) {
            echo '<div class="alert alert-success">Correo de atrasados enviado.</div>';
        }
    } else {
        $detalle = isset($GLOBALS['MAILER_LAST_ERROR']) ? (string)$GLOBALS['MAILER_LAST_ERROR'] : '';
        mv_echo('ERROR: no se pudo enviar ATRASADOS. ' . $detalle, $es_cli);
        if (!$es_cli) {
            echo '<div class="alert alert-danger">Error al enviar atrasados. ' . htmlspecialchars($detalle) . '</div>';
        }
    }
}

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

    $html_finalizados = '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><style>'
        . $baseCss
        . '</style></head><body>'
        . '<h2>Nuevos Pagos Finalizados</h2>'
        . '<p>Fecha: <strong>' . htmlspecialchars($db_today) . '</strong></p>'
        . '<p>Nuevos clientes que pasaron a finalizados: <strong>' . $total_finalizados . '</strong></p>'
        . '<table><thead><tr>'
        . '<th>Cliente</th><th>Teléfono</th><th>Barrio</th><th>Valor total</th><th>Último pago</th>'
        . '</tr></thead><tbody>' . $filas_finalizados . '</tbody></table>'
        . '<p class="muted" style="margin-top:16px">Este es un correo automático, por favor no responder.</p>'
        . '</body></html>';

    $asunto_finalizados = 'Pagos finalizados: ' . $total_finalizados . ' nuevo(s)';
    $ok_finalizados = mv_enviar_correo($destinatario, $asunto_finalizados, $html_finalizados);

    if ($ok_finalizados) {
        foreach ($clientes_finalizados as $c) {
            $cid = (int)$c['id'];
            @mysqli_query($conn, "UPDATE mv_estado_clientes SET notificado_finalizado_at = NOW() WHERE cliente_id = $cid");
        }
        mv_echo('OK: correo FINALIZADOS enviado.', $es_cli);
        if (!$es_cli) {
            echo '<div class="alert alert-success">Correo de finalizados enviado.</div>';
        }
    } else {
        $detalle = isset($GLOBALS['MAILER_LAST_ERROR']) ? (string)$GLOBALS['MAILER_LAST_ERROR'] : '';
        mv_echo('ERROR: no se pudo enviar FINALIZADOS. ' . $detalle, $es_cli);
        if (!$es_cli) {
            echo '<div class="alert alert-danger">Error al enviar finalizados. ' . htmlspecialchars($detalle) . '</div>';
        }
    }
}

if (!$es_cli) {
    echo '</div></body></html>';
}

mysqli_close($conn);
