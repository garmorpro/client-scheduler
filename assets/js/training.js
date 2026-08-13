document.addEventListener('DOMContentLoaded', () => {
    const tbody = document.getElementById('trainingTableBody');
    if (!tbody) return;

    function escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    async function openEditor(tr) {
        const userId = tr.dataset.userId;
        const userName = tr.dataset.userName;
        const currentChips = Array.from(tr.querySelectorAll('.tr-chip')).map(c => c.textContent.trim());

        const value = await appTextPrompt({
            title: 'Training status',
            text: `Criteria <strong>${escapeHtml(userName)}</strong> hasn't completed training on yet &mdash; the DOL Generator won't assign these to them until cleared here.`,
            value: currentChips.join(', '),
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

            const cell = tr.querySelector('.tr-restricted-cell');
            cell.innerHTML = restricted.length
                ? restricted.map(c => `<span class="tr-chip">${escapeHtml(c)}</span>`).join('')
                : '<span class="text-muted" style="font-size:12.5px;">Fully trained</span>';

            if (typeof appNotify !== 'undefined') {
                appNotify({ icon: 'success', title: 'Saved', timer: 1200 });
            }
        } catch (err) {
            console.error('Failed to save training status', err);
            appNotify({ icon: 'error', title: 'Error', text: 'Failed to save training status' });
        }
    }

    tbody.addEventListener('click', (e) => {
        const btn = e.target.closest('.tr-edit-btn');
        if (!btn) return;
        openEditor(btn.closest('tr[data-user-id]'));
    });
});
