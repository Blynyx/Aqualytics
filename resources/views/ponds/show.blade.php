@extends('layouts.app')

@section('title', $pond->name.' | Aqualytics')
@section('header-title', 'Detalle del estanque')

@section('content')
    @php
        $metricCards = [
            [
                'label' => 'Temperatura',
                'value' => $latestReading?->temperature,
                'unit' => '°C',
                'min' => $pond->threshold?->temperature_min,
                'max' => $pond->threshold?->temperature_max,
                'icon' => 'temperature',
            ],
            [
                'label' => 'pH',
                'value' => $latestReading?->ph,
                'unit' => '',
                'min' => $pond->threshold?->ph_min,
                'max' => $pond->threshold?->ph_max,
                'icon' => 'ph',
            ],
            [
                'label' => 'Turbidez',
                'value' => $latestReading?->turbidity,
                'unit' => 'NTU',
                'min' => null,
                'max' => $pond->threshold?->turbidity_max,
                'icon' => 'turbidity',
            ],
            [
                'label' => 'Nivel del agua',
                'value' => $latestReading?->water_level,
                'unit' => '%',
                'min' => $pond->threshold?->water_level_min,
                'max' => $pond->threshold?->water_level_max,
                'icon' => 'level',
            ],
        ];
    @endphp

    <x-page-header
        eyebrow="Monitoreo de estanque"
        :title="$pond->name"
        :description="($pond->species ?: 'Especie no definida').' · '.($pond->location ?: 'Ubicación no definida')"
        :back-url="route('ponds.index')"
        back-label="Volver a estanques"
    >
        <x-slot:actions>
            <div class="flex items-center gap-3">
                <span class="rounded-xl border border-slate-200 bg-white px-3 py-2 font-mono text-xs font-bold text-slate-500 shadow-sm">
                    {{ $pond->code }}
                </span>
                <x-status-badge :status="$pond->status" />
            </div>
        </x-slot:actions>
    </x-page-header>

    <section aria-labelledby="latest-metrics-title">
        <div class="mb-4 flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h2 id="latest-metrics-title" class="text-lg font-extrabold tracking-tight text-slate-900">Últimas métricas</h2>
                <p class="mt-1 text-sm text-slate-500">Estado más reciente reportado por los sensores del estanque.</p>
            </div>
            @if ($latestReading)
                <p class="text-xs font-medium text-slate-400">Actualizado {{ $latestReading->recorded_at }}</p>
            @endif
        </div>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ($metricCards as $metric)
                @php
                    $hasValue = $metric['value'] !== null;
                    $outsideRange = $hasValue && (
                        ($metric['min'] !== null && $metric['value'] < $metric['min'])
                        || ($metric['max'] !== null && $metric['value'] > $metric['max'])
                    );
                    $stateClasses = ! $hasValue
                        ? 'bg-slate-100 text-slate-500'
                        : ($outsideRange ? 'bg-red-50 text-red-700' : 'bg-emerald-50 text-emerald-700');
                    $stateLabel = ! $hasValue ? 'Sin datos' : ($outsideRange ? 'Fuera de rango' : 'En rango');
                @endphp
                <article class="surface-card p-5">
                    <div class="flex items-start justify-between gap-4">
                        <span class="grid size-10 place-items-center rounded-xl {{ $stateClasses }}">
                            @if ($metric['icon'] === 'temperature')
                                <svg class="size-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="M10 14.8V6a2 2 0 1 1 4 0v8.8a4 4 0 1 1-4 0Z" stroke="currentColor" stroke-width="1.7"/>
                                    <path d="M12 11v6" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
                                </svg>
                            @elseif ($metric['icon'] === 'ph')
                                <svg class="size-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="M12 3s6 6.5 6 11a6 6 0 1 1-12 0c0-4.5 6-11 6-11Z" stroke="currentColor" stroke-width="1.7"/>
                                    <path d="M9.5 15.5c.7.7 1.5 1 2.5 1" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
                                </svg>
                            @elseif ($metric['icon'] === 'turbidity')
                                <svg class="size-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="M12 3s6 6.5 6 11a6 6 0 1 1-12 0c0-4.5 6-11 6-11Z" stroke="currentColor" stroke-width="1.7"/>
                                    <path d="M9 13h6M10 16h4" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
                                </svg>
                            @else
                                <svg class="size-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="M4 16c2 0 2 1.5 4 1.5s2-1.5 4-1.5 2 1.5 4 1.5 2-1.5 4-1.5M4 11c2 0 2 1.5 4 1.5s2-1.5 4-1.5 2 1.5 4 1.5 2-1.5 4-1.5M7 7h10" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
                                </svg>
                            @endif
                        </span>
                        <span class="rounded-full px-2 py-1 text-[0.625rem] font-bold uppercase tracking-wider {{ $stateClasses }}">
                            {{ $stateLabel }}
                        </span>
                    </div>
                    <p class="mt-4 text-sm font-semibold text-slate-500">{{ $metric['label'] }}</p>
                    <p class="mt-1 text-2xl font-extrabold tracking-tight text-slate-950">
                        @if ($hasValue)
                            {{ $metric['value'] }} <span class="text-sm font-bold text-slate-400">{{ $metric['unit'] }}</span>
                        @else
                            <span class="text-lg text-slate-400">Sin datos</span>
                        @endif
                    </p>
                    <p class="mt-2 text-xs text-slate-400">
                        Rango: {{ $metric['min'] ?? '—' }} a {{ $metric['max'] ?? '—' }} {{ $metric['unit'] }}
                    </p>
                </article>
            @endforeach
        </div>
    </section>

    @php
        $isHomeAccount = auth()->user()->fishFarm->isHome();
        $historyTitle = $isHomeAccount ? 'Historial de mi pecera' : 'Historial del estanque';
        $historyThresholds = [
            'temperature' => [
                'min' => $pond->threshold?->temperature_min,
                'max' => $pond->threshold?->temperature_max,
                'unit' => '°C',
            ],
            'ph' => [
                'min' => $pond->threshold?->ph_min,
                'max' => $pond->threshold?->ph_max,
                'unit' => '',
            ],
            'turbidity' => [
                'min' => null,
                'max' => $pond->threshold?->turbidity_max,
                'unit' => 'NTU',
            ],
            'water_level' => [
                'min' => $pond->threshold?->water_level_min,
                'max' => $pond->threshold?->water_level_max,
                'unit' => '%',
            ],
        ];
        $historyRangeLabels = [
            '24h' => 'Últimas 24 h',
            '7d' => '7 días',
            '30d' => '30 días',
            '90d' => '90 días',
        ];
    @endphp

    <section
        class="surface-card mt-8 overflow-hidden"
        aria-labelledby="parameter-history-title"
        data-cy="parameter-history"
        data-pond-history
        data-history-url="{{ route('ponds.readings.history', $pond) }}"
        data-thresholds="{{ json_encode($historyThresholds) }}"
    >
        <div class="flex flex-col gap-4 border-b border-slate-100 px-5 py-5 sm:flex-row sm:items-start sm:justify-between sm:px-6">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-slate-400">Historial de parámetros</p>
                <h2 id="parameter-history-title" class="mt-1 text-lg font-extrabold tracking-tight text-slate-900">{{ $historyTitle }}</h2>
                <p class="mt-1 text-sm text-slate-500">Evolución temporal de temperatura, pH, turbidez y nivel del agua.</p>
            </div>
            <div class="flex flex-wrap gap-2" role="group" aria-label="Periodo del historial">
                @foreach ($allowedHistoryRanges as $range)
                    <button
                        type="button"
                        data-cy="history-range-{{ $range }}"
                        data-history-range="{{ $range }}"
                        class="rounded-xl border px-3 py-2 text-xs font-bold transition {{ $range === '24h' ? 'border-slate-900 bg-slate-900 text-white' : 'border-slate-200 bg-white text-slate-600 hover:border-slate-300 hover:bg-slate-50' }}"
                    >
                        {{ $historyRangeLabels[$range] }}
                    </button>
                @endforeach
            </div>
        </div>

        <div class="p-5 sm:p-6">
            <p data-cy="history-status" data-history-status class="mb-4 hidden text-sm font-semibold text-slate-500"></p>
            <p data-cy="history-empty" data-history-empty class="hidden rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-4 py-8 text-center text-sm font-semibold text-slate-500">
                No hay lecturas disponibles para este periodo.
            </p>
            <p data-cy="history-error" data-history-error class="hidden rounded-2xl border border-red-200 bg-red-50 px-4 py-8 text-center text-sm font-semibold text-red-700">
                No se pudo cargar el historial.
            </p>
            <div data-history-charts class="grid gap-4 md:grid-cols-2">
                @foreach ([
                    ['key' => 'temperature', 'label' => 'Temperatura', 'unit' => '°C'],
                    ['key' => 'ph', 'label' => 'pH', 'unit' => ''],
                    ['key' => 'turbidity', 'label' => 'Turbidez', 'unit' => 'NTU'],
                    ['key' => 'water_level', 'label' => 'Nivel del agua', 'unit' => '%'],
                ] as $chart)
                    <article class="rounded-2xl border border-slate-200 bg-white p-4">
                        <div class="mb-3 flex items-baseline justify-between gap-3">
                            <h3 class="text-sm font-extrabold text-slate-800">{{ $chart['label'] }}</h3>
                            @if ($chart['unit'] !== '')
                                <span class="text-xs font-bold text-slate-400">{{ $chart['unit'] }}</span>
                            @endif
                        </div>
                        <div class="relative h-52">
                            <canvas data-cy="history-chart-{{ str_replace('_', '-', $chart['key']) }}" data-history-chart="{{ $chart['key'] }}" aria-label="Gráfico de {{ $chart['label'] }}"></canvas>
                        </div>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    <section class="surface-card mt-8 overflow-hidden" aria-labelledby="devices-title">
        <div class="flex flex-col gap-3 border-b border-slate-100 px-5 py-5 sm:flex-row sm:items-center sm:justify-between sm:px-6">
            <div>
                <h2 id="devices-title" class="text-lg font-extrabold tracking-tight text-slate-900">Dispositivos IoT</h2>
                <p class="mt-1 text-sm text-slate-500">Sensores ESP32 vinculados a esta unidad de producción.</p>
            </div>
            <span class="self-start rounded-lg bg-blue-50 px-2.5 py-1 text-xs font-bold text-blue-700 sm:self-auto">
                {{ $pond->devices->count() }} {{ $pond->devices->count() === 1 ? 'dispositivo' : 'dispositivos' }}
            </span>
        </div>

        <div class="p-5 sm:p-6">
            @if ($pond->devices->isNotEmpty())
                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    @foreach ($pond->devices as $device)
                        <article data-cy="device-card" class="rounded-2xl border border-slate-200 bg-slate-50/60 p-5">
                            <div class="flex items-start justify-between gap-3">
                                <span class="grid size-10 place-items-center rounded-xl bg-blue-100 text-blue-700">
                                    <svg class="size-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                        <rect x="6" y="3" width="12" height="18" rx="3" stroke="currentColor" stroke-width="1.7"/>
                                        <path d="M9 7h6M9 11h6M10 17h4" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
                                    </svg>
                                </span>
                                <x-status-badge :status="$device->status" />
                            </div>
                            <h3 class="mt-4 font-extrabold text-slate-900">{{ $device->name }}</h3>
                            <p class="mt-1 font-mono text-xs font-semibold text-slate-500">UID: {{ $device->device_uid }}</p>
                            <div class="mt-4 border-t border-slate-200 pt-3">
                                <p class="text-xs text-slate-400">Última conexión</p>
                                <p class="mt-1 text-sm font-semibold text-slate-600">{{ $device->last_seen_at ?: 'Sin conexión registrada' }}</p>
                            </div>
                            @if (auth()->user()->role === \App\Models\User::ROLE_ADMIN)
                                <form
                                    method="POST"
                                    action="{{ route('devices.regenerate-token', $device) }}"
                                    class="mt-4 border-t border-slate-200 pt-4"
                                    onsubmit="return confirm('¿Regenerar la clave de este dispositivo? El token anterior dejará de funcionar.');"
                                >
                                    @csrf
                                    <p class="text-xs text-slate-500">Si perdiste la clave, genera una nueva. El token anterior quedará inválido.</p>
                                    <button type="submit" data-cy="regenerate-device-token" class="mt-3 inline-flex min-h-11 items-center rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs font-bold text-slate-700 transition hover:bg-slate-50">
                                        Regenerar clave
                                    </button>
                                </form>
                            @endif
                        </article>
                    @endforeach
                </div>
            @else
                <div class="rounded-2xl border border-dashed border-slate-300">
                    <x-empty-state title="Sin dispositivos conectados" description="Vincula un ESP32 para comenzar a recibir métricas del estanque." />
                </div>
            @endif

            @if (auth()->user()->role === \App\Models\User::ROLE_ADMIN)
                <form method="POST" action="{{ route('ponds.devices.store', $pond) }}" data-cy="register-device-form" class="mt-6 rounded-2xl border border-cyan-100 bg-cyan-50/50 p-4 sm:p-5">
                    @csrf
                    <div class="mb-4 flex items-center gap-3">
                        <span class="grid size-9 place-items-center rounded-xl bg-cyan-100 text-cyan-700">
                            <svg class="size-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                            </svg>
                        </span>
                        <div>
                            <h3 class="font-extrabold text-slate-900">Registrar ESP32</h3>
                            <p class="text-xs text-slate-500">Asocia un nuevo dispositivo de monitoreo.</p>
                        </div>
                    </div>
                    <div class="grid gap-4 lg:grid-cols-[1fr_1fr_auto] lg:items-start">
                        <div>
                            <label for="device_name" class="form-label">Nombre del dispositivo</label>
                            <input id="device_name" name="name" data-cy="device-name" value="{{ old('name') }}" required
                                placeholder="Ej. Sensor principal" class="form-control">
                            @error('name')
                                <p class="form-error"><span aria-hidden="true">●</span>{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="device_uid" class="form-label">Device UID</label>
                            <input id="device_uid" name="device_uid" data-cy="device-uid" value="{{ old('device_uid') }}" required
                                placeholder="Ej. ESP32-0001" class="form-control font-mono">
                            @error('device_uid')
                                <p class="form-error"><span aria-hidden="true">●</span>{{ $message }}</p>
                            @enderror
                        </div>
                        <button type="submit" data-cy="submit-device" class="btn-primary mt-0 w-full lg:mt-7 lg:w-auto">
                            Registrar ESP32
                        </button>
                    </div>
                </form>
            @endif
        </div>
    </section>

    <section class="surface-card mt-8 overflow-hidden" aria-labelledby="thresholds-title">
        <div class="border-b border-slate-100 px-5 py-5 sm:px-6">
            <h2 id="thresholds-title" class="text-lg font-extrabold tracking-tight text-slate-900">Rangos hídricos</h2>
            <p class="mt-1 text-sm text-slate-500">Límites operativos utilizados para evaluar automáticamente las lecturas.</p>
        </div>

        <div class="grid gap-4 p-5 sm:grid-cols-2 sm:p-6 xl:grid-cols-4">
            @foreach ([
                ['label' => 'Temperatura', 'value' => ($pond->threshold?->temperature_min ?? '—').' – '.($pond->threshold?->temperature_max ?? '—'), 'unit' => '°C'],
                ['label' => 'pH', 'value' => ($pond->threshold?->ph_min ?? '—').' – '.($pond->threshold?->ph_max ?? '—'), 'unit' => ''],
                ['label' => 'Turbidez máxima', 'value' => $pond->threshold?->turbidity_max ?? '—', 'unit' => 'NTU'],
                ['label' => 'Nivel del agua', 'value' => ($pond->threshold?->water_level_min ?? '—').' – '.($pond->threshold?->water_level_max ?? '—'), 'unit' => '%'],
            ] as $range)
                <div class="rounded-2xl border border-slate-200 bg-slate-50/70 p-4">
                    <p class="text-xs font-bold uppercase tracking-wider text-slate-400">{{ $range['label'] }}</p>
                    <p class="mt-2 text-lg font-extrabold text-slate-800">{{ $range['value'] }} <span class="text-xs text-slate-400">{{ $range['unit'] }}</span></p>
                </div>
            @endforeach
        </div>

        @if (auth()->user()->role === \App\Models\User::ROLE_ADMIN)
            <form method="POST" action="{{ route('ponds.thresholds.store', $pond) }}" data-cy="threshold-form" class="border-t border-slate-100 bg-slate-50/60 px-5 py-6 sm:px-6">
                @csrf
                <div class="mb-5">
                    <h3 class="font-extrabold text-slate-900">Editar configuración</h3>
                    <p class="mt-1 text-sm text-slate-500">Deja un campo vacío cuando no quieras aplicar ese límite.</p>
                </div>

                <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-4">
                    @foreach ([
                        'temperature_min' => ['Temperatura mínima', '°C'],
                        'temperature_max' => ['Temperatura máxima', '°C'],
                        'ph_min' => ['pH mínimo', ''],
                        'ph_max' => ['pH máximo', ''],
                        'turbidity_max' => ['Turbidez máxima', 'NTU'],
                        'water_level_min' => ['Nivel mínimo', '%'],
                        'water_level_max' => ['Nivel máximo', '%'],
                    ] as $field => [$label, $unit])
                        <div>
                            <label for="{{ $field }}" class="form-label">{{ $label }}</label>
                            <div class="relative">
                                <input id="{{ $field }}" name="{{ $field }}" data-cy="{{ str_replace('_', '-', $field) }}" type="number" step="0.01"
                                    value="{{ old($field, $pond->threshold?->{$field}) }}" class="form-control {{ $unit ? 'pr-14' : '' }}">
                                @if ($unit)
                                    <span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-xs font-bold text-slate-400">{{ $unit }}</span>
                                @endif
                            </div>
                            @error($field)
                                <p data-cy="{{ str_replace('_', '-', $field) }}-error" class="form-error">
                                    <span aria-hidden="true">●</span>{{ $message }}
                                </p>
                            @enderror
                        </div>
                    @endforeach

                    <div class="flex items-end md:col-span-2 xl:col-span-1">
                        <button type="submit" data-cy="submit-thresholds" class="btn-primary w-full">
                            Guardar rangos
                        </button>
                    </div>
                </div>
            </form>
        @endif
    </section>

    <section class="surface-card mt-8 overflow-hidden" aria-labelledby="readings-title">
        <div class="border-b border-slate-100 px-5 py-5 sm:px-6">
            <h2 id="readings-title" class="text-lg font-extrabold tracking-tight text-slate-900">Lecturas recientes</h2>
            <p class="mt-1 text-sm text-slate-500">Últimos registros recibidos desde los dispositivos vinculados.</p>
        </div>
        @if ($latestReadings->isNotEmpty())
            <div class="overflow-x-auto">
                <table class="min-w-[50rem] w-full text-sm">
                    <thead class="table-head">
                        <tr>
                            <th class="px-6 py-3.5">Temperatura</th>
                            <th class="px-4 py-3.5">pH</th>
                            <th class="px-4 py-3.5">Turbidez</th>
                            <th class="px-4 py-3.5">Nivel</th>
                            <th class="px-4 py-3.5">Dispositivo</th>
                            <th class="px-6 py-3.5 text-right">Fecha</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($latestReadings as $reading)
                            <tr class="transition hover:bg-slate-50/80">
                                <td class="px-6 py-4 font-semibold text-slate-700">{{ $reading->temperature }} °C</td>
                                <td class="px-4 py-4 font-semibold text-slate-700">{{ $reading->ph }}</td>
                                <td class="px-4 py-4 font-semibold text-slate-700">{{ $reading->turbidity }} NTU</td>
                                <td class="px-4 py-4 font-semibold text-slate-700">{{ $reading->water_level }} %</td>
                                <td class="px-4 py-4 text-slate-600">{{ $reading->device->name }}</td>
                                <td class="whitespace-nowrap px-6 py-4 text-right text-xs text-slate-500">{{ $reading->recorded_at }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <x-empty-state title="Sin lecturas disponibles" description="Las mediciones aparecerán aquí cuando un dispositivo comience a reportar." />
        @endif
    </section>

    <section class="mt-8" aria-labelledby="alerts-title">
        <div class="mb-4">
            <h2 id="alerts-title" class="text-lg font-extrabold tracking-tight text-slate-900">Alertas e incidencias</h2>
            <p class="mt-1 text-sm text-slate-500">Eventos hídricos activos que requieren revisión o acción correctiva.</p>
        </div>

        <div class="space-y-4">
            @forelse ($activeAlerts as $alert)
                @php
                    $isAssigned = $alert->status === 'assigned';
                    $incidentClasses = $isAssigned
                        ? 'border-blue-200 bg-blue-50/50'
                        : 'border-amber-200 bg-amber-50/50';
                    $incidentAccent = $isAssigned ? 'bg-blue-600 text-white' : 'bg-amber-500 text-white';
                @endphp
                <article data-cy="active-alert" class="overflow-hidden rounded-2xl border {{ $incidentClasses }} shadow-sm">
                    <div class="p-5 sm:p-6">
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                            <div class="flex min-w-0 gap-4">
                                <span class="grid size-11 shrink-0 place-items-center rounded-xl {{ $incidentAccent }}">
                                    <svg class="size-6" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                        <path d="M12 8v5m0 3h.01M10.3 4.8 3.2 17.1A2 2 0 0 0 4.9 20h14.2a2 2 0 0 0 1.7-2.9L13.7 4.8a2 2 0 0 0-3.4 0Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                                    </svg>
                                </span>
                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <p class="text-xs font-bold uppercase tracking-[0.15em] {{ $isAssigned ? 'text-blue-700' : 'text-amber-700' }}">
                                            Incidencia {{ $alert->parameter }}
                                        </p>
                                        <x-status-badge :status="$alert->status" :raw="true" data-cy="alert-status" />
                                    </div>
                                    <h3 class="mt-2 text-base font-extrabold text-slate-900">{{ $alert->message }}</h3>
                                    <dl class="mt-4 grid gap-3 text-sm sm:grid-cols-3">
                                        <div>
                                            <dt class="text-xs font-semibold text-slate-400">Valor detectado</dt>
                                            <dd class="mt-1 font-bold text-slate-700">{{ $alert->value }}</dd>
                                        </div>
                                        <div>
                                            <dt class="text-xs font-semibold text-slate-400">Rango configurado</dt>
                                            <dd class="mt-1 font-bold text-slate-700">
                                                {{ $alert->min_threshold ?? '—' }} – {{ $alert->max_threshold ?? '—' }}
                                            </dd>
                                        </div>
                                        <div>
                                            <dt class="text-xs font-semibold text-slate-400">Detectada</dt>
                                            <dd class="mt-1 font-bold text-slate-700">{{ $alert->detected_at }}</dd>
                                        </div>
                                    </dl>
                                    @if ($alert->assignedTo)
                                        <p data-cy="assigned-specialist" class="mt-4 inline-flex items-center gap-2 rounded-xl bg-white/80 px-3 py-2 text-sm font-bold text-blue-800 shadow-sm">
                                            <svg class="size-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                                <path d="M12 13a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm7 7c0-3.3-3.1-5-7-5s-7 1.7-7 5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
                                            </svg>
                                            Especialista asignado: {{ $alert->assignedTo->name }}
                                        </p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    @if (
                        $alert->incident === null
                        && in_array(auth()->user()->role, [
                            \App\Models\User::ROLE_ADMIN,
                            \App\Models\User::ROLE_SUPERVISOR,
                        ], true)
                    )
                        <form method="POST" action="{{ route('incidents.store', $alert) }}" data-cy="create-incident-form" class="border-t {{ $isAssigned ? 'border-blue-200/70' : 'border-amber-200/70' }} bg-white/65 p-5 sm:p-6">
                            @csrf
                            <div class="mb-4">
                                <h4 class="font-extrabold text-slate-900">Abrir incidencia gestionable</h4>
                                <p class="mt-1 text-sm text-slate-500">Convierte esta alerta IoT en una incidencia para asignar y documentar la solución.</p>
                            </div>
                            <div class="grid gap-4">
                                <div>
                                    <label for="incident_title_{{ $alert->id }}" class="form-label">Título</label>
                                    <input id="incident_title_{{ $alert->id }}" name="title" data-cy="incident-title" required
                                        value="{{ old('title', $alert->message) }}" class="form-control">
                                    @error('title')
                                        <p class="form-error"><span aria-hidden="true">●</span>{{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <label for="incident_description_{{ $alert->id }}" class="form-label">Descripción</label>
                                    <textarea id="incident_description_{{ $alert->id }}" name="description" data-cy="incident-description" rows="3" required
                                        class="form-control resize-y">{{ old('description', 'Alerta automática de '.$alert->parameter.' con valor '.$alert->value.'.') }}</textarea>
                                    @error('description')
                                        <p class="form-error"><span aria-hidden="true">●</span>{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                            <div class="mt-4 flex justify-end">
                                <button type="submit" data-cy="create-incident" class="btn-primary">
                                    Crear incidencia
                                </button>
                            </div>
                        </form>
                    @elseif ($alert->incident)
                        <div class="border-t {{ $isAssigned ? 'border-blue-200/70' : 'border-amber-200/70' }} bg-white/65 px-5 py-4 sm:px-6">
                            <a href="{{ route('incidents.show', $alert->incident) }}" data-cy="open-incident" class="text-sm font-bold text-cyan-700 hover:text-cyan-900">
                                Ver incidencia #{{ $alert->incident->id }}
                            </a>
                        </div>
                    @endif

                    @if (
                        $alert->status === 'active'
                        && in_array(auth()->user()->role, [
                            \App\Models\User::ROLE_ADMIN,
                            \App\Models\User::ROLE_SUPERVISOR,
                        ], true)
                    )
                        <form method="POST" action="{{ route('alerts.assign', $alert) }}" class="border-t border-amber-200/70 bg-white/65 p-5 sm:p-6">
                            @csrf
                            <div class="mb-4">
                                <h4 class="font-extrabold text-slate-900">Asignar especialista</h4>
                                <p class="mt-1 text-sm text-slate-500">Selecciona quién dará seguimiento a esta incidencia.</p>
                            </div>
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-start">
                                <div class="min-w-0 flex-1">
                                    <label for="specialist_{{ $alert->id }}" class="sr-only">Especialista responsable</label>
                                    <select id="specialist_{{ $alert->id }}" name="specialist_id" data-cy="specialist-select" required class="form-control">
                                        <option value="">Selecciona un especialista</option>
                                        @foreach ($specialists as $specialist)
                                            <option value="{{ $specialist->id }}">{{ $specialist->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('specialist_id')
                                        <p class="form-error"><span aria-hidden="true">●</span>{{ $message }}</p>
                                    @enderror
                                </div>
                                <button type="submit" data-cy="assign-incident" class="btn-primary shrink-0">
                                    Asignar incidencia
                                </button>
                            </div>
                        </form>
                    @endif

                    @if (
                        auth()->user()->role === \App\Models\User::ROLE_ADMIN
                        || (
                            auth()->user()->role === \App\Models\User::ROLE_SPECIALIST
                            && $alert->status === 'assigned'
                            && $alert->assigned_to_user_id === auth()->id()
                        )
                    )
                        <form method="POST" action="{{ route('alerts.resolve', $alert) }}" data-cy="resolution-form" class="border-t {{ $isAssigned ? 'border-blue-200/70' : 'border-amber-200/70' }} bg-white/65 p-5 sm:p-6">
                            @csrf
                            <div class="mb-4">
                                <h4 class="font-extrabold text-slate-900">Registrar acción correctiva</h4>
                                <p class="mt-1 text-sm text-slate-500">Documenta la intervención realizada antes de resolver la incidencia.</p>
                            </div>
                            <label for="resolution_notes_{{ $alert->id }}" class="form-label">Acción correctiva realizada</label>
                            <textarea id="resolution_notes_{{ $alert->id }}" name="resolution_notes" data-cy="resolution-notes" rows="3" required
                                placeholder="Describe de forma clara la acción ejecutada..." class="form-control resize-y">{{ old('resolution_notes') }}</textarea>
                            @error('resolution_notes')
                                <p class="form-error"><span aria-hidden="true">●</span>{{ $message }}</p>
                            @enderror
                            <div class="mt-4 flex justify-end">
                                <button type="submit" data-cy="resolve-incident" class="inline-flex items-center justify-center gap-2 rounded-xl bg-emerald-700 px-4 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-emerald-800 focus-visible:ring-emerald-600">
                                    <svg class="size-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                        <path d="m5 12 4 4L19 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                    Resolver incidencia
                                </button>
                            </div>
                        </form>
                    @endif
                </article>
            @empty
                <div class="surface-card">
                    <div data-cy="no-active-alerts">
                        <x-empty-state title="Sin alertas activas" description="No hay incidencias pendientes para este estanque." />
                    </div>
                </div>
            @endforelse
        </div>
    </section>

    <section data-cy="incident-history" class="surface-card mt-8 overflow-hidden" aria-labelledby="history-title">
        <div class="border-b border-slate-100 px-5 py-5 sm:px-6">
            <h2 id="history-title" class="text-lg font-extrabold tracking-tight text-slate-900">Historial de incidencias</h2>
            <p class="mt-1 text-sm text-slate-500">Registro de eventos resueltos y acciones correctivas aplicadas.</p>
        </div>
        @if ($resolvedAlerts->isNotEmpty())
            <div class="overflow-x-auto">
                <table class="min-w-[62rem] w-full text-sm">
                    <thead class="table-head">
                        <tr>
                            <th class="px-6 py-3.5">Incidencia</th>
                            <th class="px-4 py-3.5">Responsable</th>
                            <th class="px-4 py-3.5">Acción correctiva</th>
                            <th class="px-6 py-3.5 text-right">Resolución</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($resolvedAlerts as $alert)
                            <tr data-cy="resolved-incident" class="align-top transition hover:bg-slate-50/80">
                                <td class="px-6 py-4">
                                    <div class="flex items-start gap-3">
                                        <span class="mt-0.5 grid size-8 shrink-0 place-items-center rounded-lg bg-emerald-50 text-emerald-700">
                                            <svg class="size-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                                <path d="m5 12 4 4L19 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                            </svg>
                                        </span>
                                        <div>
                                            <p class="font-bold text-slate-800">{{ $alert->message }}</p>
                                            <p class="mt-1 text-xs font-semibold uppercase tracking-wider text-slate-400">{{ $alert->parameter }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-4 font-semibold text-slate-600">
                                    {{ $alert->resolvedBy?->name ?? $alert->assignedTo?->name ?? '—' }}
                                </td>
                                <td data-cy="resolution-history-notes" class="max-w-md px-4 py-4 leading-6 text-slate-600">
                                    {{ $alert->resolution_notes ?: '—' }}
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-right text-xs text-slate-500">{{ $alert->resolved_at }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <x-empty-state title="Sin incidencias resueltas" description="El historial se completará conforme el equipo cierre alertas y documente acciones." />
        @endif
    </section>
@endsection

@push('scripts')
    @vite('resources/js/pond-history.js')
@endpush
