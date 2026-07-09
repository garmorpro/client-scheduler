document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('requestTimeOffForm');
    const modalEl = document.getElementById('requestTimeOffModal');
    const tableBody = document.getElementById('myRequestsTableBody');
    const hintEl = document.getElementById('myRequestsHint');

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
        const d = new Date(dateString + 'T00:00:00');
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

    async function loadMyRequests() {
        tableBody.innerHTML = '<tr><td colspan="6" class="text-center">Loading...</td></tr>';
        try {
            const res = await fetch('get_my_time_off_requests.php');
            const data = await res.json();
            const requests = data.requests || [];

            hintEl.textContent = `${requests.length} request${requests.length === 1 ? '' : 's'}`;

            if (requests.length === 0) {
                tableBody.innerHTML = '<tr><td colspan="6" class="text-center">No time off requests yet</td></tr>';
                return;
            }

            tableBody.innerHTML = '';
            requests.forEach(r => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td><span class="client-name">${formatDate(r.date)}</span></td>
                    <td class="num"><span class="hours-value">${r.hours}h</span></td>
                    <td>${r.reason ? r.reason : '<span class="text-muted">-</span>'}</td>
                    <td>
                        <span class="eng-status-pill ${statusPillClass(r.status)}">
                            <span class="dot"></span>${statusLabel(r.status)}
                        </span>
                    </td>
                    <td><span class="client-onboarded-text">${formatDate(r.created)}</span></td>
                    <td class="num">
                        ${r.status === 'pending' ? `
                            <div class="client-row-actions">
                                <button type="button" class="timeoff-cancel-btn" title="Cancel request" data-timeoff-id="${r.timeoff_id}">
                                    <i class="bi bi-x-lg"></i>
                                </button>
                            </div>
                        ` : ''}
                    </td>
                `;
                tableBody.appendChild(tr);
            });

            tableBody.querySelectorAll('.timeoff-cancel-btn').forEach(btn => {
                btn.addEventListener('click', () => cancelRequest(btn.dataset.timeoffId));
            });
        } catch (err) {
            console.error('Failed to load time off requests', err);
            tableBody.innerHTML = '<tr><td colspan="6" class="text-center text-danger">Could not load requests</td></tr>';
        }
    }

    function cancelRequest(timeoffId) {
        const run = async () => {
            try {
                const res = await fetch('delete_time_off_request.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ timeoff_id: timeoffId })
                });
                const data = await res.json();
                if (data.success) {
                    loadMyRequests();
                } else {
                    notify('error', 'Could not cancel request', data.error || 'Please try again.');
                }
            } catch (err) {
                console.error('Failed to cancel request', err);
            }
        };

        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'warning', title: 'Cancel this request?',
                showCancelButton: true, confirmButtonText: 'Cancel Request', confirmButtonColor: '#c0392b'
            }).then(result => { if (result.isConfirmed) run(); });
        } else if (confirm('Cancel this request?')) {
            run();
        }
    }

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const payload = {
            date: document.getElementById('tor_date').value,
            hours: document.getElementById('tor_hours').value,
            reason: document.getElementById('tor_reason').value
        };

        try {
            const res = await fetch('request_time_off.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            const data = await res.json();
            if (data.success) {
                const modalInstance = bootstrap.Modal.getInstance(modalEl);
                if (modalInstance) modalInstance.hide();
                form.reset();
                document.getElementById('tor_hours').value = 8;
                loadMyRequests();
            } else {
                notify('error', 'Could not submit request', data.error || 'Please try again.');
            }
        } catch (err) {
            console.error('Failed to submit time off request', err);
        }
    });

    modalEl.addEventListener('hidden.bs.modal', () => form.reset());

    loadMyRequests();
});
