<?php

namespace App\Http\Controllers;

use App\Models\User;
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
            ['key' => 'cvs', 'label' => 'Banco de CVs'],
            ['key' => 'folders', 'label' => 'Gestión Documental'],
        ];
    }

    public function index(Request $request)
    {
         $query = User::query();

        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('email', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->filled('date_start')) {
            $query->whereDate('created_at', '>=', $request->date_start);
        }

        if ($request->filled('date_end')) {
            $query->whereDate('created_at', '<=', $request->date_end);
        }

        return Inertia::render('Config/Index', [
            'users' => $query->latest()->paginate(10),
            'filters' => $request->only(['search', 'date_start', 'date_end']),
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
