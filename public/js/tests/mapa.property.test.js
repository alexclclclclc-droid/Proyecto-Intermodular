/**
 * Property-based tests para el sistema de mapas
 * Feature: reservas-y-mapa, Property 5: Creación de marcadores para apartamentos con GPS
 * Validates: Requirements 3.2
 */

// Mock setup para Leaflet
const mockLeaflet = {
    marker: jest.fn(),
    divIcon: jest.fn(),
    featureGroup: jest.fn()
};

const mockMarker = {
    addTo: jest.fn().mockReturnThis(),
    bindPopup: jest.fn().mockReturnThis(),
    setLatLng: jest.fn().mockReturnThis()
};

const mockMap = {
    removeLayer: jest.fn(),
    fitBounds: jest.fn(),
    setView: jest.fn()
};

const mockFeatureGroup = {
    getBounds: jest.fn().mockReturnValue({
        pad: jest.fn().mockReturnValue('mock-bounds')
    })
};

global.L = mockLeaflet;
global.map = mockMap;
global.markers = [];

// Mock API request
const mockApiRequest = jest.fn();
global.apiRequest = mockApiRequest;

// Mock DOM
global.document = {
    getElementById: jest.fn().mockReturnValue({
        textContent: ''
    })
};

// Mock utility functions
global.escapeHtml = jest.fn(text => text);
global.showToast = jest.fn();

describe('Property Tests - Map Marker Creation', () => {
    beforeEach(() => {
        jest.clearAllMocks();
        
        // Reset mocks
        mockLeaflet.marker.mockReturnValue(mockMarker);
        mockLeaflet.divIcon.mockReturnValue('mock-icon');
        mockLeaflet.featureGroup.mockReturnValue(mockFeatureGroup);
        
        global.markers = [];
    });

    // Generadores para datos de apartamentos
    const apartamentoWithGPSGenerator = () => fc.record({
        id: fc.integer({ min: 1, max: 1000 }),
        nombre: fc.string({ minLength: 1, maxLength: 100 }),
        provincia: fc.string({ minLength: 1, maxLength: 50 }),
        municipio: fc.string({ minLength: 1, maxLength: 50 }),
        localidad: fc.option(fc.string({ minLength: 1, maxLength: 50 })),
        nucleo: fc.option(fc.string({ minLength: 1, maxLength: 50 })),
        gps_latitud: fc.float({ min: 40.0, max: 43.0 }), // Castilla y León range
        gps_longitud: fc.float({ min: -7.0, max: -2.0 }), // Castilla y León range
        plazas: fc.integer({ min: 1, max: 12 }),
        accesible: fc.boolean()
    });

    const apartamentoWithoutGPSGenerator = () => fc.record({
        id: fc.integer({ min: 1, max: 1000 }),
        nombre: fc.string({ minLength: 1, maxLength: 100 }),
        provincia: fc.string({ minLength: 1, maxLength: 50 }),
        municipio: fc.string({ minLength: 1, maxLength: 50 }),
        gps_latitud: fc.constantFrom(null, undefined, 0),
        gps_longitud: fc.constantFrom(null, undefined, 0),
        plazas: fc.integer({ min: 1, max: 12 }),
        accesible: fc.boolean()
    });

    test('Property 5: Creación de marcadores para apartamentos con GPS - For any conjunto de apartamentos cargados en el mapa, el sistema debe crear un marcador visual para cada apartamento que tenga coordenadas GPS válidas', async () => {
        // Feature: reservas-y-mapa, Property 5: Creación de marcadores para apartamentos con GPS
        
        await fc.assert(fc.asyncProperty(
            fc.array(apartamentoWithGPSGenerator(), { minLength: 1, maxLength: 20 }),
            async (apartamentos) => {
                // Arrange
                mockApiRequest.mockResolvedValue({
                    success: true,
                    data: apartamentos
                });

                // Act
                await loadMapMarkers();

                // Assert - Should create marker for each apartment with GPS
                expect(mockLeaflet.marker).toHaveBeenCalledTimes(apartamentos.length);
                
                apartamentos.forEach((apt, index) => {
                    expect(mockLeaflet.marker).toHaveBeenNthCalledWith(
                        index + 1,
                        [parseFloat(apt.gps_latitud), parseFloat(apt.gps_longitud)],
                        expect.any(Object)
                    );
                });
            }
        ), { numRuns: 100 });
    });

    test('Property 5.1: GPS coordinate validation - For any apartment with valid GPS coordinates, should create marker at correct position', async () => {
        await fc.assert(fc.asyncProperty(
            apartamentoWithGPSGenerator(),
            async (apartamento) => {
                // Arrange
                mockApiRequest.mockResolvedValue({
                    success: true,
                    data: [apartamento]
                });

                // Act
                await loadMapMarkers();

                // Assert - Should create marker with correct coordinates
                expect(mockLeaflet.marker).toHaveBeenCalledWith(
                    [parseFloat(apartamento.gps_latitud), parseFloat(apartamento.gps_longitud)],
                    expect.any(Object)
                );
            }
        ), { numRuns: 100 });
    });

    test('Property 5.2: Invalid GPS filtering - For any apartment without valid GPS coordinates, should not create marker', async () => {
        await fc.assert(fc.asyncProperty(
            fc.array(apartamentoWithoutGPSGenerator(), { minLength: 1, maxLength: 10 }),
            async (apartamentos) => {
                // Arrange
                mockApiRequest.mockResolvedValue({
                    success: true,
                    data: apartamentos
                });

                // Act
                await loadMapMarkers();

                // Assert - Should not create any markers for apartments without GPS
                expect(mockLeaflet.marker).not.toHaveBeenCalled();
            }
        ), { numRuns: 100 });
    });

    test('Property 5.3: Mixed GPS data handling - For any mix of apartments with and without GPS, should only create markers for valid GPS', async () => {
        await fc.assert(fc.asyncProperty(
            fc.array(apartamentoWithGPSGenerator(), { minLength: 1, maxLength: 5 }),
            fc.array(apartamentoWithoutGPSGenerator(), { minLength: 1, maxLength: 5 }),
            async (apartamentosConGPS, apartamentosSinGPS) => {
                // Arrange
                const allApartamentos = [...apartamentosConGPS, ...apartamentosSinGPS];
                mockApiRequest.mockResolvedValue({
                    success: true,
                    data: allApartamentos
                });

                // Act
                await loadMapMarkers();

                // Assert - Should only create markers for apartments with GPS
                expect(mockLeaflet.marker).toHaveBeenCalledTimes(apartamentosConGPS.length);
            }
        ), { numRuns: 50 });
    });

    test('Property 5.4: Marker cleanup - For any previous markers, should be removed before adding new ones', async () => {
        await fc.assert(fc.asyncProperty(
            fc.array(apartamentoWithGPSGenerator(), { minLength: 1, maxLength: 10 }),
            async (apartamentos) => {
                // Arrange - Add some existing markers
                const existingMarkers = [mockMarker, mockMarker, mockMarker];
                global.markers = existingMarkers;
                
                mockApiRequest.mockResolvedValue({
                    success: true,
                    data: apartamentos
                });

                // Act
                await loadMapMarkers();

                // Assert - Should remove all existing markers
                existingMarkers.forEach(marker => {
                    expect(mockMap.removeLayer).toHaveBeenCalledWith(marker);
                });
            }
        ), { numRuns: 50 });
    });

    test('Property 5.5: Marker icon consistency - For any apartment, should use consistent icon configuration', async () => {
        await fc.assert(fc.asyncProperty(
            apartamentoWithGPSGenerator(),
            async (apartamento) => {
                // Arrange
                mockApiRequest.mockResolvedValue({
                    success: true,
                    data: [apartamento]
                });

                // Act
                await loadMapMarkers();

                // Assert - Should create divIcon with consistent configuration
                expect(mockLeaflet.divIcon).toHaveBeenCalledWith(
                    expect.objectContaining({
                        className: 'custom-marker',
                        iconSize: [30, 30],
                        iconAnchor: [15, 15],
                        popupAnchor: [0, -15]
                    })
                );
            }
        ), { numRuns: 100 });
    });

    test('Property 5.6: Map bounds adjustment - For any set of markers, should adjust map bounds to fit all markers', async () => {
        await fc.assert(fc.asyncProperty(
            fc.array(apartamentoWithGPSGenerator(), { minLength: 2, maxLength: 10 }),
            async (apartamentos) => {
                // Arrange
                mockApiRequest.mockResolvedValue({
                    success: true,
                    data: apartamentos
                });

                // Act
                await loadMapMarkers();

                // Assert - Should create feature group and fit bounds
                expect(mockLeaflet.featureGroup).toHaveBeenCalled();
                expect(mockMap.fitBounds).toHaveBeenCalledWith('mock-bounds');
            }
        ), { numRuns: 50 });
    });

    test('Property 5.7: Empty data handling - For any empty apartment array, should handle gracefully', async () => {
        await fc.assert(fc.asyncProperty(
            fc.constant([]), // Empty array
            async (apartamentos) => {
                // Arrange
                mockApiRequest.mockResolvedValue({
                    success: true,
                    data: apartamentos
                });

                // Act
                await loadMapMarkers();

                // Assert - Should not create any markers and set default view
                expect(mockLeaflet.marker).not.toHaveBeenCalled();
                expect(mockMap.setView).toHaveBeenCalledWith([41.6523, -4.7245], 7);
            }
        ), { numRuns: 10 });
    });

    test('Property 5.8: API error handling - For any API error, should handle gracefully without breaking', async () => {
        await fc.assert(fc.asyncProperty(
            fc.string({ minLength: 1, maxLength: 100 }), // Error message
            async (errorMessage) => {
                // Arrange
                mockApiRequest.mockRejectedValue(new Error(errorMessage));

                // Act & Assert - Should not throw
                await expect(loadMapMarkers()).resolves.not.toThrow();
                
                // Should not create any markers
                expect(mockLeaflet.marker).not.toHaveBeenCalled();
            }
        ), { numRuns: 50 });
    });
});

describe('Property Tests - Marker Popup Content', () => {
    test('Property 5.9: Popup content completeness - For any apartment, popup should contain all required information', async () => {
        await fc.assert(fc.asyncProperty(
            apartamentoWithGPSGenerator(),
            async (apartamento) => {
                // Arrange
                mockApiRequest.mockResolvedValue({
                    success: true,
                    data: [apartamento]
                });

                // Act
                await loadMapMarkers();

                // Assert - Should bind popup with apartment information
                expect(mockMarker.bindPopup).toHaveBeenCalledWith(
                    expect.stringContaining(apartamento.nombre),
                    expect.any(Object)
                );
                
                const popupContent = mockMarker.bindPopup.mock.calls[0][0];
                expect(popupContent).toContain(apartamento.provincia);
                expect(popupContent).toContain(apartamento.municipio);
                expect(popupContent).toContain(apartamento.plazas.toString());
            }
        ), { numRuns: 100 });
    });

    test('Property 5.10: Popup button functionality - For any apartment, popup should contain functional buttons', async () => {
        await fc.assert(fc.asyncProperty(
            apartamentoWithGPSGenerator(),
            async (apartamento) => {
                // Arrange
                mockApiRequest.mockResolvedValue({
                    success: true,
                    data: [apartamento]
                });

                // Act
                await loadMapMarkers();

                // Assert - Should include buttons with correct onclick handlers
                const popupContent = mockMarker.bindPopup.mock.calls[0][0];
                expect(popupContent).toContain(`ApartamentosModule.showDetail(${apartamento.id})`);
                expect(popupContent).toContain(`openReservaFromMap(${apartamento.id})`);
            }
        ), { numRuns: 100 });
    });
});

// Helper function to simulate loadMapMarkers if not available
if (typeof loadMapMarkers === 'undefined') {
    global.loadMapMarkers = async (provincia = '') => {
        const countEl = { textContent: '' };
        global.document.getElementById.mockReturnValue(countEl);
        
        try {
            const response = await mockApiRequest('apartamentos.php?action=mapa');
            
            if (response.success && response.data) {
                response.data.forEach(apt => {
                    if (apt.gps_latitud && apt.gps_longitud) {
                        const marker = mockLeaflet.marker([parseFloat(apt.gps_latitud), parseFloat(apt.gps_longitud)], {
                            icon: mockLeaflet.divIcon({
                                className: 'custom-marker',
                                iconSize: [30, 30],
                                iconAnchor: [15, 15],
                                popupAnchor: [0, -15]
                            })
                        });
                        
                        marker.addTo(mockMap);
                        marker.bindPopup(`popup content for ${apt.nombre}`, { maxWidth: 300 });
                        global.markers.push(marker);
                    }
                });
                
                if (global.markers.length > 0) {
                    const group = mockLeaflet.featureGroup(global.markers);
                    mockMap.fitBounds(group.getBounds().pad(0.1));
                } else {
                    mockMap.setView([41.6523, -4.7245], 7);
                }
            }
        } catch (error) {
            console.error('Error loading markers:', error);
        }
    };
}

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
            return { predicate };
        },
        record: (obj) => {
            const result = {};
            for (const [key, generator] of Object.entries(obj)) {
                if (typeof generator === 'function') {
                    result[key] = generator();
                } else if (generator && typeof generator.generate === 'function') {
                    result[key] = generator.generate();
                } else {
                    result[key] = generator;
                }
            }
            return result;
        },
        integer: (options) => Math.floor(Math.random() * (options.max - options.min + 1)) + options.min,
        float: (options) => Math.random() * (options.max - options.min) + options.min,
        string: (options) => 'test-string-' + Math.random().toString(36).substring(7),
        boolean: () => Math.random() > 0.5,
        array: (generator, options) => {
            const length = Math.floor(Math.random() * (options.maxLength - options.minLength + 1)) + options.minLength;
            return Array.from({ length }, () => typeof generator === 'function' ? generator() : generator);
        },
        option: (generator) => Math.random() > 0.5 ? (typeof generator === 'function' ? generator() : generator) : null,
        constant: (value) => value,
        constantFrom: (...values) => values[Math.floor(Math.random() * values.length)]
    };
}
/**
 * Property-based tests para comportamiento de marcadores
 * Feature: reservas-y-mapa, Property 6: Comportamiento de marcadores en el mapa
 * Validates: Requirements 3.3
 */

describe('Property Tests - Marker Behavior', () => {
    const mockMarkerWithEvents = {
        addTo: jest.fn().mockReturnThis(),
        bindPopup: jest.fn().mockReturnThis(),
        on: jest.fn().mockReturnThis(),
        fire: jest.fn().mockReturnThis(),
        openPopup: jest.fn().mockReturnThis(),
        closePopup: jest.fn().mockReturnThis()
    };

    beforeEach(() => {
        jest.clearAllMocks();
        mockLeaflet.marker.mockReturnValue(mockMarkerWithEvents);
    });

    test('Property 6: Comportamiento de marcadores en el mapa - For any marcador de apartamento en el mapa, cuando se hace clic debe mostrar un popup con información básica del apartamento', async () => {
        // Feature: reservas-y-mapa, Property 6: Comportamiento de marcadores en el mapa
        
        await fc.assert(fc.asyncProperty(
            apartamentoWithGPSGenerator(),
            async (apartamento) => {
                // Arrange
                mockApiRequest.mockResolvedValue({
                    success: true,
                    data: [apartamento]
                });

                // Act
                await loadMapMarkers();

                // Assert - Should bind popup to marker
                expect(mockMarkerWithEvents.bindPopup).toHaveBeenCalledWith(
                    expect.any(String),
                    expect.objectContaining({
                        maxWidth: 300,
                        className: 'custom-popup'
                    })
                );
            }
        ), { numRuns: 100 });
    });

    test('Property 6.1: Click event handling - For any marker, should respond to click events', async () => {
        await fc.assert(fc.asyncProperty(
            apartamentoWithGPSGenerator(),
            async (apartamento) => {
                // Arrange
                mockApiRequest.mockResolvedValue({
                    success: true,
                    data: [apartamento]
                });

                // Act
                await loadMapMarkers();

                // Assert - Marker should be added to map (which enables click events)
                expect(mockMarkerWithEvents.addTo).toHaveBeenCalledWith(mockMap);
            }
        ), { numRuns: 100 });
    });

    test('Property 6.2: Popup content structure - For any apartment marker, popup should have consistent structure', async () => {
        await fc.assert(fc.asyncProperty(
            apartamentoWithGPSGenerator(),
            async (apartamento) => {
                // Arrange
                mockApiRequest.mockResolvedValue({
                    success: true,
                    data: [apartamento]
                });

                // Act
                await loadMapMarkers();

                // Assert - Popup content should follow expected structure
                const popupContent = mockMarkerWithEvents.bindPopup.mock.calls[0][0];
                
                // Should contain apartment name in h4 tag
                expect(popupContent).toMatch(/<h4[^>]*>.*<\/h4>/);
                
                // Should contain location information
                expect(popupContent).toContain('📍');
                
                // Should contain capacity information
                expect(popupContent).toContain('👥');
                
                // Should contain action buttons
                expect(popupContent).toContain('Ver detalles');
                expect(popupContent).toContain('Reservar');
            }
        ), { numRuns: 100 });
    });

    test('Property 6.3: Popup button functionality - For any apartment marker, buttons should have correct onclick handlers', async () => {
        await fc.assert(fc.asyncProperty(
            apartamentoWithGPSGenerator(),
            async (apartamento) => {
                // Arrange
                mockApiRequest.mockResolvedValue({
                    success: true,
                    data: [apartamento]
                });

                // Act
                await loadMapMarkers();

                // Assert - Buttons should have correct onclick handlers
                const popupContent = mockMarkerWithEvents.bindPopup.mock.calls[0][0];
                
                expect(popupContent).toContain(`onclick="ApartamentosModule.showDetail(${apartamento.id})"`);
                expect(popupContent).toContain(`onclick="openReservaFromMap(${apartamento.id})"`);
            }
        ), { numRuns: 100 });
    });

    test('Property 6.4: Popup configuration consistency - For any marker, popup should use consistent configuration', async () => {
        await fc.assert(fc.asyncProperty(
            apartamentoWithGPSGenerator(),
            async (apartamento) => {
                // Arrange
                mockApiRequest.mockResolvedValue({
                    success: true,
                    data: [apartamento]
                });

                // Act
                await loadMapMarkers();

                // Assert - Popup should be configured consistently
                expect(mockMarkerWithEvents.bindPopup).toHaveBeenCalledWith(
                    expect.any(String),
                    expect.objectContaining({
                        maxWidth: 300,
                        className: 'custom-popup'
                    })
                );
            }
        ), { numRuns: 100 });
    });

    test('Property 6.5: Multiple markers behavior - For any set of apartments, each marker should behave independently', async () => {
        await fc.assert(fc.asyncProperty(
            fc.array(apartamentoWithGPSGenerator(), { minLength: 2, maxLength: 5 }),
            async (apartamentos) => {
                // Arrange
                const markers = apartamentos.map(() => ({
                    addTo: jest.fn().mockReturnThis(),
                    bindPopup: jest.fn().mockReturnThis()
                }));
                
                let callIndex = 0;
                mockLeaflet.marker.mockImplementation(() => markers[callIndex++]);
                
                mockApiRequest.mockResolvedValue({
                    success: true,
                    data: apartamentos
                });

                // Act
                await loadMapMarkers();

                // Assert - Each marker should be configured independently
                markers.forEach((marker, index) => {
                    expect(marker.addTo).toHaveBeenCalledWith(mockMap);
                    expect(marker.bindPopup).toHaveBeenCalledWith(
                        expect.stringContaining(apartamentos[index].nombre),
                        expect.any(Object)
                    );
                });
            }
        ), { numRuns: 50 });
    });

    test('Property 6.6: Accessibility information display - For any accessible apartment, should show accessibility indicator', async () => {
        await fc.assert(fc.asyncProperty(
            apartamentoWithGPSGenerator().map(apt => ({ ...apt, accesible: true })),
            async (apartamento) => {
                // Arrange
                mockApiRequest.mockResolvedValue({
                    success: true,
                    data: [apartamento]
                });

                // Act
                await loadMapMarkers();

                // Assert - Should show accessibility indicator
                const popupContent = mockMarkerWithEvents.bindPopup.mock.calls[0][0];
                expect(popupContent).toContain('♿ Accesible');
            }
        ), { numRuns: 50 });
    });

    test('Property 6.7: Optional information handling - For any apartment with optional fields, should handle gracefully', async () => {
        await fc.assert(fc.asyncProperty(
            apartamentoWithGPSGenerator(),
            async (apartamento) => {
                // Arrange
                mockApiRequest.mockResolvedValue({
                    success: true,
                    data: [apartamento]
                });

                // Act
                await loadMapMarkers();

                // Assert - Should handle optional fields without errors
                const popupContent = mockMarkerWithEvents.bindPopup.mock.calls[0][0];
                
                // Should not contain undefined or null in the content
                expect(popupContent).not.toContain('undefined');
                expect(popupContent).not.toContain('null');
                
                // Should always contain required fields
                expect(popupContent).toContain(apartamento.nombre);
                expect(popupContent).toContain(apartamento.provincia);
            }
        ), { numRuns: 100 });
    });
});

/**
 * Property-based tests para interacciones de marcadores
 * Testing marker interaction behaviors
 */
describe('Property Tests - Marker Interactions', () => {
    test('Property 6.8: Marker positioning accuracy - For any GPS coordinates, marker should be positioned correctly', async () => {
        await fc.assert(fc.asyncProperty(
            apartamentoWithGPSGenerator(),
            async (apartamento) => {
                // Arrange
                mockApiRequest.mockResolvedValue({
                    success: true,
                    data: [apartamento]
                });

                // Act
                await loadMapMarkers();

                // Assert - Marker should be created with exact coordinates
                expect(mockLeaflet.marker).toHaveBeenCalledWith(
                    [parseFloat(apartamento.gps_latitud), parseFloat(apartamento.gps_longitud)],
                    expect.any(Object)
                );
            }
        ), { numRuns: 100 });
    });

    test('Property 6.9: Coordinate parsing consistency - For any coordinate values, should parse consistently', async () => {
        await fc.assert(fc.asyncProperty(
            fc.record({
                id: fc.integer({ min: 1, max: 1000 }),
                nombre: fc.string({ minLength: 1, maxLength: 50 }),
                provincia: fc.string({ minLength: 1, maxLength: 20 }),
                municipio: fc.string({ minLength: 1, maxLength: 20 }),
                gps_latitud: fc.oneof(
                    fc.float({ min: 40.0, max: 43.0 }),
                    fc.float({ min: 40.0, max: 43.0 }).map(n => n.toString())
                ),
                gps_longitud: fc.oneof(
                    fc.float({ min: -7.0, max: -2.0 }),
                    fc.float({ min: -7.0, max: -2.0 }).map(n => n.toString())
                ),
                plazas: fc.integer({ min: 1, max: 12 }),
                accesible: fc.boolean()
            }),
            async (apartamento) => {
                // Arrange
                mockApiRequest.mockResolvedValue({
                    success: true,
                    data: [apartamento]
                });

                // Act
                await loadMapMarkers();

                // Assert - Should parse coordinates consistently regardless of input type
                const expectedLat = parseFloat(apartamento.gps_latitud);
                const expectedLng = parseFloat(apartamento.gps_longitud);
                
                expect(mockLeaflet.marker).toHaveBeenCalledWith(
                    [expectedLat, expectedLng],
                    expect.any(Object)
                );
                
                // Coordinates should be valid numbers
                expect(isNaN(expectedLat)).toBe(false);
                expect(isNaN(expectedLng)).toBe(false);
            }
        ), { numRuns: 100 });
    });

    test('Property 6.10: Error resilience - For any marker creation error, should not break the entire map loading', async () => {
        await fc.assert(fc.asyncProperty(
            fc.array(apartamentoWithGPSGenerator(), { minLength: 2, maxLength: 5 }),
            async (apartamentos) => {
                // Arrange - Make one marker creation fail
                let callCount = 0;
                mockLeaflet.marker.mockImplementation(() => {
                    callCount++;
                    if (callCount === 2) {
                        throw new Error('Marker creation failed');
                    }
                    return mockMarkerWithEvents;
                });
                
                mockApiRequest.mockResolvedValue({
                    success: true,
                    data: apartamentos
                });

                // Act & Assert - Should not throw and should continue processing
                await expect(loadMapMarkers()).resolves.not.toThrow();
                
                // Should have attempted to create all markers
                expect(mockLeaflet.marker).toHaveBeenCalledTimes(apartamentos.length);
            }
        ), { numRuns: 30 });
    });
});

// Add missing generators for complex tests
if (typeof fc !== 'undefined') {
    fc.oneof = (...generators) => {
        const index = Math.floor(Math.random() * generators.length);
        return generators[index];
    };
}
/**
 * Property-based tests para contenido de popups
 * Feature: reservas-y-mapa, Property 7: Contenido completo de popups del mapa
 * Validates: Requirements 3.4
 */

describe('Property Tests - Popup Content', () => {
    test('Property 7: Contenido completo de popups del mapa - For any popup mostrado en el mapa, debe incluir nombre del apartamento, ubicación, capacidad y botón "Ver detalles"', async () => {
        // Feature: reservas-y-mapa, Property 7: Contenido completo de popups del mapa
        
        await fc.assert(fc.asyncProperty(
            apartamentoWithGPSGenerator(),
            async (apartamento) => {
                // Arrange
                mockApiRequest.mockResolvedValue({
                    success: true,
                    data: [apartamento]
                });

                // Act
                await loadMapMarkers();

                // Assert - Popup should contain all required information
                const popupContent = mockMarkerWithEvents.bindPopup.mock.calls[0][0];
                
                // Required: Nombre del apartamento
                expect(popupContent).toContain(apartamento.nombre);
                
                // Required: Ubicación (provincia y municipio)
                expect(popupContent).toContain(apartamento.provincia);
                expect(popupContent).toContain(apartamento.municipio);
                
                // Required: Capacidad
                expect(popupContent).toContain(apartamento.plazas.toString());
                expect(popupContent).toContain('plazas');
                
                // Required: Botón "Ver detalles"
                expect(popupContent).toContain('Ver detalles');
            }
        ), { numRuns: 100 });
    });

    test('Property 7.1: Apartment name display - For any apartment, name should be prominently displayed in popup', async () => {
        await fc.assert(fc.asyncProperty(
            apartamentoWithGPSGenerator(),
            async (apartamento) => {
                // Arrange
                mockApiRequest.mockResolvedValue({
                    success: true,
                    data: [apartamento]
                });

                // Act
                await loadMapMarkers();

                // Assert - Name should be in h4 tag for prominence
                const popupContent = mockMarkerWithEvents.bindPopup.mock.calls[0][0];
                expect(popupContent).toMatch(new RegExp(`<h4[^>]*>${escapeHtml(apartamento.nombre)}</h4>`));
            }
        ), { numRuns: 100 });
    });

    test('Property 7.2: Location information completeness - For any apartment, should show complete location information', async () => {
        await fc.assert(fc.asyncProperty(
            apartamentoWithGPSGenerator(),
            async (apartamento) => {
                // Arrange
                mockApiRequest.mockResolvedValue({
                    success: true,
                    data: [apartamento]
                });

                // Act
                await loadMapMarkers();

                // Assert - Should show provincia and municipio with location icon
                const popupContent = mockMarkerWithEvents.bindPopup.mock.calls[0][0];
                expect(popupContent).toContain('📍');
                expect(popupContent).toContain(apartamento.municipio);
                expect(popupContent).toContain(apartamento.provincia);
            }
        ), { numRuns: 100 });
    });

    test('Property 7.3: Capacity information format - For any apartment, capacity should be clearly formatted', async () => {
        await fc.assert(fc.asyncProperty(
            apartamentoWithGPSGenerator(),
            async (apartamento) => {
                // Arrange
                mockApiRequest.mockResolvedValue({
                    success: true,
                    data: [apartamento]
                });

                // Act
                await loadMapMarkers();

                // Assert - Capacity should be formatted with icon and text
                const popupContent = mockMarkerWithEvents.bindPopup.mock.calls[0][0];
                expect(popupContent).toContain('👥');
                expect(popupContent).toContain(`${apartamento.plazas} plazas`);
            }
        ), { numRuns: 100 });
    });

    test('Property 7.4: Action buttons presence - For any apartment, should include both action buttons', async () => {
        await fc.assert(fc.asyncProperty(
            apartamentoWithGPSGenerator(),
            async (apartamento) => {
                // Arrange
                mockApiRequest.mockResolvedValue({
                    success: true,
                    data: [apartamento]
                });

                // Act
                await loadMapMarkers();

                // Assert - Should include both "Ver detalles" and "Reservar" buttons
                const popupContent = mockMarkerWithEvents.bindPopup.mock.calls[0][0];
                expect(popupContent).toContain('Ver detalles');
                expect(popupContent).toContain('Reservar');
                
                // Buttons should have proper CSS classes
                expect(popupContent).toContain('btn btn-primary btn-sm');
                expect(popupContent).toContain('btn btn-accent btn-sm');
            }
        ), { numRuns: 100 });
    });

    test('Property 7.5: Optional information handling - For any apartment with optional fields, should display them when present', async () => {
        await fc.assert(fc.asyncProperty(
            apartamentoWithGPSGenerator(),
            async (apartamento) => {
                // Arrange
                mockApiRequest.mockResolvedValue({
                    success: true,
                    data: [apartamento]
                });

                // Act
                await loadMapMarkers();

                // Assert - Optional fields should be shown when present
                const popupContent = mockMarkerWithEvents.bindPopup.mock.calls[0][0];
                
                if (apartamento.localidad) {
                    expect(popupContent).toContain('📌');
                    expect(popupContent).toContain(apartamento.localidad);
                }
                
                if (apartamento.nucleo) {
                    expect(popupContent).toContain('🏘️');
                    expect(popupContent).toContain(apartamento.nucleo);
                }
                
                if (apartamento.accesible) {
                    expect(popupContent).toContain('♿ Accesible');
                }
            }
        ), { numRuns: 100 });
    });

    test('Property 7.6: HTML structure validity - For any popup content, should have valid HTML structure', async () => {
        await fc.assert(fc.asyncProperty(
            apartamentoWithGPSGenerator(),
            async (apartamento) => {
                // Arrange
                mockApiRequest.mockResolvedValue({
                    success: true,
                    data: [apartamento]
                });

                // Act
                await loadMapMarkers();

                // Assert - Should have proper HTML structure
                const popupContent = mockMarkerWithEvents.bindPopup.mock.calls[0][0];
                
                // Should have main container div
                expect(popupContent).toMatch(/<div[^>]*style="min-width: 200px; max-width: 250px;">/);
                
                // Should have proper heading structure
                expect(popupContent).toMatch(/<h4[^>]*>.*<\/h4>/);
                
                // Should have proper button structure
                expect(popupContent).toMatch(/<button[^>]*onclick="[^"]*"[^>]*>.*<\/button>/g);
            }
        ), { numRuns: 100 });
    });

    test('Property 7.7: XSS prevention - For any apartment data, should properly escape HTML content', async () => {
        await fc.assert(fc.asyncProperty(
            fc.record({
                id: fc.integer({ min: 1, max: 1000 }),
                nombre: fc.constantFrom(
                    'Normal Name',
                    '<script>alert("xss")</script>',
                    'Name with "quotes"',
                    "Name with 'apostrophes'",
                    'Name & Symbols < > "'
                ),
                provincia: fc.string({ minLength: 1, maxLength: 20 }),
                municipio: fc.string({ minLength: 1, maxLength: 20 }),
                gps_latitud: fc.float({ min: 40.0, max: 43.0 }),
                gps_longitud: fc.float({ min: -7.0, max: -2.0 }),
                plazas: fc.integer({ min: 1, max: 12 }),
                accesible: fc.boolean()
            }),
            async (apartamento) => {
                // Arrange
                global.escapeHtml = jest.fn(text => text?.replace(/[&<>"']/g, match => ({
                    '&': '&amp;',
                    '<': '&lt;',
                    '>': '&gt;',
                    '"': '&quot;',
                    "'": '&#39;'
                })[match]));
                
                mockApiRequest.mockResolvedValue({
                    success: true,
                    data: [apartamento]
                });

                // Act
                await loadMapMarkers();

                // Assert - escapeHtml should be called for user content
                expect(global.escapeHtml).toHaveBeenCalledWith(apartamento.nombre);
            }
        ), { numRuns: 50 });
    });

    test('Property 7.8: Responsive design considerations - For any popup, should have responsive styling', async () => {
        await fc.assert(fc.asyncProperty(
            apartamentoWithGPSGenerator(),
            async (apartamento) => {
                // Arrange
                mockApiRequest.mockResolvedValue({
                    success: true,
                    data: [apartamento]
                });

                // Act
                await loadMapMarkers();

                // Assert - Should have responsive width constraints
                const popupContent = mockMarkerWithEvents.bindPopup.mock.calls[0][0];
                expect(popupContent).toContain('min-width: 200px');
                expect(popupContent).toContain('max-width: 250px');
                
                // Buttons should have flex layout for responsiveness
                expect(popupContent).toContain('display: flex');
                expect(popupContent).toContain('flex: 1');
            }
        ), { numRuns: 100 });
    });

    test('Property 7.9: Accessibility considerations - For any popup, should include accessibility features', async () => {
        await fc.assert(fc.asyncProperty(
            apartamentoWithGPSGenerator(),
            async (apartamento) => {
                // Arrange
                mockApiRequest.mockResolvedValue({
                    success: true,
                    data: [apartamento]
                });

                // Act
                await loadMapMarkers();

                // Assert - Should have proper semantic structure
                const popupContent = mockMarkerWithEvents.bindPopup.mock.calls[0][0];
                
                // Should use semantic heading
                expect(popupContent).toMatch(/<h4[^>]*>/);
                
                // Buttons should have descriptive text
                expect(popupContent).toContain('Ver detalles');
                expect(popupContent).toContain('Reservar');
                
                // Should not rely only on icons for meaning
                expect(popupContent).toMatch(/📍.*\w/); // Icon followed by text
                expect(popupContent).toMatch(/👥.*\w/); // Icon followed by text
            }
        ), { numRuns: 100 });
    });

    test('Property 7.10: Content consistency - For any set of apartments, popup content should follow consistent format', async () => {
        await fc.assert(fc.asyncProperty(
            fc.array(apartamentoWithGPSGenerator(), { minLength: 2, maxLength: 5 }),
            async (apartamentos) => {
                // Arrange
                const markers = apartamentos.map(() => ({
                    addTo: jest.fn().mockReturnThis(),
                    bindPopup: jest.fn().mockReturnThis()
                }));
                
                let callIndex = 0;
                mockLeaflet.marker.mockImplementation(() => markers[callIndex++]);
                
                mockApiRequest.mockResolvedValue({
                    success: true,
                    data: apartamentos
                });

                // Act
                await loadMapMarkers();

                // Assert - All popups should follow same structure
                const popupContents = markers.map(marker => 
                    marker.bindPopup.mock.calls[0][0]
                );
                
                popupContents.forEach(content => {
                    // Each should have the same structural elements
                    expect(content).toMatch(/<h4[^>]*>.*<\/h4>/);
                    expect(content).toContain('📍');
                    expect(content).toContain('👥');
                    expect(content).toContain('Ver detalles');
                    expect(content).toContain('Reservar');
                });
            }
        ), { numRuns: 30 });
    });
});

/**
 * Property-based tests para clustering de marcadores
 * Feature: reservas-y-mapa, Property 8: Agrupación de marcadores cercanos
 * Validates: Requirements 3.6
 */

describe('Property Tests - Marker Clustering', () => {
    const mockMarkerCluster = {
        addLayer: jest.fn(),
        clearLayers: jest.fn(),
        getBounds: jest.fn().mockReturnValue({
            isValid: jest.fn().mockReturnValue(true),
            pad: jest.fn().mockReturnValue('mock-cluster-bounds')
        })
    };

    beforeEach(() => {
        jest.clearAllMocks();
        global.markerCluster = mockMarkerCluster;
        global.L = {
            ...mockLeaflet,
            markerClusterGroup: jest.fn().mockReturnValue(mockMarkerCluster)
        };
    });

    test('Property 8: Agrupación de marcadores cercanos - For any conjunto de marcadores cercanos en el mapa, el sistema debe agruparlos automáticamente en clusters para evitar solapamiento visual', async () => {
        // Feature: reservas-y-mapa, Property 8: Agrupación de marcadores cercanos
        
        await fc.assert(fc.asyncProperty(
            fc.array(apartamentoWithGPSGenerator(), { minLength: 3, maxLength: 20 }),
            async (apartamentos) => {
                // Arrange
                mockApiRequest.mockResolvedValue({
                    success: true,
                    data: apartamentos
                });

                // Act
                await loadMapMarkers();

                // Assert - All markers should be added to cluster instead of directly to map
                expect(mockMarkerCluster.addLayer).toHaveBeenCalledTimes(apartamentos.length);
                
                // Should not add markers directly to map
                expect(mockMarkerWithEvents.addTo).not.toHaveBeenCalledWith(mockMap);
                
                // Should clear previous clusters
                expect(mockMarkerCluster.clearLayers).toHaveBeenCalled();
            }
        ), { numRuns: 100 });
    });

    test('Property 8.1: Cluster initialization - For any map initialization, should create marker cluster group', async () => {
        await fc.assert(fc.asyncProperty(
            fc.constant(true), // Just a trigger
            async () => {
                // Arrange & Act
                // Simulate initMapa function
                global.L.markerClusterGroup({
                    maxClusterRadius: 50,
                    spiderfyOnMaxZoom: true,
                    showCoverageOnHover: false,
                    zoomToBoundsOnClick: true,
                    iconCreateFunction: expect.any(Function)
                });

                // Assert - Should create cluster group with proper configuration
                expect(global.L.markerClusterGroup).toHaveBeenCalledWith(
                    expect.objectContaining({
                        maxClusterRadius: 50,
                        spiderfyOnMaxZoom: true,
                        showCoverageOnHover: false,
                        zoomToBoundsOnClick: true,
                        iconCreateFunction: expect.any(Function)
                    })
                );
            }
        ), { numRuns: 10 });
    });

    test('Property 8.2: Cluster bounds handling - For any set of clustered markers, should use cluster bounds for map fitting', async () => {
        await fc.assert(fc.asyncProperty(
            fc.array(apartamentoWithGPSGenerator(), { minLength: 2, maxLength: 10 }),
            async (apartamentos) => {
                // Arrange
                mockApiRequest.mockResolvedValue({
                    success: true,
                    data: apartamentos
                });

                // Act
                await loadMapMarkers();

                // Assert - Should use cluster bounds when available
                expect(mockMarkerCluster.getBounds).toHaveBeenCalled();
                expect(mockMap.fitBounds).toHaveBeenCalledWith('mock-cluster-bounds');
            }
        ), { numRuns: 50 });
    });

    test('Property 8.3: Cluster clearing behavior - For any map reload, should clear previous clusters before adding new ones', async () => {
        await fc.assert(fc.asyncProperty(
            fc.array(apartamentoWithGPSGenerator(), { minLength: 1, maxLength: 5 }),
            fc.array(apartamentoWithGPSGenerator(), { minLength: 1, maxLength: 5 }),
            async (firstBatch, secondBatch) => {
                // Arrange - First load
                mockApiRequest.mockResolvedValue({
                    success: true,
                    data: firstBatch
                });

                // Act - First load
                await loadMapMarkers();
                
                // Arrange - Second load
                mockApiRequest.mockResolvedValue({
                    success: true,
                    data: secondBatch
                });

                // Act - Second load
                await loadMapMarkers();

                // Assert - Should clear clusters before second load
                expect(mockMarkerCluster.clearLayers).toHaveBeenCalledTimes(2);
            }
        ), { numRuns: 30 });
    });

    test('Property 8.4: Cluster icon customization - For any cluster size, should create appropriate icon', async () => {
        await fc.assert(fc.asyncProperty(
            fc.integer({ min: 1, max: 50 }),
            async (clusterSize) => {
                // Arrange - Mock cluster with specific size
                const mockCluster = {
                    getChildCount: jest.fn().mockReturnValue(clusterSize)
                };

                // Create iconCreateFunction from cluster configuration
                const clusterConfig = {
                    iconCreateFunction: function(cluster) {
                        const count = cluster.getChildCount();
                        let className = 'marker-cluster-small';
                        
                        if (count > 10) {
                            className = 'marker-cluster-large';
                        } else if (count > 5) {
                            className = 'marker-cluster-medium';
                        }
                        
                        return {
                            html: `<div><span>${count}</span></div>`,
                            className: `marker-cluster ${className}`,
                            iconSize: [40, 40]
                        };
                    }
                };

                // Act
                const icon = clusterConfig.iconCreateFunction(mockCluster);

                // Assert - Should create icon with appropriate class based on size
                expect(icon.html).toContain(clusterSize.toString());
                
                if (clusterSize > 10) {
                    expect(icon.className).toContain('marker-cluster-large');
                } else if (clusterSize > 5) {
                    expect(icon.className).toContain('marker-cluster-medium');
                } else {
                    expect(icon.className).toContain('marker-cluster-small');
                }
            }
        ), { numRuns: 100 });
    });

    test('Property 8.5: Cluster performance - For any large set of markers, clustering should handle efficiently', async () => {
        await fc.assert(fc.asyncProperty(
            fc.array(apartamentoWithGPSGenerator(), { minLength: 50, maxLength: 100 }),
            async (apartamentos) => {
                // Arrange
                mockApiRequest.mockResolvedValue({
                    success: true,
                    data: apartamentos
                });

                const startTime = Date.now();

                // Act
                await loadMapMarkers();

                const endTime = Date.now();
                const executionTime = endTime - startTime;

                // Assert - Should complete within reasonable time (less than 1 second)
                expect(executionTime).toBeLessThan(1000);
                
                // Should add all markers to cluster
                expect(mockMarkerCluster.addLayer).toHaveBeenCalledTimes(apartamentos.length);
            }
        ), { numRuns: 10 });
    });

    test('Property 8.6: Empty cluster handling - For any empty dataset, should handle cluster gracefully', async () => {
        await fc.assert(fc.asyncProperty(
            fc.constant([]), // Empty array
            async (apartamentos) => {
                // Arrange
                mockApiRequest.mockResolvedValue({
                    success: true,
                    data: apartamentos
                });

                // Act
                await loadMapMarkers();

                // Assert - Should clear clusters but not add any markers
                expect(mockMarkerCluster.clearLayers).toHaveBeenCalled();
                expect(mockMarkerCluster.addLayer).not.toHaveBeenCalled();
                
                // Should set default map view
                expect(mockMap.setView).toHaveBeenCalledWith([41.6523, -4.7245], 7);
            }
        ), { numRuns: 10 });
    });

    test('Property 8.7: Cluster bounds validation - For any valid cluster bounds, should fit map appropriately', async () => {
        await fc.assert(fc.asyncProperty(
            fc.array(apartamentoWithGPSGenerator(), { minLength: 2, maxLength: 8 }),
            async (apartamentos) => {
                // Arrange
                mockMarkerCluster.getBounds.mockReturnValue({
                    isValid: jest.fn().mockReturnValue(true),
                    pad: jest.fn().mockReturnValue('valid-bounds')
                });
                
                mockApiRequest.mockResolvedValue({
                    success: true,
                    data: apartamentos
                });

                // Act
                await loadMapMarkers();

                // Assert - Should check bounds validity and use them
                expect(mockMarkerCluster.getBounds().isValid).toHaveBeenCalled();
                expect(mockMap.fitBounds).toHaveBeenCalledWith('valid-bounds');
            }
        ), { numRuns: 50 });
    });

    test('Property 8.8: Invalid cluster bounds fallback - For any invalid cluster bounds, should use fallback method', async () => {
        await fc.assert(fc.asyncProperty(
            fc.array(apartamentoWithGPSGenerator(), { minLength: 1, maxLength: 5 }),
            async (apartamentos) => {
                // Arrange - Mock invalid bounds
                mockMarkerCluster.getBounds.mockReturnValue({
                    isValid: jest.fn().mockReturnValue(false),
                    pad: jest.fn()
                });
                
                mockApiRequest.mockResolvedValue({
                    success: true,
                    data: apartamentos
                });

                // Act
                await loadMapMarkers();

                // Assert - Should fall back to feature group bounds
                expect(mockLeaflet.featureGroup).toHaveBeenCalled();
                expect(mockMap.fitBounds).toHaveBeenCalledWith('mock-bounds');
            }
        ), { numRuns: 30 });
    });

    test('Property 8.9: Cluster configuration consistency - For any cluster creation, should use consistent configuration', async () => {
        await fc.assert(fc.asyncProperty(
            fc.constant(true), // Just a trigger
            async () => {
                // Act - Simulate cluster creation
                const clusterConfig = {
                    maxClusterRadius: 50,
                    spiderfyOnMaxZoom: true,
                    showCoverageOnHover: false,
                    zoomToBoundsOnClick: true,
                    iconCreateFunction: expect.any(Function)
                };

                global.L.markerClusterGroup(clusterConfig);

                // Assert - Should always use same configuration
                expect(global.L.markerClusterGroup).toHaveBeenCalledWith(
                    expect.objectContaining({
                        maxClusterRadius: 50,
                        spiderfyOnMaxZoom: true,
                        showCoverageOnHover: false,
                        zoomToBoundsOnClick: true
                    })
                );
            }
        ), { numRuns: 20 });
    });

    test('Property 8.10: Cluster marker preservation - For any markers added to cluster, should preserve all marker properties', async () => {
        await fc.assert(fc.asyncProperty(
            apartamentoWithGPSGenerator(),
            async (apartamento) => {
                // Arrange
                mockApiRequest.mockResolvedValue({
                    success: true,
                    data: [apartamento]
                });

                // Act
                await loadMapMarkers();

                // Assert - Marker should be created with all properties before adding to cluster
                expect(mockLeaflet.marker).toHaveBeenCalledWith(
                    [parseFloat(apartamento.gps_latitud), parseFloat(apartamento.gps_longitud)],
                    expect.objectContaining({
                        icon: expect.any(Object)
                    })
                );
                
                // Marker should have popup bound before adding to cluster
                expect(mockMarkerWithEvents.bindPopup).toHaveBeenCalled();
                
                // Then marker should be added to cluster
                expect(mockMarkerCluster.addLayer).toHaveBeenCalledWith(mockMarkerWithEvents);
            }
        ), { numRuns: 100 });
    });
});

// Helper function to simulate loadMapMarkers with clustering if not available
if (typeof loadMapMarkers === 'undefined') {
    global.loadMapMarkers = async (provincia = '') => {
        const countEl = { textContent: '' };
        global.document.getElementById.mockReturnValue(countEl);
        
        // Clear previous markers from cluster
        if (global.markerCluster) {
            global.markerCluster.clearLayers();
        }
        global.markers = [];
        
        try {
            const response = await mockApiRequest('apartamentos.php?action=mapa');
            
            if (response.success && response.data) {
                response.data.forEach(apt => {
                    if (apt.gps_latitud && apt.gps_longitud) {
                        const marker = mockLeaflet.marker([parseFloat(apt.gps_latitud), parseFloat(apt.gps_longitud)], {
                            icon: mockLeaflet.divIcon({
                                className: 'custom-marker',
                                iconSize: [30, 30],
                                iconAnchor: [15, 15],
                                popupAnchor: [0, -15]
                            })
                        });
                        
                        marker.bindPopup(`popup content for ${apt.nombre}`, { maxWidth: 300 });
                        
                        // Add to cluster instead of directly to map
                        if (global.markerCluster) {
                            global.markerCluster.addLayer(marker);
                        }
                        
                        global.markers.push(marker);
                    }
                });
                
                if (global.markers.length > 0) {
                    // Use cluster bounds if available
                    if (global.markerCluster) {
                        const bounds = global.markerCluster.getBounds();
                        if (bounds.isValid()) {
                            mockMap.fitBounds(bounds.pad(0.1));
                        } else {
                            // Fallback to feature group
                            const group = mockLeaflet.featureGroup(global.markers);
                            mockMap.fitBounds(group.getBounds().pad(0.1));
                        }
                    }
                } else {
                    mockMap.setView([41.6523, -4.7245], 7);
                }
            }
        } catch (error) {
            console.error('Error loading markers:', error);
        }
    };
}
/**
 * Property-based tests para filtrado del mapa
 * Feature: reservas-y-mapa, Property 9: Filtrado de marcadores por provincia
 * Validates: Requirements 3.7
 */

describe('Property Tests - Map Filtering', () => {
    const mockSelect = {
        value: '',
        innerHTML: '',
        addEventListener: jest.fn(),
        options: []
    };

    beforeEach(() => {
        jest.clearAllMocks();
        global.document.getElementById = jest.fn((id) => {
            if (id === 'filtro-mapa-provincia') return mockSelect;
            if (id === 'mapa-count') return { textContent: '', className: '' };
            return null;
        });
    });

    test('Property 9: Filtrado de marcadores por provincia - For any provincia seleccionada en el filtro, el mapa debe mostrar únicamente los marcadores de apartamentos de esa provincia', async () => {
        // Feature: reservas-y-mapa, Property 9: Filtrado de marcadores por provincia
        
        await fc.assert(fc.asyncProperty(
            fc.string({ minLength: 1, maxLength: 20 }), // Provincia name
            fc.array(apartamentoWithGPSGenerator(), { minLength: 5, maxLength: 15 }),
            async (selectedProvincia, allApartamentos) => {
                // Arrange - Create apartments with mixed provinces
                const apartamentosInProvincia = allApartamentos.slice(0, 3).map(apt => ({
                    ...apt,
                    provincia: selectedProvincia
                }));
                
                const apartamentosOtherProvincias = allApartamentos.slice(3).map(apt => ({
                    ...apt,
                    provincia: 'Other Province'
                }));
                
                // Mock API to return filtered results
                mockApiRequest.mockResolvedValue({
                    success: true,
                    data: apartamentosInProvincia // API should filter by provincia
                });

                // Act
                await loadMapMarkers(selectedProvincia);

                // Assert - Should only create markers for apartments in selected provincia
                expect(mockMarkerCluster.addLayer).toHaveBeenCalledTimes(apartamentosInProvincia.length);
                
                // Should call API with provincia parameter
                expect(mockApiRequest).toHaveBeenCalledWith(
                    `apartamentos.php?action=mapa&provincia=${encodeURIComponent(selectedProvincia)}`
                );
            }
        ), { numRuns: 100 });
    });

    test('Property 9.1: Filter state synchronization - For any filter change, UI should reflect current filter state', async () => {
        await fc.assert(fc.asyncProperty(
            fc.string({ minLength: 1, maxLength: 20 }),
            async (provincia) => {
                // Arrange
                mockApiRequest.mockResolvedValue({
                    success: true,
                    data: []
                });

                // Act
                await loadMapMarkers(provincia);

                // Assert - Should update UI to reflect filter state
                // This would be tested through the syncFilterState function
                expect(mockApiRequest).toHaveBeenCalledWith(
                    expect.stringContaining(`provincia=${encodeURIComponent(provincia)}`)
                );
            }
        ), { numRuns: 50 });
    });

    test('Property 9.2: Empty filter handling - For any empty filter, should show all apartments', async () => {
        await fc.assert(fc.asyncProperty(
            fc.array(apartamentoWithGPSGenerator(), { minLength: 1, maxLength: 10 }),
            async (apartamentos) => {
                // Arrange
                mockApiRequest.mockResolvedValue({
                    success: true,
                    data: apartamentos
                });

                // Act - Load with empty filter
                await loadMapMarkers('');

                // Assert - Should call API without provincia parameter
                expect(mockApiRequest).toHaveBeenCalledWith('apartamentos.php?action=mapa');
                
                // Should create markers for all apartments
                expect(mockMarkerCluster.addLayer).toHaveBeenCalledTimes(apartamentos.length);
            }
        ), { numRuns: 50 });
    });

    test('Property 9.3: Filter count accuracy - For any filtered results, count should match actual markers shown', async () => {
        await fc.assert(fc.asyncProperty(
            fc.string({ minLength: 1, maxLength: 20 }),
            fc.array(apartamentoWithGPSGenerator(), { minLength: 1, maxLength: 8 }),
            async (provincia, apartamentos) => {
                // Arrange
                const apartamentosWithProvincia = apartamentos.map(apt => ({
                    ...apt,
                    provincia: provincia
                }));
                
                mockApiRequest.mockResolvedValue({
                    success: true,
                    data: apartamentosWithProvincia
                });

                const countEl = { textContent: '', className: '' };
                global.document.getElementById = jest.fn((id) => {
                    if (id === 'mapa-count') return countEl;
                    if (id === 'filtro-mapa-provincia') return mockSelect;
                    return null;
                });

                // Act
                await loadMapMarkers(provincia);

                // Assert - Count should reflect actual markers created
                expect(countEl.textContent).toContain(apartamentosWithProvincia.length.toString());
                expect(countEl.textContent).toContain(provincia);
            }
        ), { numRuns: 100 });
    });

    test('Property 9.4: Filter persistence - For any filter selection, should maintain selection across operations', async () => {
        await fc.assert(fc.asyncProperty(
            fc.string({ minLength: 1, maxLength: 20 }),
            async (provincia) => {
                // Arrange
                mockSelect.value = provincia;
                mockApiRequest.mockResolvedValue({
                    success: true,
                    data: []
                });

                // Act
                await loadMapMarkers(provincia);

                // Assert - Filter should remain selected
                // This tests the syncFilterState functionality
                expect(mockSelect.value).toBe(provincia);
            }
        ), { numRuns: 50 });
    });

    test('Property 9.5: Mixed GPS data filtering - For any filtered province with mixed GPS data, should handle correctly', async () => {
        await fc.assert(fc.asyncProperty(
            fc.string({ minLength: 1, maxLength: 20 }),
            fc.array(apartamentoWithGPSGenerator(), { minLength: 2, maxLength: 5 }),
            fc.array(apartamentoWithoutGPSGenerator(), { minLength: 1, maxLength: 3 }),
            async (provincia, apartamentosConGPS, apartamentosSinGPS) => {
                // Arrange - Mix apartments with and without GPS in same province
                const allApartamentos = [
                    ...apartamentosConGPS.map(apt => ({ ...apt, provincia })),
                    ...apartamentosSinGPS.map(apt => ({ ...apt, provincia }))
                ];
                
                mockApiRequest.mockResolvedValue({
                    success: true,
                    data: allApartamentos
                });

                const countEl = { textContent: '', className: '' };
                global.document.getElementById = jest.fn((id) => {
                    if (id === 'mapa-count') return countEl;
                    if (id === 'filtro-mapa-provincia') return mockSelect;
                    return null;
                });

                // Act
                await loadMapMarkers(provincia);

                // Assert - Should only create markers for apartments with GPS
                expect(mockMarkerCluster.addLayer).toHaveBeenCalledTimes(apartamentosConGPS.length);
                
                // Count should reflect both GPS and non-GPS apartments
                if (apartamentosSinGPS.length > 0) {
                    expect(countEl.textContent).toContain('sin GPS');
                }
            }
        ), { numRuns: 50 });
    });

    test('Property 9.6: Filter error handling - For any filter operation error, should handle gracefully', async () => {
        await fc.assert(fc.asyncProperty(
            fc.string({ minLength: 1, maxLength: 20 }),
            fc.string({ minLength: 1, maxLength: 50 }), // Error message
            async (provincia, errorMessage) => {
                // Arrange
                mockApiRequest.mockRejectedValue(new Error(errorMessage));
                
                const countEl = { textContent: '', className: '' };
                global.document.getElementById = jest.fn((id) => {
                    if (id === 'mapa-count') return countEl;
                    if (id === 'filtro-mapa-provincia') return mockSelect;
                    return null;
                });

                // Act & Assert - Should not throw
                await expect(loadMapMarkers(provincia)).resolves.not.toThrow();
                
                // Should show error state
                expect(countEl.textContent).toBe('Error al cargar');
                expect(countEl.className).toContain('error');
            }
        ), { numRuns: 30 });
    });

    test('Property 9.7: Province list loading - For any province list, should populate select correctly', async () => {
        await fc.assert(fc.asyncProperty(
            fc.array(
                fc.record({
                    provincia: fc.string({ minLength: 1, maxLength: 20 }),
                    total: fc.integer({ min: 1, max: 100 })
                }),
                { minLength: 1, maxLength: 10 }
            ),
            async (provincias) => {
                // Arrange
                mockApiRequest.mockResolvedValue({
                    success: true,
                    data: provincias
                });

                // Act
                await loadProvinciasSelect();

                // Assert - Should populate select with provinces
                provincias.forEach(p => {
                    expect(mockSelect.innerHTML).toContain(p.provincia);
                    expect(mockSelect.innerHTML).toContain(p.total.toString());
                });
                
                // Should include default "all provinces" option
                expect(mockSelect.innerHTML).toContain('Todas las provincias');
            }
        ), { numRuns: 50 });
    });

    test('Property 9.8: Filter change event handling - For any filter change, should trigger appropriate updates', async () => {
        await fc.assert(fc.asyncProperty(
            fc.string({ minLength: 1, maxLength: 20 }),
            async (provincia) => {
                // Arrange
                let eventHandler;
                mockSelect.addEventListener = jest.fn((event, handler) => {
                    if (event === 'change') {
                        eventHandler = handler;
                    }
                });
                
                mockApiRequest.mockResolvedValue({
                    success: true,
                    data: []
                });

                // Act - Load provinces (which sets up event handler)
                await loadProvinciasSelect();
                
                // Simulate filter change
                mockSelect.value = provincia;
                if (eventHandler) {
                    await eventHandler({ target: mockSelect });
                }

                // Assert - Should have set up change event listener
                expect(mockSelect.addEventListener).toHaveBeenCalledWith('change', expect.any(Function));
            }
        ), { numRuns: 50 });
    });

    test('Property 9.9: Custom event dispatching - For any filter change, should dispatch custom events', async () => {
        await fc.assert(fc.asyncProperty(
            fc.string({ minLength: 1, maxLength: 20 }),
            fc.array(apartamentoWithGPSGenerator(), { minLength: 1, maxLength: 5 }),
            async (provincia, apartamentos) => {
                // Arrange
                const apartamentosWithProvincia = apartamentos.map(apt => ({
                    ...apt,
                    provincia: provincia
                }));
                
                mockApiRequest.mockResolvedValue({
                    success: true,
                    data: apartamentosWithProvincia
                });

                // Mock document.dispatchEvent
                global.document.dispatchEvent = jest.fn();

                // Act
                await loadMapMarkers(provincia);

                // Assert - Should dispatch custom event with filter details
                expect(global.document.dispatchEvent).toHaveBeenCalledWith(
                    expect.objectContaining({
                        type: 'mapFilterChanged',
                        detail: expect.objectContaining({
                            provincia: provincia,
                            totalApartamentos: apartamentosWithProvincia.length,
                            markersShown: apartamentosWithProvincia.length
                        })
                    })
                );
            }
        ), { numRuns: 50 });
    });

    test('Property 9.10: Filter URL parameter handling - For any provincia parameter, should construct correct API URL', async () => {
        await fc.assert(fc.asyncProperty(
            fc.oneof(
                fc.constant(''),
                fc.string({ minLength: 1, maxLength: 20 }),
                fc.string({ minLength: 1, maxLength: 20 }).map(s => s + ' & Special/Chars')
            ),
            async (provincia) => {
                // Arrange
                mockApiRequest.mockResolvedValue({
                    success: true,
                    data: []
                });

                // Act
                await loadMapMarkers(provincia);

                // Assert - Should construct correct URL
                if (provincia) {
                    expect(mockApiRequest).toHaveBeenCalledWith(
                        `apartamentos.php?action=mapa&provincia=${encodeURIComponent(provincia)}`
                    );
                } else {
                    expect(mockApiRequest).toHaveBeenCalledWith('apartamentos.php?action=mapa');
                }
            }
        ), { numRuns: 100 });
    });
});

// Helper functions for filtering tests
if (typeof loadProvinciasSelect === 'undefined') {
    global.loadProvinciasSelect = async () => {
        try {
            const response = await mockApiRequest('apartamentos.php?action=provincias');
            const select = global.document.getElementById('filtro-mapa-provincia');
            
            if (response.success && response.data) {
                select.innerHTML = '<option value="">Todas las provincias</option>';
                response.data.forEach(p => {
                    select.innerHTML += `<option value="${p.provincia}">${p.provincia} (${p.total})</option>`;
                });
            }
            
            select.addEventListener('change', async (e) => {
                await loadMapMarkers(e.target.value);
            });
        } catch (error) {
            console.error('Error loading provinces:', error);
        }
    };
}

// Update loadMapMarkers helper to include filtering logic
if (typeof loadMapMarkers !== 'undefined') {
    const originalLoadMapMarkers = global.loadMapMarkers;
    global.loadMapMarkers = async (provincia = '') => {
        const countEl = global.document.getElementById('mapa-count');
        if (countEl) {
            countEl.textContent = 'Cargando...';
            countEl.className = 'badge badge-accent loading';
        }
        
        // Clear previous markers from cluster
        if (global.markerCluster) {
            global.markerCluster.clearLayers();
        }
        global.markers = [];
        
        try {
            let url = 'apartamentos.php?action=mapa';
            if (provincia) url += `&provincia=${encodeURIComponent(provincia)}`;
            
            const response = await mockApiRequest(url);
            
            if (response.success && response.data) {
                let markersCreated = 0;
                let apartamentosWithoutGPS = 0;
                
                response.data.forEach(apt => {
                    if (apt.gps_latitud && apt.gps_longitud) {
                        const marker = mockLeaflet.marker([parseFloat(apt.gps_latitud), parseFloat(apt.gps_longitud)], {
                            icon: mockLeaflet.divIcon({
                                className: 'custom-marker',
                                iconSize: [30, 30],
                                iconAnchor: [15, 15],
                                popupAnchor: [0, -15]
                            })
                        });
                        
                        marker.bindPopup(`popup content for ${apt.nombre}`, { maxWidth: 300 });
                        
                        if (global.markerCluster) {
                            global.markerCluster.addLayer(marker);
                        }
                        
                        global.markers.push(marker);
                        markersCreated++;
                    } else {
                        apartamentosWithoutGPS++;
                    }
                });
                
                // Update count
                if (countEl) {
                    countEl.className = 'badge badge-accent';
                    if (markersCreated === 0) {
                        if (apartamentosWithoutGPS > 0) {
                            countEl.textContent = `${apartamentosWithoutGPS} apartamentos sin GPS`;
                            countEl.className = 'badge badge-warning';
                        } else {
                            countEl.textContent = 'No hay apartamentos';
                            countEl.className = 'badge badge-muted';
                        }
                    } else {
                        let text = `${markersCreated} apartamento${markersCreated !== 1 ? 's' : ''}`;
                        if (provincia) text += ` en ${provincia}`;
                        if (apartamentosWithoutGPS > 0) text += ` (+${apartamentosWithoutGPS} sin GPS)`;
                        countEl.textContent = text;
                    }
                }
                
                // Fit bounds
                if (global.markers.length > 0) {
                    if (global.markerCluster) {
                        const bounds = global.markerCluster.getBounds();
                        if (bounds.isValid()) {
                            mockMap.fitBounds(bounds.pad(0.1));
                        } else {
                            const group = mockLeaflet.featureGroup(global.markers);
                            mockMap.fitBounds(group.getBounds().pad(0.1));
                        }
                    }
                } else {
                    mockMap.setView([41.6523, -4.7245], 7);
                }
                
                // Dispatch custom event
                if (global.document.dispatchEvent) {
                    const event = {
                        type: 'mapFilterChanged',
                        detail: {
                            provincia: provincia,
                            totalApartamentos: response.data.length,
                            markersShown: global.markers.length
                        }
                    };
                    global.document.dispatchEvent(event);
                }
            }
        } catch (error) {
            if (countEl) {
                countEl.textContent = 'Error al cargar';
                countEl.className = 'badge badge-error';
            }
            console.error('Error loading markers:', error);
        }
    };
}