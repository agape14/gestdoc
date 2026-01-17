# Instrucciones para Agregar Imagen 360° al Dashboard

## ✅ Pasos Completados

1. ✅ Dashboard actualizado con contadores reales desde la base de datos
2. ✅ Componente visor 360° instalado y configurado
3. ✅ Sección "Actividad Reciente" reemplazada por el visor 360°

## 📸 Cómo Agregar Tu Imagen 360°

### Opción 1: Descargar de la imagen del link que compartiste

Si quieres usar la imagen del link `https://360photocam.com/online-viewer?id=DchKPW`, sigue estos pasos:

1. **Abre el link en tu navegador**
   ```
   https://360photocam.com/online-viewer?id=DchKPW
   ```

2. **Descarga la imagen**
   - Haz clic derecho sobre la imagen 360° en el visor
   - Selecciona "Guardar imagen como..." o "Save image as..."
   - Guarda el archivo con el nombre: `default-panorama.jpg`

3. **Coloca la imagen en tu proyecto**
   ```
   Copia el archivo a: C:\laragon\www\gestdoc\public\images\360\default-panorama.jpg
   ```

### Opción 2: Usar una imagen 360° gratuita de Internet

**Sitios recomendados:**

1. **Flickr - Equirectangular Group**
   - URL: https://www.flickr.com/groups/equirectangular/
   - Busca imágenes marcadas como "Creative Commons" o libres de derechos
   - Descarga la imagen en resolución completa
   - Renómbrala a `default-panorama.jpg`

2. **Poly Haven (antes HDRI Haven)**
   - URL: https://polyhaven.com/hdris
   - Todas las imágenes son 100% gratuitas
   - Descarga en formato JPG
   - Renómbrala a `default-panorama.jpg`

3. **360 Photo Cam App**
   - Descarga la app en tu teléfono
   - Toma tus propias fotos 360° del sitio de construcción
   - Transfiere la imagen a tu computadora
   - Renómbrala a `default-panorama.jpg`

### Opción 3: Descargar imagen de ejemplo directamente

Puedes usar este comando para descargar una imagen 360° de ejemplo:

```powershell
# Ejecuta este comando en PowerShell desde la raíz del proyecto
Invoke-WebRequest -Uri "https://raw.githubusercontent.com/googlevr/omnitone/master/examples/resources/4ch_B_FuMa_ACN_SN3D.jpg" -OutFile "public/images/360/default-panorama.jpg"
```

O descarga manualmente desde estos enlaces y guarda como `default-panorama.jpg`:

- [Ejemplo 1 - Paisaje](https://raw.githubusercontent.com/mistic100/Photo-Sphere-Viewer/master/examples/assets/sphere.jpg)
- [Ejemplo 2 - Interior](https://www.gstatic.com/culturalinstitute/searchar/assets/versailles_stockli_2048.jpg)

## 📋 Requisitos de la Imagen

- **Formato**: JPG o PNG
- **Proyección**: Equirectangular (ratio 2:1)
- **Resolución mínima**: 4096 x 2048 píxeles (recomendado)
- **Tamaño de archivo**: Menor a 5MB para mejor rendimiento

## 🔄 Verificar que Funciona

1. Coloca tu imagen en: `public/images/360/default-panorama.jpg`
2. Ejecuta: `npm run dev` (si no está corriendo)
3. Abre el dashboard en tu navegador: `http://localhost/gestdoc/dashboard`
4. Deberías ver la imagen 360° con controles interactivos

## 🎨 Personalizar el Visor

Si quieres cambiar la ruta de la imagen o agregar múltiples imágenes:

1. Edita el archivo: `resources/js/Pages/Dashboard.jsx`
2. Encuentra la línea:
   ```jsx
   <Viewer360 
       imageUrl="/images/360/default-panorama.jpg" 
       title="Vista 360° del Proyecto"
   />
   ```
3. Cambia `imageUrl` a la ruta de tu imagen

## ❓ Problemas Comunes

### La imagen no se muestra
- Verifica que el archivo esté en la ruta correcta: `public/images/360/default-panorama.jpg`
- Verifica que el archivo no esté corrupto
- Limpia la caché del navegador (Ctrl + F5)

### La imagen se ve distorsionada
- Asegúrate de que la imagen tenga formato equirectangular (ratio 2:1)
- La imagen debe ser una foto panorámica 360° completa

### El visor es lento
- Reduce el tamaño de la imagen (comprime el archivo)
- Usa formato JPG en lugar de PNG
- Optimiza la imagen con herramientas como TinyPNG o ImageOptim

## 📞 Soporte

Si necesitas más ayuda, consulta la documentación de Photo Sphere Viewer:
https://photo-sphere-viewer.js.org/

## 🎉 ¡Listo!

Una vez que coloques tu imagen, el dashboard mostrará automáticamente:
- ✅ Contadores reales de Contratos, Licitaciones y CVs
- ✅ Visor 360° interactivo con tu imagen panorámica

