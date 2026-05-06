<div class="space-y-5">
    <div>
        <h1 class="text-lg font-semibold text-slate-900 text-center">Definir nueva contraseña</h1>
        <p class="mt-2 text-sm text-slate-600 text-center">
            Es la primera vez que accedes con la contraseña provisional. Por seguridad debes elegir una nueva contraseña para continuar.
        </p>
    </div>

    <form wire:submit="save" class="space-y-4">
        <div>
            <label for="fp-password" class="block text-xs font-semibold text-slate-600 mb-1">Nueva contraseña</label>
            <x-password-input wire:model="password" id="fp-password" name="password"
                class="w-full rounded-md border-slate-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-dash-lift dark:border-white/15 dark:text-slate-100"
                required autocomplete="new-password" />
            @error('password')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>
        <div>
            <label for="fp-password_confirmation" class="block text-xs font-semibold text-slate-600 mb-1">Confirmar contraseña</label>
            <x-password-input wire:model="password_confirmation" id="fp-password_confirmation" name="password_confirmation"
                class="w-full rounded-md border-slate-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-dash-lift dark:border-white/15 dark:text-slate-100"
                required autocomplete="new-password" />
        </div>

        <button type="submit"
            class="w-full flex justify-center py-2.5 px-4 rounded-md bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
            Guardar y continuar
        </button>
    </form>

    <div class="pt-4 border-t border-slate-200 flex justify-center">
        <livewire:auth.logout-button />
    </div>
</div>
