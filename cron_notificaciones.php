<?php
/**
 * CRON JOB - Notificaciones de Pagos
 * 
 * Este script debe ejecutarse diariamente para enviar recordatorios
 * de pagos próximos (3 días antes de la fecha programada).
 * 
 * Configuración en Windows (Programador de tareas):
 * - Acción: Iniciar programa
 * - Programa: C:\xampp\php\php.exe
 * - Argumentos: -f "C:\xampp\htdocs\crud-instituto-empresa\cron_notificaciones.php"
 * - Frecuencia: Diariamente a las 8:00 AM
 * 
 * Configuración en Linux (crontab):
 * 0 8 * * * /usr/bin/php /var/www/html/crud-instituto-empresa/cron_notificaciones.php
 */

// Evitar ejecución desde navegador (solo línea de comandos)
if (php_sapi_name() != 'cli') {
    die('Este script solo puede ejecutarse desde la línea de comandos');
}

require_once(__DIR__ . '/conexion.php');
require_once(__DIR__ . '/PHPMailer/src/PHPMailer.php');
require_once(__DIR__ . '/PHPMailer/src/SMTP.php');
require_once(__DIR__ . '/PHPMailer/src/Exception.php');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Fecha de referencia (3 días en el futuro)
$fecha_limite = date('Y-m-d', strtotime('+3 days'));

// Buscar pagos pendientes que vencen en 3 días
$query = "SELECT 
    pc.id,
    pc.cliente_id,
    pc.numero_cuota,
    pc.fecha_programada,
    pc.monto,
    c.nombre_completo,
    c.telefono,
    c.barrio
FROM pagos_clientes pc
JOIN clientes c ON pc.cliente_id = c.id
WHERE pc.estado = 'pendiente'
AND pc.fecha_programada = ?
ORDER BY c.nombre_completo";

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, 's', $fecha_limite);
mysqli_stmt_execute($stmt);
$resultado = mysqli_stmt_get_result($stmt);

$notificaciones_enviadas = 0;
$errores = [];

echo "=== CRON JOB: Notificaciones de Pagos ===\n";
echo "Fecha: " . date('Y-m-d H:i:s') . "\n";
echo "Buscando pagos para: $fecha_limite (3 días)\n\n";

while ($pago = mysqli_fetch_assoc($resultado)) {
    echo "Procesando: {$pago['nombre_completo']} - Cuota {$pago['numero_cuota']}\n";
    
    // Configurar PHPMailer
    $mail = new PHPMailer(true);
    
    try {
        // Configuración del servidor SMTP
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'lautaro1524arias@gmail.com'; // Cambiar por tu email
        $mail->Password = 'tu_contraseña_aqui'; // Usar App Password de Gmail
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        $mail->CharSet = 'UTF-8';
        
        // Destinatarios
        $mail->setFrom('lautaro1524arias@gmail.com', 'Mujeres Virtuosas S.A');
        $mail->addAddress('lautaro1524arias@gmail.com'); // Email del administrador
        
        // Contenido del email
        $mail->isHTML(true);
        $mail->Subject = '🔔 Recordatorio: Pago Próximo - ' . $pago['nombre_completo'];
        
        $mail->Body = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(to right, #3a8efc, #a84de4); color: white; padding: 20px; text-align: center; border-radius: 10px 10px 0 0; }
                .content { background: #f9f9f9; padding: 20px; border-radius: 0 0 10px 10px; }
                .info-box { background: white; padding: 15px; margin: 10px 0; border-left: 4px solid #3a8efc; }
                .alert { background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 10px 0; }
                .footer { text-align: center; margin-top: 20px; font-size: 12px; color: #666; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>🔔 Recordatorio de Pago</h1>
                </div>
                <div class='content'>
                    <div class='alert'>
                        <strong>⏰ Pago próximo en 3 días</strong>
                    </div>
                    
                    <div class='info-box'>
                        <h3>📋 Información del Cliente</h3>
                        <p><strong>Nombre:</strong> {$pago['nombre_completo']}</p>
                        <p><strong>Teléfono:</strong> {$pago['telefono']}</p>
                        <p><strong>Barrio:</strong> {$pago['barrio']}</p>
                    </div>
                    
                    <div class='info-box'>
                        <h3>💰 Detalles del Pago</h3>
                        <p><strong>Cuota:</strong> #{$pago['numero_cuota']}</p>
                        <p><strong>Monto:</strong> $" . number_format($pago['monto'], 2, ',', '.') . "</p>
                        <p><strong>Fecha programada:</strong> " . date('d/m/Y', strtotime($pago['fecha_programada'])) . "</p>
                    </div>
                    
                    <p style='margin-top: 20px;'>
                        <strong>👉 Acción requerida:</strong><br>
                        Por favor, contactar al cliente para recordarle sobre este pago próximo.
                    </p>
                </div>
                <div class='footer'>
                    <p>Este es un mensaje automático del sistema Mujeres Virtuosas S.A</p>
                    <p>No responder a este email</p>
                </div>
            </div>
        </body>
        </html>
        ";
        
        $mail->AltBody = "Recordatorio de Pago\n\n" .
                        "Cliente: {$pago['nombre_completo']}\n" .
                        "Teléfono: {$pago['telefono']}\n" .
                        "Cuota: #{$pago['numero_cuota']}\n" .
                        "Monto: $" . number_format($pago['monto'], 2, ',', '.') . "\n" .
                        "Fecha: " . date('d/m/Y', strtotime($pago['fecha_programada']));
        
        $mail->send();
        $notificaciones_enviadas++;
        echo "  ✅ Email enviado correctamente\n";
        
    } catch (Exception $e) {
        $error_msg = "Error al enviar email: {$mail->ErrorInfo}";
        $errores[] = $error_msg;
        echo "  ❌ $error_msg\n";
    }
}

// Resumen
echo "\n=== RESUMEN ===\n";
echo "Total de notificaciones enviadas: $notificaciones_enviadas\n";
echo "Errores: " . count($errores) . "\n";

if (count($errores) > 0) {
    echo "\nDetalle de errores:\n";
    foreach ($errores as $error) {
        echo "- $error\n";
    }
}

// Registrar en auditoría
$stmt_audit = mysqli_prepare($conn, "INSERT INTO auditoria (usuario, accion, tabla, registro_id, detalles) VALUES ('SISTEMA', 'CRON_NOTIFICACIONES', 'pagos_clientes', 0, ?)");
$detalles_audit = "Notificaciones enviadas: $notificaciones_enviadas | Errores: " . count($errores);
mysqli_stmt_bind_param($stmt_audit, 's', $detalles_audit);
mysqli_stmt_execute($stmt_audit);
mysqli_stmt_close($stmt_audit);

mysqli_stmt_close($stmt);
mysqli_close($conn);

echo "\n✅ Proceso finalizado\n";
?>
