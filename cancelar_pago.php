<?php
include("conexion.php");

if (!isset($_SESSION['usuario'])) {
    header('Location: login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['pago_id']) && isset($_POST['cliente_id'])) {
    $pago_id = intval($_POST['pago_id']);
    $cliente_id = intval($_POST['cliente_id']);

    $tab = isset($_POST['tab']) ? strtolower((string)$_POST['tab']) : '';
    if (!in_array($tab, ['pendientes', 'atrasados', 'finalizados'], true)) {
        $tab = '';
    }
    $tab_qs = ($tab !== '') ? ('&tab=' . urlencode($tab)) : '';
    
    // Actualizar el pago: cambiar estado a 'pendiente' y limpiar fecha_pago
    $query = "UPDATE pagos_clientes SET estado = 'pendiente', fecha_pago = NULL WHERE id = ? AND cliente_id = ?";
    $stmt = mysqli_prepare($conn, $query);
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'ii', $pago_id, $cliente_id);
        $resultado = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        
        if ($resultado) {
            $_SESSION['message'] = '✅ Pago cancelado correctamente. La cuota vuelve a estar pendiente';
        } else {
            $_SESSION['message'] = 'Error al cancelar el pago';
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
