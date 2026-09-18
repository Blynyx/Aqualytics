@props(['status', 'raw' => false])

@php
    $styles = [
        'active' => 'border-emerald-200 bg-emerald-50 text-emerald-700',
        'open' => 'border-amber-200 bg-amber-50 text-amber-700',
        'assigned' => 'border-blue-200 bg-blue-50 text-blue-700',
        'in_progress' => 'border-cyan-200 bg-cyan-50 text-cyan-700',
        'resolved' => 'border-emerald-200 bg-emerald-50 text-emerald-700',
        'closed' => 'border-slate-200 bg-slate-100 text-slate-600',
        'inactive' => 'border-slate-200 bg-slate-100 text-slate-600',
        'admin' => 'border-violet-200 bg-violet-50 text-violet-700',
        'supervisor' => 'border-blue-200 bg-blue-50 text-blue-700',
        'specialist' => 'border-teal-200 bg-teal-50 text-teal-700',
    ];

    $labels = [
        'active' => 'Activo',
        'open' => 'Abierta',
        'assigned' => 'Asignada',
        'in_progress' => 'En progreso',
        'resolved' => 'Resuelta',
        'closed' => 'Cerrada',
        'inactive' => 'Inactivo',
        'admin' => 'Administrador',
        'supervisor' => 'Supervisor',
        'specialist' => 'Especialista',
    ];

    $style = $styles[$status] ?? 'border-slate-200 bg-slate-100 text-slate-600';
    $label = $raw ? $status : ($labels[$status] ?? ucfirst($status));
@endphp

<span {{ $attributes->merge([
    'class' => 'inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-xs font-bold '.$style,
]) }}>
    <span class="size-1.5 rounded-full bg-current" aria-hidden="true"></span>
    {{ $label }}
</span>
