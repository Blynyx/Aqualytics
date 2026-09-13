@extends('layouts.app')

@section('title', 'Dashboard | Aqualytics')

@section('content')
    <div class="mb-8">
        <p class="text-sm font-semibold uppercase tracking-wider text-cyan-700">Resumen general</p>
        <h1 class="mt-2 text-3xl font-bold">Dashboard</h1>
        <p class="mt-2 text-slate-500">Estado actual de tu operación acuícola.</p>
    </div>

    <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ([
            ['label' => 'Estanques', 'value' => $pondCount],
            ['label' => 'Dispositivos', 'value' => $deviceCount],
            ['label' => 'Lecturas', 'value' => $readingCount],
            ['label' => 'Alertas activas', 'value' => $activeAlertCount],
        ] as $summary)
            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-medium text-slate-500">{{ $summary['label'] }}</p>
                <p class="mt-3 text-3xl font-bold text-slate-900">{{ $summary['value'] }}</p>
            </article>
        @endforeach
    </section>

    <section class="mt-8 rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 px-6 py-4">
            <h2 class="text-lg font-semibold">Estanques del usuario</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wider text-slate-500">
                    <tr>
                        <th class="px-6 py-3">Nombre</th>
                        <th class="px-6 py-3">Código</th>
                        <th class="px-6 py-3">Especie</th>
                        <th class="px-6 py-3">Estado</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($ponds as $pond)
                        <tr>
                            <td class="px-6 py-4 font-medium">{{ $pond->name }}</td>
                            <td class="px-6 py-4">{{ $pond->code }}</td>
                            <td class="px-6 py-4">{{ $pond->species ?: '—' }}</td>
                            <td class="px-6 py-4">{{ $pond->status }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-8 text-center text-slate-500">
                                Aún no tienes estanques registrados.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="mt-8 rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 px-6 py-4">
            <h2 class="text-lg font-semibold">Últimas lecturas</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wider text-slate-500">
                    <tr>
                        <th class="px-6 py-3">Estanque</th>
                        <th class="px-6 py-3">Dispositivo</th>
                        <th class="px-6 py-3">Temperatura</th>
                        <th class="px-6 py-3">pH</th>
                        <th class="px-6 py-3">Turbidez</th>
                        <th class="px-6 py-3">Nivel</th>
                        <th class="px-6 py-3">Fecha</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($latestReadings as $reading)
                        <tr>
                            <td class="px-6 py-4 font-medium">{{ $reading->pond->name }}</td>
                            <td class="px-6 py-4">{{ $reading->device->name }}</td>
                            <td class="px-6 py-4">{{ $reading->temperature }} °C</td>
                            <td class="px-6 py-4">{{ $reading->ph }}</td>
                            <td class="px-6 py-4">{{ $reading->turbidity }}</td>
                            <td class="px-6 py-4">{{ $reading->water_level }}</td>
                            <td class="px-6 py-4 text-slate-500">{{ $reading->recorded_at }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-8 text-center text-slate-500">
                                No hay lecturas disponibles.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="mt-8 rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 px-6 py-4">
            <h2 class="text-lg font-semibold">Alertas activas</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wider text-slate-500">
                    <tr>
                        <th class="px-6 py-3">Estanque</th>
                        <th class="px-6 py-3">Parámetro</th>
                        <th class="px-6 py-3">Valor</th>
                        <th class="px-6 py-3">Mensaje</th>
                        <th class="px-6 py-3">Fecha</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($activeAlerts as $alert)
                        <tr>
                            <td class="px-6 py-4 font-medium">{{ $alert->pond->name }}</td>
                            <td class="px-6 py-4">{{ $alert->parameter }}</td>
                            <td class="px-6 py-4">{{ $alert->value }}</td>
                            <td class="px-6 py-4 text-amber-700">{{ $alert->message }}</td>
                            <td class="px-6 py-4 text-slate-500">{{ $alert->detected_at }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-8 text-center text-slate-500">
                                No hay alertas activas.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
