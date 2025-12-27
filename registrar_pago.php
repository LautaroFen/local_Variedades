<?php
include("conexion.php");

if (!isset($_SESSION['usuario'])) {
    header('Location: login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['pago_id']) && isset($_POST['cliente_id'])) {
    $pago_id = intval($_POST['pago_id']);
    $cliente_id = intval($_POST['cliente_id']);
    
    // Actualizar el pago a estado "pagado" con la fecha actual
    $query = "UPDATE pagos_clientes SET estado = 'pagado', fecha_pago = CURDATE() WHERE id = ? AND cliente_id = ?";
    $stmt = mysqli_prepare($conn, $query);
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'ii', $pago_id, $cliente_id);
        $resultado = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        
        if ($resultado) {
            $_SESSION['message'] = '✅ Pago registrado correctamente';
            
            // Notificaciones por email: solo atrasados (se envía por cron/script dedicado)
        } else {
            $_SESSION['message'] = 'Error al registrar el pago';
        }
    } else {
        $_SESSION['message'] = 'Error al preparar la consulta';
    }
    
    header("Location: ver.php?id=" . $cliente_id);
    exit();
} else {
    header("Location: index.php");
    exit();
}
?>
