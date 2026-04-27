<?php

/**
 * PROJECT LAB v8 - Dashboard Modular Autodescubrible
 * Estructura: tools/project-lab/
 */

// ============================================================
// 1. AUTODESCUBRIMIENTO DE LA RAÍZ DEL PROYECTO
// ============================================================
function findProjectRoot($startPath)
{
    $path = realpath($startPath);
    $maxLevels = 6; // Buscar hasta 6 niveles arriba

    for ($i = 0; $i < $maxLevels; $i++) {
        // Señales de que estamos en un proyecto Laravel
        $laravelSignals = [
            'artisan' => 'file',    // Archivo artisan
            'vendor/autoload.php' => 'file',    // Composer
            'bootstrap/app.php' => 'file',    // Bootstrap Laravel
            'composer.json' => 'json',    // Composer config
        ];

        foreach ($laravelSignals as $signal => $type) {
            $fullPath = $path.DIRECTORY_SEPARATOR.$signal;

            if ($type === 'file' && file_exists($fullPath)) {
                // Verificación adicional: comprobar que composer.json tiene laravel
                $composerPath = $path.DIRECTORY_SEPARATOR.'composer.json';
                if (file_exists($composerPath)) {
                    $composer = json_decode(file_get_contents($composerPath), true);
                    if (isset($composer['require']['laravel/framework'])) {
                        return $path; // ¡Es un proyecto Laravel!
                    }
                }

                return $path; // Tiene artisan, suficiente
            }
        }

        // Subir un nivel
        $parentPath = dirname($path);
        if ($parentPath === $path) {
            break; // Llegamos a la raíz del filesystem
        }
        $path = $parentPath;
    }

    return null; // No se encontró
}

// Detectar raíz del proyecto
$projectRoot = findProjectRoot(__DIR__);

if (! $projectRoot || ! file_exists($projectRoot.'/vendor/autoload.php')) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Laravel no encontrado',
        'searched_from' => __DIR__,
        'tip' => 'Coloca esta carpeta dentro de tools/ en tu proyecto Laravel',
    ], JSON_PRETTY_PRINT);
    exit;
}

// ============================================================
// 2. CARGAR LARAVEL
// ============================================================
require $projectRoot.'/vendor/autoload.php';
$app = require_once $projectRoot.'/bootstrap/app.php';

// Inicializar sesión
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Kernel para Laravel 12
$app->singleton(
    Kernel::class,
    Illuminate\Foundation\Console\Kernel::class
);
$app->make(Kernel::class)->bootstrap();

// ============================================================
// 3. CARGAR CLASE PRINCIPAL
// ============================================================
require_once __DIR__.'/ProjectLab.php';

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;

// ============================================================
// 4. INICIALIZAR Y EJECUTAR
// ============================================================
$lab = new ProjectLab($projectRoot, __DIR__);
$lab->handleRequest();
