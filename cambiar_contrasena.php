<?php
include("conexion.php");

if (!isset($_SESSION['usuario'])) {
    header('Location: login.php');
    exit();
}

$message = '';
$message_type = '';

$is_jefe = isset($_SESSION['tipo_usuario']) && $_SESSION['tipo_usuario'] === 'jefe';

// Lista de usuarios existentes (solo para jefe)
$usuarios_existentes = [];
if ($is_jefe) {
    try {
        $qUsers = mysqli_query($conn, "SELECT usuario FROM usuarios_sistema ORDER BY usuario ASC");
        if ($qUsers) {
            while ($row = mysqli_fetch_assoc($qUsers)) {
                $u = isset($row['usuario']) ? trim((string)$row['usuario']) : '';
                if ($u !== '') $usuarios_existentes[] = $u;
            }
            mysqli_free_result($qUsers);
        }
    } catch (Throwable $e) {
        // No bloquear la página si falla la consulta
        $usuarios_existentes = [];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario_objetivo = isset($_POST['usuario_objetivo']) ? trim((string)$_POST['usuario_objetivo']) : '';
    $password_nueva = isset($_POST['password_nueva']) ? (string)$_POST['password_nueva'] : '';
    $password_confirmar = isset($_POST['password_confirmar']) ? (string)$_POST['password_confirmar'] : '';

    $errors = [];

    if (!$is_jefe) {
        // Si no es jefe, solo puede cambiar su propia contraseña
        $usuario_objetivo = (string)$_SESSION['usuario'];
    }

    if ($usuario_objetivo === '') {
        $errors[] = 'Debe ingresar el usuario al que se le va a cambiar la contraseña.';
    } elseif (!preg_match('/^[A-Za-z0-9]{3,30}$/', $usuario_objetivo)) {
        $errors[] = 'Usuario inválido (solo letras y números, 3 a 30 caracteres).';
    }

    if (strlen($password_nueva) < 4) {
        $errors[] = 'La nueva contraseña debe tener al menos 4 caracteres.';
    }

    if ($password_nueva !== $password_confirmar) {
        $errors[] = 'Las contraseñas nuevas no coinciden.';
    }

    if (empty($errors)) {
        // Verificar que el usuario exista
        $stmt_check = mysqli_prepare($conn, 'SELECT id FROM usuarios_sistema WHERE usuario = ? LIMIT 1');
        mysqli_stmt_bind_param($stmt_check, 's', $usuario_objetivo);
        mysqli_stmt_execute($stmt_check);
        $res = mysqli_stmt_get_result($stmt_check);

        if ($res && mysqli_num_rows($res) === 1) {
            $row = mysqli_fetch_assoc($res);
            $usuario_id = (int)($row['id'] ?? 0);

            // Actualizar contraseña
            $password_hash = password_hash($password_nueva, PASSWORD_DEFAULT);
            $stmt_update = mysqli_prepare($conn, 'UPDATE usuarios_sistema SET password = ?, fecha_modificacion = NOW() WHERE usuario = ?');
            mysqli_stmt_bind_param($stmt_update, 'ss', $password_hash, $usuario_objetivo);

            if (mysqli_stmt_execute($stmt_update)) {
                // Registrar en auditoría
                $detalles = $is_jefe
                    ? ('Cambió contraseña del usuario: ' . $usuario_objetivo)
                    : 'Cambió su contraseña';

                $stmt_audit = mysqli_prepare($conn, "INSERT INTO auditoria (usuario, accion, tabla, registro_id, detalles) VALUES (?, 'CAMBIO_PASSWORD', 'usuarios_sistema', ?, ?)");
                mysqli_stmt_bind_param($stmt_audit, 'sis', $_SESSION['usuario'], $usuario_id, $detalles);
                mysqli_stmt_execute($stmt_audit);
                mysqli_stmt_close($stmt_audit);

                $message = '✅ Contraseña actualizada exitosamente';
                $message_type = 'success';
            } else {
                $message = 'Error al actualizar la contraseña';
                $message_type = 'danger';
            }
            mysqli_stmt_close($stmt_update);
        } else {
            $message = 'El usuario indicado no existe.';
            $message_type = 'danger';
        }

        mysqli_stmt_close($stmt_check);
    } else {
        $message = implode('<br>', $errors);
        $message_type = 'danger';
    }
}

include("includes/header.php");
?>

<main>
    <div class="container p-4">
        <div class="col-12 ">
            <div class="card w-100">
                <div class="card-header w-100">
                    <h5 class="mb-0">Cambiar Contraseña</h5>
                </div>
                <div class="card-body">
                    <?php if ($message): ?>
                        <div class="alert alert-<?php echo $message_type; ?>"><?php echo $message; ?></div>
                    <?php endif; ?>

                    <form method="POST" autocomplete="off">
                        <div class="mb-3">
                            <label class="form-label">Usuario</label>
                            <?php if ($is_jefe): ?>
                                <select name="usuario_objetivo" class="form-control" required>
                                    <option value="" selected disabled>Seleccione un usuario</option>
                                    <?php foreach ($usuarios_existentes as $u): ?>
                                        <option value="<?php echo htmlspecialchars($u); ?>"><?php echo htmlspecialchars($u); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="text-muted">Seleccioná el usuario al que se le va a cambiar la contraseña.</small>
                            <?php else: ?>
                                <input type="text" name="usuario_objetivo" class="form-control"
                                    value="<?php echo htmlspecialchars((string)$_SESSION['usuario']); ?>"
                                    readonly required minlength="3" maxlength="30"
                                    pattern="[A-Za-z0-9]{3,30}">
                                <small class="text-muted">Solo podés cambiar tu propia contraseña.</small>
                            <?php endif; ?>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Nueva contraseña</label>
                            <input type="password" name="password_nueva" class="form-control" required minlength="4">
                            <small class="text-muted">Mínimo 4 caracteres</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Confirmar nueva contraseña</label>
                            <input type="password" name="password_confirmar" class="form-control" required minlength="4">
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">Actualizar Contraseña</button>
                            <a href="index.php" class="btn btn-secondary">Volver</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <div class="row mt-3">
            <div class="col-12 text-center">
                <a href="index.php" class="btn btn-primary btn-lg me-2">Volver al Inicio</a>
            </div>
        </div>
</main>

<!--Scripts-->
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js" integrity="sha384-I7E8VVD/ismYTF4hNIPjVp/Zjvgyol6VFvRkX/vR+Vc4jQkC+hVqc2pM8ODewa9r" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.min.js" integrity="sha384-0pUGZvbkm6XF6gxjEnlmuGrJXVbNuzT9qBBavbLwCsOGabYfZo0T0to5eqruptLy" crossorigin="anonymous"></script>
