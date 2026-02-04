# Configuración de módulos (como Licitaciones)

Se ha replicado la configuración de **Licitaciones** en los módulos: Consultor de Obras, Ejecutor de Obra, Proveedor de Servicios, Proveedor de Bienes, Especialistas en Ejecución y Especialistas en Consultoría.

## Hecho

### Rutas
- Rutas de **export** declaradas **antes** del `resource` en consultor-obras, ejecutor-obra y proveedor-servicios para evitar que `/export` sea capturado por `show`.
- Rutas **folders.store** para: consultor-obras, ejecutor-obra, proveedor-servicios, proveedor-bienes, especialistas-ejecucion, especialistas-consultoria.
- Parámetros de resource unificados (consultorObra, ejecutorObra, etc.).

### Migración
- **2026_02_05_100000_add_clasificacion_and_documentos_tables_to_modules.php**
  - Añade columna `clasificacion` (miga de pan) a: consultor_obras, ejecutor_obras, proveedor_servicios, proveedor_biens, especialista_ejecucions, especialista_consultorias.
  - Crea tablas de documentos: `consultor_obra_documentos`, `ejecutor_obra_documentos`, `proveedor_servicio_documentos`, `proveedor_bien_documentos`, `especialista_ejecucion_documentos`, `especialista_consultoria_documentos` (nombre + file_path).

### Modelos
- Modelos **XDocumento** creados y relación **documentos()** en cada modelo padre.
- En modelos padre se añadieron: `clasificacion`, `folder_id`, `anulado` (donde faltaba), `scopeActive()`, `folder()`.

### Consultor de Obras
- **Controller**: index con carpetas, breadcrumb, operadores, filtro por user_id (admin), with('documentos'). create() con folderId y breadcrumbLabel. store() con clasificacion, folder_id, redirect con folder_id, storeDocumentos(). edit() con load documentos. update() con documento_delete_ids y syncDocumentosUpdate(). storeFolder().
- **Vistas**: Create.jsx y Edit.jsx con formulario y sección documentos (nombre + archivo, agregar más). Index con breadcrumb, carpetas, select operador (admin), enlace “Nuevo Registro” con folder_id, sin botones PÚBLICAS/PRIVADAS.

### Ejecutor de Obra
- **Controller**: MODULE, index con carpetas, breadcrumb, operadores, user_id filter, with('documentos'). storeFolder(). create() con folderId y breadcrumbLabel. store() con folder_id, clasificacion, storeDocumentos(), redirect con folder_id. edit() con load documentos. destroy() hace anulado (soft delete).
- **Index**: breadcrumb, carpetas, modal nueva carpeta, select operador (admin), enlace “Nuevo Registro” con folder_id, sin PÚBLICAS/PRIVADAS.
- **Create**: folderId, breadcrumbLabel, clasificacion, documentos (nombre + archivo, agregar más), cancelUrl con folder_id.

### Proveedor de Servicios, Proveedor de Bienes, Especialistas (Ejecución y Consultoría)
- **Controller**: MODULE, index con carpetas, breadcrumb, operadores, user_id filter (admin), with('documentos'). storeFolder(). Falta en cada uno: create() con breadcrumb, store() con folder_id/clasificacion/documentos y redirect con folder_id, edit() con documentos, update() con syncDocumentosUpdate().
- **Index**: Falta en cada uno: recibir operadores/folders/currentFolder/breadcrumb, select operador, breadcrumb, grid de carpetas, enlace a create con folder_id.

## Cómo completar un módulo (ej. Proveedor de Servicios)

1. **Controller**
   - create(Request $request): folderId, breadcrumbLabel (igual que LicitacionController).
   - store(): validar clasificacion, incluir en $data, folder_id, llamar a storeDocumentos(), redirect con folder_id.
   - Añadir storeDocumentos() y syncDocumentosUpdate() (patrón LicitacionController).
   - edit(): load('documentos').
   - update(): validar documento_delete_ids, except documentos y documento_delete_ids, llamar a syncDocumentosUpdate().

2. **Create.jsx**
   - Props: folderId, breadcrumbLabel.
   - useForm: clasificacion: breadcrumbLabel, folder_id: folderId, documentos: [{ nombre: '...', archivo: null }, ...].
   - Campo “Tipo / Clasificación” y sección documentos (nombre + archivo, agregar más).
   - cancelUrl con folder_id.

3. **Edit.jsx**
   - Mostrar documentos existentes (con enlace y eliminar) y nuevos (nombre + archivo), documento_delete_ids, put con forceFormData.

4. **Index.jsx**
   - Props: operadores, folders, currentFolder, breadcrumb.
   - Estado: operatorId, showFolderModal, newFolderName.
   - buildIndexParams(), useEffect con user_id y folder_id.
   - handleCreateFolder.
   - Bloque breadcrumb, bloque carpetas, select operador (admin), enlace “Nuevo X” con currentFolder?.id ? { folder_id: currentFolder.id } : {}.
   - Modal nueva carpeta (igual que en Licitaciones/ConsultorObras).

## Buscar por operador

En el index de cada módulo el controlador ya envía `operadores` cuando el usuario es Administrador. En las vistas donde ya se aplicó el patrón (Licitaciones, ConsultorObras, EjecutorObra) hay un select “Operador” que filtra por `user_id`. En ProveedorServicios, ProveedorBienes, EspecialistasEjecucion y EspecialistasConsultoria solo falta añadir en el Index el mismo bloque de filtros (operatorId, select, useEffect con user_id) y, si se desea, breadcrumb y carpetas.
