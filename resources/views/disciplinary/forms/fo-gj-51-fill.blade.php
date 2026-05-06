<x-app-layout>
    @push('module-nav')
        <x-disciplinary.nav />
    @endpush

    @php
        /** @var string|null $prefillWorkerName */
        /** @var string|null $prefillWorkerDocument */
        /** @var bool $openPdfUploadModal */
        $openPdfUploadModal = $openPdfUploadModal ?? false;
        $pdfIframeName = 'fo51_pdf_iframe_page';
    @endphp

    <div class="bg-white border-b border-slate-200 dark:bg-dash-ink/60 dark:border-white/10">
        <div class="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8 py-5">
            <p class="text-xs uppercase tracking-widest text-slate-500 font-semibold dark:text-dash-muted">Disciplinarios · FO-GJ-51</p>
            <h1 class="mt-1 text-2xl font-bold text-slate-900 dark:text-white">Informe disciplinario</h1>
        </div>
    </div>

    <div class="py-6 sm:py-8">
        <div class="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="mb-6 rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-900 ring-1 ring-emerald-200 dark:bg-emerald-500/15 dark:text-emerald-100 dark:ring-emerald-500/30">
                    {{ session('success') }}
                </div>
            @endif

            @include('disciplinary.forms.partials.fo-gj-51-informe-body', [
                'prefillWorkerName' => $prefillWorkerName,
                'prefillWorkerDocument' => $prefillWorkerDocument,
                'openPdfUploadModal' => $openPdfUploadModal,
                'pdfIframeName' => $pdfIframeName,
            ])
        </div>
    </div>
</x-app-layout>
