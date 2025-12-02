<?php

include("conexion.php");
// No llamar a session_start() aquí: la sesión se inicia en conexion.php
if (!isset($_SESSION['usuario'])) {
    header('Location: login.php');
    exit();
}

$nombre_completo = '';
$telefono = '';
$barrio = '';
$direccion = '';
$articulos = '';
$valor_total = '';
$sena = 0;
$frecuencia_pago = '';
$cuotas = '';
$monto_por_cuota = '';

if (isset($_GET['id'])) {
    $id = intval($_GET['id']); // sanear a entero
    if ($id > 0) {
        $stmt = mysqli_prepare($conn, "SELECT * FROM clientes WHERE id=?");
        mysqli_stmt_bind_param($stmt, 'i', $id);
        mysqli_stmt_execute($stmt);
        $resultado = mysqli_stmt_get_result($stmt);

        if ($resultado && mysqli_num_rows($resultado) == 1) {
            $row = mysqli_fetch_array($resultado);
            mysqli_stmt_close($stmt);
            $nombre_completo = isset($row['nombre_completo']) ? $row['nombre_completo'] : '';
            $telefono = isset($row['telefono']) ? $row['telefono'] : '';
            $barrio = isset($row['barrio']) ? $row['barrio'] : '';
            $direccion = isset($row['direccion']) ? $row['direccion'] : '';
            $articulos = isset($row['articulos']) ? $row['articulos'] : '';
            $valor_total = isset($row['valor_total']) ? $row['valor_total'] : '';
            $sena = isset($row['sena']) ? $row['sena'] : 0;
            $frecuencia_pago = isset($row['frecuencia_pago']) ? $row['frecuencia_pago'] : '';
            $cuotas = isset($row['cuotas']) ? $row['cuotas'] : '';

            // Calcular saldo restante y monto por cuota
            $saldo_restante = $valor_total - $sena;
            if ($cuotas > 0) {
                $monto_por_cuota = number_format($saldo_restante / $cuotas, 2, ',', '.');
            }
        }
    }
}
?>

<?php include("includes/header.php"); ?>

<main>
    <div class="container p-4">
        <div class="row">
            <div class="col-md-8 mx-auto">
                <div class="card card-body" style="border-radius: 10px; box-shadow: 0 4px 8px rgba(5, 5, 5, 0.1);">
                    <div class="d-flex flex-column align-items-center mb-3">
                        <img src="includes/logo.jpg" alt="Mujeres Virtuosas S.A" style="height:100px; width:100px; object-fit:cover; border-radius:50%;" class="mb-2">
                        <div class="text-center fw-bold" style="font-size:3.35rem; color:#024fb7;">Mujeres Virtuosas S.A</div>
                    </div><br><br>
                    <h2 class="text-center mb-3">Ver cliente</h2>
                    <!-- Mostrar datos en readonly -->
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Nombre completo</label>
                            <input type="text" value="<?php echo htmlspecialchars($nombre_completo); ?>" class="form-control" readonly>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Teléfono</label>
                            <input type="text" value="<?php echo htmlspecialchars($telefono); ?>" class="form-control" readonly>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Barrio</label>
                            <input type="text" value="<?php echo htmlspecialchars($barrio); ?>" class="form-control" readonly>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Dirección</label>
                            <input type="text" value="<?php echo htmlspecialchars($direccion); ?>" class="form-control" readonly>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Artículos</label>
                        <textarea class="form-control" rows="4" readonly><?php echo htmlspecialchars($articulos); ?></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Valor total</label>
                            <input type="text" value="$<?php echo number_format($valor_total, 2, ',', '.'); ?>" class="form-control" readonly>
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
                            <input type="text" value="<?php echo ucfirst(htmlspecialchars($frecuencia_pago)); ?>" class="form-control" readonly>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Cuotas</label>
                            <input type="text" value="<?php echo htmlspecialchars($cuotas); ?>" class="form-control" readonly>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Monto por cuota</label>
                            <input type="text" value="$<?php echo $monto_por_cuota; ?>" class="form-control" readonly style="font-weight: bold; color: #28a745;">
                        </div>
                    </div>

                    <!-- Tabla de pagos -->
                    <hr class="my-4">
                    <h4 class="mb-3">Calendario de pagos</h4>

                    <?php
                    // Obtener todas las cuotas de pago
                    $query_pagos = "SELECT * FROM pagos_clientes WHERE cliente_id = $id ORDER BY numero_cuota ASC";
                    $resultado_pagos = mysqli_query($conn, $query_pagos);

                    if ($resultado_pagos && mysqli_num_rows($resultado_pagos) > 0) {
                        // Calcular estadÃ­sticas
                        $total_cuotas = mysqli_num_rows($resultado_pagos);
                        $cuotas_pagadas = 0;
                        $proximo_pago = null;

                        mysqli_data_seek($resultado_pagos, 0);
                        while ($pago = mysqli_fetch_assoc($resultado_pagos)) {
                            if ($pago['estado'] == 'pagado') {
                                $cuotas_pagadas++;
                            } elseif ($proximo_pago === null && $pago['estado'] == 'pendiente') {
                                $proximo_pago = $pago;
                            }
                        }

                        $progreso = ($cuotas_pagadas / $total_cuotas) * 100;
                    ?>

                        <div class="alert alert-info alert-permanent mb-3">
                            <strong>Progreso:</strong> <?php echo $cuotas_pagadas; ?> de <?php echo $total_cuotas; ?> cuotas pagadas (<?php echo number_format($progreso, 1); ?>%)
                            <div class="progress mt-2" style="height: 25px;">
                                <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $progreso; ?>%;" aria-valuenow="<?php echo $progreso; ?>" aria-valuemin="0" aria-valuemax="100">
                                    <?php echo number_format($progreso, 1); ?>%
                                </div>
                            </div>
                        </div>

                        <?php if ($proximo_pago): ?>
                            <div class="alert alert-warning alert-permanent mb-3">
                                <strong>Próximo pago:</strong> <?php echo date('d/m/Y', strtotime($proximo_pago['fecha_programada'])); ?> -
                                $<?php echo number_format($proximo_pago['monto'], 2, ',', '.'); ?>
                            </div>
                        <?php endif; ?>

                        <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                            <table class="table table-bordered table-hover">
                                <thead class="table-light sticky-top">
                                    <tr>
                                        <th>Cuota</th>
                                        <th>Fecha programada</th>
                                        <th>Monto</th>
                                        <th>Estado</th>
                                        <th>Fecha de pago</th>
                                        <th>Acción</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
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
                                            <td>
                                                <?php if ($pago['estado'] == 'pendiente'): ?>
                                                    <form method="POST" action="registrar_pago.php" style="display: inline;">
                                                        <input type="hidden" name="pago_id" value="<?php echo $pago['id']; ?>">
                                                        <input type="hidden" name="cliente_id" value="<?php echo $id; ?>">
                                                        <button type="submit" class="btn btn-sm btn-success" onclick="return confirm('¿Confirmar que el cliente pague esta cuota?')">
                                                            Confirmar pago
                                                        </button>
                                                    </form>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>
                    <?php } else { ?>
                        <div class="alert alert-warning">
                            No hay cuotas programadas para este cliente.
                        </div>
                    <?php } ?>

                    <div class="text-end mt-3">
                        <a href="index.php" class="btn btn-success">Volver</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<!--Scripts-->
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js" integrity="sha384-I7E8VVD/ismYTF4hNIPjVp/Zjvgyol6VFvRkX/vR+Vc4jQkC+hVqc2pM8ODewa9r" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.min.js" integrity="sha384-0pUGZvbkm6XF6gxjEnlmuGrJXVbNuzT9qBBavbLwCsOGabYfZo0T0to5eqruptLy" crossorigin="anonymous"></script>
<script src="javascript/ver.js?v=<?php echo time(); ?>"></script>
