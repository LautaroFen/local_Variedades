<?php
include("conexion.php");

if (!isset($_SESSION['usuario'])) {
    header('Location: login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['pago_id']) && isset($_POST['cliente_id']) && isset($_POST['nueva_fecha'])) {
    $pago_id = intval($_POST['pago_id']);
    $cliente_id = intval($_POST['cliente_id']);
    $nueva_fecha = mysqli_real_escape_string($conn, $_POST['nueva_fecha']);

    $tab = isset($_POST['tab']) ? strtolower((string)$_POST['tab']) : '';
    if (!in_array($tab, ['pendientes', 'atrasados', 'finalizados'], true)) {
        $tab = '';
    }
    $tab_qs = ($tab !== '') ? ('&tab=' . urlencode($tab)) : '';
    
    // Validar formato de fecha
    $fecha_obj = DateTime::createFromFormat('Y-m-d', $nueva_fecha);
    if (!$fecha_obj || $fecha_obj->format('Y-m-d') !== $nueva_fecha) {
        $_SESSION['message'] = 'Fecha inválida';
        header("Location: editar.php?id=" . $cliente_id . $tab_qs);
        exit();
    }
    
    // Actualizar la fecha programada
    $query = "UPDATE pagos_clientes SET fecha_programada = ? WHERE id = ? AND cliente_id = ?";
    $stmt = mysqli_prepare($conn, $query);
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'sii', $nueva_fecha, $pago_id, $cliente_id);
        $resultado = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        
        if ($resultado) {
            $_SESSION['message'] = '✅ Fecha de pago actualizada correctamente';
        } else {
            $_SESSION['message'] = 'Error al actualizar la fecha';
        }
    } else {
        $_SESSION['message'] = 'Error al preparar la consulta';
    }
    
    header("Location: editar.php?id=" . $cliente_id . $tab_qs);
    exit();
} else {
    header("Location: index.php");
    exit();
}
?>
