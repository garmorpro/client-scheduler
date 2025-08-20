document.addEventListener('DOMContentLoaded', () => {
    // Auto-fill onboarded_date with today
    const onboardedDateInput = document.getElementById('add_onboarded_date');
    const today = new Date().toISOString().split('T')[0];
    onboardedDateInput.value = today;

    const addForm = document.getElementById('addClientForm');
    const addModal = new bootstrap.Modal(document.getElementById('addClientModal'));

    addForm.addEventListener('submit', async (e) => {
        e.preventDefault();

        const formData = new FormData(addForm);

        try {
            const response = await fetch('add_client.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                alert('Client added successfully!');
                addModal.hide();
                location.reload(); // reload page to show new client
            } else {
                alert('Error: ' + result.message);
            }
        } catch (error) {
            alert('Error adding client: ' + error.message);
        }
    });
});