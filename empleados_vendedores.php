<?php
include("conexion.php");

// Solo jefes pueden gestionar vendedores
if ($_SESSION['tipo_usuario'] != 'jefe') {
    $_SESSION['message'] = '⛔ Acceso denegado. Solo jefes pueden gestionar empleados vendedores.';
    header('Location: index.php');
    exit();
}

// Procesar acciones
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];

        if ($action == 'agregar' && !empty($_POST['nombre_completo'])) {
            $nombre = trim($_POST['nombre_completo']);
            $stmt = mysqli_prepare($conn, "INSERT INTO empleados_vendedores (nombre_completo) VALUES (?)");
            mysqli_stmt_bind_param($stmt, 's', $nombre);
            if (mysqli_stmt_execute($stmt)) {
                $_SESSION['message'] = '✅ Empleado vendedor agregado correctamente';
            }
            mysqli_stmt_close($stmt);
        }

        if ($action == 'desactivar' && !empty($_POST['id'])) {
            $id = intval($_POST['id']);
            $stmt = mysqli_prepare($conn, "UPDATE empleados_vendedores SET activo = 0 WHERE id = ?");
            mysqli_stmt_bind_param($stmt, 'i', $id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            $_SESSION['message'] = '✅ Empleado desactivado';
        }

        if ($action == 'activar' && !empty($_POST['id'])) {
            $id = intval($_POST['id']);
            $stmt = mysqli_prepare($conn, "UPDATE empleados_vendedores SET activo = 1 WHERE id = ?");
            mysqli_stmt_bind_param($stmt, 'i', $id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            $_SESSION['message'] = '✅ Empleado activado';
        }

        if ($action == 'editar' && !empty($_POST['id']) && !empty($_POST['nombre_completo'])) {
            $id = intval($_POST['id']);
            $nombre = trim($_POST['nombre_completo']);
            $stmt = mysqli_prepare($conn, "UPDATE empleados_vendedores SET nombre_completo = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt, 'si', $nombre, $id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            $_SESSION['message'] = '✅ Empleado actualizado';
        }

        if ($action == 'eliminar' && !empty($_POST['id'])) {
            $id = intval($_POST['id']);
            // Primero eliminar las referencias en la tabla clientes (poner vendedor_id en NULL)
            $stmt = mysqli_prepare($conn, "UPDATE clientes SET vendedor_id = NULL WHERE vendedor_id = ?");
            mysqli_stmt_bind_param($stmt, 'i', $id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            // Luego eliminar el empleado
            $stmt = mysqli_prepare($conn, "DELETE FROM empleados_vendedores WHERE id = ?");
            mysqli_stmt_bind_param($stmt, 'i', $id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            $_SESSION['message'] = '✅ Empleado eliminado correctamente';
        }

        header('Location: empleados_vendedores.php');
        exit();
    }
}

// Obtener lista de empleados
$query = "SELECT * FROM empleados_vendedores ORDER BY nombre_completo";
$result = mysqli_query($conn, $query);

include("includes/header.php");
?>
<main>
    <div class="container p-4">
        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-info alert-dismissible fade show">
                <?php echo htmlspecialchars($_SESSION['message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['message']); ?>
        <?php endif; ?>

        <div class="d-flex align-items-center justify-content-between mb-4 page-title-banner" style="background: linear-gradient(135deg, #2563eb 0%, #1486e2 100%); border-radius: 1rem; padding: 1.2rem 1rem; box-shadow: 0 2px 12px rgba(0,0,0,0.08);">
            <i class="bi bi-person-badge display-4 text-white flex-shrink-0" style="text-shadow: 0 2px 8px #0002;"></i>
            <h1 class="mb-0 text-white fw-bold text-uppercase flex-grow-1 text-center">Gestión de Empleados</h1>
            <i class="bi bi-person-badge display-4 text-white flex-shrink-0" style="text-shadow: 0 2px 8px #0002;"></i>
        </div>
        <!-- Formulario para agregar empleado -->
        <div class="row g-0">
            <div class="col-12 mb-4">
                <div class="card w-100">
                    <div class="card-header">
                        <h5 class="mb-0">Agregar Nuevo Empleado</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" autocomplete="off">
                            <input type="hidden" name="action" value="agregar">
                            <div class="input-group">
                                <input type="text"
                                    name="nombre_completo"
                                    class="form-control"
                                    placeholder="Nombre completo del empleado"
                                    pattern="[A-Za-zÁ-ÿ\s]+"
                                    required>
                                <button type="submit" class="btn btn-primary">
                                    Agregar Empleado
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Lista de empleados -->
        <div class="row g-0">
            <div class="col-12 mb-4">
                <div class="card w-100">
                    <div class="card-header">
                        <h5 class="mb-0">Lista de Empleados Vendedores n</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover table-bordered">
                                <thead class="table-dark">
                                    <tr>
                                        <th>ID</th>
                                        <th>Nombre Completo</th>
                                        <th>Estado</th>
                                        <th>Fecha Registro</th>
                                        <th>Ventas</th>
                                        <th>Total Vendido</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    // Primero guardar todos los empleados en un array
                                    $empleados = [];
                                    while ($empleado = mysqli_fetch_assoc($result)) {
                                        // Contar ventas y total vendido del empleado
                                        $stmt_stats = mysqli_prepare($conn, "SELECT COUNT(*) as total, SUM(valor_total) as total_vendido FROM clientes WHERE vendedor_id = ?");
                                        mysqli_stmt_bind_param($stmt_stats, 'i', $empleado['id']);
                                        mysqli_stmt_execute($stmt_stats);
                                        $stats_result = mysqli_stmt_get_result($stmt_stats);
                                        $stats_data = mysqli_fetch_assoc($stats_result);
                                        $empleado['total_ventas'] = $stats_data['total'];
                                        $empleado['total_vendido'] = $stats_data['total_vendido'] ?? 0;
                                        mysqli_stmt_close($stmt_stats);
                                        $empleados[] = $empleado;
                                    }

                                    // Mostrar filas de empleados
                                    foreach ($empleados as $empleado):
                                        $total_ventas = $empleado['total_ventas'];
                                        $total_vendido = $empleado['total_vendido'];
                                    ?>
                                        <tr>
                                            <td><?php echo $empleado['id']; ?></td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($empleado['nombre_completo']); ?></strong>
                                            </td>
                                            <td>
                                                <?php if ($empleado['activo']): ?>
                                                    <span class="badge bg-success"><i class="bi bi-check-circle-fill me-1"></i>Activo</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">⏸️ Inactivo</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo date('d/m/Y', strtotime($empleado['fecha_registro'])); ?></td>
                                            <td class="text-center">
                                                <button type="button"
                                                    class="btn btn-sm btn-info w-100"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#ventasModal<?php echo $empleado['id']; ?>"
                                                    title="Ver historial de ventas">
                                                    <i class="bi bi-bar-chart-line"></i>
                                                    <?php echo $total_ventas; ?>
                                                </button>
                                            </td>
                                            <td class="text-end">
                                                <strong class="text-success fs-6">$<?php echo number_format($total_vendido, 2, ',', '.'); ?></strong>
                                            </td>
                                            <td class="text-nowrap">
                                                <div class="btn-group btn-group-sm" role="group" aria-label="Acciones empleado">
                                                    <button type="button"
                                                        class="btn btn-outline-warning"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#editarModal<?php echo $empleado['id']; ?>"
                                                        title="Editar empleado">
                                                        Editar
                                                    </button>

                                                    <?php if ($empleado['activo']): ?>
                                                        <button type="submit"
                                                            form="desactivarEmpleado<?php echo $empleado['id']; ?>"
                                                            class="btn btn-outline-secondary"
                                                            data-confirm="¿Desactivar este empleado?"
                                                            title="Desactivar empleado">
                                                            Desactivar
                                                        </button>
                                                    <?php else: ?>
                                                        <button type="submit"
                                                            form="activarEmpleado<?php echo $empleado['id']; ?>"
                                                            class="btn btn-outline-success"
                                                            title="Activar empleado">
                                                            Activar
                                                        </button>
                                                    <?php endif; ?>

                                                    <button type="submit"
                                                        form="eliminarEmpleado<?php echo $empleado['id']; ?>"
                                                        class="btn btn-outline-danger"
                                                        data-confirm="⚠️ ¿ELIMINAR permanentemente este empleado?&#10;&#10;Esta acción NO se puede deshacer.&#10;Las ventas asignadas quedarán sin vendedor."
                                                        title="Eliminar empleado permanentemente">
                                                        Eliminar
                                                    </button>
                                                </div>

                                                <?php if ($empleado['activo']): ?>
                                                    <form id="desactivarEmpleado<?php echo $empleado['id']; ?>" method="POST" class="d-none">
                                                        <input type="hidden" name="action" value="desactivar">
                                                        <input type="hidden" name="id" value="<?php echo $empleado['id']; ?>">
                                                    </form>
                                                <?php else: ?>
                                                    <form id="activarEmpleado<?php echo $empleado['id']; ?>" method="POST" class="d-none">
                                                        <input type="hidden" name="action" value="activar">
                                                        <input type="hidden" name="id" value="<?php echo $empleado['id']; ?>">
                                                    </form>
                                                <?php endif; ?>

                                                <form id="eliminarEmpleado<?php echo $empleado['id']; ?>" method="POST" class="d-none">
                                                    <input type="hidden" name="action" value="eliminar">
                                                    <input type="hidden" name="id" value="<?php echo $empleado['id']; ?>">
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
     <div class="row mt-2">
            <div class="col-12 text-center">
                <a href="index.php" class="btn btn-primary btn-lg me-2">Volver al Inicio</a>
            </div>
        </div>
</main>

<!-- Modales fuera de la tabla -->
<?php foreach ($empleados as $empleado): ?>
    <!-- Modal para editar -->
    <div class="modal fade" id="editarModal<?php echo $empleado['id']; ?>" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">✏️ Editar Empleado</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="editar">
                        <input type="hidden" name="id" value="<?php echo $empleado['id']; ?>">
                        <div class="mb-3">
                            <label class="form-label">Nombre Completo</label>
                            <input type="text"
                                name="nombre_completo"
                                class="form-control"
                                value="<?php echo htmlspecialchars($empleado['nombre_completo']); ?>"
                                pattern="[A-Za-zÁ-ÿ\s]+"
                                required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">💾 Guardar Cambios</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal para historial de ventas -->
    <div class="modal fade" id="ventasModal<?php echo $empleado['id']; ?>" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title">
                        📊 Historial de Ventas - <?php echo htmlspecialchars($empleado['nombre_completo']); ?>
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <?php
                    // Obtener ventas del empleado
                    $stmt_ventas = mysqli_prepare($conn, "SELECT id, nombre_completo, telefono, valor_total, cuotas, frecuencia_pago, fecha_registro FROM clientes WHERE vendedor_id = ? ORDER BY fecha_registro DESC");
                    mysqli_stmt_bind_param($stmt_ventas, 'i', $empleado['id']);
                    mysqli_stmt_execute($stmt_ventas);
                    $ventas_result = mysqli_stmt_get_result($stmt_ventas);
                    $ventas_count = mysqli_num_rows($ventas_result);
                    ?>

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <div class="alert alert-info mb-0">
                                <strong>📦 Total de Ventas:</strong> <?php echo $ventas_count; ?>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="alert alert-success mb-0">
                                <strong>💰 Total Vendido:</strong> $<?php echo number_format($empleado['total_vendido'], 2, ',', '.'); ?>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="alert alert-warning mb-0">
                                <strong>📊 Promedio:</strong> $<?php echo $ventas_count > 0 ? number_format($empleado['total_vendido'] / $ventas_count, 2, ',', '.') : '0,00'; ?>
                            </div>
                        </div>
                    </div>

                    <?php if ($ventas_count > 0): ?>
                        <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                            <table class="table table-striped table-hover">
                                <thead class="table-dark sticky-top">
                                    <tr>
                                        <th>ID</th>
                                        <th>Cliente</th>
                                        <th>Teléfono</th>
                                        <th>Valor Total</th>
                                        <th>Cuotas</th>
                                        <th>Frecuencia</th>
                                        <th>Fecha Registro</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($venta = mysqli_fetch_assoc($ventas_result)): ?>
                                        <tr>
                                            <td><strong>#<?php echo $venta['id']; ?></strong></td>
                                            <td><?php echo htmlspecialchars($venta['nombre_completo']); ?></td>
                                            <td><?php echo htmlspecialchars($venta['telefono']); ?></td>
                                            <td><strong class="text-success">$<?php echo number_format($venta['valor_total'], 2, ',', '.'); ?></strong></td>
                                            <td><?php echo $venta['cuotas']; ?></td>
                                            <td>
                                                <span class="badge bg-secondary">
                                                    <?php echo ucfirst($venta['frecuencia_pago']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('d/m/Y H:i', strtotime($venta['fecha_registro'])); ?></td>
                                            <td>
                                                <a href="ver.php?id=<?php echo $venta['id']; ?>" class="btn btn-sm btn-success" title="Ver detalles">👁️ Ver</a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-warning text-center">
                            <strong>⚠️ Sin ventas registradas</strong><br>
                            Este empleado aún no tiene ventas asignadas.
                        </div>
                    <?php endif; ?>

                    <?php mysqli_stmt_close($stmt_ventas); ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>
<?php endforeach; ?>