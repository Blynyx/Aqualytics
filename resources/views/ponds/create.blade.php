@extends('layouts.app')

@section('title', 'Nuevo estanque | Aqualytics')
@section('header-title', 'Nuevo estanque')

@section('content')
    <div class="mx-auto max-w-4xl">
        <x-page-header
            eyebrow="Gestión acuícola"
            title="Nuevo estanque"
            description="Registra una nueva unidad de producción para comenzar su monitoreo."
            :back-url="route('ponds.index')"
            back-label="Volver a estanques"
        />

        <form method="POST" action="{{ url('/ponds') }}" class="surface-card overflow-hidden">
            @csrf

            <div class="border-b border-slate-100 px-5 py-5 sm:px-8">
                <div class="flex items-center gap-3">
                    <span class="grid size-10 place-items-center rounded-xl bg-cyan-50 text-cyan-700">
                        <svg class="size-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M4 10c0-2.2 3.6-4 8-4s8 1.8 8 4-3.6 4-8 4-8-1.8-8-4Z" stroke="currentColor" stroke-width="1.7"/>
                            <path d="M4 10v5c0 2.2 3.6 4 8 4s8-1.8 8-4v-5" stroke="currentColor" stroke-width="1.7"/>
                        </svg>
                    </span>
                    <div>
                        <h2 class="font-extrabold text-slate-900">Información general</h2>
                        <p class="mt-0.5 text-sm text-slate-500">Datos para identificar el estanque dentro de la plataforma.</p>
                    </div>
                </div>
            </div>

            <div class="grid gap-6 p-5 sm:grid-cols-2 sm:p-8">
                <div>
                    <label for="name" class="form-label">Nombre del estanque</label>
                    <input id="name" name="name" data-cy="pond-name" value="{{ old('name') }}" required
                        autofocus placeholder="Ej. Estanque Norte" class="form-control">
                    <p class="form-hint">Usa un nombre fácil de reconocer por el equipo.</p>
                    @error('name')
                        <p class="form-error"><span aria-hidden="true">●</span>{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="code" class="form-label">Código interno</label>
                    <input id="code" name="code" data-cy="pond-code" value="{{ old('code') }}" required
                        placeholder="Ej. EST-001" class="form-control font-mono uppercase">
                    <p class="form-hint">Identificador único para reportes y sensores.</p>
                    @error('code')
                        <p class="form-error"><span aria-hidden="true">●</span>{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="species" class="form-label">Especie</label>
                    <input id="species" name="species" data-cy="pond-species" value="{{ old('species') }}"
                        placeholder="Ej. Tilapia" class="form-control">
                    <p class="form-hint">Especie principal cultivada en esta unidad.</p>
                    @error('species')
                        <p class="form-error"><span aria-hidden="true">●</span>{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="location" class="form-label">Ubicación</label>
                    <input id="location" name="location" data-cy="pond-location" value="{{ old('location') }}"
                        placeholder="Ej. Sector norte" class="form-control">
                    <p class="form-hint">Referencia física dentro de la piscigranja.</p>
                    @error('location')
                        <p class="form-error"><span aria-hidden="true">●</span>{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="flex flex-col-reverse gap-3 border-t border-slate-100 bg-slate-50/60 px-5 py-4 sm:flex-row sm:justify-end sm:px-8">
                <a href="{{ route('ponds.index') }}" class="btn-secondary">
                    Cancelar
                </a>
                <button type="submit" data-cy="submit-pond" class="btn-primary">
                    Crear estanque
                </button>
            </div>
        </form>
    </div>
@endsection
