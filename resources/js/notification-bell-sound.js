/**
 * Tono corto para nuevas notificaciones en la campanita (Web Audio API; sin archivo externo).
 * Algunos navegadores exigen interacción previa del usuario para AudioContext; falla en silencio si aplica.
 */
function sjPlayNotificationBellSound() {
    try {
        const AC = window.AudioContext || window.webkitAudioContext;
        if (!AC) {
            return;
        }
        const ctx = new AC();
        const osc = ctx.createOscillator();
        const gain = ctx.createGain();
        osc.type = 'sine';
        osc.frequency.setValueAtTime(880, ctx.currentTime);
        osc.frequency.setValueAtTime(660, ctx.currentTime + 0.08);
        gain.gain.setValueAtTime(0.0001, ctx.currentTime);
        gain.gain.exponentialRampToValueAtTime(0.11, ctx.currentTime + 0.02);
        gain.gain.exponentialRampToValueAtTime(0.0001, ctx.currentTime + 0.22);
        osc.connect(gain);
        gain.connect(ctx.destination);
        osc.start(ctx.currentTime);
        osc.stop(ctx.currentTime + 0.24);
        osc.onended = () => {
            try {
                ctx.close();
            } catch {
                //
            }
        };
    } catch {
        //
    }
}

window.sjPlayNotificationBellSound = sjPlayNotificationBellSound;
