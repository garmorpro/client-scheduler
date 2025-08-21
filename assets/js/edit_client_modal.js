document.addEventListener('DOMContentLoaded', () => {
    const editModal = document.getElementById('editClientModal');
    const editForm = document.getElementById('editClientForm');

    // Populate modal when edit button is clicked
    const editButtons = document.querySelectorAll('.edit-client-btn');
    editButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            document.getElementById('edit_modal_client_id').value = btn.getAttribute('data-client-id');
            document.getElementById('edit_client_name').value = btn.getAttribute('data-client-name');
            document.getElementById('edit_onboarded_date').value = btn.getAttribute('data-onboarded-date');
            document.getElementById('edit_status').value = btn.getAttribute('data-status');
            document.getElementById('edit_notes').value = btn.getAttribute('data-notes');
        });
    });

    // Handle form submission
    editForm.addEventListener('submit', async (e) => {
        e.preventDefault();

        const formData = new FormData(editForm);

        try {
            const response = await fetch('edit_client.php', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();

            if (result.success) {
                // alert('Client updated successfully!');
                editModal.querySelector('.btn-close').click();
                location.reload(); // refresh to update client card
            } else {
                alert('Error: ' + result.message);
            }
        } catch (error) {
            alert('Error updating client: ' + error.message);
        }
    });
});