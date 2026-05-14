type ToastKind = 'info' | 'success' | 'error';

export function showToast(message: string, kind: ToastKind = 'info', timeoutMs = 3500): void {
    const host = document.getElementById('toast-host');
    if (!host) {
        console.log(`[${kind}] ${message}`);
        return;
    }
    const el = document.createElement('div');
    el.className = `toast toast-${kind}`;
    el.textContent = message;
    host.appendChild(el);
    requestAnimationFrame(() => el.classList.add('visible'));
    window.setTimeout(() => {
        el.classList.remove('visible');
        window.setTimeout(() => el.remove(), 300);
    }, timeoutMs);
}
