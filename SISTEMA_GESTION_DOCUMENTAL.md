# Sistema de Gestión Documental Jerárquico

## Descripción General

Se ha implementado un sistema completo de gestión documental jerárquico que permite organizar contratos en carpetas anidadas con una estructura predefinida basada en Privados/Públicos y categorías específicas.

## ✅ Componentes Implementados

### 1. Base de Datos

#### Tabla `folders`
- `id`: ID único
- `parent_id`: ID de carpeta padre (nullable, permite jerarquía recursiva)
- `name`: Nombre de la carpeta
- `slug`: Slug único para URLs limpias
- `color`: Color de la carpeta (hex, default: #EAEAEA)
- `icon`: Nombre del icono (Lucide React)
- `description`: Descripción de la carpeta
- `is_system`: Marca carpetas protegidas del sistema
- Índices: `parent_id`, `is_system`

#### Tabla `contratos` (actualizada)
**Campos nuevos:**
- `folder_id`: Relación con carpeta contenedora
- `client`: Cliente
- `project_name`: Nombre del proyecto
- `contract_object`: Objeto del contrato (texto)
- `contract_number`: Número de contrato/O/S/Comprobante
- `currency`: Moneda (PEN/USD)
- `amount`: Monto contratado
- `participation_percentage`: Porcentaje de participación
- `contract_date`: Fecha de contrato
- `conformity_date`: Fecha de conformidad
- `exchange_rate`: Tipo de cambio
- `accumulated_amount`: Monto acumulado (calculado automáticamente)
- `status`: Estado (completo/incompleto)
- `file_path`: Ruta del archivo PDF (renombrado de `archivo_path`)

### 2. Backend (Laravel)

#### Modelos
- **`Folder`**: Modelo recursivo con relaciones parent/children, método `getPathAttribute()` para breadcrumb, y `getContractsSummaryAttribute()` para estadísticas
- **`Contrato`**: Actualizado con todos los campos nuevos, cálculo automático de `accumulated_amount` y relación con `Folder`

#### Controladores
- **`FolderController`**: CRUD completo para carpetas
  - `index()`: Muestra carpetas raíz
  - `show($id)`: Muestra contenido de una carpeta específica
  - `store()`: Crea nueva carpeta
  - `update()`: Actualiza carpeta (respeta restricciones de sistema)
  - `destroy()`: Elimina carpeta (protege carpetas del sistema)
  - `getTree()`: Retorna árbol completo de carpetas

- **`ContractController`**: Actualizado para trabajar con carpetas
  - `index()`: Lista contratos con filtros avanzados
  - `store()`: Crea contrato en carpeta específica
  - `update()`: Actualiza contrato
  - `destroy()`: Elimina contrato y archivo
  - `download()`: Descarga PDF del contrato

#### Seeders
- **`FolderSeeder`**: Crea estructura completa de carpetas:
  - **Nivel 1**: PRIVADOS, PUBLICAS
  - **Nivel 2**: EJECUTOR DE OBRAS, CONSULTORIAS DE OBRA, BIENES, SERVICIOS, OTROS
  - **Nivel 3** (solo en Consultorías): RIEGO, AGUA Y DESAGUE, COLEGIOS, PAVIMENTOS, PUENTES, LOSAS DEPORTIVAS

### 3. Frontend (React)

#### Páginas
- **`Pages/Folders/Index.jsx`**: Vista principal del explorador de carpetas con navegación jerárquica usando MainLayout (mismo diseño que Licitaciones)

#### Componentes
- **`FolderModal.jsx`**: Modal Bootstrap para crear/editar carpetas
- **`ContractModal.jsx`**: Modal Bootstrap completo para crear/editar contratos con todos los campos

#### Características UI/UX
- ✅ **Diseño consistente**: Usa el mismo layout y esquema visual que el resto de la aplicación (MainLayout)
- ✅ **Bootstrap 5**: Cards con `border-0 shadow-sm rounded-4` para consistencia visual
- ✅ Colores personalizables por carpeta
- ✅ Iconos visuales (Bootstrap Icons)
- ✅ Indicador de progreso (contratos completos/totales) en badges
- ✅ Toggle de estado Completo/Incompleto con radio buttons
- ✅ Cálculo automático de monto facturado según porcentaje de participación
- ✅ Diseño responsive con clases de Bootstrap
- ✅ Navegación tipo explorador de archivos con breadcrumb
- ✅ Tabla responsive para lista de contratos
- ✅ Modales con scrollable para formularios largos

### 4. Rutas

```php
// Sistema de carpetas
GET    /folders              - Lista carpetas raíz
GET    /folders/{id}         - Muestra contenido de carpeta
POST   /folders              - Crea nueva carpeta
PUT    /folders/{id}         - Actualiza carpeta
DELETE /folders/{id}         - Elimina carpeta
GET    /folders-tree         - Obtiene árbol completo

// Contratos
GET    /contracts            - Lista contratos
POST   /contracts            - Crea contrato
PUT    /contracts/{id}       - Actualiza contrato
DELETE /contracts/{id}       - Elimina contrato
GET    /contracts/{id}/download - Descarga PDF
```

## 📋 Reglas de Negocio Implementadas

1. **Carpetas del Sistema** (`is_system = true`):
   - No se pueden eliminar
   - Solo se puede cambiar nombre, color y descripción
   - No se puede cambiar el icono

2. **Jerarquía de Carpetas**:
   - Recursiva infinita mediante `parent_id`
   - Cada nivel puede tener subcarpetas
   - Generación automática de slug único

3. **Contratos**:
   - Deben pertenecer a una carpeta
   - Si `participation_percentage < 100`, calcula automáticamente `accumulated_amount`
   - Estado visual según completitud
   - Archivo PDF obligatorio al crear, opcional al editar

4. **Indicadores Visuales**:
   - Badge con conteo de contratos completos/totales
   - Colores de borde según estado (verde: completo, ámbar: incompleto)
   - Carpetas del sistema tienen badge especial

## 🎨 Estructura de Carpetas Predefinida

```
├── PRIVADOS (🔒)
│   ├── EJECUTOR DE OBRAS (👷)
│   ├── CONSULTORIAS DE OBRA (💼)
│   │   ├── RIEGO (💧)
│   │   ├── AGUA Y DESAGUE (🌊)
│   │   ├── COLEGIOS (🏫)
│   │   ├── PAVIMENTOS (🛣️)
│   │   ├── PUENTES (🌉)
│   │   └── LOSAS DEPORTIVAS (🏆)
│   ├── BIENES (📦)
│   ├── SERVICIOS (⚙️)
│   └── OTROS (⋯)
│
└── PUBLICAS (🌐)
    ├── EJECUTOR DE OBRAS (👷)
    ├── CONSULTORIAS DE OBRA (💼)
    │   ├── RIEGO (💧)
    │   ├── AGUA Y DESAGUE (🌊)
    │   ├── COLEGIOS (🏫)
    │   ├── PAVIMENTOS (🛣️)
    │   ├── PUENTES (🌉)
    │   └── LOSAS DEPORTIVAS (🏆)
    ├── BIENES (📦)
    ├── SERVICIOS (⚙️)
    └── OTROS (⋯)
```

## 🚀 Uso del Sistema

### Inicializar la Base de Datos

```bash
# Ejecutar migraciones
php artisan migrate

# Ejecutar seeder de carpetas
php artisan db:seed --class=FolderSeeder
```

### Navegar por Carpetas
1. Acceder a "Gestión Documental" desde el menú lateral
2. Hacer clic en una carpeta para ver su contenido
3. Usar el breadcrumb para navegar hacia atrás

### Crear una Nueva Carpeta
1. Hacer clic en "Nueva Carpeta"
2. Completar nombre, seleccionar color e icono
3. Agregar descripción opcional
4. Guardar

### Crear un Nuevo Contrato
1. Navegar a la carpeta destino
2. Hacer clic en "Nuevo Contrato"
3. Completar todos los campos requeridos:
   - Carpeta destino
   - Cliente y nombre del proyecto
   - Monto y moneda
   - Porcentaje de participación
   - Fechas
   - Estado (completo/incompleto)
   - Archivo PDF
4. Guardar

### Gestionar Contratos
- **Editar**: Clic en el icono de lápiz
- **Eliminar**: Clic en el icono de papelera
- **Descargar**: Clic en el icono de descarga

## 📊 Características Adicionales Implementadas

1. **Breadcrumb de Navegación**: Muestra la ruta completa desde la raíz
2. **Indicador de Progreso**: Badge con formato "5/10" mostrando contratos completos
3. **Cálculo Automático**: El monto facturado se calcula en tiempo real según el porcentaje
4. **Validación de Archivos**: Solo acepta PDFs de máximo 10 MB
5. **Búsqueda y Filtros**: En la vista de contratos (por proyecto, cliente, estado)
6. **Responsive Design**: Adaptado para dispositivos móviles y tablets

## 🔧 Dependencias Instaladas

- `lucide-react`: Biblioteca de iconos para React (aunque finalmente se usa Bootstrap Icons para consistencia)

## 📝 Notas Importantes

1. Las carpetas del sistema están protegidas contra eliminación
2. El campo `licitacion_id` se mantiene como nullable para compatibilidad con el sistema anterior
3. Los PDFs se almacenan en `storage/app/public/contratos`
4. El slug de las carpetas se genera automáticamente y es único
5. El sistema soporta múltiples niveles de anidación sin límite

## ✨ Mejoras Futuras Sugeridas

1. Búsqueda global de contratos
2. Exportación de reportes en Excel/PDF
3. Sistema de permisos por carpeta
4. Historial de cambios en contratos
5. Notificaciones de contratos por vencer
6. Dashboard con estadísticas visuales

---

**Implementado por:** Sistema de IA
**Fecha:** 16 de Enero de 2026
**Stack:** Laravel 11 + React + Inertia.js + Bootstrap 5
**Layout:** MainLayout (mismo esquema que Licitaciones y otros módulos)

