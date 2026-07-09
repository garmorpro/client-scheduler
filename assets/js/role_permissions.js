document.addEventListener('DOMContentLoaded', () => {
    const modalEl = document.getElementById('rolePermissionsModal');
    if (!modalEl) return;

    const tbody = document.getElementById('rpTableBody');
    const saveBtn = document.getElementById('rpSaveBtn');
    const dirtyHint = document.getElementById('rpDirtyHint');
    const permissionKeys = ['manage_employees', 'manage_clients_engagements', 'approve_time_off', 'access_system_settings'];

    const palette = ['#4f8ef7', '#9b6bd6', '#4fbf9f', '#e0994c', '#5fb85f', '#5aa8d6', '#d67aa8', '#7a8fd6'];
    function hashColor(name) {
        let hash = 0;
        for (let i = 0; i < (name || '').length; i++) hash = (hash * 31 + name.charCodeAt(i)) >>> 0;
        return palette[hash % palette.length];
    }

    function roleLabel(role) {
        const r = (role || '').toLowerCase();
        if (r === 'crm_team') return 'CRM Team';
        if (!r) return '';
        return r.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
    }

    let savedSnapshot = '';

    function currentState() {
        return Array.from(tbody.querySelectorAll('tr[data-role]')).map(tr => {
            const entry = { role: tr.dataset.role };
            tr.querySelectorAll('.rp-toggle-input').forEach(cb => {
                entry[cb.dataset.permission] = cb.checked;
            });
            return entry;
        });
    }

    function checkDirty() {
        const isDirty = JSON.stringify(currentState()) !== savedSnapshot;
        saveBtn.disabled = !isDirty;
        dirtyHint.textContent = isDirty ? 'Unsaved changes' : 'No changes yet';
        dirtyHint.classList.toggle('is-dirty', isDirty);
    }

    function renderRows(permissions) {
        const adminRow = tbody.querySelector('tr');
        tbody.innerHTML = '';
        tbody.appendChild(adminRow);

        permissions.forEach(p => {
            const tr = document.createElement('tr');
            tr.dataset.role = p.role;
            tr.innerHTML = `
                <td>
                    <span class="rp-role-cell">
                        <span class="rp-role-avatar" style="background-color:${hashColor(p.role)};">${roleLabel(p.role).charAt(0)}</span>
                        <span class="rp-role-name">${roleLabel(p.role)}</span>
                    </span>
                </td>
                ${permissionKeys.map(key => `
                    <td class="rp-toggle-cell">
                        <label class="rp-toggle">
                            <input type="checkbox" class="rp-toggle-input" data-permission="${key}" ${p[key] ? 'checked' : ''}>
                            <span class="rp-toggle-track"><span class="rp-toggle-thumb"></span></span>
                        </label>
                    </td>
                `).join('')}
            `;
            tbody.appendChild(tr);
        });

        savedSnapshot = JSON.stringify(currentState());
        checkDirty();
    }

    tbody.addEventListener('change', (e) => {
        if (e.target.matches('.rp-toggle-input')) checkDirty();
    });

    modalEl.addEventListener('show.bs.modal', async () => {
        tbody.querySelectorAll('tr[data-role]').forEach(tr => tr.remove());
        try {
            const res = await fetch('get_role_permissions.php');
            const data = await res.json();
            renderRows(data.permissions || []);
        } catch (err) {
            console.error('Failed to load role permissions', err);
        }
    });

    saveBtn.addEventListener('click', async () => {
        const permissions = currentState();

        try {
            const res = await fetch('update_role_permissions.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ permissions })
            });
            const result = await res.json();
            if (result.success) {
                savedSnapshot = JSON.stringify(permissions);
                checkDirty();
                modalEl.querySelector('.btn-close').click();
                if (typeof Swal !== 'undefined') {
                    Swal.fire({ icon: 'success', title: 'Permissions saved', timer: 1400, showConfirmButton: false });
                }
            } else {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({ icon: 'error', title: 'Could not save permissions', text: result.error || 'Please try again.' });
                } else {
                    alert('Error: ' + (result.error || 'Could not save permissions.'));
                }
            }
        } catch (err) {
            console.error('Failed to save role permissions', err);
        }
    });
});
