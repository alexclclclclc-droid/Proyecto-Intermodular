# Requirements Document

## Introduction

Este documento especifica los requisitos para completar el sistema de reservas de apartamentos turísticos y mejorar el mapa interactivo. El objetivo es permitir a los usuarios reservar apartamentos directamente desde la aplicación web y visualizar todos los apartamentos en un mapa interactivo con marcadores de ubicación.

## Glossary

- **Sistema**: La aplicación web de apartamentos turísticos de Castilla y León
- **Usuario**: Persona que navega por la aplicación y puede realizar reservas
- **Apartamento**: Alojamiento turístico disponible para reserva
- **Reserva**: Solicitud de alojamiento para fechas específicas
- **Mapa_Interactivo**: Componente visual que muestra apartamentos con marcadores geográficos
- **API_Reservas**: Interfaz de programación ya existente para gestionar reservas
- **Panel_Admin**: Interfaz administrativa para gestionar reservas

## Requirements

### Requirement 1: Sistema de Reservas Completo

**User Story:** Como usuario, quiero poder reservar un apartamento directamente desde la aplicación, para que pueda asegurar mi alojamiento de forma rápida y sencilla.

#### Acceptance Criteria

1. WHEN un usuario ve los detalles de un apartamento, THE Sistema SHALL mostrar un botón "Reservar" prominente
2. WHEN un usuario hace clic en "Reservar", THE Sistema SHALL mostrar un formulario de reserva con campos para fechas y número de huéspedes
3. WHEN un usuario selecciona fechas, THE Sistema SHALL verificar la disponibilidad en tiempo real usando la API_Reservas existente
4. WHEN las fechas no están disponibles, THE Sistema SHALL mostrar un mensaje de error claro y sugerir fechas alternativas
5. WHEN un usuario completa el formulario de reserva, THE Sistema SHALL validar todos los campos antes del envío
6. WHEN una reserva es exitosa, THE Sistema SHALL mostrar confirmación y redirigir a "Mis Reservas"
7. WHEN una reserva es creada, THE Sistema SHALL enviar los datos a la API_Reservas existente para persistir en base de datos

### Requirement 2: Integración con Panel de Administrador

**User Story:** Como administrador, quiero ver todas las reservas realizadas en el panel administrativo, para que pueda gestionar y monitorear las reservas de los usuarios.

#### Acceptance Criteria

1. WHEN se crea una nueva reserva, THE Sistema SHALL almacenar la información en la base de datos usando ReservaDAO
2. WHEN un administrador accede al panel, THE Sistema SHALL mostrar todas las reservas con información del usuario y apartamento
3. WHEN se muestran las reservas, THE Sistema SHALL incluir estado, fechas, usuario y apartamento reservado
4. WHEN un administrador selecciona una reserva, THE Sistema SHALL permitir cambiar el estado de la reserva
5. THE Sistema SHALL mantener la funcionalidad existente del Panel_Admin sin modificaciones

### Requirement 3: Mapa Interactivo Mejorado

**User Story:** Como usuario, quiero ver todos los apartamentos disponibles en un mapa interactivo, para que pueda visualizar su ubicación geográfica y elegir según la zona que me interese.

#### Acceptance Criteria

1. WHEN un usuario accede a la página del mapa, THE Mapa_Interactivo SHALL cargar y mostrar todos los apartamentos con coordenadas GPS válidas
2. WHEN se cargan los apartamentos, THE Sistema SHALL crear un marcador visual para cada apartamento en su ubicación GPS
3. WHEN un usuario hace clic en un marcador, THE Sistema SHALL mostrar un popup con información básica del apartamento
4. WHEN se muestra el popup, THE Sistema SHALL incluir nombre, ubicación, capacidad y botón "Ver detalles"
5. WHEN un usuario hace clic en "Ver detalles" en el popup, THE Sistema SHALL abrir el modal de detalles del apartamento
6. WHEN hay múltiples apartamentos en una zona, THE Sistema SHALL agrupar los marcadores para evitar solapamiento
7. WHEN un usuario filtra por provincia, THE Mapa_Interactivo SHALL actualizar los marcadores mostrados según el filtro

### Requirement 4: Validación y Experiencia de Usuario

**User Story:** Como usuario, quiero recibir retroalimentación clara durante el proceso de reserva, para que pueda completar mi reserva sin confusión.

#### Acceptance Criteria

1. WHEN un usuario no ha iniciado sesión, THE Sistema SHALL requerir login antes de mostrar el formulario de reserva
2. WHEN se validan fechas de reserva, THE Sistema SHALL verificar que la fecha de entrada sea posterior a hoy
3. WHEN se validan fechas de reserva, THE Sistema SHALL verificar que la fecha de salida sea posterior a la fecha de entrada
4. WHEN se valida capacidad, THE Sistema SHALL verificar que el número de huéspedes no exceda la capacidad del apartamento
5. WHEN ocurre un error, THE Sistema SHALL mostrar mensajes de error específicos y útiles
6. WHEN una operación es exitosa, THE Sistema SHALL mostrar confirmación visual clara
7. WHEN se cargan datos, THE Sistema SHALL mostrar indicadores de carga para mejorar la experiencia del usuario

### Requirement 5: Compatibilidad y Rendimiento

**User Story:** Como usuario, quiero que el sistema funcione correctamente en diferentes dispositivos y navegadores, para que pueda acceder desde cualquier plataforma.

#### Acceptance Criteria

1. THE Sistema SHALL mantener compatibilidad con la arquitectura PHP existente
2. THE Sistema SHALL utilizar las clases DAO y modelos existentes sin modificaciones estructurales
3. THE Sistema SHALL funcionar correctamente en dispositivos móviles y de escritorio
4. WHEN se cargan mapas, THE Sistema SHALL optimizar la carga de marcadores para evitar problemas de rendimiento
5. THE Sistema SHALL mantener la consistencia visual con el diseño existente de la aplicación
6. WHEN se realizan peticiones a la API, THE Sistema SHALL manejar errores de red de forma elegante