<?php
/**
 * Script de diagnóstico para identificar el error 500
 * Ejecutar desde el servidor: php diagnostico.php
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

echo "=== DIAGNÓSTICO DEL DASHBOARD ===\n\n";

// 1. Verificar conexión a base de datos
echo "1. Verificando conexión a base de datos...\n";
try {
    DB::connection()->getPdo();
    echo "   ✅ Conexión a BD: OK\n";
} catch (\Exception $e) {
    echo "   ❌ Error de conexión: " . $e->getMessage() . "\n";
    exit(1);
}

// 2. Verificar tablas
echo "\n2. Verificando tablas...\n";
$tables = [
    'users', 'licitacions', 'consultor_obras', 'ejecutor_obras',
    'proveedor_servicios', 'proveedor_biens', 'especialista_ejecucions',
    'especialista_consultorias', 'inmobiliarias', 'topografias',
    'tecnologias', 'plantilla_ings', 'curricula', 'folders', 'configurations'
];

foreach ($tables as $table) {
    try {
        $exists = Schema::hasTable($table);
        echo "   " . ($exists ? "✅" : "❌") . " Tabla '{$table}': " . ($exists ? "EXISTE" : "NO EXISTE") . "\n";
    } catch (\Exception $e) {
        echo "   ❌ Error verificando '{$table}': " . $e->getMessage() . "\n";
    }
}

// 3. Verificar modelos
echo "\n3. Verificando modelos...\n";
$models = [
    'App\Models\Licitacion',
    'App\Models\ConsultorObra',
    'App\Models\EjecutorObra',
    'App\Models\ProveedorServicio',
    'App\Models\ProveedorBien',
    'App\Models\EspecialistaEjecucion',
    'App\Models\EspecialistaConsultoria',
    'App\Models\Inmobiliaria',
    'App\Models\Topografia',
    'App\Models\Tecnologia',
    'App\Models\PlantillaIng',
    'App\Models\Curriculum',
    'App\Models\Folder',
    'App\Models\Configuration',
];

foreach ($models as $model) {
    try {
        if (class_exists($model)) {
            $count = $model::count();
            echo "   ✅ {$model}: OK (count: {$count})\n";
        } else {
            echo "   ❌ {$model}: CLASE NO EXISTE\n";
        }
    } catch (\Exception $e) {
        echo "   ❌ {$model}: ERROR - " . $e->getMessage() . "\n";
    }
}

// 4. Verificar cache
echo "\n4. Verificando cache...\n";
try {
    Cache::put('test', 'value', 1);
    $value = Cache::get('test');
    echo "   ✅ Cache: OK\n";
} catch (\Exception $e) {
    echo "   ❌ Cache: ERROR - " . $e->getMessage() . "\n";
}

// 5. Probar DashboardController
echo "\n5. Probando DashboardController...\n";
try {
    $controller = new App\Http\Controllers\DashboardController();
    $result = $controller->index();
    echo "   ✅ DashboardController: OK\n";
} catch (\Exception $e) {
    echo "   ❌ DashboardController: ERROR\n";
    echo "   Mensaje: " . $e->getMessage() . "\n";
    echo "   Archivo: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "   Stack trace:\n";
    echo $e->getTraceAsString() . "\n";
}

echo "\n=== FIN DEL DIAGNÓSTICO ===\n";
