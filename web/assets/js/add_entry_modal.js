function openAddEntryModal(userId, employeeName, weekStart) {
    // set hidden inputs for form submission
    document.getElementById('addEntryUserId').value = userId;
    document.getElementById('addEntryWeek').value = weekStart;

    // display in modal header
    document.getElementById('addEntryEmployeeNameDisplay').textContent = employeeName;
    document.getElementById('addEntryWeekDisplay').textContent = weekStart;

    // show the modal
    const modal = new bootstrap.Modal(document.getElementById('addEntryModal'));
    modal.show();
}
