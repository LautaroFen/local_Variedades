<?php
/**
 * Sistema de Notificaciones por Email
 * Envía alertas sobre pagos atrasados y finalizados
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/PHPMailer/src/Exception.php';
require_once __DIR__ . '/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/src/SMTP.php';
require_once __DIR__ . '/email_config.php';

/**
 * Envía notificación de pagos atrasados
 * 
 * @param array $clientesAtrasados Array con los datos de clientes con pagos atrasados
 * @param string $emailDestino Email del destinatario
 * @return bool True si se envió correctamente
 */
function enviarNotificacionPagosAtrasados($clientesAtrasados, $emailDestino) {
    $mail = new PHPMailer(true);
    
    try {
        // Configuración del servidor SMTP
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USERNAME;
        $mail->Password   = SMTP_PASSWORD;
        $mail->SMTPSecure = SMTP_SECURE;
        $mail->Port       = SMTP_PORT;
        $mail->CharSet    = 'UTF-8';
        
        // Remitente y destinatario
        $mail->setFrom(EMAIL_FROM, EMAIL_FROM_NAME);
        $mail->addAddress($emailDestino);
        
        // Contenido
        $mail->isHTML(true);
        $mail->Subject = '🚨 ALERTA: ' . count($clientesAtrasados) . ' Cliente(s) con Pagos Atrasados';
        
        // Generar HTML del correo
        $html = generarHTMLPagosAtrasados($clientesAtrasados);
        $mail->Body = $html;
        
        // Enviar
        $mail->send();
        return true;
        
    } catch (Exception $e) {
        error_log("Error al enviar notificación de pagos atrasados: {$mail->ErrorInfo}");
        return false;
    }
}

/**
 * Envía notificación de pagos finalizados
 * 
 * @param array $clientesFinalizados Array con los datos de clientes que finalizaron
 * @param string $emailDestino Email del destinatario
 * @return bool True si se envió correctamente
 */
function enviarNotificacionPagosFinalizados($clientesFinalizados, $emailDestino) {
    $mail = new PHPMailer(true);
    
    try {
        // Configuración del servidor SMTP
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USERNAME;
        $mail->Password   = SMTP_PASSWORD;
        $mail->SMTPSecure = SMTP_SECURE;
        $mail->Port       = SMTP_PORT;
        $mail->CharSet    = 'UTF-8';
        
        // Remitente y destinatario
        $mail->setFrom(EMAIL_FROM, EMAIL_FROM_NAME);
        $mail->addAddress($emailDestino);
        
        // Contenido
        $mail->isHTML(true);
        $mail->Subject = '🎉 ¡Éxito! ' . count($clientesFinalizados) . ' Cliente(s) Finalizaron sus Pagos';
        
        // Generar HTML del correo
        $html = generarHTMLPagosFinalizados($clientesFinalizados);
        $mail->Body = $html;
        
        // Enviar
        $mail->send();
        return true;
        
    } catch (Exception $e) {
        error_log("Error al enviar notificación de pagos finalizados: {$mail->ErrorInfo}");
        return false;
    }
}

/**
 * Genera el HTML para el correo de pagos atrasados
 */
function generarHTMLPagosAtrasados($clientes) {
    $totalClientes = count($clientes);
    $totalDeuda = array_sum(array_column($clientes, 'monto_total_atrasado'));
    
    $filas = '';
    foreach ($clientes as $cliente) {
        $diasAtraso = round((strtotime('today') - strtotime($cliente['fecha_mas_antigua'])) / 86400);
        $filas .= '
        <tr style="border-bottom: 1px solid #e5e7eb;">
            <td style="padding: 15px;">
                <strong>' . htmlspecialchars($cliente['nombre_completo']) . '</strong><br>
                <small style="color: #666;">📞 ' . htmlspecialchars($cliente['telefono']) . '</small><br>
                <small style="color: #666;">📍 ' . htmlspecialchars($cliente['barrio']) . '</small>
            </td>
            <td style="padding: 15px; text-align: center;">
                <span style="background: #fecaca; color: #991b1b; padding: 5px 10px; border-radius: 5px; font-weight: bold;">
                    ' . $cliente['pagos_atrasados'] . ' pago(s)
                </span>
            </td>
            <td style="padding: 15px; text-align: center;">
                <span style="background: #fef3c7; color: #92400e; padding: 5px 10px; border-radius: 5px;">
                    ' . $diasAtraso . ' días
                </span>
            </td>
            <td style="padding: 15px; text-align: right;">
                <strong style="color: #dc2626; font-size: 18px;">$' . number_format($cliente['monto_total_atrasado'], 0, ',', '.') . '</strong>
            </td>
        </tr>';
    }
    
    return '
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <style>
            body { 
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif;
                line-height: 1.6; 
                color: #333;
                margin: 0;
                padding: 0;
                background-color: #f3f4f6;
            }
            .container { 
                max-width: 700px; 
                margin: 20px auto; 
                background: white;
                border-radius: 12px;
                overflow: hidden;
                box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            }
            .header { 
                background: linear-gradient(135deg, #dc2626 0%, #991b1b 100%);
                color: white; 
                padding: 30px 20px; 
                text-align: center;
            }
            .header h1 {
                margin: 0;
                font-size: 28px;
            }
            .header p {
                margin: 10px 0 0 0;
                opacity: 0.9;
            }
            .stats {
                display: flex;
                background: #fef2f2;
                padding: 20px;
                border-bottom: 3px solid #dc2626;
            }
            .stat-box {
                flex: 1;
                text-align: center;
                padding: 10px;
            }
            .stat-box .number {
                font-size: 32px;
                font-weight: bold;
                color: #dc2626;
            }
            .stat-box .label {
                font-size: 14px;
                color: #666;
                margin-top: 5px;
            }
            .content { 
                padding: 30px 20px;
            }
            .alert-box {
                background: #fef2f2;
                border-left: 4px solid #dc2626;
                padding: 15px;
                margin-bottom: 20px;
                border-radius: 4px;
            }
            table {
                width: 100%;
                border-collapse: collapse;
                margin: 20px 0;
            }
            th {
                background: #f9fafb;
                padding: 12px;
                text-align: left;
                font-weight: 600;
                color: #374151;
                border-bottom: 2px solid #e5e7eb;
            }
            .footer { 
                text-align: center; 
                padding: 20px;
                background: #f9fafb;
                border-top: 1px solid #e5e7eb;
                font-size: 13px;
                color: #666;
            }
            .button {
                display: inline-block;
                background: #dc2626;
                color: white;
                padding: 12px 30px;
                text-decoration: none;
                border-radius: 6px;
                font-weight: 600;
                margin: 15px 0;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>🚨 ALERTA: Pagos Atrasados</h1>
                <p>Reporte de clientes con pagos vencidos</p>
            </div>
            
            <div class="stats">
                <div class="stat-box">
                    <div class="number">' . $totalClientes . '</div>
                    <div class="label">Cliente(s) Atrasados</div>
                </div>
                <div class="stat-box">
                    <div class="number">$' . number_format($totalDeuda, 0, ',', '.') . '</div>
                    <div class="label">Total Adeudado</div>
                </div>
            </div>
            
            <div class="content">
                <div class="alert-box">
                    <strong>⚠️ Acción Requerida:</strong> Los siguientes clientes tienen pagos atrasados que requieren seguimiento inmediato.
                </div>
                
                <table>
                    <thead>
                        <tr>
                            <th>Cliente</th>
                            <th style="text-align: center;">Pagos Atrasados</th>
                            <th style="text-align: center;">Días Atraso</th>
                            <th style="text-align: right;">Monto</th>
                        </tr>
                    </thead>
                    <tbody>
                        ' . $filas . '
                    </tbody>
                </table>
                
                <div style="text-align: center; margin-top: 30px;">
                    <p><strong>📅 Fecha del reporte:</strong> ' . date('d/m/Y H:i') . '</p>
                </div>
            </div>
            
            <div class="footer">
                <p><strong>Mujeres Virtuosas S.A</strong></p>
                <p>Sistema de Gestión de Créditos</p>
                <p style="margin-top: 10px; font-size: 11px;">Este es un correo automático generado por el sistema.</p>
            </div>
        </div>
    </body>
    </html>
    ';
}

/**
 * Genera el HTML para el correo de pagos finalizados
 */
function generarHTMLPagosFinalizados($clientes) {
    $totalClientes = count($clientes);
    $totalCobrado = array_sum(array_column($clientes, 'valor_total'));
    
    $filas = '';
    foreach ($clientes as $cliente) {
        $diasDesde = round((strtotime('today') - strtotime($cliente['fecha_ultimo_pago'])) / 86400);
        $filas .= '
        <tr style="border-bottom: 1px solid #e5e7eb;">
            <td style="padding: 15px;">
                <strong>' . htmlspecialchars($cliente['nombre_completo']) . '</strong><br>
                <small style="color: #666;">📞 ' . htmlspecialchars($cliente['telefono']) . '</small><br>
                <small style="color: #666;">📍 ' . htmlspecialchars($cliente['barrio']) . '</small>
            </td>
            <td style="padding: 15px; text-align: center;">
                <span style="background: #d1fae5; color: #065f46; padding: 5px 10px; border-radius: 5px;">
                    Hace ' . $diasDesde . ' día(s)
                </span>
            </td>
            <td style="padding: 15px; text-align: right;">
                <strong style="color: #059669; font-size: 18px;">$' . number_format($cliente['valor_total'], 0, ',', '.') . '</strong>
            </td>
        </tr>';
    }
    
    return '
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <style>
            body { 
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif;
                line-height: 1.6; 
                color: #333;
                margin: 0;
                padding: 0;
                background-color: #f3f4f6;
            }
            .container { 
                max-width: 700px; 
                margin: 20px auto; 
                background: white;
                border-radius: 12px;
                overflow: hidden;
                box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            }
            .header { 
                background: linear-gradient(135deg, #059669 0%, #047857 100%);
                color: white; 
                padding: 30px 20px; 
                text-align: center;
            }
            .header h1 {
                margin: 0;
                font-size: 28px;
            }
            .header p {
                margin: 10px 0 0 0;
                opacity: 0.9;
            }
            .stats {
                display: flex;
                background: #f0fdf4;
                padding: 20px;
                border-bottom: 3px solid #059669;
            }
            .stat-box {
                flex: 1;
                text-align: center;
                padding: 10px;
            }
            .stat-box .number {
                font-size: 32px;
                font-weight: bold;
                color: #059669;
            }
            .stat-box .label {
                font-size: 14px;
                color: #666;
                margin-top: 5px;
            }
            .content { 
                padding: 30px 20px;
            }
            .success-box {
                background: #f0fdf4;
                border-left: 4px solid #059669;
                padding: 15px;
                margin-bottom: 20px;
                border-radius: 4px;
            }
            table {
                width: 100%;
                border-collapse: collapse;
                margin: 20px 0;
            }
            th {
                background: #f9fafb;
                padding: 12px;
                text-align: left;
                font-weight: 600;
                color: #374151;
                border-bottom: 2px solid #e5e7eb;
            }
            .footer { 
                text-align: center; 
                padding: 20px;
                background: #f9fafb;
                border-top: 1px solid #e5e7eb;
                font-size: 13px;
                color: #666;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>🎉 ¡Pagos Finalizados!</h1>
                <p>Clientes que completaron sus pagos exitosamente</p>
            </div>
            
            <div class="stats">
                <div class="stat-box">
                    <div class="number">' . $totalClientes . '</div>
                    <div class="label">Cliente(s) Finalizados</div>
                </div>
                <div class="stat-box">
                    <div class="number">$' . number_format($totalCobrado, 0, ',', '.') . '</div>
                    <div class="label">Total Cobrado</div>
                </div>
            </div>
            
            <div class="content">
                <div class="success-box">
                    <strong>✅ ¡Excelente trabajo!</strong> Los siguientes clientes han completado todos sus pagos en los últimos 7 días.
                </div>
                
                <table>
                    <thead>
                        <tr>
                            <th>Cliente</th>
                            <th style="text-align: center;">Finalizado</th>
                            <th style="text-align: right;">Total Cobrado</th>
                        </tr>
                    </thead>
                    <tbody>
                        ' . $filas . '
                    </tbody>
                </table>
                
                <div style="text-align: center; margin-top: 30px;">
                    <p><strong>📅 Fecha del reporte:</strong> ' . date('d/m/Y H:i') . '</p>
                </div>
            </div>
            
            <div class="footer">
                <p><strong>Mujeres Virtuosas S.A</strong></p>
                <p>Sistema de Gestión de Créditos</p>
                <p style="margin-top: 10px; font-size: 11px;">Este es un correo automático generado por el sistema.</p>
            </div>
        </div>
    </body>
    </html>
    ';
}
?>
