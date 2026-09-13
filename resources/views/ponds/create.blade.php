@extends('layouts.app')

@section('title', 'Nuevo estanque | Aqualytics')

@section('content')
    <div class="mx-auto max-w-2xl">
        <div class="mb-8">
            <a href="{{ route('ponds.index') }}" class="text-sm font-medium text-cyan-700 hover:underline">
                ← Volver a estanques
            </a>
            <h1 class="mt-3 text-3xl font-bold">Nuevo estanque</h1>
            <p class="mt-2 text-slate-500">Registra la información básica de tu estanque.</p>
        </div>

        <form method="POST" action="{{ url('/ponds') }}" class="space-y-5 rounded-2xl border border-slate-200 bg-white p-8 shadow-sm">
            @csrf

            <div>
                <label for="name" class="mb-2 block text-sm font-medium">Nombre</label>
                <input id="name" name="name" value="{{ old('name') }}" required
                    class="w-full rounded-lg border border-slate-300 px-3 py-2.5 focus:border-cyan-600 focus:ring-2 focus:ring-cyan-100">
                @error('name')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="code" class="mb-2 block text-sm font-medium">Código</label>
                <input id="code" name="code" value="{{ old('code') }}" required
                    class="w-full rounded-lg border border-slate-300 px-3 py-2.5 focus:border-cyan-600 focus:ring-2 focus:ring-cyan-100">
                @error('code')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <label for="species" class="mb-2 block text-sm font-medium">Especie</label>
                    <input id="species" name="species" value="{{ old('species') }}"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2.5 focus:border-cyan-600 focus:ring-2 focus:ring-cyan-100">
                    @error('species')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="location" class="mb-2 block text-sm font-medium">Ubicación</label>
                    <input id="location" name="location" value="{{ old('location') }}"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2.5 focus:border-cyan-600 focus:ring-2 focus:ring-cyan-100">
                    @error('location')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="flex justify-end gap-3">
                <a href="{{ route('ponds.index') }}" class="rounded-lg border border-slate-300 px-4 py-2.5 font-medium text-slate-600 hover:bg-slate-50">
                    Cancelar
                </a>
                <button type="submit" class="rounded-lg bg-cyan-700 px-5 py-2.5 font-semibold text-white hover:bg-cyan-600">
                    Crear estanque
                </button>
            </div>
        </form>
    </div>
@endsection
