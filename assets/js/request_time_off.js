document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('requestTimeOffForm');
    const modalEl = document.getElementById('requestTimeOffModal');
    const tableBody = document.getElementById('myRequestsTableBody');
    const hintEl = document.getElementById('myRequestsHint');
    const daysContainer = document.getElementById('torDaysContainer');
    const addDayBtn = document.getElementById('torAddDayBtn');

    function statusPillClass(status) {
        if (status === 'approved') return 'confirmed';
        if (status === 'denied') return 'denied';
        return 'pending';
    }
    function statusLabel(status) {
        return status.charAt(0).toUpperCase() + status.slice(1);
    }
    function categoryLabel(category) {
        return category.charAt(0).toUpperCase() + category.slice(1);
    }
    function formatDate(dateString) {
        if (!dateString) return '-';
        const d = new Date(dateString.length <= 10 ? dateString + 'T00:00:00' : dateString);
        if (isNaN(d)) return '-';
        return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
    }
    function formatDateRange(days) {
        if (days.length === 1) return formatDate(days[0].date);
        const sorted = [...days].sort((a, b) => a.date.localeCompare(b.date));
        return `${formatDate(sorted[0].date)} &ndash; ${formatDate(sorted[sorted.length - 1].date)} <span class="text-muted">(${days.length} days)</span>`;
    }

    function notify(icon, title, text) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({ icon, title, text });
        } else {
            alert(title + (text ? ': ' + text : ''));
        }
    }

    // --- New Request modal: dynamic day rows ---
    function dayRowTemplate() {
        const row = document.createElement('div');
        row.className = 'hol-day-row';
        row.innerHTML = `
            <input type="date" class="eng-edit-input date-input" required>
            <input type="number" min="1" max="24" step="0.5" class="eng-edit-input hours-input" value="8" required>
            <button type="button" class="hol-day-remove" title="Remove"><i class="bi bi-x-lg"></i></button>
        `;
        row.querySelector('.hol-day-remove').addEventListener('click', () => {
            if (daysContainer.querySelectorAll('.hol-day-row').length > 1) row.remove();
        });
        return row;
    }

    function resetDayRows() {
        daysContainer.innerHTML = '';
        daysContainer.appendChild(dayRowTemplate());
    }

    addDayBtn.addEventListener('click', () => daysContainer.appendChild(dayRowTemplate()));
    resetDayRows();

    // --- My Requests table ---
    async function loadMyRequests() {
        tableBody.innerHTML = '<tr><td colspan="7" class="text-center">Loading...</td></tr>';
        try {
            const res = await fetch('get_my_time_off_requests.php');
            const data = await res.json();
            const requests = data.requests || [];

            hintEl.textContent = `${requests.length} request${requests.length === 1 ? '' : 's'}`;

            if (requests.length === 0) {
                tableBody.innerHTML = '<tr><td colspan="7" class="text-center">No time off requests yet</td></tr>';
                return;
            }

            tableBody.innerHTML = '';
            requests.forEach(r => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td><span class="client-name">${formatDateRange(r.days)}</span></td>
                    <td><span class="category-pill ${r.category}">${categoryLabel(r.category)}</span></td>
                    <td class="num"><span class="hours-value">${r.total_hours}h</span></td>
                    <td>${r.reason ? r.reason : '<span class="text-muted">-</span>'}</td>
                    <td>
                        <span class="eng-status-pill ${statusPillClass(r.status)}">
                            <span class="dot"></span>${statusLabel(r.status)}
                        </span>
                        ${r.reviewer_comment ? `<div class="text-muted" style="font-size:11px; margin-top:3px;" title="${r.reviewer_comment}"><i class="bi bi-chat-left-text"></i> ${r.reviewer_comment}</div>` : ''}
                    </td>
                    <td><span class="client-onboarded-text">${formatDate(r.created)}</span></td>
                    <td class="num">
                        ${r.status === 'pending' ? `
                            <div class="client-row-actions">
                                <button type="button" class="timeoff-cancel-btn" title="Cancel request" data-request-group="${r.request_group}">
                                    <i class="bi bi-x-lg"></i>
                                </button>
                            </div>
                        ` : ''}
                    </td>
                `;
                tableBody.appendChild(tr);
            });

            tableBody.querySelectorAll('.timeoff-cancel-btn').forEach(btn => {
                btn.addEventListener('click', () => cancelRequest(btn.dataset.requestGroup));
            });
        } catch (err) {
            console.error('Failed to load time off requests', err);
            tableBody.innerHTML = '<tr><td colspan="7" class="text-center text-danger">Could not load requests</td></tr>';
        }
    }

    function cancelRequest(requestGroup) {
        const run = async () => {
            try {
                const res = await fetch('delete_time_off_request.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ request_group: requestGroup })
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
        const days = Array.from(daysContainer.querySelectorAll('.hol-day-row')).map(row => ({
            date: row.querySelector('.date-input').value,
            hours: row.querySelector('.hours-input').value
        }));

        const payload = {
            category: document.getElementById('tor_category').value,
            reason: document.getElementById('tor_reason').value,
            days
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
                resetDayRows();
                loadMyRequests();
            } else {
                notify('error', 'Could not submit request', data.error || 'Please try again.');
            }
        } catch (err) {
            console.error('Failed to submit time off request', err);
        }
    });

    modalEl.addEventListener('hidden.bs.modal', () => {
        form.reset();
        resetDayRows();
    });

    loadMyRequests();
});
