@extends('layouts.app')

@section('title', $incident->title.' | Aqualytics')
@section('header-title', 'Detalle de incidencia')

@section('content')
    <x-page-header
        eyebrow="Incidencia #{{ $incident->id }}"
        :title="$incident->title"
        :description="$incident->description"
        :back-url="route('incidents.index')"
        back-label="Volver a incidencias"
    >
        <x-slot:actions>
            <x-status-badge :status="$incident->status" data-cy="incident-status" />
        </x-slot:actions>
    </x-page-header>

    <div class="grid gap-6 xl:grid-cols-[1.4fr_0.8fr]">
        <section class="surface-card overflow-hidden">
            <div class="border-b border-slate-100 px-5 py-5 sm:px-6">
                <h2 class="text-lg font-extrabold tracking-tight text-slate-900">Seguimiento</h2>
                <p class="mt-1 text-sm text-slate-500">Estados: abierta → asignada → en progreso → resuelta → cerrada.</p>
            </div>
            <dl class="grid gap-4 px-5 py-5 sm:grid-cols-2 sm:px-6">
                <div>
                    <dt class="text-xs font-bold uppercase tracking-wider text-slate-400">Creada por</dt>
                    <dd class="mt-1 font-semibold text-slate-700">{{ $incident->creator?->name ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-bold uppercase tracking-wider text-slate-400">Asignada a</dt>
                    <dd data-cy="incident-assignee" class="mt-1 font-semibold text-slate-700">{{ $incident->assignee?->name ?? 'Sin asignar' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-bold uppercase tracking-wider text-slate-400">Alerta original</dt>
                    <dd class="mt-1 font-semibold text-slate-700">{{ $incident->alert?->message ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-bold uppercase tracking-wider text-slate-400">Unidad</dt>
                    <dd class="mt-1 font-semibold text-slate-700">{{ $incident->alert?->pond?->name ?? '—' }}</dd>
                </div>
            </dl>

            @if ($incident->resolution)
                <div class="border-t border-slate-100 px-5 py-5 sm:px-6">
                    <h3 class="font-extrabold text-slate-900">Resolución</h3>
                    <p data-cy="incident-resolution" class="mt-2 text-sm leading-6 text-slate-600">{{ $incident->resolution }}</p>
                    @if ($incident->resolved_at)
                        <p class="mt-2 text-xs text-slate-400">Registrada {{ $incident->resolved_at }}</p>
                    @endif
                </div>
            @endif
        </section>

        <aside class="space-y-6">
            @if (
                in_array(auth()->user()->role, [
                    \App\Models\User::ROLE_ADMIN,
                    \App\Models\User::ROLE_SUPERVISOR,
                ], true)
                && ! in_array($incident->status, [
                    \App\Models\Incident::STATUS_RESOLVED,
                    \App\Models\Incident::STATUS_CLOSED,
                ], true)
            )
                <section class="surface-card p-5 sm:p-6">
                    <h3 class="font-extrabold text-slate-900">Asignar especialista</h3>
                    <form method="POST" action="{{ route('incidents.assign', $incident) }}" class="mt-4">
                        @csrf
                        <label for="specialist_id" class="form-label">Especialista</label>
                        <select id="specialist_id" name="specialist_id" data-cy="incident-specialist" required class="form-control">
                            <option value="">Selecciona un especialista</option>
                            @foreach ($specialists as $specialist)
                                <option value="{{ $specialist->id }}" @selected($incident->assigned_to === $specialist->id)>
                                    {{ $specialist->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('specialist_id')
                            <p class="form-error"><span aria-hidden="true">●</span>{{ $message }}</p>
                        @enderror
                        <button type="submit" data-cy="assign-managed-incident" class="btn-primary mt-4 w-full">
                            Asignar
                        </button>
                    </form>
                </section>
            @endif

            @if (
                auth()->user()->role === \App\Models\User::ROLE_SPECIALIST
                && $incident->isAssignedTo(auth()->user())
                && in_array($incident->status, [
                    \App\Models\Incident::STATUS_ASSIGNED,
                    \App\Models\Incident::STATUS_IN_PROGRESS,
                ], true)
            )
                @if ($incident->status === \App\Models\Incident::STATUS_ASSIGNED)
                    <form method="POST" action="{{ route('incidents.start', $incident) }}" class="surface-card p-5 sm:p-6">
                        @csrf
                        <h3 class="font-extrabold text-slate-900">Tomar incidencia</h3>
                        <p class="mt-1 text-sm text-slate-500">Pasa el trabajo a en progreso.</p>
                        <button type="submit" data-cy="start-incident" class="btn-primary mt-4 w-full">
                            Iniciar trabajo
                        </button>
                    </form>
                @endif

                <form method="POST" action="{{ route('incidents.resolve', $incident) }}" data-cy="resolve-managed-incident" class="surface-card p-5 sm:p-6">
                    @csrf
                    <h3 class="font-extrabold text-slate-900">Registrar solución</h3>
                    <label for="resolution" class="form-label mt-4">Resolución</label>
                    <textarea id="resolution" name="resolution" data-cy="incident-resolution-notes" rows="4" required
                        class="form-control resize-y">{{ old('resolution') }}</textarea>
                    @error('resolution')
                        <p class="form-error"><span aria-hidden="true">●</span>{{ $message }}</p>
                    @enderror
                    <button type="submit" class="mt-4 inline-flex w-full items-center justify-center rounded-xl bg-emerald-700 px-4 py-2.5 text-sm font-bold text-white hover:bg-emerald-800">
                        Marcar como resuelta
                    </button>
                </form>
            @endif

            @if (
                auth()->user()->role === \App\Models\User::ROLE_ADMIN
                && $incident->status === \App\Models\Incident::STATUS_RESOLVED
            )
                <form method="POST" action="{{ route('incidents.close', $incident) }}" class="surface-card p-5 sm:p-6">
                    @csrf
                    <h3 class="font-extrabold text-slate-900">Cerrar incidencia</h3>
                    <p class="mt-1 text-sm text-slate-500">Confirma que el trabajo quedó documentado.</p>
                    <button type="submit" data-cy="close-incident" class="btn-primary mt-4 w-full">
                        Cerrar
                    </button>
                </form>
            @endif
        </aside>
    </div>
@endsection
