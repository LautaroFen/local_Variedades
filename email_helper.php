<?php
/**
 * Funció³n para enviar correos electró³nicos usando PHPMailer
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Cargar PHPMailer
require_once 'PHPMailer/src/Exception.php';
require_once 'PHPMailer/src/PHPMailer.php';
require_once 'PHPMailer/src/SMTP.php';

// Cargar configuració³n
require_once 'email_config.php';

/**
 * Envó­a un correo electró³nico
 * 
 * @param string $to Correo del destinatario
 * @param string $subject Asunto del correo
 * @param string $body Cuerpo del correo (HTML)
 * @return bool True si se envió³ correctamente, False si hubo error
 */
function enviarCorreo($to, $subject, $body) {
    $mail = new PHPMailer(true);
    
    try {
        // Configuració³n del servidor SMTP
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USERNAME;
        $mail->Password   = SMTP_PASSWORD;
        $mail->SMTPSecure = SMTP_SECURE;
        $mail->Port       = SMTP_PORT;
        $mail->CharSet    = 'UTF-8';
        
        // Remitente
        $mail->setFrom(EMAIL_FROM, EMAIL_FROM_NAME);
        
        // Destinatario
        $mail->addAddress($to);
        
        // Contenido
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;
        
        // Enviar
        $mail->send();
        return true;
        
    } catch (Exception $e) {
        error_log("Error al enviar correo: {$mail->ErrorInfo}");
        return false;
    }
}

/**
 * Genera el HTML del correo para có³digo de recuperació³n
 * 
 * @param string $codigo Có³digo de 6 dó­gitos
 * @param string $expiracion Fecha de expiració³n
 * @return string HTML del correo
 */
function generarHTMLCodigoRecuperacion($codigo, $expiracion) {
    return '
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: linear-gradient(to right, #024fb7, #0b3d91); color: white; padding: 20px; text-align: center; border-radius: 10px 10px 0 0; }
            .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
            .codigo { background: #e9ecef; padding: 20px; text-align: center; border-radius: 5px; margin: 20px 0; }
            .codigo h1 { color: #024fb7; font-size: 48px; letter-spacing: 10px; margin: 0; }
            .footer { text-align: center; margin-top: 20px; font-size: 12px; color: #666; }
            .button { background: #024fb7; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 10px 0; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h2>🔐 Mujeres Virtuosas</h2>
                <p>Recuperación de Usuario</p>
            </div>
            <div class="content">
                <h3>Hola,</h3>
                <p>Recibimos una solicitud para recuperar el usuario de tu cuenta en <strong>Mujeres Virtuosas</strong>.</p>
                <p>Tu código de verificación es:</p>
                
                <div class="codigo">
                    <h1>' . $codigo . '</h1>
                </div>
                
                <p>Ingresa este có³digo en la pó¡gina de recuperació³n de usuario para continuar.</p>
                <p><strong>° Este có³digo expiraró¡ el:</strong> ' . $expiracion . '</p>
                <p><small style="color: #666;">Si no solicitaste este cambio, puedes ignorar este correo y tu cuenta permaneceró¡ sin cambios.</small></p>
            </div>
            <div class="footer">
                <p><strong>Equipo de Mujeres Virtuosas</strong></p>
                <p>Este es un correo automó¡tico, por favor no respondas a este mensaje.</p>
            </div>
        </div>
    </body>
    </html>
    ';
}
?>

