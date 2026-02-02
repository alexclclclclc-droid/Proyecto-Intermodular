-- =============================================
-- Script para configurar la base de datos
-- Ejecutar en MySQL/phpMyAdmin
-- =============================================

-- Crear la base de datos
CREATE DATABASE IF NOT EXISTS apartamentos_cyl
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE apartamentos_cyl;

-- =============================================
-- Tabla: apartamentos
-- =============================================
DROP TABLE IF EXISTS favoritos;
DROP TABLE IF EXISTS reservas;
DROP TABLE IF EXISTS apartamentos;

CREATE TABLE apartamentos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    n_registro VARCHAR(50) NOT NULL UNIQUE,
    nombre VARCHAR(255) NOT NULL,
    direccion VARCHAR(500),
    codigo_postal VARCHAR(10),
    provincia VARCHAR(100) NOT NULL,
    municipio VARCHAR(100),
    localidad VARCHAR(100),
    nucleo VARCHAR(100),
    telefono_1 VARCHAR(20),
    telefono_2 VARCHAR(20),
    telefono_3 VARCHAR(20),
    email VARCHAR(255),
    web VARCHAR(500),
    q_calidad BOOLEAN DEFAULT FALSE,
    plazas INT DEFAULT 0,
    categoria VARCHAR(100),
    especialidades TEXT,
    gps_latitud DECIMAL(10, 7),
    gps_longitud DECIMAL(10, 7),
    accesible BOOLEAN DEFAULT FALSE,
    fecha_sincronizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    activo BOOLEAN DEFAULT TRUE,
   
    INDEX idx_provincia (provincia),
    INDEX idx_municipio (municipio),
    INDEX idx_coordenadas (gps_latitud, gps_longitud),
    INDEX idx_plazas (plazas),
    INDEX idx_accesible (accesible)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- Tabla: usuarios
-- =============================================
DROP TABLE IF EXISTS usuarios;

CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    apellidos VARCHAR(150),
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    telefono VARCHAR(20),
    rol ENUM('usuario', 'admin') DEFAULT 'usuario',
    activo BOOLEAN DEFAULT TRUE,
    verificado BOOLEAN DEFAULT FALSE,
    token_verificacion VARCHAR(100),
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ultimo_acceso TIMESTAMP NULL,
   
    INDEX idx_email (email),
    INDEX idx_rol (rol)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- Tabla: reservas
-- =============================================
CREATE TABLE reservas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL,
    id_apartamento INT NOT NULL,
    fecha_entrada DATE NOT NULL,
    fecha_salida DATE NOT NULL,
    num_huespedes INT NOT NULL DEFAULT 1,
    precio_total DECIMAL(10, 2),
    estado ENUM('pendiente', 'confirmada', 'cancelada', 'completada') DEFAULT 'pendiente',
    notas TEXT,
    fecha_reserva TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_modificacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
   
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (id_apartamento) REFERENCES apartamentos(id) ON DELETE CASCADE,
   
    INDEX idx_fechas (fecha_entrada, fecha_salida),
    INDEX idx_estado (estado),
    INDEX idx_usuario (id_usuario),
    INDEX idx_apartamento (id_apartamento)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- Tabla: favoritos
-- =============================================
CREATE TABLE favoritos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL,
    id_apartamento INT NOT NULL,
    fecha_agregado TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
   
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (id_apartamento) REFERENCES apartamentos(id) ON DELETE CASCADE,
   
    UNIQUE KEY unique_favorito (id_usuario, id_apartamento)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- Usuario administrador por defecto
-- Password: Admin123!
-- =============================================
INSERT INTO usuarios (nombre, apellidos, email, password, rol, activo, verificado) VALUES (
    'Administrador',
    'Sistema',
    'admin@apartamentoscyl.es',
    '$2y$12$aq6deBshmIeCTXMkxKjBc.P2LI9Fm1RxuKzBI6d5nupwbkyK4J87W',
    'admin',
    TRUE,
    TRUE
);

-- =============================================
-- Datos de prueba
-- =============================================

-- Insertar usuarios de prueba
INSERT INTO usuarios (nombre, apellidos, email, password, telefono, rol, activo, verificado) VALUES
('Juan', 'Pérez García', 'juan@test.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '666111222', 'usuario', 1, 1),
('María', 'López Martín', 'maria@test.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '666333444', 'usuario', 1, 1),
('Carlos', 'Ruiz Sánchez', 'carlos@test.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '666555666', 'usuario', 0, 1);

-- Insertar apartamentos de prueba
INSERT INTO apartamentos (n_registro, nombre, direccion, codigo_postal, provincia, municipio, localidad, telefono_1, email, plazas, categoria, gps_latitud, gps_longitud, activo) VALUES
('AT-001-2024', 'Apartamento Centro Salamanca', 'Plaza Mayor, 15', '37001', 'Salamanca', 'Salamanca', 'Salamanca', '923123456', 'info@apartcentro.com', 4, '3 estrellas', 40.9701, -5.6635, 1),
('AT-002-2024', 'Casa Rural Valladolid', 'Calle Real, 25', '47001', 'Valladolid', 'Valladolid', 'Valladolid', '983654321', 'reservas@casarural.com', 6, '4 estrellas', 41.6523, -4.7245, 1),
('AT-003-2024', 'Apartamento León Centro', 'Calle Ancha, 8', '24001', 'León', 'León', 'León', '987789123', 'contacto@leoncentro.com', 2, '2 estrellas', 42.5987, -5.5671, 1);

-- Insertar reservas de prueba
INSERT INTO reservas (id_usuario, id_apartamento, fecha_entrada, fecha_salida, num_huespedes, precio_total, estado) VALUES
(2, 1, '2024-03-15', '2024-03-18', 2, 180.00, 'confirmada'),
(3, 2, '2024-04-01', '2024-04-05', 4, 320.00, 'pendiente'),
(4, 3, '2024-02-20', '2024-02-22', 1, 90.00, 'completada'),
(2, 1, '2024-05-10', '2024-05-12', 2, 120.00, 'cancelada');

-- Verificar que todo se insertó correctamente
SELECT 'Usuarios creados:' as info, COUNT(*) as total FROM usuarios;
SELECT 'Apartamentos creados:' as info, COUNT(*) as total FROM apartamentos;
SELECT 'Reservas creadas:' as info, COUNT(*) as total FROM reservas;