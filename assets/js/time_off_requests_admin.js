document.addEventListener('DOMContentLoaded', () => {
    const tableBody = document.getElementById('torTableBody');
    const reviewModalEl = document.getElementById('reviewTimeOffModal');
    const reviewModal = new bootstrap.Modal(reviewModalEl);
    let allRequests = [];
    let activeTab = 'all';
    let activeRequest = null;

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
    function formatDateTime(dateString) {
        if (!dateString) return '-';
        const d = new Date(dateString.length <= 10 ? dateString + 'T00:00:00' : dateString.replace(' ', 'T'));
        if (isNaN(d)) return '-';
        return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) +
            ' at ' + d.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' });
    }
    function escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str || '';
        return div.innerHTML;
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
                <td>${formatDateRange(r.days)}</td>
                <td><span class="category-pill ${r.category}">${categoryLabel(r.category)}</span></td>
                <td class="num"><span class="hours-value">${r.total_hours}h</span></td>
                <td>
                    <span class="eng-status-pill ${statusPillClass(r.status)}">
                        <span class="dot"></span>${statusLabel(r.status)}
                    </span>
                </td>
                <td><span class="client-onboarded-text">${formatDate(r.created)}</span></td>
                <td class="num">
                    <div class="client-row-actions">
                        <button type="button" class="client-icon-btn" title="Review" data-request-group="${r.request_group}">
                            <i class="bi bi-eye"></i>
                        </button>
                        <button type="button" class="client-icon-btn danger" title="Remove" data-delete-group="${r.request_group}">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </td>
            `;
            tableBody.appendChild(tr);
        });

        tableBody.querySelectorAll('[data-request-group]').forEach(btn => {
            btn.addEventListener('click', () => openReview(btn.dataset.requestGroup));
        });
        tableBody.querySelectorAll('[data-delete-group]').forEach(btn => {
            btn.addEventListener('click', () => deleteRequest(btn.dataset.deleteGroup));
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

    async function loadCommentHistory(r) {
        const wrap = document.getElementById('rtoHistoryWrap');
        const list = document.getElementById('rtoCommentHistory');
        wrap.style.display = '';
        list.innerHTML = '<div class="text-muted" style="font-size:12px;">Loading...</div>';

        const entries = [];
        if (r.reason) {
            entries.push({ full_name: r.full_name, comment: r.reason, created: r.created });
        }

        try {
            const res = await fetch(`get_time_off_comments.php?request_group=${encodeURIComponent(r.request_group)}`);
            const data = await res.json();
            if (data.success) entries.push(...data.comments);
        } catch (err) {
            console.error('Failed to load comment history', err);
        }

        if (entries.length === 0) {
            wrap.style.display = 'none';
            list.innerHTML = '';
            return;
        }

        list.innerHTML = entries.map(e => `
            <div class="timeoff-comment-item">
                <div class="timeoff-comment-avatar" style="background-color:${hashColor(e.full_name)};">${initials(e.full_name)}</div>
                <div class="timeoff-comment-body">
                    <div class="timeoff-comment-meta">
                        <span class="timeoff-comment-name">${e.full_name}</span>
                        <span class="timeoff-comment-time">${formatDateTime(e.created)}</span>
                    </div>
                    <p class="timeoff-comment-text">${escapeHtml(e.comment)}</p>
                </div>
            </div>
        `).join('');
    }

    function openReview(requestGroup) {
        const r = allRequests.find(req => req.request_group === requestGroup);
        if (!r) return;
        activeRequest = r;

        document.getElementById('rtoAvatar').style.backgroundColor = hashColor(r.full_name);
        document.getElementById('rtoAvatar').textContent = initials(r.full_name);
        document.getElementById('rtoName').textContent = r.full_name;
        const catEl = document.getElementById('rtoCategory');
        catEl.className = `category-pill ${r.category}`;
        catEl.textContent = categoryLabel(r.category);

        const statusEl = document.getElementById('rtoStatus');
        statusEl.className = `eng-status-pill ${statusPillClass(r.status)}`;
        document.getElementById('rtoStatusText').textContent = statusLabel(r.status);

        document.getElementById('rtoTotalHours').textContent = `${r.total_hours}h`;
        document.getElementById('rtoRequested').textContent = formatDate(r.created);
        document.getElementById('rtoReason').textContent = r.reason || 'No reason provided.';

        const daysList = document.getElementById('rtoDaysList');
        daysList.innerHTML = [...r.days].sort((a, b) => a.date.localeCompare(b.date)).map(d => `
            <div class="eng-vm-emp-row">
                <div class="eng-vm-emp-info">
                    <div class="eng-vm-emp-name">${formatDate(d.date)}</div>
                </div>
                <div class="eng-vm-emp-hours">${d.hours}h</div>
            </div>
        `).join('');

        const commentField = document.getElementById('rtoCommentField');
        const commentInput = document.getElementById('rtoComment');
        const footer = document.getElementById('rtoFooter');

        if (r.status === 'pending') {
            commentField.style.display = '';
            commentInput.value = '';
            footer.querySelector('#rtoApproveBtn').style.display = '';
            footer.querySelector('#rtoDenyBtn').style.display = '';
            footer.querySelector('#rtoChangesBtn').style.display = '';
        } else {
            commentField.style.display = 'none';
            footer.querySelector('#rtoApproveBtn').style.display = 'none';
            footer.querySelector('#rtoDenyBtn').style.display = 'none';
            footer.querySelector('#rtoChangesBtn').style.display = 'none';
        }

        loadCommentHistory(r);

        reviewModal.show();
    }

    function submitReview(action) {
        if (!activeRequest) return;
        const comment = document.getElementById('rtoComment').value.trim();

        if (action === 'request_changes' && !comment) {
            notify('warning', 'Comment required', 'Please explain what needs to change before sending this back.');
            return;
        }

        const run = async () => {
            try {
                const res = await fetch('review_time_off_request.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ request_group: activeRequest.request_group, action, comment })
                });
                const data = await res.json();
                if (data.success) {
                    reviewModal.hide();
                    loadRequests();
                } else {
                    notify('error', 'Could not update request', data.error || 'Please try again.');
                }
            } catch (err) {
                console.error('Failed to review request', err);
            }
        };

        const copy = {
            approve: { icon: 'question', title: 'Approve this request?', confirmText: 'Approve', color: '#2f9e57' },
            deny: { icon: 'warning', title: 'Deny this request?', confirmText: 'Deny', color: '#c0392b' },
            request_changes: { icon: 'question', title: 'Send this back to the employee?', confirmText: 'Send Back', color: '#3f83b8' }
        }[action];

        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: copy.icon,
                title: copy.title,
                showCancelButton: true,
                confirmButtonText: copy.confirmText,
                confirmButtonColor: copy.color
            }).then(result => { if (result.isConfirmed) run(); });
        } else {
            run();
        }
    }

    document.getElementById('rtoApproveBtn').addEventListener('click', () => submitReview('approve'));
    document.getElementById('rtoDenyBtn').addEventListener('click', () => submitReview('deny'));
    document.getElementById('rtoChangesBtn').addEventListener('click', () => submitReview('request_changes'));

    function deleteRequest(requestGroup) {
        const run = async () => {
            try {
                const res = await fetch('delete_time_off_request.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ request_group: requestGroup })
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
