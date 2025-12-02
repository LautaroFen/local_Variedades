<?php
/**
 * CRON JOB - Envío de Notificaciones Diarias
 * 
 * Este script envía correos electrónicos diarios con:
 * 1. Clientes con pagos atrasados
 * 2. Clientes que finalizaron sus pagos (últimos 7 días)
 * 
 * CONFIGURACIÓN:
 * 
 * Windows (Programador de tareas):
 * - Acción: Iniciar programa
 * - Programa: C:\xampp\php\php.exe
 * - Argumentos: -f "C:\xampp\htdocs\Local_MV\enviar_notificaciones_diarias.php"
 * - Frecuencia: Diariamente a las 8:00 AM
 * 
 * Linux (crontab):
 * 0 8 * * * /usr/bin/php /var/www/html/Local_MV/enviar_notificaciones_diarias.php
 * 
 * EJECUCIÓN MANUAL (desde navegador):
 * http://localhost/Local_MV/enviar_notificaciones_diarias.php
 */

require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/notificaciones_email.php';
require_once __DIR__ . '/email_config.php';

// Permitir ejecución desde CLI o navegador
$es_cli = (php_sapi_name() === 'cli');

if (!$es_cli) {
    // Si se ejecuta desde navegador, mostrar HTML
    echo '<!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Envío de Notificaciones</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
        <style>
            body {
                padding: 2rem;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                min-height: 100vh;
            }
            .container {
                background: white;
                padding: 2rem;
                border-radius: 12px;
                box-shadow: 0 10px 30px rgba(0,0,0,0.3);
                max-width: 900px;
            }
            .log-entry {
                padding: 10px;
                margin: 5px 0;
                border-left: 4px solid #0d6efd;
                background: #f8f9fa;
                border-radius: 4px;
            }
            .success { border-left-color: #198754; }
            .error { border-left-color: #dc3545; }
            .warning { border-left-color: #ffc107; }
        </style>
    </head>
    <body>
        <div class="container">
            <h1 class="mb-4">📧 Sistema de Notificaciones por Email</h1>
            <hr>';
}

// Email del destinatario (jefe)
$email_jefe = EMAIL_TO; // Definido en email_config.php

echo $es_cli ? "=== ENVÍO DE NOTIFICACIONES DIARIAS ===\n" : "<h3>Iniciando proceso...</h3>";
echo $es_cli ? "Fecha: " . date('Y-m-d H:i:s') . "\n" : "<p><strong>Fecha:</strong> " . date('d/m/Y H:i:s') . "</p>";
echo $es_cli ? "Destinatario: $email_jefe\n\n" : "<p><strong>Destinatario:</strong> $email_jefe</p><hr>";

// ====================================
// 1. BUSCAR CLIENTES CON PAGOS ATRASADOS
// ====================================
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

// ====================================
// 2. BUSCAR CLIENTES QUE FINALIZARON (ÚLTIMOS 7 DÍAS)
// ====================================
echo $es_cli ? "📋 Buscando clientes que finalizaron sus pagos (últimos 7 días)...\n" : "<div class='log-entry'><strong>📋 Buscando clientes que finalizaron sus pagos (últimos 7 días)...</strong></div>";

$query_finalizados = "SELECT 
    c.id,
    c.nombre_completo,
    c.telefono,
    c.barrio,
    c.valor_total,
    MAX(pc.fecha_pago) as fecha_ultimo_pago
FROM clientes c
JOIN pagos_clientes pc ON c.id = pc.cliente_id
WHERE NOT EXISTS (
    SELECT 1 FROM pagos_clientes pc2 
    WHERE pc2.cliente_id = c.id AND pc2.estado = 'pendiente'
)
GROUP BY c.id
HAVING MAX(pc.fecha_pago) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
ORDER BY fecha_ultimo_pago DESC";

$resultado_finalizados = mysqli_query($conn, $query_finalizados);
$clientes_finalizados = [];

if ($resultado_finalizados) {
    while ($row = mysqli_fetch_assoc($resultado_finalizados)) {
        $clientes_finalizados[] = $row;
    }
}

$total_finalizados = count($clientes_finalizados);
echo $es_cli ? "✓ Encontrados: $total_finalizados cliente(s)\n\n" : "<div class='log-entry success'>✓ Encontrados: <strong>$total_finalizados</strong> cliente(s) que finalizaron</div>";

// ====================================
// 3. ENVIAR NOTIFICACIONES
// ====================================
$notificaciones_enviadas = 0;

// Enviar notificación de pagos atrasados (si hay)
if ($total_atrasados > 0) {
    echo $es_cli ? "📧 Enviando notificación de pagos atrasados...\n" : "<div class='log-entry warning'><strong>📧 Enviando notificación de pagos atrasados...</strong></div>";
    
    if (enviarNotificacionPagosAtrasados($clientes_atrasados, $email_jefe)) {
        $notificaciones_enviadas++;
        echo $es_cli ? "✓ Correo de pagos atrasados enviado correctamente\n\n" : "<div class='log-entry success'>✓ Correo de pagos atrasados enviado correctamente a <strong>$email_jefe</strong></div>";
    } else {
        echo $es_cli ? "✗ Error al enviar correo de pagos atrasados\n\n" : "<div class='log-entry error'>✗ Error al enviar correo de pagos atrasados</div>";
    }
} else {
    echo $es_cli ? "ℹ No hay pagos atrasados, no se envía notificación\n\n" : "<div class='log-entry'>ℹ No hay pagos atrasados para notificar</div>";
}

// Enviar notificación de pagos finalizados (si hay)
if ($total_finalizados > 0) {
    echo $es_cli ? "📧 Enviando notificación de pagos finalizados...\n" : "<div class='log-entry success'><strong>📧 Enviando notificación de pagos finalizados...</strong></div>";
    
    if (enviarNotificacionPagosFinalizados($clientes_finalizados, $email_jefe)) {
        $notificaciones_enviadas++;
        echo $es_cli ? "✓ Correo de pagos finalizados enviado correctamente\n\n" : "<div class='log-entry success'>✓ Correo de pagos finalizados enviado correctamente a <strong>$email_jefe</strong></div>";
    } else {
        echo $es_cli ? "✗ Error al enviar correo de pagos finalizados\n\n" : "<div class='log-entry error'>✗ Error al enviar correo de pagos finalizados</div>";
    }
} else {
    echo $es_cli ? "ℹ No hay pagos finalizados recientes, no se envía notificación\n\n" : "<div class='log-entry'>ℹ No hay pagos finalizados recientes para notificar</div>";
}

// ====================================
// 4. RESUMEN
// ====================================
echo $es_cli ? "=== RESUMEN ===\n" : "<hr><h3>📊 Resumen</h3>";
echo $es_cli ? "Total de notificaciones enviadas: $notificaciones_enviadas\n" : "<div class='log-entry'><strong>Total de notificaciones enviadas:</strong> $notificaciones_enviadas</div>";
echo $es_cli ? "Clientes con pagos atrasados: $total_atrasados\n" : "<div class='log-entry warning'><strong>Clientes con pagos atrasados:</strong> $total_atrasados</div>";
echo $es_cli ? "Clientes que finalizaron: $total_finalizados\n" : "<div class='log-entry success'><strong>Clientes que finalizaron:</strong> $total_finalizados</div>";
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
