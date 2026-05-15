/**
 * ApexCharts + Livewire `wire:navigate`: destruir antes del morph evita SVG huérfanos
 * (radios/alturas negativas) que Livewire intenta parchear en livewire.js.
 */

/**
 * @param {ParentNode} [root]
 */
export function teardownApexCharts(root = document) {
    root.querySelectorAll('[data-apex-chart-root]').forEach((holder) => {
        const ch = holder._apexChart;
        if (ch && typeof ch.destroy === 'function') {
            try {
                ch.destroy();
            } catch {
                //
            }
        }
        delete holder._apexChart;
        try {
            holder.replaceChildren();
        } catch {
            holder.innerHTML = '';
        }
    });

    root.querySelectorAll('.apexcharts-root').forEach((node) => {
        try {
            node.remove();
        } catch {
            //
        }
    });
}

export function resizeVisibleApexCharts() {
    document.querySelectorAll('[data-apex-chart-root]').forEach((holder) => {
        if (!holder.isConnected) {
            return;
        }
        const ch = holder._apexChart;
        if (!ch || typeof ch.resize !== 'function') {
            return;
        }
        const rect = holder.getBoundingClientRect();
        if (rect.width < 32 || rect.height < 32) {
            return;
        }
        try {
            ch.resize();
        } catch {
            //
        }
    });
}

function registerMorphRemovingHook() {
    if (!window.Livewire?.hook) {
        return false;
    }
    window.Livewire.hook('morph.removing', ({ el }) => {
        if (el?.querySelectorAll) {
            teardownApexCharts(el);
        }
    });

    return true;
}

export function registerApexChartsLivewireHooks() {
    document.addEventListener('livewire:navigating', () => {
        teardownApexCharts(document);
    });

    document.addEventListener('livewire:navigated', () => {
        const run = () => resizeVisibleApexCharts();
        requestAnimationFrame(run);
        setTimeout(run, 80);
        setTimeout(run, 280);
    });

    document.addEventListener('livewire:init', () => {
        registerMorphRemovingHook();
    });
    registerMorphRemovingHook();
}
