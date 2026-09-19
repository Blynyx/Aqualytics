@extends('layouts.app')

@section('title', 'Notificaciones | Aqualytics')
@section('header-title', 'Notificaciones')

@section('content')
    <x-page-header
        eyebrow="Bandeja interna"
        title="Notificaciones"
        description="Avisos de alertas e incidencias de tu cuenta. Marcar como leída no elimina el registro ni el origen."
    />

    <section class="surface-card overflow-hidden" data-cy="notification-list">
        <div class="border-b border-slate-100 px-5 py-5 sm:px-6">
            <h2 class="text-lg font-extrabold tracking-tight text-slate-900">Bandeja</h2>
            <p class="mt-1 text-sm text-slate-500">Solo ves tus notificaciones. No hay canales externos.</p>
        </div>

        @if ($notifications->isNotEmpty())
            <ul class="divide-y divide-slate-100">
                @foreach ($notifications as $notification)
                    @php
                        $isUnread = $notification->read_at === null;
                        $sourceUrl = $sourceLinks[$notification->id] ?? null;
                    @endphp
                    <li
                        data-cy="notification-item"
                        @if ($isUnread) data-cy-unread="1" @endif
                        class="px-5 py-5 sm:px-6 {{ $isUnread ? 'bg-cyan-50/40' : '' }}"
                    >
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <p class="font-bold text-slate-900">{{ $notification->title }}</p>
                                    @if ($isUnread)
                                        <span class="inline-flex items-center rounded-full border border-amber-200 bg-amber-50 px-2.5 py-1 text-xs font-bold text-amber-700">No leída</span>
                                    @else
                                        <span class="inline-flex items-center rounded-full border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-xs font-bold text-emerald-700">Leída</span>
                                    @endif
                                    <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">
                                        {{ $notification->typeLabel() }}
                                    </span>
                                </div>
                                <p
                                    class="mt-2 text-sm leading-6 text-slate-600"
                                    data-cy="{{ $isUnread ? 'notification-unread' : 'notification-read' }}"
                                >
                                    {{ $notification->message }}
                                </p>
                                <p class="mt-2 text-xs font-semibold text-slate-400">
                                    {{ $notification->created_at?->timezone(config('app.timezone'))->format('d/m/Y H:i') }}
                                    @if (! $isUnread)
                                        · Leída
                                    @else
                                        · No leída
                                    @endif
                                </p>
                            </div>
                            <div class="flex shrink-0 flex-wrap items-center gap-3">
                                @if ($sourceUrl)
                                    <a
                                        href="{{ $sourceUrl }}"
                                        data-cy="notification-source-link"
                                        class="inline-flex min-h-11 items-center gap-1.5 font-bold text-cyan-700 hover:text-cyan-900"
                                    >
                                        Ver origen
                                        <svg class="size-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                            <path d="m9 18 6-6-6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                    </a>
                                @endif
                                @if ($isUnread)
                                    <form method="POST" action="{{ route('notifications.read', $notification) }}">
                                        @csrf
                                        <button
                                            type="submit"
                                            data-cy="notification-mark-read"
                                            class="inline-flex min-h-11 items-center rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-bold text-slate-700 shadow-sm transition hover:bg-slate-50"
                                        >
                                            Marcar como leída
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </li>
                @endforeach
            </ul>
            @if ($notifications->hasPages())
                <div class="border-t border-slate-100 px-5 py-4 sm:px-6">
                    {{ $notifications->links() }}
                </div>
            @endif
        @else
            <x-empty-state title="Sin notificaciones" description="Cuando se genere una alerta o se te asigne trabajo, el aviso aparecerá aquí." />
        @endif
    </section>
@endsection
