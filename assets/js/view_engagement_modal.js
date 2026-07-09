document.addEventListener('DOMContentLoaded', () => {
    const modalEl = document.getElementById('viewEngagementModal');
    if (!modalEl) return;
    const modal = new bootstrap.Modal(modalEl);
    const modalBody = document.getElementById('viewEngagementModalBody');

    function statusClass(status) {
        return (status || '').replace(/_/g, '-');
    }
    function statusLabel(status) {
        if (status === 'not_confirmed') return 'Not Confirmed';
        return status ? status.charAt(0).toUpperCase() + status.slice(1) : '';
    }

    document.querySelectorAll('.view-engagement-btn').forEach(btn => {
        btn.addEventListener('click', async (e) => {
            e.preventDefault();
            const engagementId = btn.dataset.engagementId;
            const avatarColor = btn.dataset.avatarColor || '#4f8ef7';
            const initials = btn.dataset.initials || '?';
            if (!engagementId) return;

            modalBody.innerHTML = '<div class="text-center text-muted py-4">Loading...</div>';
            modal.show();

            try {
                const res = await fetch(`engagement-details.php?id=${encodeURIComponent(engagementId)}`);
                const data = await res.json();

                const budgeted = data.budgeted_hours || 0;
                const allocated = data.total_hours || 0;
                const isOver = allocated > budgeted;
                const employees = data.assigned_employees || [];

                const pct = budgeted > 0 ? (allocated / budgeted) * 100 : 0;
                const barWidth = Math.min(100, pct);
                const overHours = allocated - budgeted;
                let utilColor;
                if (isOver) utilColor = 'red';
                else if (pct >= 75) utilColor = 'green';
                else utilColor = 'yellow';

                const empRowsHtml = employees.length > 0
                    ? employees.map(emp => `
                        <div class="eng-vm-emp-row">
                            <div class="eng-vm-emp-avatar">${(emp.name || '?').trim().split(/\s+/).slice(0, 2).map(w => w[0].toUpperCase()).join('')}</div>
                            <div class="eng-vm-emp-info">
                                <div class="eng-vm-emp-name">${emp.name}</div>
                                <div class="eng-vm-emp-role">${emp.role}</div>
                            </div>
                            <div class="eng-vm-emp-hours">${emp.hours}h</div>
                        </div>
                    `).join('')
                    : '<div class="eng-vm-emp-row"><div class="eng-vm-emp-info"><div class="eng-vm-emp-name text-muted">No employees assigned yet</div></div></div>';

                modalBody.innerHTML = `
                    <div class="eng-vm-header">
                        <div class="eng-vm-client-row">
                            <div class="eng-vm-tile" style="background-color:${avatarColor};">${initials}</div>
                            <div>
                                <div class="eng-vm-client-name">${data.client_name || ''}</div>
                                <span class="eng-status-pill ${statusClass(data.status)}"><span class="dot"></span>${statusLabel(data.status)}</span>
                            </div>
                        </div>
                    </div>
                    <div class="eng-vm-body">
                        <div class="eng-vm-stat-row">
                            <div class="eng-vm-stat-card">
                                <div class="eng-vm-stat-title">Budgeted</div>
                                <div class="eng-vm-stat-value">${budgeted}h</div>
                            </div>
                            <div class="eng-vm-stat-card">
                                <div class="eng-vm-stat-title">Allocated</div>
                                <div class="eng-vm-stat-value ${isOver ? 'over' : ''}">${allocated}h</div>
                            </div>
                            <div class="eng-vm-stat-card">
                                <div class="eng-vm-stat-title">Employees</div>
                                <div class="eng-vm-stat-value">${employees.length}</div>
                            </div>
                            <div class="eng-vm-stat-card">
                                <div class="eng-vm-stat-title">Manager</div>
                                <div class="eng-vm-stat-value text" title="${data.manager || '-'}">${data.manager || '-'}</div>
                            </div>
                        </div>
                        <div style="margin-bottom: 16px;">
                            <div class="eng-util-cell">
                                <div class="eng-util-track">
                                    <div class="eng-util-fill ${utilColor}" style="width: ${barWidth}%"></div>
                                </div>
                                <span class="eng-util-pct ${utilColor}">${Math.round(pct)}%</span>
                            </div>
                            ${isOver ? `<div class="eng-util-over">+${overHours}h over</div>` : ''}
                        </div>
                        <div class="eng-vm-section-title">Assigned Employees</div>
                        <div class="eng-vm-emp-list">${empRowsHtml}</div>
                    </div>
                `;
            } catch (err) {
                console.error('Failed to load engagement details', err);
                modalBody.innerHTML = '<div class="text-center text-danger py-4">Could not load engagement details.</div>';
            }
        });
    });
});
