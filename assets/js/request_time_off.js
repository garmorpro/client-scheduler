document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('requestTimeOffForm');
    const modalEl = document.getElementById('requestTimeOffModal');
    const modal = new bootstrap.Modal(modalEl);
    const modalTitle = document.getElementById('requestTimeOffModalTitle');
    const submitBtn = form.querySelector('.eng-edit-btn-save');
    const tableBody = document.getElementById('myRequestsTableBody');
    const hintEl = document.getElementById('myRequestsHint');
    const daysContainer = document.getElementById('torDaysContainer');
    const addDayBtn = document.getElementById('torAddDayBtn');

    const viewModalEl = document.getElementById('viewMyTimeOffModal');
    const viewModal = new bootstrap.Modal(viewModalEl);

    let editingRequestGroup = null;
    let myRequests = [];
    let activeViewRequest = null;

    function statusPillClass(status) {
        if (status === 'approved') return 'confirmed';
        if (status === 'denied') return 'denied';
        if (status === 'changes_requested') return 'not-confirmed';
        return 'pending';
    }
    function statusLabel(status) {
        if (status === 'changes_requested') return 'Changes Requested';
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

    // --- New/Edit Request modal: dynamic day rows ---
    function dayRowTemplate(date, hours) {
        const row = document.createElement('div');
        row.className = 'hol-day-row';
        row.innerHTML = `
            <input type="date" class="eng-edit-input date-input" value="${date || ''}" required>
            <input type="number" min="1" max="24" step="0.5" class="eng-edit-input hours-input" value="${hours || 8}" required>
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

    function openNewRequestModal() {
        editingRequestGroup = null;
        modalTitle.textContent = 'Request Time Off';
        submitBtn.textContent = 'Submit Request';
        form.reset();
        resetDayRows();
        modal.show();
    }

    function openEditRequestModal(r) {
        editingRequestGroup = r.request_group;
        modalTitle.textContent = 'Edit Request';
        submitBtn.textContent = 'Resubmit for Approval';
        document.getElementById('tor_category').value = r.category;
        document.getElementById('tor_reason').value = r.reason || '';
        daysContainer.innerHTML = '';
        [...r.days].sort((a, b) => a.date.localeCompare(b.date)).forEach(d => {
            daysContainer.appendChild(dayRowTemplate(d.date, d.hours));
        });
        modal.show();
    }

    document.getElementById('newTimeOffRequestBtn').addEventListener('click', (e) => {
        e.preventDefault();
        openNewRequestModal();
    });

    // --- View modal ---
    function openViewModal(r) {
        activeViewRequest = r;

        document.getElementById('vmtoTitle').textContent = `${categoryLabel(r.category)} Request`;
        const catEl = document.getElementById('vmtoCategory');
        catEl.className = `category-pill ${r.category}`;
        catEl.textContent = categoryLabel(r.category);

        const statusEl = document.getElementById('vmtoStatus');
        statusEl.className = `eng-status-pill ${statusPillClass(r.status)}`;
        document.getElementById('vmtoStatusText').textContent = statusLabel(r.status);

        document.getElementById('vmtoTotalHours').textContent = `${r.total_hours}h`;
        document.getElementById('vmtoSubmitted').textContent = formatDate(r.created);
        document.getElementById('vmtoReason').textContent = r.reason || 'No reason provided.';

        const daysList = document.getElementById('vmtoDaysList');
        daysList.innerHTML = [...r.days].sort((a, b) => a.date.localeCompare(b.date)).map(d => `
            <div class="eng-vm-emp-row">
                <div class="eng-vm-emp-info">
                    <div class="eng-vm-emp-name">${formatDate(d.date)}</div>
                </div>
                <div class="eng-vm-emp-hours">${d.hours}h</div>
            </div>
        `).join('');

        const commentWrap = document.getElementById('vmtoCommentWrap');
        if (r.reviewer_comment) {
            commentWrap.style.display = '';
            document.getElementById('vmtoComment').textContent = r.reviewer_comment;
        } else {
            commentWrap.style.display = 'none';
        }

        const cancelBtn = document.getElementById('vmtoCancelBtn');
        const editBtn = document.getElementById('vmtoEditBtn');
        const canCancel = r.status === 'pending' || r.status === 'changes_requested';
        cancelBtn.style.display = canCancel ? '' : 'none';
        editBtn.style.display = r.status === 'changes_requested' ? '' : 'none';

        viewModal.show();
    }

    document.getElementById('vmtoEditBtn').addEventListener('click', () => {
        if (!activeViewRequest) return;
        viewModal.hide();
        openEditRequestModal(activeViewRequest);
    });

    document.getElementById('vmtoCancelBtn').addEventListener('click', () => {
        if (!activeViewRequest) return;
        cancelRequest(activeViewRequest.request_group, () => viewModal.hide());
    });

    // --- My Requests table ---
    async function loadMyRequests() {
        tableBody.innerHTML = '<tr><td colspan="6" class="text-center">Loading...</td></tr>';
        try {
            const res = await fetch('get_my_time_off_requests.php');
            const data = await res.json();
            myRequests = data.requests || [];

            hintEl.textContent = `${myRequests.length} request${myRequests.length === 1 ? '' : 's'}`;

            if (myRequests.length === 0) {
                tableBody.innerHTML = '<tr><td colspan="6" class="text-center">No time off requests yet</td></tr>';
                return;
            }

            tableBody.innerHTML = '';
            myRequests.forEach(r => {
                const needsAction = r.status === 'changes_requested';
                const tr = document.createElement('tr');
                if (needsAction) tr.style.background = 'var(--surface-hover, #f1f4f3)';
                tr.innerHTML = `
                    <td><span class="client-name">${formatDateRange(r.days)}</span></td>
                    <td><span class="category-pill ${r.category}">${categoryLabel(r.category)}</span></td>
                    <td class="num"><span class="hours-value">${r.total_hours}h</span></td>
                    <td>
                        <span class="eng-status-pill ${statusPillClass(r.status)}">
                            <span class="dot"></span>${statusLabel(r.status)}
                        </span>
                        ${needsAction ? `<div style="font-size:11px; margin-top:3px; color:#285a80; font-weight:600;"><i class="bi bi-arrow-repeat"></i> Action needed</div>` : ''}
                    </td>
                    <td><span class="client-onboarded-text">${formatDate(r.created)}</span></td>
                    <td class="num">
                        <div class="client-row-actions">
                            <button type="button" class="client-icon-btn" title="View" data-view-group="${r.request_group}">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </td>
                `;
                tableBody.appendChild(tr);
            });

            tableBody.querySelectorAll('[data-view-group]').forEach(btn => {
                btn.addEventListener('click', () => {
                    const r = myRequests.find(req => req.request_group === btn.dataset.viewGroup);
                    if (r) openViewModal(r);
                });
            });
        } catch (err) {
            console.error('Failed to load time off requests', err);
            tableBody.innerHTML = '<tr><td colspan="6" class="text-center text-danger">Could not load requests</td></tr>';
        }
    }

    function cancelRequest(requestGroup, onSuccess) {
        const run = async () => {
            try {
                const res = await fetch('delete_time_off_request.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ request_group: requestGroup })
                });
                const data = await res.json();
                if (data.success) {
                    if (onSuccess) onSuccess();
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

        const isEdit = !!editingRequestGroup;
        if (isEdit) payload.request_group = editingRequestGroup;

        try {
            const res = await fetch(isEdit ? 'update_time_off_request.php' : 'request_time_off.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            const data = await res.json();
            if (data.success) {
                modal.hide();
                loadMyRequests();
            } else {
                notify('error', isEdit ? 'Could not resubmit request' : 'Could not submit request', data.error || 'Please try again.');
            }
        } catch (err) {
            console.error('Failed to submit time off request', err);
        }
    });

    modalEl.addEventListener('hidden.bs.modal', () => {
        editingRequestGroup = null;
        form.reset();
        resetDayRows();
    });

    loadMyRequests();
});
