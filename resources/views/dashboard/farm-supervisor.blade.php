<x-page-header
    eyebrow="Seguimiento operativo"
    title="Dashboard"
    description="¿Qué requiere atención? Alertas e incidencias de tu piscigranja. La gestión de usuarios no es parte de este tablero."
/>

<section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3" aria-label="Atención">
    <article class="surface-card p-5" data-cy="dashboard-active-alerts">
        <p class="text-sm font-semibold text-slate-500">Alertas activas</p>
        <p class="mt-2 text-3xl font-extrabold">{{ $activeAlertCount }}</p>
    </article>
    <article class="surface-card p-5">
        <p class="text-sm font-semibold text-slate-500">Alertas asignadas</p>
        <p class="mt-2 text-3xl font-extrabold">{{ $assignedAlertCount }}</p>
    </article>
    <article class="surface-card p-5">
        <p class="text-sm font-semibold text-slate-500">Estanques con alerta</p>
        <p class="mt-2 text-3xl font-extrabold">{{ $pondsWithActiveAlertsCount }}</p>
    </article>
    <article class="surface-card p-5">
        <p class="text-sm font-semibold text-slate-500">Incidencias abiertas</p>
        <p class="mt-2 text-3xl font-extrabold">{{ $openIncidentCount }}</p>
    </article>
    <article class="surface-card p-5">
        <p class="text-sm font-semibold text-slate-500">Incidencias asignadas</p>
        <p class="mt-2 text-3xl font-extrabold">{{ $assignedIncidentCount }}</p>
    </article>
    <article class="surface-card p-5">
        <p class="text-sm font-semibold text-slate-500">En progreso</p>
        <p class="mt-2 text-3xl font-extrabold">{{ $inProgressIncidentCount }}</p>
    </article>
</section>

<section class="mt-8 surface-card overflow-hidden">
    <div class="border-b border-slate-100 px-5 py-5">
        <h2 class="text-lg font-extrabold">Incidencias operativas</h2>
        <p class="mt-1 text-sm text-slate-500">Abiertas, asignadas o en progreso.</p>
    </div>
    @if ($operationalIncidents->isNotEmpty())
        <ul class="divide-y divide-slate-100">
            @foreach ($operationalIncidents as $incident)
                <li class="px-5 py-4">
                    <a href="{{ route('incidents.show', $incident) }}" class="font-bold text-cyan-800">{{ $incident->title }}</a>
                    <p class="mt-1 text-xs text-slate-400">{{ $incident->alert?->pond?->name }} · {{ $incident->status }}</p>
                </li>
            @endforeach
        </ul>
    @else
        <x-empty-state data-cy="dashboard-empty-state" title="Sin incidencias operativas" description="No hay incidencias abiertas, asignadas o en progreso." />
    @endif
</section>

<section class="mt-8 surface-card overflow-hidden" data-cy="dashboard-active-alerts">
    <div class="border-b border-slate-100 px-5 py-5">
        <h2 class="text-lg font-extrabold">Alertas activas</h2>
    </div>
    @if ($activeAlerts->isNotEmpty())
        <div class="divide-y divide-slate-100">
            @foreach ($activeAlerts as $alert)
                <a href="{{ route('ponds.show', $alert->pond) }}" class="block px-5 py-4 hover:bg-amber-50/40">
                    <p class="font-bold">{{ $alert->pond?->name }}</p>
                    <p class="text-sm text-amber-800">{{ $alert->message }}</p>
                </a>
            @endforeach
        </div>
    @else
        <x-empty-state data-cy="dashboard-empty-state" title="Operación sin alertas" description="No hay alertas activas en la cuenta." />
    @endif
</section>

<section class="mt-8 surface-card overflow-hidden" data-cy="dashboard-latest-readings">
    <div class="border-b border-slate-100 px-5 py-5">
        <h2 class="text-lg font-extrabold">Últimas lecturas</h2>
    </div>
    @if ($latestRelevantReadings->isNotEmpty())
        <ul class="divide-y divide-slate-100">
            @foreach ($latestRelevantReadings as $reading)
                <li data-cy="dashboard-latest-reading-row" class="px-5 py-3 text-sm">
                    <span class="font-bold">{{ $reading->pond?->name }}</span>
                    · {{ $reading->temperature }} °C · pH {{ $reading->ph }}
                </li>
            @endforeach
        </ul>
    @else
        <x-empty-state data-cy="dashboard-empty-state" title="Sin lecturas disponibles" description="Aún no hay mediciones recientes." />
    @endif
</section>
