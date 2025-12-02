<?php
session_start();
include("conexion.php");

// Procesar el formulario ANTES de cualquier salida HTML
$error_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = isset($_POST['usuario']) ? trim($_POST['usuario']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';

    $errs = [];
    if (!preg_match('/^[A-Za-z0-9]{3,30}$/', $usuario)) {
        $errs[] = 'Usuario inválido.';
    }
    if (strlen($password) < 4) {
        $errs[] = 'Contraseña muy corta.';
    }

    if (empty($errs)) {
        // Validar usuario y contraseña
        $stmt = mysqli_prepare($conn, 'SELECT usuario, tipo, password FROM usuarios_sistema WHERE usuario = ? LIMIT 1');
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 's', $usuario);
            mysqli_stmt_execute($stmt);
            $res = mysqli_stmt_get_result($stmt);
            
            if ($res && mysqli_num_rows($res) === 1) {
                $row = mysqli_fetch_assoc($res);
                
                // Verificar contraseña
                if (password_verify($password, $row['password'])) {
                    $_SESSION['usuario'] = $row['usuario'];
                    $_SESSION['tipo_usuario'] = $row['tipo'];
                    
                    // Registrar acceso en auditoría
                    $stmt_audit = mysqli_prepare($conn, "INSERT INTO auditoria (usuario, accion, tabla, registro_id, detalles) VALUES (?, 'LOGIN', 'sistema', 0, 'Inicio de sesión exitoso')");
                    mysqli_stmt_bind_param($stmt_audit, 's', $usuario);
                    mysqli_stmt_execute($stmt_audit);
                    mysqli_stmt_close($stmt_audit);
                    
                    header('Location: index.php');
                    exit();
                } else {
                    $error_message = 'Usuario o contraseña incorrectos';
                }
            } else {
                $error_message = 'Usuario o contraseña incorrectos';
            }
            mysqli_stmt_close($stmt);
        } else {
            $error_message = 'Error al procesar la solicitud';
        }
    } else {
        $error_message = htmlspecialchars(implode(' ', $errs));
    }
}

// Ahora sí incluir el header
include("includes/header.php");
?>

<main>
    <div class="container-fluid min-vh-100 d-flex justify-content-center align-items-center p-4"
        style="background: url('includes/fondo_Login.jpg') no-repeat center center; background-size: cover;">

        <div class="card card-body rounded-4 border-2 border-light bg-white bg-opacity-50 shadow-lg p-4 d-flex align-items-center"
            style="backdrop-filter: blur(5px); max-width: 500px;">

            <!-- ICONO -->
            <img src="includes/logo.png" alt="Gestión Laboral S.A"
                class="rounded-circle mb-3 d-block mx-auto"
                style="height:150px; width:150px; object-fit:cover;">

            <!-- TÍTULO -->
            <h1 class="text-center fw-bold px-3 py-2 rounded-5"
                style="color: transparent; background-clip: text;-webkit-background-clip: text;-webkit-text-fill-color: transparent; border: 3px solid #8b2dec; box-shadow: 0 0 30px #8b2dec; font-family: 'Playfair Display', serif; letter-spacing: 1px; background-color: #000;">
                Mujeres Virtuosas
            </h1>

            <form class="mt-2" action="login.php" method="POST" novalidate autocomplete="off">
                <div class="mb-2">
                    <label for="usuario" class="form-label fw-semibold">Usuario</label>
                    <input
                        type="text"
                        name="usuario"
                        id="usuario"
                        class="form-control form-control-lg bg-white bg-opacity-50 text-black shadow-sm"
                        placeholder="Ingrese su usuario"
                        required
                        pattern="[A-Za-z0-9]{3,30}"
                        maxlength="30"
                        title="Solo letras y números (3 a 30 caracteres)"
                        autocomplete="off">
                </div>

                <div class="mb-2">
                    <label for="password" class="form-label fw-semibold">Contraseña</label>
                    <input
                        type="password"
                        name="password"
                        id="password"
                        class="form-control form-control-lg bg-white bg-opacity-50 text-light shadow-sm"
                        placeholder="Ingrese su contraseña"
                        required
                        minlength="4"
                        title="Mínimo 4 caracteres"
                        autocomplete="off">
                </div>


                <?php if (!empty($error_message)): ?>
                    <div class="alert alert-danger text-center"><?php echo $error_message; ?></div>
                <?php endif; ?>

                <button type="submit" class="btn btn-primary w-100 mb-3 mt-3">
                    <strong>Iniciar Sesión</strong>
                </button>
            </form>

            <div class="text-center pt-3 border-top">
                <small class="text-muted d-block mb-2">Contraseña por defecto: <strong>1234</strong></small>
                <a href="recuperar_contrasena.php" class="text-decoration-none">¿Olvidaste tu Usuario?</a>
            </div>
        </div>
    </div>

</main>

<footer class="footer">
    <div class="container text-center">
        <p class="mb-2 text-muted">
            <strong>Mujeres Virtuosas S.A</strong> © 2025 - Todos los derechos reservados
        </p>
        <p class="mb-0">
            <small class="text-muted">Sistema de Gestión de Créditos</small>
        </p>
    </div>
</footer>

<!--Scripts-->
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js" integrity="sha384-I7E8VVD/ismYTF4hNIPjVp/Zjvgyol6VFvRkX/vR+Vc4jQkC+hVqc2pM8ODewa9r" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.min.js" integrity="sha384-0pUGZvbkm6XF6gxjEnlmuGrJXVbNuzT9qBBavbLwCsOGabYfZo0T0to5eqruptLy" crossorigin="anonymous"></script>