<x-app-layout>
    @push('module-nav')
        <x-disciplinary.nav />
    @endpush

    <div class="bg-white border-b border-slate-200 dark:bg-dash-ink/60 dark:border-white/10">
        <div class="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8 py-5">
            <p class="text-xs uppercase tracking-widest text-slate-500 font-semibold dark:text-dash-muted">Disciplinarios · FO-GJ-51</p>
            <h1 class="mt-1 text-2xl font-bold text-slate-900 dark:text-white">Generando PDF</h1>
        </div>
    </div>

    <div class="py-10 sm:py-14">
        <div class="max-w-lg mx-auto px-4 sm:px-6 text-center">
            <div class="inline-flex h-12 w-12 items-center justify-center rounded-full bg-sky-100 text-sky-700 dark:bg-sky-500/20 dark:text-sky-200" aria-hidden="true">
                <svg class="h-6 w-6 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
            </div>

            <p id="fo51-queue-message" class="mt-6 text-base text-slate-700 dark:text-slate-200">
                @if ($intent === 'enviar')
                    Estamos generando su informe y enviándolo a revisión. Suele tardar menos de un minuto.
                @else
                    Estamos generando su PDF. Suele tardar menos de un minuto.
                @endif
            </p>

            <p id="fo51-queue-hint" class="mt-4 hidden rounded-lg border border-amber-300 bg-amber-50 px-3 py-2 text-left text-xs text-amber-900 dark:border-amber-500/40 dark:bg-amber-500/10 dark:text-amber-100">
                Sigue pendiente. En Hostinger hace falta el cron CLI.
                SSH: <code class="font-mono">php artisan disciplinary:process-pdf-queue</code>.
                Si el mutex del scheduler quedó trabado:
                <code class="font-mono">php artisan schedule:clear-cache</code>.
            </p>

            <p id="fo51-queue-error" class="mt-4 hidden text-sm text-red-700 dark:text-red-300"></p>

            <a id="fo51-queue-back" href="{{ route('disciplinary.forms.informe-fo-gj-51', ['vista_completa' => 1]) }}"
               class="mt-8 inline-flex hidden items-center rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800 dark:bg-white dark:text-slate-900">
                Volver al formulario
            </a>
        </div>
    </div>

    @push('scripts')
        <script>
            (function () {
                const statusUrl = @json($statusUrl);
                const downloadUrl = @json($downloadUrl);
                const messageEl = document.getElementById('fo51-queue-message');
                const hintEl = document.getElementById('fo51-queue-hint');
                const errorEl = document.getElementById('fo51-queue-error');
                const backEl = document.getElementById('fo51-queue-back');
                const started = Date.now();

                const poll = () => {
                    if (Date.now() - started > 45000) {
                        hintEl.classList.remove('hidden');
                    }

                    fetch(statusUrl, {
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                        credentials: 'same-origin',
                    })
                        .then((response) => {
                            if (!response.ok) {
                                throw new Error('No se pudo consultar el estado del PDF.');
                            }

                            return response.json();
                        })
                        .then((data) => {
                            if (data.status === 'ready') {
                                window.location.href = downloadUrl;
                                return;
                            }

                            if (data.status === 'submitted' && data.redirect_url) {
                                window.location.href = data.redirect_url;
                                return;
                            }

                            if (data.status === 'failed') {
                                messageEl.classList.add('hidden');
                                hintEl.classList.add('hidden');
                                errorEl.textContent = data.error || 'No se pudo generar el PDF.';
                                errorEl.classList.remove('hidden');
                                backEl.classList.remove('hidden');
                                return;
                            }

                            window.setTimeout(poll, 2000);
                        })
                        .catch(() => {
                            window.setTimeout(poll, 3000);
                        });
                };

                poll();
            })();
        </script>
    @endpush
</x-app-layout>
