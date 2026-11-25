-- Crear la base de datos (si no existe)
CREATE DATABASE IF NOT EXISTS sistema_citas
CHARACTER SET utf8 COLLATE utf8_general_ci;

-- Usar la base de datos
USE sistema_citas;

-- Usuarios del sistema: admin (todos los privilegios) y asistente (alta y consulta de citas)
CREATE TABLE IF NOT EXISTS `usuarios` (
  `id_usuario` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL UNIQUE,
  `password` varchar(255) NOT NULL,
  `rol` enum('admin','asistente') NOT NULL DEFAULT 'asistente',
  PRIMARY KEY (`id_usuario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Citas: campos del formulario de cita mas email, CURP y telefono
CREATE TABLE IF NOT EXISTS `citas` (
  `id_cita` int(11) NOT NULL AUTO_INCREMENT,
  `fecha_cita` date NOT NULL,
  `hora_cita` char(5) NOT NULL,
  `nombre` varchar(60) NOT NULL,
  `apellido` varchar(60) NOT NULL,
  `direccion` varchar(150) NOT NULL,
  `direccion2` varchar(150) DEFAULT NULL,
  `ciudad` varchar(80) NOT NULL,
  `region` varchar(80) NOT NULL,
  `codigo_postal` varchar(10) NOT NULL,
  `pais` varchar(60) NOT NULL,
  `fecha_nacimiento` date NOT NULL,
  `email` varchar(150) NOT NULL,
  `curp` char(18) NOT NULL,
  `telefono` varchar(10) NOT NULL,
  `creado_por` int(11) DEFAULT NULL,
  `creado` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_cita`),
  UNIQUE KEY `uq_dia_hora` (`fecha_cita`, `hora_cita`),
  KEY `idx_curp` (`curp`),
  CONSTRAINT `fk_cita_usuario` FOREIGN KEY (`creado_por`) REFERENCES `usuarios` (`id_usuario`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
