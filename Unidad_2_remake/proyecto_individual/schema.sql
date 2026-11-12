-- Crear la base de datos (si no existe)
CREATE DATABASE IF NOT EXISTS sistema_invitados
CHARACTER SET utf8 COLLATE utf8_general_ci;

-- Usar la base de datos
USE sistema_invitados;

-- Estructura de tabla `usuarios`
CREATE TABLE IF NOT EXISTS `usuarios` (
  `id_usuario` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL UNIQUE,
  `password` varchar(255) NOT NULL,
  `rol` enum('admin','asistente') NOT NULL DEFAULT 'asistente',
  PRIMARY KEY (`id_usuario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Estructura de tabla `invitados`
CREATE TABLE IF NOT EXISTS `invitados` (
  `id_invitado` int(11) NOT NULL AUTO_INCREMENT,
  `nombres` varchar(100) NOT NULL,
  `apellidos` varchar(100) NOT NULL,
  `correo` varchar(100) NOT NULL,
  `telefono` varchar(20) NOT NULL,
  `direccion` text NOT NULL,
  `fecha_llegada` date NOT NULL,
  `fecha_salida` date NOT NULL,
  `num_invitados` int(11) NOT NULL,
  `transporte` enum('SI','NO') NOT NULL DEFAULT 'NO',
  `tipo_habitacion` enum('Individual','Doble','Suite') NOT NULL DEFAULT 'Individual',
  `dieta` enum('SI','NO') NOT NULL DEFAULT 'NO',
  PRIMARY KEY (`id_invitado`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
