<?php

/**
 * Helpers para generar el PDF de Estado de Cuenta (Dompdf)
 *
 * - Reutilizable desde estado_cuenta_pdf.php (descarga)
 * - Reutilizable desde guardar.php (adjuntar por email)
 */

function mv_estado_cuenta_filename(array $cliente): string
{
    $cliente_nombre = isset($cliente['nombre_completo']) ? (string)$cliente['nombre_completo'] : 'cliente';
    $cliente_nombre_file = preg_replace('/[^A-Za-z0-9_-]+/', '_', strtolower($cliente_nombre));
    return 'estado_cuenta_' . $cliente_nombre_file . '_' . date('Ymd_His') . '.pdf';
}

function mv_estado_cuenta_build_html(array $cliente, array $pagos, array $stats): string
{
    $sena = isset($cliente['sena']) ? (float)$cliente['sena'] : 0.0;
    $valorTotal = isset($cliente['valor_total']) ? (float)$cliente['valor_total'] : 0.0;
    $cuotas = isset($cliente['cuotas']) ? (int)$cliente['cuotas'] : 0;

    $saldo_restante = $valorTotal - $sena;
    $monto_por_cuota = $cuotas > 0 ? $saldo_restante / $cuotas : 0.0;

    $cuotas_pagadas = isset($stats['cuotas_pagadas']) ? (int)$stats['cuotas_pagadas'] : 0;
    $total_cuotas = isset($stats['total_cuotas']) ? (int)$stats['total_cuotas'] : 0;
    $progreso = $total_cuotas > 0 ? ($cuotas_pagadas / $total_cuotas) * 100 : 0;

    $html = '<!doctype html><html lang="es"><head><meta charset="UTF-8">'
        . '<style>'
        . 'body{font-family:DejaVu Sans, Arial, sans-serif;font-size:12px;color:#111;}'
        . 'h2{margin:0 0 4px 0;} .muted{color:#666;}'
        . '.box{border:1px solid #ddd;border-radius:6px;padding:10px;margin:10px 0;}'
        . 'table{width:100%;border-collapse:collapse;margin-top:8px;}'
        . 'th,td{border:1px solid #ddd;padding:6px;}'
        . 'th{background:#f3f4f6;text-align:left;}'
        . '.badge{display:inline-block;padding:2px 8px;border-radius:10px;font-size:11px;color:#fff;}'
        . '.b-success{background:#16a34a;} .b-warning{background:#f59e0b;}'
        . '</style></head><body>';

    $html .= '<div class="box">'
        . '<h2>Mujeres Virtuosas</h2>'
        . '<div class="muted">Estado de Cuenta</div>'
        . '<div class="muted">Generado el ' . date('d/m/Y H:i') . '</div>'
        . '</div>';

    $html .= '<div class="box">'
        . '<strong>Nombre:</strong> ' . htmlspecialchars((string)($cliente['nombre_completo'] ?? '')) . '<br>'
        . '<strong>Teléfono:</strong> ' . htmlspecialchars((string)($cliente['telefono'] ?? '')) . '<br>'
        . '<strong>Dirección:</strong> ' . htmlspecialchars((string)($cliente['direccion'] ?? '')) . '<br>'
        . '</div>';

    $html .= '<div class="box">'
        . '<strong>Artículos:</strong><br>'
        . nl2br(htmlspecialchars((string)($cliente['articulos'] ?? '')))
        . '</div>';

    $html .= '<div class="box">'
        . '<strong>Detalle económico</strong><br>'
        . 'Valor total: $' . number_format($valorTotal, 2, ',', '.') . '<br>'
        . 'Seña / Adelanto: $' . number_format($sena, 2, ',', '.') . '<br>'
        . 'Saldo restante: $' . number_format($saldo_restante, 2, ',', '.') . '<br>'
        . 'Cuotas: ' . $cuotas . '<br>'
        . 'Monto por cuota: $' . number_format($monto_por_cuota, 2, ',', '.') . '<br>'
        . '</div>';

    $html .= '<div class="box">'
        . '<strong>Calendario de pagos</strong><br>'
        . '<span class="muted">Progreso: ' . $cuotas_pagadas . ' de ' . $total_cuotas . ' (' . number_format((float)$progreso, 1) . '%)</span>';

    $html .= '<table><thead><tr>'
        . '<th>Cuota</th><th>Fecha programada</th><th>Monto</th><th>Estado</th><th>Fecha pago</th>'
        . '</tr></thead><tbody>';

    if (!empty($pagos)) {
        foreach ($pagos as $pago) {
            $estado = (isset($pago['estado']) && $pago['estado'] === 'pagado') ? 'Pagado' : 'Pendiente';
            $badgeClass = $estado === 'Pagado' ? 'b-success' : 'b-warning';

            $fechaProgramada = isset($pago['fecha_programada']) && $pago['fecha_programada']
                ? date('d/m/Y', strtotime((string)$pago['fecha_programada']))
                : '-';
            $fechaPagoTexto = isset($pago['fecha_pago']) && $pago['fecha_pago']
                ? date('d/m/Y', strtotime((string)$pago['fecha_pago']))
                : '-';

            $html .= '<tr>'
                . '<td><strong>' . (int)($pago['numero_cuota'] ?? 0) . '</strong></td>'
                . '<td>' . $fechaProgramada . '</td>'
                . '<td>$' . number_format((float)($pago['monto'] ?? 0), 2, ',', '.') . '</td>'
                . '<td><span class="badge ' . $badgeClass . '">' . $estado . '</span></td>'
                . '<td>' . $fechaPagoTexto . '</td>'
                . '</tr>';
        }
    } else {
        $html .= '<tr><td colspan="5" style="text-align:center" class="muted">No hay cuotas programadas para este cliente.</td></tr>';
    }

    $html .= '</tbody></table></div>';
    $html .= '</body></html>';

    return $html;
}

function mv_estado_cuenta_pdf_bytes(array $cliente, array $pagos, array $stats): string
{
    $autoload = dirname(__DIR__) . '/vendor/autoload.php';
    if (!is_file($autoload)) {
        throw new RuntimeException('Falta vendor/autoload.php. Ejecutá: composer install');
    }
    require_once $autoload;

    $options = new \Dompdf\Options();
    $options->set('defaultFont', 'DejaVu Sans');
    $options->set('isRemoteEnabled', false);

    $dompdf = new \Dompdf\Dompdf($options);
    $dompdf->loadHtml(mv_estado_cuenta_build_html($cliente, $pagos, $stats), 'UTF-8');
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    return $dompdf->output();
}
