<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Folder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;

class UserController extends Controller
{
    /** Claves y etiquetas de menús que se pueden asignar a Operador/Visualizador. Panel de Control y Configuración solo Administrador. */
    public static function menuOptions(): array
    {
        return [
            ['key' => 'dashboard', 'label' => 'Inicio'],
            ['key' => 'licitaciones', 'label' => 'Licitaciones'],
            ['key' => 'consultor-obras', 'label' => 'Consultor de Obras'],
            ['key' => 'ejecutor-obra', 'label' => 'Ejecutor de Obra'],
            ['key' => 'proveedor-servicios', 'label' => 'Proveedor de Servicios'],
            ['key' => 'proveedor-bienes', 'label' => 'Proveedor de Bienes'],
            ['key' => 'especialistas-ejecucion', 'label' => 'Especialistas en Ejecución de Obra'],
            ['key' => 'especialistas-consultoria', 'label' => 'Especialistas en Consultoría de Obra'],
            ['key' => 'inmobiliaria', 'label' => 'Inmobiliaria'],
            ['key' => 'topografia', 'label' => 'Topografía'],
            ['key' => 'tecnologia', 'label' => 'Tecnología'],
            ['key' => 'plantillas-ing', 'label' => 'Plantillas de Ing.'],
            ['key' => 'registro-expedientes', 'label' => 'Registro de Expedientes'],
            ['key' => 'cvs', 'label' => 'Banco de CVs'],
            ['key' => 'folders', 'label' => 'Gestión Documental'],
        ];
    }

    /** Menús que tienen carpetas (para el modal de permisos por carpeta del visualizador). */
    public static function menuKeysWithFolders(): array
    {
        return [
            'folders' => 'Gestión Documental',
            'cvs' => 'Banco de CVs',
            'licitaciones' => 'Licitaciones',
            'consultor-obras' => 'Consultor de Obras',
            'ejecutor-obra' => 'Ejecutor de Obra',
            'proveedor-servicios' => 'Proveedor de Servicios',
            'proveedor-bienes' => 'Proveedor de Bienes',
            'especialistas-ejecucion' => 'Especialistas en Ejecución',
            'especialistas-consultoria' => 'Especialistas en Consultoría',
            'inmobiliaria' => 'Inmobiliaria',
            'topografia' => 'Topografía',
            'tecnologia' => 'Tecnología',
            'plantillas-ing' => 'Plantillas de Ing.',
            'registro-expedientes' => 'Registro de Expedientes',
        ];
    }

    /**
     * Datos para el modal de carpetas visibles (visualizador): usuario y carpetas por módulo.
     */
    public function folderPermissions(User $user)
    {
        $allowedFolders = $user->allowed_folders ?? [];
        $menuWithFolders = self::menuKeysWithFolders();
        $foldersByModule = [];

        $operadores = collect();
        foreach ($menuWithFolders as $menuKey => $label) {
            $query = Folder::query();
            if ($menuKey === 'folders') {
                $query->whereNull('module');
            } else {
                $query->where('module', $menuKey);
            }
            $folders = $query->with('user:id,name')
                ->orderBy('name')
                ->get(['id', 'name', 'parent_id', 'user_id']);
            $foldersByModule[$menuKey] = $folders->map(fn ($folder) => [
                'id' => $folder->id,
                'name' => $folder->name,
                'parent_id' => $folder->parent_id,
                'user_id' => $folder->user_id,
                'creator_name' => $folder->user?->name ?? '—',
            ])->values()->all();
            foreach ($folders as $f) {
                if ($f->user_id && $f->user) {
                    $operadores->put($f->user_id, ['id' => $f->user_id, 'name' => $f->user->name]);
                }
            }
        }
        $operadores = $operadores->values()->sortBy('name')->values()->all();

        return response()->json([
            'user' => ['id' => $user->id, 'name' => $user->name, 'role' => $user->role, 'allowed_folders' => $allowedFolders],
            'foldersByModule' => $foldersByModule,
            'menuLabels' => $menuWithFolders,
            'operadores' => $operadores,
        ]);
    }

    /**
     * Actualizar carpetas permitidas para el usuario (visualizador).
     */
    public function updateFolderPermissions(Request $request, User $user)
    {
        $menuKeys = array_keys(self::menuKeysWithFolders());
        $raw = $request->input('allowed_folders', []);
        $allowedFolders = [];
        foreach ($menuKeys as $key) {
            $ids = $raw[$key] ?? [];
            if (!is_array($ids)) {
                continue;
            }
            $ids = array_map('intval', array_filter($ids));
            $ids = array_values(array_unique($ids));
            if (!empty($ids)) {
                $allowedFolders[$key] = $ids;
            }
        }
        $user->allowed_folders = $allowedFolders;
        $user->save();

        return response()->json(['success' => true, 'message' => 'Carpetas actualizadas.']);
    }

    public function index(Request $request)
    {
         $query = User::query();

        if ($request->filled('search')) {
            $term = '%' . $request->search . '%';
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', $term)
                    ->orWhere('email', 'like', $term)
                    ->orWhere('role', 'like', $term);
            });
        }

        if ($request->filled('date_start')) {
            $query->whereDate('created_at', '>=', $request->date_start);
        }

        if ($request->filled('date_end')) {
            $query->whereDate('created_at', '<=', $request->date_end);
        }

        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        $users = $query->latest()->paginate(10)->withQueryString();

        return Inertia::render('Config/Index', [
            'users' => $users,
            'filters' => $request->only(['search', 'date_start', 'date_end', 'role']),
        ]);
    }

    public function create()
    {
        return Inertia::render('Config/Create', [
            'menuOptions' => self::menuOptions(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:'.User::class,
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'role' => 'required|string|in:Administrador,Operador,Visualizador',
            'allowed_menus' => 'nullable|array',
            'allowed_menus.*' => 'string|in:'.implode(',', array_column(self::menuOptions(), 'key')),
        ]);

        $allowedMenus = $validated['allowed_menus'] ?? null;
        if ($validated['role'] === 'Administrador') {
            $allowedMenus = null;
        } elseif ($allowedMenus !== null && empty($allowedMenus)) {
            $allowedMenus = array_column(self::menuOptions(), 'key');
        }

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
            'password' => Hash::make($validated['password']),
            'allowed_menus' => $allowedMenus,
        ]);

        return redirect()->route('users.index')->with('success', 'Usuario creado.');
    }

    public function edit(User $user)
    {
        $menuOptions = self::menuOptions();
        $allowedMenus = $user->allowed_menus;
        if ($allowedMenus === null || $allowedMenus === []) {
            $allowedMenus = array_column($menuOptions, 'key');
        }
        return Inertia::render('Config/Edit', [
            'user' => $user,
            'menuOptions' => $menuOptions,
            'allowedMenusDefault' => $allowedMenus,
        ]);
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,'.$user->id,
            'password' => ['nullable', 'confirmed', Rules\Password::defaults()],
            'role' => 'required|string|in:Administrador,Operador,Visualizador',
            'allowed_menus' => 'nullable|array',
            'allowed_menus.*' => 'string|in:'.implode(',', array_column(self::menuOptions(), 'key')),
        ]);

        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->role = $validated['role'];

        if ($validated['role'] === 'Administrador') {
            $user->allowed_menus = null;
        } else {
            $allowedMenus = $validated['allowed_menus'] ?? [];
            $user->allowed_menus = empty($allowedMenus) ? array_column(self::menuOptions(), 'key') : $allowedMenus;
        }

        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }
        $user->save();

        return redirect()->route('users.index')->with('success', 'Usuario actualizado.');
    }

    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'No puedes eliminar tu propia cuenta.');
        }
        $user->delete();
        return redirect()->route('users.index')->with('success', 'Usuario eliminado.');
    }
}
