# Implementation Plan: Sistema de Reservas y Mapa Interactivo

## Overview

Este plan implementa la funcionalidad completa de reservas y mejora el mapa interactivo aprovechando al máximo la infraestructura existente. Las tareas se enfocan en agregar la interfaz de usuario faltante y corregir los problemas del mapa, sin modificar la API o los modelos ya funcionales.

## Tasks

- [x] 1. Implementar interfaz de reserva en modal de detalles
  - Agregar botón "Reservar" al modal de detalles de apartamento
  - Crear modal de formulario de reserva con campos para fechas, huéspedes y notas
  - Integrar con sistema de modales existente
  - _Requirements: 1.1, 1.2_

- [x] 1.1 Write unit tests for reservation modal
  - Test modal opening when "Reservar" button is clicked
  - Test form contains all required fields
  - _Requirements: 1.1, 1.2_

- [x] 2. Implementar módulo JavaScript de reservas
  - [x] 2.1 Crear ReservaModule con funciones de validación y envío
    - Implementar validación de fechas en tiempo real
    - Implementar verificación de disponibilidad usando API existente
    - Implementar envío de formulario a api/reservas.php
    - _Requirements: 1.3, 1.5, 1.7_

  - [x] 2.2 Write property test for availability validation
    - **Property 1: Validación de disponibilidad en tiempo real**
    - **Validates: Requirements 1.3**

  - [x] 2.3 Write property test for form validation
    - **Property 2: Validación completa de formularios de reserva**
    - **Validates: Requirements 1.5, 4.2, 4.3, 4.4**

  - [x] 2.4 Implementar manejo de respuestas y estados de UI
    - Mostrar mensajes de error específicos para fechas no disponibles
    - Mostrar confirmación de reserva exitosa
    - Implementar redirección a "Mis Reservas" después de reserva exitosa
    - _Requirements: 1.4, 1.6, 4.5, 4.6_

  - [x] 2.5 Write property test for reservation persistence
    - **Property 3: Persistencia de reservas**
    - **Validates: Requirements 1.7, 2.1**

- [x] 3. Implementar validación de autenticación
  - Verificar que usuario esté logueado antes de mostrar formulario de reserva
  - Redirigir a login si usuario no está autenticado
  - Integrar con AuthModule existente
  - _Requirements: 4.1_

- [x] 3.1 Write unit test for authentication check
  - Test unauthenticated user is redirected to login
  - _Requirements: 4.1_

- [x] 4. Checkpoint - Verificar sistema de reservas
  - Ensure all tests pass, ask the user if questions arise.

- [x] 5. Corregir y mejorar mapa interactivo
  - [x] 5.1 Corregir carga de marcadores en el mapa
    - Verificar que loadMapMarkers() funcione correctamente
    - Asegurar que se crean marcadores para apartamentos con GPS válido
    - Implementar manejo de errores para apartamentos sin coordenadas
    - _Requirements: 3.1, 3.2_

  - [x] 5.2 Write property test for marker creation
    - **Property 5: Creación de marcadores para apartamentos con GPS**
    - **Validates: Requirements 3.2**

  - [x] 5.3 Mejorar popups de marcadores
    - Implementar popups informativos con datos completos del apartamento
    - Agregar botón "Ver detalles" en popup que abra modal existente
    - Agregar botón "Reservar" en popup para acceso rápido
    - _Requirements: 3.3, 3.4, 3.5_

  - [x] 5.4 Write property test for marker behavior
    - **Property 6: Comportamiento de marcadores en el mapa**
    - **Validates: Requirements 3.3**

  - [x] 5.5 Write property test for popup content
    - **Property 7: Contenido completo de popups del mapa**
    - **Validates: Requirements 3.4**

- [x] 6. Implementar clustering de marcadores
  - Agregar librería de clustering para Leaflet
  - Implementar agrupación automática de marcadores cercanos
  - Configurar clustering para evitar solapamiento visual
  - _Requirements: 3.6_

- [x] 6.1 Write property test for marker clustering
  - **Property 8: Agrupación de marcadores cercanos**
  - **Validates: Requirements 3.6**

- [x] 7. Mejorar filtrado del mapa
  - Corregir filtro por provincia para actualizar marcadores correctamente
  - Asegurar sincronización entre filtro y marcadores mostrados
  - Actualizar contador de apartamentos mostrados
  - _Requirements: 3.7_

- [x] 7.1 Write property test for map filtering
  - **Property 9: Filtrado de marcadores por provincia**
  - **Validates: Requirements 3.7**

- [x] 8. Verificar integración con panel de administrador
  - Confirmar que nuevas reservas aparecen automáticamente en panel admin
  - Verificar que información completa se muestra en listado de reservas
  - Probar funcionalidad de cambio de estado de reservas
  - _Requirements: 2.2, 2.3, 2.4_

- [x] 8.1 Write property test for admin reservation display
  - **Property 4: Información completa en visualización de reservas**
  - **Validates: Requirements 2.3**

- [x] 8.2 Write unit test for admin panel integration
  - Test new reservations appear in admin panel
  - Test admin can change reservation status
  - _Requirements: 2.2, 2.4_

- [x] 9. Implementar manejo robusto de errores
  - [x] 9.1 Mejorar manejo de errores de red
    - Implementar timeouts para peticiones API
    - Mostrar mensajes de error comprensibles para fallos de red
    - Agregar retry automático para operaciones críticas
    - _Requirements: 5.6_

  - [x] 9.2 Write property test for error handling
    - **Property 11: Manejo elegante de errores de red**
    - **Validates: Requirements 5.6**

  - [x] 9.3 Implementar indicadores de carga consistentes
    - Agregar spinners durante operaciones asíncronas
    - Deshabilitar botones durante procesamiento
    - Mostrar feedback visual para todas las operaciones
    - _Requirements: 4.7_

  - [x] 9.4 Write property test for UI state management
    - **Property 10: Manejo consistente de estados de UI**
    - **Validates: Requirements 4.5, 4.6, 4.7**

- [x] 10. Checkpoint final - Verificar integración completa
  - Ensure all tests pass, ask the user if questions arise.

- [x] 11. Optimización y pulido final
  - Verificar compatibilidad móvil de nuevos componentes
  - Optimizar rendimiento de carga de mapa con muchos marcadores
  - Asegurar consistencia visual con diseño existente
  - Realizar pruebas de integración end-to-end
  - _Requirements: 5.3, 5.4, 5.5_

## Notes

- All tasks are required for comprehensive implementation from start
- Each task references specific requirements for traceability
- Checkpoints ensure incremental validation
- Property tests validate universal correctness properties
- Unit tests validate specific examples and edge cases
- The implementation leverages existing API, DAOs, and models without modifications
- Focus is on adding missing UI components and fixing map functionality

## Bug Fixes Applied

- **Fixed SQL parameter issue in ReservaDAO.verificarDisponibilidad()**: The method was using named parameters `:entrada` and `:salida` multiple times in the same query, which caused PDO to throw "Invalid parameter number" errors. Changed to positional parameters to resolve the issue. This was the root cause of the "Error al verificar disponibilidad" message users were experiencing.