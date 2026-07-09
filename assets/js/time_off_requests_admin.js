document.addEventListener('DOMContentLoaded', () => {
    const tableBody = document.getElementById('torTableBody');
    let allRequests = [];
    let activeTab = 'all';

    function initials(name) {
        return (name || '?').trim().split(/\s+/).slice(0, 2).map(w => w[0].toUpperCase()).join('');
    }
    const palette = ['#4f8ef7', '#9b6bd6', '#4fbf9f', '#e0994c', '#5fb85f', '#5aa8d6', '#d67aa8', '#7a8fd6'];
    function hashColor(name) {
        let hash = 0;
        for (let i = 0; i < (name || '').length; i++) hash = (hash * 31 + name.charCodeAt(i)) >>> 0;
        return palette[hash % palette.length];
    }
    function statusPillClass(status) {
        if (status === 'approved') return 'confirmed';
        if (status === 'denied') return 'denied';
        return 'pending';
    }
    function statusLabel(status) {
        return status.charAt(0).toUpperCase() + status.slice(1);
    }
    function formatDate(dateString) {
        if (!dateString) return '-';
        const d = new Date(dateString.length <= 10 ? dateString + 'T00:00:00' : dateString);
        if (isNaN(d)) return '-';
        return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
    }

    function notify(icon, title, text) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({ icon, title, text });
        } else {
            alert(title + (text ? ': ' + text : ''));
        }
    }

    function render() {
        const pendingCount = allRequests.filter(r => r.status === 'pending').length;
        document.getElementById('torAllCount').textContent = allRequests.length;
        document.getElementById('torPendingCount').textContent = pendingCount;

        const rows = activeTab === 'pending' ? allRequests.filter(r => r.status === 'pending') : allRequests;

        if (rows.length === 0) {
            tableBody.innerHTML = `<tr><td colspan="7" class="text-center">${activeTab === 'pending' ? 'Nothing awaiting approval' : 'No time off requests'}</td></tr>`;
            return;
        }

        tableBody.innerHTML = '';
        rows.forEach(r => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>
                    <div class="client-cell">
                        <div class="client-tile" style="background-color:${hashColor(r.full_name)};">${initials(r.full_name)}</div>
                        <span class="client-name">${r.full_name}</span>
                    </div>
                </td>
                <td>${formatDate(r.date)}</td>
                <td class="num"><span class="hours-value">${r.hours}h</span></td>
                <td>${r.reason ? r.reason : '<span class="text-muted">-</span>'}</td>
                <td>
                    <span class="eng-status-pill ${statusPillClass(r.status)}">
                        <span class="dot"></span>${statusLabel(r.status)}
                    </span>
                </td>
                <td><span class="client-onboarded-text">${formatDate(r.created)}</span></td>
                <td class="num">
                    <div class="client-row-actions">
                        ${r.status === 'pending' ? `
                            <button type="button" class="client-icon-btn timeoff-approve-btn" title="Approve" data-timeoff-id="${r.timeoff_id}" data-action="approve">
                                <i class="bi bi-check-lg"></i>
                            </button>
                            <button type="button" class="client-icon-btn timeoff-deny-btn" title="Deny" data-timeoff-id="${r.timeoff_id}" data-action="deny">
                                <i class="bi bi-x-lg"></i>
                            </button>
                        ` : ''}
                        <button type="button" class="client-icon-btn danger" title="Remove" data-timeoff-id="${r.timeoff_id}" data-action="delete">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </td>
            `;
            tableBody.appendChild(tr);
        });

        tableBody.querySelectorAll('[data-action="approve"], [data-action="deny"]').forEach(btn => {
            btn.addEventListener('click', () => reviewRequest(btn.dataset.timeoffId, btn.dataset.action));
        });
        tableBody.querySelectorAll('[data-action="delete"]').forEach(btn => {
            btn.addEventListener('click', () => deleteRequest(btn.dataset.timeoffId));
        });
    }

    async function loadRequests() {
        tableBody.innerHTML = '<tr><td colspan="7" class="text-center">Loading...</td></tr>';
        try {
            const res = await fetch('get_all_time_off_requests.php');
            const data = await res.json();
            allRequests = data.requests || [];
            render();
        } catch (err) {
            console.error('Failed to load time off requests', err);
            tableBody.innerHTML = '<tr><td colspan="7" class="text-center text-danger">Could not load requests</td></tr>';
        }
    }

    function reviewRequest(timeoffId, action) {
        const run = async () => {
            try {
                const res = await fetch('review_time_off_request.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ timeoff_id: timeoffId, action })
                });
                const data = await res.json();
                if (data.success) {
                    loadRequests();
                } else {
                    notify('error', 'Could not update request', data.error || 'Please try again.');
                }
            } catch (err) {
                console.error('Failed to review request', err);
            }
        };

        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: action === 'approve' ? 'question' : 'warning',
                title: action === 'approve' ? 'Approve this request?' : 'Deny this request?',
                showCancelButton: true,
                confirmButtonText: action === 'approve' ? 'Approve' : 'Deny',
                confirmButtonColor: action === 'approve' ? '#2f9e57' : '#c0392b'
            }).then(result => { if (result.isConfirmed) run(); });
        } else {
            run();
        }
    }

    function deleteRequest(timeoffId) {
        const run = async () => {
            try {
                const res = await fetch('delete_time_off_request.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ timeoff_id: timeoffId })
                });
                const data = await res.json();
                if (data.success) {
                    loadRequests();
                } else {
                    notify('error', 'Could not remove request', data.error || 'Please try again.');
                }
            } catch (err) {
                console.error('Failed to delete request', err);
            }
        };

        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'warning', title: 'Remove this request?', text: 'This permanently deletes the request record.',
                showCancelButton: true, confirmButtonText: 'Remove', confirmButtonColor: '#c0392b'
            }).then(result => { if (result.isConfirmed) run(); });
        } else if (confirm('Remove this request?')) {
            run();
        }
    }

    document.querySelectorAll('.tor-tab').forEach(tab => {
        tab.addEventListener('click', () => {
            document.querySelectorAll('.tor-tab').forEach(t => t.classList.remove('active'));
            tab.classList.add('active');
            activeTab = tab.dataset.torTab;
            render();
        });
    });

    loadRequests();
});
