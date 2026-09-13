@extends('layouts.app')

@section('title', 'Crear cuenta | Aqualytics')

@section('content')
    <div class="mx-auto max-w-md">
        <div class="rounded-2xl border border-slate-200 bg-white p-8 shadow-sm">
            <div class="mb-7">
                <p class="text-sm font-semibold uppercase tracking-wider text-cyan-700">Comienza ahora</p>
                <h1 class="mt-2 text-2xl font-bold">Crear cuenta</h1>
                <p class="mt-2 text-sm text-slate-500">Administra tus estanques desde un solo lugar.</p>
            </div>

            <form method="POST" action="{{ url('/register') }}" class="space-y-5">
                @csrf

                <div>
                    <label for="name" class="mb-2 block text-sm font-medium">Nombre</label>
                    <input
                        id="name"
                        name="name"
                        type="text"
                        value="{{ old('name') }}"
                        required
                        autofocus
                        autocomplete="name"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2.5 outline-none focus:border-cyan-600 focus:ring-2 focus:ring-cyan-100"
                    >
                    @error('name')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="email" class="mb-2 block text-sm font-medium">Correo electrónico</label>
                    <input
                        id="email"
                        name="email"
                        type="email"
                        value="{{ old('email') }}"
                        required
                        autocomplete="email"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2.5 outline-none focus:border-cyan-600 focus:ring-2 focus:ring-cyan-100"
                    >
                    @error('email')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password" class="mb-2 block text-sm font-medium">Contraseña</label>
                    <input
                        id="password"
                        name="password"
                        type="password"
                        required
                        autocomplete="new-password"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2.5 outline-none focus:border-cyan-600 focus:ring-2 focus:ring-cyan-100"
                    >
                    @error('password')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password_confirmation" class="mb-2 block text-sm font-medium">
                        Confirmar contraseña
                    </label>
                    <input
                        id="password_confirmation"
                        name="password_confirmation"
                        type="password"
                        required
                        autocomplete="new-password"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2.5 outline-none focus:border-cyan-600 focus:ring-2 focus:ring-cyan-100"
                    >
                </div>

                <button type="submit" class="w-full rounded-lg bg-cyan-700 px-4 py-3 font-semibold text-white hover:bg-cyan-600">
                    Crear cuenta
                </button>
            </form>

            <p class="mt-6 text-center text-sm text-slate-500">
                ¿Ya tienes una cuenta?
                <a href="{{ route('login') }}" class="font-semibold text-cyan-700 hover:underline">Inicia sesión</a>
            </p>
        </div>
    </div>
@endsection
