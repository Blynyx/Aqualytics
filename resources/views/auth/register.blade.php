@extends('layouts.app')

@section('title', 'Crear cuenta | Aqualytics')

@section('content')
    <div class="min-h-screen bg-slate-50">
        <header class="border-b border-slate-200 bg-white">
            <div class="mx-auto flex max-w-6xl items-center justify-between px-4 py-5 sm:px-6">
                <x-app-logo class="text-slate-950" />
                <a href="{{ route('login') }}" class="btn-secondary">Iniciar sesión</a>
            </div>
        </header>

        <div class="mx-auto grid max-w-6xl gap-10 px-4 py-10 sm:px-6 lg:grid-cols-[0.75fr_1.25fr] lg:items-start lg:py-16">
            <aside class="lg:sticky lg:top-10">
                <p class="section-eyebrow">Comienza con Aqualytics</p>
                <h1 class="mt-4 text-3xl font-extrabold leading-tight tracking-tight text-slate-950 sm:text-4xl">
                    Crea el centro de control de tu piscigranja.
                </h1>
                <p class="mt-4 max-w-md leading-7 text-slate-500">
                    Registra la organización y su primer administrador. Desde allí podrás incorporar al equipo y conectar la operación.
                </p>
                <ul class="mt-8 space-y-4 text-sm font-semibold text-slate-700">
                    @foreach (['Visibilidad integral de los estanques', 'Roles y responsabilidades claras', 'Alertas e incidencias trazables'] as $item)
                        <li class="flex items-center gap-3">
                            <span class="grid size-6 shrink-0 place-items-center rounded-full bg-teal-50 text-teal-700">
                                <svg class="size-3.5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="m5 12 4 4L19 6" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </span>
                            {{ $item }}
                        </li>
                    @endforeach
                </ul>
            </aside>

            <section class="surface-card p-5 sm:p-8 lg:p-10">
                <div class="border-b border-slate-100 pb-6">
                    <h2 class="text-2xl font-extrabold tracking-tight text-slate-950">Configura tu cuenta</h2>
                    <p class="mt-2 text-sm text-slate-500">Completa los datos de la piscigranja y del administrador principal.</p>
                </div>

                <form method="POST" action="{{ url('/register') }}" class="mt-7 grid gap-5 sm:grid-cols-2">
                    @csrf

                    <div class="sm:col-span-2">
                        <label for="fish_farm_name" class="form-label">Nombre de la piscigranja</label>
                        <input id="fish_farm_name" name="fish_farm_name" type="text" value="{{ old('fish_farm_name') }}"
                            required autofocus autocomplete="organization" placeholder="Ej. Piscigranja El Manantial" class="form-control">
                        <p class="form-hint">Este nombre identificará tu espacio de trabajo.</p>
                        @error('fish_farm_name')
                            <p class="form-error"><span aria-hidden="true">●</span>{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="sm:col-span-2">
                        <label for="name" class="form-label">Nombre del administrador</label>
                        <input id="name" name="name" type="text" value="{{ old('name') }}" required
                            autocomplete="name" placeholder="Nombre y apellido" class="form-control">
                        @error('name')
                            <p class="form-error"><span aria-hidden="true">●</span>{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="sm:col-span-2">
                        <label for="email" class="form-label">Correo electrónico</label>
                        <input id="email" name="email" type="email" value="{{ old('email') }}" required
                            autocomplete="email" placeholder="administrador@piscigranja.com" class="form-control">
                        @error('email')
                            <p class="form-error"><span aria-hidden="true">●</span>{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="password" class="form-label">Contraseña</label>
                        <input id="password" name="password" type="password" required autocomplete="new-password"
                            placeholder="Mínimo 8 caracteres" class="form-control">
                        @error('password')
                            <p class="form-error"><span aria-hidden="true">●</span>{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="password_confirmation" class="form-label">Confirmar contraseña</label>
                        <input id="password_confirmation" name="password_confirmation" type="password" required
                            autocomplete="new-password" placeholder="Repite la contraseña" class="form-control">
                    </div>

                    <div class="mt-2 flex flex-col-reverse gap-3 border-t border-slate-100 pt-6 sm:col-span-2 sm:flex-row sm:items-center sm:justify-between">
                        <p class="text-xs leading-5 text-slate-500">Al crear la cuenta podrás comenzar a registrar estanques y usuarios.</p>
                        <button type="submit" class="btn-primary shrink-0">
                            Crear cuenta
                            <svg class="size-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="m9 18 6-6-6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </button>
                    </div>
                </form>
            </section>
        </div>
    </div>
@endsection
