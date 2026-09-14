@extends('layouts.app')

@section('title', 'Usuarios | Aqualytics')
@section('header-title', 'Usuarios')

@section('content')
    <x-page-header
        :eyebrow="auth()->user()->fishFarm->name"
        title="Equipo de la piscigranja"
        description="Administra las personas que pueden consultar y operar la plataforma."
    >
        <x-slot:actions>
            <a href="{{ route('users.create') }}" class="btn-primary w-full sm:w-auto">
                <svg class="size-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                </svg>
                Nuevo usuario
            </a>
        </x-slot:actions>
    </x-page-header>

    @if (session('success'))
        <div class="mb-6 flex items-start gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800" role="status">
            <svg class="mt-0.5 size-5 shrink-0" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <path d="m5 12 4 4L19 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    <div class="surface-card overflow-hidden">
        <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4 sm:px-6">
            <div>
                <h2 class="font-extrabold text-slate-900">Usuarios registrados</h2>
                <p class="mt-1 text-xs text-slate-500">{{ $users->count() }} {{ $users->count() === 1 ? 'miembro' : 'miembros' }} con acceso</p>
            </div>
            <span class="grid size-9 place-items-center rounded-xl bg-violet-50 text-violet-700">
                <svg class="size-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M16 20v-1.5c0-2-1.8-3.5-4-3.5H7c-2.2 0-4 1.5-4 3.5V20M9.5 11a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7Zm7-1a3 3 0 0 0 0-5.8M17 14c2.2 0 4 1.5 4 3.5V19" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
                </svg>
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-[42rem] w-full text-sm">
                <thead class="table-head">
                    <tr>
                        <th class="px-6 py-3.5">Usuario</th>
                        <th class="px-4 py-3.5">Correo electrónico</th>
                        <th class="px-6 py-3.5">Rol y permisos</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($users as $user)
                        <tr class="transition hover:bg-slate-50/80">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <span class="grid size-10 shrink-0 place-items-center rounded-full bg-slate-900 text-sm font-extrabold text-white">
                                        {{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}
                                    </span>
                                    <div>
                                        <p class="font-bold text-slate-900">{{ $user->name }}</p>
                                        <p class="mt-0.5 text-xs text-slate-400">Miembro del equipo</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-4 text-slate-600">{{ $user->email }}</td>
                            <td class="px-6 py-4"><x-status-badge :status="$user->role" /></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection
