<?php
/**
 * CRON JOB - Envío de Notificaciones SOLO de Pagos Atrasados
 * 
 * Este script envía correos diarios únicamente con pagos atrasados.
 * Las notificaciones de pagos finalizados ahora se envían automáticamente
 * al momento de registrar el último pago.
 * 
 * CONFIGURACIÓN:
 * 
 * Windows (Programador de tareas):
 * - Programa: C:\xampp\php\php.exe
 * - Argumentos: -f "C:\xampp\htdocs\Local_MV\enviar_notificacion_atrasados.php"
 * - Frecuencia: Diariamente a las 8:00 AM
 */

require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/notificaciones_email.php';
require_once __DIR__ . '/email_config.php';

$es_cli = (php_sapi_name() === 'cli');

if (!$es_cli) {
    echo '<!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <title>Notificación de Pagos Atrasados</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
        <style>
            body { padding: 2rem; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; }
            .container { background: white; padding: 2rem; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.3); max-width: 800px; }
            .log-entry { padding: 10px; margin: 5px 0; border-left: 4px solid #0d6efd; background: #f8f9fa; border-radius: 4px; }
            .success { border-left-color: #198754; }
            .error { border-left-color: #dc3545; }
            .warning { border-left-color: #ffc107; }
        </style>
    </head>
    <body>
        <div class="container">
            <h1 class="mb-4">🚨 Notificación de Pagos Atrasados</h1>
            <hr>';
}

$email_jefe = EMAIL_TO;

echo $es_cli ? "=== NOTIFICACIÓN DE PAGOS ATRASADOS ===\n" : "<h3>Iniciando proceso...</h3>";
echo $es_cli ? "Fecha: " . date('Y-m-d H:i:s') . "\n" : "<p><strong>Fecha:</strong> " . date('d/m/Y H:i:s') . "</p>";
echo $es_cli ? "Destinatario: $email_jefe\n\n" : "<p><strong>Destinatario:</strong> $email_jefe</p><hr>";

// Buscar clientes con pagos atrasados
echo $es_cli ? "📋 Buscando clientes con pagos atrasados...\n" : "<div class='log-entry'><strong>📋 Buscando clientes con pagos atrasados...</strong></div>";

$query_atrasados = "SELECT DISTINCT
    c.id,
    c.nombre_completo,
    c.telefono,
    c.barrio,
    COUNT(pc.id) as pagos_atrasados,
    SUM(pc.monto) as monto_total_atrasado,
    MIN(pc.fecha_programada) as fecha_mas_antigua
FROM clientes c
JOIN pagos_clientes pc ON c.id = pc.cliente_id
WHERE pc.estado = 'pendiente' AND pc.fecha_programada < CURDATE()
GROUP BY c.id
ORDER BY fecha_mas_antigua ASC";

$resultado_atrasados = mysqli_query($conn, $query_atrasados);
$clientes_atrasados = [];

if ($resultado_atrasados) {
    while ($row = mysqli_fetch_assoc($resultado_atrasados)) {
        $clientes_atrasados[] = $row;
    }
}

$total_atrasados = count($clientes_atrasados);
echo $es_cli ? "✓ Encontrados: $total_atrasados cliente(s)\n\n" : "<div class='log-entry success'>✓ Encontrados: <strong>$total_atrasados</strong> cliente(s) con pagos atrasados</div>";

// Enviar notificación si hay pagos atrasados
if ($total_atrasados > 0) {
    echo $es_cli ? "📧 Enviando notificación de pagos atrasados...\n" : "<div class='log-entry warning'><strong>📧 Enviando notificación de pagos atrasados...</strong></div>";
    
    if (enviarNotificacionPagosAtrasados($clientes_atrasados, $email_jefe)) {
        echo $es_cli ? "✓ Correo enviado correctamente\n\n" : "<div class='log-entry success'>✓ Correo enviado correctamente a <strong>$email_jefe</strong></div>";
    } else {
        echo $es_cli ? "✗ Error al enviar correo\n\n" : "<div class='log-entry error'>✗ Error al enviar correo</div>";
    }
} else {
    echo $es_cli ? "ℹ No hay pagos atrasados\n\n" : "<div class='log-entry'>ℹ No hay pagos atrasados para notificar</div>";
}

echo $es_cli ? "=== FIN ===\n" : "<hr><p class='text-center'><strong>✓ Proceso completado</strong></p>";

if (!$es_cli) {
    echo '
            <div class="mt-4 text-center">
                <a href="dashboard.php" class="btn btn-primary">Ir al Dashboard</a>
                <a href="index.php" class="btn btn-secondary">Ver Clientes</a>
            </div>
        </div>
    </body>
    </html>';
}

mysqli_close($conn);
?>
