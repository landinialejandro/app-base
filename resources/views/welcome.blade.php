{{-- resources/views/welcome.blade.php --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Mi App</title>
    @vite(['resources/css/app.css'])
</head>
<body class="font-sans antialiased">
    <div class="min-h-screen flex flex-col items-center justify-center bg-gray-100">
        <div class="text-center">
            <h1 class="text-4xl font-bold text-gray-900 mb-4">Mi Aplicación</h1>
            
            @auth
                <p class="mb-4">Bienvenido, {{ auth()->user()->name }}</p>
                <div class="space-x-4">
                    @if(auth()->user()->is_platform_admin)
                        <a href="/super" class="bg-purple-500 text-white px-4 py-2 rounded hover:bg-purple-600">
                            Panel Super Admin
                        </a>
                    @else
                        <a href="/app" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                            Mi Dashboard
                        </a>
                    @endif
                </div>
            @else
                <div class="space-y-4">
                    <div class="space-x-4">
                        <a href="/app/login" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                            Login Usuario
                        </a>
                        <a href="/app/register" class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600">
                            Registro Usuario
                        </a>
                    </div>
                    <div class="mt-4">
                        <a href="/super/login" class="text-sm text-gray-500 hover:text-gray-700 underline">
                            Acceso Super Admin
                        </a>
                    </div>
                </div>
            @endauth
        </div>
    </div>
</body>
</html>