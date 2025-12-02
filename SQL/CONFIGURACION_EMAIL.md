# 📧 Configuración de Correo Electrónico - Sistema Mujeres Virtuosas S.A

## 📋 Índice
1. [Introducción](#introducción)
2. [Archivos de Configuración](#archivos-de-configuración)
3. [Cambiar Email de Notificaciones](#cambiar-email-de-notificaciones)
4. [Cambiar Email de Recuperación](#cambiar-email-de-recuperación)
5. [Configurar Gmail](#configurar-gmail)
6. [Configurar Otros Proveedores](#configurar-otros-proveedores)
7. [Pruebas de Email](#pruebas-de-email)
8. [Solución de Problemas](#solución-de-problemas)
9. [Seguridad](#seguridad)

---

## 📧 Introducción

El sistema utiliza correo electrónico para **dos funcionalidades principales**:

1. **📬 Notificaciones**: Alertas de pagos atrasados y finalizados
2. **🔐 Recuperación de Contraseña**: Envío de códigos de recuperación

Ambas funcionalidades usan **PHPMailer** con autenticación SMTP de Gmail u otro proveedor.

---

## 📁 Archivos de Configuración

### Archivo Principal: `email_config.php`

**Ubicación**: `c:\xampp\htdocs\Local_MV\email_config.php`

Este archivo contiene **todas las constantes de configuración** de email:

```php
<?php
// =====================================================
// CONFIGURACIÓN DE EMAIL - SISTEMA MUJERES VIRTUOSAS
// =====================================================

// SMTP - Servidor de correo
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_SECURE', 'tls');

// SMTP - Autenticación
define('SMTP_USERNAME', 'tucorreo@gmail.com');  // ← CAMBIAR AQUÍ
define('SMTP_PASSWORD', 'tu_app_password');     // ← CAMBIAR AQUÍ

// Email - Remitente (quien envía)
define('EMAIL_FROM', 'tucorreo@gmail.com');     // ← CAMBIAR AQUÍ
define('EMAIL_FROM_NAME', 'Mujeres Virtuosas S.A');

// Email - Destinatario (quien recibe notificaciones)
define('EMAIL_TO', 'correo_jefe@gmail.com');    // ← CAMBIAR AQUÍ
define('EMAIL_TO_NAME', 'Administrador');

// Configuración adicional
define('EMAIL_CHARSET', 'UTF-8');
define('EMAIL_DEBUG', 0); // 0=sin debug, 2=debug completo
?>
```

---

## 📬 Cambiar Email de Notificaciones

### ¿Qué son las Notificaciones?

El sistema envía emails automáticos para:
- ⚠️ **Pagos Atrasados**: Clientes con cuotas vencidas (diario a las 8:00 AM)
- ✅ **Pagos Finalizados**: Cliente completa todos sus pagos (instantáneo)

### Paso 1: Editar `email_config.php`

**Abrir archivo**: `email_config.php` (líneas 12-18)

```php
// SMTP - Autenticación
define('SMTP_USERNAME', 'nuevo_correo@gmail.com');    // Email que envía
define('SMTP_PASSWORD', 'xxxx xxxx xxxx xxxx');       // Contraseña de aplicación

// Email - Remitente
define('EMAIL_FROM', 'nuevo_correo@gmail.com');       // Mismo que SMTP_USERNAME
define('EMAIL_FROM_NAME', 'Mujeres Virtuosas S.A');   // Nombre que aparece

// Email - Destinatario
define('EMAIL_TO', 'jefe@gmail.com');                 // Email del jefe (quien recibe)
define('EMAIL_TO_NAME', 'Administrador');             // Nombre del jefe
```

### Explicación de Constantes

| Constante | Descripción | Ejemplo |
|-----------|-------------|---------|
| `SMTP_USERNAME` | Email de la cuenta Gmail que **envía** los correos | `notificaciones@gmail.com` |
| `SMTP_PASSWORD` | **Contraseña de aplicación** de Gmail (NO la contraseña normal) | `abcd efgh ijkl mnop` |
| `EMAIL_FROM` | Email del remitente (mismo que `SMTP_USERNAME`) | `notificaciones@gmail.com` |
| `EMAIL_FROM_NAME` | Nombre que aparece como remitente | `Mujeres Virtuosas S.A` |
| `EMAIL_TO` | Email del **destinatario** (jefe que recibe notificaciones) | `gerente@gmail.com` |
| `EMAIL_TO_NAME` | Nombre del destinatario | `Gerente General` |

### ⚠️ Importante

- `SMTP_USERNAME` y `EMAIL_FROM` **deben ser el mismo email**
- `EMAIL_TO` puede ser **diferente** (otro email, otro proveedor)
- Usar **contraseña de aplicación**, NO la contraseña normal de Gmail

---

## 🔐 Cambiar Email de Recuperación

### ¿Qué es la Recuperación de Contraseña?

Cuando un usuario olvida su contraseña:
1. Ingresa a `recuperar_contrasena.php`
2. Solicita código de recuperación
3. Sistema envía email con código de 6 dígitos
4. Usuario ingresa código y restablece contraseña

### Archivos Involucrados

#### 1. `email_helper.php` (Funciones de envío)

**Ubicación**: `c:\xampp\htdocs\Local_MV\email_helper.php`

**Función principal**: `enviarEmailRecuperacion($emailDestino, $nombreUsuario, $codigoRecuperacion)`

```php
<?php
require_once 'PHPMailer/src/PHPMailer.php';
require_once 'PHPMailer/src/SMTP.php';
require_once 'PHPMailer/src/Exception.php';
require_once 'email_config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function enviarEmailRecuperacion($emailDestino, $nombreUsuario, $codigoRecuperacion) {
    $mail = new PHPMailer(true);
    
    try {
        // Configuración SMTP
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USERNAME;   // ← Usa email_config.php
        $mail->Password = SMTP_PASSWORD;   // ← Usa email_config.php
        $mail->SMTPSecure = SMTP_SECURE;
        $mail->Port = SMTP_PORT;
        
        // Remitente
        $mail->setFrom(EMAIL_FROM, EMAIL_FROM_NAME);  // ← Usa email_config.php
        
        // Destinatario (el usuario que olvidó su contraseña)
        $mail->addAddress($emailDestino, $nombreUsuario);
        
        // Contenido del email
        $mail->isHTML(true);
        $mail->CharSet = EMAIL_CHARSET;
        $mail->Subject = '🔐 Recuperación de Contraseña - Mujeres Virtuosas';
        
        $mail->Body = "
        <html>
        <body style='font-family: Arial, sans-serif;'>
            <h2>Recuperación de Contraseña</h2>
            <p>Hola <strong>{$nombreUsuario}</strong>,</p>
            <p>Tu código de recuperación es:</p>
            <h1 style='color: #2563eb; font-size: 36px;'>{$codigoRecuperacion}</h1>
            <p>Este código expira en <strong>15 minutos</strong>.</p>
        </body>
        </html>
        ";
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
}
?>
```

### Cambiar Configuración

**No necesitas modificar `email_helper.php`**, solo cambia `email_config.php` porque usa las constantes definidas allí.

#### Ejemplo de Cambio:

**Antes** (en `email_config.php`):
```php
define('SMTP_USERNAME', 'viejo@gmail.com');
define('EMAIL_FROM', 'viejo@gmail.com');
```

**Después**:
```php
define('SMTP_USERNAME', 'nuevo@gmail.com');
define('EMAIL_FROM', 'nuevo@gmail.com');
```

✅ Automáticamente todos los emails (notificaciones Y recuperación) usarán el nuevo email.

---

## 🔧 Configurar Gmail

### Paso 1: Habilitar Verificación en 2 Pasos

1. Ir a: https://myaccount.google.com/security
2. Buscar **"Verificación en dos pasos"**
3. Click en **"Activar"**
4. Seguir los pasos (verificar con celular)

### Paso 2: Crear Contraseña de Aplicación

1. Ir a: https://myaccount.google.com/apppasswords
2. En "Selecciona la app": elegir **"Correo"**
3. En "Selecciona el dispositivo": elegir **"Otro (nombre personalizado)"**
4. Escribir: `Sistema Mujeres Virtuosas`
5. Click en **"Generar"**
6. **Copiar la contraseña** (16 caracteres con espacios, ejemplo: `abcd efgh ijkl mnop`)

### Paso 3: Actualizar `email_config.php`

```php
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_SECURE', 'tls');

define('SMTP_USERNAME', 'tucorreo@gmail.com');        // Tu email de Gmail
define('SMTP_PASSWORD', 'abcd efgh ijkl mnop');       // Contraseña generada

define('EMAIL_FROM', 'tucorreo@gmail.com');           // Mismo email
define('EMAIL_FROM_NAME', 'Mujeres Virtuosas S.A');

define('EMAIL_TO', 'jefe@gmail.com');                 // Email del destinatario
define('EMAIL_TO_NAME', 'Administrador');
```

### ⚠️ Errores Comunes con Gmail

| Error | Causa | Solución |
|-------|-------|----------|
| `SMTP AUTH failed` | Contraseña incorrecta | Regenerar contraseña de aplicación |
| `Username and Password not accepted` | Usando contraseña normal en vez de app password | Usar contraseña de aplicación |
| `Must issue a STARTTLS` | Puerto o encriptación incorrecta | Usar puerto `587` con `tls` |
| `Daily user sending quota exceeded` | Límite de envío diario superado | Gmail: 500 emails/día, esperar 24h |

---

## 📮 Configurar Otros Proveedores

### Outlook / Hotmail

```php
define('SMTP_HOST', 'smtp-mail.outlook.com');
define('SMTP_PORT', 587);
define('SMTP_SECURE', 'tls');

define('SMTP_USERNAME', 'tucorreo@outlook.com');
define('SMTP_PASSWORD', 'tu_contraseña_outlook');

define('EMAIL_FROM', 'tucorreo@outlook.com');
```

**Nota**: Outlook también requiere habilitar "Permitir aplicaciones menos seguras" en configuración.

### Yahoo Mail

```php
define('SMTP_HOST', 'smtp.mail.yahoo.com');
define('SMTP_PORT', 587);
define('SMTP_SECURE', 'tls');

define('SMTP_USERNAME', 'tucorreo@yahoo.com');
define('SMTP_PASSWORD', 'tu_app_password_yahoo');  // Requiere app password

define('EMAIL_FROM', 'tucorreo@yahoo.com');
```

### SMTP Personalizado (Hosting)

```php
define('SMTP_HOST', 'mail.tudominio.com');
define('SMTP_PORT', 465);                    // O 587 según proveedor
define('SMTP_SECURE', 'ssl');                // O 'tls'

define('SMTP_USERNAME', 'notificaciones@tudominio.com');
define('SMTP_PASSWORD', 'tu_contraseña');

define('EMAIL_FROM', 'notificaciones@tudominio.com');
```

**Consultar con el proveedor de hosting**:
- Host SMTP
- Puerto (465 o 587)
- Tipo de encriptación (SSL o TLS)

---

## 🧪 Pruebas de Email

### Método 1: Archivo de Prueba (test_recuperar.php)

**Ubicación**: `c:\xampp\htdocs\Local_MV\test_recuperar.php`

```php
<?php
require_once 'email_helper.php';

// Datos de prueba
$emailDestino = 'prueba@gmail.com';  // ← CAMBIAR a tu email de prueba
$nombreUsuario = 'Usuario Prueba';
$codigoRecuperacion = '123456';

// Enviar email de prueba
$resultado = enviarEmailRecuperacion($emailDestino, $nombreUsuario, $codigoRecuperacion);

if ($resultado) {
    echo "✅ Email enviado correctamente a: " . $emailDestino;
} else {
    echo "❌ Error al enviar email";
}
?>
```

**Ejecutar**:
1. Editar línea 5: poner tu email
2. Abrir navegador: `http://localhost/Local_MV/test_recuperar.php`
3. Verificar tu bandeja de entrada

### Método 2: Probar Script de Notificaciones

**Ubicación**: `c:\xampp\htdocs\Local_MV\enviar_notificacion_atrasados.php`

```php
<?php
require_once 'conexion.php';
require_once 'notificaciones_email.php';
require_once 'email_config.php';

// Obtener clientes con pagos atrasados
$query = "SELECT DISTINCT 
            c.id, c.nombre_completo, c.telefono, c.barrio,
            COUNT(*) as cuotas_atrasadas,
            MIN(pc.fecha_programada) as fecha_mas_antigua
          FROM clientes c
          INNER JOIN pagos_clientes pc ON c.id = pc.cliente_id
          WHERE pc.estado = 'pendiente' 
            AND pc.fecha_programada < CURDATE()
          GROUP BY c.id
          ORDER BY fecha_mas_antigua ASC";

$result = mysqli_query($conexion, $query);
$clientes = mysqli_fetch_all($result, MYSQLI_ASSOC);

if (count($clientes) > 0) {
    // Enviar notificación
    $enviado = enviarNotificacionPagosAtrasados($clientes, EMAIL_TO);
    
    if ($enviado) {
        echo "✅ Notificación enviada a: " . EMAIL_TO;
    } else {
        echo "❌ Error al enviar notificación";
    }
} else {
    echo "ℹ️ No hay clientes con pagos atrasados";
}
?>
```

**Ejecutar**: `http://localhost/Local_MV/enviar_notificacion_atrasados.php`

### Método 3: Modo Debug de PHPMailer

**Activar debug** en `email_config.php`:

```php
define('EMAIL_DEBUG', 2);  // Cambiar de 0 a 2
```

**Niveles de debug**:
- `0`: Sin debug (producción)
- `1`: Mensajes del cliente
- `2`: Mensajes del cliente y servidor (recomendado para pruebas)
- `3`: Debug completo
- `4`: Debug de bajo nivel

**Ver logs**:
- Los errores se mostrarán en la página
- Revisar conexión SMTP, autenticación, envío

**Desactivar debug** después de probar:
```php
define('EMAIL_DEBUG', 0);
```

---

## ❌ Solución de Problemas

### Error: "SMTP connect() failed"

**Causas**:
- No hay conexión a internet
- Puerto bloqueado por firewall
- Host incorrecto

**Soluciones**:
```php
// Verificar configuración
define('SMTP_HOST', 'smtp.gmail.com');  // Correcto para Gmail
define('SMTP_PORT', 587);               // Puerto TLS
define('SMTP_SECURE', 'tls');           // Encriptación TLS
```

### Error: "SMTP Error: Could not authenticate"

**Causas**:
- Contraseña incorrecta
- Usando contraseña normal en vez de app password
- Usuario incorrecto

**Soluciones**:
1. Regenerar contraseña de aplicación en Gmail
2. Verificar que `SMTP_USERNAME` y `EMAIL_FROM` sean iguales
3. Copiar contraseña con espacios: `abcd efgh ijkl mnop`

### Error: "Could not instantiate mail function"

**Causa**: PHPMailer no está instalado correctamente

**Solución**:
```bash
# Verificar que exista la carpeta
dir PHPMailer\src\
# Debe tener: PHPMailer.php, SMTP.php, Exception.php
```

### Email No Llega (Sin Errores)

**Revisar**:
1. **Carpeta de Spam**: Gmail a veces marca como spam
2. **Email destinatario**: Verificar `EMAIL_TO` en `email_config.php`
3. **Límite de envío**: Gmail tiene límite de 500 emails/día

**Verificar con debug**:
```php
define('EMAIL_DEBUG', 2);
```

### Error: "Message body empty"

**Causa**: No se está enviando contenido HTML

**Solución**:
```php
$mail->isHTML(true);
$mail->Body = "<html><body><h1>Contenido</h1></body></html>";
```

---

## 🔒 Seguridad

### ⚠️ Proteger `email_config.php`

#### 1. No Subir a GitHub

**Crear archivo**: `.gitignore`

```
email_config.php
*.log
```

#### 2. Permisos del Archivo (Linux/Mac)

```bash
chmod 600 email_config.php
```

#### 3. Variables de Entorno (Recomendado para Producción)

**Crear**: `.env`

```
SMTP_USERNAME=tucorreo@gmail.com
SMTP_PASSWORD=abcd efgh ijkl mnop
EMAIL_TO=jefe@gmail.com
```

**Modificar** `email_config.php`:

```php
<?php
// Cargar variables de entorno
$dotenv = parse_ini_file('.env');

define('SMTP_USERNAME', $dotenv['SMTP_USERNAME']);
define('SMTP_PASSWORD', $dotenv['SMTP_PASSWORD']);
define('EMAIL_TO', $dotenv['EMAIL_TO']);
?>
```

### 🛡️ Buenas Prácticas

1. ✅ **Usar contraseña de aplicación** (no contraseña normal)
2. ✅ **Cambiar contraseña periódicamente** (cada 3-6 meses)
3. ✅ **No compartir credenciales** por email o chat
4. ✅ **Revisar actividad de la cuenta** Gmail regularmente
5. ✅ **Usar email dedicado** para el sistema (no personal)
6. ✅ **Encriptar conexión** (TLS/SSL siempre activo)
7. ✅ **Limitar intentos** de envío para evitar spam

### 📧 Email Dedicado (Recomendación)

**Crear cuenta Gmail específica**:
- Email: `notificaciones.mujeresvirtuosas@gmail.com`
- Uso: Solo para el sistema
- No usar para otros fines

**Ventajas**:
- Organización separada
- Fácil auditoría de emails enviados
- Si hay problema, no afecta emails personales
- Mejor control de seguridad

---

## 📝 Resumen Rápido

### Para Cambiar Email de Notificaciones:

**Archivo**: `email_config.php`

```php
// 1. Email que ENVÍA (cuenta Gmail)
define('SMTP_USERNAME', 'nuevo_envio@gmail.com');
define('SMTP_PASSWORD', 'xxxx xxxx xxxx xxxx');  // App password
define('EMAIL_FROM', 'nuevo_envio@gmail.com');

// 2. Email que RECIBE (jefe)
define('EMAIL_TO', 'nuevo_jefe@gmail.com');
```

### Para Cambiar Email de Recuperación:

**Mismo archivo**: `email_config.php`

```php
// Solo cambiar el remitente
define('SMTP_USERNAME', 'nuevo_envio@gmail.com');
define('SMTP_PASSWORD', 'xxxx xxxx xxxx xxxx');
define('EMAIL_FROM', 'nuevo_envio@gmail.com');
```

El destinatario en recuperación es dinámico (usuario que solicitó código).

### Pasos Completos:

1. ✅ Editar `email_config.php`
2. ✅ Obtener contraseña de aplicación de Gmail
3. ✅ Actualizar `SMTP_USERNAME`, `SMTP_PASSWORD`, `EMAIL_FROM`
4. ✅ Actualizar `EMAIL_TO` (para notificaciones)
5. ✅ Probar con `test_recuperar.php`
6. ✅ Verificar bandeja de entrada
7. ✅ Desactivar `EMAIL_DEBUG` (poner en 0)

---

## 📞 Checklist de Configuración

### Gmail
- [ ] Verificación en 2 pasos activada
- [ ] Contraseña de aplicación generada
- [ ] `email_config.php` actualizado con app password
- [ ] `SMTP_HOST` = `smtp.gmail.com`
- [ ] `SMTP_PORT` = `587`
- [ ] `SMTP_SECURE` = `tls`
- [ ] Email de prueba enviado exitosamente

### Otros Proveedores
- [ ] Consultar documentación del proveedor (host, puerto, encriptación)
- [ ] Configurar `email_config.php` con datos correctos
- [ ] Probar envío de email
- [ ] Verificar límites de envío diario

### Seguridad
- [ ] No usar contraseña normal de Gmail
- [ ] `email_config.php` no subido a GitHub (`.gitignore`)
- [ ] Debug desactivado en producción (`EMAIL_DEBUG = 0`)
- [ ] Revisar actividad de cuenta regularmente

---

**¡Configuración de Email Completada!** 📧✅

---

*Documentación creada: Noviembre 2025*  
*Última actualización: 25 de Noviembre de 2025*
