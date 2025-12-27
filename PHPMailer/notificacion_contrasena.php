<?php
// Notificación de recuperación (usuario/contraseña)
// Usa el remitente general definido en PHPMailer/email_config.php

require_once __DIR__ . '/email_config.php';

function mv_generar_html_recuperacion($codigo, $expiracion)
{
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
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h2>Mujeres Virtuosas</h2>
                <p>Recuperación</p>
            </div>
            <div class="content">
                <h3>Hola,</h3>
                <p>Tu código de verificación es:</p>
                <div class="codigo"><h1>' . htmlspecialchars((string)$codigo) . '</h1></div>
                <p><strong>Este código expira el:</strong> ' . htmlspecialchars((string)$expiracion) . '</p>
                <p><small>Si no solicitaste esto, podés ignorar este correo.</small></p>
            </div>
            <div class="footer">
                <p>Este es un correo automático, por favor no responder.</p>
            </div>
        </div>
    </body>
    </html>
    ';
}

function mv_enviar_notificacion_contrasena($destinatario, $codigo, $expiracionFormateada)
{
    $html = mv_generar_html_recuperacion($codigo, $expiracionFormateada);
    return mv_enviar_correo($destinatario, 'Código de recuperación - Mujeres Virtuosas', $html);
}
