# 🗺️ Mapa Interactivo - Guía de Configuración para el Equipo

## 🚀 Configuración Rápida

Después de hacer `git pull`, ejecuta estos pasos:

### 1. **Ejecutar Script de Configuración**
```
http://localhost/tu-proyecto/setup_mapa_equipo.php
```

### 2. **Seguir las Instrucciones**
El script te dirá exactamente qué hacer según tu situación.

---

## 🔧 Problemas Comunes y Soluciones

### ❌ **"El mapa está vacío"**
**Causa:** No tienes apartamentos con coordenadas GPS
**Solución:** 
1. Ve a `setup_mapa_equipo.php`
2. Haz clic en "Generar Coordenadas GPS"

### ❌ **"Error al cargar provincias"**
**Causa:** No tienes datos de apartamentos
**Solución:**
1. Ve a `api/sync.php` para sincronizar datos
2. Luego ejecuta `setup_mapa_equipo.php`

### ❌ **"APIs no funcionan"**
**Causa:** Problema de configuración de base de datos
**Solución:**
1. Verifica `config/database.php`
2. Asegúrate de que tu base de datos esté corriendo

---

## 📁 Archivos del Mapa

```
views/mapa.php              # Página principal del mapa
api/apartamentos.php        # API para datos del mapa
dao/ApartamentoDAO.php      # Acceso a datos
public/js/app.js            # JavaScript principal
setup_mapa_equipo.php       # Script de configuración (EJECUTAR PRIMERO)
```

---

## 🧪 Verificar que Funciona

1. **APIs funcionan:**
   - `api/apartamentos.php?action=provincias` → Debe devolver JSON con provincias
   - `api/apartamentos.php?action=mapa` → Debe devolver JSON con apartamentos

2. **Mapa funciona:**
   - `views/mapa.php` → Debe mostrar mapa con marcadores 🏠

3. **Consola del navegador:**
   - Presiona F12 → Console
   - Ejecuta `diagnosticarMapa()` para información detallada

---

## 🆘 Si Nada Funciona

1. **Verifica tu entorno:**
   - ¿Está corriendo tu servidor web (XAMPP/WAMP/MAMP)?
   - ¿Está corriendo MySQL?
   - ¿Tienes la base de datos creada?

2. **Verifica la configuración:**
   - `config/database.php` → Credenciales correctas
   - `config/config.php` → URLs correctas

3. **Ejecuta paso a paso:**
   ```
   1. setup_mapa_equipo.php  (diagnóstico)
   2. api/sync.php           (si no tienes datos)
   3. setup_mapa_equipo.php  (generar GPS si es necesario)
   4. views/mapa.php         (probar mapa)
   ```

---

## ✅ Funcionalidades del Mapa

Una vez configurado, el mapa incluye:

- 🗺️ **Mapa interactivo** de Castilla y León
- 🏠 **Marcadores** para cada apartamento
- 🔍 **Filtrado por provincia**
- 📋 **Modal de detalles** al hacer clic en "Ver detalles"
- 📅 **Sistema de reservas** al hacer clic en "Reservar"
- 📱 **Responsive** para móviles

---

## 💡 Consejos

- **Siempre ejecuta `setup_mapa_equipo.php` primero** después de hacer pull
- **Si cambias la base de datos**, vuelve a ejecutar el script
- **Para debugging**, usa `diagnosticarMapa()` en la consola del navegador
- **El script es seguro**, solo lee y configura, no borra datos

---

¿Problemas? Revisa la consola del navegador (F12) y ejecuta `diagnosticarMapa()` para más información.