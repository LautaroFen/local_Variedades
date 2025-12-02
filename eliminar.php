<!--Se hace la conexión a la bd tareas, se recupera el id con $_GET y se realiza la consulta-->

<?php

include("conexion.php");

// PROTECCIÓN: Solo jefes pueden eliminar clientes
if ($_SESSION['tipo_usuario'] != 'jefe') {
    $_SESSION['message'] = '⛔ Acceso denegado. No tienes permisos para eliminar clientes.';
    $_SESSION['message_type'] = 'danger';
    header('Location: index.php');
    exit();
}

if (isset($_GET['id'])) {

    $id = intval($_GET['id']);

    // Obtener datos del cliente antes de eliminar (para auditoría)
    $stmt_info = mysqli_prepare($conn, "SELECT nombre_completo FROM clientes WHERE id=?");
    mysqli_stmt_bind_param($stmt_info, 'i', $id);
    mysqli_stmt_execute($stmt_info);
    $res_info = mysqli_stmt_get_result($stmt_info);
    $cliente_info = mysqli_fetch_assoc($res_info);
    mysqli_stmt_close($stmt_info);

    // Eliminar cliente
    $stmt = mysqli_prepare($conn, "DELETE FROM clientes WHERE id=?");
    mysqli_stmt_bind_param($stmt, 'i', $id);
    $resultado = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    if (!$resultado) {
        die("Error al eliminar el cliente");
    }

    // Registrar en auditoría
    if (isset($_SESSION['usuario']) && $cliente_info) {
        $stmt_audit = mysqli_prepare($conn, "INSERT INTO auditoria (usuario, accion, tabla, registro_id, detalles) VALUES (?, 'ELIMINAR', 'clientes', ?, ?)");
        $detalles = "Cliente eliminado: " . $cliente_info['nombre_completo'];
        mysqli_stmt_bind_param($stmt_audit, 'sis', $_SESSION['usuario'], $id, $detalles);
        mysqli_stmt_execute($stmt_audit);
        mysqli_stmt_close($stmt_audit);
    }

    $_SESSION['message'] = 'Cliente eliminado correctamente';

    header("location: index.php");
}

?>