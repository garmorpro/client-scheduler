document.addEventListener('DOMContentLoaded', () => {
    const addEngagementModal = document.getElementById('addEngagementModal');
    const clientButtons = document.querySelectorAll('[data-bs-target="#addEngagementModal"]');
    const addForm = document.getElementById('addEngagementForm');

    // Populate client info when modal opens
    clientButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            const clientId = btn.getAttribute('data-client-id');
            const clientName = btn.getAttribute('data-client-name');

            document.getElementById('modal_client_id').value = clientId;
            document.getElementById('modal_client_name').value = clientName;
        });
    });

    // Handle form submission via AJAX
    addForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(addForm);

        try {
            const response = await fetch('add_engagement.php', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();

            if (result.success) {
                addEngagementModal.querySelector('.btn-close').click();
                location.reload(); // Refresh to update list
            } else {
                alert('Error: ' + result.message);
            }
        } catch (error) {
            alert('Error adding engagement: ' + error.message);
        }
    });
});
