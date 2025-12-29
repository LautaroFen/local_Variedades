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
<div class="d-flex align-items-center justify-content-between mb-4 page-title-banner" style="background: linear-gradient(135deg, #2563eb 0%, #1486e2 100%); border-radius: 1rem; padding: 1.2rem 1rem; box-shadow: 0 2px 12px rgba(0,0,0,0.08);">
    <i class="bi bi-cart display-4 text-white flex-shrink-0" style="text-shadow: 0 2px 8px #0002;"></i>
    <h1 class="mb-0 text-white fw-bold text-uppercase flex-grow-1 text-center">Gestión de Compras</h1>
    <i class="bi bi-cart display-4 text-white flex-shrink-0" style="text-shadow: 0 2px 8px #0002;"></i>
</div>

<div class="row g-0">
    <div class="col-12 mb-4">
        <div class="card w-100">
            <div class="card-header w-100">
                <h5 class="mb-0">Registrar Compras</h5>
            </div>
            <div class="card-body">
                <form action="guardar.php" method="post" autocomplete="off" class="needs-validation" id="form-guardar-cliente" novalidate>
                    <div class="form-group mb-2">
                        <!-- Nombre completo -->
                        <label for="nombre_completo" class="form-label">Nombre completo</label>
                        <input type="text" name="nombre_completo" id="nombre_completo" class="form-control mb-3"
                            placeholder="Ingrese nombre completo del cliente"
                            pattern="[A-Za-zÀ-ÖØ-öø-ÿ\s]+"
                            title="Solo letras y espacios"
                            maxlength="150"
                            autocomplete="off"
                            autofocus
                            required>

                        <!-- Teléfono -->
                        <label for="telefono" class="form-label">Teléfono</label>
                        <input type="tel" name="telefono" id="telefono" class="form-control mb-3"
                            placeholder="Ej: 1154896235"
                            pattern="[0-9]{10,15}"
                            maxlength="15"
                            title="Solo números (10 a 15 dígitos)"
                            autocomplete="off"
                            required>

                        <!-- Email -->
                        <label for="email" class="form-label">Email</label>
                        <input type="email" name="email" id="email" class="form-control mb-3"
                            placeholder="Ej: cliente@ejemplo.com"
                            maxlength="150"
                            title="Ingrese un email válido"
                            autocomplete="off">

                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" name="enviar_pdf_email" id="enviar_pdf_email" value="1">
                            <label class="form-check-label" for="enviar_pdf_email">Enviar PDF al email ingresado</label>
                        </div>

                        <!-- Barrio -->
                        <label for="barrio" class="form-label">Barrio</label>
                        <input type="text" name="barrio" id="barrio" class="form-control mb-3"
                            placeholder="Ingrese el barrio"
                            pattern="[A-Za-zÀ-ÖØ-öø-ÿ0-9\s.,\-]+"
                            maxlength="100"
                            title="Letras, números, espacios y . , -"
                            autocomplete="off">

                        <!-- Dirección -->
                        <label for="direccion" class="form-label">Dirección</label>
                        <input type="text" name="direccion" id="direccion" class="form-control mb-3"
                            placeholder="Calle y número"
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

                        <!-- Frecuencia de pago -->
                        <label for="frecuencia_pago" class="form-label">Frecuencia de pago</label>
                        <select name="frecuencia_pago" id="frecuencia_pago" class="form-control mb-3" required>
                            <option value="" selected disabled>Seleccione frecuencia</option>
                            <option value="semanal">Semanal</option>
                            <option value="quincenal">Quincenal</option>
                            <option value="mensual">Mensual</option>
                            <option value="unico_pago">Único pago</option>
                        </select>

                        <!-- Seña (adelanto) -->
                        <label for="sena" class="form-label">Seña / Adelanto ($)</label>
                        <input type="number" name="sena" id="sena" class="form-control mb-3"
                            min="0"
                            step="0.01"
                            value="0"
                            title="Dinero que el cliente deja como adelanto"
                            autocomplete="off">
                        <small class="text-muted d-block mb-3">💡 Si el cliente deja una seña, se descontará del valor total</small>

                        <!-- Cuotas -->
                        <label for="cuotas" class="form-label">Cantidad de cuotas</label>
                        <input type="number" name="cuotas" id="cuotas" class="form-control mb-3"
                            min="1"
                            max="60"
                            value="1"
                            required
                            title="Entre 1 y 60 cuotas"
                            autocomplete="off">

                        <!-- Fecha de primer pago -->
                        <label for="fecha_primer_pago" class="form-label">Fecha del primer pago</label>
                        <input type="date" name="fecha_primer_pago" id="fecha_primer_pago" class="form-control mb-3"
                            required
                            min="<?php echo date('Y-m-d'); ?>"
                            value="<?php echo date('Y-m-d'); ?>"
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
                            <input type="text"
                                name="buscar"
                                class="form-control"
                                placeholder="Buscar por nombre, teléfono o email"
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
                    <div class="text-center d-flex justify-content-end">
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
                                        <option value="finalizado" <?php echo (isset($_GET['estado']) && $_GET['estado'] == 'finalizado') ? 'selected' : ''; ?>>Finalizados</option>
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
                        $val = addslashes($_GET[$key]);
                        // Para LIKE, poner los comodines dentro del valor
                        if (strpos($sql, 'LIKE') !== false) {
                            $val = "%$val%";
                        }
                        $where[] = str_replace("?", "'" . $val . "'", $sql);
                        $params[] = "$key=" . urlencode($_GET[$key]);
                    }
                }

                addFilter("buscar", "(c.nombre_completo LIKE ? OR c.telefono LIKE ? OR c.email LIKE ?)", $where, $params);
                addFilter("barrio", "c.barrio LIKE ?", $where, $params);
                addFilter("frecuencia", "c.frecuencia_pago = ?", $where, $params);
                addFilter("fecha_desde", "c.fecha_registro >= ?", $where, $params);
                addFilter("fecha_hasta", "c.fecha_registro <= ?", $where, $params);

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
                ");
                //LIMIT $por_pagina OFFSET $offset  // Comando comentado para referencia, no usar paginación

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

                function pintarFila($r, $estado_html, $tab)
                {
                    $tab_qs = urlencode((string)$tab);
                    $id = (int)$r['id'];

                    $cuota = number_format($r["valor_total"] / $r["cuotas"], 2, ",", ".");
                    $valor_total = number_format($r["valor_total"], 2, ",", ".");
                    $es_jefe = $_SESSION["tipo_usuario"] == "jefe";
                    $vendedor_nombre = (isset($r['vendedor_nombre']) && $r['vendedor_nombre'] !== null && $r['vendedor_nombre'] !== '')
                        ? htmlspecialchars($r['vendedor_nombre'])
                        : 'Sin asignar';

                    $frecuencia_raw = isset($r['frecuencia_pago']) ? (string)$r['frecuencia_pago'] : '';
                    $frecuencia_label_map = [
                        'semanal' => 'Semanal',
                        'quincenal' => 'Quincenal',
                        'mensual' => 'Mensual',
                        'unico_pago' => 'Único pago',
                    ];
                    $frecuencia_label = $frecuencia_label_map[$frecuencia_raw] ?? ucfirst(str_replace('_', ' ', $frecuencia_raw));

                    return "
                    <tr id='cliente-$id'>
                        <td class='text-nowrap'>{$r['nombre_completo']}</td>
                        <td class='text-nowrap'>{$r['telefono']}</td>
                        <td class='text-nowrap'>{$r['email']}</td>
                        <td class='text-nowrap'>{$vendedor_nombre}</td>
                        <td class='text-nowrap'>$ $valor_total</td>
                        <td class='text-nowrap'>
                            <span class='badge text-bg-info'>{$frecuencia_label}</span>
                        </td>
                        <td class='text-nowrap'>{$r['cuotas']}</td>
                        <td class='text-nowrap fw-semibold'>$ $cuota</td>
                        <td class='text-nowrap'>
                            <span class='badge text-bg-info'>$estado_html</span>
                        </td>
                        <td class='text-nowrap'>
                            <div class='btn-group btn-group-sm'>
                                <a href='ver.php?id=$id&tab=$tab_qs' class='btn btn-outline-primary'>
                                    Ver
                                </a>
                                " . ($es_jefe ? "<a href='editar.php?id=$id' class='btn btn-outline-warning'>
                                    Editar
                                </a>" : "") . "
                                <a href='estado_cuenta_pdf.php?id=$id' target='_blank' class='btn btn-outline-success'>
                                    PDF
                                </a>
                                " . ($es_jefe ? "<a href='eliminar.php?id=$id' class='btn btn-outline-danger' data-confirm='¿Quiere borrar el registro?'>
                                    Eliminar
                                </a>" : "") . "
                            </div>
                        </td>
                    </tr>";
                }
                function renderTabla($items, $titulo, $class, $badge)
                {
                    $tabName = 'pendientes';
                    if ($badge == 'table-danger') {
                        $tabName = 'atrasados';
                    } elseif ($badge == 'table-success') {
                        $tabName = 'finalizados';
                    }

                    echo "
                    <div class='$class'>
                        <h4 class='fw-bold mb-3 text-center'>$titulo</h4>
                
                        <div class='table-responsive'>
                            <table class='table table-striped table-hover align-middle mb-0'>
                                <thead class='$badge'>
                                    <tr>
                                        <th>Nombre</th>
                                        <th>Teléfono</th>
                                        <th>Email</th>
                                        <th>Vendedor</th>
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
                        echo "<tr><td colspan='10' class='text-center text-muted py-4'>Sin resultados</td></tr>";
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

                            echo pintarFila($r, $estado_txt, $tabName);
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
                <div id="grilla-compras">
                    <?php
                    $tabParam = isset($_GET['tab']) ? strtolower((string)$_GET['tab']) : '';
                    if (!in_array($tabParam, ['pendientes', 'atrasados', 'finalizados'], true)) {
                        $tabParam = 'pendientes';
                    }
                    $tab1Active = $tabParam === 'pendientes' ? 'active' : '';
                    $tab2Active = $tabParam === 'atrasados' ? 'active' : '';
                    $tab3Active = $tabParam === 'finalizados' ? 'active' : '';

                    $pane1Active = $tabParam === 'pendientes' ? 'show active' : '';
                    $pane2Active = $tabParam === 'atrasados' ? 'show active' : '';
                    $pane3Active = $tabParam === 'finalizados' ? 'show active' : '';
                    ?>
                    <ul class="nav nav-tabs mb-3">
                        <li class="nav-item">
                            <button class="nav-link <?php echo $tab1Active; ?>" data-bs-toggle="tab" data-bs-target="#tab1">
                                Pendientes
                            </button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link <?php echo $tab2Active; ?>" data-bs-toggle="tab" data-bs-target="#tab2">
                                Atrasados
                            </button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link <?php echo $tab3Active; ?>" data-bs-toggle="tab" data-bs-target="#tab3">
                                Finalizados
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content">

                        <!-- PENDIENTES -->
                        <div class="tab-pane fade <?php echo $pane1Active; ?>" id="tab1">
                            <?php renderTabla($pendientes, "Clientes con pagos pendientes", "", "table-warning"); ?>
                        </div>

                        <!-- ATRASADOS -->
                        <div class="tab-pane fade <?php echo $pane2Active; ?>" id="tab2">
                            <?php renderTabla($atrasados, "Clientes con pagos atrasados", "", "table-danger"); ?>
                        </div>

                        <!-- FINALIZADOS -->
                        <div class="tab-pane fade <?php echo $pane3Active; ?>" id="tab3">
                            <?php renderTabla($finalizados, "Clientes finalizados", "", "table-success"); ?>
                        </div>
                    </div>
                </div> <!-- cierre grilla-compras -->
            </div>
        </div>
    </div>
</div>

<script>
/* ===============================
   UTILIDADES
================================ */
function todayISODate() {
    const d = new Date();
    d.setMinutes(d.getMinutes() - d.getTimezoneOffset());
    return d.toISOString().slice(0, 10);
}

/* ===============================
   CALCULO DE CUOTA
================================ */
function calcularMontoCuota() {
    if (frecuenciaPago.value === 'unico_pago') {
        document.getElementById('monto-cuota').textContent = '0.00';
        document.getElementById('info-cuota').style.display = 'block';
        return;
    }

    const total = parseFloat(valorTotal.value) || 0;
    const adelanto = parseFloat(sena.value) || 0;
    const cantCuotas = parseInt(cuotas.value) || 1;

    if (adelanto > total) {
        sena.value = total;
        return;
    }

    if (total <= 0 || cantCuotas <= 0) {
        document.getElementById('info-cuota').style.display = 'none';
        return;
    }

    const saldo = total - adelanto;
    const cuota = saldo / cantCuotas;

    document.getElementById('monto-cuota').textContent =
        cuota.toLocaleString('es-AR', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });

    document.getElementById('info-cuota').style.display = 'block';
}

/* ===============================
   UNICO PAGO – BLOQUEO REAL
================================ */
function syncUnicoPagoLockState() {
    const isUnico = frecuenciaPago.value === 'unico_pago';
    const hoy = todayISODate();

    if (isUnico) {
        sena.value = 0;
        cuotas.value = 1;
        fechaPrimerPago.value = hoy;

        sena.disabled = true;
        cuotas.disabled = true;
        fechaPrimerPago.disabled = true;
    } else {
        sena.disabled = false;
        cuotas.disabled = false;
        fechaPrimerPago.disabled = false;
    }

    calcularMontoCuota();
}

/* ===============================
   DOM READY
================================ */
document.addEventListener('DOMContentLoaded', () => {
    // Elementos
    window.valorTotal = document.getElementById('valor_total');
    window.sena = document.getElementById('sena');
    window.cuotas = document.getElementById('cuotas');
    window.frecuenciaPago = document.getElementById('frecuencia_pago');
    window.fechaPrimerPago = document.getElementById('fecha_primer_pago');
    const form = document.getElementById('form-guardar-cliente');

    // Defaults iniciales
    if (!sena.value) sena.value = 0;
    if (!cuotas.value) cuotas.value = 1;
    if (!fechaPrimerPago.value) fechaPrimerPago.value = todayISODate();

    // Eventos
    valorTotal.addEventListener('input', calcularMontoCuota);
    sena.addEventListener('input', calcularMontoCuota);
    cuotas.addEventListener('input', calcularMontoCuota);

    frecuenciaPago.addEventListener('change', syncUnicoPagoLockState);

    // Aplicar estado inicial
    syncUnicoPagoLockState();

    // Antes de enviar → reactivar disabled
    form.addEventListener('submit', () => {
        sena.disabled = false;
        cuotas.disabled = false;
        fechaPrimerPago.disabled = false;
    });
});
</script>

</main>