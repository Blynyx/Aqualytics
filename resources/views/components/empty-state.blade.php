@props(['title', 'description'])

<div {{ $attributes->merge(['class' => 'flex flex-col items-center justify-center px-6 py-12 text-center']) }}>
    <span class="grid size-12 place-items-center rounded-2xl bg-cyan-50 text-cyan-700">
        <svg class="size-6" viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <path d="M12 3v3m7.8 1.2-2.1 2.1M21 15h-3M6.3 9.3 4.2 7.2M6 15H3m6.5 4h5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
            <path d="M8 15a4 4 0 1 1 8 0c0 1.4-.7 2.2-1.5 3H9.5C8.7 17.2 8 16.4 8 15Z" stroke="currentColor" stroke-width="1.7"/>
        </svg>
    </span>
    <h3 class="mt-4 text-sm font-bold text-slate-800">{{ $title }}</h3>
    <p class="mt-1 max-w-sm text-sm leading-6 text-slate-500">{{ $description }}</p>
    @isset($action)
        <div class="mt-5">{{ $action }}</div>
    @endisset
</div>
