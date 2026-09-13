<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Aqualytics')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 text-slate-900">
    <header class="border-b border-slate-200 bg-white shadow-sm">
        <nav class="mx-auto flex max-w-7xl items-center justify-between px-4 py-4 sm:px-6 lg:px-8">
            <a href="{{ url('/') }}" class="text-xl font-bold text-cyan-700">Aqualytics</a>

            <div class="flex items-center gap-5 text-sm font-medium">
                @auth
                    <a href="{{ route('dashboard') }}" class="text-slate-600 hover:text-cyan-700">
                        Dashboard
                    </a>
                    <a href="{{ route('ponds.index') }}" class="text-slate-600 hover:text-cyan-700">
                        Estanques
                    </a>
                    @if (auth()->user()->role === \App\Models\User::ROLE_ADMIN)
                        <a href="{{ route('users.index') }}" class="text-slate-600 hover:text-cyan-700">
                            Usuarios
                        </a>
                    @endif
                    <div class="hidden text-right md:block">
                        <p class="font-medium text-slate-700">{{ auth()->user()->fishFarm->name }}</p>
                        <p class="text-xs text-slate-400">
                            {{ auth()->user()->name }} ·
                            {{ [
                                'admin' => 'Administrador',
                                'supervisor' => 'Supervisor',
                                'specialist' => 'Especialista',
                            ][auth()->user()->role] ?? auth()->user()->role }}
                        </p>
                    </div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="rounded-lg bg-slate-900 px-4 py-2 text-white hover:bg-slate-700">
                            Cerrar sesión
                        </button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="text-slate-600 hover:text-cyan-700">
                        Iniciar sesión
                    </a>
                    <a href="{{ route('register') }}" class="rounded-lg bg-cyan-700 px-4 py-2 text-white hover:bg-cyan-600">
                        Registrarse
                    </a>
                @endauth
            </div>
        </nav>
    </header>

    <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        @yield('content')
    </main>
</body>
</html>
