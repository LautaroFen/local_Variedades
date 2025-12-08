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
    <div class="col-12 mb-4">
        <div class="card w-100">
            <div class="card-header w-100">
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
    <div class="col-12 mb-4">
        <div class="card w-100">
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
                    <div class="text-center d-flex justify-content-end" >
                        <button class="btn btn-success mt-2 w-100" type="button" data-bs-toggle="collapse" data-bs-target="#filtrosAvanzados" aria-expanded="false">
                            Filtros de Busqueda
                        </button>
                    </div>
                    

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
    <div class="col-12 mb-4">
        <div class="card w-100">
            <div class="card-header">
                <h5 class="mb-0">Historico Compras</h5>
            </div>

            <div class="card-body">

                <script>
                    const confirmar = () => confirm("¿Quiere borrar el registro?");
                </script>

                <?php
                /* ==========================
                   PAGINACIÓN
                ============================ */
                $por_pagina = 20;
                $pagina = $_GET['pagina'] ?? 1;
                $offset = ($pagina - 1) * $por_pagina;

                /* ==========================
                   FILTROS
                ============================ */
                $where = [];
                $params = [];

                function addFilter($key, $sql, &$where, &$params)
                {
                    if (!empty($_GET[$key])) {
                        $val = $_GET[$key];
                        $where[] = str_replace("?", "'" . addslashes($val) . "'", $sql);
                        $params[] = "$key=" . urlencode($val);
                    }
                }

                addFilter("buscar", "(c.nombre_completo LIKE '%?%' OR c.telefono LIKE '%?%')", $where, $params);
                addFilter("barrio", "c.barrio LIKE '%?%'", $where, $params);
                addFilter("frecuencia", "c.frecuencia_pago = '?'", $where, $params);
                addFilter("fecha_desde", "c.fecha_registro >= '?'", $where, $params);
                addFilter("fecha_hasta", "c.fecha_registro <= '?'", $where, $params);

                $where_sql = count($where) ? "WHERE " . implode(" AND ", $where) : "";

                /* ==========================
                   TOTAL REGISTROS
                ============================ */
                $total = mysqli_fetch_assoc(mysqli_query($conn, "
                    SELECT COUNT(*) total 
                    FROM clientes c 
                    LEFT JOIN empleados_vendedores ev ON c.vendedor_id = ev.id 
                    $where_sql
                "))["total"];

                $paginas = ceil($total / $por_pagina);

                /* ==========================
                   CONSULTA PRINCIPAL
                ============================ */
                $q = mysqli_query($conn, "
                    SELECT 
                        c.*,
                        ev.nombre_completo AS vendedor_nombre,
                        pc.total,
                        pc.pagadas,
                        pc.atrasadas
                    FROM clientes c
                    LEFT JOIN empleados_vendedores ev ON c.vendedor_id = ev.id
                    LEFT JOIN (
                        SELECT cliente_id,
                            COUNT(*) total,
                            SUM(CASE WHEN estado='pagado' THEN 1 END) pagadas,
                            SUM(CASE WHEN estado='pendiente' AND fecha_programada < CURDATE() THEN 1 END) atrasadas
                        FROM pagos_clientes
                        GROUP BY cliente_id
                    ) pc ON pc.cliente_id = c.id
                    $where_sql
                    ORDER BY c.id DESC
                    LIMIT $por_pagina OFFSET $offset
                ");

                $pendientes = [];
                $atrasados = [];
                $finalizados = [];

                while ($r = mysqli_fetch_assoc($q)) {
                    $r["cuotas_pendientes"] = $r["total"] - $r["pagadas"];

                    if ($r["cuotas_pendientes"] == 0)
                        $finalizados[] = $r;
                    elseif ($r["atrasadas"] > 0)
                        $atrasados[] = $r;
                    else
                        $pendientes[] = $r;
                }

                /* ==========================
                   FUNCIONES PARA TABLAS
                ============================ */

                function pintarFila($r, $estado_html)
                {
                    $cuota = number_format($r["valor_total"] / $r["cuotas"], 2, ",", ".");
                    $valor_total = number_format($r["valor_total"], 2, ",", ".");
                    $es_jefe = $_SESSION["tipo_usuario"] == "jefe";

                    return "
                    <tr>
                        <td class='text-nowrap'>{$r['nombre_completo']}</td>
                        <td class='text-nowrap'>{$r['telefono']}</td>
                
                        <td class='text-nowrap'>$ $valor_total</td>
                        <td class='text-nowrap'>
                            <span class='badge text-bg-info'>" . ucfirst($r["frecuencia_pago"]) . "</span>
                        </td>
                
                        <td class='text-nowrap'>{$r['cuotas']}</td>
                        <td class='text-nowrap fw-semibold'>$ $cuota</td>
                
                        <td class='text-nowrap'>
                            <span class='badge text-bg-info'>$estado_html</span>
                        </td>
                
                        <td class='text-nowrap'>
                            <div class='btn-group btn-group-sm'>
                
                                <a href='ver.php?id={$r['id']}' class='btn btn-outline-primary'>
                                    Ver
                                </a>
                
                                " . ($es_jefe ? "<a href='editar.php?id={$r['id']}' class='btn btn-outline-warning'>
                                    Editar
                                </a>" : "") . "
                
                                <a href='estado_cuenta_pdf.php?id={$r['id']}' target='_blank' class='btn btn-outline-success'>
                                    PDF
                                </a>
                
                                " . ($es_jefe ? "<a href='eliminar.php?id={$r['id']}' class='btn btn-outline-danger' onclick='return confirmar()'>
                                    Eliminar
                                </a>" : "") . "

                            </div>
                        </td>
                    </tr>";
                }
                function renderTabla($items, $titulo, $class, $badge)
                {
                    echo "
                    <div class='$class'>
                        <h4 class='fw-bold mb-3 text-center'>$titulo</h4>
                
                        <div class='table-responsive d-flex justify-content-center'>
                            <table class='table table-striped table-hover align-middle mb-0'>
                                <thead class='$badge'>
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
                                <tbody>";

                    if (!$items) {
                        echo "<tr><td colspan='8' class='text-center text-muted py-4'>Sin resultados</td></tr>";
                    } else {
                        foreach ($items as $r) {

                            // badge del estado (texto sin badge)
                            if ($badge == "table-danger") {
                                $estado_txt = "{$r['atrasadas']} atrasada(s)";
                            } elseif ($badge == "table-success") {
                                $estado_txt = "Finalizado";
                            } else {
                                $estado_txt = "{$r['cuotas_pendientes']} pendiente(s)";
                            }

                            echo pintarFila($r, $estado_txt);
                        }
                    }

                    echo "
                                </tbody>
                            </table>
                        </div>
                    </div>
                    ";
                }
                ?>
                <!-- NAV -->
                <ul class="nav nav-tabs mb-3">
                    <li class="nav-item">
                        <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab1">
                            Pendientes
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab2">
                            Atrasados
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab3">
                            Finalizados
                        </button>
                    </li>
                </ul>

                <div class="tab-content">

                    <!-- PENDIENTES -->
                    <div class="tab-pane fade show active" id="tab1">
                        <?php renderTabla($pendientes, "Clientes con pagos pendientes", "", "table-warning"); ?>
                    </div>

                    <!-- ATRASADOS -->
                    <div class="tab-pane fade" id="tab2">
                        <?php renderTabla($atrasados, "Clientes con pagos atrasados", "", "table-danger"); ?>
                    </div>

                    <!-- FINALIZADOS -->
                    <div class="tab-pane fade" id="tab3">
                        <?php renderTabla($finalizados, "Clientes finalizados", "", "table-success"); ?>
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