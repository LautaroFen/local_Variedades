<?php
session_start();

// Verificar que el usuario esté autenticado
if (!isset($_SESSION['usuario'])) {
    header('Location: ../login.php');
    exit;
}

// PROTECCIÓN: Solo jefes pueden exportar datos
if (!isset($_SESSION['tipo_usuario']) || $_SESSION['tipo_usuario'] != 'jefe') {
    $_SESSION['message'] = '⛔ Acceso denegado. No tienes permisos para exportar datos.';
    $_SESSION['message_type'] = 'danger';
    header('Location: ../index.php');
    exit();
}

require_once __DIR__ . '/../conexion.php';

$vendedor_id = isset($_GET['vendedor_id']) ? intval($_GET['vendedor_id']) : 0;

$where = '';
if ($vendedor_id > 0) {
    $where = 'WHERE c.vendedor_id = ?';
}

$sql = "SELECT 
    c.id,
    c.nombre_completo,
    c.telefono,
    c.barrio,
    c.direccion,
    c.articulos,
    c.valor_total,
    c.sena,
    (c.valor_total - IFNULL(c.sena, 0)) as saldo_restante,
    c.frecuencia_pago,
    c.cuotas,
    c.fecha_primer_pago,
    c.fecha_registro,
    c.vendedor_id,
    ev.nombre_completo as vendedor_nombre,
    COUNT(pc.id) as total_cuotas_generadas,
    SUM(CASE WHEN pc.estado = 'pagado' THEN 1 ELSE 0 END) as cuotas_pagadas,
    SUM(CASE WHEN pc.estado = 'pendiente' THEN 1 ELSE 0 END) as cuotas_pendientes,
    SUM(CASE WHEN pc.estado = 'pagado' THEN pc.monto ELSE 0 END) as dinero_pagado,
    SUM(CASE WHEN pc.estado = 'pendiente' THEN pc.monto ELSE 0 END) as dinero_pendiente
FROM clientes c
LEFT JOIN empleados_vendedores ev ON ev.id = c.vendedor_id
LEFT JOIN pagos_clientes pc ON c.id = pc.cliente_id
$where
GROUP BY c.id
ORDER BY c.fecha_registro DESC, c.id DESC";

if ($vendedor_id > 0) {
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        http_response_code(500);
        exit('Error interno al preparar exportación.');
    }
    mysqli_stmt_bind_param($stmt, 'i', $vendedor_id);
    mysqli_stmt_execute($stmt);
    $resultado = mysqli_stmt_get_result($stmt);
} else {
    $resultado = mysqli_query($conn, $sql);
}

$filename = "ventas_" . date('Y-m-d_His') . ".csv";
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$output = fopen('php://output', 'w');

// BOM para UTF-8 (Excel)
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

$headers = [
    'ID Venta',
    'Cliente',
    'Teléfono',
    'Barrio',
    'Dirección',
    'Artículos',
    'Vendedor ID',
    'Vendedor',
    'Valor Total',
    'Seña',
    'Saldo Restante',
    'Frecuencia Pago',
    'Total Cuotas',
    'Fecha Primer Pago',
    'Fecha Registro',
    'Cuotas Pagadas',
    'Cuotas Pendientes',
    'Dinero Pagado',
    'Dinero Pendiente',
    'Estado'
];

fputcsv($output, $headers, ';');

$total_exportados = 0;
if ($resultado) {
    while ($row = mysqli_fetch_assoc($resultado)) {
        if ($row['cuotas_pendientes'] == 0 && $row['cuotas_pagadas'] > 0) {
            $estado = 'Finalizado';
        } elseif ($row['cuotas_pendientes'] > 0) {
            $estado = 'Activo - ' . $row['cuotas_pendientes'] . ' pendientes';
        } else {
            $estado = 'Sin cuotas';
        }

        $data = [
            $row['id'],
            $row['nombre_completo'],
            $row['telefono'],
            $row['barrio'],
            $row['direccion'],
            $row['articulos'],
            $row['vendedor_id'],
            $row['vendedor_nombre'] ?? '',
            number_format($row['valor_total'], 2, ',', ''),
            number_format($row['sena'], 2, ',', ''),
            number_format($row['saldo_restante'], 2, ',', ''),
            ucfirst($row['frecuencia_pago']),
            $row['cuotas'],
            $row['fecha_primer_pago'],
            $row['fecha_registro'],
            $row['cuotas_pagadas'],
            $row['cuotas_pendientes'],
            number_format($row['dinero_pagado'], 2, ',', ''),
            number_format($row['dinero_pendiente'], 2, ',', ''),
            $estado
        ];

        fputcsv($output, $data, ';');
        $total_exportados++;
    }
}

// Auditoría
$usuario = $_SESSION['usuario'];
$stmt_audit = mysqli_prepare($conn, "INSERT INTO auditoria (usuario, accion, tabla, registro_id, detalles) VALUES (?, 'EXPORT', 'ventas', 0, ?)");
if ($stmt_audit) {
    $detalles = $vendedor_id > 0
        ? "Exportación de ventas (vendedor_id=$vendedor_id) - $total_exportados registros"
        : "Exportación de ventas - $total_exportados registros";
    mysqli_stmt_bind_param($stmt_audit, 'ss', $usuario, $detalles);
    mysqli_stmt_execute($stmt_audit);
    mysqli_stmt_close($stmt_audit);
}

if ($vendedor_id > 0 && isset($stmt)) {
    mysqli_stmt_close($stmt);
}

fclose($output);
mysqli_close($conn);
exit;
?>
