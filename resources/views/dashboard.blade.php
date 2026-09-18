@extends('layouts.app')

@section('title', 'Dashboard | Aqualytics')
@section('header-title', 'Dashboard')

@section('content')
    <x-page-header
        eyebrow="Resumen general"
        title="Dashboard"
        :description="$account->isHome() ? 'Monitoreo de tu pecera, dispositivos, lecturas y alertas.' : 'Monitoreo general de la piscigranja y sus variables operativas.'"
    />

    @if ($plan)
        <section class="mb-8 surface-card p-5 sm:p-6" aria-label="Plan actual">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="text-[0.625rem] font-bold uppercase tracking-[0.18em] text-slate-400">Plan actual</p>
                    <h2 class="mt-1 text-xl font-extrabold tracking-tight text-slate-950">{{ $plan->name }}</h2>
                </div>
            </div>
            <dl class="mt-5 grid gap-4 sm:grid-cols-3">
                <div class="rounded-2xl bg-slate-50 px-4 py-3">
                    <dt class="text-xs font-semibold text-slate-500">{{ $account->unitsLabel() }}</dt>
                    <dd class="mt-1 text-lg font-extrabold text-slate-950">{{ $usage['units']['current'] }} / {{ $usage['units']['max'] }}</dd>
                </div>
                <div class="rounded-2xl bg-slate-50 px-4 py-3">
                    <dt class="text-xs font-semibold text-slate-500">Dispositivos</dt>
                    <dd class="mt-1 text-lg font-extrabold text-slate-950">{{ $usage['devices']['current'] }} / {{ $usage['devices']['max'] }}</dd>
                </div>
                <div class="rounded-2xl bg-slate-50 px-4 py-3">
                    <dt class="text-xs font-semibold text-slate-500">Usuarios</dt>
                    <dd class="mt-1 text-lg font-extrabold text-slate-950">{{ $usage['users']['current'] }} / {{ $usage['users']['max'] }}</dd>
                </div>
            </dl>
        </section>
    @endif

    <section class="grid gap-4 sm:grid-cols-2 2xl:grid-cols-4" aria-label="Indicadores generales">
        <article class="surface-card group p-5 transition hover:-translate-y-0.5 hover:shadow-md">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-sm font-semibold text-slate-500">{{ $account->unitsLabel() }}</p>
                    <p class="mt-2 text-3xl font-extrabold tracking-tight text-slate-950">{{ $pondCount }}</p>
                    <p class="mt-1 text-xs text-slate-400">{{ $account->isHome() ? 'Tu pecera' : 'Unidades de producción' }}</p>
                </div>
                <span class="grid size-11 place-items-center rounded-xl bg-cyan-50 text-cyan-700">
                    <svg class="size-6" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M4 8c0-2.2 3.6-4 8-4s8 1.8 8 4-3.6 4-8 4-8-1.8-8-4Z" stroke="currentColor" stroke-width="1.7"/>
                        <path d="M4 8v8c0 2.2 3.6 4 8 4s8-1.8 8-4V8" stroke="currentColor" stroke-width="1.7"/>
                    </svg>
                </span>
            </div>
        </article>

        <article class="surface-card group p-5 transition hover:-translate-y-0.5 hover:shadow-md">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-sm font-semibold text-slate-500">Dispositivos</p>
                    <p class="mt-2 text-3xl font-extrabold tracking-tight text-slate-950">{{ $deviceCount }}</p>
                    <p class="mt-1 text-xs text-slate-400">Sensores IoT registrados</p>
                </div>
                <span class="grid size-11 place-items-center rounded-xl bg-blue-50 text-blue-700">
                    <svg class="size-6" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <rect x="6" y="3" width="12" height="18" rx="3" stroke="currentColor" stroke-width="1.7"/>
                        <path d="M9 7h6M9 11h6M10 17h4" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
                    </svg>
                </span>
            </div>
        </article>

        <article class="surface-card group p-5 transition hover:-translate-y-0.5 hover:shadow-md">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-sm font-semibold text-slate-500">Lecturas</p>
                    <p class="mt-2 text-3xl font-extrabold tracking-tight text-slate-950">{{ $readingCount }}</p>
                    <p class="mt-1 text-xs text-slate-400">Registros de monitoreo</p>
                </div>
                <span class="grid size-11 place-items-center rounded-xl bg-teal-50 text-teal-700">
                    <svg class="size-6" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M4 17 9 12l3 3 7-8" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M15 7h4v4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </span>
            </div>
        </article>

        <article class="surface-card group p-5 transition hover:-translate-y-0.5 hover:shadow-md">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-sm font-semibold text-slate-500">Alertas activas</p>
                    <p class="mt-2 text-3xl font-extrabold tracking-tight text-slate-950">{{ $activeAlertCount }}</p>
                    <p class="mt-1 text-xs text-slate-400">Requieren seguimiento</p>
                </div>
                <span class="grid size-11 place-items-center rounded-xl {{ $activeAlertCount > 0 ? 'bg-amber-50 text-amber-700' : 'bg-emerald-50 text-emerald-700' }}">
                    <svg class="size-6" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M12 8v5m0 3h.01M10.3 4.8 3.2 17.1A2 2 0 0 0 4.9 20h14.2a2 2 0 0 0 1.7-2.9L13.7 4.8a2 2 0 0 0-3.4 0Z" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
                    </svg>
                </span>
            </div>
        </article>
    </section>

    <section class="mt-8">
        <div class="mb-4 flex items-end justify-between gap-4">
            <div>
                <h2 class="text-lg font-extrabold tracking-tight text-slate-900">{{ $account->isHome() ? 'Mi pecera' : 'Estado de los estanques' }}</h2>
                <p class="mt-1 text-sm text-slate-500">Vista rápida de las unidades bajo monitoreo.</p>
            </div>
            <a href="{{ route('ponds.index') }}" class="hidden text-sm font-bold text-cyan-700 hover:text-cyan-900 sm:inline">Ver todos</a>
        </div>

        @if ($ponds->isNotEmpty())
            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                @foreach ($ponds as $pond)
                    <a href="{{ route('ponds.show', $pond) }}" class="surface-card group block p-5 transition hover:border-cyan-200 hover:shadow-md">
                        <div class="flex items-start justify-between gap-4">
                            <span class="grid size-10 place-items-center rounded-xl bg-slate-100 text-slate-600 transition group-hover:bg-cyan-50 group-hover:text-cyan-700">
                                <svg class="size-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="M4 10c0-2.2 3.6-4 8-4s8 1.8 8 4-3.6 4-8 4-8-1.8-8-4Z" stroke="currentColor" stroke-width="1.7"/>
                                    <path d="M4 10v5c0 2.2 3.6 4 8 4s8-1.8 8-4v-5" stroke="currentColor" stroke-width="1.7"/>
                                </svg>
                            </span>
                            <x-status-badge :status="$pond->status" />
                        </div>
                        <h3 class="mt-4 font-bold text-slate-900 group-hover:text-cyan-800">{{ $pond->name }}</h3>
                        <p class="mt-1 text-sm text-slate-500">{{ $pond->species ?: 'Especie no definida' }}</p>
                        <p class="mt-4 text-xs font-bold uppercase tracking-wider text-slate-400">{{ $pond->code }}</p>
                    </a>
                @endforeach
            </div>
        @else
            <div class="surface-card">
                <x-empty-state :title="$account->isHome() ? 'Sin pecera registrada' : 'Sin estanques registrados'" :description="$account->isHome() ? 'Cuando registres tu pecera aparecerá aquí su estado.' : 'Cuando registres tu primera unidad aparecerá aquí su estado operativo.'">
                    @if (auth()->user()->role === \App\Models\User::ROLE_ADMIN)
                        <x-slot:action>
                            <a href="{{ route('ponds.create') }}" class="btn-primary">{{ $account->isHome() ? 'Registrar pecera' : 'Registrar estanque' }}</a>
                        </x-slot:action>
                    @endif
                </x-empty-state>
            </div>
        @endif
    </section>

    <div class="mt-8 grid gap-8 2xl:grid-cols-[1.35fr_0.65fr]">
        <section class="surface-card min-w-0 overflow-hidden">
            <div class="flex items-center justify-between border-b border-slate-100 px-5 py-5 sm:px-6">
                <div>
                    <h2 class="text-lg font-extrabold tracking-tight text-slate-900">Últimas lecturas</h2>
                    <p class="mt-1 text-sm text-slate-500">Datos recientes recibidos desde los sensores.</p>
                </div>
                <span class="hidden rounded-lg bg-teal-50 px-2.5 py-1 text-xs font-bold text-teal-700 sm:block">Tiempo real</span>
            </div>
            @if ($latestReadings->isNotEmpty())
                <div class="overflow-x-auto">
                    <table class="min-w-[52rem] w-full text-sm">
                        <thead class="table-head">
                            <tr>
                                <th class="px-6 py-3.5">{{ $account->unitLabel() }} / sensor</th>
                                <th class="px-4 py-3.5">Temperatura</th>
                                <th class="px-4 py-3.5">pH</th>
                                <th class="px-4 py-3.5">Turbidez</th>
                                <th class="px-4 py-3.5">Nivel</th>
                                <th class="px-6 py-3.5 text-right">Fecha</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($latestReadings as $reading)
                                <tr class="transition hover:bg-slate-50/80">
                                    <td class="px-6 py-4">
                                        <p class="font-bold text-slate-800">{{ $reading->pond->name }}</p>
                                        <p class="mt-0.5 text-xs text-slate-400">{{ $reading->device->name }}</p>
                                    </td>
                                    <td class="px-4 py-4 font-semibold text-slate-700">{{ $reading->temperature }} °C</td>
                                    <td class="px-4 py-4 font-semibold text-slate-700">{{ $reading->ph }}</td>
                                    <td class="px-4 py-4 font-semibold text-slate-700">{{ $reading->turbidity }}</td>
                                    <td class="px-4 py-4 font-semibold text-slate-700">{{ $reading->water_level }} %</td>
                                    <td class="whitespace-nowrap px-6 py-4 text-right text-xs text-slate-500">{{ $reading->recorded_at }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <x-empty-state title="Sin lecturas disponibles" description="Los registros aparecerán cuando un dispositivo comience a enviar mediciones." />
            @endif
        </section>

        <section class="surface-card min-w-0 overflow-hidden">
            <div class="border-b border-slate-100 px-5 py-5 sm:px-6">
                <h2 class="text-lg font-extrabold tracking-tight text-slate-900">Alertas activas</h2>
                <p class="mt-1 text-sm text-slate-500">Incidencias que requieren atención.</p>
            </div>
            @if ($activeAlerts->isNotEmpty())
                <div class="divide-y divide-slate-100">
                    @foreach ($activeAlerts as $alert)
                        <a href="{{ route('ponds.show', $alert->pond) }}" class="flex gap-3 p-5 transition hover:bg-amber-50/50 sm:px-6">
                            <span class="grid size-9 shrink-0 place-items-center rounded-xl bg-amber-50 text-amber-700">
                                <svg class="size-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="M12 8v5m0 3h.01M10.3 4.8 3.2 17.1A2 2 0 0 0 4.9 20h14.2a2 2 0 0 0 1.7-2.9L13.7 4.8a2 2 0 0 0-3.4 0Z" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
                                </svg>
                            </span>
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <p class="font-bold text-slate-800">{{ $alert->pond->name }}</p>
                                    <x-status-badge :status="$alert->status" />
                                </div>
                                <p class="mt-1 text-sm font-medium text-amber-800">{{ $alert->message }}</p>
                                <p class="mt-2 text-xs text-slate-400">{{ $alert->parameter }} · {{ $alert->value }} · {{ $alert->detected_at }}</p>
                            </div>
                        </a>
                    @endforeach
                </div>
            @else
                <x-empty-state title="Operación sin alertas" description="Todos los parámetros monitoreados se encuentran sin incidencias activas." />
            @endif
        </section>
    </div>
@endsection
