@extends('layouts.app')

@section('title', 'Iniciar sesión | Aqualytics')

@section('content')
    <div class="mx-auto max-w-md">
        <div class="rounded-2xl border border-slate-200 bg-white p-8 shadow-sm">
            <div class="mb-7">
                <p class="text-sm font-semibold uppercase tracking-wider text-cyan-700">Bienvenido</p>
                <h1 class="mt-2 text-2xl font-bold">Iniciar sesión</h1>
                <p class="mt-2 text-sm text-slate-500">Accede al monitoreo de tus estanques.</p>
            </div>

            <form method="POST" action="{{ url('/login') }}" class="space-y-5">
                @csrf

                <div>
                    <label for="email" class="mb-2 block text-sm font-medium">Correo electrónico</label>
                    <input
                        id="email"
                        name="email"
                        data-cy="login-email"
                        type="email"
                        value="{{ old('email') }}"
                        required
                        autofocus
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
                        data-cy="login-password"
                        type="password"
                        required
                        autocomplete="current-password"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2.5 outline-none focus:border-cyan-600 focus:ring-2 focus:ring-cyan-100"
                    >
                    @error('password')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <button type="submit" data-cy="login-submit" class="w-full rounded-lg bg-cyan-700 px-4 py-3 font-semibold text-white hover:bg-cyan-600">
                    Ingresar
                </button>
            </form>

            <p class="mt-6 text-center text-sm text-slate-500">
                ¿No tienes cuenta?
                <a href="{{ route('register') }}" class="font-semibold text-cyan-700 hover:underline">Regístrate</a>
            </p>
        </div>
    </div>
@endsection
