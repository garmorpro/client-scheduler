function openEmployeeModal(employeeId) {
    fetch(`employee-details.php?id=${employeeId}`)
        .then(response => {
            if (!response.ok) throw new Error("Network error");
            return response.json();
        })
        .then(data => {
            if (data.error) {
                alert(data.error);
                return;
            }

            document.getElementById('employeeName').innerText = data.full_name;
            document.getElementById('employeeRole').innerText = data.role;

            document.getElementById('totalAssignedHoursEmployee').innerText = data.total_assigned_hours;
            document.getElementById('totalAvailableHoursEmployeeVal').innerText = data.total_available_hours;

            // Set progress bar
            const percent = (data.total_assigned_hours / data.total_available_hours) * 100;
            const bar = document.getElementById('utilizationBarEmployee');
            bar.style.width = percent + "%";
            bar.setAttribute('aria-valuenow', data.total_assigned_hours);
            bar.setAttribute('aria-valuemax', data.total_available_hours);

            // Set assignments
            document.getElementById('assignedAssignments').innerHTML = data.assignment_items;

            // Show modal
            const employeeModal = new bootstrap.Modal(document.getElementById('employeeDetailsModal'));
            employeeModal.show();
        })
        .catch(err => {
            console.error("Error fetching employee details:", err);
            alert("Failed to load employee details.");
        });
}