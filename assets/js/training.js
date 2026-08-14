document.addEventListener('DOMContentLoaded', () => {
    const list = document.getElementById('trainingList');
    if (!list) return;

    function escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    // Recomputes the three stat cards from what's actually on screen -
    // cheaper than a round trip, and always exactly matches the rows below
    // since it's reading the same data-restricted attributes the rows
    // themselves were just updated from.
    function refreshStats() {
        const rows = list.querySelectorAll('.tr-row');
        let restrictedCount = 0;
        rows.forEach(row => {
            if ((row.dataset.restricted || '').trim() !== '') restrictedCount++;
        });
        const totalEl = document.getElementById('trStatTotal');
        const trainedEl = document.getElementById('trStatTrained');
        const restrictedEl = document.getElementById('trStatRestricted');
        if (totalEl) totalEl.textContent = rows.length;
        if (trainedEl) trainedEl.textContent = rows.length - restrictedCount;
        if (restrictedEl) restrictedEl.textContent = restrictedCount;
    }

    async function openEditor(row) {
        const userId = row.dataset.userId;
        const userName = row.dataset.userName;
        const currentValue = row.dataset.restricted || '';

        const value = await appTextPrompt({
            title: 'Training status',
            text: `Criteria <strong>${escapeHtml(userName)}</strong> hasn't completed training on yet &mdash; the DOL Generator won't assign these to them until cleared here.`,
            value: currentValue,
            placeholder: 'e.g. CC6, CC9, Privacy',
            confirmText: 'Save'
        });
        if (value === null) return;

        const restricted = value.split(',').map(s => s.trim()).filter(Boolean);

        try {
            const res = await fetch('update-training-status.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ user_id: userId, restricted })
            });
            const data = await res.json();
            if (!data.success) {
                appNotify({ icon: 'error', title: 'Error', text: data.error || 'Failed to save training status' });
                return;
            }

            row.dataset.restricted = restricted.join(',');
            const statusEl = row.querySelector('.tr-row-status');
            statusEl.innerHTML = restricted.length
                ? `<span class="eng-status-pill denied"><span class="dot"></span>${restricted.length} restricted</span><span class="tr-row-restricted-list">${escapeHtml(restricted.join(', '))}</span>`
                : '<span class="eng-status-pill confirmed"><span class="dot"></span>Fully trained</span>';
            refreshStats();

            if (typeof appNotify !== 'undefined') {
                appNotify({ icon: 'success', title: 'Saved', timer: 1200 });
            }
        } catch (err) {
            console.error('Failed to save training status', err);
            appNotify({ icon: 'error', title: 'Error', text: 'Failed to save training status' });
        }
    }

    list.addEventListener('click', (e) => {
        const btn = e.target.closest('.tr-edit-btn');
        if (!btn) return;
        openEditor(btn.closest('.tr-row[data-user-id]'));
    });
});
