@extends('layouts.app')

@section('title', 'Iniciar sesión | Aqualytics')

@section('content')
    <div class="grid min-h-screen bg-white lg:grid-cols-[minmax(0,1.05fr)_minmax(32rem,0.95fr)]">
        <section class="relative hidden overflow-hidden bg-slate-950 p-12 text-white lg:flex lg:flex-col lg:justify-between">
            <div class="absolute inset-0 opacity-60" aria-hidden="true">
                <div class="absolute -left-24 top-24 size-96 rounded-full bg-cyan-500/20 blur-3xl"></div>
                <div class="absolute -bottom-32 right-0 size-[30rem] rounded-full bg-teal-500/15 blur-3xl"></div>
                <svg class="absolute inset-x-0 bottom-0 w-full text-cyan-400/15" viewBox="0 0 900 360" fill="none">
                    <path d="M-20 178c130-74 210 67 351-3s225 57 350-5 198 14 249 31v179H-20V178Z" fill="currentColor"/>
                    <path d="M-20 233c130-74 210 67 351-3s225 57 350-5 198 14 249 31" stroke="currentColor" stroke-width="2"/>
                </svg>
            </div>

            <div class="relative flex items-center gap-3">
                <span class="grid size-11 place-items-center rounded-xl bg-cyan-500 text-white shadow-lg shadow-cyan-950/30">
                    <svg class="size-7" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M4 14.5C7.2 11.7 9 8.8 12 4c3 4.8 4.8 7.7 8 10.5M4 17c2.1 0 2.1 1.5 4.1 1.5S10.2 17 12.2 17s2.1 1.5 4.1 1.5S18.4 17 20.4 17" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                    </svg>
                </span>
                <span class="text-xl font-extrabold tracking-tight">Aqualytics</span>
            </div>

            <div class="relative max-w-xl pb-16">
                <p class="section-eyebrow text-cyan-300">Aquaculture Intelligence</p>
                <h1 class="mt-5 text-5xl font-extrabold leading-[1.08] tracking-tight">
                    Monitoreo inteligente para piscigranjas.
                </h1>
                <p class="mt-6 max-w-lg text-lg leading-8 text-slate-300">
                    Centraliza sensores, calidad del agua e incidencias para tomar decisiones con claridad y confianza.
                </p>
                <div class="mt-10 grid grid-cols-3 gap-3">
                    @foreach (['Datos en contexto', 'Alertas oportunas', 'Operación trazable'] as $benefit)
                        <div class="rounded-2xl border border-white/10 bg-white/[0.05] px-4 py-4 text-sm font-semibold text-slate-200 backdrop-blur">
                            <span class="mb-3 block size-2 rounded-full bg-cyan-400"></span>
                            {{ $benefit }}
                        </div>
                    @endforeach
                </div>
            </div>

            <p class="relative text-xs text-slate-500">Plataforma de gestión acuícola e IoT</p>
        </section>

        <section class="flex min-h-screen items-center justify-center bg-slate-50 px-4 py-10 sm:px-8">
            <div class="w-full max-w-md">
                <x-app-logo data-cy="brand" class="mb-10 text-slate-950 lg:mb-12" />

                <div>
                    <p class="section-eyebrow">Acceso seguro</p>
                    <h2 class="mt-3 text-3xl font-extrabold tracking-tight text-slate-950">Bienvenido de nuevo</h2>
                    <p class="mt-2 text-sm leading-6 text-slate-500">Ingresa tus credenciales para acceder al centro de monitoreo.</p>
                </div>

                <form method="POST" action="{{ url('/login') }}" class="mt-8 space-y-5">
                    @csrf

                    <div>
                        <label for="email" class="form-label">Correo electrónico</label>
                        <input id="email" name="email" data-cy="login-email" type="email" value="{{ old('email') }}"
                            required autofocus autocomplete="email" placeholder="nombre@piscigranja.com"
                            class="form-control">
                        @error('email')
                            <p class="form-error">
                                <span aria-hidden="true">●</span>{{ $message }}
                            </p>
                        @enderror
                    </div>

                    <div>
                        <label for="password" class="form-label">Contraseña</label>
                        <input id="password" name="password" data-cy="login-password" type="password"
                            required autocomplete="current-password" placeholder="Ingresa tu contraseña"
                            class="form-control">
                        @error('password')
                            <p class="form-error">
                                <span aria-hidden="true">●</span>{{ $message }}
                            </p>
                        @enderror
                    </div>

                    <button type="submit" data-cy="login-submit" class="btn-primary w-full py-3">
                        Ingresar al panel
                        <svg class="size-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="m9 18 6-6-6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </button>
                </form>

                <p class="mt-8 text-center text-sm text-slate-500">
                    ¿Aún no tienes una cuenta?
                    <a href="{{ route('register') }}" class="font-bold text-cyan-700 hover:text-cyan-900 hover:underline">Crea tu espacio</a>
                </p>
            </div>
        </section>
    </div>
@endsection
