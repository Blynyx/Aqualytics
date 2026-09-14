@extends('layouts.app')

@section('title', 'Estanques | Aqualytics')
@section('header-title', 'Estanques')

@section('content')
    <x-page-header
        eyebrow="Gestión acuícola"
        title="Estanques"
        description="Administra las unidades de producción y consulta su estado operativo."
    >
        @if (auth()->user()->role === \App\Models\User::ROLE_ADMIN)
            <x-slot:actions>
                <a href="{{ route('ponds.create') }}" data-cy="new-pond" class="btn-primary w-full sm:w-auto">
                    <svg class="size-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                    Nuevo estanque
                </a>
            </x-slot:actions>
        @endif
    </x-page-header>

    <div class="surface-card overflow-hidden">
        <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4 sm:px-6">
            <div>
                <h2 class="font-extrabold text-slate-900">Unidades registradas</h2>
                <p class="mt-1 text-xs text-slate-500">{{ $ponds->count() }} {{ $ponds->count() === 1 ? 'estanque' : 'estanques' }} en esta piscigranja</p>
            </div>
            <span class="grid size-9 place-items-center rounded-xl bg-cyan-50 text-cyan-700">
                <svg class="size-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M4 8c0-2.2 3.6-4 8-4s8 1.8 8 4-3.6 4-8 4-8-1.8-8-4Z" stroke="currentColor" stroke-width="1.7"/>
                    <path d="M4 8v8c0 2.2 3.6 4 8 4s8-1.8 8-4V8" stroke="currentColor" stroke-width="1.7"/>
                </svg>
            </span>
        </div>

        @if ($ponds->isNotEmpty())
            <div class="overflow-x-auto">
                <table class="min-w-[52rem] w-full text-sm">
                    <thead class="table-head">
                        <tr>
                            <th class="px-6 py-3.5">Estanque</th>
                            <th class="px-4 py-3.5">Código</th>
                            <th class="px-4 py-3.5">Especie</th>
                            <th class="px-4 py-3.5">Ubicación</th>
                            <th class="px-4 py-3.5">Estado</th>
                            <th class="px-6 py-3.5 text-right">Acción</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($ponds as $pond)
                            <tr class="transition hover:bg-slate-50/80">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-slate-100 text-slate-600">
                                            <svg class="size-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                                <path d="M4 10c0-2.2 3.6-4 8-4s8 1.8 8 4-3.6 4-8 4-8-1.8-8-4Z" stroke="currentColor" stroke-width="1.7"/>
                                                <path d="M4 10v5c0 2.2 3.6 4 8 4s8-1.8 8-4v-5" stroke="currentColor" stroke-width="1.7"/>
                                            </svg>
                                        </span>
                                        <span class="font-bold text-slate-900">{{ $pond->name }}</span>
                                    </div>
                                </td>
                                <td class="px-4 py-4 font-mono text-xs font-semibold text-slate-600">{{ $pond->code }}</td>
                                <td class="px-4 py-4 text-slate-600">{{ $pond->species ?: '—' }}</td>
                                <td class="px-4 py-4 text-slate-600">{{ $pond->location ?: '—' }}</td>
                                <td class="px-4 py-4"><x-status-badge :status="$pond->status" /></td>
                                <td class="px-6 py-4 text-right">
                                    <a href="{{ route('ponds.show', $pond) }}" data-cy="pond-details" class="inline-flex items-center gap-1.5 font-bold text-cyan-700 hover:text-cyan-900">
                                        Ver detalles
                                        <svg class="size-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                            <path d="m9 18 6-6-6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <x-empty-state title="Aún no hay estanques" description="Registra la primera unidad para comenzar a centralizar lecturas, sensores y alertas.">
                @if (auth()->user()->role === \App\Models\User::ROLE_ADMIN)
                    <x-slot:action>
                        <a href="{{ route('ponds.create') }}" class="btn-primary">Registrar estanque</a>
                    </x-slot:action>
                @endif
            </x-empty-state>
        @endif
    </div>
@endsection
