@extends('layouts.app')

@section('title', 'Usuarios | Aqualytics')

@section('content')
    <div class="mb-8 flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="text-sm font-semibold uppercase tracking-wider text-cyan-700">
                {{ auth()->user()->fishFarm->name }}
            </p>
            <h1 class="mt-2 text-3xl font-bold">Usuarios de la piscigranja</h1>
        </div>
        <a href="{{ route('users.create') }}" class="rounded-lg bg-cyan-700 px-5 py-2.5 font-semibold text-white hover:bg-cyan-600">
            Nuevo usuario
        </a>
    </div>

    @if (session('success'))
        <div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-emerald-800">
            {{ session('success') }}
        </div>
    @endif

    <div class="overflow-x-auto rounded-2xl border border-slate-200 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-xs uppercase tracking-wider text-slate-500">
                <tr>
                    <th class="px-6 py-3">Nombre</th>
                    <th class="px-6 py-3">Correo</th>
                    <th class="px-6 py-3">Rol</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach ($users as $user)
                    <tr>
                        <td class="px-6 py-4 font-medium">{{ $user->name }}</td>
                        <td class="px-6 py-4">{{ $user->email }}</td>
                        <td class="px-6 py-4">
                            {{ [
                                'admin' => 'Administrador',
                                'supervisor' => 'Supervisor',
                                'specialist' => 'Especialista',
                            ][$user->role] ?? $user->role }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endsection
