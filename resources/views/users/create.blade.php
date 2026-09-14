@extends('layouts.app')

@section('title', 'Nuevo usuario | Aqualytics')
@section('header-title', 'Nuevo usuario')

@section('content')
    <div class="mx-auto max-w-4xl">
        <x-page-header
            eyebrow="Gestión de accesos"
            title="Nuevo usuario"
            :description="'Agrega un supervisor o especialista a '.auth()->user()->fishFarm->name.'.'"
            :back-url="route('users.index')"
            back-label="Volver a usuarios"
        />

        <form method="POST" action="{{ route('users.store') }}" class="surface-card overflow-hidden">
            @csrf

            <div class="border-b border-slate-100 px-5 py-5 sm:px-8">
                <div class="flex items-center gap-3">
                    <span class="grid size-10 place-items-center rounded-xl bg-violet-50 text-violet-700">
                        <svg class="size-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M12 13a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm7 7c0-3.3-3.1-5-7-5s-7 1.7-7 5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
                        </svg>
                    </span>
                    <div>
                        <h2 class="font-extrabold text-slate-900">Información y permisos</h2>
                        <p class="mt-0.5 text-sm text-slate-500">Define la identidad, el rol y las credenciales de acceso.</p>
                    </div>
                </div>
            </div>

            <div class="grid gap-6 p-5 sm:grid-cols-2 sm:p-8">
                <div>
                    <label for="name" class="form-label">Nombre completo</label>
                    <input id="name" name="name" value="{{ old('name') }}" required autofocus
                        autocomplete="name" placeholder="Nombre y apellido" class="form-control">
                    @error('name')
                        <p class="form-error"><span aria-hidden="true">●</span>{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="email" class="form-label">Correo electrónico</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" required
                        autocomplete="email" placeholder="usuario@piscigranja.com" class="form-control">
                    @error('email')
                        <p class="form-error"><span aria-hidden="true">●</span>{{ $message }}</p>
                    @enderror
                </div>

                <div class="sm:col-span-2">
                    <label for="role" class="form-label">Rol operativo</label>
                    <select id="role" name="role" required class="form-control">
                        <option value="">Selecciona un rol</option>
                        <option value="supervisor" @selected(old('role') === 'supervisor')>Supervisor</option>
                        <option value="specialist" @selected(old('role') === 'specialist')>Especialista</option>
                    </select>
                    <p class="form-hint">El supervisor gestiona incidencias; el especialista ejecuta y documenta acciones correctivas.</p>
                    @error('role')
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
            </div>

            <div class="flex flex-col-reverse gap-3 border-t border-slate-100 bg-slate-50/60 px-5 py-4 sm:flex-row sm:justify-end sm:px-8">
                <a href="{{ route('users.index') }}" class="btn-secondary">
                    Cancelar
                </a>
                <button type="submit" class="btn-primary">
                    Crear usuario
                </button>
            </div>
        </form>
    </div>
@endsection
