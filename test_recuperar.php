<?php
// Test de recuperación de contraseña
include("conexion.php");

echo "<h2>Test de Recuperación de Contraseña</h2>";

// 1. Verificar sesión
echo "<h3>1. Estado de Sesión:</h3>";
echo "Session ID: " . session_id() . "<br>";
echo "Estado sesión: " . (session_status() === PHP_SESSION_ACTIVE ? "ACTIVA" : "INACTIVA") . "<br>";
echo "Paso actual: " . (isset($_SESSION['paso_recuperacion']) ? $_SESSION['paso_recuperacion'] : "No definido") . "<br>";

// 2. Verificar POST
echo "<h3>2. Datos POST:</h3>";
echo "Método: " . $_SERVER['REQUEST_METHOD'] . "<br>";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<pre>";
    print_r($_POST);
    echo "</pre>";
} else {
    echo "No hay datos POST<br>";
}

// 3. Verificar tabla password_reset_tokens
echo "<h3>3. Últimos tokens generados:</h3>";
$query = "SELECT id, usuario_id, token, expiracion, usado, fecha_creacion FROM password_reset_tokens ORDER BY fecha_creacion DESC LIMIT 5";
$result = mysqli_query($conn, $query);
if ($result) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Usuario ID</th><th>Token</th><th>Expiración</th><th>Usado</th><th>Fecha Creación</th></tr>";
    while ($row = mysqli_fetch_assoc($result)) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . $row['usuario_id'] . "</td>";
        echo "<td>" . $row['token'] . "</td>";
        echo "<td>" . $row['expiracion'] . "</td>";
        echo "<td>" . ($row['usado'] ? 'Sí' : 'No') . "</td>";
        echo "<td>" . $row['fecha_creacion'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "Error: " . mysqli_error($conn);
}

// 4. Formulario de prueba
echo "<h3>4. Formulario de Prueba:</h3>";
?>
<form method="post" action="test_recuperar.php">
    <button type="submit" name="solicitar" value="1">Solicitar Código (Test)</button>
</form>

<hr>
<a href="recuperar_contrasena.php">Ir a recuperar_contrasena.php</a>

<?php
// 5. Procesar solicitud de prueba
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['solicitar'])) {
    echo "<h3>5. Procesando solicitud...</h3>";
    
    $codigo = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
    $expiracion = date('Y-m-d H:i:s', strtotime('+1 hour'));
    
    $stmtToken = mysqli_prepare($conn, 'INSERT INTO password_reset_tokens (usuario_id, token, expiracion) VALUES (0, ?, ?)');
    if ($stmtToken) {
        mysqli_stmt_bind_param($stmtToken, 'ss', $codigo, $expiracion);
        if (mysqli_stmt_execute($stmtToken)) {
            echo "✅ Código generado: <strong>$codigo</strong><br>";
            echo "Expira: $expiracion<br>";
            
            // Cambiar paso
            $_SESSION['paso_recuperacion'] = 2;
            echo "✅ Paso actualizado a: " . $_SESSION['paso_recuperacion'] . "<br>";
            echo "<br><a href='test_recuperar.php'>Recargar para ver sesión actualizada</a>";
        } else {
            echo "❌ Error al insertar: " . mysqli_error($conn);
        }
        mysqli_stmt_close($stmtToken);
    } else {
        echo "❌ Error al preparar: " . mysqli_error($conn);
    }
}
?>
