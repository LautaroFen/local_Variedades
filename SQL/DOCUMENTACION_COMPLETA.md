# 📚 Documentación Completa - Sistema Mujeres Virtuosas S.A

## 📋 Índice
1. [Descripción General](#descripción-general)
2. [Estructura del Proyecto](#estructura-del-proyecto)
3. [Base de Datos](#base-de-datos)
4. [Archivos Principales](#archivos-principales)
5. [Sistema de Autenticación](#sistema-de-autenticación)
6. [Gestión de Clientes](#gestión-de-clientes)
7. [Sistema de Pagos](#sistema-de-pagos)
8. [Sistema de Notificaciones](#sistema-de-notificaciones)
9. [Reportes y Exportación](#reportes-y-exportación)
10. [Configuración del Sistema](#configuración-del-sistema)
11. [Flujo de Trabajo](#flujo-de-trabajo)
12. [Guía para Desarrolladores](#guía-para-desarrolladores)

---

## 📖 Descripción General

### ¿Qué es este sistema?
Sistema web de gestión de créditos para "Mujeres Virtuosas S.A" que permite:
- Registrar clientes y sus compras a crédito
- Gestionar pagos por cuotas (semanal, quincenal, mensual)
- Hacer seguimiento de pagos pendientes, atrasados y finalizados
- Enviar notificaciones por email
- Generar reportes en Excel y PDF
- Control de empleados vendedores
- Dashboard con estadísticas en tiempo real

### Tecnologías Utilizadas
- **Backend**: PHP 7.4+ con MySQLi
- **Frontend**: HTML5, CSS3, JavaScript, Bootstrap 5.3.2
- **Base de Datos**: MySQL/MariaDB
- **Librerías Externas**:
  - PHPMailer (envío de emails)
  - PHPSpreadsheet (exportación a Excel)
  - TCPDF o similar (generación de PDFs)

---

## 🗂️ Estructura del Proyecto

```
Local_MV/
│
├── includes/                          # Archivos compartidos
│   ├── header.php                     # Header con menú de navegación
│   ├── styles.css                     # Estilos personalizados
│   ├── app.js                         # JavaScript personalizado
│   ├── notificaciones_widget.php      # Widget de notificaciones
│   ├── logo.jpg                       # Logo de la empresa
│   └── logoG.png                      # Logo grande
│
├── PHPMailer/                         # Librería para envío de emails
│   └── src/
│       ├── PHPMailer.php
│       ├── SMTP.php
│       └── Exception.php
│
├── SQL/                               # Scripts de base de datos
│   ├── estructura_completa.sql        # Estructura completa de BD
│   ├── instalacion_completa.sql       # Instalación desde cero
│   └── [otros scripts].sql
│
├── Archivos Principales PHP
├── conexion.php                       # Conexión a base de datos
├── index.php                          # Página principal (lista de clientes)
├── login.php                          # Inicio de sesión
├── logout.php                         # Cerrar sesión
├── dashboard.php                      # Panel de estadísticas
│
├── Gestión de Clientes
├── guardar.php                        # Guardar nuevo cliente
├── ver.php                            # Ver detalles de un cliente
├── editar.php                         # Editar cliente
├── eliminar.php                       # Eliminar cliente
│
├── Gestión de Pagos
├── registrar_pago.php                 # Registrar pago de cuota
├── cancelar_pago.php                  # Cancelar/revertir pago
├── editar_fecha_pago.php              # Modificar fecha de pago
│
├── Sistema de Notificaciones
├── notificaciones_email.php           # Funciones de envío de emails
├── enviar_notificaciones_diarias.php  # Script envío diario (completo)
├── enviar_notificacion_atrasados.php  # Script solo atrasados
│
├── Reportes
├── exportar_excel.php                 # Exportar clientes a Excel
├── estado_cuenta_pdf.php              # Estado de cuenta en PDF
│
├── Sistema de Usuarios
├── empleados_vendedores.php           # Gestión de empleados
├── cambiar_contrasena.php             # Cambiar contraseña
├── recuperar_contrasena.php           # Recuperar acceso
│
├── Configuración
├── email_config.php                   # Configuración de Gmail
├── email_helper.php                   # Funciones auxiliares email
│
└── Documentación
    ├── NOTIFICACIONES_README.md
    ├── NOTIFICACIONES_EMAIL_README.md
    ├── NOTIFICACIONES_INSTANTANEAS_INFO.md
    └── DOCUMENTACION_COMPLETA.md      # Este archivo
```

---

## 🗄️ Base de Datos

### Tablas Principales

#### 1. `clientes`
Almacena la información de los clientes que compran a crédito.

```sql
CREATE TABLE clientes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre_completo VARCHAR(100) NOT NULL,
    telefono VARCHAR(20) NOT NULL,
    barrio VARCHAR(100),
    direccion VARCHAR(200),
    articulos TEXT,                    -- Descripción de los artículos comprados
    valor_total DECIMAL(10,2) NOT NULL,
    sena DECIMAL(10,2) DEFAULT 0,      -- Adelanto/seña
    cuotas INT NOT NULL,
    frecuencia_pago ENUM('diario', 'semanal', 'quincenal', 'mensual'),
    vendedor_id INT,                   -- ID del empleado que hizo la venta
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (vendedor_id) REFERENCES empleados_vendedores(id)
);
```

**Campos importantes:**
- `valor_total`: Monto total de la compra
- `sena`: Adelanto que dio el cliente (se resta del total)
- `cuotas`: Cantidad de pagos a realizar
- `frecuencia_pago`: Cada cuánto paga (semanal, quincenal, mensual)

#### 2. `pagos_clientes`
Registra cada cuota/pago programado y su estado.

```sql
CREATE TABLE pagos_clientes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cliente_id INT NOT NULL,
    numero_cuota INT NOT NULL,
    monto DECIMAL(10,2) NOT NULL,
    fecha_programada DATE NOT NULL,    -- Fecha en que debe pagar
    fecha_pago DATE NULL,              -- Fecha real del pago (NULL si pendiente)
    estado ENUM('pendiente', 'pagado') DEFAULT 'pendiente',
    FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE
);
```

**Estados:**
- `pendiente`: No ha pagado aún
- `pagado`: Ya pagó (tiene fecha_pago)

**Cálculo de estado:**
- **Atrasado**: `estado = 'pendiente' AND fecha_programada < HOY`
- **Próximo**: `estado = 'pendiente' AND fecha_programada BETWEEN HOY AND +7 días`
- **Finalizado**: No existen pagos con `estado = 'pendiente'`

#### 3. `empleados_vendedores`
Vendedores/empleados que registran ventas.

```sql
CREATE TABLE empleados_vendedores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre_completo VARCHAR(100) NOT NULL,
    telefono VARCHAR(20),
    fecha_ingreso DATE,
    activo TINYINT(1) DEFAULT 1,      -- 1 = activo, 0 = inactivo
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

#### 4. `usuarios`
Usuarios del sistema (jefe y empleados).

```sql
CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario VARCHAR(50) UNIQUE NOT NULL,
    contrasena VARCHAR(255) NOT NULL,  -- Hash con password_hash()
    tipo_usuario ENUM('jefe', 'empleado') DEFAULT 'empleado',
    activo TINYINT(1) DEFAULT 1,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

**Tipos de usuario:**
- `jefe`: Acceso total (puede gestionar empleados)
- `empleado`: Acceso limitado (solo gestión de clientes)

---

## 📄 Archivos Principales

### 1. `conexion.php`
**Propósito**: Establece la conexión con la base de datos y maneja sesiones.

```php
<?php
session_start();

$host = 'localhost';
$usuario = 'root';
$contrasena = '';
$base_datos = 'local_mv';

$conn = mysqli_connect($host, $usuario, $contrasena, $base_datos);

if (!$conn) {
    die("Error de conexión: " . mysqli_connect_error());
}

mysqli_set_charset($conn, "utf8");
?>
```

**Uso**: Incluir al inicio de cada archivo PHP que necesite BD.
```php
include("conexion.php");
```

---

### 2. `index.php`
**Propósito**: Página principal que muestra la lista de clientes.

**Funcionalidades:**
1. **Formulario de registro de nuevo cliente**
   - Validación de campos
   - Cálculo automático de monto por cuota
   - Selección de vendedor

2. **Búsqueda y filtros**
   - Búsqueda por nombre/teléfono
   - Filtros avanzados (barrio, frecuencia, estado)
   - Paginación de resultados

3. **Tabla de clientes**
   - Muestra todos los clientes registrados
   - Información de pagos (pendientes, atrasados, finalizados)
   - Acciones: Ver, Editar, Eliminar, Estado de cuenta

**Consulta principal:**
```sql
SELECT 
    c.*,
    ev.nombre_completo as vendedor_nombre,
    COUNT(pc.id) as total_cuotas,
    SUM(CASE WHEN pc.estado = 'pagado' THEN 1 ELSE 0 END) as cuotas_pagadas,
    SUM(CASE WHEN pc.estado = 'pendiente' THEN 1 ELSE 0 END) as cuotas_pendientes,
    SUM(CASE WHEN pc.estado = 'pendiente' AND pc.fecha_programada < CURDATE() THEN 1 ELSE 0 END) as cuotas_atrasadas
FROM clientes c
LEFT JOIN empleados_vendedores ev ON c.vendedor_id = ev.id
LEFT JOIN pagos_clientes pc ON c.id = pc.cliente_id
GROUP BY c.id
ORDER BY c.fecha_registro DESC
```

**JavaScript importante:**
- `calcularMontoCuota()`: Calcula automáticamente el monto de cada cuota
- Validación de campos numéricos y texto
- Filtros dinámicos

---

### 3. `guardar.php`
**Propósito**: Procesa el formulario de nuevo cliente y genera las cuotas.

**Flujo:**
1. Recibe datos del formulario
2. Valida los datos
3. Inserta el cliente en la tabla `clientes`
4. **Genera automáticamente las cuotas** en `pagos_clientes`
5. Redirige a `ver.php` o `index.php`

**Generación de cuotas:**
```php
$saldo_restante = $valor_total - $sena;
$monto_cuota = $saldo_restante / $cuotas;

// Calcular fechas según frecuencia
for ($i = 1; $i <= $cuotas; $i++) {
    if ($frecuencia_pago == 'semanal') {
        $fecha = date('Y-m-d', strtotime("+{$i} week", strtotime($fecha_primer_pago)));
    } else if ($frecuencia_pago == 'quincenal') {
        $fecha = date('Y-m-d', strtotime("+".($i*2)." weeks", strtotime($fecha_primer_pago)));
    } else if ($frecuencia_pago == 'mensual') {
        $fecha = date('Y-m-d', strtotime("+{$i} month", strtotime($fecha_primer_pago)));
    }
    
    // Insertar cuota
    INSERT INTO pagos_clientes (cliente_id, numero_cuota, monto, fecha_programada, estado)
    VALUES ($cliente_id, $i, $monto_cuota, $fecha, 'pendiente');
}
```

---

### 4. `ver.php`
**Propósito**: Muestra todos los detalles de un cliente específico.

**Información mostrada:**
- Datos personales del cliente
- Información de la compra
- **Tabla de pagos/cuotas:**
  - Número de cuota
  - Monto
  - Fecha programada
  - Estado (Pendiente/Pagado/Atrasado)
  - Fecha de pago real
  - Acciones (Registrar pago, Cancelar pago, Editar fecha)

**Consultas importantes:**
```sql
-- Obtener cliente
SELECT c.*, ev.nombre_completo as vendedor_nombre
FROM clientes c
LEFT JOIN empleados_vendedores ev ON c.vendedor_id = ev.id
WHERE c.id = ?

-- Obtener pagos
SELECT * FROM pagos_clientes
WHERE cliente_id = ?
ORDER BY numero_cuota ASC
```

**Acciones disponibles:**
- Registrar pago → `registrar_pago.php`
- Cancelar pago → `cancelar_pago.php`
- Editar fecha → `editar_fecha_pago.php`
- Editar cliente → `editar.php`
- Eliminar cliente → `eliminar.php`
- Estado de cuenta PDF → `estado_cuenta_pdf.php`

---

### 5. `registrar_pago.php`
**Propósito**: Marca una cuota como pagada.

**Proceso:**
1. Recibe `pago_id` y `cliente_id`
2. Actualiza el pago:
   ```sql
   UPDATE pagos_clientes 
   SET estado = 'pagado', fecha_pago = CURDATE() 
   WHERE id = ? AND cliente_id = ?
   ```
3. **Verifica si el cliente finalizó todos sus pagos**
4. **Si finalizó, envía notificación por email automáticamente**
5. Redirige a `ver.php`

**Código de verificación de finalización:**
```php
// Verificar si quedan pagos pendientes
$query = "SELECT COUNT(*) as pendientes 
          FROM pagos_clientes 
          WHERE cliente_id = ? AND estado = 'pendiente'";

if ($pendientes == 0) {
    // Cliente finalizó - enviar notificación
    enviarNotificacionPagosFinalizados([$cliente], EMAIL_TO);
}
```

---

### 6. `dashboard.php`
**Propósito**: Panel de control con estadísticas y métricas del negocio.

**Estadísticas mostradas:**
1. **Total de clientes** (activos y finalizados)
2. **Dinero total prestado**
3. **Dinero cobrado** (suma de pagos realizados)
4. **Dinero pendiente** (suma de pagos por cobrar)
5. **Pagos próximos** (próximos 7 días)
6. **Pagos atrasados**
7. **Barra de progreso** de cobros
8. **Top 5 clientes** con mayor deuda

**Notificaciones:**
- 🚨 **Clientes con pagos atrasados** (hasta 10)
- 🎉 **Clientes que finalizaron** (últimos 30 días)

**Consultas principales:**
```sql
-- Total cobrado y pendiente
SELECT 
    SUM(CASE WHEN estado = 'pagado' THEN monto ELSE 0 END) as cobrado,
    SUM(CASE WHEN estado = 'pendiente' THEN monto ELSE 0 END) as pendiente
FROM pagos_clientes

-- Clientes con pagos atrasados
SELECT c.*, COUNT(pc.id) as pagos_atrasados, 
       SUM(pc.monto) as monto_total_atrasado
FROM clientes c
JOIN pagos_clientes pc ON c.id = pc.cliente_id
WHERE pc.estado = 'pendiente' AND pc.fecha_programada < CURDATE()
GROUP BY c.id
ORDER BY MIN(pc.fecha_programada) ASC
```

---

## 🔐 Sistema de Autenticación

### `login.php`
**Funcionalidad:**
1. Formulario de inicio de sesión
2. Validación de usuario y contraseña
3. Uso de `password_verify()` para verificar contraseñas hasheadas
4. Creación de sesión con datos del usuario

```php
$query = "SELECT * FROM usuarios WHERE usuario = ? AND activo = 1";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, 's', $usuario);
mysqli_stmt_execute($stmt);
$resultado = mysqli_stmt_get_result($stmt);

if ($row = mysqli_fetch_assoc($resultado)) {
    if (password_verify($contrasena, $row['contrasena'])) {
        // Iniciar sesión
        $_SESSION['usuario_id'] = $row['id'];
        $_SESSION['usuario'] = $row['usuario'];
        $_SESSION['tipo_usuario'] = $row['tipo_usuario'];
        header('Location: index.php');
    }
}
```

### Protección de páginas
Todas las páginas deben tener al inicio:
```php
<?php
include("conexion.php");

if (!isset($_SESSION['usuario'])) {
    header('Location: login.php');
    exit();
}
?>
```

### Restricción por tipo de usuario
```php
// Solo jefes pueden acceder
if ($_SESSION['tipo_usuario'] != 'jefe') {
    header('Location: index.php');
    exit();
}
```

---

## 💳 Sistema de Pagos

### Tipos de Frecuencia
- **Semanal**: Pagos cada 7 días
- **Quincenal**: Pagos cada 14 días
- **Mensual**: Pagos cada mes (mismo día)

### Cálculo de Fechas
```php
function calcularFechaPago($fecha_base, $frecuencia, $numero_cuota) {
    switch($frecuencia) {
        case 'semanal':
            return date('Y-m-d', strtotime("+{$numero_cuota} week", strtotime($fecha_base)));
        case 'quincenal':
            $semanas = $numero_cuota * 2;
            return date('Y-m-d', strtotime("+{$semanas} weeks", strtotime($fecha_base)));
        case 'mensual':
            return date('Y-m-d', strtotime("+{$numero_cuota} month", strtotime($fecha_base)));
    }
}
```

### Estados de Pago
1. **Pendiente Normal**: `estado = 'pendiente' AND fecha_programada >= HOY`
2. **Atrasado**: `estado = 'pendiente' AND fecha_programada < HOY`
3. **Pagado**: `estado = 'pagado'`
4. **Próximo**: `estado = 'pendiente' AND fecha_programada BETWEEN HOY AND +7 días`

### Acciones sobre Pagos

#### Registrar Pago (`registrar_pago.php`)
- Marca cuota como pagada
- Registra fecha de pago
- Verifica finalización del cliente
- Envía notificación si finalizó

#### Cancelar Pago (`cancelar_pago.php`)
- Revierte un pago registrado
- Vuelve el estado a 'pendiente'
- Limpia la fecha de pago

#### Editar Fecha (`editar_fecha_pago.php`)
- Permite cambiar la fecha programada
- Útil para reprogramar pagos

---

## 📧 Sistema de Notificaciones

### Tipos de Notificaciones

#### 1. Notificaciones de Pagos Atrasados (Diarias)
**Archivo**: `enviar_notificacion_atrasados.php`

**Cuándo se envía**: Automáticamente cada día a las 8:00 AM (configurado en Windows Task Scheduler)

**Contenido**:
- Lista de todos los clientes con pagos vencidos
- Cantidad de pagos atrasados por cliente
- Días de atraso
- Monto total adeudado

**Configuración**:
```
Windows Task Scheduler:
- Programa: C:\xampp\php\php.exe
- Argumentos: -f "C:\xampp\htdocs\Local_MV\enviar_notificacion_atrasados.php"
- Frecuencia: Diaria a las 8:00 AM
```

#### 2. Notificaciones de Pagos Finalizados (Instantáneas)
**Archivo**: `registrar_pago.php` (integrado)

**Cuándo se envía**: Automáticamente cuando se registra el último pago de un cliente

**Contenido**:
- Datos del cliente que finalizó
- Valor total cobrado
- Fecha de finalización

**Flujo automático**:
1. Empleado registra un pago
2. Sistema detecta que no quedan pagos pendientes
3. Envía email automáticamente (10-30 segundos)
4. Muestra mensaje: "🎉 ¡Cliente finalizado! Se envió notificación por email."

### Configuración de Email

**Archivo**: `email_config.php`

```php
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_SECURE', 'tls');
define('SMTP_USERNAME', 'Cristaldoericagraciela@gmail.com');
define('SMTP_PASSWORD', 'contraseña_de_aplicacion_aqui');
define('EMAIL_FROM', 'Cristaldoericagraciela@gmail.com');
define('EMAIL_TO', 'Cristaldoericagraciela@gmail.com'); // Email del jefe
```

**Requisitos**:
1. Cuenta de Gmail
2. Verificación en 2 pasos activada
3. Contraseña de aplicación generada

### Funciones de Envío

**Archivo**: `notificaciones_email.php`

```php
// Enviar notificación de pagos atrasados
enviarNotificacionPagosAtrasados($clientesAtrasados, $emailDestino);

// Enviar notificación de pagos finalizados
enviarNotificacionPagosFinalizados($clientesFinalizados, $emailDestino);
```

Ambas funciones generan HTML profesional con:
- Diseño responsive para móviles
- Colores distintivos (rojo/verde)
- Tablas con información completa
- Estadísticas resumidas

---

## 📊 Reportes y Exportación

### 1. Exportar a Excel (`exportar_excel.php`)
**Propósito**: Exporta la lista completa de clientes a Excel.

**Librerías necesarias**: PhpSpreadsheet

**Columnas exportadas**:
- ID, Nombre, Teléfono, Barrio, Dirección
- Artículos, Valor Total, Seña, Cuotas
- Frecuencia, Vendedor, Fecha Registro
- Total Cuotas, Pagadas, Pendientes, Atrasadas

**Uso**:
```php
// Generar archivo Excel
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Headers
$sheet->setCellValue('A1', 'ID');
$sheet->setCellValue('B1', 'Nombre');
// ... más columnas

// Datos
$fila = 2;
foreach($clientes as $cliente) {
    $sheet->setCellValue('A'.$fila, $cliente['id']);
    $sheet->setCellValue('B'.$fila, $cliente['nombre_completo']);
    // ... más datos
    $fila++;
}

// Descargar
$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
```

### 2. Estado de Cuenta PDF (`estado_cuenta_pdf.php`)
**Propósito**: Genera un PDF con el estado de cuenta de un cliente específico.

**Información incluida**:
- Datos del cliente
- Detalle de la compra
- Tabla de pagos (realizados y pendientes)
- Total pagado vs total pendiente
- Fecha de generación

**Librerías**: TCPDF o similar

---

## ⚙️ Configuración del Sistema

### Requisitos del Servidor
- PHP 7.4 o superior
- MySQL 5.7 o superior / MariaDB 10.3+
- Apache o Nginx con mod_rewrite
- Extensiones PHP:
  - mysqli
  - session
  - mbstring
  - openssl (para PHPMailer)

### Instalación

#### 1. Base de Datos
```bash
# Importar estructura
mysql -u root -p local_mv < SQL/estructura_completa.sql

# O instalación completa
mysql -u root -p < SQL/instalacion_completa.sql
```

#### 2. Configurar Conexión
Editar `conexion.php`:
```php
$host = 'localhost';
$usuario = 'root';
$contrasena = 'tu_contraseña';
$base_datos = 'local_mv';
```

#### 3. Configurar Email
Editar `email_config.php`:
```php
define('SMTP_USERNAME', 'tu_email@gmail.com');
define('SMTP_PASSWORD', 'contraseña_de_aplicacion');
define('EMAIL_TO', 'email_del_jefe@gmail.com');
```

#### 4. Crear Usuario Inicial
```sql
-- Contraseña: admin123
INSERT INTO usuarios (usuario, contrasena, tipo_usuario) 
VALUES ('admin', '$2y$10$hash_aqui', 'jefe');
```

O crear desde PHP:
```php
$contrasena = password_hash('admin123', PASSWORD_DEFAULT);
```

#### 5. Configurar Notificaciones Automáticas
**Windows**:
1. Abrir Task Scheduler (`taskschd.msc`)
2. Crear tarea básica:
   - Programa: `C:\xampp\php\php.exe`
   - Argumentos: `-f "C:\xampp\htdocs\Local_MV\enviar_notificacion_atrasados.php"`
   - Frecuencia: Diaria a las 8:00 AM

**Linux (crontab)**:
```bash
0 8 * * * /usr/bin/php /var/www/html/Local_MV/enviar_notificacion_atrasados.php
```

---

## 🔄 Flujo de Trabajo

### Flujo Completo: Desde Venta hasta Finalización

#### 1. Registrar Nueva Venta
**Archivo**: `index.php`

1. Empleado accede a la página principal
2. Completa formulario "Registrar Nuevo Cliente":
   - Datos personales (nombre, teléfono, barrio, dirección)
   - Artículos vendidos
   - Valor total de la compra
   - Seña/adelanto (opcional)
   - Cantidad de cuotas
   - Frecuencia de pago
   - Fecha del primer pago
   - Vendedor que hizo la venta
3. Sistema calcula automáticamente el monto por cuota
4. Al guardar → `guardar.php`

#### 2. Proceso de Guardado
**Archivo**: `guardar.php`

1. Valida los datos recibidos
2. Inserta el cliente en BD
3. **Genera automáticamente todas las cuotas**:
   - Calcula fechas según frecuencia
   - Distribuye el saldo en las cuotas
   - Crea registros en `pagos_clientes`
4. Redirige a `ver.php?id=X`

#### 3. Seguimiento del Cliente
**Archivo**: `ver.php`

1. Muestra toda la información del cliente
2. Tabla de pagos con estados:
   - ✅ Pagado (verde)
   - ⏳ Pendiente (amarillo)
   - ⚠️ Atrasado (rojo)
3. Acciones disponibles por cada cuota

#### 4. Registrar Pagos
**Archivo**: `registrar_pago.php`

Cuando el cliente paga:
1. Empleado hace clic en "Registrar Pago"
2. Sistema marca la cuota como pagada
3. Registra fecha de pago
4. **Si es el último pago**:
   - Envía email automático al jefe
   - Mensaje: "🎉 ¡Cliente finalizado!"
5. Vuelve a `ver.php`

#### 5. Notificaciones Diarias

**Cada día a las 8:00 AM**:
1. Se ejecuta `enviar_notificacion_atrasados.php`
2. Busca clientes con pagos atrasados
3. Genera email con lista completa
4. Envía al jefe

**Inmediatamente al finalizar**:
1. Se ejecuta desde `registrar_pago.php`
2. Genera email de celebración
3. Envía al jefe (10-30 segundos)

#### 6. Gestión desde Dashboard

**Archivo**: `dashboard.php`

El jefe puede:
1. Ver estadísticas generales
2. Revisar notificaciones de:
   - Pagos atrasados (con detalles)
   - Pagos finalizados (últimos 30 días)
3. Hacer clic en cualquier cliente para ver detalles
4. Exportar datos a Excel

---

## 👨‍💻 Guía para Desarrolladores

### Agregar Nueva Funcionalidad

#### Ejemplo: Agregar Campo "Email" a Clientes

**1. Modificar Base de Datos**
```sql
ALTER TABLE clientes ADD COLUMN email VARCHAR(100) AFTER telefono;
```

**2. Actualizar Formulario (`index.php`)**
```html
<label for="email" class="form-label">Email</label>
<input type="email" name="email" id="email" class="form-control mb-3" 
       placeholder="ejemplo@email.com" autocomplete="off">
```

**3. Modificar Guardado (`guardar.php`)**
```php
$email = mysqli_real_escape_string($conn, $_POST['email']);

$query = "INSERT INTO clientes (..., email) VALUES (..., ?)";
mysqli_stmt_bind_param($stmt, '...s', ..., $email);
```

**4. Actualizar Vistas**
- `ver.php`: Mostrar email
- `editar.php`: Permitir editar email
- `index.php`: Agregar columna email (opcional)

### Buenas Prácticas

#### 1. Seguridad
```php
// SIEMPRE usar prepared statements
$stmt = mysqli_prepare($conn, "SELECT * FROM clientes WHERE id = ?");
mysqli_stmt_bind_param($stmt, 'i', $id);

// SIEMPRE validar sesión
if (!isset($_SESSION['usuario'])) {
    header('Location: login.php');
    exit();
}

// SIEMPRE escapar salida HTML
echo htmlspecialchars($cliente['nombre_completo']);
```

#### 2. Manejo de Errores
```php
if (!$resultado) {
    error_log("Error en query: " . mysqli_error($conn));
    $_SESSION['message'] = 'Error al procesar la solicitud';
    header('Location: index.php');
    exit();
}
```

#### 3. Validación de Datos
```php
// Validar campos requeridos
if (empty($nombre) || empty($telefono)) {
    $_SESSION['message'] = 'Campos obligatorios faltantes';
    header('Location: index.php');
    exit();
}

// Validar tipos de datos
$valor_total = filter_var($_POST['valor_total'], FILTER_VALIDATE_FLOAT);
if ($valor_total === false || $valor_total <= 0) {
    $_SESSION['message'] = 'Valor total inválido';
    header('Location: index.php');
    exit();
}
```

#### 4. Transacciones (para operaciones múltiples)
```php
mysqli_begin_transaction($conn);

try {
    // Insertar cliente
    $query1 = "INSERT INTO clientes ...";
    mysqli_query($conn, $query1);
    
    // Insertar pagos
    $query2 = "INSERT INTO pagos_clientes ...";
    mysqli_query($conn, $query2);
    
    mysqli_commit($conn);
} catch (Exception $e) {
    mysqli_rollback($conn);
    error_log("Error: " . $e->getMessage());
}
```

### Estructura de Archivos Nuevos

#### Template básico para una página
```php
<?php
// 1. Conexión y sesión
include("conexion.php");

if (!isset($_SESSION['usuario'])) {
    header('Location: login.php');
    exit();
}

// 2. Procesar POST/GET
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Procesar datos
}

// 3. Consultas a BD
$query = "SELECT ...";
$resultado = mysqli_query($conn, $query);

// 4. Header
include("includes/header.php");
?>

<!-- 5. HTML -->
<main>
    <div class="container">
        <!-- Contenido -->
    </div>
</main>

<!-- 6. Scripts (si es necesario) -->
<script>
    // JavaScript
</script>

<?php
// 7. Cerrar conexión
mysqli_close($conn);
?>
```

### Debugging

#### Ver errores PHP
```php
// En desarrollo (agregar a conexion.php)
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

#### Log de consultas
```php
$query = "SELECT ...";
error_log("Query: " . $query); // Ver en php_error_log
$resultado = mysqli_query($conn, $query);
```

#### Ver datos de sesión
```php
echo '<pre>';
print_r($_SESSION);
echo '</pre>';
```

---

## 🐛 Problemas Comunes y Soluciones

### 1. "No se muestran los clientes"
**Causa**: Error en consulta SQL o conexión a BD

**Solución**:
```php
if (!$resultado) {
    echo "Error: " . mysqli_error($conn);
}
```

### 2. "No se envían los emails"
**Causas posibles**:
- Contraseña de aplicación incorrecta
- Verificación en 2 pasos no activada
- Puerto bloqueado por firewall

**Solución**:
1. Verificar `email_config.php`
2. Generar nueva contraseña de aplicación
3. Probar con: `php enviar_notificaciones_diarias.php`

### 3. "Cálculo de cuotas incorrecto"
**Causa**: Error en lógica de fechas o división

**Verificar**:
```php
$saldo = $valor_total - $sena;
$monto_cuota = $saldo / $cuotas;
echo "Saldo: $saldo, Cuotas: $cuotas, Monto: $monto_cuota";
```

### 4. "Widget de notificaciones no aparece"
**Causa**: Archivo no incluido o error en consultas

**Solución**:
- Verificar que `includes/notificaciones_widget.php` existe
- Revisar consola del navegador (F12) para errores JS

### 5. "Session perdida / logout automático"
**Causa**: `session_start()` no llamado o cookies deshabilitadas

**Solución**:
- Verificar que `conexion.php` tiene `session_start()`
- Revisar configuración de cookies del navegador

---

## 📚 Recursos Adicionales

### Documentos Relacionados
- `NOTIFICACIONES_README.md`: Sistema de notificaciones en dashboard
- `NOTIFICACIONES_EMAIL_README.md`: Configuración de emails automáticos
- `NOTIFICACIONES_INSTANTANEAS_INFO.md`: Notificaciones en tiempo real
- `SQL/README.md`: Información sobre scripts de base de datos

### Librerías Externas

#### PHPMailer
- **Propósito**: Envío de emails con SMTP
- **Documentación**: https://github.com/PHPMailer/PHPMailer
- **Versión**: 6.x

#### Bootstrap
- **Propósito**: Framework CSS para diseño responsive
- **Documentación**: https://getbootstrap.com/
- **Versión**: 5.3.2

### Referencias MySQL
- **Prepared Statements**: https://www.php.net/manual/es/mysqli.prepare.php
- **Transacciones**: https://www.php.net/manual/es/mysqli.begin-transaction.php

---

## 🚀 Roadmap de Mejoras (Opcional)

### Funcionalidades Sugeridas

1. **Recordatorios por WhatsApp**
   - Integración con WhatsApp Business API
   - Enviar recordatorios de pagos próximos

2. **App Móvil**
   - Versión nativa Android/iOS
   - Notificaciones push

3. **Dashboard Mejorado**
   - Gráficos con Chart.js
   - Análisis de tendencias
   - Proyecciones de cobro

4. **Gestión de Inventario**
   - Control de stock de artículos
   - Alertas de inventario bajo

5. **Comisiones para Vendedores**
   - Cálculo automático de comisiones
   - Reportes por vendedor

6. **Backup Automático**
   - Script para respaldar BD diariamente
   - Almacenamiento en la nube

7. **Multi-tienda**
   - Gestión de múltiples sucursales
   - Reportes consolidados

8. **Pagos Online**
   - Integración con Mercado Pago
   - PayPal, transferencias bancarias

---

## 📞 Contacto y Soporte

### Información del Proyecto
- **Nombre**: Sistema de Gestión de Créditos - Mujeres Virtuosas S.A
- **Versión**: 1.0
- **Fecha**: Noviembre 2025
- **Desarrollador Original**: [Tu nombre]

### Para el Nuevo Desarrollador

**Antes de empezar**:
1. Lee completamente esta documentación
2. Revisa los archivos en el orden sugerido
3. Instala el sistema en un entorno de prueba
4. Crea datos de prueba para familiarizarte

**Orden recomendado de lectura del código**:
1. `conexion.php` - Entender conexión y sesiones
2. `index.php` - Página principal y lógica general
3. `guardar.php` - Proceso de guardado y generación de cuotas
4. `ver.php` - Visualización de datos
5. `registrar_pago.php` - Lógica de pagos
6. `dashboard.php` - Estadísticas y reportes
7. `notificaciones_email.php` - Sistema de emails

**Archivos SQL importantes**:
- `SQL/estructura_completa.sql` - Para entender la BD
- `SQL/instalacion_completa.sql` - Para instalación limpia

**Preguntas frecuentes**:
- **¿Cómo funciona el cálculo de fechas?** Ver función en `guardar.php`
- **¿Cómo se detectan pagos atrasados?** Ver consultas en `dashboard.php`
- **¿Cómo se envían emails?** Ver `notificaciones_email.php`

---

## ✅ Checklist de Implementación

Para asegurar que todo funciona:

- [ ] Base de datos creada e importada
- [ ] Conexión a BD configurada (`conexion.php`)
- [ ] Usuario admin creado
- [ ] Login funcional
- [ ] Crear cliente de prueba
- [ ] Registrar pagos de prueba
- [ ] Verificar estados (pendiente, atrasado, pagado)
- [ ] Dashboard muestra estadísticas correctas
- [ ] Email configurado (`email_config.php`)
- [ ] Envío de emails de prueba funciona
- [ ] Notificaciones instantáneas al finalizar cliente
- [ ] Tarea programada para emails diarios configurada
- [ ] Exportar Excel funciona
- [ ] Generar PDF funciona
- [ ] Búsquedas y filtros funcionan
- [ ] Responsive en móviles
- [ ] Widget de notificaciones aparece

---

## 📝 Notas Finales

Este sistema fue diseñado específicamente para **Mujeres Virtuosas S.A**, una empresa que vende productos a crédito y necesita hacer seguimiento de pagos por cuotas.

**Características clave**:
- ✅ Simple y fácil de usar
- ✅ Notificaciones automáticas
- ✅ Seguimiento completo de pagos
- ✅ Responsive para usar en celular
- ✅ Reportes en Excel y PDF
- ✅ Control de empleados vendedores

**El código está comentado** en las secciones más importantes para facilitar su comprensión y modificación.

Si tienes dudas sobre alguna funcionalidad específica, revisa los comentarios en el código fuente o los otros archivos README incluidos en el proyecto.

---

**¡Éxito con el desarrollo!** 🚀

---

*Documentación creada: Noviembre 2025*  
*Última actualización: 25 de Noviembre de 2025*
