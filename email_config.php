<?php
/**
 * Configuración de correo electrónico
 * Para enviar correos reales con Gmail
 */

// Credenciales de Gmail
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_SECURE', 'tls'); // o 'ssl' para puerto 465

// Tu correo de Gmail y contraseña de aplicación
define('SMTP_USERNAME', 'Cristaldoericagraciela@gmail.com'); // Tu correo de Gmail
define('SMTP_PASSWORD', 'chwz hgkw druy jdhn'); // IMPORTANTE: Necesitas generar una "Contraseña de aplicación" en tu cuenta de Google

// Correo del remitente
define('EMAIL_FROM', 'Cristaldoericagraciela@gmail.com');
define('EMAIL_SUBJECT_PREFIX', '');
define('EMAIL_FROM_NAME', 'Mujeres Virtuosas');
define('EMAIL_FOOTER', 'Este es un correo automático, por favor no responder.');

// Correo del destinatario (donde se enviarán las notificaciones)
define('EMAIL_TO', 'Cristaldoericagraciela@gmail.com');

/**
 * INSTRUCCIONES PARA CONFIGURAR GMAIL:
 * 
 * 1. Ve a tu cuenta de Google: https://myaccount.google.com/
 * 2. En el menú izquierdo, selecciona "Seguridad"
 * 3. En "Cómo iniciar sesión en Google", activa la "Verificación en 2 pasos" (si no está activada)
 * 4. Una vez activada, vuelve a "Seguridad" y busca "Contraseñas de aplicaciones"
 * 5. Selecciona "Correo" y "Windows PC" (o el dispositivo que uses)
 * 6. Google te generará una contraseña de 16 caracteres
 * 7. Copia esa contraseña y pégala en SMTP_PASSWORD arriba (sin espacios)
 * 8. ¡Listo! Ya puedes enviar correos
 */
?>
