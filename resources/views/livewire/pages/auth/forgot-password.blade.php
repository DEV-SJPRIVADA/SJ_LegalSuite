<?php

use Illuminate\Support\Facades\Password;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public string $email = '';

    /**
     * Send a password reset link to the provided email address.
     */
    public function sendPasswordResetLink(): void
    {
        $this->validate([
            'email' => ['required', 'string', 'email'],
        ]);

        $status = Password::sendResetLink(
            $this->only('email')
        );

        if ($status != Password::RESET_LINK_SENT) {
            $this->addError('email', __($status));

            return;
        }

        $this->reset('email');

        session()->flash('status', __($status));
    }
}; ?>

@php
    $field = 'block w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 shadow-sm placeholder:text-slate-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20';
    $label = 'block text-xs font-semibold uppercase tracking-wide text-slate-600';
@endphp

<div>
    <div class="mb-6">
        <h2 class="text-lg font-bold text-slate-900 sm:text-xl">Recuperar contraseña</h2>
        <p class="mt-1 text-sm text-slate-500">
            Indica tu correo y te enviaremos un enlace para restablecerla.
        </p>
    </div>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form wire:submit="sendPasswordResetLink" class="space-y-5">
        <div>
            <label for="email" class="{{ $label }}">Correo electrónico</label>
            <x-text-input
                wire:model="email"
                id="email"
                class="{{ $field }} mt-1.5"
                type="email"
                name="email"
                required
                autofocus
                placeholder="usuario@sjsp.net"
            />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-between">
            <a href="{{ route('login') }}" wire:navigate class="text-center text-sm font-medium text-slate-600 hover:text-slate-900 sm:text-left">
                ← Volver al login
            </a>
            <button
                type="submit"
                class="inline-flex w-full items-center justify-center rounded-lg bg-slate-900 px-5 py-3 text-sm font-semibold text-white shadow-lg transition hover:bg-slate-800 sm:w-auto"
                wire:loading.attr="disabled"
            >
                <span wire:loading.remove wire:target="sendPasswordResetLink">Enviar enlace</span>
                <span wire:loading wire:target="sendPasswordResetLink">Enviando…</span>
            </button>
        </div>
    </form>
</div>
