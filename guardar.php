<?php

include("conexion.php");

// Verificar que el usuario esté autenticado
if (!isset($_SESSION['usuario'])) {
    header('Location: login.php');
    exit();
}

// NUEVO: Guardar cliente con crédito
if (isset($_POST['guardar-cliente'])) {
    if (!empty($_POST['nombre_completo']) && !empty($_POST['telefono']) && 
        !empty($_POST['barrio']) && !empty($_POST['direccion']) && 
        !empty($_POST['articulos']) && !empty($_POST['valor_total']) && 
        !empty($_POST['frecuencia_pago']) && !empty($_POST['cuotas']) &&
        !empty($_POST['fecha_primer_pago']) && !empty($_POST['vendedor_id'])) {
        
        $nombre_completo = mysqli_real_escape_string($conn, trim($_POST['nombre_completo']));
        $telefono = mysqli_real_escape_string($conn, trim($_POST['telefono']));
        $barrio = mysqli_real_escape_string($conn, trim($_POST['barrio']));
        $direccion = mysqli_real_escape_string($conn, trim($_POST['direccion']));
        $articulos = mysqli_real_escape_string($conn, trim($_POST['articulos']));
        $valor_total = floatval($_POST['valor_total']);
        $sena = isset($_POST['sena']) ? floatval($_POST['sena']) : 0;
        $frecuencia_pago = mysqli_real_escape_string($conn, $_POST['frecuencia_pago']);
        $cuotas = intval($_POST['cuotas']);
        $fecha_primer_pago = mysqli_real_escape_string($conn, $_POST['fecha_primer_pago']);
        $vendedor_id = intval($_POST['vendedor_id']);
        
        // Validaciones
        $errors = [];
        
        if (!preg_match('/^[A-Za-zÀ-ÖØ-öø-ÿ\s]+$/u', $nombre_completo)) {
            $errors[] = 'Nombre completo: solo letras y espacios.';
        }
        if (!preg_match('/^\d{10,15}$/', $telefono)) {
            $errors[] = 'Teléfono: debe tener entre 10 y 15 dígitos.';
        }
        if ($valor_total <= 0) {
            $errors[] = 'El valor total debe ser mayor a 0.';
        }
        if ($sena < 0) {
            $errors[] = 'La seña no puede ser negativa.';
        }
        if ($sena > $valor_total) {
            $errors[] = 'La seña no puede ser mayor al valor total.';
        }
        if ($cuotas < 1 || $cuotas > 60) {
            $errors[] = 'Las cuotas deben estar entre 1 y 60.';
        }
        if (!in_array($frecuencia_pago, ['semanal', 'quincenal', 'mensual'])) {
            $errors[] = 'Frecuencia de pago inválida.';
        }
        
        if (!empty($errors)) {
            $_SESSION['message'] = implode(' ', $errors);
            header('Location: index.php');
            exit();
        }
        
        // Insertar cliente
        $stmt = mysqli_prepare($conn, "INSERT INTO clientes (nombre_completo, telefono, barrio, direccion, articulos, valor_total, sena, frecuencia_pago, cuotas, fecha_primer_pago, vendedor_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        if (!$stmt) {
            $_SESSION['message'] = 'Error al preparar consulta: ' . mysqli_error($conn);
            header('Location: index.php');
            exit();
        }
        
        mysqli_stmt_bind_param($stmt, 'sssssddsisi', $nombre_completo, $telefono, $barrio, $direccion, $articulos, $valor_total, $sena, $frecuencia_pago, $cuotas, $fecha_primer_pago, $vendedor_id);
        $resultado = mysqli_stmt_execute($stmt);
        $cliente_id = mysqli_insert_id($conn);
        mysqli_stmt_close($stmt);
        
        if ($resultado) {
            // Calcular saldo restante después de la seña
            $saldo_restante = $valor_total - $sena;
            // Generar las cuotas de pago (distribuir el saldo restante)
            $monto_por_cuota = $saldo_restante / $cuotas;
            $fecha_actual = new DateTime($fecha_primer_pago);
            
            for ($i = 1; $i <= $cuotas; $i++) {
                $fecha_programada = $fecha_actual->format('Y-m-d');
                
                $stmt_pago = mysqli_prepare($conn, "INSERT INTO pagos_clientes (cliente_id, numero_cuota, fecha_programada, monto, estado) VALUES (?, ?, ?, ?, 'pendiente')");
                mysqli_stmt_bind_param($stmt_pago, 'iisd', $cliente_id, $i, $fecha_programada, $monto_por_cuota);
                mysqli_stmt_execute($stmt_pago);
                mysqli_stmt_close($stmt_pago);
                
                // Calcular siguiente fecha según frecuencia
                switch ($frecuencia_pago) {
                    case 'semanal':
                        $fecha_actual->modify('+7 days');
                        break;
                    case 'quincenal':
                        $fecha_actual->modify('+15 days');
                        break;
                    case 'mensual':
                        $fecha_actual->modify('+1 month');
                        break;
                }
            }
            
            // Registrar en auditoría
            if (isset($_SESSION['usuario'])) {
                $stmt_audit = mysqli_prepare($conn, "INSERT INTO auditoria (usuario, accion, tabla, registro_id, detalles) VALUES (?, 'CREAR', 'clientes', ?, ?)");
                $detalles = "Cliente creado: $nombre_completo - Valor: $$valor_total - Cuotas: $cuotas";
                mysqli_stmt_bind_param($stmt_audit, 'sis', $_SESSION['usuario'], $cliente_id, $detalles);
                mysqli_stmt_execute($stmt_audit);
                mysqli_stmt_close($stmt_audit);
            }
            
            $_SESSION['message'] = '✅ Cliente registrado exitosamente con ' . $cuotas . ' cuotas';
        } else {
            $_SESSION['message'] = 'Error al guardar cliente: ' . mysqli_error($conn);
        }
        
        header("Location: index.php");
        exit();
    } else {
        $_SESSION['message'] = 'Por favor, complete todos los campos';
        header("Location: index.php");
        exit();
    }
}

// ANTIGUO: Guardar empleado (mantener por compatibilidad)
if (isset($_POST['guardar-empleado'])) {

    if (!empty($_POST['dni']) && !empty($_POST['nombre']) && !empty($_POST['apellido']) && !empty($_POST['telefono']) && !empty($_POST['departamento_id']) && !empty($_POST['direccion']) && !empty($_POST['localidad_id']) && !empty($_POST['pais_id']) && !empty($_POST['provincia_id'])) {
        $dni = trim($_POST['dni']);
        $nombre = mysqli_real_escape_string($conn, trim($_POST['nombre']));
        $apellido = mysqli_real_escape_string($conn, trim($_POST['apellido']));
        $telefono = trim($_POST['telefono']);
        $departamento_id = intval($_POST['departamento_id']);
        $direccion = mysqli_real_escape_string($conn, trim($_POST['direccion']));
        $localidad_id = intval($_POST['localidad_id']);
        $pais_id = intval($_POST['pais_id']);
        $provincia_id = intval($_POST['provincia_id']);
        // Validaciones servidor
        $errors = [];
        if (!preg_match('/^\d{1,8}$/', $dni)) {
            $errors[] = 'DNI: solo números (máx 8 dígitos).';
        }
        // Nombre/Apellido: letras (incluye acentos) y espacios
        if (!preg_match('/^[A-Za-zÀ-ÖØ-öø-ÿ\s]+$/u', $nombre)) {
            $errors[] = 'Nombre: solo letras.';
        }
        if (!preg_match('/^[A-Za-zÀ-ÖØ-öø-ÿ\s]+$/u', $apellido)) {
            $errors[] = 'Apellido: solo letras.';
        }
        if (!preg_match('/^\d{1,10}$/', $telefono)) {
            $errors[] = 'Teléfono: solo números (máx 10 dígitos).';
        }
        // Dirección: letras con acentos, números y signos comunes . , - / # º ª
        if (!preg_match('/^[A-Za-zÀ-ÖØ-öø-ÿ0-9\s\.,#ºª\-\/]+$/u', $direccion)) {
            $errors[] = 'Dirección: caracteres inválidos. Usa letras, números y . , - / # º ª';
        }
        if ($pais_id <= 0) { $errors[] = 'País inválido.'; }
        if ($provincia_id <= 0) { $errors[] = 'Provincia inválida.'; }
        if ($localidad_id <= 0) { $errors[] = 'Localidad inválida.'; }

        if (!empty($errors)) {
            $_SESSION['message'] = implode(' ', $errors);
            header('Location: index.php');
            exit();
        }

        // Verificar duplicado de DNI
        $check_sql = "SELECT id FROM empleados WHERE dni = '$dni' LIMIT 1";
        $check_res = mysqli_query($conn, $check_sql);
        if ($check_res && mysqli_num_rows($check_res) > 0) {
            $_SESSION['message'] = 'Ya existe un empleado con ese DNI';
            header('Location: index.php');
            exit();
        }

        // Validar existencia básica de claves foráneas (opcional pero recomendable)
        $ok_fk = true;
    $res = mysqli_query($conn, "SELECT id FROM paises WHERE id = $pais_id LIMIT 1");
        if (!$res || mysqli_num_rows($res) === 0) { $ok_fk = false; }
    $res = mysqli_query($conn, "SELECT id FROM provincias WHERE id = $provincia_id AND pais_id = $pais_id LIMIT 1");
        if (!$res || mysqli_num_rows($res) === 0) { $ok_fk = false; }
    $res = mysqli_query($conn, "SELECT id FROM localidades WHERE id = $localidad_id AND provincia_id = $provincia_id LIMIT 1");
        if (!$res || mysqli_num_rows($res) === 0) { $ok_fk = false; }
        if (!$ok_fk) {
            $_SESSION['message'] = 'Selección de ubicación inválida.';
            header('Location: index.php');
            exit();
        }

        // Primero, insertar la ubicación con IDs
        $stmtU = mysqli_prepare($conn, "INSERT INTO ubicaciones (pais_id, provincia_id, localidad_id, direccion) VALUES (?, ?, ?, ?)");
        if (!$stmtU) {
            $_SESSION['message'] = 'Error al preparar ubicación: ' . mysqli_error($conn);
            header('Location: index.php');
            exit();
        }
        mysqli_stmt_bind_param($stmtU, 'iiis', $pais_id, $provincia_id, $localidad_id, $direccion);
        $okU = mysqli_stmt_execute($stmtU);
        if (!$okU) {
            $_SESSION['message'] = 'Error al guardar ubicación: ' . mysqli_error($conn);
            mysqli_stmt_close($stmtU);
            header('Location: index.php');
            exit();
        }
        $ubicacion_id = mysqli_insert_id($conn);
        mysqli_stmt_close($stmtU);

        // Ahora insertar el empleado con los IDs
        $stmtE = mysqli_prepare($conn, "INSERT INTO empleados (dni, nombre, apellido, telefono, departamento_id, ubicacion_id) VALUES (?, ?, ?, ?, ?, ?)");
        if (!$stmtE) {
            $_SESSION['message'] = 'Error al preparar empleado: ' . mysqli_error($conn);
            header('Location: index.php');
            exit();
        }
        mysqli_stmt_bind_param($stmtE, 'ssssii', $dni, $nombre, $apellido, $telefono, $departamento_id, $ubicacion_id);
        $resultado = mysqli_stmt_execute($stmtE);
        mysqli_stmt_close($stmtE);
        
        if (!$resultado) {
            $_SESSION['message'] = 'Error al guardar empleado: ' . mysqli_error($conn);
            header('Location: index.php');
            exit();
        }
        
        $_SESSION['message'] = 'Registro guardado con éxito';
        header("Location: index.php");
        exit();
    } else {
        $_SESSION['message'] = 'Completar todos los datos';
        header("Location: index.php");
        exit();
    }
}
