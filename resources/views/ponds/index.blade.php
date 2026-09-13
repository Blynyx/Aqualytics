@extends('layouts.app')

@section('title', 'Estanques | Aqualytics')

@section('content')
    <div class="mb-8">
        <p class="text-sm font-semibold uppercase tracking-wider text-cyan-700">Gestión acuícola</p>
        <h1 class="mt-2 text-3xl font-bold">Mis estanques</h1>
    </div>

    <div class="overflow-x-auto rounded-2xl border border-slate-200 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-xs uppercase tracking-wider text-slate-500">
                <tr>
                    <th class="px-6 py-3">Nombre</th>
                    <th class="px-6 py-3">Código</th>
                    <th class="px-6 py-3">Especie</th>
                    <th class="px-6 py-3">Ubicación</th>
                    <th class="px-6 py-3">Estado</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($ponds as $pond)
                    <tr>
                        <td class="px-6 py-4 font-medium">{{ $pond->name }}</td>
                        <td class="px-6 py-4">{{ $pond->code }}</td>
                        <td class="px-6 py-4">{{ $pond->species ?: '—' }}</td>
                        <td class="px-6 py-4">{{ $pond->location ?: '—' }}</td>
                        <td class="px-6 py-4">{{ $pond->status }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-8 text-center text-slate-500">
                            Aún no tienes estanques registrados.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
