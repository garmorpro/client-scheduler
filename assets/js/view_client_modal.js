document.addEventListener('DOMContentLoaded', () => {
    const modalEl = document.getElementById('viewClientModal');
    if (!modalEl) return;
    const modal = new bootstrap.Modal(modalEl);

    const avatarEl = document.getElementById('vcAvatar');
    const titleEl = document.getElementById('vcTitle');
    const onboardedEl = document.getElementById('vcOnboarded');
    const statusPillEl = document.getElementById('vcStatusPill');
    const statusTextEl = document.getElementById('vcStatusText');
    const totalEl = document.getElementById('vcTotalEngagements');
    const confirmedEl = document.getElementById('vcConfirmedEngagements');
    const historyListEl = document.getElementById('vcHistoryList');

    const palette = ['#4f8ef7', '#9b6bd6', '#4fbf9f', '#e0994c', '#5fb85f', '#5aa8d6', '#d67aa8', '#7a8fd6'];
    function hashColor(name) {
        let hash = 0;
        for (let i = 0; i < (name || '').length; i++) hash = (hash * 31 + name.charCodeAt(i)) >>> 0;
        return palette[hash % palette.length];
    }
    function initials(name) {
        return (name || '?').trim().split(/\s+/).slice(0, 2).map(w => w[0].toUpperCase()).join('');
    }
    function ucfirst(str) {
        return str ? str.charAt(0).toUpperCase() + str.slice(1) : '';
    }
    function formatDate(dateString) {
        if (!dateString) return 'N/A';
        const d = new Date(dateString);
        if (isNaN(d)) return 'N/A';
        return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
    }
    function formatList(val) {
        if (!val) return '<span class="text-muted">&mdash;</span>';
        return val.split(',').map(v => v.trim()).filter(Boolean).join(', ');
    }
    function statusLabel(status) {
        return status === 'not_confirmed' ? 'Not Confirmed' : ucfirst(status);
    }
    function statusPillClass(status) {
        return status === 'not_confirmed' ? 'not-confirmed' : status;
    }

    let currentClient = null;
    let reopenAfterEngagementModal = false;

    // Archived years have no engagement_id (the row's gone from `engagements`
    // by the time it's archived) so there's nothing to fetch - render the
    // manager/senior/staff name lists we already have straight into the same
    // View Engagement modal shell used for active engagements.
    function openArchivedDetail(item) {
        const engModalBody = document.getElementById('viewEngagementModalBody');
        if (!window.ViewEngagementModal || !engModalBody) return;

        engModalBody.innerHTML = `
            <div class="eng-vm-header">
                <div class="eng-vm-client-row">
                    <div class="eng-vm-tile" style="background-color:${hashColor(currentClient.client_name)};">${initials(currentClient.client_name)}</div>
                    <div>
                        <div class="eng-vm-client-name">${currentClient.client_name} &mdash; ${item.engagement_year}</div>
                        <span class="eng-status-pill" style="background:#f1f4f3;color:#6b7570;"><span class="dot"></span>Archived</span>
                    </div>
                </div>
            </div>
            <div class="eng-vm-body">
                <div class="eng-vm-stat-row">
                    <div class="eng-vm-stat-card">
                        <div class="eng-vm-stat-title">Budgeted</div>
                        <div class="eng-vm-stat-value">${item.budgeted_hours}h</div>
                    </div>
                    <div class="eng-vm-stat-card">
                        <div class="eng-vm-stat-title">Allocated</div>
                        <div class="eng-vm-stat-value">${item.allocated_hours}h</div>
                    </div>
                </div>
                <div class="eng-vm-section-title">Staffing</div>
                <div class="detail-row"><span class="detail-label">Manager</span><span class="detail-value">${formatList(item.manager)}</span></div>
                <div class="detail-row"><span class="detail-label">Senior</span><span class="detail-value">${formatList(item.senior)}</span></div>
                <div class="detail-row"><span class="detail-label">Staff</span><span class="detail-value">${formatList(item.staff)}</span></div>
                <div class="eng-vm-section-title">Archive Details</div>
                <div class="detail-row"><span class="detail-label">Archived</span><span class="detail-value">${formatDate(item.archive_date)}</span></div>
                <div class="detail-row"><span class="detail-label">Archived By</span><span class="detail-value">${item.archived_by || 'N/A'}</span></div>
                ${item.notes ? `<div class="detail-row"><span class="detail-label">Notes</span><span class="detail-value">${item.notes}</span></div>` : ''}
            </div>
        `;
        reopenAfterEngagementModal = true;
        modal.hide();
        window.ViewEngagementModal.modal.show();
    }

    function openEngagementDetail(item) {
        if (item.type === 'active') {
            if (!window.ViewEngagementModal) return;
            reopenAfterEngagementModal = true;
            modal.hide();
            window.ViewEngagementModal.open(item.sort_id, hashColor(currentClient.client_name), initials(currentClient.client_name), false);
        } else {
            openArchivedDetail(item);
        }
    }

    // Reopen the client modal once the nested engagement-detail modal closes.
    if (window.ViewEngagementModal) {
        window.ViewEngagementModal.modalEl.addEventListener('hidden.bs.modal', () => {
            if (!reopenAfterEngagementModal) return;
            reopenAfterEngagementModal = false;
            modal.show();
        });
    }

    function renderHistory(history) {
        if (history.length === 0) {
            historyListEl.innerHTML = '<div class="settings-empty-row">No engagement history yet.</div>';
            return;
        }

        // Group by year, newest year first (history already arrives sorted).
        const years = [];
        const byYear = {};
        history.forEach(h => {
            const y = h.engagement_year;
            if (!byYear[y]) { byYear[y] = []; years.push(y); }
            byYear[y].push(h);
        });

        historyListEl.innerHTML = years.map((year, idx) => {
            const items = byYear[year];
            const isOpen = idx === 0; // most recent year expanded by default
            const itemsHtml = items.map((h, i) => `
                <div class="ch-eng-item" data-year="${year}" data-idx="${i}" role="button" tabindex="0">
                    <div class="ch-item-head">
                        <div>
                            <div class="ch-item-name">
                                ${h.type === 'active'
                                    ? `<span class="eng-status-pill ${statusPillClass(h.status)}"><span class="dot"></span>${statusLabel(h.status)}</span>`
                                    : `<span class="eng-status-pill" style="background:#f1f4f3;color:#6b7570;">Archived</span>`}
                            </div>
                            <div class="ch-item-total">Budgeted ${h.budgeted_hours}h &middot; Allocated ${h.allocated_hours}h &middot; Manager: ${formatList(h.manager)}</div>
                        </div>
                        <i class="bi bi-chevron-right ch-eng-arrow"></i>
                    </div>
                </div>
            `).join('');

            return `
                <div class="yr-group">
                    <div class="yr-header ${isOpen ? 'open' : ''}" data-year-toggle="${year}">
                        <span><i class="bi bi-chevron-right yr-chev"></i> ${year}</span>
                        <span class="yr-count">${items.length} engagement${items.length === 1 ? '' : 's'}</span>
                    </div>
                    <div class="yr-items ${isOpen ? '' : 'closed'}">${itemsHtml}</div>
                </div>
            `;
        }).join('');

        // Store the grouped data on the DOM node set so click handlers can
        // look items back up by year/index without re-parsing HTML.
        historyListEl._groups = byYear;

        historyListEl.querySelectorAll('[data-year-toggle]').forEach(header => {
            header.addEventListener('click', () => {
                header.classList.toggle('open');
                const itemsEl = header.nextElementSibling;
                if (itemsEl) itemsEl.classList.toggle('closed');
            });
        });

        historyListEl.querySelectorAll('.ch-eng-item').forEach(el => {
            const open = () => {
                const year = el.dataset.year;
                const idx = parseInt(el.dataset.idx, 10);
                const item = (historyListEl._groups[year] || [])[idx];
                if (item) openEngagementDetail(item);
            };
            el.addEventListener('click', open);
            el.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); open(); }
            });
        });
    }

    document.querySelectorAll('.view-btn').forEach(button => {
        button.addEventListener('click', async () => {
            const clientId = button.dataset.clientId;
            if (!clientId) return;

            avatarEl.style.backgroundColor = '';
            avatarEl.textContent = '';
            titleEl.textContent = 'Loading...';
            onboardedEl.textContent = '';
            statusPillEl.className = 'status-pill';
            statusTextEl.textContent = '';
            totalEl.textContent = '0';
            confirmedEl.textContent = '0';
            historyListEl.innerHTML = '<div class="settings-empty-row">Loading...</div>';
            modal.show();

            try {
                const res = await fetch(`view_client.php?client_id=${encodeURIComponent(clientId)}`);
                const data = await res.json();

                if (data.error) {
                    titleEl.textContent = 'Error';
                    historyListEl.innerHTML = `<div class="settings-empty-row text-danger">${data.error}</div>`;
                    return;
                }

                const client = data.client;
                currentClient = client;
                const history = data.history || [];
                const status = (client.status || '').toLowerCase();

                avatarEl.style.backgroundColor = hashColor(client.client_name);
                avatarEl.textContent = initials(client.client_name);
                titleEl.textContent = client.client_name;
                onboardedEl.textContent = `Onboarded ${formatDate(client.onboarded_date)}`;
                statusPillEl.className = `status-pill ${status === 'active' ? 'active' : 'inactive'}`;
                statusTextEl.textContent = ucfirst(client.status);
                totalEl.textContent = client.total_engagements ?? 0;
                confirmedEl.textContent = client.confirmed_engagements ?? 0;

                renderHistory(history);
            } catch (err) {
                console.error('Failed to load client details', err);
                titleEl.textContent = 'Error';
                historyListEl.innerHTML = '<div class="settings-empty-row text-danger">Network error loading client details.</div>';
            }
        });
    });
});
