-- =============================================
-- Base de datos: Apartamentos Turísticos CyL
-- Schema completo con mejoras del mapa
-- =============================================
 
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
    INDEX idx_accesible (accesible),
    INDEX idx_gps_completo (activo, gps_latitud, gps_longitud, provincia),
    INDEX idx_provincia_activo (provincia, activo)
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
-- Tabla: log_sincronizacion
-- =============================================
DROP TABLE IF EXISTS log_sincronizacion;
 
CREATE TABLE log_sincronizacion (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fecha_inicio TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_fin TIMESTAMP NULL,
    registros_procesados INT DEFAULT 0,
    registros_nuevos INT DEFAULT 0,
    registros_actualizados INT DEFAULT 0,
    registros_errores INT DEFAULT 0,
    estado ENUM('en_proceso', 'completado', 'error') DEFAULT 'en_proceso',
    mensaje TEXT
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
-- VISTAS OPTIMIZADAS
-- =============================================

-- Vista: estadísticas por provincia
CREATE OR REPLACE VIEW vista_estadisticas_provincia AS
SELECT
    provincia,
    COUNT(*) as total_apartamentos,
    SUM(plazas) as plazas_totales,
    SUM(CASE WHEN accesible = TRUE THEN 1 ELSE 0 END) as accesibles,
    ROUND(AVG(plazas), 1) as media_plazas
FROM apartamentos
WHERE activo = TRUE
GROUP BY provincia
ORDER BY total_apartamentos DESC;

-- Vista: estadísticas de provincias con GPS
CREATE OR REPLACE VIEW vista_provincias_gps AS
SELECT 
    provincia,
    COUNT(*) as total,
    SUM(CASE WHEN gps_latitud IS NOT NULL AND gps_longitud IS NOT NULL THEN 1 ELSE 0 END) as con_gps,
    SUM(CASE WHEN gps_latitud IS NULL OR gps_longitud IS NULL THEN 1 ELSE 0 END) as sin_gps,
    ROUND(
        (SUM(CASE WHEN gps_latitud IS NOT NULL AND gps_longitud IS NOT NULL THEN 1 ELSE 0 END) * 100.0) / COUNT(*),
        1
    ) as porcentaje_gps
FROM apartamentos
WHERE activo = TRUE AND provincia IS NOT NULL
GROUP BY provincia
ORDER BY provincia;

-- Vista: apartamentos del mapa (optimizada)
CREATE OR REPLACE VIEW vista_mapa_apartamentos AS
SELECT 
    id,
    nombre,
    provincia,
    municipio,
    localidad,
    nucleo,
    gps_latitud,
    gps_longitud,
    plazas,
    accesible
FROM apartamentos
WHERE activo = TRUE 
  AND gps_latitud IS NOT NULL 
  AND gps_longitud IS NOT NULL;

-- =============================================
-- VERIFICACIÓN Y ESTADÍSTICAS INICIALES
-- =============================================

-- Verificar índices creados
SHOW INDEX FROM apartamentos;

-- Mostrar estadísticas de provincias con GPS
SELECT * FROM vista_provincias_gps;

-- Contar totales
SELECT 
    COUNT(*) as total_apartamentos,
    SUM(CASE WHEN gps_latitud IS NOT NULL AND gps_longitud IS NOT NULL THEN 1 ELSE 0 END) as con_gps,
    SUM(CASE WHEN gps_latitud IS NULL OR gps_longitud IS NULL THEN 1 ELSE 0 END) as sin_gps,
    ROUND(
        (SUM(CASE WHEN gps_latitud IS NOT NULL AND gps_longitud IS NOT NULL THEN 1 ELSE 0 END) * 100.0) / COUNT(*),
        1
    ) as porcentaje_gps
FROM apartamentos
WHERE activo = TRUE;