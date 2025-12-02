
<?php
// Inicia la sesión sólo si no está iniciada (evita el notice)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Conexión a MySQL (sin seleccionar BD primero)
$conn = mysqli_connect('localhost', 'root', '');
if (!$conn) {
    die("Error de conexión: " . mysqli_connect_error());
}

// Intentar seleccionar una BD conocida (en orden de preferencia)
$prevReport = mysqli_report(MYSQLI_REPORT_OFF); // evitar excepciones al seleccionar BD
$dbCandidates = ['local_mv', 'mujeres_virtuosas_db', 'instituto_db', 'crud_instituto_empresa']; // agregar más nombres si es necesario
$selected = false;
foreach ($dbCandidates as $dbName) {
    if (@mysqli_select_db($conn, $dbName)) {
        $selected = true;
        break;
    }
}

if (!$selected) {
    die('No se encontró ninguna de las bases de datos esperadas: ' . implode(', ', $dbCandidates) . '. Importá database.sql en una de ellas o actualiza conexion.php.');
}

// Rehabilitar reporte de errores/exception para el resto de la app
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Juego de caracteres recomendado
mysqli_set_charset($conn, 'utf8mb4');
?>