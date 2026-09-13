@extends('layouts.app')

@section('title', $pond->name.' | Aqualytics')

@section('content')
    <div class="mb-8">
        <a href="{{ route('ponds.index') }}" class="text-sm font-medium text-cyan-700 hover:underline">
            ← Volver a estanques
        </a>
        <div class="mt-3 flex flex-wrap items-center justify-between gap-4">
            <div>
                <div class="flex items-center gap-3">
                    <h1 class="text-3xl font-bold">{{ $pond->name }}</h1>
                    <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold uppercase text-emerald-700">
                        {{ $pond->status }}
                    </span>
                </div>
                <p class="mt-2 text-slate-500">
                    {{ $pond->species ?: 'Especie no definida' }}
                    <span class="mx-2">|</span>
                    {{ $pond->location ?: 'Ubicación no definida' }}
                </p>
                <p class="mt-1 text-sm text-slate-400">Código: {{ $pond->code }}</p>
            </div>
        </div>
    </div>

    <section class="grid gap-6 lg:grid-cols-2">
        <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold">Condiciones actuales</h2>

            @if ($latestReading)
                <dl class="mt-5 grid grid-cols-2 gap-4">
                    <div class="rounded-xl bg-slate-50 p-4">
                        <dt class="text-sm text-slate-500">Temperatura</dt>
                        <dd class="mt-1 text-2xl font-bold">{{ $latestReading->temperature }} °C</dd>
                    </div>
                    <div class="rounded-xl bg-slate-50 p-4">
                        <dt class="text-sm text-slate-500">pH</dt>
                        <dd class="mt-1 text-2xl font-bold">{{ $latestReading->ph }}</dd>
                    </div>
                    <div class="rounded-xl bg-slate-50 p-4">
                        <dt class="text-sm text-slate-500">Turbidez</dt>
                        <dd class="mt-1 text-2xl font-bold">{{ $latestReading->turbidity }}</dd>
                    </div>
                    <div class="rounded-xl bg-slate-50 p-4">
                        <dt class="text-sm text-slate-500">Nivel de agua</dt>
                        <dd class="mt-1 text-2xl font-bold">{{ $latestReading->water_level }} %</dd>
                    </div>
                </dl>
            @else
                <p class="mt-5 text-sm text-slate-500">Aún no hay lecturas para este estanque.</p>
            @endif
        </article>

        <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold">Rangos configurados</h2>
            <dl class="mt-5 space-y-3 text-sm">
                <div class="flex justify-between border-b border-slate-100 pb-3">
                    <dt class="text-slate-500">Temperatura</dt>
                    <dd class="font-medium">{{ $pond->threshold?->temperature_min ?? '—' }} - {{ $pond->threshold?->temperature_max ?? '—' }}</dd>
                </div>
                <div class="flex justify-between border-b border-slate-100 pb-3">
                    <dt class="text-slate-500">pH</dt>
                    <dd class="font-medium">{{ $pond->threshold?->ph_min ?? '—' }} - {{ $pond->threshold?->ph_max ?? '—' }}</dd>
                </div>
                <div class="flex justify-between border-b border-slate-100 pb-3">
                    <dt class="text-slate-500">Turbidez máxima</dt>
                    <dd class="font-medium">{{ $pond->threshold?->turbidity_max ?? '—' }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-slate-500">Nivel de agua</dt>
                    <dd class="font-medium">{{ $pond->threshold?->water_level_min ?? '—' }} - {{ $pond->threshold?->water_level_max ?? '—' }}</dd>
                </div>
            </dl>
        </article>
    </section>

    <section class="mt-8 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="text-lg font-semibold">Dispositivos ESP32</h2>

        <div class="mt-5 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            @forelse ($pond->devices as $device)
                <article class="rounded-xl border border-slate-200 p-4">
                    <div class="flex items-start justify-between gap-3">
                        <h3 class="font-semibold">{{ $device->name }}</h3>
                        <span class="rounded-full bg-emerald-100 px-2 py-1 text-xs font-semibold uppercase text-emerald-700">
                            {{ $device->status }}
                        </span>
                    </div>
                    <p class="mt-3 text-sm text-slate-500">UID: <span class="font-medium text-slate-700">{{ $device->device_uid }}</span></p>
                    <p class="mt-1 text-sm text-slate-500">Última conexión: {{ $device->last_seen_at ?: 'Sin conexión registrada' }}</p>
                </article>
            @empty
                <p class="text-sm text-slate-500">No hay dispositivos registrados.</p>
            @endforelse
        </div>

        <form method="POST" action="{{ route('ponds.devices.store', $pond) }}" class="mt-6 grid gap-4 rounded-xl bg-slate-50 p-5 md:grid-cols-[1fr_1fr_auto] md:items-end">
            @csrf
            <div>
                <label for="device_name" class="mb-2 block text-sm font-medium">Nombre del dispositivo</label>
                <input id="device_name" name="name" value="{{ old('name') }}" required
                    class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 focus:border-cyan-600 focus:ring-2 focus:ring-cyan-100">
                @error('name')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="device_uid" class="mb-2 block text-sm font-medium">Device UID</label>
                <input id="device_uid" name="device_uid" value="{{ old('device_uid') }}" required
                    class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 focus:border-cyan-600 focus:ring-2 focus:ring-cyan-100">
                @error('device_uid')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
            <button type="submit" class="rounded-lg bg-cyan-700 px-5 py-2.5 font-semibold text-white hover:bg-cyan-600">
                Registrar ESP32
            </button>
        </form>
    </section>

    <section class="mt-8 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="text-lg font-semibold">Configuración hídrica</h2>

        <form method="POST" action="{{ route('ponds.thresholds.store', $pond) }}" class="mt-5 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @csrf
            @foreach ([
                'temperature_min' => 'Temperatura mínima',
                'temperature_max' => 'Temperatura máxima',
                'ph_min' => 'pH mínimo',
                'ph_max' => 'pH máximo',
                'turbidity_max' => 'Turbidez máxima',
                'water_level_min' => 'Nivel mínimo',
                'water_level_max' => 'Nivel máximo',
            ] as $field => $label)
                <div>
                    <label for="{{ $field }}" class="mb-2 block text-sm font-medium">{{ $label }}</label>
                    <input id="{{ $field }}" name="{{ $field }}" type="number" step="0.01"
                        value="{{ old($field, $pond->threshold?->{$field}) }}"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2.5 focus:border-cyan-600 focus:ring-2 focus:ring-cyan-100">
                    @error($field)
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            @endforeach

            <div class="flex items-end">
                <button type="submit" class="w-full rounded-lg bg-slate-900 px-5 py-2.5 font-semibold text-white hover:bg-slate-700">
                    Guardar rangos
                </button>
            </div>
        </form>
    </section>

    <section class="mt-8 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 px-6 py-4">
            <h2 class="text-lg font-semibold">Últimas lecturas</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wider text-slate-500">
                    <tr>
                        <th class="px-6 py-3">Temperatura</th>
                        <th class="px-6 py-3">pH</th>
                        <th class="px-6 py-3">Turbidez</th>
                        <th class="px-6 py-3">Nivel</th>
                        <th class="px-6 py-3">Dispositivo</th>
                        <th class="px-6 py-3">Fecha</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($latestReadings as $reading)
                        <tr>
                            <td class="px-6 py-4">{{ $reading->temperature }} °C</td>
                            <td class="px-6 py-4">{{ $reading->ph }}</td>
                            <td class="px-6 py-4">{{ $reading->turbidity }}</td>
                            <td class="px-6 py-4">{{ $reading->water_level }} %</td>
                            <td class="px-6 py-4">{{ $reading->device->name }}</td>
                            <td class="px-6 py-4 text-slate-500">{{ $reading->recorded_at }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-8 text-center text-slate-500">No hay lecturas disponibles.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="mt-8 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="text-lg font-semibold">Alertas activas</h2>

        <div class="mt-5 space-y-4">
            @forelse ($activeAlerts as $alert)
                <article class="flex flex-wrap items-center justify-between gap-4 rounded-xl border border-amber-200 bg-amber-50 p-4">
                    <div>
                        <p class="font-semibold text-amber-900">⚠ {{ $alert->message }}</p>
                        <p class="mt-1 text-sm text-amber-800">
                            {{ $alert->parameter }} · Valor: {{ $alert->value }} · {{ $alert->detected_at }}
                        </p>
                    </div>
                    <form method="POST" action="{{ route('alerts.resolve', $alert) }}">
                        @csrf
                        <button type="submit" class="rounded-lg bg-amber-700 px-4 py-2 text-sm font-semibold text-white hover:bg-amber-600">
                            Resolver
                        </button>
                    </form>
                </article>
            @empty
                <p class="text-sm text-slate-500">No hay alertas activas.</p>
            @endforelse
        </div>
    </section>
@endsection
