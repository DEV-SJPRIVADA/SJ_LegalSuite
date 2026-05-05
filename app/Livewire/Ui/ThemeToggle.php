<?php

namespace App\Livewire\Ui;

use Livewire\Component;

class ThemeToggle extends Component
{
    public function setTheme(string $mode): void
    {
        abort_unless(auth()->check(), 403);
        abort_unless(in_array($mode, ['light', 'dark'], true), 400);

        auth()->user()->forceFill(['theme' => $mode])->save();

        // Livewire POSTs to /livewire/update; fullUrl() would redirect there as GET → MethodNotAllowed.
        $referer = request()->headers->get('referer');
        $target = (is_string($referer) && $referer !== '' && ! str_contains($referer, '/livewire/update'))
            ? $referer
            : route('dashboard');

        $this->redirect($target, navigate: false);
    }

    public function render()
    {
        $current = auth()->user()->theme ?? 'light';
        if (! in_array($current, ['light', 'dark'], true)) {
            $current = 'light';
        }

        return view('livewire.ui.theme-toggle', [
            'current' => $current,
        ]);
    }
}
