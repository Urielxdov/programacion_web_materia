CREATE DATABASE IF NOT EXISTS clinica_final CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE clinica_final;

CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    correo VARCHAR(150) NOT NULL UNIQUE,
    telefono VARCHAR(20) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    rol ENUM('admin','usuario') NOT NULL DEFAULT 'usuario',
    creado TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS citas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NULL,
    paciente VARCHAR(100) NOT NULL,
    telefono VARCHAR(20) NOT NULL,
    correo VARCHAR(150) NOT NULL,
    fecha DATE NOT NULL,
    hora VARCHAR(10) NOT NULL,
    problema TEXT,
    estado ENUM('confirmada','cancelada') NOT NULL DEFAULT 'confirmada',
    creado TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
);
