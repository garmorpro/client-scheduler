document.addEventListener('DOMContentLoaded', () => {
    const modalEl = document.getElementById('assignManagerModal');
    const form = document.getElementById('assignManagerForm');
    if (!modalEl || !form) return;

    modalEl.addEventListener('show.bs.modal', (e) => {
        const btn = e.relatedTarget;
        if (!btn) return;
        document.getElementById('am_user_id').value = btn.dataset.userId || '';
        document.getElementById('am_user_name').textContent = btn.dataset.userName || '';
        document.getElementById('am_manager_id').value = btn.dataset.managerId || '';
    });

    modalEl.addEventListener('hidden.bs.modal', () => form.reset());

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(form);
        try {
            const response = await fetch('assign_manager.php', { method: 'POST', body: formData });
            const result = await response.json();
            if (result.success) {
                modalEl.querySelector('.btn-close').click();
                location.reload();
            } else {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({ icon: 'error', title: 'Could not assign manager', text: result.error || 'Please try again.' });
                } else {
                    alert('Error: ' + (result.error || 'Could not assign manager.'));
                }
            }
        } catch (error) {
            console.error('Failed to assign manager', error);
        }
    });
});
