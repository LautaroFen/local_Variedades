# 🎨 Documentación de Diseño - Sistema Mujeres Virtuosas S.A

## 📋 Índice
1. [Introducción al Diseño](#introducción-al-diseño)
2. [Tecnologías de Frontend](#tecnologías-de-frontend)
3. [Sistema de Colores](#sistema-de-colores)
4. [Tipografía](#tipografía)
5. [Componentes de Bootstrap](#componentes-de-bootstrap)
6. [Estructura HTML](#estructura-html)
7. [CSS Personalizado](#css-personalizado)
8. [JavaScript y Interactividad](#javascript-y-interactividad)
9. [Diseño Responsive](#diseño-responsive)
10. [Animaciones y Efectos](#animaciones-y-efectos)
11. [Guía de Modificación](#guía-de-modificación)
12. [Recursos y Referencias](#recursos-y-referencias)

---

## 🎨 Introducción al Diseño

### Concepto Visual
El diseño del sistema está basado en un estilo **moderno, limpio y profesional** con:
- Gradientes vibrantes (morado/azul)
- Cards con sombras y efectos hover
- Diseño responsive mobile-first
- Iconos y emojis para mejor UX
- Colores semánticos (rojo=urgente, verde=éxito, amarillo=pendiente)

### Filosofía de Diseño
1. **Claridad**: Información fácil de leer y entender
2. **Accesibilidad**: Contrastes adecuados, textos legibles
3. **Consistencia**: Mismos estilos en todas las páginas
4. **Responsive**: Funciona en desktop, tablet y móvil
5. **Profesional**: Transmite confianza y seriedad

---

## 🛠️ Tecnologías de Frontend

### 1. Bootstrap 5.3.2
**Framework CSS principal para el diseño**

**CDN utilizado:**
```html
<!-- CSS de Bootstrap -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" 
      rel="stylesheet" 
      integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" 
      crossorigin="anonymous">

<!-- JavaScript de Bootstrap (incluye Popper.js) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" 
        integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" 
        crossorigin="anonymous"></script>
```

**Ubicación**: `includes/header.php` (línea 8-9)

**Componentes de Bootstrap usados:**
- Grid System (container, row, col)
- Cards
- Forms (inputs, selects, textareas)
- Buttons
- Alerts
- Tables
- Badges
- Dropdowns
- Modals (si se necesitan)
- Navbar
- Progress bars

### 2. Google Fonts - Inter
**Fuente principal del sistema**

```html
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" 
      rel="stylesheet">
```

**Pesos usados:**
- 300: Light (textos secundarios)
- 400: Regular (texto normal)
- 500: Medium (subtítulos)
- 600: SemiBold (títulos pequeños)
- 700: Bold (títulos principales)
- 800: ExtraBold (números grandes, estadísticas)

### 3. CSS Personalizado
**Archivo**: `includes/styles.css` (1134 líneas)

Contiene:
- Variables CSS custom
- Estilos de cards
- Efectos hover
- Animaciones
- Estilos de tablas
- Componentes personalizados
- Media queries

### 4. JavaScript Personalizado
**Archivo**: `includes/app.js`

Funciones:
- Validaciones de formularios
- Cálculos automáticos
- Interacciones dinámicas
- Manejo de eventos

---

## 🎨 Sistema de Colores

### Paleta Principal
Definida en `includes/styles.css` (líneas 6-19)

```css
:root {
    /* Colores principales */
    --primary-color: #2563eb;      /* Azul primario */
    --secondary-color: #7c3aed;    /* Morado/violeta */
    --success-color: #10b981;      /* Verde éxito */
    --danger-color: #ef4444;       /* Rojo peligro/urgente */
    --warning-color: #f59e0b;      /* Amarillo advertencia */
    --info-color: #06b6d4;         /* Cyan información */
    --dark-color: #1f2937;         /* Gris oscuro */
    --light-color: #f9fafb;        /* Gris claro */
    --white: #ffffff;              /* Blanco */
    
    /* Gradientes */
    --gradient-primary: linear-gradient(135deg, #2563eb 0%, #7c3aed 100%);
    --gradient-success: linear-gradient(135deg, #10b981 0%, #059669 100%);
    --gradient-warning: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
}
```

### Uso de Colores por Contexto

#### 🔵 Azul (`#2563eb`)
- Botones de acción principal
- Links importantes
- Headers de cards
- Iconos informativos

**Ejemplo:**
```html
<button class="btn btn-primary">Buscar</button>
```

#### 🟣 Morado (`#7c3aed`)
- Gradientes de fondo
- Acentos secundarios
- Badges especiales

**Ejemplo:**
```html
<div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
```

#### 🟢 Verde (`#10b981`)
- Botones de guardar/confirmar
- Pagos realizados
- Clientes finalizados
- Indicadores de éxito

**Ejemplo:**
```html
<span class="badge bg-success">✅ Pagado</span>
```

#### 🔴 Rojo (`#ef4444`)
- Pagos atrasados
- Acciones de eliminar
- Alertas urgentes
- Errores

**Ejemplo:**
```html
<div class="alert alert-danger">⚠️ Pago atrasado</div>
```

#### 🟡 Amarillo (`#f59e0b`)
- Pagos pendientes
- Advertencias
- Estados intermedios

**Ejemplo:**
```html
<span class="badge bg-warning text-dark">⏳ Pendiente</span>
```

#### 🔵 Cyan (`#06b6d4`)
- Información general
- Estadísticas
- Links secundarios

**Ejemplo:**
```html
<button class="btn btn-info">📊 Dashboard</button>
```

---

## 📝 Tipografía

### Jerarquía de Texto

#### Títulos Principales (H1)
```css
h1 {
    font-size: 2.5rem;      /* 40px */
    font-weight: 700;       /* Bold */
    margin-bottom: 1.5rem;
    color: #1f2937;
}
```

**Uso:**
```html
<h1 class="mb-4">📊 Dashboard</h1>
```

#### Títulos Secundarios (H2-H6)
```css
h2 { font-size: 2rem; }      /* 32px */
h3 { font-size: 1.75rem; }   /* 28px */
h4 { font-size: 1.5rem; }    /* 24px */
h5 { font-size: 1.25rem; }   /* 20px */
h6 { font-size: 1rem; }      /* 16px */
```

#### Texto Normal
```css
body {
    font-family: 'Inter', 'Segoe UI', sans-serif;
    font-size: 1rem;        /* 16px */
    line-height: 1.6;
    color: #333;
}
```

#### Texto Pequeño
```html
<small class="text-muted">Información adicional</small>
```

### Clases de Bootstrap para Texto

```html
<!-- Alineación -->
<p class="text-start">Izquierda</p>
<p class="text-center">Centro</p>
<p class="text-end">Derecha</p>

<!-- Peso -->
<span class="fw-light">Light (300)</span>
<span class="fw-normal">Normal (400)</span>
<span class="fw-bold">Bold (700)</span>

<!-- Tamaño -->
<p class="fs-1">Muy grande</p>
<p class="fs-6">Pequeño</p>

<!-- Color -->
<p class="text-primary">Azul</p>
<p class="text-success">Verde</p>
<p class="text-danger">Rojo</p>
<p class="text-muted">Gris</p>
```

---

## 🧩 Componentes de Bootstrap

### 1. Sistema de Grid

#### Container
```html
<!-- Container responsive (max-width según breakpoint) -->
<div class="container">
    <!-- Contenido -->
</div>

<!-- Container full-width -->
<div class="container-fluid">
    <!-- Contenido -->
</div>
```

#### Filas y Columnas
```html
<div class="row">
    <!-- 2 columnas iguales en desktop -->
    <div class="col-md-6">Columna 1</div>
    <div class="col-md-6">Columna 2</div>
</div>

<div class="row">
    <!-- 3 columnas iguales -->
    <div class="col-lg-4">Columna 1</div>
    <div class="col-lg-4">Columna 2</div>
    <div class="col-lg-4">Columna 3</div>
</div>

<div class="row g-4">
    <!-- g-4 = gap de 1.5rem entre columnas -->
    <div class="col-12 col-md-6 col-lg-3">Card 1</div>
    <div class="col-12 col-md-6 col-lg-3">Card 2</div>
    <div class="col-12 col-md-6 col-lg-3">Card 3</div>
    <div class="col-12 col-md-6 col-lg-3">Card 4</div>
</div>
```

**Breakpoints de Bootstrap:**
- `xs`: < 576px (móviles)
- `sm`: ≥ 576px (móviles grandes)
- `md`: ≥ 768px (tablets)
- `lg`: ≥ 992px (desktop pequeño)
- `xl`: ≥ 1200px (desktop grande)
- `xxl`: ≥ 1400px (desktop extra grande)

### 2. Cards

#### Card Básica
```html
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">Título de la Card</h5>
    </div>
    <div class="card-body">
        <p class="card-text">Contenido de la card</p>
    </div>
    <div class="card-footer">
        <button class="btn btn-primary">Acción</button>
    </div>
</div>
```

#### Card con Gradiente (Usado en Dashboard)
```html
<div class="stat-card hover-lift" 
     style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
    <div class="stat-icon">👥</div>
    <div class="stat-value">150</div>
    <div class="stat-label">Total Clientes</div>
</div>
```

**CSS de stat-card** (`includes/styles.css` líneas 69-93):
```css
.stat-card {
    border-radius: var(--border-radius);
    padding: 1.5rem;
    color: var(--white);
    position: relative;
    overflow: hidden;
}

.stat-card .stat-value {
    font-size: 2.5rem;
    font-weight: 700;
    margin: 0;
}

.stat-card .stat-label {
    font-size: 0.95rem;
    opacity: 0.9;
    margin-top: 0.5rem;
}
```

#### Card con Hover Effect
```html
<div class="card hover-lift">
    <!-- Contenido -->
</div>
```

**CSS:**
```css
.hover-lift {
    transition: all 0.3s ease;
}

.hover-lift:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 25px rgba(0,0,0,0.15);
}
```

### 3. Formularios

#### Estructura Básica
```html
<form action="guardar.php" method="POST">
    <div class="mb-3">
        <label for="nombre" class="form-label">Nombre completo</label>
        <input type="text" 
               name="nombre" 
               id="nombre" 
               class="form-control" 
               placeholder="Juan Pérez"
               required>
    </div>
    
    <div class="mb-3">
        <label for="email" class="form-label">Email</label>
        <input type="email" 
               name="email" 
               id="email" 
               class="form-control">
    </div>
    
    <div class="mb-3">
        <label for="mensaje" class="form-label">Mensaje</label>
        <textarea name="mensaje" 
                  id="mensaje" 
                  class="form-control" 
                  rows="3"></textarea>
    </div>
    
    <button type="submit" class="btn btn-primary">Enviar</button>
</form>
```

#### Select/Dropdown
```html
<select name="frecuencia" class="form-select" required>
    <option value="">Seleccione frecuencia</option>
    <option value="semanal">Semanal</option>
    <option value="quincenal">Quincenal</option>
    <option value="mensual">Mensual</option>
</select>
```

#### Input con Validación
```html
<input type="number" 
       name="valor_total" 
       id="valor_total" 
       class="form-control" 
       min="1" 
       step="0.01" 
       required 
       title="Ingrese el valor total">
```

### 4. Botones

#### Estilos de Botones
```html
<!-- Primario (azul) -->
<button class="btn btn-primary">Acción Principal</button>

<!-- Éxito (verde) -->
<button class="btn btn-success">Guardar</button>

<!-- Peligro (rojo) -->
<button class="btn btn-danger">Eliminar</button>

<!-- Advertencia (amarillo) -->
<button class="btn btn-warning">Advertencia</button>

<!-- Información (cyan) -->
<button class="btn btn-info">Info</button>

<!-- Secundario (gris) -->
<button class="btn btn-secondary">Cancelar</button>

<!-- Outline (solo borde) -->
<button class="btn btn-outline-primary">Outline</button>
```

#### Tamaños de Botones
```html
<button class="btn btn-primary btn-sm">Pequeño</button>
<button class="btn btn-primary">Normal</button>
<button class="btn btn-primary btn-lg">Grande</button>
```

#### Botones con Iconos/Emojis
```html
<button class="btn btn-primary">
    <strong>💾 Guardar Cliente</strong>
</button>

<button class="btn btn-success">
    ✅ Confirmar
</button>
```

#### Grupos de Botones
```html
<div class="btn-group" role="group">
    <button class="btn btn-primary">Ver</button>
    <button class="btn btn-warning">Editar</button>
    <button class="btn btn-danger">Eliminar</button>
</div>
```

### 5. Tablas

#### Tabla Básica
```html
<div class="table-responsive">
    <table class="table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Nombre</th>
                <th>Teléfono</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>1</td>
                <td>Juan Pérez</td>
                <td>1122334455</td>
                <td>
                    <button class="btn btn-sm btn-primary">Ver</button>
                </td>
            </tr>
        </tbody>
    </table>
</div>
```

#### Tabla con Estilos Adicionales
```html
<table class="table table-striped table-hover">
    <!-- Contenido -->
</table>
```

**Clases útiles:**
- `table-striped`: Filas zebra (alternadas)
- `table-hover`: Efecto hover en filas
- `table-bordered`: Con bordes
- `table-sm`: Más compacta
- `table-responsive`: Scroll horizontal en móviles

### 6. Alerts (Alertas)

```html
<!-- Información -->
<div class="alert alert-info">
    <strong>ℹ️ Información:</strong> Mensaje informativo
</div>

<!-- Éxito -->
<div class="alert alert-success">
    <strong>✅ Éxito:</strong> Operación completada
</div>

<!-- Advertencia -->
<div class="alert alert-warning">
    <strong>⚠️ Advertencia:</strong> Ten cuidado
</div>

<!-- Peligro -->
<div class="alert alert-danger">
    <strong>❌ Error:</strong> Algo salió mal
</div>

<!-- Con botón de cerrar -->
<div class="alert alert-info alert-dismissible fade show">
    Mensaje con botón de cerrar
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
```

### 7. Badges

```html
<!-- Colores -->
<span class="badge bg-primary">Primario</span>
<span class="badge bg-success">Éxito</span>
<span class="badge bg-danger">Peligro</span>
<span class="badge bg-warning text-dark">Advertencia</span>
<span class="badge bg-info">Info</span>

<!-- Estados de pago -->
<span class="badge bg-success">✅ Pagado</span>
<span class="badge bg-warning text-dark">⏳ Pendiente</span>
<span class="badge bg-danger">⚠️ Atrasado</span>

<!-- Contador -->
<button class="btn btn-primary">
    Notificaciones <span class="badge bg-danger">5</span>
</button>
```

### 8. Navbar (Menú de Navegación)

**Ubicación**: `includes/header.php`

```html
<nav class="navbar navbar-expand-lg navbar-dark" 
     style="background: linear-gradient(135deg, #1f2937 0%, #111827 100%);">
    <div class="container-fluid">
        <!-- Logo y nombre -->
        <a href="index.php" class="navbar-brand">
            <img src="includes/logo.jpg" alt="Logo" style="height:48px;">
            <span>Mujeres Virtuosas S.A</span>
        </a>
        
        <!-- Botón hamburguesa (móvil) -->
        <button class="navbar-toggler" 
                type="button" 
                data-bs-toggle="collapse" 
                data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <!-- Menú colapsable -->
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a href="dashboard.php" class="btn btn-sm btn-primary">
                        📊 Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a href="logout.php" class="btn btn-sm btn-danger">
                        🚪 Salir
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>
```

### 9. Dropdown (Menú Desplegable)

```html
<div class="dropdown">
    <button class="btn btn-secondary dropdown-toggle" 
            type="button" 
            data-bs-toggle="dropdown">
        Acciones
    </button>
    <ul class="dropdown-menu">
        <li><a class="dropdown-item" href="#">Ver</a></li>
        <li><a class="dropdown-item" href="#">Editar</a></li>
        <li><hr class="dropdown-divider"></li>
        <li><a class="dropdown-item text-danger" href="#">Eliminar</a></li>
    </ul>
</div>
```

### 10. Progress Bar

**Usado en**: `dashboard.php` para mostrar progreso de cobros

```html
<div class="progress">
    <div class="progress-bar" 
         role="progressbar" 
         style="width: 65%; background: linear-gradient(90deg, #10b981 0%, #059669 100%);"
         aria-valuenow="65" 
         aria-valuemin="0" 
         aria-valuemax="100">
        <strong>65% Cobrado</strong>
    </div>
    <div class="progress-bar" 
         role="progressbar" 
         style="width: 35%; background: linear-gradient(90deg, #f59e0b 0%, #d97706 100%);"
         aria-valuenow="35" 
         aria-valuemin="0" 
         aria-valuemax="100">
        <strong>35% Pendiente</strong>
    </div>
</div>
```

---

## 🏗️ Estructura HTML

### Layout Principal

#### Header (`includes/header.php`)
```html
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- Bootstrap CSS -->
    <link href="bootstrap.css" rel="stylesheet">
    
    <!-- Google Fonts -->
    <link href="google-fonts-inter.css" rel="stylesheet">
    
    <!-- CSS Personalizado -->
    <link rel="stylesheet" href="includes/styles.css">
    
    <!-- JavaScript -->
    <script src="includes/app.js" defer></script>
    
    <title>Mujeres Virtuosas - Sistema</title>
</head>
<body>
    <!-- Navbar aquí -->
</body>
```

#### Estructura de Página Típica
```html
<?php include("includes/header.php"); ?>

<main>
    <div class="container p-4">
        <!-- Título -->
        <div class="row mb-3">
            <div class="col-12">
                <h1>📊 Título de la Página</h1>
            </div>
        </div>
        
        <!-- Contenido -->
        <div class="row">
            <div class="col-12">
                <!-- Cards, tablas, formularios -->
            </div>
        </div>
    </div>
</main>

<!-- Scripts adicionales si es necesario -->
<script>
    // JavaScript específico de la página
</script>
```

### Espaciado con Utilities de Bootstrap

```html
<!-- Margin -->
<div class="m-3">Margin en todos lados (1rem)</div>
<div class="mt-4">Margin top (1.5rem)</div>
<div class="mb-2">Margin bottom (0.5rem)</div>
<div class="mx-auto">Margin horizontal auto (centrar)</div>

<!-- Padding -->
<div class="p-4">Padding en todos lados</div>
<div class="pt-3">Padding top</div>
<div class="px-2">Padding horizontal</div>

<!-- Tamaños: 0, 1, 2, 3, 4, 5 -->
<!-- 0 = 0, 1 = 0.25rem, 2 = 0.5rem, 3 = 1rem, 4 = 1.5rem, 5 = 3rem -->
```

---

## 🎨 CSS Personalizado

### Archivo: `includes/styles.css`

#### Variables CSS (Root)
```css
:root {
    /* Colores */
    --primary-color: #2563eb;
    --secondary-color: #7c3aed;
    
    /* Sombras */
    --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
    --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
    
    /* Bordes */
    --border-radius: 12px;
    --border-radius-sm: 8px;
    --border-radius-lg: 16px;
}
```

#### Estilos del Body
```css
body {
    font-family: 'Inter', sans-serif;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    min-height: 100vh;
    display: flex;
    flex-direction: column;
}

main {
    flex: 1 0 auto;
}
```

#### Cards Mejoradas
```css
.card {
    border: none;
    border-radius: var(--border-radius);
    box-shadow: var(--shadow-md);
    transition: all 0.3s ease;
}

.card:hover {
    box-shadow: var(--shadow-lg);
    transform: translateY(-2px);
}

.card-header {
    background: var(--gradient-primary);
    color: var(--white);
    border-radius: var(--border-radius) var(--border-radius) 0 0 !important;
    padding: 1.25rem 1.5rem;
    border: none;
}
```

#### Efectos Hover
```css
.hover-lift {
    transition: all 0.3s ease;
}

.hover-lift:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 25px rgba(0,0,0,0.15);
}

.btn-hover-shine {
    position: relative;
    overflow: hidden;
}

.btn-hover-shine::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: rgba(255,255,255,0.3);
    transition: left 0.5s;
}

.btn-hover-shine:hover::before {
    left: 100%;
}
```

#### Tablas Personalizadas
```css
.table thead {
    background: var(--gradient-primary);
    color: var(--white);
}

.table thead th {
    border: none;
    padding: 1rem;
    font-weight: 600;
    text-transform: uppercase;
    font-size: 0.85rem;
}

.table tbody tr {
    transition: all 0.2s ease;
}

.table tbody tr:hover {
    background-color: rgba(37, 99, 235, 0.05);
    transform: scale(1.01);
}
```

#### Scrollbar Personalizado
```css
.table-responsive::-webkit-scrollbar {
    height: 8px;
    width: 8px;
}

.table-responsive::-webkit-scrollbar-track {
    background: #f1f5f9;
    border-radius: 10px;
}

.table-responsive::-webkit-scrollbar-thumb {
    background: var(--primary-color);
    border-radius: 10px;
}

.table-responsive::-webkit-scrollbar-thumb:hover {
    background: var(--secondary-color);
}
```

---

## 💻 JavaScript y Interactividad

### Funciones Principales en `index.php`

#### 1. Cálculo Automático de Cuotas
```javascript
function calcularMontoCuota() {
    const valorTotal = parseFloat(document.getElementById('valor_total').value) || 0;
    const sena = parseFloat(document.getElementById('sena').value) || 0;
    const cuotas = parseInt(document.getElementById('cuotas').value) || 1;
    
    // Validar que la seña no sea mayor al valor total
    if (sena > valorTotal) {
        document.getElementById('sena').value = valorTotal;
        return;
    }
    
    const saldoRestante = valorTotal - sena;
    
    if (saldoRestante > 0 && cuotas > 0) {
        const montoCuota = saldoRestante / cuotas;
        document.getElementById('monto-cuota').textContent = 
            '$' + montoCuota.toLocaleString('es-AR', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        document.getElementById('info-cuota').style.display = 'block';
    } else {
        document.getElementById('info-cuota').style.display = 'none';
    }
}

// Eventos
document.getElementById('valor_total').addEventListener('input', calcularMontoCuota);
document.getElementById('sena').addEventListener('input', calcularMontoCuota);
document.getElementById('cuotas').addEventListener('input', calcularMontoCuota);
```

#### 2. Validación de Campos Numéricos
```javascript
function allowOnlyNumbers(e) {
    var key = e.key;
    if (e.ctrlKey || e.metaKey || key.length > 1) return;
    if (!/\d/.test(key)) {
        e.preventDefault();
    }
}

document.getElementById('telefono').addEventListener('keypress', allowOnlyNumbers);
```

#### 3. Validación de Campos de Texto
```javascript
function allowOnlyLetters(e) {
    var key = e.key;
    if (e.ctrlKey || e.metaKey || key.length > 1) return;
    if (!/[A-Za-zÀ-ÖØ-öø-ÿ\s]/.test(key)) {
        e.preventDefault();
    }
}

document.getElementById('nombre_completo').addEventListener('keypress', allowOnlyLetters);
```

### Widget de Notificaciones
**Archivo**: `includes/notificaciones_widget.php`

```javascript
function toggleNotifications() {
    const widget = document.getElementById('notificationWidget');
    widget.classList.toggle('active');
}

// Cerrar al hacer clic fuera
document.addEventListener('click', function(event) {
    const widget = document.getElementById('notificationWidget');
    if (widget && !widget.contains(event.target)) {
        widget.classList.remove('active');
    }
});
```

### Bootstrap JavaScript

#### Collapse (para filtros avanzados)
```html
<!-- Botón -->
<button class="btn btn-outline-primary" 
        type="button" 
        data-bs-toggle="collapse" 
        data-bs-target="#filtrosAvanzados">
    🎯 Mostrar/Ocultar Filtros
</button>

<!-- Contenido colapsable -->
<div class="collapse" id="filtrosAvanzados">
    <!-- Contenido de filtros -->
</div>
```

#### Modal
```html
<!-- Botón que abre modal -->
<button type="button" 
        class="btn btn-primary" 
        data-bs-toggle="modal" 
        data-bs-target="#miModal">
    Abrir Modal
</button>

<!-- Modal -->
<div class="modal fade" id="miModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Título</h5>
                <button type="button" 
                        class="btn-close" 
                        data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                Contenido del modal
            </div>
            <div class="modal-footer">
                <button type="button" 
                        class="btn btn-secondary" 
                        data-bs-dismiss="modal">Cerrar</button>
                <button type="button" 
                        class="btn btn-primary">Guardar</button>
            </div>
        </div>
    </div>
</div>
```

---

## 📱 Diseño Responsive

### Mobile-First Approach
El sistema está diseñado primero para móviles y luego se adapta a pantallas más grandes.

### Breakpoints y Adaptaciones

#### Navegación Móvil
**Archivo**: `includes/header.php`

```css
@media (max-width: 768px) {
    .navbar-collapse {
        background: rgba(0, 0, 0, 0.9);
        padding: 1rem;
        border-radius: 8px;
        margin-top: 1rem;
    }
    
    .navbar-nav .nav-item {
        width: 100%;
        margin-bottom: 0.5rem;
    }
    
    .navbar-nav .btn {
        width: 100%;
        text-align: left;
    }
}
```

#### Tablas en Móvil
```css
@media (max-width: 768px) {
    .table-responsive {
        font-size: 0.85rem;
    }
    
    .table td, .table th {
        padding: 0.5rem;
    }
    
    /* Ocultar columnas menos importantes en móvil */
    .table .d-none.d-md-table-cell {
        display: none !important;
    }
}
```

**HTML:**
```html
<table class="table">
    <thead>
        <tr>
            <th>Nombre</th>
            <th class="d-none d-md-table-cell">Teléfono</th>
            <th class="d-none d-lg-table-cell">Barrio</th>
            <th>Acciones</th>
        </tr>
    </thead>
</table>
```

#### Cards en Móvil
```css
@media (max-width: 768px) {
    .card {
        margin-bottom: 1rem;
    }
    
    .card-body {
        padding: 1rem;
    }
    
    /* Estadísticas en columna */
    .stat-card {
        margin-bottom: 1rem;
    }
}
```

#### Formularios Responsivos
```html
<div class="row g-2">
    <!-- En móvil: 1 columna -->
    <!-- En tablet: 2 columnas -->
    <!-- En desktop: 3 columnas -->
    <div class="col-12 col-md-6 col-lg-4">
        <input type="text" class="form-control">
    </div>
</div>
```

### Utilities Responsive de Bootstrap
```html
<!-- Mostrar/ocultar según tamaño -->
<span class="d-none d-sm-inline">Texto completo</span>
<span class="d-inline d-sm-none">Texto corto</span>

<!-- Ejemplos de visibilidad -->
<div class="d-none">Oculto en todos</div>
<div class="d-block">Visible en todos</div>
<div class="d-none d-md-block">Visible desde tablet</div>
<div class="d-block d-lg-none">Visible hasta desktop</div>
```

---

## ✨ Animaciones y Efectos

### Animaciones CSS

#### Fade In
```css
@keyframes fadeIn {
    from {
        opacity: 0;
    }
    to {
        opacity: 1;
    }
}

.animate-fade-in {
    animation: fadeIn 0.5s ease-in;
}
```

#### Slide In
```css
@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.animate-slide-in {
    animation: slideIn 0.5s ease-out;
}
```

#### Slide Down
```css
@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.animate-slide-down {
    animation: slideDown 0.3s ease;
}
```

#### Pulse (para notificaciones)
```css
@keyframes pulse {
    0%, 100% {
        transform: scale(1);
        opacity: 1;
    }
    50% {
        transform: scale(1.1);
        opacity: 0.8;
    }
}

.notification-badge {
    animation: pulse 2s infinite;
}
```

### Transiciones Suaves

#### Botones
```css
.btn {
    transition: all 0.3s ease;
}

.btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.2);
}
```

#### Cards
```css
.card {
    transition: all 0.3s ease;
}

.card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 25px rgba(0,0,0,0.15);
}
```

#### Links
```css
a {
    transition: color 0.2s ease;
}

a:hover {
    color: var(--primary-color);
}
```

---

## 🛠️ Guía de Modificación

### Cambiar Colores Principales

#### 1. Modificar Variables CSS
**Archivo**: `includes/styles.css` (líneas 6-19)

```css
:root {
    --primary-color: #ff6b6b;      /* Cambiar a rojo */
    --secondary-color: #4ecdc4;    /* Cambiar a turquesa */
    --success-color: #95e1d3;      /* Verde claro */
}
```

#### 2. Actualizar Gradientes
```css
:root {
    --gradient-primary: linear-gradient(135deg, #ff6b6b 0%, #f06595 100%);
}

body {
    background: linear-gradient(135deg, #ff6b6b 0%, #f06595 100%);
}
```

### Cambiar Fuente Tipográfica

#### 1. Importar Nueva Fuente
**Archivo**: `includes/header.php`

```html
<!-- Cambiar Inter por Roboto -->
<link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700;900&display=swap" 
      rel="stylesheet">
```

#### 2. Actualizar CSS
```css
body {
    font-family: 'Roboto', 'Arial', sans-serif;
}
```

### Modificar Tamaños de Cards

```css
.stat-card .stat-value {
    font-size: 3rem;        /* Números más grandes */
}

.card {
    padding: 2rem;          /* Más padding */
}
```

### Cambiar Bordes Redondeados

```css
:root {
    --border-radius: 20px;    /* Más redondeado */
    --border-radius-sm: 10px;
}

/* O bordes cuadrados */
.card {
    border-radius: 0;
}

.btn {
    border-radius: 0;
}
```

### Agregar Modo Oscuro (Dark Mode)

#### 1. Crear Variables para Dark Mode
```css
:root {
    --bg-primary: #ffffff;
    --text-primary: #1f2937;
}

[data-theme="dark"] {
    --bg-primary: #1f2937;
    --text-primary: #ffffff;
}

body {
    background-color: var(--bg-primary);
    color: var(--text-primary);
}
```

#### 2. Toggle JavaScript
```javascript
function toggleDarkMode() {
    const html = document.documentElement;
    const currentTheme = html.getAttribute('data-theme');
    const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
    html.setAttribute('data-theme', newTheme);
    localStorage.setItem('theme', newTheme);
}

// Cargar tema guardado
document.addEventListener('DOMContentLoaded', function() {
    const savedTheme = localStorage.getItem('theme') || 'light';
    document.documentElement.setAttribute('data-theme', savedTheme);
});
```

#### 3. Botón de Toggle
```html
<button onclick="toggleDarkMode()" class="btn btn-outline-secondary">
    🌙 Modo Oscuro
</button>
```

### Personalizar Navbar

#### Cambiar Color
```html
<!-- Navbar oscuro -->
<nav class="navbar navbar-dark bg-dark">

<!-- Navbar claro -->
<nav class="navbar navbar-light bg-light">

<!-- Navbar con gradiente personalizado -->
<nav class="navbar navbar-dark" 
     style="background: linear-gradient(to right, #ff6b6b, #f06595);">
```

#### Navbar Transparente
```css
.navbar {
    background: transparent !important;
    backdrop-filter: blur(10px);
}
```

### Modificar Tablas

#### Tabla Compacta
```html
<table class="table table-sm">
    <!-- Contenido -->
</table>
```

#### Tabla sin Bordes
```html
<table class="table table-borderless">
    <!-- Contenido -->
</table>
```

#### Colores Alternos Personalizados
```css
.table-striped tbody tr:nth-of-type(odd) {
    background-color: rgba(37, 99, 235, 0.05);
}
```

### Agregar Iconos (Font Awesome)

#### 1. Incluir Font Awesome
```html
<!-- En includes/header.php -->
<link rel="stylesheet" 
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
```

#### 2. Usar Iconos
```html
<button class="btn btn-primary">
    <i class="fas fa-save"></i> Guardar
</button>

<a href="#" class="btn btn-danger">
    <i class="fas fa-trash"></i> Eliminar
</a>
```

### Personalizar Alertas

```css
.alert {
    border-left: 4px solid;
    border-radius: 8px;
}

.alert-success {
    border-left-color: #10b981;
    background: linear-gradient(to right, #f0fdf4, #ffffff);
}

.alert-danger {
    border-left-color: #ef4444;
    background: linear-gradient(to right, #fef2f2, #ffffff);
}
```

---

## 📚 Recursos y Referencias

### Documentación Oficial

#### Bootstrap 5.3
- **Sitio oficial**: https://getbootstrap.com/
- **Documentación**: https://getbootstrap.com/docs/5.3/getting-started/introduction/
- **Ejemplos**: https://getbootstrap.com/docs/5.3/examples/
- **Componentes**: https://getbootstrap.com/docs/5.3/components/alerts/
- **Utilities**: https://getbootstrap.com/docs/5.3/utilities/spacing/

#### Google Fonts
- **Catálogo**: https://fonts.google.com/
- **Inter Font**: https://fonts.google.com/specimen/Inter

#### CSS Tricks
- **Flexbox Guide**: https://css-tricks.com/snippets/css/a-guide-to-flexbox/
- **Grid Guide**: https://css-tricks.com/snippets/css/complete-guide-grid/

### Herramientas Útiles

#### Generadores de Color
- **Coolors**: https://coolors.co/ (paletas de colores)
- **Color Hunt**: https://colorhunt.co/ (inspiración)
- **Adobe Color**: https://color.adobe.com/ (rueda de color)

#### Generadores de Gradientes
- **CSS Gradient**: https://cssgradient.io/
- **UI Gradients**: https://uigradients.com/

#### Iconos
- **Font Awesome**: https://fontawesome.com/
- **Bootstrap Icons**: https://icons.getbootstrap.com/
- **Heroicons**: https://heroicons.com/
- **Emojis**: https://emojipedia.org/

#### Sombras y Efectos
- **Box Shadow Generator**: https://shadows.brumm.af/
- **Neumorphism**: https://neumorphism.io/

### Inspiración de Diseño
- **Dribbble**: https://dribbble.com/ (diseños creativos)
- **Awwwards**: https://www.awwwards.com/ (mejores sitios web)
- **Bootstrap Themes**: https://themes.getbootstrap.com/

### Tipografía
- **Google Fonts Pairing**: https://fontpair.co/
- **Type Scale**: https://typescale.com/ (escalas tipográficas)

---

## 📋 Checklist de Personalización

Para modificar el diseño del sistema:

### Básico
- [ ] Cambiar colores principales (`includes/styles.css`)
- [ ] Modificar logo (`includes/logo.jpg`)
- [ ] Actualizar fuente tipográfica (`includes/header.php`)
- [ ] Ajustar tamaños de texto
- [ ] Personalizar navbar

### Intermedio
- [ ] Modificar gradientes de fondo
- [ ] Cambiar estilos de cards
- [ ] Personalizar botones
- [ ] Ajustar espaciados generales
- [ ] Modificar tablas

### Avanzado
- [ ] Implementar modo oscuro
- [ ] Agregar nuevas animaciones
- [ ] Crear componentes personalizados
- [ ] Optimizar para accesibilidad
- [ ] Agregar iconos personalizados

---

## 🎯 Recomendaciones Finales

### Para Mejorar el Diseño

1. **Consistencia**: Mantener los mismos estilos en todas las páginas
2. **Jerarquía Visual**: Usar tamaños y pesos para guiar la atención
3. **Espacios en Blanco**: No saturar, dejar respiro visual
4. **Colores Limitados**: Usar 3-5 colores máximo
5. **Tipografía Clara**: Máximo 2 fuentes (una para títulos, una para texto)
6. **Feedback Visual**: Hover, active, focus states en elementos interactivos
7. **Mobile First**: Diseñar primero para móvil
8. **Accesibilidad**: Contrastar, textos alternativos, navegación por teclado
9. **Performance**: Optimizar imágenes, minimizar CSS/JS
10. **Testing**: Probar en diferentes navegadores y dispositivos

### Errores Comunes a Evitar

1. ❌ Usar demasiados colores
2. ❌ Textos muy pequeños (< 14px)
3. ❌ Bajo contraste (difícil de leer)
4. ❌ No probar en móvil
5. ❌ Animaciones muy rápidas o lentas
6. ❌ Botones sin feedback visual
7. ❌ Formularios sin validación visual
8. ❌ Olvidar estados (hover, active, disabled)

### Flujo de Trabajo Recomendado

1. **Planificar** el diseño (bocetos, wireframes)
2. **Definir** paleta de colores
3. **Elegir** tipografía
4. **Crear** componentes base (botones, cards, forms)
5. **Implementar** página por página
6. **Probar** en diferentes dispositivos
7. **Refinar** y optimizar
8. **Documentar** cambios realizados

---

## 📞 Soporte de Diseño

### Archivos Clave para Modificar

**Colores y Estilos Generales:**
- `includes/styles.css` (líneas 1-200)

**Componentes:**
- `includes/styles.css` (líneas 200-800)

**Responsive:**
- `includes/styles.css` (líneas 800-1000)

**Animaciones:**
- `includes/styles.css` (líneas 1000-1134)

**Header/Navbar:**
- `includes/header.php` (líneas 100-199)

### Preguntas Frecuentes

**P: ¿Cómo cambio el color principal?**
R: Modifica `--primary-color` en `includes/styles.css` línea 6

**P: ¿Cómo hago el diseño más minimalista?**
R: Reduce sombras, simplifica gradientes, usa colores planos

**P: ¿Cómo agrego un logo más grande?**
R: Modifica `height` en `includes/header.php` línea 118

**P: ¿Puedo usar otro framework CSS?**
R: Sí, pero tendrías que reescribir todos los componentes

**P: ¿Cómo optimizo para móvil?**
R: Usa las clases responsive de Bootstrap y media queries

---

**¡Éxito con la personalización del diseño!** 🎨

---

*Documentación de Diseño creada: Noviembre 2025*  
*Última actualización: 25 de Noviembre de 2025*
