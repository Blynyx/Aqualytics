<x-page-header
    eyebrow="Operación de la piscigranja"
    title="Dashboard"
    description="Cómo está tu operación: alertas, incidencias y unidades de tu cuenta."
/>

<section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4" aria-label="Atención operativa">
    <article class="surface-card p-5" data-cy="dashboard-active-alerts">
        <p class="text-sm font-semibold text-slate-500">Alertas activas</p>
        <p class="mt-2 text-3xl font-extrabold tracking-tight text-slate-950">{{ $activeAlertCount }}</p>
    </article>
    <article class="surface-card p-5" data-cy="dashboard-pending-incidents">
        <p class="text-sm font-semibold text-slate-500">Incidencias pendientes</p>
        <p class="mt-2 text-3xl font-extrabold tracking-tight text-slate-950">{{ $pendingIncidentCount }}</p>
    </article>
    <article class="surface-card p-5">
        <p class="text-sm font-semibold text-slate-500">{{ $account->unitsLabel() }}</p>
        <p class="mt-2 text-3xl font-extrabold tracking-tight text-slate-950">{{ $pondCount }}</p>
    </article>
    <article class="surface-card p-5">
        <p class="text-sm font-semibold text-slate-500">Dispositivos</p>
        <p class="mt-2 text-3xl font-extrabold tracking-tight text-slate-950">{{ $deviceCount }}</p>
    </article>
</section>

<section class="mt-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
    <article class="surface-card p-5">
        <p class="text-sm font-semibold text-slate-500">Usuarios</p>
        <p class="mt-2 text-3xl font-extrabold">{{ $userCount }}</p>
        <a href="{{ route('users.index') }}" data-cy="dashboard-user-management" class="mt-3 inline-flex text-sm font-bold text-cyan-700">Gestionar usuarios</a>
    </article>
    <article class="surface-card p-5">
        <p class="text-sm font-semibold text-slate-500">Lecturas</p>
        <p class="mt-2 text-3xl font-extrabold">{{ $readingCount }}</p>
    </article>
    <article class="surface-card p-5">
        <p class="text-sm font-semibold text-slate-500">Accesos</p>
        <div class="mt-3 flex flex-col gap-2 text-sm font-bold text-cyan-700">
            <a href="{{ route('ponds.index') }}">Estanques</a>
            <a href="{{ route('incidents.index') }}">Incidencias</a>
            <a href="{{ route('notifications.index') }}">Notificaciones</a>
        </div>
    </article>
</section>

<section class="mt-8">
    <h2 class="mb-4 text-lg font-extrabold tracking-tight text-slate-900">Estado de los estanques</h2>
    @if ($ponds->isNotEmpty())
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            @foreach ($ponds as $pond)
                <a href="{{ route('ponds.show', $pond) }}" class="surface-card block p-5 hover:border-cyan-200">
                    <h3 class="font-bold text-slate-900">{{ $pond->name }}</h3>
                    <p class="mt-1 text-xs font-bold uppercase tracking-wider text-slate-400">{{ $pond->code }}</p>
                </a>
            @endforeach
        </div>
    @else
        <div class="surface-card">
            <x-empty-state data-cy="dashboard-empty-state" title="Sin estanques registrados" description="Cuando registres tu primera unidad aparecerá aquí." />
        </div>
    @endif
</section>

<div class="mt-8 grid gap-8 2xl:grid-cols-2">
    <section class="surface-card overflow-hidden" data-cy="dashboard-latest-readings">
        <div class="border-b border-slate-100 px-5 py-5">
            <h2 class="text-lg font-extrabold">Últimas lecturas</h2>
        </div>
        @if ($latestReadings->isNotEmpty())
            <div class="overflow-x-auto">
                <table class="min-w-[40rem] w-full text-sm">
                    <thead class="table-head">
                        <tr>
                            <th class="px-6 py-3.5">Unidad</th>
                            <th class="px-4 py-3.5">Temperatura</th>
                            <th class="px-4 py-3.5">pH</th>
                            <th class="px-4 py-3.5">Fecha</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($latestReadings as $reading)
                            <tr data-cy="dashboard-latest-reading-row">
                                <td class="px-6 py-4 font-bold">{{ $reading->pond?->name }}</td>
                                <td class="px-4 py-4">{{ $reading->temperature }} °C</td>
                                <td class="px-4 py-4">{{ $reading->ph }}</td>
                                <td class="px-4 py-4 text-xs text-slate-500">{{ $reading->recorded_at }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <x-empty-state data-cy="dashboard-empty-state" title="Sin lecturas disponibles" description="Los registros aparecerán cuando un dispositivo envíe mediciones." />
        @endif
    </section>

    <section class="surface-card overflow-hidden">
        <div class="border-b border-slate-100 px-5 py-5">
            <h2 class="text-lg font-extrabold">Incidencias pendientes</h2>
        </div>
        @if ($pendingIncidents->isNotEmpty())
            <ul class="divide-y divide-slate-100">
                @foreach ($pendingIncidents as $incident)
                    <li class="px-5 py-4">
                        <a href="{{ route('incidents.show', $incident) }}" class="font-bold text-cyan-800 hover:text-cyan-950">{{ $incident->title }}</a>
                        <p class="mt-1 text-xs text-slate-400">{{ $incident->alert?->pond?->name }} · {{ $incident->status }}</p>
                    </li>
                @endforeach
            </ul>
        @else
            <x-empty-state data-cy="dashboard-empty-state" title="Sin incidencias pendientes" description="No hay incidencias abiertas, asignadas o en progreso." />
        @endif
    </section>
</div>

<section class="mt-8 surface-card overflow-hidden" data-cy="dashboard-active-alerts">
    <div class="border-b border-slate-100 px-5 py-5">
        <h2 class="text-lg font-extrabold">Alertas recientes</h2>
    </div>
    @if ($recentActiveAlerts->isNotEmpty())
        <div class="divide-y divide-slate-100">
            @foreach ($recentActiveAlerts as $alert)
                <a href="{{ route('ponds.show', $alert->pond) }}" class="block px-5 py-4 hover:bg-amber-50/50">
                    <p class="font-bold">{{ $alert->pond?->name }}</p>
                    <p class="text-sm text-amber-800">{{ $alert->message }}</p>
                </a>
            @endforeach
        </div>
    @else
        <x-empty-state data-cy="dashboard-empty-state" title="Operación sin alertas" description="Todos los parámetros monitoreados se encuentran sin alertas activas." />
    @endif
</section>

@if ($plan)
    <section class="mt-8 surface-card p-5 sm:p-6" aria-label="Plan actual">
        <p class="text-[0.625rem] font-bold uppercase tracking-[0.18em] text-slate-400">Plan</p>
        <h2 class="mt-1 text-lg font-extrabold">{{ $plan->name }}</h2>
        <dl class="mt-4 grid gap-3 sm:grid-cols-3">
            <div class="rounded-2xl bg-slate-50 px-4 py-3">
                <dt class="text-xs font-semibold text-slate-500">{{ $account->unitsLabel() }}</dt>
                <dd class="mt-1 text-lg font-extrabold">{{ $usage['units']['current'] }} / {{ $usage['units']['max'] }}</dd>
            </div>
            <div class="rounded-2xl bg-slate-50 px-4 py-3">
                <dt class="text-xs font-semibold text-slate-500">Dispositivos</dt>
                <dd class="mt-1 text-lg font-extrabold">{{ $usage['devices']['current'] }} / {{ $usage['devices']['max'] }}</dd>
            </div>
            <div class="rounded-2xl bg-slate-50 px-4 py-3">
                <dt class="text-xs font-semibold text-slate-500">Usuarios</dt>
                <dd class="mt-1 text-lg font-extrabold">{{ $usage['users']['current'] }} / {{ $usage['users']['max'] }}</dd>
            </div>
        </dl>
    </section>
@endif
