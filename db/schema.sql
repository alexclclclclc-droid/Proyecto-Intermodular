-- =====================================================
-- BASE DE DATOS: APARTAMENTOS TURÍSTICOS DE CASTILLA Y LEÓN
-- Proyecto Intermodular - DAW
-- =====================================================

-- Crear base de datos
DROP DATABASE IF EXISTS apartamentos_cyl;
CREATE DATABASE apartamentos_cyl CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE apartamentos_cyl;

-- =====================================================
-- TABLA: APARTAMENTOS (Datos de la API de datos abiertos)
-- =====================================================
CREATE TABLE apartamentos (
    id INT AUTO_INCREMENT,
    n_registro VARCHAR(50) NOT NULL UNIQUE,
    tipo_establecimiento VARCHAR(100),
    nombre VARCHAR(200) NOT NULL,
    direccion VARCHAR(300),
    codigo_postal VARCHAR(10),
    provincia VARCHAR(100) NOT NULL,
    municipio VARCHAR(150),
    localidad VARCHAR(150),
    nucleo VARCHAR(150),
    telefono_1 VARCHAR(30),
    telefono_2 VARCHAR(30),
    telefono_3 VARCHAR(30),
    email VARCHAR(200),
    web VARCHAR(300),
    q_calidad BOOLEAN DEFAULT FALSE,
    capacidad_alojamiento INT DEFAULT 0,
    categoria VARCHAR(50),
    especialidades TEXT,
    gps_latitud DECIMAL(10, 7),
    gps_longitud DECIMAL(10, 7),
    accesible BOOLEAN DEFAULT FALSE,
    fecha_sincronizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    activo BOOLEAN DEFAULT TRUE,
    PRIMARY KEY (id),
    INDEX idx_provincia (provincia),
    INDEX idx_municipio (municipio),
    INDEX idx_n_registro (n_registro),
    INDEX idx_coordenadas (gps_latitud, gps_longitud),
    INDEX idx_capacidad (capacidad_alojamiento),
    INDEX idx_accesible (accesible)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: USUARIOS
-- =====================================================
CREATE TABLE usuarios (
    id INT AUTO_INCREMENT,
    nombre VARCHAR(100) NOT NULL,
    apellidos VARCHAR(150),
    email VARCHAR(200) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    telefono VARCHAR(20),
    rol ENUM('usuario', 'admin') DEFAULT 'usuario',
    activo BOOLEAN DEFAULT TRUE,
    verificado BOOLEAN DEFAULT FALSE,
    token_verificacion VARCHAR(100),
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ultimo_acceso TIMESTAMP NULL,
    PRIMARY KEY (id),
    INDEX idx_email (email),
    INDEX idx_rol (rol)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: RESERVAS
-- =====================================================
CREATE TABLE reservas (
    id INT AUTO_INCREMENT,
    id_usuario INT NOT NULL,
    id_apartamento INT NOT NULL,
    fecha_entrada DATE NOT NULL,
    fecha_salida DATE NOT NULL,
    num_huespedes INT DEFAULT 1,
    precio_total DECIMAL(10, 2),
    estado ENUM('pendiente', 'confirmada', 'cancelada', 'completada') DEFAULT 'pendiente',
    notas TEXT,
    fecha_reserva TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_modificacion TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (id_apartamento) REFERENCES apartamentos(id) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_fechas (fecha_entrada, fecha_salida),
    INDEX idx_estado (estado),
    INDEX idx_usuario (id_usuario),
    INDEX idx_apartamento (id_apartamento)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: FAVORITOS
-- =====================================================
CREATE TABLE favoritos (
    id INT AUTO_INCREMENT,
    id_usuario INT NOT NULL,
    id_apartamento INT NOT NULL,
    fecha_agregado TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_usuario_apartamento (id_usuario, id_apartamento),
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (id_apartamento) REFERENCES apartamentos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: LOG DE SINCRONIZACIÓN
-- =====================================================
CREATE TABLE log_sincronizacion (
    id INT AUTO_INCREMENT,
    fecha_inicio TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_fin TIMESTAMP NULL,
    registros_procesados INT DEFAULT 0,
    registros_nuevos INT DEFAULT 0,
    registros_actualizados INT DEFAULT 0,
    errores INT DEFAULT 0,
    estado ENUM('en_proceso', 'completado', 'error') DEFAULT 'en_proceso',
    mensaje TEXT,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- USUARIO ADMINISTRADOR POR DEFECTO
-- Password: Admin123! (hasheado con bcrypt)
-- =====================================================
INSERT INTO usuarios (nombre, apellidos, email, password, rol, activo, verificado)
VALUES (
    'Administrador',
    'Sistema',
    'admin@apartamentoscyl.es',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'admin',
    TRUE,
    TRUE
);

-- =====================================================
-- VISTA: Estadísticas por provincia
-- =====================================================
CREATE VIEW vista_estadisticas_provincia AS
SELECT 
    provincia,
    COUNT(*) as total_apartamentos,
    SUM(capacidad_alojamiento) as capacidad_total,
    SUM(CASE WHEN accesible = TRUE THEN 1 ELSE 0 END) as total_accesibles,
    ROUND(AVG(capacidad_alojamiento), 1) as media_capacidad
FROM apartamentos
WHERE activo = TRUE
GROUP BY provincia
ORDER BY total_apartamentos DESC;

-- =====================================================
-- PROCEDIMIENTO: Buscar apartamentos cercanos
-- =====================================================
DELIMITER //
CREATE PROCEDURE buscar_cercanos(
    IN lat DECIMAL(10,7),
    IN lng DECIMAL(10,7),
    IN radio_km INT
)
BEGIN
    SELECT 
        a.*,
        (6371 * ACOS(
            COS(RADIANS(lat)) * COS(RADIANS(gps_latitud)) * 
            COS(RADIANS(gps_longitud) - RADIANS(lng)) + 
            SIN(RADIANS(lat)) * SIN(RADIANS(gps_latitud))
        )) AS distancia_km
    FROM apartamentos a
    WHERE a.activo = TRUE
        AND a.gps_latitud IS NOT NULL 
        AND a.gps_longitud IS NOT NULL
    HAVING distancia_km <= radio_km
    ORDER BY distancia_km ASC;
END //
DELIMITER ;