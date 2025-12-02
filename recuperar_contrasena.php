<?php
include("conexion.php");
include("includes/header.php");

// Intentar cargar el sistema de correo (si está instalado)
$email_disponible = false;
if (file_exists('email_helper.php')) {
    require_once 'email_helper.php';
    $email_disponible = true;
}

$message = '';
$paso = 1; // Paso actual: 1=solicitar, 2=verificar, 3=cambiar
$emailDetails = [];

// Si se accede con GET y hay parámetro reset, limpiar sesión
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['reset'])) {
    unset($_SESSION['paso_recuperacion']);
    unset($_SESSION['email_details']);
    unset($_SESSION['token_id']);
    $paso = 1;
}

// Recuperar el paso de la sesión si existe
if (isset($_SESSION['paso_recuperacion'])) {
    $paso = intval($_SESSION['paso_recuperacion']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Solicitar código
    $solicitud_usuario = isset($_POST['solicitar']) ? true : false;
    
    // 2. Verificar código
    $verificar_codigo = isset($_POST['verificar_codigo']) ? true : false;
    
    // 3. Cambiar usuario
    $cambiar_usuario = isset($_POST['cambiar_usuario']) ? true : false;
    
    if ($solicitud_usuario) {
        // Generar código de 6 dígitos
        $codigo = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
        $expiracion = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        // Guardar código en la base de datos (reutilizando tabla password_reset_tokens)
        // usuario_id = 0 para indicar que es para cualquier usuario
        $stmtToken = mysqli_prepare($conn, 'INSERT INTO password_reset_tokens (usuario_id, token, expiracion) VALUES (0, ?, ?)');
        if ($stmtToken) {
            mysqli_stmt_bind_param($stmtToken, 'ss', $codigo, $expiracion);
            if (mysqli_stmt_execute($stmtToken)) {
                $expiracion_formateada = date('d/m/Y H:i', strtotime($expiracion));
                
                // Preparar datos del email
                $emailDetails = [
                    'para' => 'lautaro1524arias@gmail.com',
                    'asunto' => 'Código de recuperación de usuario - Mujeres Virtuosas',
                    'codigo' => $codigo,
                    'expiracion' => $expiracion_formateada
                ];
                
                // Intentar enviar correo real si está disponible
                $correo_enviado = false;
                if ($email_disponible && defined('SMTP_PASSWORD') && SMTP_PASSWORD !== '') {
                    try {
                        $html_correo = generarHTMLCodigoRecuperacion($codigo, $expiracion_formateada);
                        $correo_enviado = enviarCorreo(
                            $emailDetails['para'],
                            $emailDetails['asunto'],
                            $html_correo
                        );
                    } catch (Exception $e) {
                        error_log("Error al enviar correo: " . $e->getMessage());
                    }
                }
                
                // Actualizar paso y guardar detalles en sesión
                $paso = 2;
                $_SESSION['paso_recuperacion'] = 2;
                $_SESSION['email_details'] = $emailDetails;
                
                if ($correo_enviado) {
                    $message = '<div class="alert alert-success">¡Código enviado exitosamente a tu correo electrónico! Revisa tu bandeja de entrada y spam.<br><small>El código expira en 1 hora.</small></div>';
                } else {
                    if ($email_disponible && (!defined('SMTP_PASSWORD') || SMTP_PASSWORD === '')) {
                        $message = '<div class="alert alert-warning">El sistema de correo no está configurado. Por favor, configura la contraseña en <code>email_config.php</code>. Mientras tanto, aquí está tu código:</div>';
                    } else {
                        $message = '<div class="alert alert-info">Simulación de correo (PHPMailer no instalado). En producción, este código se enviaría al correo electrónico.</div>';
                    }
                }
            }
            mysqli_stmt_close($stmtToken);
        }
    }
    
    // Verificar código ingresado
    elseif ($verificar_codigo) {
        $codigo_ingresado = isset($_POST['codigo']) ? trim($_POST['codigo']) : '';
        
        if ($codigo_ingresado !== '') {
            $stmt = mysqli_prepare($conn, 'SELECT id, token, expiracion, usado FROM password_reset_tokens WHERE token = ? AND usuario_id = 0 LIMIT 1');
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, 's', $codigo_ingresado);
                mysqli_stmt_execute($stmt);
                $res = mysqli_stmt_get_result($stmt);

                if ($res && mysqli_num_rows($res) === 1) {
                    $row = mysqli_fetch_assoc($res);

                    // Verificar si ya fue usado
                    if ($row['usado'] == 1) {
                        $message = '<div class="alert alert-danger">Este código ya fue utilizado. Solicita uno nuevo.</div>';
                        $paso = 1;
                        $_SESSION['paso_recuperacion'] = 1;
                    }
                    // Verificar si expiró
                    elseif (strtotime($row['expiracion']) < time()) {
                        $message = '<div class="alert alert-danger">Este código ha expirado. Solicita uno nuevo.</div>';
                        $paso = 1;
                        $_SESSION['paso_recuperacion'] = 1;
                    } else {
                        $paso = 3;
                        $_SESSION['paso_recuperacion'] = 3;
                        $_SESSION['token_id'] = $row['id'];
                        $message = '<div class="alert alert-success">¡Código verificado! Ahora ingresa tu usuario actual y el nuevo usuario.</div>';
                    }
                } else {
                    $message = '<div class="alert alert-danger">Código inválido. Verifica e intenta nuevamente.</div>';
                }
                mysqli_stmt_close($stmt);
            }
        } else {
            $message = '<div class="alert alert-warning">Por favor ingresa el código de 6 dígitos.</div>';
        }
    }
    
    // Cambiar usuario
    elseif ($cambiar_usuario) {
        $usuario_actual = isset($_POST['usuario_actual']) ? trim($_POST['usuario_actual']) : '';
        $nuevo_usuario = isset($_POST['nuevo_usuario']) ? trim($_POST['nuevo_usuario']) : '';
        $token_id = isset($_POST['token_id']) ? intval($_POST['token_id']) : 0;

        $errors = [];

        // Validar que el usuario actual existe en la base de datos
        $stmt_check_actual = mysqli_prepare($conn, 'SELECT id, usuario FROM usuarios_sistema WHERE usuario = ? LIMIT 1');
        if ($stmt_check_actual) {
            mysqli_stmt_bind_param($stmt_check_actual, 's', $usuario_actual);
            mysqli_stmt_execute($stmt_check_actual);
            $res_actual = mysqli_stmt_get_result($stmt_check_actual);
            
            if (!$res_actual || mysqli_num_rows($res_actual) !== 1) {
                $errors[] = 'El usuario actual no existe en el sistema.';
            } else {
                $row_actual = mysqli_fetch_assoc($res_actual);
                $usuario_id = $row_actual['id'];
            }
            mysqli_stmt_close($stmt_check_actual);
        }

        // Validaciones
        if (!preg_match('/^[A-Za-z0-9]{3,30}$/', $nuevo_usuario)) {
            $errors[] = 'El nuevo usuario debe tener solo letras y números (3 a 30 caracteres).';
        }
        
        // Verificar que el nuevo usuario no esté en uso (excepto si es el mismo)
        if ($nuevo_usuario !== $usuario_actual) {
            $stmt_check_nuevo = mysqli_prepare($conn, 'SELECT id FROM usuarios_sistema WHERE usuario = ? LIMIT 1');
            if ($stmt_check_nuevo) {
                mysqli_stmt_bind_param($stmt_check_nuevo, 's', $nuevo_usuario);
                mysqli_stmt_execute($stmt_check_nuevo);
                $res_nuevo = mysqli_stmt_get_result($stmt_check_nuevo);
                
                if ($res_nuevo && mysqli_num_rows($res_nuevo) > 0) {
                    $errors[] = 'El nuevo usuario ya está en uso por otro usuario del sistema.';
                }
                mysqli_stmt_close($stmt_check_nuevo);
            }
        }

        if (empty($errors)) {
            // Actualizar el usuario en la base de datos
            $stmt_update = mysqli_prepare($conn, 'UPDATE usuarios_sistema SET usuario = ?, fecha_modificacion = NOW() WHERE usuario = ?');
            if ($stmt_update) {
                mysqli_stmt_bind_param($stmt_update, 'ss', $nuevo_usuario, $usuario_actual);
                
                if (mysqli_stmt_execute($stmt_update)) {
                    // Marcar código como usado
                    $stmtMark = mysqli_prepare($conn, 'UPDATE password_reset_tokens SET usado = 1 WHERE id = ?');
                    if ($stmtMark) {
                        mysqli_stmt_bind_param($stmtMark, 'i', $token_id);
                        mysqli_stmt_execute($stmtMark);
                        mysqli_stmt_close($stmtMark);
                    }

                    // Limpiar sesión
                    unset($_SESSION['paso_recuperacion']);
                    unset($_SESSION['token_id']);
                    
                    $paso = 4; // Paso completado
                    $message = '<div class="alert alert-success">
                        <h5>✅ ¡Usuario cambiado exitosamente!</h5>
                        <p><strong>Usuario anterior:</strong> ' . htmlspecialchars($usuario_actual) . '</p>
                        <p><strong>Usuario nuevo:</strong> ' . htmlspecialchars($nuevo_usuario) . '</p>
                        <hr>
                        <p class="mb-0">Ya puedes <a href="login.php" class="alert-link fw-bold">iniciar sesión</a> con tu nuevo usuario.</p>
                    </div>';
                } else {
                    $errors[] = 'Error al actualizar el usuario en la base de datos.';
                }
                mysqli_stmt_close($stmt_update);
            } else {
                $errors[] = 'Error al preparar la actualización.';
            }
        }
        
        if (!empty($errors)) {
            $paso = 3; // Mantener en el paso 3
            $message = '<div class="alert alert-danger"><strong>Errores encontrados:</strong><ul class="mb-0 mt-2">';
            foreach ($errors as $e) {
                $message .= '<li>' . htmlspecialchars($e) . '</li>';
            }
            $message .= '</ul></div>';
        }
    }
}
?>

<main>
    <div class="container p-4">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card card-body" style="border-radius: 10px; box-shadow: 0 4px 8px rgba(5, 5, 5, 0.1);">
                    <div class="d-flex flex-column align-items-center mb-3">
                        <img src="includes/logo.jpg" alt="Mujeres Virtuosas S.A" style="height:100px; width:100px; object-fit:cover; border-radius:50%;" class="mb-2">
                        <div class="text-center fw-bold" style="font-size:3.35rem; color:#024fb7;">Mujeres Virtuosas S.A</div>
                    </div><br><br>
                    <h2 class="text-center mb-3">Recuperar usuario</h2>

                    <?php if ($message) echo $message; ?>

                    <?php if ($paso === 4): ?>
                        <!-- COMPLETADO: Proceso terminado -->
                        <div class="text-center mt-4">
                            <a href="login.php" class="btn btn-primary btn-lg">
                                🔐 Ir a iniciar sesión
                            </a>
                        </div>
                        
                    <?php elseif ($paso === 3): ?>
                        <!-- PASO 3: Cambiar usuario -->
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
                                    // Obtener usuarios de la base de datos
                                    $query_usuarios = "SELECT usuario, tipo FROM usuarios_sistema ORDER BY tipo DESC, usuario";
                                    $result_usuarios = mysqli_query($conn, $query_usuarios);
                                    while ($user = mysqli_fetch_assoc($result_usuarios)) {
                                        $tipo_label = $user['tipo'] == 'jefe' ? ' (Jefe)' : ' (Empleado)';
                                        echo '<option value="' . htmlspecialchars($user['usuario']) . '">' . htmlspecialchars($user['usuario']) . $tipo_label . '</option>';
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

                    <?php elseif ($paso === 2): ?>
                        <!-- PASO 2: Ingresar código -->
                        <?php
                        // Verificar si el correo se envió realmente
                        $correo_enviado_real = $email_disponible && defined('SMTP_PASSWORD') && SMTP_PASSWORD !== '';
                        
                        // Recuperar detalles del email de la sesión si existen
                        if (isset($_SESSION['email_details'])) {
                            $emailDetails = $_SESSION['email_details'];
                        }
                        ?>
                        
                        <?php if (!$correo_enviado_real && !empty($emailDetails) && isset($emailDetails['codigo'])): ?>
                            <!-- Mostrar simulación completa solo si NO se envió el correo -->
                            <div class="card mt-3 mb-3" style="background-color: #f8f9fa; border: 2px dashed #6c757d;">
                                <div class="card-header bg-secondary text-white">
                                    <strong>📧 SIMULACIÓN DE CORREO ELECTRÓNICO</strong>
                                </div>
                                <div class="card-body">
                                    <p><strong>Para:</strong> <?php echo htmlspecialchars($emailDetails['para']); ?></p>
                                    <p><strong>Asunto:</strong> <?php echo htmlspecialchars($emailDetails['asunto']); ?></p>
                                    <hr>
                                    <div style="padding: 15px; background: white; border-radius: 5px;">
                                        <h5>Hola,</h5>
                                        <p>Tu código de verificación es:</p>
                                        <div class="mb-3 p-3 text-center" style="background-color: #e9ecef; border-radius: 5px;">
                                            <h2 class="text-primary fw-bold mb-0" style="letter-spacing: 5px;"><?php echo htmlspecialchars($emailDetails['codigo']); ?></h2>
                                        </div>
                                        <p><strong>📅 Expira el:</strong> <?php echo htmlspecialchars($emailDetails['expiracion']); ?></p>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <div class="alert alert-info">
                            <strong>Paso 2:</strong> Ingresa el código de 6 dígitos que recibiste en tu correo
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
                                    Código enviado a: lautaro1524arias@gmail.com
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
                        <!-- PASO 1: Solicitar código -->
                        <div class="alert alert-info">
                            <strong>Paso 1:</strong> Solicita un código de verificación
                        </div>
                        
                        <div class="text-center mb-3">
                            <p>¿Olvidaste tu usuario?</p>
                            <p class="text-muted">Se enviará un código de verificación a:</p>
                            <p class="fw-bold text-primary">lautaro1524arias@gmail.com</p>
                        </div>

                        <form action="recuperar_contrasena.php" method="post" novalidate autocomplete="off">
                            <button type="submit" name="solicitar" value="1" class="btn btn-primary btn-lg w-100">
                                📧 Solicitar código de recuperación
                            </button>
                        </form>
                    <?php endif; ?>

                    <hr>
                    <div class="text-center mt-2">
                        <a href="login.php" class="text-decoration-none">Volver a iniciar sesión</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js" integrity="sha384-I7E8VVD/ismYTF4hNIPjVp/Zjvgyol6VFvRkX/vR+Vc4jQkC+hVqc2pM8ODewa9r" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.min.js" integrity="sha384-0pUGZvbkm6XF6gxjEnlmuGrJXVbNuzT9qBBavbLwCsOGabYfZo0T0to5eqruptLy" crossorigin="anonymous"></script>
<script src="javascript/recuperar_contrasena.js?v=<?php echo time(); ?>"></script>
