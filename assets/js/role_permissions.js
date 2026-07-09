document.addEventListener('DOMContentLoaded', () => {
    const modalEl = document.getElementById('rolePermissionsModal');
    if (!modalEl) return;

    const tbody = document.getElementById('rpTableBody');
    const saveBtn = document.getElementById('rpSaveBtn');
    const permissionKeys = ['manage_employees', 'manage_clients_engagements', 'approve_time_off', 'access_system_settings'];

    function roleLabel(role) {
        const r = (role || '').toLowerCase();
        if (r === 'crm_team') return 'CRM Team';
        if (!r) return '';
        return r.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
    }

    function renderRows(permissions) {
        const adminRow = tbody.querySelector('tr');
        tbody.innerHTML = '';
        tbody.appendChild(adminRow);

        permissions.forEach(p => {
            const tr = document.createElement('tr');
            tr.dataset.role = p.role;
            tr.innerHTML = `
                <td><span class="rp-role-name">${roleLabel(p.role)}</span></td>
                ${permissionKeys.map(key => `
                    <td class="rp-checkbox-cell">
                        <input type="checkbox" class="rp-checkbox" data-permission="${key}" ${p[key] ? 'checked' : ''}>
                    </td>
                `).join('')}
            `;
            tbody.appendChild(tr);
        });
    }

    modalEl.addEventListener('show.bs.modal', async () => {
        try {
            const res = await fetch('get_role_permissions.php');
            const data = await res.json();
            renderRows(data.permissions || []);
        } catch (err) {
            console.error('Failed to load role permissions', err);
        }
    });

    saveBtn.addEventListener('click', async () => {
        const permissions = Array.from(tbody.querySelectorAll('tr[data-role]')).map(tr => {
            const entry = { role: tr.dataset.role };
            tr.querySelectorAll('.rp-checkbox').forEach(cb => {
                entry[cb.dataset.permission] = cb.checked;
            });
            return entry;
        });

        try {
            const res = await fetch('update_role_permissions.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ permissions })
            });
            const result = await res.json();
            if (result.success) {
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
