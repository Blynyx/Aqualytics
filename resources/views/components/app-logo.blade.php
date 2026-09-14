@props(['compact' => false])

<a href="{{ url('/') }}" {{ $attributes->merge(['class' => 'inline-flex items-center gap-3 rounded-lg']) }}>
    <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-cyan-600 text-white shadow-lg shadow-cyan-950/20">
        <svg class="size-6" viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <path d="M4 14.5C7.2 11.7 9 8.8 12 4c3 4.8 4.8 7.7 8 10.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
            <path d="M4 17c2.1 0 2.1 1.5 4.1 1.5S10.2 17 12.2 17s2.1 1.5 4.1 1.5S18.4 17 20.4 17" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
        </svg>
    </span>
    @unless ($compact)
        <span>
            <span class="block text-lg font-extrabold tracking-tight">Aqualytics</span>
            <span class="block text-[0.625rem] font-bold uppercase tracking-[0.18em] opacity-60">Aquaculture Intelligence</span>
        </span>
    @endunless
</a>
