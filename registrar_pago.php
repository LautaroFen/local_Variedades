<?php
include("conexion.php");
require_once __DIR__ . '/notificaciones_email.php';
require_once __DIR__ . '/email_config.php';

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
            
            // 🔔 VERIFICAR SI EL CLIENTE FINALIZÓ TODOS SUS PAGOS
            $query_verificar = "SELECT COUNT(*) as pendientes FROM pagos_clientes WHERE cliente_id = ? AND estado = 'pendiente'";
            $stmt_verificar = mysqli_prepare($conn, $query_verificar);
            mysqli_stmt_bind_param($stmt_verificar, 'i', $cliente_id);
            mysqli_stmt_execute($stmt_verificar);
            $result_verificar = mysqli_stmt_get_result($stmt_verificar);
            $row = mysqli_fetch_assoc($result_verificar);
            mysqli_stmt_close($stmt_verificar);
            
            // Si no hay pagos pendientes, el cliente finalizó
            if ($row['pendientes'] == 0) {
                // Obtener datos del cliente
                $query_cliente = "SELECT nombre_completo, telefono, barrio, valor_total FROM clientes WHERE id = ?";
                $stmt_cliente = mysqli_prepare($conn, $query_cliente);
                mysqli_stmt_bind_param($stmt_cliente, 'i', $cliente_id);
                mysqli_stmt_execute($stmt_cliente);
                $result_cliente = mysqli_stmt_get_result($stmt_cliente);
                $cliente = mysqli_fetch_assoc($result_cliente);
                mysqli_stmt_close($stmt_cliente);
                
                if ($cliente) {
                    // Preparar datos para el correo
                    $cliente['id'] = $cliente_id;
                    $cliente['fecha_ultimo_pago'] = date('Y-m-d');
                    
                    // Enviar notificación inmediata
                    $enviado = enviarNotificacionPagosFinalizados([$cliente], EMAIL_TO);
                    
                    if ($enviado) {
                        $_SESSION['message'] .= ' 🎉 ¡Cliente finalizado! Se envió notificación por email.';
                    }
                }
            }
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
