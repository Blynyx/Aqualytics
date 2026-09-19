<x-page-header
    eyebrow="Monitoreo doméstico"
    title="Estado de tu pecera"
    description="Respuesta rápida: cómo está el agua ahora. El historial detallado sigue en la ficha de la pecera."
/>

@if ($pond)
    <section class="mb-8 grid gap-4 sm:grid-cols-2 xl:grid-cols-4" aria-label="Estado actual">
        @foreach ([
            'temperature' => ['label' => 'Temperatura', 'value' => $latestReading?->temperature, 'unit' => '°C'],
            'ph' => ['label' => 'pH', 'value' => $latestReading?->ph, 'unit' => ''],
            'turbidity' => ['label' => 'Turbidez', 'value' => $latestReading?->turbidity, 'unit' => ''],
            'water_level' => ['label' => 'Nivel', 'value' => $latestReading?->water_level, 'unit' => '%'],
        ] as $key => $metric)
            @php $state = $parameterStates[$key] ?? 'unevaluated'; @endphp
            <article class="surface-card p-5">
                <p class="text-sm font-semibold text-slate-500">{{ $metric['label'] }}</p>
                <p class="mt-2 text-3xl font-extrabold tracking-tight text-slate-950">
                    {{ $metric['value'] === null ? '—' : $metric['value'].($metric['unit'] ? ' '.$metric['unit'] : '') }}
                </p>
                <p class="mt-1 text-xs font-bold
                    {{ $state === 'ok' ? 'text-emerald-700' : ($state === 'out_of_range' ? 'text-amber-700' : 'text-slate-400') }}">
                    {{ $state === 'ok' ? 'Dentro de rango' : ($state === 'out_of_range' ? 'Fuera de rango' : 'Sin evaluar') }}
                </p>
            </article>
        @endforeach
    </section>

    <section class="mb-8 surface-card p-5 sm:p-6" aria-label="Pecera">
        <p class="text-[0.625rem] font-bold uppercase tracking-[0.18em] text-slate-400">Tu pecera</p>
        <h2 class="mt-1 text-xl font-extrabold text-slate-950">{{ $pond->name }}</h2>
        <p class="mt-1 text-sm text-slate-500">{{ $pond->code }}</p>
    </section>
@else
    <div class="mb-8 surface-card">
        <x-empty-state data-cy="dashboard-empty-state" title="Sin pecera registrada" description="Cuando registres tu pecera aparecerá aquí su estado hídrico.">
            <x-slot:action>
                <a href="{{ route('ponds.create') }}" class="btn-primary">Registrar pecera</a>
            </x-slot:action>
        </x-empty-state>
    </div>
@endif

<section class="mb-8 surface-card min-w-0 overflow-hidden" data-cy="dashboard-active-alerts">
    <div class="border-b border-slate-100 px-5 py-5 sm:px-6">
        <h2 class="text-lg font-extrabold tracking-tight text-slate-900">Alertas activas</h2>
        <p class="mt-1 text-sm text-slate-500">{{ $activeAlertCount }} alerta(s) fuera de rango.</p>
    </div>
    @if ($activeAlerts->isNotEmpty())
        <div class="divide-y divide-slate-100">
            @foreach ($activeAlerts as $alert)
                <a href="{{ route('ponds.show', $alert->pond) }}" class="flex gap-3 p-5 transition hover:bg-amber-50/50 sm:px-6">
                    <div class="min-w-0">
                        <p class="font-bold text-slate-800">{{ $alert->pond?->name }}</p>
                        <p class="mt-1 text-sm font-medium text-amber-800">{{ $alert->message }}</p>
                    </div>
                </a>
            @endforeach
        </div>
    @else
        <x-empty-state data-cy="dashboard-empty-state" title="Operación sin alertas" description="No hay alertas activas en tu pecera." />
    @endif
</section>

<section class="mb-8 grid gap-4 lg:grid-cols-2">
    <article class="surface-card p-5 sm:p-6">
        <p class="text-sm font-semibold text-slate-500">Dispositivo</p>
        @if ($device)
            <h2 class="mt-2 text-lg font-extrabold text-slate-950">{{ $device->name }}</h2>
            <p class="mt-1 text-sm text-slate-500">Estado: {{ $device->status }}</p>
            <p class="mt-1 text-sm text-slate-500">Última conexión: {{ $device->last_seen_at?->format('d/m/Y H:i') ?? 'Sin conexión registrada' }}</p>
        @else
            <x-empty-state data-cy="dashboard-empty-state" title="Sin dispositivo" description="Registra un ESP32 para ver la última conexión." />
        @endif
    </article>
    <article class="surface-card p-5 sm:p-6">
        <p class="text-sm font-semibold text-slate-500">Última lectura</p>
        @if ($latestReading)
            <p class="mt-2 text-sm text-slate-600">{{ $latestReading->recorded_at }}</p>
            @if ($historyUrl)
                <a href="{{ $historyUrl }}" class="mt-4 inline-flex font-bold text-cyan-700 hover:text-cyan-900">Ver historial gráfico</a>
            @endif
        @else
            <x-empty-state data-cy="dashboard-empty-state" title="Sin lecturas" description="Las mediciones aparecerán cuando el dispositivo envíe datos." />
        @endif
    </article>
</section>

@if ($plan)
    <section class="surface-card p-5 sm:p-6" aria-label="Plan actual">
        <p class="text-[0.625rem] font-bold uppercase tracking-[0.18em] text-slate-400">Plan</p>
        <h2 class="mt-1 text-lg font-extrabold text-slate-950">{{ $plan->name }}</h2>
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
