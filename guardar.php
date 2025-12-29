<?php
include("conexion.php");

// ==============================
// AUTENTICACIÓN
// ==============================
if (!isset($_SESSION['usuario'])) {
    header('Location: login.php');
    exit();
}

// ==============================
// GUARDAR CLIENTE
// ==============================
if (isset($_POST['guardar-cliente'])) {

    // ------------------------------
    // VALIDACIÓN BÁSICA
    // ------------------------------
    $required = [
        'nombre_completo',
        'telefono',
        'articulos',
        'valor_total',
        'frecuencia_pago',
        'cuotas',
        'fecha_primer_pago',
        'vendedor_id'
    ];

    foreach ($required as $field) {
        if (empty($_POST[$field])) {
            $_SESSION['message'] = 'Por favor, complete todos los campos';
            header('Location: index.php');
            exit();
        }
    }

    // ------------------------------
    // SANITIZACIÓN
    // ------------------------------
    $nombre_completo = mysqli_real_escape_string($conn, trim($_POST['nombre_completo']));
    $telefono        = mysqli_real_escape_string($conn, trim($_POST['telefono']));
    $email           = isset($_POST['email']) ? trim($_POST['email']) : '';
    $barrio          = isset($_POST['barrio']) ? mysqli_real_escape_string($conn, trim((string)$_POST['barrio'])) : '';
    $direccion       = isset($_POST['direccion']) ? mysqli_real_escape_string($conn, trim((string)$_POST['direccion'])) : '';
    $articulos        = mysqli_real_escape_string($conn, trim($_POST['articulos']));
    $valor_total      = (float) $_POST['valor_total'];
    $sena             = isset($_POST['sena']) ? (float) $_POST['sena'] : 0;
    $frecuencia_pago  = mysqli_real_escape_string($conn, $_POST['frecuencia_pago']);
    $cuotas           = (int) $_POST['cuotas'];
    $fecha_primer_pago = mysqli_real_escape_string($conn, $_POST['fecha_primer_pago']);
    $vendedor_id      = (int) $_POST['vendedor_id'];
    $enviar_pdf_email = isset($_POST['enviar_pdf_email']) && $_POST['enviar_pdf_email'] == '1';

    // ------------------------------
    // LÓGICA ÚNICO PAGO (BACKEND MANDA)
    // ------------------------------
    if ($frecuencia_pago === 'unico_pago') {
        $sena = 0;
        $cuotas = 1;
        $fecha_primer_pago = date('Y-m-d');
    }

    // ------------------------------
    // VALIDACIONES
    // ------------------------------
    $errors = [];

    if (!preg_match('/^[A-Za-zÀ-ÖØ-öø-ÿ\s]+$/u', $nombre_completo)) {
        $errors[] = 'Nombre inválido.';
    }

    if (!preg_match('/^\d{10,15}$/', $telefono)) {
        $errors[] = 'Teléfono inválido.';
    }

    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Email inválido.';
    }

    if ($enviar_pdf_email && $email === '') {
        $errors[] = 'Debe ingresar un email para enviar PDF.';
    }

    if ($valor_total <= 0) {
        $errors[] = 'El valor total debe ser mayor a 0.';
    }

    if ($sena < 0 || $sena > $valor_total) {
        $errors[] = 'Seña inválida.';
    }

    if ($frecuencia_pago !== 'unico_pago') {
        if ($cuotas < 1 || $cuotas > 60) {
            $errors[] = 'Cuotas inválidas.';
        }
    }

    if (!in_array($frecuencia_pago, ['semanal', 'quincenal', 'mensual', 'unico_pago'])) {
        $errors[] = 'Frecuencia inválida.';
    }

    if (!empty($errors)) {
        $_SESSION['message'] = implode(' ', $errors);
        header('Location: index.php');
        exit();
    }

    // ------------------------------
    // INSERT CLIENTE
    // ------------------------------
    $stmt = mysqli_prepare($conn, "
        INSERT INTO clientes 
        (nombre_completo, telefono, email, barrio, direccion, articulos, valor_total, sena, frecuencia_pago, cuotas, fecha_primer_pago, vendedor_id)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    mysqli_stmt_bind_param(
        $stmt,
        'ssssssddsisi',
        $nombre_completo,
        $telefono,
        $email,
        $barrio,
        $direccion,
        $articulos,
        $valor_total,
        $sena,
        $frecuencia_pago,
        $cuotas,
        $fecha_primer_pago,
        $vendedor_id
    );

    if (!mysqli_stmt_execute($stmt)) {
        $_SESSION['message'] = 'Error al guardar cliente.';
        header('Location: index.php');
        exit();
    }

    $cliente_id = mysqli_insert_id($conn);
    mysqli_stmt_close($stmt);

    // ------------------------------
    // GENERAR PAGOS
    // ------------------------------
    $saldo_restante = $valor_total - $sena;
    $monto_por_cuota = round($saldo_restante / $cuotas, 2);

    $fecha_actual = new DateTime($fecha_primer_pago);
    $finaliza = ($cuotas === 1);

    for ($i = 1; $i <= $cuotas; $i++) {

        $fecha_programada = $fecha_actual->format('Y-m-d');
        $estado = $finaliza ? 'pagado' : 'pendiente';
        $fecha_pago = $finaliza ? $fecha_programada : null;

        $stmt_pago = mysqli_prepare($conn, "
            INSERT INTO pagos_clientes 
            (cliente_id, numero_cuota, fecha_programada, fecha_pago, monto, estado)
            VALUES (?, ?, ?, ?, ?, ?)
        ");

        mysqli_stmt_bind_param(
            $stmt_pago,
            'iisdss',
            $cliente_id,
            $i,
            $fecha_programada,
            $fecha_pago,
            $monto_por_cuota,
            $estado
        );

        mysqli_stmt_execute($stmt_pago);
        mysqli_stmt_close($stmt_pago);

        switch ($frecuencia_pago) {
            case 'semanal':   $fecha_actual->modify('+7 days'); break;
            case 'quincenal': $fecha_actual->modify('+15 days'); break;
            case 'mensual':   $fecha_actual->modify('+1 month'); break;
        }
    }

    // ------------------------------
    // AUDITORÍA
    // ------------------------------
    $detalles = sprintf(
        "Cliente creado: %s | Total: %.2f | Cuotas: %d",
        $nombre_completo,
        $valor_total,
        $cuotas
    );

    $stmt_audit = mysqli_prepare($conn, "
        INSERT INTO auditoria (usuario, accion, tabla, registro_id, detalles)
        VALUES (?, 'CREAR', 'clientes', ?, ?)
    ");

    mysqli_stmt_bind_param($stmt_audit, 'sis', $_SESSION['usuario'], $cliente_id, $detalles);
    mysqli_stmt_execute($stmt_audit);
    mysqli_stmt_close($stmt_audit);

    // ------------------------------
    // MENSAJE FINAL
    // ------------------------------
    $_SESSION['message'] = '✅ Cliente registrado correctamente.';
    header('Location: index.php');
    exit();
}
