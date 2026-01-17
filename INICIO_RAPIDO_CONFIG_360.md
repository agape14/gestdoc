# 🚀 Inicio Rápido - Configuración de Imagen 360°

## ✅ ¡Ya está todo listo!

La funcionalidad de configuración de imagen 360° está **completamente instalada y operativa**.

---

## 🎯 ¿Qué puedes hacer ahora?

### 1️⃣ Acceder a la Configuración

**Opción 1: Desde el Sidebar**
1. Abre tu aplicación: `http://localhost:8082/dashboard`
2. En el sidebar izquierdo, busca el menú **"Configuración"** (icono de engranaje)
3. Haz clic para desplegar el submenú
4. Selecciona **"Imagen 360°"**

**Opción 2: URL Directa**
```
http://localhost:8082/config/image360
```

### 2️⃣ Subir tu Primera Imagen 360°

1. En la página de configuración, haz clic en **"Seleccionar nueva imagen 360°"**
2. Elige tu archivo de imagen (JPG, PNG, máx 10MB)
3. Verás una **vista previa en tiempo real**
4. Si te gusta, haz clic en **"Subir y Aplicar"**
5. ¡Listo! 🎉

### 3️⃣ Ver el Resultado

1. Navega al **Dashboard** (desde el sidebar o haciendo clic en el logo)
2. Verás tu nueva imagen 360° en la parte inferior
3. Puedes **interactuar** con ella:
   - 🖱️ Arrastra para rotar
   - 🔍 Zoom con la rueda del mouse
   - 🖥️ Pantalla completa con el botón

---

## 📸 ¿Dónde Conseguir Imágenes 360°?

### Opción 1: Del link que compartiste
Si quieres usar esa imagen específica:
1. Abre: https://360photocam.com/online-viewer?id=DchKPW
2. Descarga la imagen (clic derecho → guardar)
3. Súbela desde **Configuración → Imagen 360°**

### Opción 2: Tomar tus propias fotos
1. Descarga la app **360 Photo Cam** en tu teléfono
2. Toma fotos del sitio de construcción o proyecto
3. Transfiere a tu PC y súbela

### Opción 3: Imágenes gratuitas
- [Flickr Equirectangular](https://www.flickr.com/groups/equirectangular/)
- [Poly Haven](https://polyhaven.com/hdris) - 100% gratis

### Opción 4: Ya hay una imagen de ejemplo
- El sistema incluye una imagen predeterminada
- Puedes probar el sistema sin subir nada primero

---

## 🎨 Características de la Página

### Vista Previa en Vivo
Al seleccionar una imagen, la verás **inmediatamente** en el visor 360° antes de aplicarla.

### Información del Archivo
Te muestra:
- Nombre del archivo
- Tamaño en MB
- Validación automática

### Restaurar Predeterminada
Si no te gusta una imagen subida, puedes volver a la original con un clic.

---

## 📋 Requisitos de la Imagen

✅ **Formato**: JPG, JPEG o PNG
✅ **Tamaño máximo**: 10MB
✅ **Proyección**: Equirectangular (ratio 2:1)
✅ **Resolución recomendada**: 4096 x 2048 píxeles

---

## 🎯 Navegación del Submenú

El menú **Configuración** ahora tiene dos opciones:

```
⚙️ Configuración
  ├── 👥 Usuarios
  └── 🖼️ Imagen 360°  ← NUEVO
```

- El submenú se **despliega/colapsa** al hacer clic
- Se mantiene **abierto automáticamente** si estás en una de sus páginas
- Diseño consistente con el resto de la aplicación

---

## ⚡ Acceso Rápido

### URLs Directas
```bash
# Página principal de configuración (Usuarios)
http://localhost:8082/config

# Configuración de Imagen 360°
http://localhost:8082/config/image360

# Dashboard (para ver el resultado)
http://localhost:8082/dashboard
```

---

## 🔧 Solución de Problemas

### La imagen no se sube
- Verifica que sea JPG, JPEG o PNG
- Comprueba que pese menos de 10MB
- Revisa que tengas permisos de escritura en `public/images/360/`

### No veo el submenú
- Actualiza la página (F5)
- Limpia la caché del navegador (Ctrl + Shift + Delete)
- Verifica que `npm run build` se haya ejecutado correctamente

### La imagen no aparece en el dashboard
- Espera 1 hora o limpia la caché de Laravel: `php artisan cache:clear`
- Verifica que la imagen se subió correctamente en `public/images/360/`

---

## 💡 Consejos

### Para mejor calidad:
- Usa imágenes de **alta resolución** (mínimo 4096x2048)
- Prefiere **JPG** sobre PNG (archivos más pequeños)
- **Optimiza** la imagen antes de subir (con TinyPNG, por ejemplo)

### Para mejor experiencia:
- Toma fotos 360° en **buena iluminación**
- Evita movimientos bruscos al capturar
- Usa un trípode si es posible para fotos más estables

---

## 🎉 ¡Eso es Todo!

Tu sistema está **100% operativo** y listo para usar.

### Lo que tienes ahora:
✅ Dashboard con contadores reales
✅ Visor 360° interactivo
✅ Sistema de configuración completo
✅ Submenú de administración

**¡Comienza a personalizar tu dashboard ahora mismo!** 🚀

---

## 📚 Más Información

- **SUBMENU_CONFIGURACION_360.md** - Documentación técnica completa
- **RESUMEN_CAMBIOS_DASHBOARD.md** - Cambios anteriores del dashboard
- **INSTRUCCIONES_IMAGEN_360.md** - Guía detallada sobre imágenes 360°

