<?php
session_start();

// Verificar que el usuario esté autenticado
if (!isset($_SESSION['usuario'])) {
    header('Location: ../login.php');
    exit;
}

// PROTECCIÓN: Solo jefes pueden exportar datos
if ($_SESSION['tipo_usuario'] != 'jefe') {
    $_SESSION['message'] = '⛔ Acceso denegado. No tienes permisos para exportar datos.';
    $_SESSION['message_type'] = 'danger';
    header('Location: ../index.php');
    exit();
}

require_once __DIR__ . '/../conexion.php';

// Consultar todos los clientes con información de pagos
$query = "SELECT 
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
    COUNT(pc.id) as total_cuotas_generadas,
    SUM(CASE WHEN pc.estado = 'pagado' THEN 1 ELSE 0 END) as cuotas_pagadas,
    SUM(CASE WHEN pc.estado = 'pendiente' THEN 1 ELSE 0 END) as cuotas_pendientes,
    SUM(CASE WHEN pc.estado = 'pagado' THEN pc.monto ELSE 0 END) as dinero_pagado,
    SUM(CASE WHEN pc.estado = 'pendiente' THEN pc.monto ELSE 0 END) as dinero_pendiente
FROM clientes c
LEFT JOIN pagos_clientes pc ON c.id = pc.cliente_id
GROUP BY c.id
ORDER BY c.id DESC";

$resultado = mysqli_query($conn, $query);

// Configurar headers para descarga de CSV
$filename = "clientes_" . date('Y-m-d_His') . ".csv";
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

// Crear output stream
$output = fopen('php://output', 'w');

// BOM para UTF-8 (para que Excel muestre correctamente los acentos)
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Encabezados del CSV
$headers = [
    'ID',
    'Nombre Completo',
    'Teléfono',
    'Barrio',
    'Dirección',
    'Artículos',
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
fputcsv($output, $headers, ';'); // Usar punto y coma para compatibilidad con Excel en español

// Escribir datos
while ($row = mysqli_fetch_assoc($resultado)) {
    // Determinar estado
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
}

// Registrar en auditoría
$usuario = $_SESSION['usuario'];
$total_exportados = mysqli_num_rows($resultado);
$stmt = mysqli_prepare($conn, "INSERT INTO auditoria (usuario, accion, tabla, registro_id, detalles) VALUES (?, 'EXPORT', 'clientes', 0, ?)");
$detalles = "Exportación a Excel - $total_exportados clientes";
mysqli_stmt_bind_param($stmt, 'ss', $usuario, $detalles);
mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

fclose($output);
mysqli_close($conn);
exit;
?>
