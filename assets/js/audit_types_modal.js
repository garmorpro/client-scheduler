document.addEventListener('DOMContentLoaded', () => {
    const modalEl = document.getElementById('auditTypesModal');
    if (!modalEl) return;
    const listEl = document.getElementById('atList');
    const addForm = document.getElementById('atAddForm');
    const newNameInput = document.getElementById('atNewName');
    const newColorInput = document.getElementById('atNewColor');

    let auditTypes = [];

    function notify(message, isError) {
        if (typeof appNotify !== 'undefined') {
            appNotify({ icon: isError ? 'error' : 'success', title: message, timer: isError ? undefined : 1300 });
        } else if (isError) {
            alert(message);
        }
    }

    function render() {
        if (auditTypes.length === 0) {
            listEl.innerHTML = '<div class="settings-empty-row">No audit types yet - add one above.</div>';
            return;
        }
        listEl.innerHTML = auditTypes.map(at => `
            <div class="at-item ${at.is_active ? '' : 'at-inactive'}">
                <span class="at-swatch" style="background:${at.color}"></span>
                <span class="at-name">${at.name}</span>
                <label class="rp-toggle" title="${at.is_active ? 'Active' : 'Inactive'}">
                    <input type="checkbox" class="rp-toggle-input at-active-toggle" data-id="${at.id}" ${at.is_active ? 'checked' : ''}>
                    <span class="rp-toggle-track"><span class="rp-toggle-thumb"></span></span>
                </label>
                <button type="button" class="settings-icon-btn at-delete-btn" data-id="${at.id}" data-name="${at.name}" title="Delete"><i class="bi bi-trash"></i></button>
            </div>
        `).join('');
    }

    async function load() {
        listEl.innerHTML = '<div class="settings-empty-row">Loading...</div>';
        try {
            const res = await fetch('get_audit_types.php?include_inactive=1');
            const data = await res.json();
            if (!data.success) {
                listEl.innerHTML = `<div class="settings-empty-row text-danger">${data.error || 'Could not load audit types.'}</div>`;
                return;
            }
            auditTypes = data.audit_types || [];
            render();
        } catch (err) {
            console.error('Failed to load audit types', err);
            listEl.innerHTML = '<div class="settings-empty-row text-danger">Network error loading audit types.</div>';
        }
    }

    modalEl.addEventListener('show.bs.modal', load);

    addForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const name = newNameInput.value.trim();
        if (!name) return;
        try {
            const res = await fetch('add_audit_type.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ name, color: newColorInput.value })
            });
            const data = await res.json();
            if (data.success) {
                newNameInput.value = '';
                newColorInput.value = '#4f8ef7';
                load();
                document.dispatchEvent(new CustomEvent('auditTypesUpdated'));
            } else {
                notify(data.error || 'Could not add audit type.', true);
            }
        } catch (err) {
            console.error('Failed to add audit type', err);
        }
    });

    listEl.addEventListener('change', async (e) => {
        const toggle = e.target.closest('.at-active-toggle');
        if (!toggle) return;
        const id = toggle.dataset.id;
        const at = auditTypes.find(a => String(a.id) === String(id));
        if (!at) return;
        try {
            const res = await fetch('update_audit_type.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id, name: at.name, color: at.color, is_active: toggle.checked })
            });
            const data = await res.json();
            if (data.success) {
                at.is_active = toggle.checked;
                render();
                document.dispatchEvent(new CustomEvent('auditTypesUpdated'));
            } else {
                toggle.checked = !toggle.checked;
                notify(data.error || 'Could not update audit type.', true);
            }
        } catch (err) {
            toggle.checked = !toggle.checked;
            console.error('Failed to update audit type', err);
        }
    });

    listEl.addEventListener('click', (e) => {
        const deleteBtn = e.target.closest('.at-delete-btn');
        if (!deleteBtn) return;
        const id = deleteBtn.dataset.id;
        const name = deleteBtn.dataset.name;

        const runDelete = () => {
            fetch('delete_audit_type.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id })
            })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        load();
                        document.dispatchEvent(new CustomEvent('auditTypesUpdated'));
                    } else {
                        notify(data.error || 'Could not delete audit type.', true);
                    }
                })
                .catch(err => console.error('Failed to delete audit type', err));
        };

        if (typeof appConfirm !== 'undefined') {
            appConfirm({ icon: 'warning', title: 'Delete this audit type?', text: `"${name}" will be permanently removed.`, confirmText: 'Delete', danger: true })
                .then(confirmed => { if (confirmed) runDelete(); });
        } else if (confirm(`Delete "${name}"?`)) {
            runDelete();
        }
    });
});
