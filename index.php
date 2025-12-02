<?php include("conexion.php"); ?>

<?php
if (!isset($_SESSION['usuario'])) {
    header('Location: login.php');
    exit();
}

include("includes/header.php");
?>

<main>
    <div class="container p-4">
        <?php if (isset($_SESSION['message'])): ?>
            <?php
            $busqueda = strpos($_SESSION['message'], 'existe');
            if ($busqueda != false) {
                echo "<div class='alert alert-danger'>";
            } else {
                echo '<div class="alert alert-info">';
            }
            ?>
            <?php echo htmlspecialchars($_SESSION['message']); ?>
    </div>
    <?php unset($_SESSION['message']); ?>
<?php endif; ?>

<!-- Card Registrar Compra -->
<div class="row g-0">
    <div class="col-12 col-lg-8 offset-lg-2 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Registrar Compras</h5>
            </div>
            <div class="card-body">
                <form action="guardar.php" method="post" autocomplete="off">
                    <div class="form-group mb-2">
                        <!-- Nombre completo -->
                        <label for="nombre_completo" class="form-label">Nombre completo</label>
                        <input type="text" name="nombre_completo" id="nombre_completo" class="form-control mb-3"
                            placeholder="Ingrese nombre completo del cliente"
                            pattern="[A-Za-zÀ-ÖØ-öø-ÿ\s]+"
                            required
                            title="Solo letras y espacios"
                            autocomplete="off"
                            autofocus>

                        <!-- Teléfono -->
                        <label for="telefono" class="form-label">Teléfono</label>
                        <input type="text" name="telefono" id="telefono" class="form-control mb-3"
                            placeholder="Ej: 1154896235"
                            pattern="\d{10,15}"
                            inputmode="numeric"
                            maxlength="15"
                            required
                            title="Solo números (10 a 15 dígitos)"
                            autocomplete="off">

                        <!-- Barrio -->
                        <label for="barrio" class="form-label">Barrio</label>
                        <input type="text" name="barrio" id="barrio" class="form-control mb-3"
                            placeholder="Ingrese el barrio"
                            pattern="[A-Za-zÀ-ÖØ-öø-ÿ0-9\s.,\-]+"
                            required
                            maxlength="100"
                            title="Letras, números, espacios y . , -"
                            autocomplete="off">

                        <!-- Dirección -->
                        <label for="direccion" class="form-label">Dirección</label>
                        <input type="text" name="direccion" id="direccion" class="form-control mb-3"
                            placeholder="Calle y número"
                            required
                            maxlength="200"
                            pattern="[A-Za-zÀ-ÖØ-öø-ÿ0-9\s.,#ºª\-/]+"
                            title="Letras, números y . , - / # º ª"
                            autocomplete="off">

                        <!-- Artículos -->
                        <label for="articulos" class="form-label">Artículos</label>
                        <textarea name="articulos" id="articulos" class="form-control mb-3"
                            placeholder="Descripción de los artículos comprados"
                            rows="3"
                            required
                            maxlength="500"
                            autocomplete="off"></textarea>

                        <!-- Valor total -->
                        <label for="valor_total" class="form-label">Valor total ($)</label>
                        <input type="number" name="valor_total" id="valor_total" class="form-control mb-3"
                            placeholder="Ej: 60000"
                            min="1"
                            step="0.01"
                            required
                            title="Ingrese el valor total en pesos"
                            autocomplete="off">

                        <!-- Seña (adelanto) -->
                        <label for="sena" class="form-label">Seña / Adelanto ($)</label>
                        <input type="number" name="sena" id="sena" class="form-control mb-3"
                            placeholder="Ej: 10000 (opcional)"
                            min="0"
                            step="0.01"
                            value="0"
                            title="Dinero que el cliente deja como adelanto"
                            autocomplete="off">
                        <small class="text-muted d-block mb-3">💡 Si el cliente deja una seña, se descontará del valor total</small>

                        <!-- Frecuencia de pago -->
                        <label for="frecuencia_pago" class="form-label">Frecuencia de pago</label>
                        <select name="frecuencia_pago" id="frecuencia_pago" class="form-control mb-3" required>
                            <option value="">Seleccione frecuencia</option>
                            <option value="semanal">Semanal</option>
                            <option value="quincenal">Quincenal</option>
                            <option value="mensual">Mensual</option>
                        </select>

                        <!-- Cuotas -->
                        <label for="cuotas" class="form-label">Cantidad de cuotas</label>
                        <input type="number" name="cuotas" id="cuotas" class="form-control mb-3"
                            placeholder="Ej: 12"
                            min="1"
                            max="60"
                            required
                            title="Entre 1 y 60 cuotas"
                            autocomplete="off">

                        <!-- Fecha de primer pago -->
                        <label for="fecha_primer_pago" class="form-label">Fecha del primer pago</label>
                        <input type="date" name="fecha_primer_pago" id="fecha_primer_pago" class="form-control mb-3"
                            required
                            min="<?php echo date('Y-m-d'); ?>"
                            title="Seleccione la fecha del primer pago"
                            autocomplete="off">

                        <!-- Empleado vendedor -->
                        <label for="vendedor_id" class="form-label">Empleado que realizó la venta</label>
                        <select name="vendedor_id" id="vendedor_id" class="form-control mb-3" required>
                            <option value="">Seleccione empleado vendedor</option>
                            <?php
                            // Obtener lista de vendedores activos
                            $stmt_vendedores = mysqli_prepare($conn, "SELECT id, nombre_completo FROM empleados_vendedores WHERE activo = 1 ORDER BY nombre_completo");
                            mysqli_stmt_execute($stmt_vendedores);
                            $result_vendedores = mysqli_stmt_get_result($stmt_vendedores);
                            while ($vendedor = mysqli_fetch_assoc($result_vendedores)) {
                                echo '<option value="' . $vendedor['id'] . '">' . htmlspecialchars($vendedor['nombre_completo']) . '</option>';
                            }
                            mysqli_stmt_close($stmt_vendedores);
                            ?>
                        </select>

                        <!-- Cálculo automático del monto por cuota -->
                        <div class="alert alert-info alert-permanent animate-fade-in" id="info-cuota" style="display: none;">
                            <div class="d-flex align-items-center">
                                <div class="fs-4 me-3">💰</div>
                                <div>
                                    <strong>Monto por cuota:</strong> <span id="monto-cuota">$0.00</span>
                                </div>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary btn-lg float-end btn-hover-shine" name="guardar-cliente">
                            <strong>Guardar</strong>
                        </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Card Buscar Compra -->
<div class="row g-0">
    <div class="col-12 col-lg-8 offset-lg-2 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Buscar Compras</h5>
            </div>
            <div class="card-body">
                <!-- Búsqueda simple (siempre visible) -->
                <form action="index.php" method="GET" autocomplete="off" id="formBusqueda">
                    <div class="row">
                        <div class="col-12 col-md-8 mb-2">
                            <input type="text" name="buscar" class="form-control" placeholder="Buscar por nombre o teléfono"
                                value="<?php echo isset($_GET['buscar']) ? htmlspecialchars($_GET['buscar']) : ''; ?>" autocomplete="off">
                        </div>
                        <div class="col-12 col-md-4 mb-2">
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary w-50 col-md-2">
                                    <span class="d-none d-sm-inline">Buscar</span>
                                    <span class="d-inline d-sm-none">🔍</span>
                                </button>
                                <a href="index.php" class="btn btn-danger w-50 col-md-2">
                                    <span class="d-none d-sm-inline">Limpiar</span>
                                    <span class="d-inline d-sm-none">✖️</span>
                                </a>
                            </div>
                        </div>
                    </div>
                    <button class="btn btn-success mt-2 w-100" type="button" data-bs-toggle="collapse" data-bs-target="#filtrosAvanzados" aria-expanded="false">
                        Filtros de Busqueda
                    </button>

                    <!-- Filtros avanzados (colapsables) -->
                    <div class="collapse mt-3" id="filtrosAvanzados">
                        <div class="card card-body bg-light">
                            <h5 class="mb-3">Filtros de Busqueda</h5>
                            <div class="row">
                                <!-- Filtro por barrio -->
                                <div class="col-md-4">
                                    <label class="form-label">Barrio</label>
                                    <input type="text" name="barrio" class="form-control" placeholder="Ej: Centro"
                                        value="<?php echo isset($_GET['barrio']) ? htmlspecialchars($_GET['barrio']) : ''; ?>">
                                </div>

                                <!-- Filtro por frecuencia -->
                                <div class="col-md-4">
                                    <label class="form-label">Frecuencia de pago</label>
                                    <select name="frecuencia" class="form-select">
                                        <option value="">Todas</option>
                                        <option value="diario" <?php echo (isset($_GET['frecuencia']) && $_GET['frecuencia'] == 'diario') ? 'selected' : ''; ?>>Diario</option>
                                        <option value="semanal" <?php echo (isset($_GET['frecuencia']) && $_GET['frecuencia'] == 'semanal') ? 'selected' : ''; ?>>Semanal</option>
                                        <option value="mensual" <?php echo (isset($_GET['frecuencia']) && $_GET['frecuencia'] == 'mensual') ? 'selected' : ''; ?>>Mensual</option>
                                    </select>
                                </div>

                                <!-- Filtro por estado -->
                                <div class="col-md-4">
                                    <label class="form-label">Estado de pagos</label>
                                    <select name="estado" class="form-select">
                                        <option value="">Todos</option>
                                        <option value="finalizado" <?php echo (isset($_GET['estado']) && $_GET['estado'] == 'finalizado') ? 'selected' : ''; ?>>✅ Finalizados</option>
                                        <option value="pendiente" <?php echo (isset($_GET['estado']) && $_GET['estado'] == 'pendiente') ? 'selected' : ''; ?>>⏳ Con pagos pendientes</option>
                                        <option value="atrasado" <?php echo (isset($_GET['estado']) && $_GET['estado'] == 'atrasado') ? 'selected' : ''; ?>>⚠️ Con pagos atrasados</option>
                                    </select>
                                </div>
                            </div>

                            <div class="row mt-3">
                                <!-- Rango de fechas -->
                                <div class="col-md-6">
                                    <label class="form-label">Fecha de registro desde</label>
                                    <input type="date" name="fecha_desde" class="form-control"
                                        value="<?php echo isset($_GET['fecha_desde']) ? htmlspecialchars($_GET['fecha_desde']) : ''; ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Fecha de registro hasta</label>
                                    <input type="date" name="fecha_hasta" class="form-control"
                                        value="<?php echo isset($_GET['fecha_hasta']) ? htmlspecialchars($_GET['fecha_hasta']) : ''; ?>">
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="row g-0">
    <div class="col-12 col-lg-8 offset-lg-2 mb-4">
        <div class="card">

        <div class="card-header">
                <h5 class="mb-0">Historico Compras</h5>
        </div> 
            <div class="card-body">
                <script type="text/javascript">
                    function confirmar() {
                        return confirm('¿Quiere borrar el registro?');
                    }
                </script>

                <?php
                // Calcular totales antes de mostrar las pestañas
                $total_atrasados = 0;
                $total_pendientes = 0;
                $total_finalizados = 0;
                ?>

                <ul class="nav nav-tabs mb-3" id="clientesTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="pendientes-tab" data-bs-toggle="tab" data-bs-target="#pendientes" type="button" role="tab">
                            Pendientes <span class="badge bg-warning text-dark" id="badge-pendientes">0</span>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="atrasados-tab" data-bs-toggle="tab" data-bs-target="#atrasados" type="button" role="tab">
                            Atrasados <span class="badge bg-danger" id="badge-atrasados">0</span>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="finalizados-tab" data-bs-toggle="tab" data-bs-target="#finalizados" type="button" role="tab">
                            Finalizados <span class="badge bg-success" id="badge-finalizados">0</span>
                        </button>
                    </li>
                </ul>

                <div class="tab-content" id="clientesTabContent">
                    <!-- TAB: CLIENTES PENDIENTES (AL DÍA) - PRIMERA PESTAÑA -->
                    <div class="tab-pane fade show active" id="pendientes" role="tabpanel">
                        <h3 class="text-center mb-3">⏳ Clientes con Pagos Pendientes (al día)</h3>
                        <div class="table-responsive" style="max-height:420px; overflow:auto;">
                            <table class="table table-bordered table-hover mb-0">
                                <thead class="table-warning">
                                    <tr>
                                        <th>Nombre</th>
                                        <th>Teléfono</th>
                                        <th>Valor Total</th>
                                        <th>Frecuencia</th>
                                        <th>Cuotas</th>
                                        <th>Por Cuota</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    // 📄 PAGINACIÓN
                                    $registros_por_pagina = 20;
                                    $pagina_actual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
                                    $offset = ($pagina_actual - 1) * $registros_por_pagina;

                                    // 🎯 CONSTRUCCIÓN DE CONSULTA CON FILTROS
                                    $where_conditions = [];
                                    $params_url = [];

                                    // Filtro de búsqueda simple
                                    if (!empty($_GET['buscar'])) {
                                        $buscar = mysqli_real_escape_string($conn, $_GET['buscar']);
                                        $where_conditions[] = "(c.nombre_completo LIKE '%$buscar%' OR c.telefono LIKE '%$buscar%')";
                                        $params_url[] = "buscar=" . urlencode($_GET['buscar']);
                                    }

                                    // Filtro por barrio
                                    if (!empty($_GET['barrio'])) {
                                        $barrio = mysqli_real_escape_string($conn, $_GET['barrio']);
                                        $where_conditions[] = "c.barrio LIKE '%$barrio%'";
                                        $params_url[] = "barrio=" . urlencode($_GET['barrio']);
                                    }

                                    // Filtro por frecuencia
                                    if (!empty($_GET['frecuencia'])) {
                                        $frecuencia = mysqli_real_escape_string($conn, $_GET['frecuencia']);
                                        $where_conditions[] = "c.frecuencia_pago = '$frecuencia'";
                                        $params_url[] = "frecuencia=" . urlencode($_GET['frecuencia']);
                                    }

                                    // Filtro por rango de fechas
                                    if (!empty($_GET['fecha_desde'])) {
                                        $fecha_desde = mysqli_real_escape_string($conn, $_GET['fecha_desde']);
                                        $where_conditions[] = "c.fecha_registro >= '$fecha_desde'";
                                        $params_url[] = "fecha_desde=" . urlencode($_GET['fecha_desde']);
                                    }
                                    if (!empty($_GET['fecha_hasta'])) {
                                        $fecha_hasta = mysqli_real_escape_string($conn, $_GET['fecha_hasta']);
                                        $where_conditions[] = "c.fecha_registro <= '$fecha_hasta'";
                                        $params_url[] = "fecha_hasta=" . urlencode($_GET['fecha_hasta']);
                                    }

                                    // Construir WHERE clause
                                    $where_sql = count($where_conditions) > 0 ? "WHERE " . implode(" AND ", $where_conditions) : "";

                                    // Contar total de registros
                                    $query_count = "SELECT COUNT(*) as total FROM clientes c 
                                                LEFT JOIN empleados_vendedores ev ON c.vendedor_id = ev.id 
                                                $where_sql";
                                    $resultado_count = mysqli_query($conn, $query_count);
                                    $row_count = mysqli_fetch_assoc($resultado_count);
                                    $total_registros = $row_count['total'];
                                    $total_paginas = ceil($total_registros / $registros_por_pagina);

                                    // Consulta principal (incluir nombre del vendedor)
                                    $query = "SELECT c.*, ev.nombre_completo as vendedor_nombre 
                                          FROM clientes c 
                                          LEFT JOIN empleados_vendedores ev ON c.vendedor_id = ev.id 
                                          $where_sql ORDER BY c.id DESC LIMIT $registros_por_pagina OFFSET $offset";
                                    $resultado = mysqli_query($conn, $query);

                                    // Agregar filtro de estado a params_url
                                    if (!empty($_GET['estado'])) {
                                        $params_url[] = "estado=" . urlencode($_GET['estado']);
                                    }

                                    // Construir URL para paginación
                                    $params_string = count($params_url) > 0 ? "&" . implode("&", $params_url) : "";

                                    // 🎯 SEPARAR CLIENTES EN ATRASADOS, PENDIENTES Y FINALIZADOS
                                    $clientes_atrasados = [];
                                    $clientes_pendientes = [];
                                    $clientes_finalizados = [];

                                    if ($resultado && mysqli_num_rows($resultado) > 0) {
                                        while ($row = mysqli_fetch_array($resultado)) {
                                            // Obtener estado de pagos
                                            $cliente_id = $row['id'];
                                            $query_estado = "SELECT COUNT(*) as total, 
                                                            SUM(CASE WHEN estado = 'pagado' THEN 1 ELSE 0 END) as pagadas,
                                                            SUM(CASE WHEN estado = 'pendiente' AND fecha_programada < CURDATE() THEN 1 ELSE 0 END) as atrasadas
                                                    FROM pagos_clientes WHERE cliente_id = $cliente_id";
                                            $resultado_estado = mysqli_query($conn, $query_estado);
                                            $estado_data = mysqli_fetch_assoc($resultado_estado);

                                            $row['total_cuotas'] = $estado_data['total'];
                                            $row['cuotas_pagadas'] = $estado_data['pagadas'];
                                            $row['cuotas_atrasadas'] = $estado_data['atrasadas'];
                                            $row['cuotas_pendientes'] = $estado_data['total'] - $estado_data['pagadas'];

                                            // Aplicar filtro de estado si existe
                                            $incluir = true;
                                            if (!empty($_GET['estado'])) {
                                                $estado_filter = $_GET['estado'];
                                                if ($estado_filter == 'finalizado' && $row['cuotas_pendientes'] > 0) {
                                                    $incluir = false;
                                                } elseif ($estado_filter == 'pendiente' && $row['cuotas_pendientes'] == 0) {
                                                    $incluir = false;
                                                } elseif ($estado_filter == 'atrasado' && $row['cuotas_atrasadas'] == 0) {
                                                    $incluir = false;
                                                }
                                            }

                                            if ($incluir) {
                                                // Clasificar en atrasados, pendientes o finalizados
                                                if ($row['cuotas_pendientes'] > 0 && $row['cuotas_atrasadas'] > 0) {
                                                    // Tiene pagos atrasados (prioridad)
                                                    $clientes_atrasados[] = $row;
                                                } elseif ($row['cuotas_pendientes'] > 0) {
                                                    // Tiene pagos pendientes pero al día
                                                    $clientes_pendientes[] = $row;
                                                } else {
                                                    // Todos los pagos completados
                                                    $clientes_finalizados[] = $row;
                                                }
                                            }
                                        }
                                    }

                                    // CALCULAR TOTALES
                                    $total_atrasados = count($clientes_atrasados);
                                    $total_pendientes = count($clientes_pendientes);
                                    $total_finalizados = count($clientes_finalizados);

                                    // MOSTRAR CLIENTES PENDIENTES (AL DÍA) - PRIMERA PESTAÑA
                                    if (count($clientes_pendientes) > 0) {
                                        foreach ($clientes_pendientes as $row) {
                                            $monto_cuota = $row['valor_total'] / $row['cuotas'];
                                            $cuotas_pendientes = $row['cuotas_pendientes'];
                                            $estado_texto = '<span class="badge bg-warning text-dark">⏳ ' . $cuotas_pendientes . ' pendiente' . ($cuotas_pendientes != 1 ? 's' : '') . '</span>';
                                    ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($row['nombre_completo']); ?></td>
                                                <td><?php echo htmlspecialchars($row['telefono']); ?></td>
                                                <td>$<?php echo number_format($row['valor_total'], 2, ',', '.'); ?></td>
                                                <td>
                                                    <span class="badge bg-info">
                                                        <?php echo ucfirst($row['frecuencia_pago']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo $row['cuotas']; ?></td>
                                                <td><strong>$<?php echo number_format($monto_cuota, 2, ',', '.'); ?></strong></td>
                                                <td><?php echo $estado_texto; ?></td>
                                                <td>
                                                    <div class="btn-group-vertical btn-group-sm" role="group">
                                                        <a href="ver.php?id=<?php echo $row['id'] ?>" class="btn btn-outline-info btn-sm">

                                                            👁️ Ver
                                                        </a>
                                                        <?php if ($_SESSION['tipo_usuario'] == 'jefe'): ?>
                                                            <a href="editar.php?id=<?php echo $row['id'] ?>" class="btn btn-outline-warning btn-sm">
                                                                ✏️ Editar
                                                            </a>
                                                        <?php endif; ?>
                                                        <a href="estado_cuenta_pdf.php?id=<?php echo $row['id'] ?>" target="_blank" class="btn btn-outline-success btn-sm">
                                                            📄 PDF
                                                        </a>
                                                        <?php if ($_SESSION['tipo_usuario'] == 'jefe'): ?>
                                                            <a href="eliminar.php?id=<?php echo $row['id'] ?>" class="btn btn-outline-danger btn-sm" onclick="return confirmar();">
                                                                🗑️ Eliminar
                                                            </a>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                    <?php }
                                    } else {
                                        echo '<tr><td colspan="8" class="text-center text-muted">No hay clientes con pagos pendientes al día</td></tr>';
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- TAB: PAGOS ATRASADOS - SEGUNDA PESTAÑA -->
                    <div class="tab-pane fade" id="atrasados" role="tabpanel">
                        <h3 class="text-center mb-3 text-danger">🚨 Clientes con Pagos Atrasados</h3>
                        <div class="alert alert-danger">
                            <strong>⚠️ Atención:</strong> Estos clientes tienen pagos vencidos que requieren seguimiento inmediato.
                        </div>
                        <div class="table-responsive" style="max-height:420px; overflow:auto;">
                            <table class="table table-bordered table-hover mb-0">
                                <thead class="table-danger">
                                    <tr>
                                        <th>Nombre</th>
                                        <th>Teléfono</th>
                                        <th>Valor Total</th>
                                        <th>Frecuencia</th>
                                        <th>Cuotas</th>
                                        <th>Por Cuota</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    // MOSTRAR CLIENTES ATRASADOS
                                    if (count($clientes_atrasados) > 0) {
                                        foreach ($clientes_atrasados as $row) {
                                            $monto_cuota = $row['valor_total'] / $row['cuotas'];
                                            $cuotas_atrasadas = $row['cuotas_atrasadas'];
                                            $estado_texto = '<span class="badge bg-danger">🚨 ' . $cuotas_atrasadas . ' atrasada' . ($cuotas_atrasadas != 1 ? 's' : '') . '</span>';
                                    ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($row['nombre_completo']); ?></td>
                                                <td><?php echo htmlspecialchars($row['telefono']); ?></td>
                                                <td>$<?php echo number_format($row['valor_total'], 2, ',', '.'); ?></td>
                                                <td>
                                                    <span class="badge bg-info">
                                                        <?php echo ucfirst($row['frecuencia_pago']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo $row['cuotas']; ?></td>
                                                <td><strong>$<?php echo number_format($monto_cuota, 2, ',', '.'); ?></strong></td>
                                                <td><?php echo $estado_texto; ?></td>
                                                <td>
                                                    <div class="btn-group-vertical btn-group-sm" role="group">
                                                        <a href="ver.php?id=<?php echo $row['id'] ?>" class="btn btn-outline-info btn-sm">
                                                            👁️ Ver
                                                        </a>
                                                        <?php if ($_SESSION['tipo_usuario'] == 'jefe'): ?>
                                                            <a href="editar.php?id=<?php echo $row['id'] ?>" class="btn btn-outline-warning btn-sm">
                                                                ✏️ Editar
                                                            </a>
                                                        <?php endif; ?>
                                                        <a href="estado_cuenta_pdf.php?id=<?php echo $row['id'] ?>" target="_blank" class="btn btn-outline-success btn-sm">
                                                            📄 PDF
                                                        </a>
                                                        <?php if ($_SESSION['tipo_usuario'] == 'jefe'): ?>
                                                            <a href="eliminar.php?id=<?php echo $row['id'] ?>" class="btn btn-outline-danger btn-sm" onclick="return confirmar();">
                                                                🗑️ Eliminar
                                                            </a>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                    <?php }
                                    } else {
                                        echo '<tr><td colspan="8" class="text-center text-success"><strong>✅ ¡Excelente! No hay clientes con pagos atrasados</strong></td></tr>';
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- TAB: CLIENTES FINALIZADOS - TERCERA PESTAÑA -->
                <div class="tab-pane fade" id="finalizados" role="tabpanel">
                    <h3 class="text-center mb-3 text-success">✅ Clientes Finalizados</h3>
                    <div class="alert alert-success">
                        <strong>¡Felicitaciones!</strong> Estos clientes han completado todos sus pagos.
                    </div>
                    <div class="table-responsive" style="max-height:420px; overflow:auto;">
                        <table class="table table-bordered table-hover mb-0">
                            <thead class="table-success">
                                <tr>
                                    <th>Nombre</th>
                                    <th>Teléfono</th>
                                    <th>Valor Total</th>
                                    <th>Frecuencia</th>
                                    <th>Cuotas</th>
                                    <th>Por Cuota</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                if (count($clientes_finalizados) > 0) {
                                    foreach ($clientes_finalizados as $row) {
                                        $monto_cuota = $row['valor_total'] / $row['cuotas'];
                                        $estado_texto = '<span class="badge bg-success">✅ Finalizado</span>';
                                ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($row['nombre_completo']); ?></td>
                                            <td><?php echo htmlspecialchars($row['telefono']); ?></td>
                                            <td>$<?php echo number_format($row['valor_total'], 2, ',', '.'); ?></td>
                                            <td>
                                                <span class="badge bg-info">
                                                    <?php echo ucfirst($row['frecuencia_pago']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo $row['cuotas']; ?></td>
                                            <td><strong>$<?php echo number_format($monto_cuota, 2, ',', '.'); ?></strong></td>
                                            <td><?php echo $estado_texto; ?></td>
                                            <td>
                                                <div class="btn-group-vertical btn-group-sm" role="group">
                                                    <a href="ver.php?id=<?php echo $row['id'] ?>" class="btn btn-outline-info btn-sm">👁️ Ver</a>
                                                    <?php if ($_SESSION['tipo_usuario'] == 'jefe'): ?>
                                                        <a href="editar.php?id=<?php echo $row['id'] ?>" class="btn btn-outline-warning btn-sm">✏️ Editar</a>
                                                    <?php endif; ?>
                                                    <a href="estado_cuenta_pdf.php?id=<?php echo $row['id'] ?>" target="_blank" class="btn btn-outline-success btn-sm">📄 PDF</a>
                                                    <?php if ($_SESSION['tipo_usuario'] == 'jefe'): ?>
                                                        <a href="eliminar.php?id=<?php echo $row['id'] ?>" class="btn btn-outline-danger btn-sm" onclick="return confirmar();">🗑️ Eliminar</a>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                <?php }
                                } else {
                                    echo '<tr><td colspan="8" class="text-center text-muted">No hay clientes finalizados</td></tr>';
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
    // Calcular monto por cuota automáticamente
    function calcularMontoCuota() {
        const valorTotalInput = document.getElementById('valor_total');
        const senaInput = document.getElementById('sena');
        const cuotasInput = document.getElementById('cuotas');

        if (!valorTotalInput || !senaInput || !cuotasInput) return;

        const valorTotal = parseFloat(valorTotalInput.value) || 0;
        const sena = parseFloat(senaInput.value) || 0;
        const cuotas = parseInt(cuotasInput.value) || 1;

        // Validar que la seña no sea mayor al valor total
        if (sena > valorTotal) {
            document.getElementById('sena').value = valorTotal;
            return;
        }

        // Validar que tenemos valores válidos
        if (valorTotal <= 0) {
            document.getElementById('info-cuota').style.display = 'none';
            return;
        }

        const saldoRestante = valorTotal - sena;

        if (saldoRestante > 0 && cuotas > 0) {
            const montoCuota = saldoRestante / cuotas;
            document.getElementById('monto-cuota').textContent = montoCuota.toLocaleString('es-AR', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
            document.getElementById('info-cuota').style.display = 'block';
        } else if (saldoRestante === 0) {
            document.getElementById('monto-cuota').textContent = '0.00';
            document.getElementById('info-cuota').style.display = 'block';
        } else {
            document.getElementById('info-cuota').style.display = 'none';
        }
    }

    // Bloquear entrada de letras en campos numéricos (teléfono)
    function allowOnlyNumbers(e) {
        var key = e.key;
        if (e.ctrlKey || e.metaKey || key.length > 1) return;
        if (!/\d/.test(key)) {
            e.preventDefault();
        }
    }

    // Bloquear entrada de números en campos de texto (nombre)
    function allowOnlyLetters(e) {
        var key = e.key;
        if (e.ctrlKey || e.metaKey || key.length > 1) return;
        if (!/[A-Za-zÀ-ÖØ-öø-ÿ\s]/.test(key)) {
            e.preventDefault();
        }
    }

    // Inicializar todo cuando el DOM esté listo
    document.addEventListener('DOMContentLoaded', function() {
        // Actualizar badges con contadores
        document.getElementById('badge-atrasados').textContent = <?php echo $total_atrasados; ?>;
        document.getElementById('badge-pendientes').textContent = <?php echo $total_pendientes; ?>;
        document.getElementById('badge-finalizados').textContent = <?php echo $total_finalizados; ?>;

        // Referencias a elementos
        const telefono = document.getElementById('telefono');
        const nombreCompleto = document.getElementById('nombre_completo');
        const valorTotal = document.getElementById('valor_total');
        const sena = document.getElementById('sena');
        const cuotas = document.getElementById('cuotas');

        // Eventos para teléfono
        if (telefono) {
            telefono.addEventListener('keypress', allowOnlyNumbers);
        }

        // Eventos para nombre completo
        if (nombreCompleto) {
            nombreCompleto.addEventListener('keypress', allowOnlyLetters);
        }

        // Eventos para cálculo de cuota
        if (valorTotal) {
            valorTotal.addEventListener('input', calcularMontoCuota);
        }
        if (sena) {
            sena.addEventListener('input', calcularMontoCuota);
        }
        if (cuotas) {
            cuotas.addEventListener('input', calcularMontoCuota);
        }
    });
</script>
</main>