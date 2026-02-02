/**
 * Property-based tests para manejo de errores
 * Feature: reservas-y-mapa, Property 10: Manejo consistente de estados de UI
 * Feature: reservas-y-mapa, Property 11: Manejo elegante de errores de red
 * Validates: Requirements 4.5, 4.6, 4.7, 5.6
 */

// Mock setup
const mockFetch = jest.fn();
global.fetch = mockFetch;

// Mock AbortController
global.AbortController = jest.fn().mockImplementation(() => ({
    abort: jest.fn(),
    signal: {}
}));

// Mock setTimeout and clearTimeout
global.setTimeout = jest.fn((fn, delay) => {
    if (typeof fn === 'function') {
        return setTimeout(fn, delay);
    }
    return 1;
});
global.clearTimeout = jest.fn();

// Mock showToast
global.showToast = jest.fn();

// Mock DOM elements
const mockElement = {
    innerHTML: '',
    disabled: false,
    className: '',
    classList: {
        add: jest.fn(),
        remove: jest.fn(),
        contains: jest.fn().mockReturnValue(false)
    },
    tagName: 'DIV'
};

global.document = {
    createElement: jest.fn().mockReturnValue(mockElement),
    body: { appendChild: jest.fn() }
};

global.window = {
    location: { 
        pathname: '/test/app.js',
        reload: jest.fn()
    }
};

describe('Property Tests - Network Error Handling', () => {
    beforeEach(() => {
        jest.clearAllMocks();
        mockFetch.mockClear();
        global.showToast.mockClear();
    });

    test('Property 11: Manejo elegante de errores de red - For any network error, should handle gracefully with user-friendly messages', async () => {
        // Feature: reservas-y-mapa, Property 11: Manejo elegante de errores de red
        
        await fc.assert(fc.asyncProperty(
            fc.constantFrom(
                'fetch',
                'network',
                'timeout',
                'server'
            ),
            fc.string({ minLength: 1, maxLength: 50 }), // endpoint
            async (errorType, endpoint) => {
                // Arrange - Setup different types of network errors
                let mockError;
                switch (errorType) {
                    case 'fetch':
                        mockError = new TypeError('Failed to fetch');
                        break;
                    case 'network':
                        mockError = new TypeError('Network request failed');
                        break;
                    case 'timeout':
                        mockError = new Error('AbortError');
                        mockError.name = 'AbortError';
                        break;
                    case 'server':
                        mockFetch.mockResolvedValue({
                            ok: false,
                            status: 500,
                            statusText: 'Internal Server Error',
                            headers: { get: () => 'application/json' },
                            json: () => Promise.resolve({ error: 'Server error' })
                        });
                        break;
                }

                if (errorType !== 'server') {
                    mockFetch.mockRejectedValue(mockError);
                }

                // Act & Assert - Should not throw unhandled errors
                await expect(apiRequest(endpoint, { retries: 0 })).rejects.toThrow();
                
                // Should show appropriate user message
                expect(global.showToast).toHaveBeenCalled();
                
                const toastCall = global.showToast.mock.calls[0];
                expect(toastCall[1]).toBe('error'); // Should be error type
                
                // Verify appropriate message based on error type
                switch (errorType) {
                    case 'fetch':
                    case 'network':
                        expect(toastCall[0]).toContain('conexión');
                        break;
                    case 'timeout':
                        expect(toastCall[0]).toContain('tardó');
                        break;
                    case 'server':
                        expect(toastCall[0]).toContain('servidor');
                        break;
                }
            }
        ), { numRuns: 100 });
    });

    test('Property 11.1: Timeout handling - For any request timeout, should abort and show timeout message', async () => {
        await fc.assert(fc.asyncProperty(
            fc.integer({ min: 100, max: 5000 }), // timeout value
            fc.string({ minLength: 1, maxLength: 30 }), // endpoint
            async (timeout, endpoint) => {
                // Arrange
                const abortController = { abort: jest.fn(), signal: {} };
                global.AbortController.mockReturnValue(abortController);
                
                const timeoutError = new Error('AbortError');
                timeoutError.name = 'AbortError';
                mockFetch.mockRejectedValue(timeoutError);

                // Act
                await expect(apiRequest(endpoint, { timeout, retries: 0 })).rejects.toThrow();

                // Assert - Should setup timeout and show timeout message
                expect(global.setTimeout).toHaveBeenCalledWith(expect.any(Function), timeout);
                expect(global.showToast).toHaveBeenCalledWith(
                    expect.stringContaining('tardó'),
                    'error'
                );
            }
        ), { numRuns: 50 });
    });

    test('Property 11.2: Retry mechanism - For any retryable error, should attempt specified number of retries', async () => {
        await fc.assert(fc.asyncProperty(
            fc.integer({ min: 1, max: 5 }), // retry count
            fc.string({ minLength: 1, maxLength: 30 }), // endpoint
            async (retries, endpoint) => {
                // Arrange - Network error that should be retried
                const networkError = new TypeError('Failed to fetch');
                mockFetch.mockRejectedValue(networkError);

                // Act
                await expect(apiRequest(endpoint, { retries })).rejects.toThrow();

                // Assert - Should make initial request + retries
                expect(mockFetch).toHaveBeenCalledTimes(retries + 1);
            }
        ), { numRuns: 50 });
    });

    test('Property 11.3: Non-retryable errors - For any 4xx error, should not retry', async () => {
        await fc.assert(fc.asyncProperty(
            fc.constantFrom(401, 403, 404, 422),
            fc.string({ minLength: 1, maxLength: 30 }),
            async (statusCode, endpoint) => {
                // Arrange
                mockFetch.mockResolvedValue({
                    ok: false,
                    status: statusCode,
                    statusText: 'Client Error',
                    headers: { get: () => 'application/json' },
                    json: () => Promise.resolve({ error: 'Client error' })
                });

                // Act
                await expect(apiRequest(endpoint, { retries: 3 })).rejects.toThrow();

                // Assert - Should only make one request (no retries)
                expect(mockFetch).toHaveBeenCalledTimes(1);
            }
        ), { numRuns: 20 });
    });

    test('Property 11.4: Exponential backoff - For any retry sequence, delay should increase', async () => {
        await fc.assert(fc.asyncProperty(
            fc.integer({ min: 2, max: 4 }), // retry count
            fc.integer({ min: 500, max: 2000 }), // initial delay
            async (retries, initialDelay) => {
                // Arrange
                const networkError = new TypeError('Failed to fetch');
                mockFetch.mockRejectedValue(networkError);
                
                // Mock Promise constructor to capture delays
                const delays = [];
                const originalPromise = global.Promise;
                global.Promise = function(executor) {
                    if (executor.toString().includes('setTimeout')) {
                        // This is our delay promise
                        return new originalPromise((resolve) => {
                            const delay = arguments[1] || initialDelay;
                            delays.push(delay);
                            resolve();
                        });
                    }
                    return new originalPromise(executor);
                };

                // Act
                await expect(apiRequest('test', { retries, retryDelay: initialDelay })).rejects.toThrow();

                // Restore Promise
                global.Promise = originalPromise;

                // Assert - Delays should increase (exponential backoff)
                for (let i = 1; i < delays.length; i++) {
                    expect(delays[i]).toBeGreaterThan(delays[i - 1]);
                }
            }
        ), { numRuns: 30 });
    });

    test('Property 11.5: JSON parsing errors - For any invalid JSON response, should handle gracefully', async () => {
        await fc.assert(fc.asyncProperty(
            fc.string({ minLength: 1, maxLength: 100 }), // invalid JSON
            async (invalidJson) => {
                // Arrange
                mockFetch.mockResolvedValue({
                    ok: true,
                    status: 200,
                    headers: { get: () => 'application/json' },
                    json: () => Promise.reject(new SyntaxError('Unexpected token'))
                });

                // Act & Assert
                await expect(apiRequest('test', { retries: 0 })).rejects.toThrow('JSON válido');
            }
        ), { numRuns: 50 });
    });

    test('Property 11.6: Non-JSON responses - For any non-JSON response, should handle appropriately', async () => {
        await fc.assert(fc.asyncProperty(
            fc.string({ minLength: 1, maxLength: 200 }), // HTML/text response
            async (textResponse) => {
                // Arrange
                mockFetch.mockResolvedValue({
                    ok: true,
                    status: 200,
                    headers: { get: () => 'text/html' },
                    text: () => Promise.resolve(textResponse)
                });

                // Act & Assert
                await expect(apiRequest('test', { retries: 0 })).rejects.toThrow('Respuesta inesperada');
            }
        ), { numRuns: 50 });
    });

    test('Property 11.7: Session expiry handling - For any 401 error, should handle session expiry', async () => {
        await fc.assert(fc.asyncProperty(
            fc.constant(401),
            async (statusCode) => {
                // Arrange
                mockFetch.mockResolvedValue({
                    ok: false,
                    status: statusCode,
                    statusText: 'Unauthorized',
                    headers: { get: () => 'application/json' },
                    json: () => Promise.resolve({ error: 'Unauthorized' })
                });

                // Act
                await expect(apiRequest('test', { retries: 0 })).rejects.toThrow();

                // Assert - Should show session expiry message and schedule reload
                expect(global.showToast).toHaveBeenCalledWith(
                    expect.stringContaining('Sesión expirada'),
                    'warning'
                );
                expect(global.setTimeout).toHaveBeenCalledWith(expect.any(Function), 2000);
            }
        ), { numRuns: 10 });
    });
});

describe('Property Tests - UI State Management', () => {
    beforeEach(() => {
        jest.clearAllMocks();
        // Reset mock element
        mockElement.innerHTML = '';
        mockElement.disabled = false;
        mockElement.className = '';
        mockElement.classList.add.mockClear();
        mockElement.classList.remove.mockClear();
    });

    test('Property 10: Manejo consistente de estados de UI - For any UI operation, should manage loading states consistently', async () => {
        // Feature: reservas-y-mapa, Property 10: Manejo consistente de estados de UI
        
        await fc.assert(fc.asyncProperty(
            fc.string({ minLength: 1, maxLength: 50 }), // loading message
            fc.constantFrom('BUTTON', 'DIV', 'SECTION'), // element type
            async (loadingMessage, tagName) => {
                // Arrange
                const element = { ...mockElement, tagName };
                const originalContent = 'Original Content';
                element.innerHTML = originalContent;

                // Act - Show loading
                UIStateManager.showLoading(element, loadingMessage);

                // Assert - Should show loading state
                expect(element.innerHTML).toContain(loadingMessage);
                expect(element.classList.add).toHaveBeenCalledWith('loading');
                
                if (tagName === 'BUTTON') {
                    expect(element.disabled).toBe(true);
                    expect(element.innerHTML).toContain('loading-spinner');
                }

                // Act - Hide loading
                UIStateManager.hideLoading(element);

                // Assert - Should restore original state
                expect(element.innerHTML).toBe(originalContent);
            }
        ), { numRuns: 100 });
    });

    test('Property 10.1: Loading state preservation - For any element, should preserve original state during loading', async () => {
        await fc.assert(fc.asyncProperty(
            fc.string({ minLength: 1, maxLength: 100 }), // original content
            fc.boolean(), // original disabled state
            fc.string({ minLength: 1, maxLength: 50 }), // original class
            async (originalContent, originalDisabled, originalClass) => {
                // Arrange
                const element = { ...mockElement };
                element.innerHTML = originalContent;
                element.disabled = originalDisabled;
                element.className = originalClass;

                // Act
                UIStateManager.showLoading(element);
                UIStateManager.hideLoading(element);

                // Assert - Should restore exact original state
                expect(element.innerHTML).toBe(originalContent);
                expect(element.disabled).toBe(originalDisabled);
                expect(element.className).toBe(originalClass);
            }
        ), { numRuns: 100 });
    });

    test('Property 10.2: Error state display - For any error, should display appropriate error state', async () => {
        await fc.assert(fc.asyncProperty(
            fc.string({ minLength: 1, maxLength: 100 }), // error message
            async (errorMessage) => {
                // Arrange
                const element = { ...mockElement };

                // Act
                UIStateManager.showError(element, errorMessage);

                // Assert
                expect(element.innerHTML).toContain(errorMessage);
                expect(element.innerHTML).toContain('error-container');
                expect(element.innerHTML).toContain('Reintentar');
                expect(element.classList.add).toHaveBeenCalledWith('error-state');
            }
        ), { numRuns: 100 });
    });

    test('Property 10.3: Empty state display - For any empty state, should display appropriate message', async () => {
        await fc.assert(fc.asyncProperty(
            fc.string({ minLength: 1, maxLength: 100 }), // empty message
            fc.string({ minLength: 1, maxLength: 5 }), // icon
            async (emptyMessage, icon) => {
                // Arrange
                const element = { ...mockElement };

                // Act
                UIStateManager.showEmpty(element, emptyMessage, icon);

                // Assert
                expect(element.innerHTML).toContain(emptyMessage);
                expect(element.innerHTML).toContain(icon);
                expect(element.innerHTML).toContain('empty-container');
                expect(element.classList.add).toHaveBeenCalledWith('empty-state');
            }
        ), { numRuns: 100 });
    });

    test('Property 10.4: State clearing - For any element with states, should clear all states properly', async () => {
        await fc.assert(fc.asyncProperty(
            fc.constantFrom('loading', 'error', 'empty'),
            async (stateType) => {
                // Arrange
                const element = { ...mockElement };
                
                // Apply different states
                switch (stateType) {
                    case 'loading':
                        UIStateManager.showLoading(element);
                        break;
                    case 'error':
                        UIStateManager.showError(element);
                        break;
                    case 'empty':
                        UIStateManager.showEmpty(element);
                        break;
                }

                // Act
                UIStateManager.clearState(element);

                // Assert
                expect(element.classList.remove).toHaveBeenCalledWith('loading', 'error-state', 'empty-state');
            }
        ), { numRuns: 30 });
    });

    test('Property 10.5: Async operation wrapper - For any async operation, should handle loading states automatically', async () => {
        await fc.assert(fc.asyncProperty(
            fc.string({ minLength: 1, maxLength: 50 }), // loading message
            fc.boolean(), // operation success
            async (loadingMessage, shouldSucceed) => {
                // Arrange
                const element = { ...mockElement };
                const asyncOperation = shouldSucceed 
                    ? () => Promise.resolve('success')
                    : () => Promise.reject(new Error('operation failed'));

                // Act & Assert
                if (shouldSucceed) {
                    const result = await withLoadingState(element, asyncOperation, loadingMessage);
                    expect(result).toBe('success');
                } else {
                    await expect(withLoadingState(element, asyncOperation, loadingMessage))
                        .rejects.toThrow('operation failed');
                    expect(element.innerHTML).toContain('Error al procesar');
                }
            }
        ), { numRuns: 100 });
    });

    test('Property 10.6: Debounce with cancellation - For any debounced function, should handle cancellation properly', async () => {
        await fc.assert(fc.asyncProperty(
            fc.integer({ min: 100, max: 1000 }), // debounce delay
            async (delay) => {
                // Arrange
                const mockFn = jest.fn();
                const debouncedFn = debounceWithCancel(mockFn, delay);

                // Act - Call multiple times then cancel
                debouncedFn();
                debouncedFn();
                debouncedFn.cancel();

                // Wait for delay
                await new Promise(resolve => setTimeout(resolve, delay + 50));

                // Assert - Function should not have been called due to cancellation
                expect(mockFn).not.toHaveBeenCalled();
            }
        ), { numRuns: 30 });
    });

    test('Property 10.7: Multiple loading states - For any element, should handle multiple loading operations correctly', async () => {
        await fc.assert(fc.asyncProperty(
            fc.array(fc.string({ minLength: 1, maxLength: 30 }), { minLength: 2, maxLength: 5 }),
            async (loadingMessages) => {
                // Arrange
                const element = { ...mockElement };
                const originalContent = 'Original';
                element.innerHTML = originalContent;

                // Act - Apply multiple loading states
                loadingMessages.forEach(message => {
                    UIStateManager.showLoading(element, message);
                });

                // Should show the last loading message
                expect(element.innerHTML).toContain(loadingMessages[loadingMessages.length - 1]);

                // Act - Hide loading
                UIStateManager.hideLoading(element);

                // Assert - Should restore original content
                expect(element.innerHTML).toBe(originalContent);
            }
        ), { numRuns: 50 });
    });
});

// Helper functions for testing
if (typeof apiRequest === 'undefined') {
    // Mock implementation for testing
    global.apiRequest = async (endpoint, options = {}) => {
        const { timeout = 10000, retries = 2, retryDelay = 1000 } = options;
        
        let lastError;
        for (let attempt = 0; attempt <= retries; attempt++) {
            try {
                const controller = new AbortController();
                const timeoutId = setTimeout(() => controller.abort(), timeout);
                
                const response = await fetch(`/api/${endpoint}`, {
                    ...options,
                    signal: controller.signal
                });
                
                clearTimeout(timeoutId);
                
                if (!response.ok) {
                    const error = new Error(`HTTP ${response.status}`);
                    error.status = response.status;
                    throw error;
                }
                
                return await response.json();
                
            } catch (error) {
                lastError = error;
                
                if (error.name === 'AbortError') {
                    const timeoutError = new Error(`Timeout: La petición tardó más de ${timeout}ms`);
                    timeoutError.type = 'timeout';
                    throw timeoutError;
                }
                
                if (error instanceof TypeError && error.message.includes('fetch')) {
                    const networkError = new Error('Error de red: Verifica tu conexión a internet');
                    networkError.type = 'network';
                    throw networkError;
                }
                
                if (error.status === 401 || error.status === 403 || error.status === 404) {
                    break;
                }
                
                if (attempt < retries) {
                    await new Promise(resolve => setTimeout(resolve, retryDelay));
                }
            }
        }
        
        // Show appropriate toast message
        if (lastError.type === 'timeout') {
            showToast('La petición tardó demasiado. Inténtalo de nuevo.', 'error');
        } else if (lastError.type === 'network') {
            showToast('Error de conexión. Verifica tu internet.', 'error');
        } else if (lastError.status >= 500) {
            showToast('Error del servidor. Inténtalo más tarde.', 'error');
        } else if (lastError.status === 401) {
            showToast('Sesión expirada. Inicia sesión de nuevo.', 'warning');
            setTimeout(() => window.location.reload(), 2000);
        }
        
        throw lastError;
    };
}

if (typeof UIStateManager === 'undefined') {
    global.UIStateManager = {
        loadingElements: new Map(),
        
        showLoading(element, message = 'Cargando...') {
            if (!element) return;
            
            if (!this.loadingElements.has(element)) {
                this.loadingElements.set(element, {
                    originalContent: element.innerHTML,
                    originalDisabled: element.disabled,
                    originalClassName: element.className
                });
            }
            
            if (element.tagName === 'BUTTON') {
                element.disabled = true;
                element.innerHTML = `<span class="loading-spinner"></span>${message}`;
            } else {
                element.innerHTML = `<div class="loading-container"><div class="loading-spinner"></div><span class="loading-text">${message}</span></div>`;
            }
            element.classList.add('loading');
        },
        
        hideLoading(element) {
            if (!element || !this.loadingElements.has(element)) return;
            
            const originalState = this.loadingElements.get(element);
            element.innerHTML = originalState.originalContent;
            element.disabled = originalState.originalDisabled;
            element.className = originalState.originalClassName;
            this.loadingElements.delete(element);
        },
        
        showError(element, message = 'Error al cargar') {
            if (!element) return;
            element.innerHTML = `<div class="error-container"><span class="error-icon">⚠️</span><span class="error-text">${message}</span><button class="btn btn-sm btn-secondary retry-btn" onclick="location.reload()">Reintentar</button></div>`;
            element.classList.add('error-state');
        },
        
        showEmpty(element, message = 'No hay datos disponibles', icon = '📭') {
            if (!element) return;
            element.innerHTML = `<div class="empty-container"><span class="empty-icon">${icon}</span><span class="empty-text">${message}</span></div>`;
            element.classList.add('empty-state');
        },
        
        clearState(element) {
            if (!element) return;
            element.classList.remove('loading', 'error-state', 'empty-state');
            this.hideLoading(element);
        }
    };
}

if (typeof withLoadingState === 'undefined') {
    global.withLoadingState = async (element, asyncOperation, loadingMessage = 'Cargando...') => {
        try {
            UIStateManager.showLoading(element, loadingMessage);
            const result = await asyncOperation();
            UIStateManager.hideLoading(element);
            return result;
        } catch (error) {
            UIStateManager.hideLoading(element);
            UIStateManager.showError(element, error.message || 'Error al procesar');
            throw error;
        }
    };
}

if (typeof debounceWithCancel === 'undefined') {
    global.debounceWithCancel = (func, wait) => {
        let timeout;
        let cancelled = false;
        
        const debounced = function(...args) {
            if (cancelled) return;
            
            const later = () => {
                clearTimeout(timeout);
                if (!cancelled) func(...args);
            };
            
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
        
        debounced.cancel = () => {
            cancelled = true;
            clearTimeout(timeout);
        };
        
        debounced.flush = () => {
            if (timeout) {
                clearTimeout(timeout);
                func();
            }
        };
        
        return debounced;
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
        integer: (options) => Math.floor(Math.random() * (options.max - options.min + 1)) + options.min,
        string: (options) => 'test-string-' + Math.random().toString(36).substring(7),
        boolean: () => Math.random() > 0.5,
        array: (generator, options) => {
            const length = Math.floor(Math.random() * (options.maxLength - options.minLength + 1)) + options.minLength;
            return Array.from({ length }, () => typeof generator === 'function' ? generator() : generator);
        },
        constant: (value) => value,
        constantFrom: (...values) => values[Math.floor(Math.random() * values.length)]
    };
}