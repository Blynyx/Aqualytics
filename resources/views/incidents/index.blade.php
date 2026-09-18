@extends('layouts.app')

@section('title', 'Incidencias | Aqualytics')
@section('header-title', 'Incidencias')

@section('content')
    <x-page-header
        eyebrow="Gestión operativa"
        title="Incidencias"
        description="Seguimiento de alertas IoT convertidas en trabajo asignable para el equipo."
    />

    <section class="surface-card overflow-hidden" data-cy="incident-list">
        <div class="border-b border-slate-100 px-5 py-5 sm:px-6">
            <h2 class="text-lg font-extrabold tracking-tight text-slate-900">
                {{ auth()->user()->role === \App\Models\User::ROLE_SPECIALIST ? 'Mis incidencias asignadas' : 'Incidencias de la cuenta' }}
            </h2>
            <p class="mt-1 text-sm text-slate-500">Las alertas originales se conservan. Aquí solo se gestiona el trabajo.</p>
        </div>

        @if ($incidents->isNotEmpty())
            <div class="overflow-x-auto">
                <table class="min-w-[56rem] w-full text-sm">
                    <thead class="table-head">
                        <tr>
                            <th class="px-6 py-3.5">Incidencia</th>
                            <th class="px-4 py-3.5">Unidad</th>
                            <th class="px-4 py-3.5">Asignada a</th>
                            <th class="px-4 py-3.5">Estado</th>
                            <th class="px-6 py-3.5 text-right">Acción</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($incidents as $incident)
                            <tr data-cy="incident-row" class="transition hover:bg-slate-50/80">
                                <td class="px-6 py-4">
                                    <p class="font-bold text-slate-800">{{ $incident->title }}</p>
                                    <p class="mt-1 text-xs font-semibold uppercase tracking-wider text-slate-400">
                                        Alerta #{{ $incident->alert_id }}
                                    </p>
                                </td>
                                <td class="px-4 py-4 font-semibold text-slate-600">
                                    {{ $incident->alert?->pond?->name ?? '—' }}
                                </td>
                                <td class="px-4 py-4 font-semibold text-slate-600">
                                    {{ $incident->assignee?->name ?? 'Sin asignar' }}
                                </td>
                                <td class="px-4 py-4">
                                    <x-status-badge :status="$incident->status" />
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <a href="{{ route('incidents.show', $incident) }}" data-cy="incident-details" class="inline-flex items-center gap-1.5 font-bold text-cyan-700 hover:text-cyan-900">
                                        Ver detalle
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
            <x-empty-state title="Sin incidencias" description="Las alertas activas pueden convertirse en incidencias desde el detalle del estanque o pecera." />
        @endif
    </section>
@endsection
