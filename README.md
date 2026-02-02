# 🏠 Apartamentos Turísticos de Castilla y León

Aplicación web para explorar y reservar apartamentos turísticos en Castilla y León, utilizando datos abiertos de la Junta de Castilla y León.

## 📋 Descripción

Este proyecto permite a los usuarios:
- Explorar apartamentos turísticos de las 9 provincias de Castilla y León
- Filtrar por provincia, municipio, capacidad y accesibilidad
- Visualizar apartamentos en un mapa interactivo (Leaflet.js)
- Registrarse e iniciar sesión
- Gestionar reservas

## 🛠️ Tecnologías

### Backend
- **PHP 8.x** - Lenguaje principal
- **MySQL/MariaDB** - Base de datos relacional
- **PDO** - Acceso a datos con prepared statements
- **Patrón MVC** - Arquitectura del proyecto
- **API REST** - Comunicación con el frontend

### Frontend
- **HTML5** - Estructura semántica
- **CSS3** - Estilos con variables CSS y diseño responsive
- **JavaScript (ES6+)** - Vanilla JS con módulos
- **Fetch API** - Comunicación asíncrona con el servidor
- **Leaflet.js** - Mapas interactivos

### Datos
- **API de Datos Abiertos de Castilla y León** - Fuente de datos
- URL: https://datosabiertos.jcyl.es

## 📁 Estructura del Proyecto

```
apartamentos_cyl/
├── api/                    # Endpoints de la API REST
│   ├── apartamentos.php    # CRUD de apartamentos
│   ├── auth.php            # Autenticación
│   ├── reservas.php        # Gestión de reservas
│   └── sync.php            # Sincronización con API externa
├── config/                 # Configuración
│   ├── config.php          # Config general
│   └── database.php        # Conexión PDO
├── dao/                    # Data Access Objects
│   ├── ApartamentoDAO.php
│   ├── UsuarioDAO.php
│   └── ReservaDAO.php
├── db/                     # Scripts de base de datos
│   └── schema.sql          # Esquema completo
├── docs/                   # Documentación (memoria, pptx, video)
├── models/                 # Modelos de datos
│   ├── Apartamento.php
│   ├── Usuario.php
│   └── Reserva.php
├── public/                 # Archivos públicos
│   ├── css/
│   │   └── styles.css      # Estilos principales
│   └── js/
│       └── app.js          # JavaScript principal
├── services/               # Servicios
│   └── ApiSyncService.php  # Sincronización API
├── views/                  # Vistas
│   ├── partials/
│   │   ├── header.php
│   │   └── footer.php
│   ├── apartamentos.php
│   ├── mapa.php
│   └── mis-reservas.php
├── .htaccess               # Configuración Apache
├── index.php               # Página principal
└── README.md               # Este archivo
```

## 🚀 Instalación

### Requisitos
- PHP 8.0 o superior
- MySQL 8.0 / MariaDB 10.5 o superior
- Apache con mod_rewrite habilitado
- Extensiones PHP: PDO, cURL, JSON

### Pasos

1. **Clonar el repositorio**
   ```bash
   git clone https://github.com/tu-usuario/apartamentos-cyl.git
   cd apartamentos-cyl
   ```

2. **Crear la base de datos**
   ```bash
   mysql -u root -p < db/schema.sql
   ```

3. **Configurar la conexión**
   Editar `config/database.php` con tus credenciales:
   ```php
   private $host = 'localhost';
   private $db_name = 'apartamentos_cyl';
   private $username = 'tu_usuario';
   private $password = 'tu_contraseña';
   ```

4. **Sincronizar datos de la API**
   ```bash
   php api/sync.php
   ```
   O acceder como admin a: `http://localhost/apartamentos_cyl/api/sync.php`

5. **Configurar Apache**
   Asegúrate de que el DocumentRoot apunte al directorio del proyecto.

## 📡 API Endpoints

### Apartamentos
| Método | Endpoint | Descripción |
|--------|----------|-------------|
| GET | `/api/apartamentos.php?action=listar` | Lista con paginación y filtros |
| GET | `/api/apartamentos.php?action=detalle&id=X` | Detalle de un apartamento |
| GET | `/api/apartamentos.php?action=provincias` | Lista de provincias |
| GET | `/api/apartamentos.php?action=municipios&provincia=X` | Municipios de una provincia |
| GET | `/api/apartamentos.php?action=mapa` | Datos para el mapa |
| GET | `/api/apartamentos.php?action=estadisticas` | Estadísticas generales |

### Autenticación
| Método | Endpoint | Descripción |
|--------|----------|-------------|
| POST | `/api/auth.php?action=login` | Iniciar sesión |
| POST | `/api/auth.php?action=registro` | Registrar usuario |
| GET | `/api/auth.php?action=logout` | Cerrar sesión |
| GET | `/api/auth.php?action=check` | Verificar sesión |

### Reservas
| Método | Endpoint | Descripción |
|--------|----------|-------------|
| POST | `/api/reservas.php?action=crear` | Crear reserva |
| GET | `/api/reservas.php?action=mis_reservas` | Reservas del usuario |
| POST | `/api/reservas.php?action=cancelar` | Cancelar reserva |
| GET | `/api/reservas.php?action=disponibilidad` | Verificar disponibilidad |

## 🔐 Seguridad

- Contraseñas hasheadas con bcrypt (cost 12)
- Prepared statements en todas las consultas (PDO)
- Validación de datos en cliente y servidor
- Tokens CSRF para formularios
- Headers de seguridad HTTP
- Escape de HTML para prevenir XSS

## 👤 Usuario Administrador por defecto

- **Email:** admin@apartamentoscyl.es
- **Contraseña:** Admin123!
- **Rol:** Administrador

## 📊 Características destacadas

- ✅ Comunicación asíncrona con fetch()
- ✅ Filtrado dinámico de apartamentos
- ✅ Mapa interactivo con Leaflet.js
- ✅ Diseño responsive (mobile-first)
- ✅ Sistema de autenticación completo
- ✅ Validación en cliente y servidor
- ✅ Integración con API de datos abiertos
- ✅ Control de versiones con Git

## 🗂️ Requisitos del Proyecto Intermodular

| Requisito | Implementación |
|-----------|----------------|
| Base de datos con PDO | ✅ MySQL con PDO y prepared statements |
| Patrón MVC | ✅ Models, DAOs, Views, Controllers (API) |
| Login con $_SESSION | ✅ Sistema completo con bcrypt |
| fetch() asíncrono | ✅ Todas las llamadas a API |
| Validación cliente | ✅ Módulo ValidacionModule en JS |
| Diseño responsive | ✅ CSS con media queries |
| Git | ✅ Control de versiones |

## 📝 Licencia

Proyecto educativo para el módulo de Proyecto Intermodular de DAW.

## 🔗 Enlaces

- [Portal de Datos Abiertos de CyL](https://datosabiertos.jcyl.es)
- [API de Registro de Turismo](https://analisis.datosabiertos.jcyl.es/explore/dataset/registro-de-turismo-de-castilla-y-leon)
- [Concurso de Datos Abiertos 2025](https://datosabiertos.jcyl.es/web/es/concurso-datos-abiertos)

-Hola
