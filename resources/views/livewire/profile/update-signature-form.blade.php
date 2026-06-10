<?php

use App\Services\Users\UserSignatureService;
use Illuminate\Support\Facades\Auth;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;

new class extends Component
{
    use WithFileUploads;

    public $signatureFile = null;

    public function saveSignature(UserSignatureService $signatures): void
    {
        $this->validate([
            'signatureFile' => ['required', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
        ], [
            'signatureFile.required' => 'Seleccione una imagen con su firma.',
            'signatureFile.image' => 'El archivo debe ser una imagen.',
            'signatureFile.max' => 'La firma no puede superar 2 MB.',
        ]);

        $user = Auth::user();
        $signatures->store($user, $this->signatureFile);
        $this->reset('signatureFile');
        $this->dispatch('signature-updated');
        session()->flash('signature-status', 'Firma guardada correctamente.');
    }

    public function removeSignature(UserSignatureService $signatures): void
    {
        $signatures->remove(Auth::user());
        $this->reset('signatureFile');
        $this->dispatch('signature-updated');
        session()->flash('signature-status', 'Firma eliminada.');
    }
}; ?>

<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900 dark:text-white">
            Firma digital
        </h2>
        <p class="mt-1 text-sm text-gray-600 dark:text-slate-400">
            Suba una imagen de su firma (PNG o JPG). Se usará en documentos oficiales que usted genere, como el FO-GJ-03.
        </p>
    </header>

    <div class="mt-6 space-y-4">
        @if (auth()->user()->hasSignature())
            <div class="rounded-lg border border-slate-200 bg-slate-50 p-4 dark:border-white/10 dark:bg-white/[0.03]">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Firma actual</p>
                <img src="{{ route('profile.signature') }}?v={{ auth()->user()->updated_at?->timestamp }}" alt="Firma actual" class="mt-2 max-h-24 object-contain">
            </div>
        @endif

        <div>
            <x-input-label for="signatureFile" value="Nueva imagen de firma" />
            <input type="file" wire:model="signatureFile" id="signatureFile" accept="image/png,image/jpeg,image/jpg,image/webp" class="mt-1 block w-full text-sm text-slate-700 dark:text-slate-300">
            <x-input-error class="mt-2" :messages="$errors->get('signatureFile')" />
            <div wire:loading wire:target="signatureFile" class="mt-2 text-xs text-slate-500">Cargando archivo…</div>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <x-primary-button type="button" wire:click="saveSignature" wire:loading.attr="disabled" wire:target="saveSignature,signatureFile">
                Guardar firma
            </x-primary-button>
            @if (auth()->user()->hasSignature())
                <button type="button" wire:click="removeSignature" wire:confirm="¿Eliminar la firma guardada?"
                    class="text-sm font-semibold text-red-700 underline dark:text-red-400">
                    Eliminar firma
                </button>
            @endif
            <x-action-message class="text-sm text-emerald-600 dark:text-emerald-400" on="signature-updated">
                {{ session('signature-status') }}
            </x-action-message>
        </div>
    </div>
</section>
