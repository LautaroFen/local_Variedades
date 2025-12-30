<?php
/*
Se realiza la conexión a la bd.
Después se obtiene el id con get.
Se seleccionan todos los datos con ese id.
Se busca un resultado con ese id.
En $row se cargan los datos en un array y se vuelcan en variables con el título de las columnas

Se carga el header y footer con include
*/

include("conexion.php");
if (!isset($_SESSION['usuario'])) {
    header('Location: login.php');
    exit();
}

// PROTECCION: Solo jefes pueden editar clientes
if ($_SESSION['tipo_usuario'] != 'jefe') {
    $_SESSION['message'] = '›” Acceso denegado. No tienes permisos para editar clientes.';
    $_SESSION['message_type'] = 'danger';
    header('Location: index.php');
    exit();
}

// Volver: mismo criterio que ver.php (conservar tab y volver a la fila)
$volver_url = 'index.php';
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$tab = '';

if (isset($_GET['tab'])) {
    $tab = strtolower((string)$_GET['tab']);
} elseif (!empty($_SERVER['HTTP_REFERER'])) {
    $ref = (string)$_SERVER['HTTP_REFERER'];
    $parts = parse_url($ref);
    $path = isset($parts['path']) ? (string)$parts['path'] : '';
    $isIndex = ($path !== '' && preg_match('~/index\.php$~', $path));
    if ($isIndex && isset($parts['query']) && (string)$parts['query'] !== '') {
        parse_str((string)$parts['query'], $qs);
        if (isset($qs['tab'])) {
            $tab = strtolower((string)$qs['tab']);
        }
    }
}

if (!in_array($tab, ['pendientes', 'atrasados', 'finalizados'], true)) {
    $tab = '';
}

$tab_qs = ($tab !== '') ? ('&tab=' . urlencode($tab)) : '';

if ($tab !== '' && $id > 0) {
    $volver_url = 'index.php?tab=' . urlencode($tab) . '#cliente-' . $id;
} elseif (!empty($_SERVER['HTTP_REFERER'])) {
    // Si venimos desde index.php con otros parámetros, conservarlos y volver a la fila
    $ref = (string)$_SERVER['HTTP_REFERER'];
    $parts = parse_url($ref);
    $path = isset($parts['path']) ? (string)$parts['path'] : '';
    $isIndex = ($path !== '' && preg_match('~/index\.php$~', $path));
    if ($isIndex) {
        $qs = (isset($parts['query']) && (string)$parts['query'] !== '') ? ('?' . (string)$parts['query']) : '';
        $anchor = ($id > 0) ? ('#cliente-' . $id) : '';
        $volver_url = 'index.php' . $qs . $anchor;
    }
}

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $stmt = mysqli_prepare($conn, "SELECT * FROM clientes WHERE id=?");
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    $resultado = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($resultado) == 1) {
        $row = mysqli_fetch_array($resultado);
        mysqli_stmt_close($stmt);
        $nombre_completo = $row['nombre_completo'];
        $telefono = $row['telefono'];
        $email = isset($row['email']) ? $row['email'] : '';
        $barrio = $row['barrio'];
        $direccion = $row['direccion'];
        $articulos = $row['articulos'];
        $valor_total = $row['valor_total'];
        $sena = isset($row['sena']) ? $row['sena'] : 0;
        $frecuencia_pago = isset($row['frecuencia_pago']) ? (string)$row['frecuencia_pago'] : '';
        $cuotas = isset($row['cuotas']) ? (int)$row['cuotas'] : 0;
    }
}


if (isset($_POST['actualizar'])) {
    $id = intval($_GET['id']);
    $nombre_completo = mysqli_real_escape_string($conn, trim($_POST['nombre_completo']));
    $telefono = mysqli_real_escape_string($conn, trim($_POST['telefono']));
    $email = isset($_POST['email']) ? trim((string)$_POST['email']) : '';
    $barrio = mysqli_real_escape_string($conn, trim($_POST['barrio']));
    $direccion = mysqli_real_escape_string($conn, trim($_POST['direccion']));
    $articulos = mysqli_real_escape_string($conn, trim($_POST['articulos']));
    $valor_total = floatval($_POST['valor_total']);
    $sena = isset($_POST['sena']) ? floatval($_POST['sena']) : 0;

    $frecuencia_pago_raw = $_POST['frecuencia_pago'] ?? ($frecuencia_pago ?? '');
    $frecuencia_pago = mysqli_real_escape_string($conn, (string)$frecuencia_pago_raw);

    $cuotas_raw = $_POST['cuotas'] ?? ($cuotas ?? 0);
    $cuotas = (int)$cuotas_raw;

    // Validaciones servidor
    $errors = [];
    if (!preg_match('/^[A-Za-zÀ-ÖØ-öø-ÿ\s]+$/u', $nombre_completo)) {
        $errors[] = 'Nombre completo inválido: solo letras y espacios.';
    }
    if (!preg_match('/^\d{10,15}$/', $telefono)) {
        $errors[] = 'Teléfono inválido: debe tener entre 10 y 15 dígitos.';
    }
    if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
        $errors[] = 'Email inválido.';
    }
    if (strlen($articulos) > 500) {
        $errors[] = 'Artículos: máximo 500 caracteres.';
    }
    if ($valor_total <= 0) {
        $errors[] = 'Valor total debe ser mayor a 0.';
    }
    if ($sena < 0) {
        $errors[] = 'La seña no puede ser negativa.';
    }
    if ($sena > $valor_total) {
        $errors[] = 'La seña no puede ser mayor al valor total.';
    }
    if (!in_array($frecuencia_pago, ['semanal', 'quincenal', 'mensual', 'unico_pago'], true)) {
        $errors[] = 'Frecuencia de pago inválida.';
    }
    if ($cuotas < 1 || $cuotas > 60) {
        $errors[] = 'Cuotas debe estar entre 1 y 60.';
    }

    if (!empty($errors)) {
        $_SESSION['message'] = implode(' ', $errors);
        header('Location: editar.php?id=' . $id . $tab_qs);
        exit();
    }

    // Actualizar el cliente
    // Si la columna email en tu BD es NOT NULL, guardamos '' cuando viene vacío.
    $email_db = $email;

    $stmt = mysqli_prepare($conn, "UPDATE clientes SET nombre_completo = ?, telefono = ?, email = ?, barrio = ?, direccion = ?, articulos = ?, valor_total = ?, sena = ?, frecuencia_pago = ?, cuotas = ? WHERE id = ?");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'ssssssddsii', $nombre_completo, $telefono, $email_db, $barrio, $direccion, $articulos, $valor_total, $sena, $frecuencia_pago, $cuotas, $id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        // Registrar en auditorÃ­a
        if (isset($_SESSION['usuario'])) {
            $stmt_audit = mysqli_prepare($conn, "INSERT INTO auditoria (usuario, accion, tabla, registro_id, detalles) VALUES (?, 'EDITAR', 'clientes', ?, ?)");
            $detalles = "Cliente editado: $nombre_completo";
            mysqli_stmt_bind_param($stmt_audit, 'sis', $_SESSION['usuario'], $id, $detalles);
            mysqli_stmt_execute($stmt_audit);
            mysqli_stmt_close($stmt_audit);
        }
    }

    $_SESSION['message'] = "El cliente se actualizo correctamente";
    header('Location: editar.php?id=' . $id . $tab_qs);
}

?>

<?php include("includes/header.php"); ?>

<main class="pb-4">

    <div class="container p-4">
        <div class="col-12 ">
            <div class="card w-100">
                <div class="card-header w-100">
                    <h5 class="mb-0">Editar Compra</h5>
                </div>
                <div class="card-body">
                    <?php if (isset($_SESSION['message'])): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?php
                            echo htmlspecialchars($_SESSION['message']);
                            unset($_SESSION['message']);
                            ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <!--Actualizar con metodo POST-->
                    <form class="form-group mb-2" action="editar.php?id=<?php echo (int)$_GET['id']; ?><?php echo $tab_qs; ?>" method="POST" autocomplete="off">
                        <label class="form-label" class="form-label">Nombre completo</label>
                        <input type="text" name="nombre_completo" value="<?php echo htmlspecialchars($nombre_completo); ?>" class="form-control mb-3" placeholder="Nombre completo" pattern="[A-Za-zÀ-ÖØ-öø-ÿ\s]+" required title="Solo letras y espacios" autocomplete="off">

                        <label class="form-label" class="form-label">Teléfono</label>
                        <input type="text" name="telefono" value="<?php echo htmlspecialchars($telefono); ?>" class="form-control mb-3" placeholder="Teléfono" pattern="\d{10,15}" inputmode="numeric" minlength="10" maxlength="15" required title="Entre 10 y 15 dígitos" autocomplete="off">

                        <label class="form-label" class="form-label">Email</label>
                        <input type="email" name="email" value="<?php echo htmlspecialchars(isset($email) ? $email : ''); ?>" class="form-control mb-3" placeholder="Email" autocomplete="off">

                        <label class="form-label" class="form-label">Barrio</label>
                        <input type="text" name="barrio" value="<?php echo htmlspecialchars($barrio); ?>" class="form-control mb-3" placeholder="Barrio" required autocomplete="off">

                        <label class="form-label" class="form-label">Dirección</label>
                        <input type="text" name="direccion" value="<?php echo htmlspecialchars($direccion); ?>" class="form-control mb-3" placeholder="Dirección" required autocomplete="off">

                        <label class="form-label" class="form-label">Artículos</label>
                        <textarea name="articulos" class="form-control" placeholder="Artículos que compra" maxlength="500" rows="3" required autocomplete="off"><?php echo htmlspecialchars($articulos); ?></textarea>
                        <small class="text-muted">Maximo 500 caracteres</small><br>

                        <label class="form-label mt-3" class="form-label">Valor total</label>
                        <input type="number" name="valor_total" id="valor_total_edit" value="<?php echo htmlspecialchars($valor_total); ?>" class="form-control mb-3" placeholder="Valor total" step="0.01" min="0.01" required autocomplete="off">

                        <label class="form-label" class="form-label">Seña / Adelanto</label>
                        <input type="number" name="sena" id="sena_edit" value="<?php echo htmlspecialchars($sena); ?>" class="form-control mb-3" placeholder="Seña / Adelanto" step="0.01" min="0" autocomplete="off">

                        <label class="form-label">Frecuencia de pago</label>
                        <select name="frecuencia_pago" class="form-select mb-3" required>
                            <option value="semanal" <?php echo ((string)$frecuencia_pago === 'semanal') ? 'selected' : ''; ?>>Semanal</option>
                            <option value="quincenal" <?php echo ((string)$frecuencia_pago === 'quincenal') ? 'selected' : ''; ?>>Quincenal</option>
                            <option value="mensual" <?php echo ((string)$frecuencia_pago === 'mensual') ? 'selected' : ''; ?>>Mensual</option>
                            <option value="unico_pago" <?php echo ((string)$frecuencia_pago === 'unico_pago') ? 'selected' : ''; ?>>Único pago</option>
                        </select>

                        <label class="form-label">Cuotas</label>
                        <input type="number" name="cuotas" id="cuotas_edit" value="<?php echo htmlspecialchars((string)$cuotas); ?>" class="form-control mb-3" placeholder="Cuotas" min="1" max="60" required autocomplete="off">

                        <label class="form-label">Monto por cuota</label>
                        <div id="monto_cuota_display_edit" class="form-control bg-light" style="font-weight: bold; color: #28a745;">
                            $<?php
                                $saldo = $valor_total - $sena;
                                $cuotas_safe = (int)$cuotas;
                                if ($cuotas_safe < 1) {
                                    $cuotas_safe = 1;
                                }
                                echo number_format($saldo / $cuotas_safe, 2, ',', '.');
                                ?>
                        </div>

                        <div class="text-end mt-3">
                            <button type="submit" class="btn btn-success" name="actualizar">Actualizar</button>
                        </div>
                        
                    </form>

                    <?php
                    // Obtener pagos del cliente
                    if (isset($id)) {
                        $query_pagos = "SELECT * FROM pagos_clientes WHERE cliente_id = $id ORDER BY numero_cuota ASC";
                        $resultado_pagos = mysqli_query($conn, $query_pagos);

                        if ($resultado_pagos && mysqli_num_rows($resultado_pagos) > 0) {
                    ?>  
                            
                            <div class="table-responsive h-100" style=" overflow-y: auto;">
                                <h4 class="mb-3">Gestionar fechas de pago</h4>
                                <table class="table table-bordered table-sm">
                                    <thead>
                                        <tr>
                                            <th>Cuota</th>
                                            <th>Fecha programada</th>
                                            <th>Estado</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($pago = mysqli_fetch_assoc($resultado_pagos)) {
                                            $estado_class = $pago['estado'] == 'pagado' ? 'success' : 'warning';
                                            $estado_texto = $pago['estado'] == 'pagado' ? 'Pagado' : 'Pendiente';
                                        ?>
                                            <tr class="table-<?php echo $estado_class; ?>">
                                                <td><strong><?php echo $pago['numero_cuota']; ?></strong></td>
                                                <td>
                                                    <form method="POST" action="editar_fecha_pago.php" class="d-inline">
                                                        <input type="hidden" name="pago_id" value="<?php echo $pago['id']; ?>">
                                                        <input type="hidden" name="cliente_id" value="<?php echo $id; ?>">
                                                        <input type="hidden" name="tab" value="<?php echo htmlspecialchars($tab); ?>">
                                                        <input type="date" name="nueva_fecha" value="<?php echo $pago['fecha_programada']; ?>" class="form-control form-control-sm d-inline" style="width: auto; display: inline-block;" required>
                                                        <button type="submit" class="btn btn-primary btn-sm" title="Guardar nueva fecha">
                                                            Guardar
                                                        </button>
                                                    </form>
                                                </td>
                                                <td>
                                                    <span class="badge bg-<?php echo $estado_class; ?>">
                                                        <?php echo $estado_texto; ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if ($pago['estado'] == 'pagado'): ?>
                                                        <form method="POST" action="cancelar_pago.php" style="display: inline;">
                                                            <input type="hidden" name="pago_id" value="<?php echo $pago['id']; ?>">
                                                            <input type="hidden" name="cliente_id" value="<?php echo $id; ?>">
                                                            <input type="hidden" name="tab" value="<?php echo htmlspecialchars($tab); ?>">
                                                            <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('¿Cancelar este pago?')" title="Cancelar pago">
                                                                Cancelar
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
                            <div class="alert alert-info">
                                No hay cuotas registradas para este cliente.
                            </div>
                        <?php } ?>
                    <?php } ?>

                </div>
                </col-md>
            </div>
        </div>
    </div>
    <div class="row mt-5 mb-5">
        <div class="col-12 text-center">
            <a href="<?php echo htmlspecialchars(isset($volver_url) ? $volver_url : 'index.php'); ?>" class="btn btn-success">Volver al inicio</a>
        </div>
    </div>
</main>


<!--Scripts-->
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js" integrity="sha384-I7E8VVD/ismYTF4hNIPjVp/Zjvgyol6VFvRkX/vR+Vc4jQkC+hVqc2pM8ODewa9r" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.min.js" integrity="sha384-0pUGZvbkm6XF6gxjEnlmuGrJXVbNuzT9qBBavbLwCsOGabYfZo0T0to5eqruptLy" crossorigin="anonymous"></script>
<script>
    // Calcular monto por cuota automÃ¡ticamente
    function calcularMontoCuotaEdit() {
        const valorTotal = parseFloat(document.getElementById('valor_total_edit').value) || 0;
        const sena = parseFloat(document.getElementById('sena_edit').value) || 0;
        const cuotas = parseInt(document.getElementById('cuotas_edit').value) || 1;

        // Validar que la seña no sea mayor al valor total
        if (sena > valorTotal) {
            document.getElementById('sena_edit').value = valorTotal;
            return;
        }

        const saldoRestante = valorTotal - sena;

        if (saldoRestante > 0 && cuotas > 0) {
            const montoPorCuota = saldoRestante / cuotas;
            document.getElementById('monto_cuota_display_edit').textContent =
                '$' + montoPorCuota.toLocaleString('es-AR', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
        } else if (saldoRestante === 0) {
            document.getElementById('monto_cuota_display_edit').textContent = '$0,00';
        } else {
            document.getElementById('monto_cuota_display_edit').textContent = '$0,00';
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        const valorInput = document.getElementById('valor_total_edit');
        const senaInput = document.getElementById('sena_edit');
        const cuotasInput = document.getElementById('cuotas_edit');

        if (valorInput && cuotasInput) {
            valorInput.addEventListener('input', calcularMontoCuotaEdit);
            cuotasInput.addEventListener('input', calcularMontoCuotaEdit);
        }
        if (senaInput) {
            senaInput.addEventListener('input', calcularMontoCuotaEdit);
        }
    });
</script>