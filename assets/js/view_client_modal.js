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

                if (history.length === 0) {
                    historyListEl.innerHTML = '<div class="settings-empty-row">No engagement history yet.</div>';
                    return;
                }

                historyListEl.innerHTML = history.map(h => `
                    <div class="ch-item">
                        <div class="ch-item-head">
                            <div>
                                <div class="ch-item-name">${h.engagement_year}</div>
                                <div class="ch-item-total">Budgeted ${h.budgeted_hours}h &middot; Allocated ${h.allocated_hours}h</div>
                            </div>
                        </div>
                        <div class="detail-row"><span class="detail-label">Manager</span><span class="detail-value">${formatList(h.manager)}</span></div>
                        <div class="detail-row"><span class="detail-label">Senior</span><span class="detail-value">${formatList(h.senior)}</span></div>
                        <div class="detail-row"><span class="detail-label">Staff</span><span class="detail-value">${formatList(h.staff)}</span></div>
                        <div class="detail-row"><span class="detail-label">Archived</span><span class="detail-value">${formatDate(h.archive_date)} by ${h.archived_by || 'N/A'}</span></div>
                    </div>
                `).join('');
            } catch (err) {
                console.error('Failed to load client details', err);
                titleEl.textContent = 'Error';
                historyListEl.innerHTML = '<div class="settings-empty-row text-danger">Network error loading client details.</div>';
            }
        });
    });
});
