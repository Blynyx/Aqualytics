<x-page-header
    eyebrow="Trabajo asignado"
    title="Dashboard"
    description="¿Qué tengo que atender? Solo incidencias asignadas a ti. Las alertas generales de la granja no aparecen como trabajo propio."
/>

<section class="grid gap-4 sm:grid-cols-2" aria-label="Mi carga">
    <article class="surface-card p-5">
        <p class="text-sm font-semibold text-slate-500">Asignadas</p>
        <p class="mt-2 text-3xl font-extrabold">{{ $assignedIncidentCount }}</p>
    </article>
    <article class="surface-card p-5">
        <p class="text-sm font-semibold text-slate-500">En progreso</p>
        <p class="mt-2 text-3xl font-extrabold">{{ $inProgressIncidentCount }}</p>
    </article>
</section>

<section class="mt-8 surface-card overflow-hidden" data-cy="dashboard-my-incidents">
    <div class="border-b border-slate-100 px-5 py-5">
        <h2 class="text-lg font-extrabold">Mis incidencias</h2>
        <p class="mt-1 text-sm text-slate-500">Asignadas o en progreso.</p>
    </div>
    @if ($myActiveIncidents->isNotEmpty())
        <ul class="divide-y divide-slate-100">
            @foreach ($myActiveIncidents as $incident)
                <li class="px-5 py-4">
                    <a href="{{ route('incidents.show', $incident) }}" class="font-bold text-cyan-800">{{ $incident->title }}</a>
                    <p class="mt-1 text-xs text-slate-400">{{ $incident->alert?->pond?->name }} · {{ $incident->status }}</p>
                </li>
            @endforeach
        </ul>
    @else
        <x-empty-state data-cy="dashboard-empty-state" title="Sin incidencias asignadas" description="Cuando te asignen trabajo aparecerá aquí." />
    @endif
</section>

<section class="mt-8">
    <h2 class="mb-4 text-lg font-extrabold">Unidades relacionadas</h2>
    @if ($relatedPonds->isNotEmpty())
        <div class="grid gap-4 md:grid-cols-2">
            @foreach ($relatedPonds as $pond)
                <a href="{{ route('ponds.show', $pond) }}" class="surface-card block p-5">
                    <h3 class="font-bold">{{ $pond->name }}</h3>
                    <p class="mt-1 text-xs uppercase tracking-wider text-slate-400">{{ $pond->code }}</p>
                </a>
            @endforeach
        </div>
    @else
        <div class="surface-card">
            <x-empty-state data-cy="dashboard-empty-state" title="Sin unidades asociadas" description="Las peceras o estanques de tus incidencias se listarán aquí." />
        </div>
    @endif
</section>

<section class="mt-8 surface-card overflow-hidden">
    <div class="border-b border-slate-100 px-5 py-5">
        <h2 class="text-lg font-extrabold">Resueltas recientemente</h2>
    </div>
    @if ($recentResolvedIncidents->isNotEmpty())
        <ul class="divide-y divide-slate-100">
            @foreach ($recentResolvedIncidents as $incident)
                <li class="px-5 py-4">
                    <a href="{{ route('incidents.show', $incident) }}" class="font-semibold text-slate-800">{{ $incident->title }}</a>
                </li>
            @endforeach
        </ul>
    @else
        <x-empty-state data-cy="dashboard-empty-state" title="Sin resoluciones recientes" description="Tus incidencias resueltas aparecerán aquí." />
    @endif
</section>
