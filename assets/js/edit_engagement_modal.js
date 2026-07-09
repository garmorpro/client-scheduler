document.addEventListener('DOMContentLoaded', () => {
    const editModal = document.getElementById('editEngagementModal');
    const editForm = document.getElementById('editEngagementForm');
    if (!editModal || !editForm) return;

    document.querySelectorAll('.edit-engagement-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            document.getElementById('edit_eng_engagement_id').value = btn.getAttribute('data-engagement-id');
            document.getElementById('edit_eng_client_name').value = btn.getAttribute('data-client-name');
            document.getElementById('edit_eng_budgeted_hours').value = btn.getAttribute('data-budgeted-hours');
            document.getElementById('edit_eng_status').value = btn.getAttribute('data-status');
            document.getElementById('edit_eng_manager').value = btn.getAttribute('data-manager') || '';
            document.getElementById('edit_eng_notes').value = btn.getAttribute('data-notes') || '';
        });
    });

    editForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(editForm);
        try {
            const response = await fetch('edit_engagement.php', { method: 'POST', body: formData });
            const result = await response.json();
            if (result.success) {
                editModal.querySelector('.btn-close').click();
                location.reload();
            } else {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({ icon: 'error', title: 'Could not save changes', text: result.message || 'Please try again.' });
                } else {
                    alert('Error: ' + result.message);
                }
            }
        } catch (error) {
            console.error('Failed to save engagement', error);
        }
    });
});
