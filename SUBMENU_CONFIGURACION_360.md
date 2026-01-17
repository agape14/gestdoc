# ✅ Submenú de Configuración - Imagen 360°

## 🎯 Funcionalidad Implementada

Se ha creado un **submenú en Configuración** que permite administrar de forma dinámica la imagen 360° que se visualiza en el Dashboard.

---

## 📋 Cambios Implementados

### 1. ✅ Base de Datos - Tabla de Configuraciones

**Archivo:** `database/migrations/2026_01_17_130612_create_configurations_table.php`

Se creó una tabla `configurations` para almacenar configuraciones del sistema:
- `key`: Clave única de la configuración
- `value`: Valor de la configuración
- `type`: Tipo (text, image, boolean, json)
- `description`: Descripción de la configuración

**Registro predeterminado insertado:**
```php
'dashboard_360_image' => '/images/360/default-panorama.jpg'
```

### 2. ✅ Modelo Configuration

**Archivo:** `app/Models/Configuration.php`

Modelo con métodos estáticos útiles:
- `Configuration::get($key, $default)` - Obtener un valor (con caché)
- `Configuration::set($key, $value, $type, $description)` - Establecer un valor
- `Configuration::clearCache($key)` - Limpiar caché

**Características:**
- Cache automático de 1 hora para mejor rendimiento
- Métodos estáticos para acceso fácil
- UpdateOrCreate para evitar duplicados

### 3. ✅ Controlador ConfigurationController

**Archivo:** `app/Http/Controllers/ConfigurationController.php`

**Métodos:**
1. `image360()` - Muestra la página de configuración
2. `updateImage360()` - Procesa la subida de nueva imagen
3. `restoreDefault360()` - Restaura la imagen predeterminada

**Validaciones:**
- Solo acepta imágenes JPG, JPEG, PNG
- Máximo 10MB por archivo
- Elimina automáticamente la imagen anterior al subir una nueva
- Manejo de errores con mensajes flash

### 4. ✅ Vista React - Página de Configuración

**Archivo:** `resources/js/Pages/Configuration/Image360.jsx`

**Características:**
- 🖼️ Vista previa en tiempo real de la imagen seleccionada
- 📤 Formulario de carga con drag & drop ready
- 🔄 Botón para restaurar imagen predeterminada
- ⚠️ Validaciones en el cliente
- 📊 Muestra información del archivo seleccionado
- 💡 Consejos y recomendaciones para imágenes 360°
- ✅ Mensajes de éxito/error con Bootstrap alerts

### 5. ✅ Navegación con Submenú

**Archivo:** `resources/js/Layouts/MainLayout.jsx`

**Nuevos componentes:**
- `NavItemWithSubmenu` - Item de navegación con submenú desplegable
- `SubNavItem` - Items dentro del submenú

**Menú Configuración ahora tiene:**
- 👥 **Usuarios** (enlace a `/config`)
- 🖼️ **Imagen 360°** (enlace a `/config/image360`) - **NUEVO**

**Características:**
- Submenú colapsable con animación
- Icono de chevron que rota al abrir/cerrar
- Se mantiene abierto si estás en una de sus páginas
- Diseño consistente con el resto del sidebar

### 6. ✅ Dashboard Dinámico

**Archivo:** `app/Http/Controllers/DashboardController.php`

Ahora el dashboard obtiene la imagen 360° desde la configuración:
```php
$image360 = Configuration::get('dashboard_360_image', '/images/360/default-panorama.jpg');
```

**Archivo:** `resources/js/Pages/Dashboard.jsx`

El componente Viewer360 usa la imagen dinámica:
```jsx
<Viewer360 
    imageUrl={image360 || "/images/360/default-panorama.jpg"} 
    title="Vista 360° del Proyecto"
/>
```

### 7. ✅ Rutas Agregadas

**Archivo:** `routes/web.php`

```php
// Configuración de imagen 360
Route::get('/config/image360', [ConfigurationController::class, 'image360'])
    ->name('config.image360');
    
Route::post('/config/image360/update', [ConfigurationController::class, 'updateImage360'])
    ->name('config.image360.update');
    
Route::post('/config/image360/restore', [ConfigurationController::class, 'restoreDefault360'])
    ->name('config.image360.restore');
```

---

## 🚀 Cómo Usar

### **Paso 1: Acceder a Configuración**

1. Abre tu aplicación en el navegador
2. En el sidebar, haz clic en **Configuración** (icono de engranaje)
3. Se desplegará un submenú, haz clic en **Imagen 360°**

### **Paso 2: Subir una Nueva Imagen**

1. En la página de configuración, haz clic en **"Seleccionar nueva imagen 360°"**
2. Selecciona tu archivo de imagen (JPG, JPEG o PNG)
3. Verás una vista previa de la imagen en el visor 360°
4. Si te gusta, haz clic en **"Subir y Aplicar"**
5. ¡Listo! La imagen se actualizará en el dashboard

### **Paso 3: Verificar en el Dashboard**

1. Navega al **Dashboard**
2. Verás tu nueva imagen panorámica 360° en la sección inferior
3. Puedes interactuar con ella (zoom, rotación, pantalla completa)

### **Paso 4: Restaurar Imagen Predeterminada (opcional)**

Si quieres volver a la imagen predeterminada:
1. Ve a **Configuración → Imagen 360°**
2. Haz clic en **"Restaurar Predeterminada"**
3. Confirma la acción
4. La imagen volverá a la original

---

## 🎨 Características de la Página de Configuración

### Vista Previa en Tiempo Real
- Al seleccionar una imagen, se muestra instantáneamente en el visor 360°
- Puedes verificar cómo se verá antes de aplicarla
- La vista previa usa el mismo componente que el dashboard

### Validaciones Automáticas
- ✅ Solo acepta imágenes válidas (JPG, JPEG, PNG)
- ✅ Límite de tamaño: 10MB máximo
- ✅ Mensajes de error claros si algo falla

### Información del Archivo
- Nombre del archivo seleccionado
- Tamaño en MB
- Badge visual con información

### Consejos Integrados
La página incluye una sección con:
- 📐 Formato equirectangular explicado
- 📏 Resolución recomendada
- 💾 Consejos de optimización
- 🔗 Enlaces a recursos (360 Photo Cam, etc.)

---

## 📁 Estructura de Archivos Creados/Modificados

```
gestdoc/
├── app/
│   ├── Http/
│   │   └── Controllers/
│   │       ├── ConfigurationController.php        [NUEVO]
│   │       └── DashboardController.php            [MODIFICADO]
│   └── Models/
│       └── Configuration.php                      [NUEVO]
├── database/
│   └── migrations/
│       └── 2026_01_17_130612_create_configurations_table.php  [NUEVO]
├── resources/
│   └── js/
│       ├── Layouts/
│       │   └── MainLayout.jsx                     [MODIFICADO]
│       └── Pages/
│           ├── Configuration/
│           │   └── Image360.jsx                   [NUEVO]
│           └── Dashboard.jsx                      [MODIFICADO]
└── routes/
    └── web.php                                    [MODIFICADO]
```

---

## 🔧 Detalles Técnicos

### Almacenamiento de Imágenes
- **Ruta:** `public/images/360/`
- **Nomenclatura:** `panorama-{timestamp}.{extension}`
- **Acceso:** Directamente desde el navegador (ruta pública)

### Caché
- Las configuraciones se cachean durante 1 hora
- Al actualizar, el caché se limpia automáticamente
- Mejora el rendimiento en cada carga del dashboard

### Seguridad
- Validación de tipo de archivo
- Validación de tamaño máximo
- Middleware de autenticación requerido
- Las imágenes anteriores se eliminan automáticamente

### Gestión de Errores
- Try-catch en la subida de archivos
- Mensajes flash con detalles del error
- Rollback automático si algo falla

---

## 🎯 Casos de Uso

### 1. Proyecto de Construcción
Sube una foto 360° del sitio de construcción actual para que el equipo vea el progreso.

### 2. Showroom Virtual
Muestra tu oficina, showroom o instalaciones en 360° a clientes potenciales.

### 3. Renders de Proyectos
Sube renders equirectangulares de proyectos futuros para visualización inmersiva.

### 4. Documentación Visual
Mantén un registro visual del estado de los proyectos con imágenes 360° actualizadas.

---

## 📊 Ventajas de esta Implementación

✅ **Gestión Centralizada**: Todo desde una página de configuración
✅ **Vista Previa**: Verifica antes de aplicar
✅ **Fácil de Usar**: Interface intuitiva con instrucciones
✅ **Flexible**: Cambia la imagen cuando quieras
✅ **Seguro**: Validaciones y manejo de errores robusto
✅ **Performante**: Cache de configuraciones
✅ **Escalable**: Sistema de configuración reutilizable para más opciones

---

## 🚧 Posibles Expansiones Futuras

### Sistema de Configuración Completo
El modelo `Configuration` está preparado para más configuraciones:
- Colores del tema
- Logo de la empresa
- Textos personalizados
- Configuraciones de notificaciones
- Etc.

### Múltiples Imágenes 360°
Se podría expandir para:
- Galería de imágenes 360°
- Rotación automática entre varias imágenes
- Selección por proyecto

### Metadatos
Agregar campos adicionales:
- Título de la imagen
- Fecha de captura
- Ubicación
- Descripción

---

## 🎉 ¡Completado!

El sistema de configuración de imágenes 360° está **100% funcional** y listo para usar.

### Para empezar:
1. ✅ Migración ejecutada
2. ✅ Assets compilados
3. ✅ Navegación actualizada
4. ✅ Todo funcionando

**¡Solo navega a Configuración → Imagen 360° y comienza a personalizar tu dashboard!** 🚀

---

## 📞 Soporte

Si necesitas ayuda o quieres agregar más funcionalidades:
- El código está bien documentado
- Los componentes son reutilizables
- La arquitectura es extensible

**¡Disfruta de tu nuevo sistema de configuración!** 🎊

