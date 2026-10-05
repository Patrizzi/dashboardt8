-- =====================================================================
-- SISTEMA: SENATI ETI - SISTEMA DE GESTIÓN DE VISITAS
-- BASE DE DATOS: dashboardt8_bd
-- MOTOR: MariaDB / MySQL (Compatible con phpMyAdmin y XAMPP)
-- =====================================================================

CREATE DATABASE IF NOT EXISTS `dashboardt8_bd`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `dashboardt8_bd`;

-- Desactivar temporalmente revisión de claves foráneas para importación limpia
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS `visitas`;
DROP TABLE IF EXISTS `usuarios`;
DROP TABLE IF EXISTS `asuntos`;
DROP TABLE IF EXISTS `empleados`;
DROP TABLE IF EXISTS `roles`;
SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================================
-- 1. TABLA: ROLES
-- =====================================================================
CREATE TABLE `roles` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nombre` VARCHAR(50) NOT NULL UNIQUE,
  `descripcion` VARCHAR(255) NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- 2. TABLA: EMPLEADOS
-- =====================================================================
CREATE TABLE `empleados` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `codigo` VARCHAR(20) NOT NULL UNIQUE,
  `nombre` VARCHAR(150) NOT NULL,
  `email` VARCHAR(150) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `cargo` VARCHAR(100) DEFAULT 'Especialista de Atención ETI',
  `rol_id` INT NOT NULL,
  `activo` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_empleados_rol` FOREIGN KEY (`rol_id`) 
    REFERENCES `roles` (`id`) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- 3. TABLA: USUARIOS (AUTENTICACIÓN Y ROLES RBAC)
-- =====================================================================
CREATE TABLE `usuarios` (
  `id_usuario` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `rol` ENUM('administrador', 'empleado') NOT NULL,
  `nombre` VARCHAR(150) NOT NULL,
  `empleado_id` INT NULL,
  `activo` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_usuarios_empleado` FOREIGN KEY (`empleado_id`) 
    REFERENCES `empleados` (`id`) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- 4. TABLA: ASUNTOS (TIPIFICACIÓN DE TRÁMITES)
-- =====================================================================
CREATE TABLE `asuntos` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nombre` VARCHAR(80) NOT NULL UNIQUE,
  `descripcion` VARCHAR(255) NULL,
  `tiempo_sla_minutos` INT DEFAULT 15,
  `activo` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- 5. TABLA: VISITAS (ENTIDAD CENTRAL CON TIMESTAMPS)
-- =====================================================================
CREATE TABLE `visitas` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `codigo` VARCHAR(30) NOT NULL UNIQUE,
  `visitante` VARCHAR(150) NOT NULL,
  `dni` VARCHAR(15) NULL,
  `asunto_id` INT NOT NULL,
  `consulta` TEXT NOT NULL,
  `respuesta` TEXT NULL,
  `prioridad` ENUM('Baja', 'Media', 'Alta', 'Urgente') NOT NULL DEFAULT 'Media',
  `estado` ENUM('En Espera', 'En Proceso', 'Completada', 'Cancelada') NOT NULL DEFAULT 'En Espera',
  `empleado_id` INT NULL,
  `fecha_registro` DATE NOT NULL,
  `hora_registro` TIME NOT NULL,
  `t_registro` DATETIME NOT NULL COMMENT 'Marca temporal de escaneo QR / llegada',
  `t_inicio` DATETIME NULL COMMENT 'Marca temporal al tomar el ticket en ventanilla',
  `t_cierre` DATETIME NULL COMMENT 'Marca temporal al completar y dictaminar respuesta',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_visitas_estado` (`estado`),
  INDEX `idx_visitas_empleado` (`empleado_id`),
  INDEX `idx_visitas_asunto` (`asunto_id`),
  INDEX `idx_visitas_fecha` (`fecha_registro`),
  INDEX `idx_visitas_t_registro` (`t_registro`),
  CONSTRAINT `fk_visitas_asunto` FOREIGN KEY (`asunto_id`) 
    REFERENCES `asuntos` (`id`) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_visitas_empleado` FOREIGN KEY (`empleado_id`) 
    REFERENCES `empleados` (`id`) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- =====================================================================
-- 6. CARGA DE DATOS DE PRUEBA REALISTAS (DML)
-- =====================================================================

-- Roles
INSERT INTO `roles` (`id`, `nombre`, `descripcion`) VALUES
(1, 'Administrador', 'Acceso gerencial a métricas, analítica y supervisión global'),
(2, 'Empleado', 'Gestión operativa de ventanilla y atención de consultas');

-- Empleados
INSERT INTO `empleados` (`id`, `codigo`, `nombre`, `email`, `password`, `cargo`, `rol_id`) VALUES
(1, 'EMP001', 'Carlos Rodríguez López', 'crodriguez@senati.pe', '$2y$10$abcdefghijklmnopqrstuv', 'Especialista de Atención ETI', 2),
(2, 'EMP002', 'María Elena Quispe', 'mquispe@senati.pe', '$2y$10$abcdefghijklmnopqrstuv', 'Asesora de Matrícula', 2),
(3, 'EMP003', 'Jorge Mendoza Soto', 'jmendoza@senati.pe', '$2y$10$abcdefghijklmnopqrstuv', 'Coordinador de Pagos', 2),
(4, 'EMP004', 'Ana Lucía Ramos', 'aramos@senati.pe', '$2y$10$abcdefghijklmnopqrstuv', 'Tutor Académico', 2),
(5, 'EMP005', 'Roberto Dávila', 'rdavila@senati.pe', '$2y$10$abcdefghijklmnopqrstuv', 'Asesor de Certificaciones', 2),
(6, 'ADM001', 'Ing. Marcos Villanueva', 'mvillanueva@senati.pe', '$2y$10$abcdefghijklmnopqrstuv', 'Jefe de Operaciones Sede', 1);

-- Usuarios con contraseñas encriptadas mediante BCRYPT (password_hash)
-- Credencial 1: Admin / admin1234 (Rol: administrador)
-- Credencial 2: Empleado / emp1234 (Rol: empleado)
INSERT INTO `usuarios` (`id_usuario`, `username`, `password`, `rol`, `nombre`, `empleado_id`) VALUES
(1, 'Admin', '$2y$10$kLG1vrZVy8zSHUlHL89RbuoZ01b8LejE7wLN16QKKaoIqMEWwbnJm', 'administrador', 'Ing. Marcos Villanueva', 6),
(2, 'Empleado', '$2y$10$AxnilRJsWvdmDMw7duL7TOKRVnFvz9Kcy4wHkw6aKP2xddV7/BYG6', 'empleado', 'Carlos Rodríguez López', 1);

-- Asuntos
INSERT INTO `asuntos` (`id`, `nombre`, `descripcion`, `tiempo_sla_minutos`) VALUES
(1, 'Matrícula', 'Inscripciones, convalidaciones y traslados de sede', 15),
(2, 'Pagos', 'Cuotas semestrales, facturación y estados de cuenta', 10),
(3, 'Tutoría', 'Apoyo pedagógico y seguimiento al alumno', 20),
(4, 'Consultas de Notas', 'Récord académico y solicitudes de rectificación', 15),
(5, 'Otros', 'Constancias, títulos y trámites varios', 15);

-- Visitas de Prueba
INSERT INTO `visitas` 
(`id`, `codigo`, `visitante`, `dni`, `asunto_id`, `consulta`, `respuesta`, `prioridad`, `estado`, `empleado_id`, `fecha_registro`, `hora_registro`, `t_registro`, `t_inicio`, `t_cierre`) 
VALUES
(1, 'VIS-1001', 'Carlos Mendoza Silva', '72849102', 1, 
 '¿Cuáles son las fechas límite de convalidación para Mecatrónica Industrial?', 
 'Se le brindó el cronograma de matrícula extemporánea y requisitos de convalidación académica.', 
 'Alta', 'Completada', 1, CURDATE(), '08:15:00', 
 DATE_SUB(NOW(), INTERVAL 140 MINUTE), DATE_SUB(NOW(), INTERVAL 130 MINUTE), DATE_SUB(NOW(), INTERVAL 122 MINUTE)),

(2, 'VIS-1002', 'Diana Sánchez Paredes', '71029384', 2, 
 'Error en el portal bancario para cancelar la cuota 2 del semestre.', 
 'Se generó nuevo código CIP y se confirmó recepción en el sistema financiero.', 
 'Media', 'Completada', 1, CURDATE(), '09:00:00', 
 DATE_SUB(NOW(), INTERVAL 105 MINUTE), DATE_SUB(NOW(), INTERVAL 97 MINUTE), DATE_SUB(NOW(), INTERVAL 90 MINUTE)),

(3, 'VIS-1003', 'Roberto Ponce Valdivia', '45892019', 3, 
 'Solicitud de cita presencial con el tutor pedagógico por temas de asistencia.', 
 NULL, 
 'Media', 'En Proceso', 1, CURDATE(), '09:45:00', 
 DATE_SUB(NOW(), INTERVAL 45 MINUTE), DATE_SUB(NOW(), INTERVAL 30 MINUTE), NULL),

(4, 'VIS-1004', 'Valeria Castillo Rivas', '76543210', 4, 
 'Revisión de calificación en examen final del curso Redes CISCO.', 
 'Se derivó la solicitud al jefe de área y se entregó formato oficial de reclamo.', 
 'Alta', 'Completada', 1, CURDATE(), '10:10:00', 
 DATE_SUB(NOW(), INTERVAL 180 MINUTE), DATE_SUB(NOW(), INTERVAL 168 MINUTE), DATE_SUB(NOW(), INTERVAL 160 MINUTE)),

(5, 'VIS-1005', 'Jorge Linares Cárdenas', '73201948', 1, 
 'Cambio de sede presencial de Independencia a San Martín de Porres.', 
 NULL, 
 'Baja', 'En Proceso', 1, CURDATE(), '10:30:00', 
 DATE_SUB(NOW(), INTERVAL 35 MINUTE), DATE_SUB(NOW(), INTERVAL 25 MINUTE), NULL),

(6, 'VIS-1006', 'Elena Bravo Montero', '78492011', 5, 
 'Emisión de constancia de egresado y trámite para título técnico.', 
 'Verificación de créditos concluida y entrega de orden de pago de trámite.', 
 'Media', 'Completada', 1, CURDATE(), '11:00:00', 
 DATE_SUB(NOW(), INTERVAL 240 MINUTE), DATE_SUB(NOW(), INTERVAL 230 MINUTE), DATE_SUB(NOW(), INTERVAL 221 MINUTE)),

-- Visitas en Espera que activan la Alerta de Auditoría Roja (> 15 min SLA)
(7, 'VIS-1007', 'Martín Quispe Gómez', '70192834', 1, 
 'Deseo inscribirme en el curso de Especialización en Inteligencia Artificial.', 
 NULL, 'Alta', 'En Espera', NULL, CURDATE(), '11:15:00', 
 DATE_SUB(NOW(), INTERVAL 26 MINUTE), NULL, NULL),

(8, 'VIS-1008', 'Lucía Fernández Torres', '74019283', 2, 
 'Consulta sobre descuento por convenio corporativo con empresa aliada.', 
 NULL, 'Urgente', 'En Espera', NULL, CURDATE(), '11:20:00', 
 DATE_SUB(NOW(), INTERVAL 21 MINUTE), NULL, NULL),

(9, 'VIS-1009', 'Andrés Ramos Vega', '75839201', 3, 
 'Información sobre programa de nivelación en matemáticas aplicadas.', 
 NULL, 'Media', 'En Espera', NULL, CURDATE(), '11:32:00', 
 DATE_SUB(NOW(), INTERVAL 8 MINUTE), NULL, NULL),

(10, 'VIS-1010', 'Sofía Guerrero Paz', '71234567', 1, 'Inscripción curso técnico', 'Atendido', 'Media', 'Completada', 2, CURDATE(), '08:30:00', DATE_SUB(NOW(), INTERVAL 180 MINUTE), DATE_SUB(NOW(), INTERVAL 170 MINUTE), DATE_SUB(NOW(), INTERVAL 160 MINUTE)),
(11, 'VIS-1011', 'Luis Morales Prado', '72345678', 2, 'Pago fraccionado', 'Aprobado', 'Alta', 'Completada', 2, CURDATE(), '10:00:00', DATE_SUB(NOW(), INTERVAL 120 MINUTE), DATE_SUB(NOW(), INTERVAL 112 MINUTE), DATE_SUB(NOW(), INTERVAL 105 MINUTE)),
(12, 'VIS-1012', 'Carla Navarrete R.', '73456789', 3, 'Tutoría vocacional', 'Orientada', 'Media', 'Completada', 3, CURDATE(), '10:45:00', DATE_SUB(NOW(), INTERVAL 95 MINUTE), DATE_SUB(NOW(), INTERVAL 85 MINUTE), DATE_SUB(NOW(), INTERVAL 75 MINUTE)),
(13, 'VIS-1013', 'Diego Salas Ortiz', '74567890', 4, 'Notas módulo 1', 'Revisado', 'Media', 'Completada', 4, CURDATE(), '11:10:00', DATE_SUB(NOW(), INTERVAL 60 MINUTE), DATE_SUB(NOW(), INTERVAL 50 MINUTE), DATE_SUB(NOW(), INTERVAL 42 MINUTE)),
(14, 'VIS-1014', 'Rosa Chávez Luna', '75678901', 1, 'Traslado de turno', NULL, 'Alta', 'En Proceso', 2, CURDATE(), '11:30:00', DATE_SUB(NOW(), INTERVAL 25 MINUTE), DATE_SUB(NOW(), INTERVAL 15 MINUTE), NULL);
