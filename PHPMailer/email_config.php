<?php
/**
 * Configuración de correo electrónico
 * Compatible con cualquier proveedor SMTP (Gmail, Yahoo, Hostinger, etc.).
 * Recomendado: configurar por variables de entorno.
 *
 * Seguridad:
 * - NO guardes usuario/contraseña SMTP en el código.
 * - Usa variables de entorno MV_SMTP_USERNAME / MV_SMTP_PASSWORD.
 * - Si querés “codificar” y NO usar variables de entorno, podés cargar los valores Base64
 *   directamente en este archivo (ver sección EDITAR AQUÍ).
 *   (Base64 es ofuscación, no cifrado real).
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Preferir Composer (vendor/) y dejar fallback a la copia local.
$__mv_vendor_autoload = dirname(__DIR__) . '/vendor/autoload.php';
if (is_file($__mv_vendor_autoload)) {
	require_once $__mv_vendor_autoload;
} else {
	require_once __DIR__ . '/src/Exception.php';
	require_once __DIR__ . '/src/PHPMailer.php';
	require_once __DIR__ . '/src/SMTP.php';
}

function mv_env_or_b64($plainEnv, $b64Env, $default = '') {
	$plain = getenv($plainEnv);
	if ($plain !== false && $plain !== '') {
		return $plain;
	}
	$b64 = getenv($b64Env);
	if ($b64 !== false && $b64 !== '') {
		$decoded = base64_decode($b64, true);
		if ($decoded !== false && $decoded !== '') {
			return $decoded;
		}
	}
	return $default;
}

function mv_b64_value($b64, $default = '') {
	if (!is_string($b64) || trim($b64) === '') {
		return $default;
	}
	$decoded = base64_decode($b64, true);
	if ($decoded === false || $decoded === '') {
		return $default;
	}
	return $decoded;
}

$__mv_smtp_host = getenv('MV_SMTP_HOST');
$__mv_smtp_port = getenv('MV_SMTP_PORT');
$__mv_smtp_secure = getenv('MV_SMTP_SECURE');

/* ======================================================
	EDITAR AQUÍ (central): usuario/clave “codificados”
	======================================================
	Si NO querés usar variables de entorno, podés poner acá:

	- $__default_smtp_username_b64: base64 del correo remitente
	- $__default_smtp_password_b64: base64 de la contraseña/app-password
	- $__default_email_to_b64: (opcional) base64 del destinatario único

	Si dejás email_to vacío, por defecto se usa el mismo smtp_username.
*/
$__default_smtp_username_b64 = '';
$__default_smtp_password_b64 = '';
$__default_email_to_b64 = '';

$__default_smtp_username = mv_b64_value($__default_smtp_username_b64, '');
$__default_smtp_password = mv_b64_value($__default_smtp_password_b64, '');

$__mv_smtp_username = mv_env_or_b64('MV_SMTP_USERNAME', 'MV_SMTP_USERNAME_B64', $__default_smtp_username);
$__mv_smtp_password = mv_env_or_b64('MV_SMTP_PASSWORD', 'MV_SMTP_PASSWORD_B64', $__default_smtp_password);

$__mv_email_from = getenv('MV_EMAIL_FROM');
$__mv_email_from_name = getenv('MV_EMAIL_FROM_NAME');
$__mv_email_to = getenv('MV_EMAIL_TO');

if ($__mv_email_to === false || $__mv_email_to === '') {
	$__mv_email_to = mv_b64_value($__default_email_to_b64, '');
}

// Defaults de servidor (pueden ajustarse si cambias proveedor)
$__default_smtp_host = 'smtp.mail.yahoo.com';
$__default_smtp_port = 587;
$__default_smtp_secure = 'tls';

define('SMTP_HOST', ($__mv_smtp_host !== false && $__mv_smtp_host !== '') ? $__mv_smtp_host : $__default_smtp_host);
define('SMTP_PORT', ($__mv_smtp_port !== false && $__mv_smtp_port !== '' && ctype_digit((string)$__mv_smtp_port)) ? (int)$__mv_smtp_port : (int)$__default_smtp_port);
define('SMTP_SECURE', ($__mv_smtp_secure !== false && $__mv_smtp_secure !== '') ? $__mv_smtp_secure : $__default_smtp_secure);

define('SMTP_USERNAME', (is_string($__mv_smtp_username) && trim($__mv_smtp_username) !== '') ? trim($__mv_smtp_username) : '');
define('SMTP_PASSWORD', preg_replace('/\s+/', '', (string)$__mv_smtp_password));

// Correo del remitente
define('EMAIL_FROM', ($__mv_email_from !== false && $__mv_email_from !== '') ? $__mv_email_from : SMTP_USERNAME);
define('EMAIL_SUBJECT_PREFIX', '');
define('EMAIL_FROM_NAME', ($__mv_email_from_name !== false && $__mv_email_from_name !== '') ? $__mv_email_from_name : 'Mujeres Virtuosas');
define('EMAIL_FOOTER', 'Este es un correo automático, por favor no responder.');

// Correo del destinatario por defecto (notificaciones/admin)
define('EMAIL_TO', ($__mv_email_to !== false && $__mv_email_to !== '') ? $__mv_email_to : SMTP_USERNAME);

// Envío general (usar en todos los casos)
function mv_enviar_correo($to, $subject, $htmlBody) {
	$mail = new PHPMailer(true);
	try {
		$GLOBALS['MAILER_LAST_ERROR'] = '';

		if (!defined('SMTP_USERNAME') || trim((string)SMTP_USERNAME) === '' || !defined('SMTP_PASSWORD') || trim((string)SMTP_PASSWORD) === '') {
			throw new Exception('SMTP no configurado. Define MV_SMTP_USERNAME y MV_SMTP_PASSWORD (o sus variantes _B64).');
		}
		if (!defined('EMAIL_FROM') || trim((string)EMAIL_FROM) === '') {
			throw new Exception('EMAIL_FROM no configurado (define MV_EMAIL_FROM o MV_SMTP_USERNAME).');
		}

		$mail->isSMTP();
		$mail->Host     = SMTP_HOST;
		$mail->Port     = SMTP_PORT;
		$mail->SMTPAuth = true;
		$mail->Username = SMTP_USERNAME;
		$mail->Password = (string)SMTP_PASSWORD;
		$mail->CharSet  = 'UTF-8';
		$mail->Timeout  = 20;
		$mail->SMTPAutoTLS = true;

		// Aceptar tanto 'tls'/'ssl' como 'starttls'/'smtps'.
		$secure = strtolower(trim((string)SMTP_SECURE));
		if ($secure === 'tls' || $secure === 'starttls') {
			$mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
		} elseif ($secure === 'ssl' || $secure === 'smtps') {
			$mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
		} else {
			$mail->SMTPSecure = '';
		}

		// Windows/WAMP a veces falla por certificados CA.
		// Activar SOLO si es necesario: setx MV_SMTP_ALLOW_SELF_SIGNED "1"
		$allowSelfSigned = getenv('MV_SMTP_ALLOW_SELF_SIGNED');
		if ($allowSelfSigned !== false && (string)$allowSelfSigned === '1') {
			$mail->SMTPOptions = [
				'ssl' => [
					'verify_peer' => false,
					'verify_peer_name' => false,
					'allow_self_signed' => true,
				],
			];
		}

		// Debug opcional a error_log: setx MV_SMTP_DEBUG "2"
		$debug = getenv('MV_SMTP_DEBUG');
		if ($debug !== false && ctype_digit((string)$debug) && (int)$debug > 0) {
			$mail->SMTPDebug = (int)$debug;
			$mail->Debugoutput = function ($str, $level) {
				error_log('[SMTP ' . $level . '] ' . $str);
			};
		}

		$mail->setFrom(EMAIL_FROM, defined('EMAIL_FROM_NAME') ? EMAIL_FROM_NAME : '');
		$mail->addAddress((string)$to);

		$mail->isHTML(true);
		$mail->Subject = (string)$subject;
		$mail->Body    = (string)$htmlBody;

		$mail->send();
		return true;
	} catch (Exception $e) {
		$detalle = trim((string)$mail->ErrorInfo) !== '' ? (string)$mail->ErrorInfo : $e->getMessage();
		$GLOBALS['MAILER_LAST_ERROR'] = $detalle;
		error_log('Error al enviar correo (mv_enviar_correo): ' . $detalle);
		return false;
	}
}

// Envío con adjuntos (por ejemplo: PDF).
// $attachments: array de items con:
// - ['data' => (string)bytes, 'name' => 'archivo.pdf', 'type' => 'application/pdf']
//   o
// - ['path' => 'C:/ruta/archivo.pdf', 'name' => 'archivo.pdf', 'type' => 'application/pdf']
function mv_enviar_correo_con_adjuntos($to, $subject, $htmlBody, $attachments = []) {
	$mail = new PHPMailer(true);
	try {
		$GLOBALS['MAILER_LAST_ERROR'] = '';

		if (!defined('SMTP_USERNAME') || trim((string)SMTP_USERNAME) === '' || !defined('SMTP_PASSWORD') || trim((string)SMTP_PASSWORD) === '') {
			throw new Exception('SMTP no configurado. Define MV_SMTP_USERNAME y MV_SMTP_PASSWORD (o sus variantes _B64).');
		}
		if (!defined('EMAIL_FROM') || trim((string)EMAIL_FROM) === '') {
			throw new Exception('EMAIL_FROM no configurado (define MV_EMAIL_FROM o MV_SMTP_USERNAME).');
		}

		$mail->isSMTP();
		$mail->Host     = SMTP_HOST;
		$mail->Port     = SMTP_PORT;
		$mail->SMTPAuth = true;
		$mail->Username = SMTP_USERNAME;
		$mail->Password = (string)SMTP_PASSWORD;
		$mail->CharSet  = 'UTF-8';
		$mail->Timeout  = 20;
		$mail->SMTPAutoTLS = true;

		$secure = strtolower(trim((string)SMTP_SECURE));
		if ($secure === 'tls' || $secure === 'starttls') {
			$mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
		} elseif ($secure === 'ssl' || $secure === 'smtps') {
			$mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
		} else {
			$mail->SMTPSecure = '';
		}

		$allowSelfSigned = getenv('MV_SMTP_ALLOW_SELF_SIGNED');
		if ($allowSelfSigned !== false && (string)$allowSelfSigned === '1') {
			$mail->SMTPOptions = [
				'ssl' => [
					'verify_peer' => false,
					'verify_peer_name' => false,
					'allow_self_signed' => true,
				],
			];
		}

		$debug = getenv('MV_SMTP_DEBUG');
		if ($debug !== false && ctype_digit((string)$debug) && (int)$debug > 0) {
			$mail->SMTPDebug = (int)$debug;
			$mail->Debugoutput = function ($str, $level) {
				error_log('[SMTP ' . $level . '] ' . $str);
			};
		}

		$mail->setFrom(EMAIL_FROM, defined('EMAIL_FROM_NAME') ? EMAIL_FROM_NAME : '');
		$mail->addAddress((string)$to);

		$mail->isHTML(true);
		$mail->Subject = (string)$subject;
		$mail->Body    = (string)$htmlBody;

		if (is_array($attachments) && !empty($attachments)) {
			foreach ($attachments as $att) {
				if (!is_array($att)) {
					continue;
				}

				$name = isset($att['name']) && is_string($att['name']) && trim($att['name']) !== '' ? (string)$att['name'] : 'archivo';
				$type = isset($att['type']) && is_string($att['type']) && trim($att['type']) !== '' ? (string)$att['type'] : 'application/octet-stream';

				if (isset($att['path']) && is_string($att['path']) && is_file($att['path'])) {
					// PHPMailer infiere MIME; pero si se pasa $type queda más explícito.
					$mail->addAttachment($att['path'], $name, 'base64', $type);
					continue;
				}

				if (isset($att['data']) && is_string($att['data']) && $att['data'] !== '') {
					$mail->addStringAttachment($att['data'], $name, 'base64', $type);
					continue;
				}
			}
		}

		$mail->send();
		return true;
	} catch (Exception $e) {
		$detalle = trim((string)$mail->ErrorInfo) !== '' ? (string)$mail->ErrorInfo : $e->getMessage();
		$GLOBALS['MAILER_LAST_ERROR'] = $detalle;
		error_log('Error al enviar correo (mv_enviar_correo_con_adjuntos): ' . $detalle);
		return false;
	}
}


unset(
	$__mv_vendor_autoload,
	$__default_smtp_host,
	$__default_smtp_port,
	$__default_smtp_secure,
	$__default_smtp_username_b64,
	$__default_smtp_password_b64,
	$__default_email_to_b64,
	$__default_smtp_username,
	$__default_smtp_password,
	$__mv_smtp_host,
	$__mv_smtp_port,
	$__mv_smtp_secure,
	$__mv_smtp_username,
	$__mv_smtp_password,
	$__mv_email_from,
	$__mv_email_from_name,
	$__mv_email_to
);

/**
 * INSTRUCCIONES PARA CONFIGURAR GMAIL (opcional si usas Gmail):
 * 
 * 1. Ve a tu cuenta de Google: https://myaccount.google.com/
 * 2. En el menú izquierdo, selecciona "Seguridad"
 * 3. En "Cómo iniciar sesión en Google", activa la "Verificación en 2 pasos" (si no está activada)
 * 4. Una vez activada, vuelve a "Seguridad" y busca "Contraseñas de aplicaciones"
 * 5. Selecciona "Correo" y "Windows PC" (o el dispositivo que uses)
 * 6. Google te generará una contraseña de 16 caracteres
 * 7. Copia esa contraseña y configúrala en MV_SMTP_PASSWORD (sin espacios)
 * 8. ¡Listo! Ya puedes enviar correos
 */
?>
