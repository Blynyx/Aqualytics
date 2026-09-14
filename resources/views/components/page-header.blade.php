@props([
    'eyebrow' => null,
    'title',
    'description' => null,
    'backUrl' => null,
    'backLabel' => null,
])

<header class="mb-8">
    @if ($backUrl)
        <a href="{{ $backUrl }}" class="mb-4 inline-flex items-center gap-2 text-sm font-semibold text-cyan-700 transition hover:text-cyan-900">
            <svg class="size-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <path d="m15 18-6-6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            {{ $backLabel }}
        </a>
    @endif

    <div class="flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
        <div class="min-w-0">
            @if ($eyebrow)
                <p class="section-eyebrow">{{ $eyebrow }}</p>
            @endif
            <h1 class="{{ $eyebrow ? 'mt-2' : '' }} text-2xl font-extrabold tracking-tight text-slate-950 sm:text-3xl">
                {{ $title }}
            </h1>
            @if ($description)
                <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-500 sm:text-base">{{ $description }}</p>
            @endif
        </div>

        @isset($actions)
            <div class="shrink-0">{{ $actions }}</div>
        @endisset
    </div>
</header>
