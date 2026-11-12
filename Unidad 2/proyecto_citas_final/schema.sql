CREATE DATABASE IF NOT EXISTS clinica_citas CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE clinica_citas;

-- Usuarios del sistema (personal de la clínica)
--   admin:     todos los privilegios (citas: alta, consulta, modificar, cancelar, eliminar; usuarios: alta, edición, baja)
--   asistente: solo da de alta citas y las consulta
CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    correo VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    rol ENUM('admin','asistente') NOT NULL DEFAULT 'asistente',
    creado TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Citas (campos del formulario + email, CURP y teléfono)
CREATE TABLE IF NOT EXISTS citas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fecha DATE NOT NULL,
    hora CHAR(5) NOT NULL,
    nombre VARCHAR(60) NOT NULL,
    apellido VARCHAR(60) NOT NULL,
    direccion VARCHAR(150) NOT NULL,
    direccion2 VARCHAR(150) NULL,
    ciudad VARCHAR(80) NOT NULL,
    region VARCHAR(80) NOT NULL,
    codigo_postal VARCHAR(10) NOT NULL,
    pais VARCHAR(60) NOT NULL,
    fecha_nacimiento DATE NOT NULL,
    email VARCHAR(150) NOT NULL,
    curp CHAR(18) NOT NULL,
    telefono VARCHAR(10) NOT NULL,
    estado ENUM('confirmada','cancelada') NOT NULL DEFAULT 'confirmada',
    creado_por INT NULL,
    creado TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX (fecha),
    INDEX (curp),
    FOREIGN KEY (creado_por) REFERENCES usuarios(id) ON DELETE SET NULL
);
