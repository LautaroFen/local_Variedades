<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <?php 
    // Detectar si estamos en vistas/ o en root
    $en_vistas = (strpos($_SERVER['PHP_SELF'], '/vistas/') !== false);
    $base_path = $en_vistas ? '../' : '';
    ?>

    <!-- CSS y JS personalizados -->
    <link rel="stylesheet" href="includes/styles.css?v=<?php echo time(); ?>">
    <script src="<?php echo $base_path; ?>includes/app.js?v=<?php echo time(); ?>" defer></script>

    <!-- Favicon -->
    <link rel="icon" href="includes/logoBlanco.png?v=<?php echo time(); ?>" type="image/png">
    <link rel="shortcut icon" href="includes/logoBlanco.png?v=<?php echo time(); ?>" type="image/png">
    <link rel="apple-touch-icon" href="includes/logoBlanco.png?v=<?php echo time(); ?>">

    <title>Mujeres Virtuosas - Sistema de Gestión</title>
</head>

<?php 
$paginas_con_navbar = ['index.php', 'dashboard.php'];
if (in_array(basename($_SERVER['PHP_SELF']), $paginas_con_navbar)): 
?>

    <nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm d-lg-flex">
    <div class="container">

        <!-- Logo -->
        <a href="<?php echo $base_path; ?>index.php" class="navbar-brand d-flex align-items-center">
            <img src="<?php echo $base_path; ?>includes/logoBlanco.png"
                alt="Logo"
                class="rounded-circle me-2 shadow-sm"
                width="60" height="60">

            <span class="fw-bold d-none d-md-inline">Mujeres Virtuosas S.A</span>
            <span class="fw-bold d-inline d-md-none">MV S.A</span>
        </a>

        <!-- Tipo de usuario -->
        <div class="navbar-text mx-auto my-2">
            <span class="badge bg-primary p-2 fw-semibold">
                <?php
                if (isset($_SESSION['tipo_usuario']) && $_SESSION['tipo_usuario'] == 'jefe') {
                    echo 'Administrador';
                } else {
                    echo 'Empleado';
                }
                ?>
            </span>
        </div>

        <!-- Notificaciones SOLO en móviles -->
        <div class="me-2 d-flex d-lg-none">
            <?php include($base_path . 'includes/notificaciones_widget.php'); ?>
        </div>

        <!-- Hamburguesa -->
        <button class="navbar-toggler" type="button"
            data-bs-toggle="collapse"
            data-bs-target="#navbarNav"
            aria-controls="navbarNav"
            aria-expanded="false"
            aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <?php if (isset($_SESSION['usuario'])) { ?>
            <div class="collapse navbar-collapse" id="navbarNav">

                <!-- BOTONES + NOTIFICACIONES (DESKTOP) -->
                <ul class="navbar-nav ms-auto gap-lg-3 align-items-lg-center">

                    <!-- Notificaciones sólo en escritorio -->
                    <li class="nav-item d-none d-lg-flex">
                        <?php include($base_path . 'includes/notificaciones_widget.php'); ?>
                    </li>

                    <!-- Dashboard -->
                    <li class="nav-item my-1 w-100">
                        <a href="<?php echo $base_path; ?>dashboard.php"
                            class="btn text-white fw-semibold fs-6 border-0 w-100 text-center"
                            style="background: linear-gradient(135deg, #9753fdff, #8233f8ff, #7014faff);">
                            Estadisticas
                        </a>
                    </li>

                    <!-- Vendedores -->
                    <?php if ($_SESSION['tipo_usuario'] == 'jefe'): ?>
                        <li class="nav-item my-1 w-100">
                            <a href="<?php echo $base_path; ?>empleados_vendedores.php"
                                class="btn text-white fw-semibold fs-6 border-0 w-100 text-center"
                                style="background: linear-gradient(135deg, #9753fdff, #8233f8ff, #7014faff);">
                                Vendedores
                            </a>
                        </li>
                    <?php endif; ?>

                    <!-- Cambiar contraseña -->
                    <li class="nav-item my-1 w-100">
                        <a href="<?php echo $base_path; ?>cambiar_contrasena.php"
                            class="btn text-white fw-semibold fs-6 border-0 w-100 text-center"
                            style="background: linear-gradient(135deg, #9753fdff, #8233f8ff, #7014faff);">
                            Contraseña
                        </a>
                    </li>

                    <!-- Salir -->
                    <li class="nav-item my-1 w-100">
                        <a href="<?php echo $base_path; ?>logout.php"
                            class="btn text-white fw-semibold fs-6 border-0 w-100 text-center"
                            style="background: linear-gradient(135deg, #9753fdff, #8233f8ff, #7014faff);">
                            Salir
                        </a>
                    </li>

                </ul>

            </div>
        <?php } ?>

    </div>
</nav>


<?php endif; ?>

</body>

</html>