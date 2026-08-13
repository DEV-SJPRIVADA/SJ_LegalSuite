<?php

use App\Livewire\Forms\LoginForm;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public LoginForm $form;

    /**
     * Handle an incoming authentication request.
     */
    public function login(): void
    {
        $this->validate();

        $this->form->authenticate();

        Session::regenerate();

        if (Auth::user()->must_change_password) {
            $this->redirect(route('password.force-change', absolute: false), navigate: true);

            return;
        }

        $this->redirectIntended(default: route('dashboard', absolute: false), navigate: true);
    }
}; ?>

@php
    $field = 'block w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 shadow-sm placeholder:text-slate-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20';
    $label = 'block text-xs font-semibold uppercase tracking-wide text-slate-600';
@endphp

<div>
    <div class="mb-6">
        <h2 class="text-lg font-bold text-slate-900 sm:text-xl">Iniciar sesión</h2>
        <p class="mt-1 text-sm text-slate-500">Usa tu correo corporativo para continuar.</p>
    </div>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form wire:submit="login" class="space-y-5">
        <div>
            <label for="email" class="{{ $label }}">Correo electrónico</label>
            <x-text-input
                wire:model="form.email"
                id="email"
                class="{{ $field }} mt-1.5"
                type="email"
                name="email"
                required
                autofocus
                autocomplete="username"
                placeholder="usuario@sjsp.net"
            />
            <x-input-error :messages="$errors->get('form.email')" class="mt-2" />
        </div>

        <div>
            <label for="password" class="{{ $label }}">Contraseña</label>
            <div class="mt-1.5">
                <x-password-input
                    wire:model="form.password"
                    id="password"
                    class="{{ $field }}"
                    name="password"
                    required
                    autocomplete="current-password"
                />
            </div>
            <x-input-error :messages="$errors->get('form.password')" class="mt-2" />
        </div>

        <div class="flex flex-wrap items-center justify-between gap-3">
            <label for="remember" class="inline-flex cursor-pointer items-center gap-2">
                <input
                    wire:model="form.remember"
                    id="remember"
                    type="checkbox"
                    class="rounded border-slate-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                    name="remember"
                >
                <span class="text-sm text-slate-600">Recordarme</span>
            </label>

            @if (Route::has('password.request'))
                <a
                    href="{{ route('password.request') }}"
                    wire:navigate
                    class="text-sm font-medium text-indigo-600 hover:text-indigo-500"
                >
                    ¿Olvidaste tu contraseña?
                </a>
            @endif
        </div>

        <button
            type="submit"
            class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-slate-900 px-5 py-3 text-sm font-semibold text-white shadow-lg transition hover:bg-slate-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2"
            wire:loading.attr="disabled"
        >
            <span wire:loading.remove wire:target="login">Ingresar</span>
            <span wire:loading wire:target="login" class="inline-flex items-center gap-2">
                <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                Ingresando…
            </span>
            <svg wire:loading.remove wire:target="login" class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path fill-rule="evenodd" d="M3 10a.75.75 0 01.75-.75h10.638L10.23 5.29a.75.75 0 111.04-1.08l5.5 5.25a.75.75 0 010 1.08l-5.5 5.25a.75.75 0 11-1.04-1.08l4.158-3.96H3.75A.75.75 0 013 10z" clip-rule="evenodd" />
            </svg>
        </button>
    </form>
</div>
