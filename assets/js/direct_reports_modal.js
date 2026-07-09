document.addEventListener('DOMContentLoaded', () => {
    const modalEl = document.getElementById('directReportsModal');
    if (!modalEl) return;

    const listEl = document.getElementById('drList');
    const searchInput = document.getElementById('drSearchInput');
    const selectedCountEl = document.getElementById('drSelectedCount');
    const saveBtn = document.getElementById('drSaveBtn');

    let currentManagerId = null;
    let employees = [];

    function roleLabel(role) {
        const r = (role || '').toLowerCase();
        if (r === 'crm_team') return 'CRM Team';
        if (!r) return '';
        return r.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
    }

    function updateSelectedCount() {
        const count = listEl.querySelectorAll('input[type="checkbox"]:checked').length;
        selectedCountEl.textContent = `${count} selected`;
    }

    function renderList(filterText) {
        const term = (filterText || '').trim().toLowerCase();
        const filtered = term
            ? employees.filter(e => e.full_name.toLowerCase().includes(term))
            : employees;

        if (filtered.length === 0) {
            listEl.innerHTML = '<div class="eng-empty">No employees found</div>';
            return;
        }

        listEl.innerHTML = filtered.map(e => {
            const isMine = e.manager_id === currentManagerId;
            const elsewhere = !isMine && e.manager_id ? `Currently reports to ${e.manager_name}` : '';
            return `
                <label class="dr-row">
                    <input type="checkbox" value="${e.user_id}" ${isMine ? 'checked' : ''}>
                    <span class="dr-row-info">
                        <span class="dr-row-name">${e.full_name}</span>
                        <span class="dr-row-role">${roleLabel(e.role)}</span>
                    </span>
                    ${elsewhere ? `<span class="dr-row-elsewhere">${elsewhere}</span>` : ''}
                </label>
            `;
        }).join('');

        updateSelectedCount();
    }

    listEl.addEventListener('change', (e) => {
        if (e.target.matches('input[type="checkbox"]')) updateSelectedCount();
    });

    searchInput.addEventListener('input', () => renderList(searchInput.value));

    modalEl.addEventListener('show.bs.modal', async (e) => {
        const btn = e.relatedTarget;
        if (!btn) return;
        currentManagerId = parseInt(btn.dataset.managerId, 10);
        document.getElementById('drManagerName').textContent = btn.dataset.managerName || '';
        searchInput.value = '';
        listEl.innerHTML = '<div class="eng-empty">Loading...</div>';

        try {
            const res = await fetch('get_assignable_employees.php');
            const data = await res.json();
            employees = data.employees || [];
            renderList('');
        } catch (err) {
            console.error('Failed to load employees', err);
            listEl.innerHTML = '<div class="eng-empty">Could not load employees</div>';
        }
    });

    saveBtn.addEventListener('click', async () => {
        const userIds = Array.from(listEl.querySelectorAll('input[type="checkbox"]:checked')).map(cb => cb.value);

        try {
            const res = await fetch('set_direct_reports.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ manager_id: currentManagerId, user_ids: userIds })
            });
            const result = await res.json();
            if (result.success) {
                modalEl.querySelector('.btn-close').click();
                location.reload();
            } else {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({ icon: 'error', title: 'Could not save direct reports', text: result.error || 'Please try again.' });
                } else {
                    alert('Error: ' + (result.error || 'Could not save direct reports.'));
                }
            }
        } catch (err) {
            console.error('Failed to save direct reports', err);
        }
    });
});
