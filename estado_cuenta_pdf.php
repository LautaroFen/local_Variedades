<?php
include("conexion.php");

if (!isset($_GET['id'])) {
    header('Location: index.php');
    exit();
}

$id = intval($_GET['id']);

// Obtener datos del cliente
$stmt = mysqli_prepare($conn, "SELECT * FROM clientes WHERE id=?");
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$resultado = mysqli_stmt_get_result($stmt);

if (!$resultado || mysqli_num_rows($resultado) != 1) {
    mysqli_stmt_close($stmt);
    die("Cliente no encontrado");
}

$cliente = mysqli_fetch_assoc($resultado);
mysqli_stmt_close($stmt);

// Obtener pagos del cliente
$query_pagos = "SELECT * FROM pagos_clientes WHERE cliente_id = $id ORDER BY numero_cuota ASC";
$resultado_pagos = mysqli_query($conn, $query_pagos);

// Calcular estadó­sticas
$total_cuotas = 0;
$cuotas_pagadas = 0;
$proximo_pago = null;

if ($resultado_pagos && mysqli_num_rows($resultado_pagos) > 0) {
    $total_cuotas = mysqli_num_rows($resultado_pagos);
    mysqli_data_seek($resultado_pagos, 0);
    while ($pago = mysqli_fetch_assoc($resultado_pagos)) {
        if ($pago['estado'] == 'pagado') {
            $cuotas_pagadas++;
        } elseif ($proximo_pago === null && $pago['estado'] == 'pendiente') {
            $proximo_pago = $pago;
        }
    }
}

$progreso = $total_cuotas > 0 ? ($cuotas_pagadas / $total_cuotas) * 100 : 0;
$sena = isset($cliente['sena']) ? $cliente['sena'] : 0;
$saldo_restante = $cliente['valor_total'] - $sena;
$monto_por_cuota = $cliente['cuotas'] > 0 ? $saldo_restante / $cliente['cuotas'] : 0;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Estado de Cuenta - <?php echo htmlspecialchars($cliente['nombre_completo']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        @media print {
            .no-print { display: none; }
            body { margin: 0; background: white !important; }
            .card { box-shadow: none !important; border: 1px solid #ddd !important; }
        }
        body { 
            padding: 20px; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
    </style>
</head>
<body>
    <main>
        <div class="container p-4">
            <div class="row">
                <div class="col-md-10 mx-auto">
                    <div class="card card-body" style="border-radius: 10px; box-shadow: 0 4px 8px rgba(5, 5, 5, 0.1);">
                        <!-- Logo y título -->
                        <div class="d-flex flex-column align-items-center mb-3">
                            <img src="includes/logo.jpg" alt="Mujeres Virtuosas" style="height:100px; width:100px; object-fit:cover; border-radius:50%;" class="mb-2">
                            <div class="text-center fw-bold" style="font-size:2.5rem; color:#024fb7;">Mujeres Virtuosas</div>
                        </div>
                        
                        <h2 class="text-center mb-4">📄 Estado de Cuenta</h2>

                        <!-- Información del Cliente -->
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nombre completo</label>
                                <input type="text" value="<?php echo htmlspecialchars($cliente['nombre_completo']); ?>" class="form-control" readonly>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Teléfono</label>
                                <input type="text" value="<?php echo htmlspecialchars($cliente['telefono']); ?>" class="form-control" readonly>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Barrio</label>
                                <input type="text" value="<?php echo htmlspecialchars($cliente['barrio']); ?>" class="form-control" readonly>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Dirección</label>
                                <input type="text" value="<?php echo htmlspecialchars($cliente['direccion']); ?>" class="form-control" readonly>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Artículos</label>
                            <textarea class="form-control" rows="4" readonly><?php echo htmlspecialchars($cliente['articulos']); ?></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Valor total</label>
                                <input type="text" value="$<?php echo number_format($cliente['valor_total'], 2, ',', '.'); ?>" class="form-control" readonly>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Seña / Adelanto</label>
                                <input type="text" value="$<?php echo number_format($sena, 2, ',', '.'); ?>" class="form-control" readonly style="color: #0066cc;">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Saldo restante</label>
                                <input type="text" value="$<?php echo number_format($saldo_restante, 2, ',', '.'); ?>" class="form-control" readonly style="font-weight: bold; color: #dc3545;">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Frecuencia de pago</label>
                                <input type="text" value="<?php echo ucfirst(htmlspecialchars($cliente['frecuencia_pago'])); ?>" class="form-control" readonly>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Cuotas</label>
                                <input type="text" value="<?php echo htmlspecialchars($cliente['cuotas']); ?>" class="form-control" readonly>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Monto por cuota</label>
                                <input type="text" value="$<?php echo number_format($monto_por_cuota, 2, ',', '.'); ?>" class="form-control" readonly style="font-weight: bold; color: #28a745;">
                            </div>
                        </div>

                        <!-- Calendario de pagos -->
                        <hr class="my-4">
                        <h4 class="mb-3">Calendario de pagos</h4>

                        <div class="alert alert-info mb-3">
                            <strong>Progreso:</strong> <?php echo $cuotas_pagadas; ?> de <?php echo $total_cuotas; ?> cuotas pagadas (<?php echo number_format($progreso, 1); ?>%)
                        </div>

                        <?php if ($proximo_pago): ?>
                            <div class="alert alert-warning mb-3">
                                <strong>Próximo pago:</strong> <?php echo date('d/m/Y', strtotime($proximo_pago['fecha_programada'])); ?> -
                                $<?php echo number_format($proximo_pago['monto'], 2, ',', '.'); ?>
                            </div>
                        <?php endif; ?>

                        <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
                            <table class="table table-bordered table-hover">
                                <thead class="table-light sticky-top">
                                    <tr>
                                        <th>Cuota</th>
                                        <th>Fecha programada</th>
                                        <th>Monto</th>
                                        <th>Estado</th>
                                        <th>Fecha de pago</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    if ($resultado_pagos && mysqli_num_rows($resultado_pagos) > 0) {
                                        mysqli_data_seek($resultado_pagos, 0);
                                        while ($pago = mysqli_fetch_assoc($resultado_pagos)) {
                                            $estado_class = $pago['estado'] == 'pagado' ? 'success' : 'warning';
                                            $estado_texto = $pago['estado'] == 'pagado' ? 'Pagado' : 'Pendiente';
                                            $fecha_pago_texto = $pago['fecha_pago'] ? date('d/m/Y', strtotime($pago['fecha_pago'])) : '-';
                                    ?>
                                        <tr class="table-<?php echo $estado_class; ?>">
                                            <td><strong><?php echo $pago['numero_cuota']; ?></strong></td>
                                            <td><?php echo date('d/m/Y', strtotime($pago['fecha_programada'])); ?></td>
                                            <td>$<?php echo number_format($pago['monto'], 2, ',', '.'); ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo $estado_class; ?>">
                                                    <?php echo $estado_texto; ?>
                                                </span>
                                            </td>
                                            <td><?php echo $fecha_pago_texto; ?></td>
                                        </tr>
                                    <?php 
                                        }
                                    } else {
                                    ?>
                                        <tr>
                                            <td colspan="5" class="text-center">No hay cuotas programadas para este cliente.</td>
                                        </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Footer con fecha de generació³n -->
                        <div class="text-center mt-4 pt-3 border-top">
                            <small class="text-muted">
                                Documento generado el <?php echo date('d/m/Y H:i'); ?> - 
                                Mujeres Virtuosas
                            </small>
                        </div>

                        <!-- Botones -->
                        <div class="text-center mt-4 no-print">
                            <button onclick="window.print()" class="btn btn-primary btn-lg">🖨 Imprimir / Descargar PDF</button>
                            <button onclick="window.close()" class="btn btn-secondary btn-lg">↩️ Volver</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="javascript/estado_cuenta_pdf.js?v=<?php echo time(); ?>"></script>
</body>
</html>

