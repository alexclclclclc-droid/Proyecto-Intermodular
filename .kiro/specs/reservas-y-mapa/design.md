# Design Document: Sistema de Reservas y Mapa Interactivo

## Overview

Este diseño implementa un sistema completo de reservas de apartamentos y mejora el mapa interactivo existente. La solución se integra con la arquitectura PHP existente, utilizando las APIs, DAOs y modelos ya implementados, agregando únicamente la interfaz de usuario faltante y las mejoras del mapa.

El enfoque es conservador y pragmático: aprovechar al máximo el código existente (API de reservas, ReservaDAO, modelos) y agregar solo los componentes de interfaz necesarios para completar la funcionalidad.

## Architecture

### Componentes Existentes (No Modificar)
- **API de Reservas** (`api/reservas.php`): Completamente funcional
- **ReservaDAO**: Maneja toda la persistencia de datos
- **Modelo Reserva**: Define la estructura de datos
- **Panel Admin**: Ya muestra reservas existentes

### Nuevos Componentes a Implementar
- **Interfaz de Reserva**: Botón y formulario en modal
- **Validación Frontend**: JavaScript para validar formularios
- **Mejoras del Mapa**: Carga correcta de marcadores
- **Integración UX**: Notificaciones y manejo de estados

### Flujo de Datos
```
Usuario → Formulario Reserva → Validación JS → API Reservas → ReservaDAO → Base de Datos
                                                     ↓
Panel Admin ← ReservaDAO ← Base de Datos ← Confirmación ← Usuario
```

## Components and Interfaces

### 1. Componente de Reserva (JavaScript)

**ReservaModule**: Nuevo módulo JavaScript que maneja la interfaz de reservas.

```javascript
const ReservaModule = {
    // Mostrar formulario de reserva
    showReservaForm(apartamentoId),
    
    // Validar disponibilidad en tiempo real
    checkDisponibilidad(apartamentoId, fechaEntrada, fechaSalida),
    
    // Procesar reserva
    submitReserva(formData),
    
    // Manejar respuestas de la API
    handleReservaResponse(response)
}
```

**Interfaz con API existente**: Utiliza `apiRequest()` para comunicarse con `api/reservas.php` sin modificaciones.

### 2. Mejoras del Mapa (JavaScript)

**MapaModule**: Extensión del código existente en `views/mapa.php`.

```javascript
// Funciones a mejorar/agregar
async function loadMapMarkers(provincia = '') {
    // Mejorar carga de marcadores
    // Agregar clustering para múltiples apartamentos
    // Mejorar popups con información completa
}

function createApartamentoMarker(apartamento) {
    // Crear marcador con popup informativo
    // Incluir botón "Ver detalles" y "Reservar"
}
```

### 3. Formulario de Reserva (HTML/CSS)

**Modal de Reserva**: Nuevo modal que se integra con el sistema de modales existente.

```html
<div id="modal-reserva" class="modal-overlay">
    <div class="modal">
        <form id="form-reserva">
            <!-- Campos: fechas, huéspedes, notas -->
            <!-- Validación en tiempo real -->
            <!-- Verificación de disponibilidad -->
        </form>
    </div>
</div>
```

## Data Models

### Modelos Existentes (Sin Cambios)
- **Reserva**: Ya implementado con todos los campos necesarios
- **Apartamento**: Ya implementado con coordenadas GPS
- **Usuario**: Ya implementado para autenticación

### Estructuras de Datos Frontend

**FormularioReserva**:
```javascript
{
    id_apartamento: number,
    fecha_entrada: string, // YYYY-MM-DD
    fecha_salida: string,  // YYYY-MM-DD
    num_huespedes: number,
    notas: string
}
```

**EstadoReserva**:
```javascript
{
    loading: boolean,
    disponible: boolean,
    error: string | null,
    fechasOcupadas: Array<{fecha_entrada, fecha_salida}>
}
```

## Correctness Properties

*A property is a characteristic or behavior that should hold true across all valid executions of a system-essentially, a formal statement about what the system should do. Properties serve as the bridge between human-readable specifications and machine-verifiable correctness guarantees.*

### Property 1: Validación de disponibilidad en tiempo real
*For any* apartamento y fechas seleccionadas por el usuario, el sistema debe verificar disponibilidad llamando a la API de reservas antes de permitir el envío del formulario
**Validates: Requirements 1.3**

### Property 2: Validación completa de formularios de reserva
*For any* formulario de reserva completado, el sistema debe validar que la fecha de entrada sea posterior a hoy, que la fecha de salida sea posterior a la entrada, y que el número de huéspedes no exceda la capacidad del apartamento
**Validates: Requirements 1.5, 4.2, 4.3, 4.4**

### Property 3: Persistencia de reservas
*For any* reserva creada exitosamente, el sistema debe enviar los datos a la API de reservas y almacenar la información en la base de datos usando ReservaDAO
**Validates: Requirements 1.7, 2.1**

### Property 4: Información completa en visualización de reservas
*For any* reserva mostrada en el sistema, debe incluir estado, fechas, información del usuario y datos del apartamento reservado
**Validates: Requirements 2.3**

### Property 5: Creación de marcadores para apartamentos con GPS
*For any* conjunto de apartamentos cargados en el mapa, el sistema debe crear un marcador visual para cada apartamento que tenga coordenadas GPS válidas
**Validates: Requirements 3.2**

### Property 6: Comportamiento de marcadores en el mapa
*For any* marcador de apartamento en el mapa, cuando se hace clic debe mostrar un popup con información básica del apartamento
**Validates: Requirements 3.3**

### Property 7: Contenido completo de popups del mapa
*For any* popup mostrado en el mapa, debe incluir nombre del apartamento, ubicación, capacidad y botón "Ver detalles"
**Validates: Requirements 3.4**

### Property 8: Agrupación de marcadores cercanos
*For any* conjunto de apartamentos con coordenadas GPS cercanas, el sistema debe agrupar los marcadores para evitar solapamiento visual
**Validates: Requirements 3.6**

### Property 9: Filtrado de marcadores por provincia
*For any* filtro de provincia aplicado en el mapa, el sistema debe actualizar los marcadores mostrados para incluir solo apartamentos de esa provincia
**Validates: Requirements 3.7**

### Property 10: Manejo consistente de estados de UI
*For any* operación del sistema (carga, éxito, error), debe mostrar el feedback visual apropiado: indicadores de carga durante operaciones, mensajes de confirmación para éxitos, y mensajes de error específicos para fallos
**Validates: Requirements 4.5, 4.6, 4.7**

### Property 11: Manejo elegante de errores de red
*For any* petición a la API que falle por problemas de red, el sistema debe capturar el error y mostrar un mensaje de error comprensible al usuario
**Validates: Requirements 5.6**

## Error Handling

### Frontend Error Handling

**Validación de Formularios**:
- Validación en tiempo real con feedback visual inmediato
- Mensajes de error específicos para cada tipo de validación
- Prevención de envío de formularios inválidos

**Manejo de Errores de API**:
```javascript
try {
    const response = await apiRequest('reservas.php', options);
    // Manejar respuesta exitosa
} catch (error) {
    // Mostrar error específico al usuario
    showToast(error.message || 'Error al procesar la solicitud', 'error');
    // Log para debugging
    console.error('Reserva error:', error);
}
```

**Estados de Carga**:
- Indicadores visuales durante operaciones asíncronas
- Deshabilitación de botones durante procesamiento
- Timeouts para operaciones que no respondan

### Backend Error Handling

**Reutilización del Sistema Existente**:
- La API de reservas ya maneja todos los errores de backend
- Validación de datos en ReservaDAO
- Manejo de errores de base de datos
- Respuestas JSON consistentes con códigos HTTP apropiados

### Mapa Error Handling

**Errores de Carga de Mapa**:
- Fallback si Leaflet no carga
- Manejo de apartamentos sin coordenadas GPS
- Timeout para carga de marcadores
- Mensaje de error si no se pueden cargar los datos

## Testing Strategy

### Dual Testing Approach

La estrategia de testing combina **unit tests** para casos específicos y **property-based tests** para verificar propiedades universales:

- **Unit tests**: Casos específicos, ejemplos concretos, y condiciones de borde
- **Property tests**: Propiedades universales que deben cumplirse para todos los inputs válidos

### Unit Testing Focus

**Casos Específicos a Testear**:
- Modal de reserva se abre al hacer clic en "Reservar"
- Formulario de reserva contiene todos los campos requeridos
- Usuario no autenticado es redirigido al login
- Fechas no disponibles muestran mensaje de error específico
- Reserva exitosa muestra confirmación y redirige
- Admin puede acceder al panel de reservas
- Admin puede cambiar estado de reservas
- Página de mapa carga correctamente
- Click en marcador abre popup
- Click en "Ver detalles" abre modal

**Integración entre Componentes**:
- Comunicación entre formulario de reserva y API
- Actualización del panel admin después de nueva reserva
- Sincronización entre filtros de mapa y marcadores mostrados

### Property-Based Testing Configuration

**Framework**: Se utilizará una librería de property-based testing para JavaScript (como fast-check)

**Configuración de Tests**:
- Mínimo 100 iteraciones por test de propiedad
- Generadores inteligentes para datos de test realistas
- Cada test debe referenciar su propiedad del documento de diseño

**Formato de Tags**:
```javascript
// Feature: reservas-y-mapa, Property 1: Validación de disponibilidad en tiempo real
test('availability check for any apartment and dates', () => {
    fc.assert(fc.property(
        apartamentoGenerator(),
        dateRangeGenerator(),
        async (apartamento, fechas) => {
            // Test implementation
        }
    ), { numRuns: 100 });
});
```

### Test Data Generation

**Generadores Inteligentes**:
- `apartamentoGenerator()`: Apartamentos con datos realistas y coordenadas GPS válidas
- `dateRangeGenerator()`: Rangos de fechas válidos (entrada < salida, entrada > hoy)
- `reservaFormGenerator()`: Formularios de reserva con datos válidos e inválidos
- `userGenerator()`: Usuarios autenticados y no autenticados

**Casos de Borde**:
- Apartamentos sin coordenadas GPS
- Fechas límite (hoy, fin de año)
- Capacidad máxima de apartamentos
- Errores de red simulados

### Integration Testing

**Flujo Completo de Reserva**:
1. Usuario ve apartamento → Click en reservar → Formulario se abre
2. Usuario completa formulario → Validación → Envío a API
3. API procesa → Respuesta → Confirmación → Redirección

**Flujo de Mapa**:
1. Carga de página → Petición de apartamentos → Creación de marcadores
2. Click en marcador → Popup → Click en detalles → Modal

**Panel Admin**:
1. Nueva reserva creada → Aparece en panel admin
2. Admin cambia estado → Se actualiza en base de datos

### Performance Testing

**Carga de Mapa**:
- Tiempo de carga con muchos apartamentos
- Rendimiento de clustering de marcadores
- Memoria utilizada por marcadores

**Formulario de Reserva**:
- Tiempo de respuesta de validación de disponibilidad
- Rendimiento de validación en tiempo real

Esta estrategia asegura que tanto los casos específicos como las propiedades universales del sistema funcionen correctamente, proporcionando confianza en la robustez de la implementación.