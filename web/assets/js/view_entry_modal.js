function openEntryModal(engagementId) {
    // Set the engagementId in the hidden input field before fetching the data
    document.getElementById('engagementId').value = engagementId;

    // Fetch the engagement details using the engagement ID
    fetch(`engagement-details.php?id=${engagementId}`)
        .then(response => response.json())
        .then(data => {
            // Set engagement (client) name
            document.getElementById('clientName').innerText = data.client_name;

            // Set total assigned hours
            let totalAssignedHours = parseFloat(data.total_hours) || 0;
            let totalAvailableHours = parseFloat(data.max_hours) || 0;

            // Set total assigned hours text
            document.getElementById('totalAssignedHours').innerText = totalAssignedHours;
            document.getElementById('totalHours').innerText = `/ ${totalAvailableHours} hrs`;

            let utilizationPercent = totalAvailableHours > 0
                ? (totalAssignedHours / totalAvailableHours) * 100
                : 0;

            const bar = document.getElementById('utilizationBar');

            // Set bar width and ARIA attributes
            bar.style.width = utilizationPercent + "%";
            bar.setAttribute('aria-valuenow', totalAssignedHours);
            bar.setAttribute('aria-valuemax', totalAvailableHours);

            // Remove any existing color classes
            bar.classList.remove('bg-success', 'bg-danger');

            // Add the appropriate color
            if (totalAssignedHours > totalAvailableHours) {
                bar.classList.add('bg-danger');
            } else {
                bar.classList.add('bg-success');
            }

            // Set assigned employees
            let assignedEmployees = data.assigned_employees;
            document.getElementById('assignedEmployees').innerHTML = assignedEmployees;

            // Set client notes
            const notes = data.notes?.trim();
            document.getElementById('clientNotes').innerText = notes ? notes : "No notes available.";

            // Show the modal after the engagement details are set
            const entryModal = new bootstrap.Modal(document.getElementById('clientDetailsModal'));
            entryModal.show();
        })
        .catch(error => console.error('Error fetching engagement details:', error));
}