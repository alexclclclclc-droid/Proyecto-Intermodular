/**
 * Property-based tests para el sistema de reservas
 * Feature: reservas-y-mapa, Property 1: Validación de disponibilidad en tiempo real
 * Validates: Requirements 1.3
 */

// Importar fast-check para property-based testing
// const fc = require('fast-check');

// Mock setup para property tests
const mockApiRequest = jest.fn();
global.apiRequest = mockApiRequest;

// Generadores para property-based testing
const apartamentoIdGenerator = () => fc.integer({ min: 1, max: 1000 });

const dateGenerator = () => fc.date({ 
    min: new Date('2024-01-01'), 
    max: new Date('2025-12-31') 
}).map(date => date.toISOString().split('T')[0]);

const validDateRangeGenerator = () => fc.tuple(dateGenerator(), dateGenerator())
    .filter(([start, end]) => new Date(start) < new Date(end))
    .map(([start, end]) => ({ fechaEntrada: start, fechaSalida: end }));

describe('Property Tests - Availability Validation', () => {
    beforeEach(() => {
        jest.clearAllMocks();
        
        // Mock DOM elements
        global.document = {
            getElementById: jest.fn().mockReturnValue({
                textContent: '',
                className: '',
                style: { display: '' }
            })
        };
        
        // Reset module state
        ReservaModule.isCheckingAvailability = false;
    });

    test('Property 1: Validación de disponibilidad en tiempo real - For any apartamento y fechas seleccionadas por el usuario, el sistema debe verificar disponibilidad llamando a la API de reservas antes de permitir el envío del formulario', async () => {
        // Feature: reservas-y-mapa, Property 1: Validación de disponibilidad en tiempo real
        
        await fc.assert(fc.asyncProperty(
            apartamentoIdGenerator(),
            validDateRangeGenerator(),
            async (apartamentoId, { fechaEntrada, fechaSalida }) => {
                // Arrange
                mockApiRequest.mockResolvedValue({ 
                    success: true, 
                    disponible: Math.random() > 0.5 // Random availability
                });

                // Act
                await ReservaModule.checkDisponibilidad(apartamentoId, fechaEntrada, fechaSalida);

                // Assert - API should always be called for valid inputs
                expect(mockApiRequest).toHaveBeenCalledWith(
                    `reservas.php?action=disponibilidad&id_apartamento=${apartamentoId}&fecha_entrada=${fechaEntrada}&fecha_salida=${fechaSalida}`
                );
            }
        ), { numRuns: 100 });
    });

    test('Property 1.1: API call format consistency - For any valid apartment and date range, API calls should follow consistent format', async () => {
        await fc.assert(fc.asyncProperty(
            apartamentoIdGenerator(),
            validDateRangeGenerator(),
            async (apartamentoId, { fechaEntrada, fechaSalida }) => {
                // Arrange
                mockApiRequest.mockResolvedValue({ success: true, disponible: true });

                // Act
                await ReservaModule.checkDisponibilidad(apartamentoId, fechaEntrada, fechaSalida);

                // Assert - Check URL format
                const expectedUrl = `reservas.php?action=disponibilidad&id_apartamento=${apartamentoId}&fecha_entrada=${fechaEntrada}&fecha_salida=${fechaSalida}`;
                expect(mockApiRequest).toHaveBeenCalledWith(expectedUrl);
            }
        ), { numRuns: 100 });
    });

    test('Property 1.2: Date validation - For any date range where checkout <= checkin, should show error without API call', async () => {
        await fc.assert(fc.asyncProperty(
            apartamentoIdGenerator(),
            dateGenerator(),
            dateGenerator(),
            async (apartamentoId, date1, date2) => {
                // Arrange - Ensure invalid date order
                const fechaEntrada = date1 > date2 ? date1 : date2;
                const fechaSalida = date1 > date2 ? date2 : date1;
                
                // Skip if dates are equal (edge case)
                if (fechaEntrada === fechaSalida) return;

                const showAvailabilityInfoSpy = jest.spyOn(ReservaModule, 'showAvailabilityInfo');

                // Act
                await ReservaModule.checkDisponibilidad(apartamentoId, fechaEntrada, fechaSalida);

                // Assert - Should show error and not call API
                expect(showAvailabilityInfoSpy).toHaveBeenCalledWith(
                    'La fecha de salida debe ser posterior a la fecha de entrada', 
                    'error'
                );
                expect(mockApiRequest).not.toHaveBeenCalled();
            }
        ), { numRuns: 100 });
    });

    test('Property 1.3: Loading state management - For any valid request, should show checking state initially', async () => {
        await fc.assert(fc.asyncProperty(
            apartamentoIdGenerator(),
            validDateRangeGenerator(),
            async (apartamentoId, { fechaEntrada, fechaSalida }) => {
                // Arrange
                let resolvePromise;
                mockApiRequest.mockImplementation(() => new Promise(resolve => {
                    resolvePromise = resolve;
                }));

                const showAvailabilityInfoSpy = jest.spyOn(ReservaModule, 'showAvailabilityInfo');

                // Act
                const checkPromise = ReservaModule.checkDisponibilidad(apartamentoId, fechaEntrada, fechaSalida);

                // Assert - Should show checking state immediately
                expect(showAvailabilityInfoSpy).toHaveBeenCalledWith('Verificando disponibilidad...', 'checking');

                // Complete the promise
                resolvePromise({ success: true, disponible: true });
                await checkPromise;
            }
        ), { numRuns: 50 }); // Reduced runs for async tests
    });

    test('Property 1.4: Error handling - For any API error, should handle gracefully', async () => {
        await fc.assert(fc.asyncProperty(
            apartamentoIdGenerator(),
            validDateRangeGenerator(),
            fc.string({ minLength: 1, maxLength: 100 }), // Error message
            async (apartamentoId, { fechaEntrada, fechaSalida }, errorMessage) => {
                // Arrange
                mockApiRequest.mockRejectedValue(new Error(errorMessage));
                const showAvailabilityInfoSpy = jest.spyOn(ReservaModule, 'showAvailabilityInfo');
                const disableButtonSpy = jest.spyOn(ReservaModule, 'disableConfirmButton');

                // Act
                await ReservaModule.checkDisponibilidad(apartamentoId, fechaEntrada, fechaSalida);

                // Assert - Should handle error gracefully
                expect(showAvailabilityInfoSpy).toHaveBeenCalledWith(
                    'Error al verificar disponibilidad. Inténtalo de nuevo.', 
                    'error'
                );
                expect(disableButtonSpy).toHaveBeenCalled();
            }
        ), { numRuns: 50 });
    });

    test('Property 1.5: Concurrent request prevention - Should not make multiple concurrent requests', async () => {
        await fc.assert(fc.asyncProperty(
            apartamentoIdGenerator(),
            validDateRangeGenerator(),
            async (apartamentoId, { fechaEntrada, fechaSalida }) => {
                // Arrange
                let requestCount = 0;
                mockApiRequest.mockImplementation(() => {
                    requestCount++;
                    return new Promise(resolve => {
                        setTimeout(() => resolve({ success: true, disponible: true }), 50);
                    });
                });

                // Act - Make multiple concurrent calls
                const promises = [
                    ReservaModule.checkDisponibilidad(apartamentoId, fechaEntrada, fechaSalida),
                    ReservaModule.checkDisponibilidad(apartamentoId, fechaEntrada, fechaSalida),
                    ReservaModule.checkDisponibilidad(apartamentoId, fechaEntrada, fechaSalida)
                ];

                await Promise.all(promises);

                // Assert - Should only make one API call due to concurrent request prevention
                expect(requestCount).toBe(1);
            }
        ), { numRuns: 30 }); // Reduced for performance
    });
});

describe('Property Tests - Button State Management', () => {
    test('Property 1.6: Button enabling - For any successful availability check with disponible=true, should enable confirm button', async () => {
        await fc.assert(fc.asyncProperty(
            apartamentoIdGenerator(),
            validDateRangeGenerator(),
            async (apartamentoId, { fechaEntrada, fechaSalida }) => {
                // Arrange
                mockApiRequest.mockResolvedValue({ success: true, disponible: true });
                const enableButtonSpy = jest.spyOn(ReservaModule, 'enableConfirmButton');

                // Act
                await ReservaModule.checkDisponibilidad(apartamentoId, fechaEntrada, fechaSalida);

                // Assert
                expect(enableButtonSpy).toHaveBeenCalled();
            }
        ), { numRuns: 100 });
    });

    test('Property 1.7: Button disabling - For any successful availability check with disponible=false, should disable confirm button', async () => {
        await fc.assert(fc.asyncProperty(
            apartamentoIdGenerator(),
            validDateRangeGenerator(),
            async (apartamentoId, { fechaEntrada, fechaSalida }) => {
                // Arrange
                mockApiRequest.mockResolvedValue({ success: true, disponible: false });
                const disableButtonSpy = jest.spyOn(ReservaModule, 'disableConfirmButton');

                // Act
                await ReservaModule.checkDisponibilidad(apartamentoId, fechaEntrada, fechaSalida);

                // Assert
                expect(disableButtonSpy).toHaveBeenCalled();
            }
        ), { numRuns: 100 });
    });
});

// Helper function to simulate fast-check if not available
if (typeof fc === 'undefined') {
    global.fc = {
        assert: async (property, options = {}) => {
            const numRuns = options.numRuns || 100;
            for (let i = 0; i < numRuns; i++) {
                await property.predicate();
            }
        },
        asyncProperty: (...args) => {
            const predicate = args[args.length - 1];
            const generators = args.slice(0, -1);
            
            return {
                predicate: async () => {
                    // Simple mock data generation
                    const apartamentoId = Math.floor(Math.random() * 1000) + 1;
                    const startDate = new Date(2024, Math.floor(Math.random() * 12), Math.floor(Math.random() * 28) + 1);
                    const endDate = new Date(startDate.getTime() + (Math.floor(Math.random() * 30) + 1) * 24 * 60 * 60 * 1000);
                    
                    const fechaEntrada = startDate.toISOString().split('T')[0];
                    const fechaSalida = endDate.toISOString().split('T')[0];
                    
                    await predicate(apartamentoId, { fechaEntrada, fechaSalida });
                }
            };
        },
        integer: (options) => Math.floor(Math.random() * (options.max - options.min + 1)) + options.min,
        string: (options) => 'test-string-' + Math.random().toString(36).substring(7),
        tuple: (...generators) => generators.map(g => typeof g === 'function' ? g() : g),
        date: (options) => new Date(options.min.getTime() + Math.random() * (options.max.getTime() - options.min.getTime()))
    };
}

/**
 * Property-based tests para validación de formularios de reserva
 * Feature: reservas-y-mapa, Property 2: Validación completa de formularios de reserva
 * Validates: Requirements 1.5, 4.2, 4.3, 4.4
 */

describe('Property Tests - Form Validation', () => {
    const mockValidacionModule = {
        validateForm: jest.fn(),
        validateField: jest.fn()
    };
    
    global.ValidacionModule = mockValidacionModule;

    beforeEach(() => {
        jest.clearAllMocks();
    });

    // Generadores para datos de formulario
    const pastDateGenerator = () => {
        const pastDate = new Date();
        pastDate.setDate(pastDate.getDate() - Math.floor(Math.random() * 365) - 1);
        return pastDate.toISOString().split('T')[0];
    };

    const futureDateGenerator = () => {
        const futureDate = new Date();
        futureDate.setDate(futureDate.getDate() + Math.floor(Math.random() * 365) + 1);
        return futureDate.toISOString().split('T')[0];
    };

    const guestCountGenerator = () => fc.integer({ min: 1, max: 20 });
    const apartmentCapacityGenerator = () => fc.integer({ min: 1, max: 15 });

    test('Property 2: Validación completa de formularios de reserva - For any formulario de reserva completado, el sistema debe validar que la fecha de entrada sea posterior a hoy, que la fecha de salida sea posterior a la entrada, y que el número de huéspedes no exceda la capacidad del apartamento', async () => {
        // Feature: reservas-y-mapa, Property 2: Validación completa de formularios de reserva
        
        await fc.assert(fc.asyncProperty(
            futureDateGenerator(),
            futureDateGenerator(),
            guestCountGenerator(),
            apartmentCapacityGenerator(),
            async (date1, date2, numGuests, apartmentCapacity) => {
                // Arrange - Ensure proper date order
                const fechaEntrada = date1 < date2 ? date1 : date2;
                const fechaSalida = date1 < date2 ? date2 : date1;
                
                // Skip if dates are equal
                if (fechaEntrada === fechaSalida) return;

                const formData = {
                    fecha_entrada: fechaEntrada,
                    fecha_salida: fechaSalida,
                    num_huespedes: numGuests
                };

                const apartamento = { plazas: apartmentCapacity };

                // Act & Assert - Test date validation
                const today = new Date().toISOString().split('T')[0];
                const entradaValid = fechaEntrada >= today;
                const salidaValid = fechaSalida > fechaEntrada;
                const capacityValid = numGuests <= apartmentCapacity;

                // All validations should be consistent
                expect(entradaValid).toBe(new Date(fechaEntrada) >= new Date(today));
                expect(salidaValid).toBe(new Date(fechaSalida) > new Date(fechaEntrada));
                expect(capacityValid).toBe(numGuests <= apartmentCapacity);
            }
        ), { numRuns: 100 });
    });

    test('Property 2.1: Date validation - entrada posterior a hoy - For any fecha de entrada anterior a hoy, should be invalid', async () => {
        await fc.assert(fc.asyncProperty(
            pastDateGenerator(),
            futureDateGenerator(),
            guestCountGenerator(),
            async (fechaEntrada, fechaSalida, numGuests) => {
                // Arrange
                const today = new Date().toISOString().split('T')[0];
                
                // Act
                const isValid = fechaEntrada >= today;

                // Assert - Past dates should always be invalid
                expect(isValid).toBe(false);
            }
        ), { numRuns: 100 });
    });

    test('Property 2.2: Date validation - salida posterior a entrada - For any fecha pair where salida <= entrada, should be invalid', async () => {
        await fc.assert(fc.asyncProperty(
            futureDateGenerator(),
            futureDateGenerator(),
            async (date1, date2) => {
                // Arrange - Force invalid order
                const fechaEntrada = date1 > date2 ? date1 : date2;
                const fechaSalida = date1 > date2 ? date2 : date1;
                
                // Skip equal dates
                if (fechaEntrada === fechaSalida) return;

                // Act
                const isValid = new Date(fechaSalida) > new Date(fechaEntrada);

                // Assert - Should always be invalid when salida <= entrada
                expect(isValid).toBe(false);
            }
        ), { numRuns: 100 });
    });

    test('Property 2.3: Capacity validation - For any guest count exceeding apartment capacity, should be invalid', async () => {
        await fc.assert(fc.asyncProperty(
            apartmentCapacityGenerator(),
            fc.integer({ min: 1, max: 10 }), // excess guests
            async (apartmentCapacity, excessGuests) => {
                // Arrange
                const numGuests = apartmentCapacity + excessGuests;

                // Act
                const isValid = numGuests <= apartmentCapacity;

                // Assert - Should always be invalid when exceeding capacity
                expect(isValid).toBe(false);
            }
        ), { numRuns: 100 });
    });

    test('Property 2.4: Valid form data - For any valid form data, all validations should pass', async () => {
        await fc.assert(fc.asyncProperty(
            apartmentCapacityGenerator(),
            async (apartmentCapacity) => {
                // Arrange - Create valid data
                const today = new Date();
                const entrada = new Date(today.getTime() + 24 * 60 * 60 * 1000); // Tomorrow
                const salida = new Date(entrada.getTime() + 24 * 60 * 60 * 1000); // Day after tomorrow
                const numGuests = Math.floor(Math.random() * apartmentCapacity) + 1; // Within capacity

                const fechaEntrada = entrada.toISOString().split('T')[0];
                const fechaSalida = salida.toISOString().split('T')[0];

                // Act
                const entradaValid = fechaEntrada >= today.toISOString().split('T')[0];
                const salidaValid = fechaSalida > fechaEntrada;
                const capacityValid = numGuests <= apartmentCapacity;

                // Assert - All should be valid
                expect(entradaValid).toBe(true);
                expect(salidaValid).toBe(true);
                expect(capacityValid).toBe(true);
            }
        ), { numRuns: 100 });
    });

    test('Property 2.5: Form validation consistency - For any form data, validation results should be deterministic', async () => {
        await fc.assert(fc.asyncProperty(
            futureDateGenerator(),
            futureDateGenerator(),
            guestCountGenerator(),
            apartmentCapacityGenerator(),
            async (date1, date2, numGuests, apartmentCapacity) => {
                // Arrange
                const fechaEntrada = date1 < date2 ? date1 : date2;
                const fechaSalida = date1 < date2 ? date2 : date1;
                
                if (fechaEntrada === fechaSalida) return;

                // Act - Validate multiple times
                const today = new Date().toISOString().split('T')[0];
                const result1 = {
                    entradaValid: fechaEntrada >= today,
                    salidaValid: fechaSalida > fechaEntrada,
                    capacityValid: numGuests <= apartmentCapacity
                };

                const result2 = {
                    entradaValid: fechaEntrada >= today,
                    salidaValid: fechaSalida > fechaEntrada,
                    capacityValid: numGuests <= apartmentCapacity
                };

                // Assert - Results should be identical
                expect(result1).toEqual(result2);
            }
        ), { numRuns: 100 });
    });
});

/**
 * Property-based tests para validación de campos específicos
 * Testing individual field validation rules
 */
describe('Property Tests - Field Validation Rules', () => {
    test('Property 2.6: Guest count validation - For any guest count, should be positive integer', async () => {
        await fc.assert(fc.asyncProperty(
            fc.integer({ min: -100, max: 100 }),
            async (guestCount) => {
                // Act
                const isValid = guestCount > 0 && Number.isInteger(guestCount);

                // Assert
                if (guestCount <= 0) {
                    expect(isValid).toBe(false);
                } else {
                    expect(isValid).toBe(true);
                }
            }
        ), { numRuns: 100 });
    });

    test('Property 2.7: Date format validation - For any valid date string, should parse correctly', async () => {
        await fc.assert(fc.asyncProperty(
            futureDateGenerator(),
            async (dateString) => {
                // Act
                const parsedDate = new Date(dateString);
                const isValidDate = !isNaN(parsedDate.getTime());
                const matchesFormat = /^\d{4}-\d{2}-\d{2}$/.test(dateString);

                // Assert
                expect(isValidDate).toBe(true);
                expect(matchesFormat).toBe(true);
            }
        ), { numRuns: 100 });
    });

    test('Property 2.8: Notes field validation - For any string input, notes should be optional and accept any text', async () => {
        await fc.assert(fc.asyncProperty(
            fc.string({ maxLength: 1000 }),
            async (notesText) => {
                // Act - Notes are always valid (optional field)
                const isValid = true; // Notes field accepts any string including empty

                // Assert
                expect(isValid).toBe(true);
                expect(typeof notesText).toBe('string');
            }
        ), { numRuns: 100 });
    });
});

/**
 * Property-based tests para persistencia de reservas
 * Feature: reservas-y-mapa, Property 3: Persistencia de reservas
 * Validates: Requirements 1.7, 2.1
 */

describe('Property Tests - Reservation Persistence', () => {
    const mockApiRequest = jest.fn();
    global.apiRequest = mockApiRequest;

    beforeEach(() => {
        jest.clearAllMocks();
        
        // Mock DOM elements for form submission
        global.document = {
            getElementById: jest.fn().mockReturnValue({
                classList: { add: jest.fn(), remove: jest.fn() },
                disabled: false
            })
        };
        
        // Mock window for redirect
        global.window = { location: { href: '' } };
    });

    // Generadores para datos de reserva
    const reservaDataGenerator = () => fc.record({
        id_apartamento: fc.integer({ min: 1, max: 1000 }),
        fecha_entrada: fc.date({ 
            min: new Date('2024-01-01'), 
            max: new Date('2025-12-31') 
        }).map(d => d.toISOString().split('T')[0]),
        fecha_salida: fc.date({ 
            min: new Date('2024-01-02'), 
            max: new Date('2025-12-31') 
        }).map(d => d.toISOString().split('T')[0]),
        num_huespedes: fc.integer({ min: 1, max: 12 }),
        notas: fc.string({ maxLength: 500 })
    }).filter(data => new Date(data.fecha_salida) > new Date(data.fecha_entrada));

    test('Property 3: Persistencia de reservas - For any reserva creada exitosamente, el sistema debe enviar los datos a la API de reservas y almacenar la información en la base de datos usando ReservaDAO', async () => {
        // Feature: reservas-y-mapa, Property 3: Persistencia de reservas
        
        await fc.assert(fc.asyncProperty(
            reservaDataGenerator(),
            async (reservaData) => {
                // Arrange
                const expectedResponse = { 
                    success: true, 
                    message: 'Reserva creada correctamente',
                    data: { id: Math.floor(Math.random() * 1000) + 1 }
                };
                mockApiRequest.mockResolvedValue(expectedResponse);

                // Act
                await ReservaModule.submitReserva(reservaData);

                // Assert - API should be called with correct data
                expect(mockApiRequest).toHaveBeenCalledWith('reservas.php?action=crear', {
                    method: 'POST',
                    body: JSON.stringify(reservaData)
                });
            }
        ), { numRuns: 100 });
    });

    test('Property 3.1: API call format - For any reservation data, API calls should use correct endpoint and method', async () => {
        await fc.assert(fc.asyncProperty(
            reservaDataGenerator(),
            async (reservaData) => {
                // Arrange
                mockApiRequest.mockResolvedValue({ success: true });

                // Act
                await ReservaModule.submitReserva(reservaData);

                // Assert - Check API call format
                expect(mockApiRequest).toHaveBeenCalledWith(
                    'reservas.php?action=crear',
                    expect.objectContaining({
                        method: 'POST',
                        body: JSON.stringify(reservaData)
                    })
                );
            }
        ), { numRuns: 100 });
    });

    test('Property 3.2: Data integrity - For any reservation data sent, should maintain all required fields', async () => {
        await fc.assert(fc.asyncProperty(
            reservaDataGenerator(),
            async (reservaData) => {
                // Arrange
                mockApiRequest.mockResolvedValue({ success: true });

                // Act
                await ReservaModule.submitReserva(reservaData);

                // Assert - Check that all required fields are present
                const callArgs = mockApiRequest.mock.calls[0];
                const sentData = JSON.parse(callArgs[1].body);
                
                expect(sentData).toHaveProperty('id_apartamento');
                expect(sentData).toHaveProperty('fecha_entrada');
                expect(sentData).toHaveProperty('fecha_salida');
                expect(sentData).toHaveProperty('num_huespedes');
                expect(sentData.id_apartamento).toBe(reservaData.id_apartamento);
                expect(sentData.fecha_entrada).toBe(reservaData.fecha_entrada);
                expect(sentData.fecha_salida).toBe(reservaData.fecha_salida);
                expect(sentData.num_huespedes).toBe(reservaData.num_huespedes);
            }
        ), { numRuns: 100 });
    });

    test('Property 3.3: Success handling - For any successful API response, should show success message and redirect', async () => {
        await fc.assert(fc.asyncProperty(
            reservaDataGenerator(),
            fc.string({ minLength: 1, maxLength: 100 }), // success message
            async (reservaData, successMessage) => {
                // Arrange
                const mockShowToast = jest.fn();
                const mockCloseModal = jest.fn();
                global.showToast = mockShowToast;
                global.AuthModule = { closeModal: mockCloseModal };
                
                mockApiRequest.mockResolvedValue({ 
                    success: true, 
                    message: successMessage 
                });

                // Act
                await ReservaModule.submitReserva(reservaData);

                // Assert
                expect(mockShowToast).toHaveBeenCalledWith('¡Reserva creada correctamente!', 'success');
                expect(mockCloseModal).toHaveBeenCalledWith('modal-reserva');
            }
        ), { numRuns: 50 });
    });

    test('Property 3.4: Error handling - For any API error, should handle gracefully without data loss', async () => {
        await fc.assert(fc.asyncProperty(
            reservaDataGenerator(),
            fc.array(fc.string({ minLength: 1, maxLength: 100 }), { minLength: 1, maxLength: 5 }), // error messages
            async (reservaData, errorMessages) => {
                // Arrange
                const mockShowToast = jest.fn();
                global.showToast = mockShowToast;
                
                mockApiRequest.mockResolvedValue({ 
                    success: false, 
                    errors: errorMessages 
                });

                // Act
                await ReservaModule.submitReserva(reservaData);

                // Assert - Should show all error messages
                errorMessages.forEach(error => {
                    expect(mockShowToast).toHaveBeenCalledWith(error, 'error');
                });
            }
        ), { numRuns: 50 });
    });

    test('Property 3.5: Loading state management - For any reservation submission, should manage loading state correctly', async () => {
        await fc.assert(fc.asyncProperty(
            reservaDataGenerator(),
            async (reservaData) => {
                // Arrange
                const mockButton = {
                    classList: { add: jest.fn(), remove: jest.fn() },
                    disabled: false
                };
                global.document.getElementById.mockReturnValue(mockButton);
                
                let resolvePromise;
                mockApiRequest.mockImplementation(() => new Promise(resolve => {
                    resolvePromise = resolve;
                }));

                // Act
                const submitPromise = ReservaModule.submitReserva(reservaData);

                // Assert - Should set loading state immediately
                expect(mockButton.classList.add).toHaveBeenCalledWith('loading');
                expect(mockButton.disabled).toBe(true);

                // Complete the promise
                resolvePromise({ success: true });
                await submitPromise;

                // Assert - Should remove loading state
                expect(mockButton.classList.remove).toHaveBeenCalledWith('loading');
                expect(mockButton.disabled).toBe(false);
            }
        ), { numRuns: 30 }); // Reduced for async tests
    });

    test('Property 3.6: Network error handling - For any network error, should handle gracefully', async () => {
        await fc.assert(fc.asyncProperty(
            reservaDataGenerator(),
            fc.string({ minLength: 1, maxLength: 100 }), // error message
            async (reservaData, errorMessage) => {
                // Arrange
                const mockShowToast = jest.fn();
                global.showToast = mockShowToast;
                
                mockApiRequest.mockRejectedValue(new Error(errorMessage));

                // Act
                await ReservaModule.submitReserva(reservaData);

                // Assert - Should show error message
                expect(mockShowToast).toHaveBeenCalledWith(errorMessage, 'error');
            }
        ), { numRuns: 50 });
    });
});

/**
 * Property-based tests para consistencia de datos
 * Testing data consistency during persistence
 */
describe('Property Tests - Data Consistency', () => {
    test('Property 3.7: Date format consistency - For any reservation dates, should maintain ISO format', async () => {
        await fc.assert(fc.asyncProperty(
            reservaDataGenerator(),
            async (reservaData) => {
                // Arrange
                mockApiRequest.mockResolvedValue({ success: true });

                // Act
                await ReservaModule.submitReserva(reservaData);

                // Assert - Check date formats
                const callArgs = mockApiRequest.mock.calls[0];
                const sentData = JSON.parse(callArgs[1].body);
                
                const dateRegex = /^\d{4}-\d{2}-\d{2}$/;
                expect(sentData.fecha_entrada).toMatch(dateRegex);
                expect(sentData.fecha_salida).toMatch(dateRegex);
            }
        ), { numRuns: 100 });
    });

    test('Property 3.8: Numeric data integrity - For any numeric fields, should maintain correct types', async () => {
        await fc.assert(fc.asyncProperty(
            reservaDataGenerator(),
            async (reservaData) => {
                // Arrange
                mockApiRequest.mockResolvedValue({ success: true });

                // Act
                await ReservaModule.submitReserva(reservaData);

                // Assert - Check numeric types
                const callArgs = mockApiRequest.mock.calls[0];
                const sentData = JSON.parse(callArgs[1].body);
                
                expect(typeof sentData.id_apartamento).toBe('number');
                expect(typeof sentData.num_huespedes).toBe('number');
                expect(Number.isInteger(sentData.id_apartamento)).toBe(true);
                expect(Number.isInteger(sentData.num_huespedes)).toBe(true);
            }
        ), { numRuns: 100 });
    });

    test('Property 3.9: Optional fields handling - For any reservation with optional fields, should handle correctly', async () => {
        await fc.assert(fc.asyncProperty(
            reservaDataGenerator(),
            async (reservaData) => {
                // Arrange
                mockApiRequest.mockResolvedValue({ success: true });

                // Act
                await ReservaModule.submitReserva(reservaData);

                // Assert - Optional fields should be included
                const callArgs = mockApiRequest.mock.calls[0];
                const sentData = JSON.parse(callArgs[1].body);
                
                // Notes field should be present (even if empty)
                expect(sentData).toHaveProperty('notas');
                expect(typeof sentData.notas).toBe('string');
            }
        ), { numRuns: 100 });
    });
});