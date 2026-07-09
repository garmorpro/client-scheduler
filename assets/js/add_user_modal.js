document.addEventListener('DOMContentLoaded', () => {
    const modalEl = document.getElementById('addUserModal');
    const form = document.getElementById('addUserForm');
    if (!modalEl || !form) return;

    modalEl.addEventListener('hidden.bs.modal', () => form.reset());

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(form);
        try {
            const response = await fetch('add_user.php', { method: 'POST', body: formData });
            const result = await response.json();
            if (result.success) {
                modalEl.querySelector('.btn-close').click();
                location.reload();
            } else {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({ icon: 'error', title: 'Could not add employee', text: result.error || 'Please try again.' });
                } else {
                    alert('Error: ' + (result.error || 'Could not add employee.'));
                }
            }
        } catch (error) {
            console.error('Failed to add user', error);
        }
    });
});
