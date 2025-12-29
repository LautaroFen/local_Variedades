-- =====================================================
-- MUJERES VIRTUOSAS S.A - Base de Datos Completa
-- Sistema de Gestión de Clientes y Ventas
-- Fecha: 15 de Noviembre 2025
-- =====================================================

-- Crear base de datos si no existe
CREATE DATABASE IF NOT EXISTS `local_mv` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `local_mv`;

-- =====================================================
-- TABLA: usuarios_sistema
-- Almacena usuarios del sistema (jefes y empleados)
-- =====================================================

DROP TABLE IF EXISTS `usuarios_sistema`;
CREATE TABLE `usuarios_sistema` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario` varchar(30) NOT NULL,
  `password` varchar(255) DEFAULT NULL,
  `tipo` enum('jefe','empleado') DEFAULT 'empleado',
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_modificacion` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `usuario` (`usuario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Insertar usuarios por defecto (contraseña: 1234 para ambos)
INSERT INTO `usuarios_sistema` (`usuario`, `password`, `tipo`) VALUES
('Esteban123', '$2y$10$OL0l963RyH3VBTbbcGZo8OMqnxOyzhQmVEqciW4Hdcgd6nXqUs08O', 'jefe'),
('Empleado123', '$2y$10$OL0l963RyH3VBTbbcGZo8OMqnxOyzhQmVEqciW4Hdcgd6nXqUs08O', 'empleado');

-- =====================================================
-- TABLA: empleados_vendedores
-- Vendedores asignados a clientes
-- =====================================================

DROP TABLE IF EXISTS `empleados_vendedores`;
CREATE TABLE `empleados_vendedores` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre_completo` varchar(200) NOT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `fecha_registro` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_activo` (`activo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: clientes
-- Información de clientes y sus compras
-- =====================================================

DROP TABLE IF EXISTS `clientes`;
CREATE TABLE `clientes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre_completo` varchar(200) NOT NULL,
  `telefono` varchar(15) NOT NULL,
  `email` varchar(200) DEFAULT NULL,
  `barrio` varchar(100) NOT NULL,
  `direccion` varchar(200) NOT NULL,
  `notas` text DEFAULT NULL,
  `articulos` text NOT NULL,
  `valor_total` decimal(10,2) NOT NULL,
  `sena` decimal(10,2) DEFAULT 0.00,
  `frecuencia_pago` enum('semanal','quincenal','mensual') NOT NULL,
  `cuotas` int(11) NOT NULL,
  `fecha_primer_pago` date DEFAULT NULL,
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_modificacion` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  `vendedor_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `nombre_completo` (`nombre_completo`),
  KEY `telefono` (`telefono`),
  KEY `email` (`email`),
  KEY `idx_vendedor` (`vendedor_id`),
  CONSTRAINT `fk_vendedor` FOREIGN KEY (`vendedor_id`) REFERENCES `empleados_vendedores` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================
-- TABLA: pagos_clientes
-- Registro de pagos programados y realizados
-- =====================================================

DROP TABLE IF EXISTS `pagos_clientes`;
CREATE TABLE `pagos_clientes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cliente_id` int(11) NOT NULL,
  `numero_cuota` int(11) NOT NULL,
  `fecha_programada` date NOT NULL,
  `fecha_pago` date DEFAULT NULL,
  `monto` decimal(10,2) NOT NULL,
  `estado` enum('pendiente','pagado') DEFAULT 'pendiente',
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_cliente_cuota` (`cliente_id`,`numero_cuota`),
  KEY `idx_estado` (`estado`),
  KEY `idx_fecha_programada` (`fecha_programada`),
  CONSTRAINT `pagos_clientes_ibfk_1` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: password_reset_tokens
-- Tokens para recuperación de contraseñas/usuarios
-- =====================================================

DROP TABLE IF EXISTS `password_reset_tokens`;
CREATE TABLE `password_reset_tokens` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) DEFAULT 0 COMMENT '0 = token general para cualquier usuario',
  `token` varchar(255) NOT NULL COMMENT 'Código de 6 dígitos o token alfanumérico',
  `expiracion` datetime NOT NULL,
  `usado` tinyint(1) DEFAULT 0,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `token` (`token`),
  KEY `expiracion` (`expiracion`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================
-- TABLA: auditoria
-- Registro de acciones importantes del sistema
-- =====================================================

DROP TABLE IF EXISTS `auditoria`;
CREATE TABLE `auditoria` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario` varchar(100) NOT NULL,
  `accion` varchar(50) NOT NULL,
  `tabla` varchar(50) NOT NULL,
  `registro_id` int(11) NOT NULL,
  `detalles` text DEFAULT NULL,
  `fecha` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_usuario` (`usuario`),
  KEY `idx_fecha` (`fecha`),
  KEY `idx_tabla` (`tabla`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- INSTALACIÓN COMPLETADA
-- =====================================================
-- Usuarios por defecto:
-- - Usuario: Esteban123 | Contraseña: 1234 | Tipo: Jefe
-- - Usuario: Empleado123 | Contraseña: 1234 | Tipo: Empleado
-- =====================================================
