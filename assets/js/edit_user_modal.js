document.addEventListener('DOMContentLoaded', () => {
    const modalEl = document.getElementById('editUserModal');
    const form = document.getElementById('editUserForm');
    if (!modalEl || !form) return;

    modalEl.addEventListener('show.bs.modal', (e) => {
        const btn = e.relatedTarget;
        if (!btn) return;
        document.getElementById('edit_user_id').value = btn.dataset.userId || '';
        document.getElementById('edit_user_full_name').value = btn.dataset.fullName || '';
        document.getElementById('edit_user_email').value = btn.dataset.email || '';
        document.getElementById('edit_user_role').value = btn.dataset.role || 'staff';
        document.getElementById('edit_user_job_title').value = btn.dataset.jobTitle || '';
        document.getElementById('edit_user_status').value = btn.dataset.status || 'active';
    });

    modalEl.addEventListener('hidden.bs.modal', () => form.reset());

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(form);
        try {
            const response = await fetch('update_user.php', { method: 'POST', body: formData });
            const result = await response.json();
            if (result.success) {
                modalEl.querySelector('.btn-close').click();
                location.reload();
            } else {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({ icon: 'error', title: 'Could not save changes', text: result.error || 'Please try again.' });
                } else {
                    alert('Error: ' + (result.error || 'Could not save changes.'));
                }
            }
        } catch (error) {
            console.error('Failed to update employee', error);
        }
    });
});
