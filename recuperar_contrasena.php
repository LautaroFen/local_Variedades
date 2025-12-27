<?php
// recuperar_contrasena.php
if (session_status() === PHP_SESSION_NONE) session_start();

include("conexion.php");
require_once __DIR__ . '/PHPMailer/email_config.php';
require_once __DIR__ . '/PHPMailer/notificacion_contrasena.php';

$message = '';
$paso = 1;

// Flash message (para evitar reenvío por refresh/doble submit)
if (isset($_SESSION['flash_message'])) {
    $message = (string)$_SESSION['flash_message'];
    unset($_SESSION['flash_message']);
}

// Destinatario fijo para recuperación (por ahora): EMAIL_TO
$allowed_recovery_recipients = [];
if (defined('EMAIL_TO')) {
    $fallback = trim(EMAIL_TO);
    if ($fallback !== '' && filter_var($fallback, FILTER_VALIDATE_EMAIL)) {
        $allowed_recovery_recipients[] = strtolower($fallback);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['reset'])) {
    unset($_SESSION['paso_recuperacion'], $_SESSION['token_id']);
    unset($_SESSION['last_recovery_send_at']);
    $paso = 1;
}

if (isset($_SESSION['paso_recuperacion'])) {
    $paso = intval($_SESSION['paso_recuperacion']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $solicitud_usuario  = isset($_POST['solicitar']);
    $verificar_codigo   = isset($_POST['verificar_codigo']);
    $cambiar_usuario    = isset($_POST['cambiar_usuario']);

    /* ------------------------------------
       PASO 1: generar token y enviar mail
       ------------------------------------ */
    if ($solicitud_usuario) {
        if (empty($allowed_recovery_recipients)) {
            $message = '<div class="alert alert-danger">No se puede enviar el código porque no está configurado el correo del administrador (EMAIL_TO).<br><small>Configurá el correo y la contraseña en <strong>PHPMailer/email_config.php</strong> (sección <strong>EDITAR AQUÍ</strong>) o usando variables de entorno <strong>MV_EMAIL_TO</strong>/<strong>MV_SMTP_USERNAME</strong>.</small></div>';
        } else {
            // Rate limit simple para evitar envíos duplicados por doble click/refresh
            $now = time();
            $lastSendAt = isset($_SESSION['last_recovery_send_at']) ? (int)$_SESSION['last_recovery_send_at'] : 0;
            if ($lastSendAt > 0 && ($now - $lastSendAt) < 30) {
                $_SESSION['paso_recuperacion'] = 2;
                $paso = 2;
                $message = '<div class="alert alert-info">Ya se envió un código recientemente. Puede tardar unos minutos. Revisa la bandeja de entrada y spam.</div>';
            } else {
            $email_destino = $allowed_recovery_recipients[0];
            try {
                $codigo = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            } catch (Exception $e) {
                $codigo = str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);
            }
            $expiracion = date('Y-m-d H:i:s', strtotime('+1 hour'));

            $stmtToken = mysqli_prepare($conn, 'INSERT INTO password_reset_tokens (usuario_id, token, expiracion) VALUES (0, ?, ?)');
            if ($stmtToken) {
                mysqli_stmt_bind_param($stmtToken, 'ss', $codigo, $expiracion);
                $okInsert = mysqli_stmt_execute($stmtToken);
                mysqli_stmt_close($stmtToken);
            } else {
                $okInsert = false;
            }

            if (!$okInsert) {
                $message = '<div class="alert alert-danger">Error interno al generar el código. Intentá nuevamente.</div>';
            } else {
                $expiracion_formateada = date('d/m/Y H:i', strtotime($expiracion));
                
                try {
                    $correo_enviado = mv_enviar_notificacion_contrasena($email_destino, $codigo, $expiracion_formateada);
                    
                    if ($correo_enviado) {
                        $_SESSION['last_recovery_send_at'] = time();
                        $_SESSION['paso_recuperacion'] = 2;
                        $_SESSION['flash_message'] = '<div class="alert alert-success">Solicitud enviada. El código fue enviado a <strong>' . htmlspecialchars($email_destino) . '</strong>.<br><small>El código expira en 1 hora. Puede tardar unos minutos (revisa spam).</small></div>';
                        header('Location: recuperar_contrasena.php');
                        exit;
                    } else {
                        $detalle_envio = isset($GLOBALS['MAILER_LAST_ERROR']) ? trim((string)$GLOBALS['MAILER_LAST_ERROR']) : '';
                        $detalle_html = $detalle_envio !== '' ? '<br><small><strong>Detalle:</strong> ' . htmlspecialchars($detalle_envio) . '</small>' : '';
                        $message = '<div class="alert alert-danger">Error al enviar el correo al administrador. Intentá nuevamente o contactá soporte.' . $detalle_html . '</div>';
                        $_SESSION['paso_recuperacion'] = 1;
                        $paso = 1;
                    }
                } catch (Throwable $e) {
                    error_log('Error al enviar correo: ' . $e->getMessage());
                    $message = '<div class="alert alert-danger">Error al enviar el correo al administrador. Por favor intentá nuevamente.<br><small><strong>Detalle:</strong> ' . htmlspecialchars($e->getMessage()) . '</small></div>';
                    $_SESSION['paso_recuperacion'] = 1;
                    $paso = 1;
                }
            }
            }
        }
    }

    /* ------------------------------------
       PASO 2: verificar código ingresado
       ------------------------------------ */ elseif ($verificar_codigo) {
        $codigo_ingresado = trim($_POST['codigo'] ?? '');

        if ($codigo_ingresado === '') {
            $message = '<div class="alert alert-warning">Por favor ingresa el código de 6 dígitos.</div>';
        } else {
            $stmt = mysqli_prepare($conn, 'SELECT id, expiracion, usado FROM password_reset_tokens WHERE token = ? AND usuario_id = 0 LIMIT 1');
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, 's', $codigo_ingresado);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_store_result($stmt);
                if (mysqli_stmt_num_rows($stmt) === 1) {
                    mysqli_stmt_bind_result($stmt, $row_id, $row_expiracion, $row_usado);
                    mysqli_stmt_fetch($stmt);

                    if ($row_usado == 1) {
                        $message = '<div class="alert alert-danger">Este código ya fue utilizado. Solicita uno nuevo.</div>';
                        $_SESSION['paso_recuperacion'] = 1;
                        $paso = 1;
                    } elseif ($row_expiracion === null || strtotime($row_expiracion) < time()) {
                        $message = '<div class="alert alert-danger">Este código ha expirado. Solicita uno nuevo.</div>';
                        $_SESSION['paso_recuperacion'] = 1;
                        $paso = 1;
                    } else {
                        $_SESSION['paso_recuperacion'] = 3;
                        $_SESSION['token_id'] = $row_id;
                        $paso = 3;
                        $message = '<div class="alert alert-success">¡Código verificado! Ahora ingresa tu usuario actual y el nuevo usuario.</div>';
                    }
                } else {
                    $message = '<div class="alert alert-danger">Código inválido. Verifica e intenta nuevamente.</div>';
                }
                mysqli_stmt_close($stmt);
            } else {
                $message = '<div class="alert alert-danger">Error interno al verificar el código.</div>';
            }
        }
    }

    /* ------------------------------------
       PASO 3: cambiar usuario
       ------------------------------------ */ elseif ($cambiar_usuario) {
        $usuario_actual = trim($_POST['usuario_actual'] ?? '');
        $nuevo_usuario  = trim($_POST['nuevo_usuario'] ?? '');
        $token_id       = intval($_POST['token_id'] ?? 0);

        $errors = [];

        if ($usuario_actual === '') {
            $errors[] = 'Seleccioná tu usuario actual.';
        }
        if (!preg_match('/^[A-Za-z0-9]{3,30}$/', $nuevo_usuario)) {
            $errors[] = 'El nuevo usuario debe tener solo letras y números (3 a 30 caracteres).';
        }

        if (empty($errors)) {
            $stmtCheck = mysqli_prepare($conn, 'SELECT id FROM usuarios_sistema WHERE usuario = ? LIMIT 1');
            if ($stmtCheck) {
                mysqli_stmt_bind_param($stmtCheck, 's', $usuario_actual);
                mysqli_stmt_execute($stmtCheck);
                mysqli_stmt_store_result($stmtCheck);
                if (mysqli_stmt_num_rows($stmtCheck) !== 1) {
                    $errors[] = 'El usuario actual no existe en el sistema.';
                }
                mysqli_stmt_close($stmtCheck);
            } else {
                $errors[] = 'Error interno comprobando el usuario actual.';
            }
        }

        if (empty($errors) && $nuevo_usuario !== $usuario_actual) {
            $stmtCheck2 = mysqli_prepare($conn, 'SELECT id FROM usuarios_sistema WHERE usuario = ? LIMIT 1');
            if ($stmtCheck2) {
                mysqli_stmt_bind_param($stmtCheck2, 's', $nuevo_usuario);
                mysqli_stmt_execute($stmtCheck2);
                mysqli_stmt_store_result($stmtCheck2);
                if (mysqli_stmt_num_rows($stmtCheck2) > 0) {
                    $errors[] = 'El nuevo usuario ya está en uso por otro usuario del sistema.';
                }
                mysqli_stmt_close($stmtCheck2);
            } else {
                $errors[] = 'Error interno comprobando el nuevo usuario.';
            }
        }

        if (empty($errors)) {
            $stmt_update = mysqli_prepare($conn, 'UPDATE usuarios_sistema SET usuario = ?, fecha_modificacion = NOW() WHERE usuario = ?');
            if ($stmt_update) {
                mysqli_stmt_bind_param($stmt_update, 'ss', $nuevo_usuario, $usuario_actual);
                mysqli_stmt_execute($stmt_update);
                $affected = mysqli_stmt_affected_rows($stmt_update);
                mysqli_stmt_close($stmt_update);

                if ($affected >= 0) {
                    if ($token_id > 0) {
                        $stmtMark = mysqli_prepare($conn, 'UPDATE password_reset_tokens SET usado = 1 WHERE id = ?');
                        if ($stmtMark) {
                            mysqli_stmt_bind_param($stmtMark, 'i', $token_id);
                            mysqli_stmt_execute($stmtMark);
                            mysqli_stmt_close($stmtMark);
                        }
                    }

                    unset($_SESSION['paso_recuperacion'], $_SESSION['token_id']);
                    $paso = 4;
                    $message = '<div class="alert alert-success">
                        <h5><i class="bi bi-check-circle-fill me-2"></i>¡Usuario cambiado exitosamente!</h5>
                        <p class="mb-0">Ya podés iniciar sesión con tu nuevo usuario.</p>
                    </div>';
                } else {
                    $errors[] = 'No se pudo actualizar el usuario.';
                }
            } else {
                $errors[] = 'Error interno al actualizar el usuario.';
            }
        }

        if (!empty($errors)) {
            $message = '<div class="alert alert-danger"><strong>Errores encontrados:</strong><ul class="mb-0 mt-2">';
            foreach ($errors as $e) {
                $message .= '<li>' . htmlspecialchars($e) . '</li>';
            }
            $message .= '</ul></div>';
            $paso = 3;
            $_SESSION['paso_recuperacion'] = 3;
        }
    }
}

// Render layout recién después de procesar POST (permite header('Location') sin errores)
include("includes/header.php");
?>

<main>
    <div class="container-fluid min-vh-100 d-flex justify-content-center align-items-center p-4"
        style="background: url('includes/fondo_Login.jpg') no-repeat center center; background-size: cover;">

        <div class="card card-body rounded-4 border-2 border-light bg-white bg-opacity-50 shadow-lg p-4 d-flex align-items-center"
            style="backdrop-filter: blur(5px); max-width: 500px;">
            <img src="includes/logo.png" alt="Mujeres Virtuosas"
                class="rounded-circle mb-3 d-block mx-auto"
                style="height:150px; width:150px; object-fit:cover;">

            <h1 class="text-center fw-bold px-3 py-2 rounded-5 mb-4"
                style="color: transparent; background-clip: text;-webkit-background-clip: text; -webkit-text-fill-color: transparent; border: 3px solid #8b2dec; box-shadow: 0 0 30px #8b2dec; font-family: 'Playfair Display', serif;
                letter-spacing: 1px; background-color: #000;">Mujeres Virtuosas
            </h1>
            <h2 class="text-center mb-3 fw-semibold">Recuperar usuario</h2>

            <?php if ($message) echo $message; ?>
            <?php if ($paso === 4): ?>
                <div class="text-center mt-4">
                    <a href="login.php" class="btn btn-primary btn-lg">
                        Ir a iniciar sesión
                    </a>

                    <div class="mt-3">
                        <a href="recuperar_contrasena.php?reset=1" class="btn btn-outline-secondary">
                            Solicitar un nuevo código
                        </a>
                    </div>
                </div>

            <?php elseif ($paso === 3): ?>
                <div class="alert alert-success">
                    <strong>Paso 3:</strong> Ingresa tu usuario actual y el nuevo usuario
                </div>

                <form action="recuperar_contrasena.php" method="post" novalidate autocomplete="off">
                    <input type="hidden" name="token_id" value="<?php echo isset($_SESSION['token_id']) ? intval($_SESSION['token_id']) : 0; ?>">

                    <div class="mb-3">
                        <label for="usuario_actual" class="form-label">👤 Usuario actual</label>
                        <select name="usuario_actual" id="usuario_actual" class="form-select" required>
                            <option value="">Selecciona tu usuario actual</option>
                            <?php
                            $query_usuarios = "SELECT usuario, tipo FROM usuarios_sistema ORDER BY tipo DESC, usuario";
                            if ($result_usuarios = mysqli_query($conn, $query_usuarios)) {
                                while ($user = mysqli_fetch_assoc($result_usuarios)) {
                                    $tipo_label = ($user['tipo'] == 'jefe') ? ' (Jefe)' : ' (Empleado)';
                                    echo '<option value="' . htmlspecialchars($user['usuario']) . '">' . htmlspecialchars($user['usuario']) . $tipo_label . '</option>';
                                }
                                mysqli_free_result($result_usuarios);
                            }
                            ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="nuevo_usuario" class="form-label">📤 Nuevo usuario</label>
                        <input type="text" name="nuevo_usuario" id="nuevo_usuario" class="form-control"
                            required maxlength="30" pattern="[A-Za-z0-9]{3,30}"
                            placeholder="Ingresa tu nuevo usuario"
                            title="Solo letras y números (3 a 30 caracteres)"
                            autocomplete="off">
                        <small class="form-text text-muted">
                            Solo letras y números, entre 3 y 30 caracteres
                        </small>
                    </div>

                    <button type="submit" name="cambiar_usuario" value="1" class="btn btn-success btn-lg w-100">
                        📤 Cambiar usuario
                    </button>
                </form>

                <div class="text-center mt-3">
                    <a href="recuperar_contrasena.php?reset=1" class="btn btn-link">
                        ← Solicitar un nuevo código
                    </a>
                </div>

            <?php elseif ($paso === 2): ?>
                <div class="alert alert-info">
                    <strong>Paso 2:</strong> Ingresa el código de 6 dígitos que te proporcionó el administrador
                </div>

                <form action="recuperar_contrasena.php" method="post" novalidate autocomplete="off">
                    <div class="mb-3">
                        <label for="codigo" class="form-label">📱 Código de verificación</label>
                        <input type="text" name="codigo" id="codigo" class="form-control text-center"
                            required maxlength="6" pattern="[0-9]{6}"
                            placeholder="000000"
                            style="font-size: 2rem; letter-spacing: 10px; font-weight: bold;"
                            title="Código de 6 dígitos"
                            autocomplete="off"
                            autofocus>
                        <small class="form-text text-muted">
                            Código enviado a <strong><?php echo !empty($allowed_recovery_recipients) ? htmlspecialchars($allowed_recovery_recipients[0]) : 'el correo del administrador'; ?></strong>
                        </small>
                    </div>

                    <button type="submit" name="verificar_codigo" value="1" class="btn btn-primary btn-lg w-100">
                        Verificar código
                    </button>
                </form>

                <div class="text-center mt-3">
                    <a href="recuperar_contrasena.php?reset=1" class="btn btn-link">
                        ← Solicitar un nuevo código
                    </a>
                </div>

            <?php else: ?>
                <div class="alert alert-info">
                    <strong>Paso 1:</strong> Solicita un código de verificación
                </div>

                <form action="recuperar_contrasena.php" method="post" novalidate autocomplete="off">
                    <button type="submit" name="solicitar" value="1" class="btn btn-primary btn-lg w-100">
                        Enviar código de recuperación
                    </button>
                </form>
            <?php endif; ?>

            <hr>
            <div class="text-center">
                <a href="login.php" class="text-decoration-none">Volver a iniciar sesión</a>
            </div>
        </div>
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js" integrity="sha384-I7E8VVD/ismYTF4hNIPjVp/Zjvgyol6VFvRkX/vR+Vc4jQkC+hVqc2pM8ODewa9r" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.min.js" integrity="sha384-0pUGZvbkm6XF6gxjEnlmuGrJXVbNuzT9qBBavbLwCsOGabYfZo0T0to5eqruptLy" crossorigin="anonymous"></script>
