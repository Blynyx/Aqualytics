<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Aqualytics')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('scripts')
</head>
<body class="min-h-screen overflow-x-hidden bg-slate-50 text-slate-950">
    @auth
        @php
            $roleLabel = [
                'admin' => 'Administrador',
                'supervisor' => 'Supervisor',
                'specialist' => 'Especialista',
            ][auth()->user()->role] ?? auth()->user()->role;
            $account = auth()->user()->fishFarm;
        @endphp

        <div class="min-h-screen">
            <div id="sidebar-overlay" class="fixed inset-0 z-40 hidden bg-slate-950/50 backdrop-blur-sm lg:hidden" aria-hidden="true"></div>

            <aside id="app-sidebar" class="fixed inset-y-0 left-0 z-50 flex w-72 -translate-x-full flex-col border-r border-white/10 bg-slate-950 text-white shadow-2xl transition-transform duration-300 lg:translate-x-0">
                <div class="flex h-20 items-center justify-between border-b border-white/10 px-6">
                    <x-app-logo data-cy="brand" class="text-white" />
                    <button type="button" data-sidebar-close class="grid size-11 place-items-center rounded-xl text-slate-400 hover:bg-white/10 hover:text-white lg:hidden" aria-label="Cerrar navegación">
                        <svg class="size-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="m6 6 12 12M18 6 6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                    </button>
                </div>

                <nav class="flex-1 overflow-y-auto px-4 py-6" aria-label="Navegación principal">
                    <p class="px-3 text-[0.625rem] font-bold uppercase tracking-[0.2em] text-slate-500">Operación</p>
                    <div class="mt-3 space-y-1">
                        <a href="{{ route('dashboard') }}" class="flex min-h-11 items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition {{ request()->routeIs('dashboard') ? 'bg-cyan-500/15 text-cyan-300' : 'text-slate-300 hover:bg-white/5 hover:text-white' }}">
                            <svg class="size-5 shrink-0" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M4 4h6v6H4V4Zm10 0h6v10h-6V4ZM4 14h6v6H4v-6Zm10 4h6v2h-6v-2Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/>
                            </svg>
                            Dashboard
                        </a>
                        <a href="{{ route('ponds.index') }}" data-cy="nav-ponds" class="flex min-h-11 items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition {{ request()->routeIs('ponds.*') ? 'bg-cyan-500/15 text-cyan-300' : 'text-slate-300 hover:bg-white/5 hover:text-white' }}">
                            <svg class="size-5 shrink-0" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M4 8c0-2.2 3.6-4 8-4s8 1.8 8 4-3.6 4-8 4-8-1.8-8-4Z" stroke="currentColor" stroke-width="1.7"/>
                                <path d="M4 8v8c0 2.2 3.6 4 8 4s8-1.8 8-4V8M4 12c0 2.2 3.6 4 8 4s8-1.8 8-4" stroke="currentColor" stroke-width="1.7"/>
                            </svg>
                            {{ $account->unitsLabel() }}
                        </a>
                        @if ($account->isFarm() && auth()->user()->role === \App\Models\User::ROLE_ADMIN)
                            <a href="{{ route('users.index') }}" class="flex min-h-11 items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition {{ request()->routeIs('users.*') ? 'bg-cyan-500/15 text-cyan-300' : 'text-slate-300 hover:bg-white/5 hover:text-white' }}">
                                <svg class="size-5 shrink-0" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="M16 20v-1.5c0-2-1.8-3.5-4-3.5H7c-2.2 0-4 1.5-4 3.5V20M9.5 11a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7Zm7-1a3 3 0 0 0 0-5.8M17 14c2.2 0 4 1.5 4 3.5V19" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
                                </svg>
                                Usuarios
                            </a>
                        @endif
                    </div>
                </nav>

                <div class="border-t border-white/10 p-4">
                    <div class="rounded-2xl bg-white/[0.06] p-4">
                        <div class="flex items-center gap-3">
                            <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-cyan-500/20 text-sm font-extrabold text-cyan-300">
                                {{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}
                            </span>
                            <div class="min-w-0">
                                <p data-cy="fish-farm-name" class="truncate text-sm font-bold text-white">{{ auth()->user()->fishFarm->name }}</p>
                                <p class="truncate text-xs text-slate-400">{{ auth()->user()->name }}</p>
                            </div>
                        </div>
                        <div class="mt-3 flex items-center justify-between gap-3 border-t border-white/10 pt-3">
                            <p data-cy="user-role" class="text-xs font-semibold text-cyan-300">{{ $roleLabel }}</p>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" data-cy="logout" class="inline-flex min-h-11 items-center gap-1.5 rounded-lg px-2 py-1.5 text-xs font-semibold text-slate-300 transition hover:bg-white/10 hover:text-white" title="Cerrar sesión">
                                    <svg class="size-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                        <path d="M10 5H6a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h4m5-4 3-3-3-3m3 3H9" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                    Salir
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </aside>

            <div class="min-h-screen lg:pl-72">
                <header class="sticky top-0 z-30 border-b border-slate-200/80 bg-white/90 backdrop-blur-xl">
                    <div class="flex h-16 items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">
                        <div class="flex min-w-0 items-center gap-3">
                            <button type="button" data-sidebar-open class="grid size-11 place-items-center rounded-xl border border-slate-200 bg-white text-slate-600 shadow-sm hover:bg-slate-50 lg:hidden" aria-label="Abrir navegación" aria-controls="app-sidebar" aria-expanded="false">
                                <svg class="size-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="M4 7h16M4 12h16M4 17h16" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                </svg>
                            </button>
                            <span class="lg:hidden"><x-app-logo compact class="text-slate-950" /></span>
                            <div class="hidden min-w-0 lg:block">
                                <p class="text-[0.625rem] font-bold uppercase tracking-[0.18em] text-slate-400">Aqualytics</p>
                                <p class="truncate text-sm font-bold text-slate-800">@yield('header-title', 'Centro de operaciones')</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="hidden items-center gap-2 rounded-full border border-slate-200 bg-slate-50 px-3 py-1.5 text-xs font-semibold text-slate-600 sm:inline-flex">
                                <span class="size-2 rounded-full bg-emerald-500"></span>
                                Sistema operativo
                            </span>
                            <span class="grid size-9 place-items-center rounded-full bg-slate-900 text-xs font-extrabold text-white">
                                {{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}
                            </span>
                        </div>
                    </div>
                </header>

                <main class="mx-auto w-full max-w-[100rem] px-4 py-7 sm:px-6 lg:px-8 lg:py-9">
                    @if (session()->has('device_token'))
                        <x-device-token-issued
                            :token="session('device_token')"
                            :device-uid="session('issued_device_uid')"
                        />
                    @endif
                    @yield('content')
                </main>
            </div>
        </div>
    @else
        <main class="min-h-screen">
            @yield('content')
        </main>
    @endauth
</body>
</html>
