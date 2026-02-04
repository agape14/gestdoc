# Reglas de perfiles, menús y anulación

## Resumen de perfiles

### Visualizador
- Solo ve los menús indicados en **Configuración** (allowed_menus) al crear/editar el usuario.
- En cada acceso/link solo tiene **botón Ver** (no crear, editar ni anular).
- En el Dashboard ve solo las tarjetas de los menús a los que tiene acceso; los contadores son 0 (no inserta).
- No puede acceder por URL a módulos no asignados (middleware `menu`).

### Operador
- Solo ve los menús indicados en Configuración (allowed_menus).
- En el **index** de cada módulo ve solo **sus registros** (user_id = su id).
- Contadores en el Dashboard: solo lo que él insertó, por los menús con acceso.
- Puede **buscar, editar y anular** solo sus propios registros (o los de perfil Operador que le pertenecen).
- Anular = estado; no se borra de la base de datos.

### Administrador
- No necesita que se le asignen menús al crear usuario (ve todo).
- **Panel de Control** y **Configuración** solo para Administrador (no aparecen en allowed_menus).
- Dashboard: ve **todos los contadores**.
- En cada menú ve registros de **todos los operadores** y los suyos.
- En el buscador de cada módulo: **select "Filtrar por operador"**; al elegir uno se listan solo los registros de ese operador.
- Puede **crear, editar y anular** cualquier registro.
- En los listados tiene pestaña **ANULADOS** donde solo el Administrador ve los registros anulados.

---

## Menús

### Menús principales (con control de acceso para Operador/Visualizador)
- INICIO (dashboard)
- LICITACIONES, CONSULTOR DE OBRAS, EJECUTOR DE OBRA, PROVEEDOR DE SERVICIOS, PROVEEDOR DE BIENES
- ESPECIALISTAS EN EJECUCIÓN DE OBRA, ESPECIALISTAS EN CONSULTORÍA DE OBRA
- INMOBILIARIA, TOPOGRAFÍA, TECNOLOGÍA, PLANTILLAS DE ING, BANCO DE CVs, GESTIÓN DOCUMENTAL

### Solo Administrador
- PANEL DE CONTROL
- CONFIGURACIÓN (Usuarios, Imagen 360°, Resetear datos)

---

## Anular (estado, no borrar)

- La opción "eliminar" en la UI se comporta como **Anular**: se pone `anulado = true` en BD.
- Los registros anulados **no se muestran** a ningún perfil en el listado normal.
- El **Administrador** ve una pestaña **ANULADOS** en cada módulo donde sí puede ver (solo lectura o según política) los anulados.
- Los contadores del Dashboard **excluyen** registros anulados.

---

## Cómo replicar en cada módulo (anulado + operadores + tab)

Para **Licitaciones** y **Consultor de Obras** ya está aplicado. Para el resto (Ejecutor Obra, Proveedor Servicios, etc.):

1. **Modelo**: Añadir `'anulado'` a `$fillable`, `'anulado' => 'boolean'` a `$casts`, y scope:
   ```php
   public function scopeActive($query) {
       return $query->where('anulado', false);
   }
   ```

2. **Controlador** (index):
   - Usar `Modelo::query()->active()` y `applyRoleBasedFilter`.
   - Si Admin y `user_id` en request: `$query->where('user_id', $request->user_id)`.
   - Pasar `anulados` (solo Admin): `Modelo::where('anulado', true)->...->get()`.
   - Pasar `operadores`: `User::where('role', 'Operador')->get(['id', 'name', 'email'])` si Admin.

3. **Controlador** (destroy):
   - En lugar de `$model->delete()`, usar `$model->update(['anulado' => true])`.

4. **Vista (Index.jsx)**:
   - Select "Filtrar por operador" (solo Admin) que envíe `user_id` en la petición.
   - Pestañas Activos / Anulados (solo Admin); en Anulados mostrar tabla de `anulados` (solo ver).
   - Botón "Anular" en lugar de "Eliminar" para los que pueden anular.

---

## Carpetas por módulo (implementado)

- La tabla `folders` tiene columna `module` (nullable). `module = null` = Gestión Documental (contratos). Valores: `licitaciones`, `consultor-obras`, `ejecutor-obra`, `proveedor-servicios`, `proveedor-bienes`, `especialistas-ejecucion`, `especialistas-consultoria`.
- Cada módulo tiene `folder_id` en su tabla (licitacions, consultor_obras, etc.).
- La jerarquía PUBLICOS/PRIVADOS (y subcarpetas) de Gestión Documental se copió a cada módulo con `ModuleFoldersSeeder`.
- **Licitaciones** y **Consultor de Obras**: ya tienen vista de carpetas (breadcrumb, grid de carpetas, "Nueva carpeta", listado de ítems por carpeta). Rutas: `POST /licitaciones/folders`, `POST /consultor-obras/folders`.
- Para replicar en Ejecutor de Obra, Proveedor de Servicios, Proveedor de Bienes, Especialistas (Ejecución y Consultoría): mismo patrón que Licitaciones (index con folder_id, storeFolder, vista con breadcrumb + carpetas + modal Nueva Carpeta).
