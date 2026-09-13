@extends('layouts.app')

@section('title', 'Nuevo usuario | Aqualytics')

@section('content')
    <div class="mx-auto max-w-2xl">
        <div class="mb-8">
            <a href="{{ route('users.index') }}" class="text-sm font-medium text-cyan-700 hover:underline">
                ← Volver a usuarios
            </a>
            <h1 class="mt-3 text-3xl font-bold">Nuevo usuario</h1>
            <p class="mt-2 text-slate-500">
                Agrega un supervisor o especialista a {{ auth()->user()->fishFarm->name }}.
            </p>
        </div>

        <form method="POST" action="{{ route('users.store') }}" class="space-y-5 rounded-2xl border border-slate-200 bg-white p-8 shadow-sm">
            @csrf

            <div>
                <label for="name" class="mb-2 block text-sm font-medium">Nombre</label>
                <input id="name" name="name" value="{{ old('name') }}" required autofocus
                    class="w-full rounded-lg border border-slate-300 px-3 py-2.5 focus:border-cyan-600 focus:ring-2 focus:ring-cyan-100">
                @error('name')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="email" class="mb-2 block text-sm font-medium">Correo electrónico</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required
                    class="w-full rounded-lg border border-slate-300 px-3 py-2.5 focus:border-cyan-600 focus:ring-2 focus:ring-cyan-100">
                @error('email')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="role" class="mb-2 block text-sm font-medium">Rol</label>
                <select id="role" name="role" required
                    class="w-full rounded-lg border border-slate-300 px-3 py-2.5 focus:border-cyan-600 focus:ring-2 focus:ring-cyan-100">
                    <option value="">Selecciona un rol</option>
                    <option value="supervisor" @selected(old('role') === 'supervisor')>Supervisor</option>
                    <option value="specialist" @selected(old('role') === 'specialist')>Especialista</option>
                </select>
                @error('role')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <label for="password" class="mb-2 block text-sm font-medium">Contraseña</label>
                    <input id="password" name="password" type="password" required
                        class="w-full rounded-lg border border-slate-300 px-3 py-2.5 focus:border-cyan-600 focus:ring-2 focus:ring-cyan-100">
                    @error('password')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="password_confirmation" class="mb-2 block text-sm font-medium">
                        Confirmar contraseña
                    </label>
                    <input id="password_confirmation" name="password_confirmation" type="password" required
                        class="w-full rounded-lg border border-slate-300 px-3 py-2.5 focus:border-cyan-600 focus:ring-2 focus:ring-cyan-100">
                </div>
            </div>

            <div class="flex justify-end gap-3">
                <a href="{{ route('users.index') }}" class="rounded-lg border border-slate-300 px-4 py-2.5 font-medium text-slate-600 hover:bg-slate-50">
                    Cancelar
                </a>
                <button type="submit" class="rounded-lg bg-cyan-700 px-5 py-2.5 font-semibold text-white hover:bg-cyan-600">
                    Crear usuario
                </button>
            </div>
        </form>
    </div>
@endsection
