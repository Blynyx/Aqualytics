@props([
    'token',
    'deviceUid' => null,
])

<section data-cy="device-token-issued" class="surface-card mb-8 overflow-hidden border border-cyan-200">
    <div class="border-b border-cyan-100 bg-cyan-50/70 px-5 py-5 sm:px-6">
        <p class="text-xs font-bold uppercase tracking-[0.18em] text-cyan-700">Dispositivo registrado correctamente</p>
        <h2 class="mt-1 text-lg font-extrabold tracking-tight text-slate-900">Guarda esta clave. Por seguridad no volverá a mostrarse.</h2>
        <p class="mt-1 text-sm text-slate-600">Este token se muestra una sola vez. Guárdalo para configurar el ESP32.</p>
    </div>
    <div class="space-y-4 px-5 py-5 sm:px-6">
        @if ($deviceUid)
            <div>
                <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Device UID</p>
                <p data-cy="issued-device-uid" class="mt-1 font-mono text-sm font-bold text-slate-800">{{ $deviceUid }}</p>
            </div>
        @endif
        <div>
            <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Token del dispositivo</p>
            <div class="mt-2 flex flex-col gap-3 sm:flex-row sm:items-center">
                <code data-cy="device-token-value" class="min-w-0 flex-1 break-all rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 font-mono text-sm font-semibold text-slate-800">{{ $token }}</code>
                <button
                    type="button"
                    data-cy="copy-device-token"
                    data-copy-device-token
                    class="btn-primary shrink-0"
                >
                    Copiar
                </button>
            </div>
        </div>
    </div>
</section>

<script>
    document.querySelector('[data-copy-device-token]')?.addEventListener('click', async (event) => {
        const token = document.querySelector('[data-cy="device-token-value"]')?.textContent?.trim();
        if (!token) {
            return;
        }

        try {
            await navigator.clipboard.writeText(token);
            event.currentTarget.textContent = 'Copiado';
        } catch {
            event.currentTarget.textContent = 'No se pudo copiar';
        }
    });
</script>
