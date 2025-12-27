<?php
include("conexion.php");

if (!isset($_SESSION['usuario'])) {
    header('Location: login.php');
    exit();
}

$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password_actual = isset($_POST['password_actual']) ? $_POST['password_actual'] : '';
    $password_nueva = isset($_POST['password_nueva']) ? $_POST['password_nueva'] : '';
    $password_confirmar = isset($_POST['password_confirmar']) ? $_POST['password_confirmar'] : '';

    $errors = [];

    if (strlen($password_nueva) < 4) {
        $errors[] = 'La nueva contraseña debe tener al menos 4 caracteres.';
    }

    if ($password_nueva !== $password_confirmar) {
        $errors[] = 'Las contraseñas nuevas no coinciden.';
    }

    if (empty($errors)) {
        // Verificar contraseña actual
        $stmt = mysqli_prepare($conn, 'SELECT password FROM usuarios_sistema WHERE usuario = ? LIMIT 1');
        mysqli_stmt_bind_param($stmt, 's', $_SESSION['usuario']);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);

        if ($res && mysqli_num_rows($res) === 1) {
            $row = mysqli_fetch_assoc($res);

            if (password_verify($password_actual, $row['password'])) {
                // Actualizar contraseña
                $password_hash = password_hash($password_nueva, PASSWORD_DEFAULT);
                $stmt_update = mysqli_prepare($conn, 'UPDATE usuarios_sistema SET password = ? WHERE usuario = ?');
                mysqli_stmt_bind_param($stmt_update, 'ss', $password_hash, $_SESSION['usuario']);

                if (mysqli_stmt_execute($stmt_update)) {
                    // Registrar en auditoría
                    $stmt_audit = mysqli_prepare($conn, "INSERT INTO auditoria (usuario, accion, tabla, registro_id, detalles) VALUES (?, 'CAMBIO_PASSWORD', 'usuarios_sistema', 0, 'Cambió su contraseña')");
                    mysqli_stmt_bind_param($stmt_audit, 's', $_SESSION['usuario']);
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
                $message = 'La contraseña actual es incorrecta';
                $message_type = 'danger';
            }
        }
        mysqli_stmt_close($stmt);
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
                            <label class="form-label">Contraseña actual</label>
                            <input type="password" name="password_actual" class="form-control" required minlength="4">
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
</main>

<!--Scripts-->
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js" integrity="sha384-I7E8VVD/ismYTF4hNIPjVp/Zjvgyol6VFvRkX/vR+Vc4jQkC+hVqc2pM8ODewa9r" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.min.js" integrity="sha384-0pUGZvbkm6XF6gxjEnlmuGrJXVbNuzT9qBBavbLwCsOGabYfZo0T0to5eqruptLy" crossorigin="anonymous"></script>
<script src="javascript/cambiar_contrasena.js?v=<?php echo time(); ?>"></script>