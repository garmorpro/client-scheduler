// In-house replacement for SweetAlert2, styled with the app's own design
// language instead of SweetAlert2's default look. Self-contained - builds
// its DOM on demand and appends to <body>, so no markup needs to exist on
// the page ahead of time; just include this script once (matches how
// SweetAlert2 itself was loaded).
//
//   appConfirm({icon, title, text, confirmText, cancelText, danger}) -> Promise<boolean>
//   appSelectPrompt({title, options: [{value,label}], confirmText, cancelText}) -> Promise<string|null>
//   appNotify({icon, title, text, timer}) -> void   (toast; icon: 'success'|'error'|'warning')
(function () {
    function iconSvg(icon) {
        const common = 'width="26" height="26" viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"';
        if (icon === 'success') return `<svg ${common} stroke="#2f9e57"><path d="M20 6 9 17l-5-5"/></svg>`;
        if (icon === 'error') return `<svg ${common} stroke="#c0392b"><circle cx="12" cy="12" r="10"/><path d="M15 9l-6 6M9 9l6 6"/></svg>`;
        if (icon === 'question') return `<svg ${common} stroke="#6b7570"><circle cx="12" cy="12" r="10"/><path d="M9.5 9a2.5 2.5 0 0 1 5 0c0 1.4-1.2 1.9-2 2.6-.4.4-.5.7-.5 1.4M12 17h.01"/></svg>`;
        return `<svg ${common} stroke="#c98a2e"><path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z"/><path d="M12 9v4M12 17h.01"/></svg>`;
    }

    let overlayEl = null;
    function onKeydown(e) {
        if (e.key === 'Escape') closeOverlay(false);
    }
    let pendingResolve = null;
    function closeOverlay(resolveValue) {
        if (!overlayEl) return;
        overlayEl.remove();
        overlayEl = null;
        document.removeEventListener('keydown', onKeydown);
        if (pendingResolve) { const r = pendingResolve; pendingResolve = null; r(resolveValue); }
    }

    function openOverlay(innerHtml) {
        if (overlayEl) closeOverlay(false);
        overlayEl = document.createElement('div');
        overlayEl.className = 'app-alert-overlay';
        overlayEl.innerHTML = `<div class="app-alert-card" role="dialog" aria-modal="true">${innerHtml}</div>`;
        document.body.appendChild(overlayEl);
        document.addEventListener('keydown', onKeydown);
        overlayEl.addEventListener('mousedown', (e) => { if (e.target === overlayEl) closeOverlay(false); });
        return overlayEl.querySelector('.app-alert-card');
    }

    // `danger` is shorthand for the standard red destructive-action color;
    // pass `confirmColor` (any CSS color) instead when a call site needs a
    // specific one-off color (e.g. request-review's approve/deny/request-
    // changes each meaning something different) - it wins over `danger`.
    window.appConfirm = function ({ icon = 'warning', title = '', text = '', confirmText = 'Confirm', cancelText = 'Cancel', danger = false, confirmColor = null } = {}) {
        return new Promise((resolve) => {
            pendingResolve = resolve;
            const card = openOverlay(`
                <div class="app-alert-icon">${iconSvg(icon)}</div>
                <div class="app-alert-title">${title}</div>
                ${text ? `<div class="app-alert-text">${text}</div>` : ''}
                <div class="app-alert-actions">
                    <button type="button" class="app-alert-btn-cancel">${cancelText}</button>
                    <button type="button" class="app-alert-btn-confirm${danger ? ' danger' : ''}"${confirmColor ? ` style="background:${confirmColor};"` : ''}>${confirmText}</button>
                </div>
            `);
            card.querySelector('.app-alert-btn-cancel').addEventListener('click', () => closeOverlay(false));
            const confirmBtn = card.querySelector('.app-alert-btn-confirm');
            confirmBtn.addEventListener('click', () => closeOverlay(true));
            confirmBtn.focus();
        });
    };

    window.appSelectPrompt = function ({ title = '', options = [], confirmText = 'Confirm', cancelText = 'Cancel' } = {}) {
        return new Promise((resolve) => {
            pendingResolve = resolve;
            const optionsHtml = options.map(o => `<option value="${o.value}">${o.label}</option>`).join('');
            const card = openOverlay(`
                <div class="app-alert-title">${title}</div>
                <select class="app-alert-select">${optionsHtml}</select>
                <div class="app-alert-actions">
                    <button type="button" class="app-alert-btn-cancel">${cancelText}</button>
                    <button type="button" class="app-alert-btn-confirm">${confirmText}</button>
                </div>
            `);
            const select = card.querySelector('.app-alert-select');
            card.querySelector('.app-alert-btn-cancel').addEventListener('click', () => closeOverlay(null));
            card.querySelector('.app-alert-btn-confirm').addEventListener('click', () => {
                const val = select.value;
                overlayEl.remove(); overlayEl = null;
                document.removeEventListener('keydown', onKeydown);
                pendingResolve = null;
                resolve(val);
            });
            select.focus();
        });
    };

    window.appTextPrompt = function ({ title = '', text = '', value = '', placeholder = '', confirmText = 'Save', cancelText = 'Cancel' } = {}) {
        return new Promise((resolve) => {
            pendingResolve = resolve;
            const card = openOverlay(`
                <div class="app-alert-title">${title}</div>
                ${text ? `<div class="app-alert-text" style="text-align:left;">${text}</div>` : ''}
                <input type="text" class="app-alert-field" placeholder="${placeholder}" value="${value.replace(/"/g, '&quot;')}">
                <div class="app-alert-actions">
                    <button type="button" class="app-alert-btn-cancel">${cancelText}</button>
                    <button type="button" class="app-alert-btn-confirm">${confirmText}</button>
                </div>
            `);
            const input = card.querySelector('input');
            card.querySelector('.app-alert-btn-cancel').addEventListener('click', () => closeOverlay(null));
            const submit = () => {
                const val = input.value;
                overlayEl.remove(); overlayEl = null;
                document.removeEventListener('keydown', onKeydown);
                pendingResolve = null;
                resolve(val);
            };
            card.querySelector('.app-alert-btn-confirm').addEventListener('click', submit);
            input.addEventListener('keydown', (e) => { if (e.key === 'Enter') { e.preventDefault(); submit(); } });
            input.focus();
            input.select();
        });
    };

    let toastContainer = null;
    window.appNotify = function ({ icon = 'success', title = '', text = '', timer } = {}) {
        if (!toastContainer) {
            toastContainer = document.createElement('div');
            toastContainer.className = 'app-toast-container';
            document.body.appendChild(toastContainer);
        }
        const toast = document.createElement('div');
        toast.className = `app-toast app-toast-${icon}`;
        toast.innerHTML = `
            <div class="app-toast-icon">${iconSvg(icon)}</div>
            <div class="app-toast-body">
                ${title ? `<div class="app-toast-title">${title}</div>` : ''}
                ${text ? `<div class="app-toast-text">${text}</div>` : ''}
            </div>
            <button type="button" class="app-toast-close" aria-label="Close">&times;</button>
        `;
        toastContainer.appendChild(toast);
        requestAnimationFrame(() => toast.classList.add('show'));
        function remove() {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 200);
        }
        toast.querySelector('.app-toast-close').addEventListener('click', remove);
        const autoTimer = timer !== undefined ? timer : (icon === 'success' ? 1800 : null);
        if (autoTimer) setTimeout(remove, autoTimer);
    };

    // Undo toast (a soft-delete pattern used a couple places) - stays up
    // for 6s with an inline Undo button; onDismiss fires once, either when
    // the toast times out/gets closed without Undo being clicked, or is a
    // no-op if it already fired via undo.
    window.appUndoToast = function (message, onUndo, onDismiss) {
        if (!toastContainer) {
            toastContainer = document.createElement('div');
            toastContainer.className = 'app-toast-container';
            document.body.appendChild(toastContainer);
        }
        const toast = document.createElement('div');
        toast.className = 'app-toast app-toast-success';
        toast.innerHTML = `
            <div class="app-toast-icon">${iconSvg('success')}</div>
            <div class="app-toast-body">
                <div class="app-toast-title">${message}</div>
                <button type="button" class="app-toast-undo-btn">Undo</button>
            </div>
            <button type="button" class="app-toast-close" aria-label="Close">&times;</button>
        `;
        toastContainer.appendChild(toast);
        requestAnimationFrame(() => toast.classList.add('show'));
        let undone = false;
        function remove() {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 200);
        }
        function settle() {
            remove();
            if (!undone && onDismiss) onDismiss();
        }
        toast.querySelector('.app-toast-undo-btn').addEventListener('click', async () => {
            undone = true;
            clearTimeout(timeoutId);
            remove();
            await onUndo();
        });
        toast.querySelector('.app-toast-close').addEventListener('click', settle);
        const timeoutId = setTimeout(settle, 6000);
    };
})();
