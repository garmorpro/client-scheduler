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

        const result = await Swal.fire({
            title: 'Training status',
            html: `<div style="text-align:left; font-size:12.5px; color:#6b7570; margin-bottom:0.75rem;">
                       Criteria <strong>${escapeHtml(userName)}</strong> hasn't completed training on yet &mdash; the DOL Generator won't assign these to them until cleared here.
                   </div>`,
            input: 'text',
            inputValue: currentChips.join(', '),
            inputPlaceholder: 'e.g. CC6, CC9, Privacy',
            showCancelButton: true,
            confirmButtonText: 'Save'
        });
        if (!result.isConfirmed) return;

        const restricted = result.value.split(',').map(s => s.trim()).filter(Boolean);

        try {
            const res = await fetch('update-training-status.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ user_id: userId, restricted })
            });
            const data = await res.json();
            if (!data.success) {
                Swal.fire('Error', data.error || 'Failed to save training status', 'error');
                return;
            }

            const cell = tr.querySelector('.tr-restricted-cell');
            cell.innerHTML = restricted.length
                ? restricted.map(c => `<span class="tr-chip">${escapeHtml(c)}</span>`).join('')
                : '<span class="text-muted" style="font-size:12.5px;">Fully trained</span>';

            if (typeof Swal !== 'undefined') {
                Swal.fire({ icon: 'success', title: 'Saved', timer: 1200, showConfirmButton: false });
            }
        } catch (err) {
            console.error('Failed to save training status', err);
            Swal.fire('Error', 'Failed to save training status', 'error');
        }
    }

    tbody.addEventListener('click', (e) => {
        const btn = e.target.closest('.tr-edit-btn');
        if (!btn) return;
        openEditor(btn.closest('tr[data-user-id]'));
    });
});
