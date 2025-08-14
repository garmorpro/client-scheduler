function openAddEntryModal(user_id, employeeName, weekStart) {
    if (!weekStart || isNaN(new Date(weekStart).getTime())) {
        console.warn('Invalid weekStart date:', weekStart);
        return;
    }

    // Add 1 day
    const weekDate = new Date(weekStart);
    weekDate.setDate(weekDate.getDate() + 1); // +1 day

    // Update modal fields
    document.getElementById('addEntryUserId').value = user_id;
    document.getElementById('addEntryWeek').value = weekDate.toISOString().split('T')[0]; // "YYYY-MM-DD"
    document.getElementById('addEntryEmployeeNameDisplay').textContent = employeeName;

    // Replace the week display with random text
    document.getElementById('addEntryWeekDisplay').textContent = "Random Text Here";

    // Reset UI states
    document.getElementById('entryTypePrompt').classList.remove('d-none');
    document.getElementById('timeOffEntryContent').classList.add('d-none');
    document.getElementById('newEntryContent').classList.add('d-none');

    // Clear inputs
    document.getElementById('selectedClient').textContent = 'Select a client';
    document.getElementById('engagementInput').value = '';
    document.getElementById('assignedHours').value = '';
    document.getElementById('timeOffHours').value = '';

    // Reset dropdown aria
    const dropdownBtn = document.getElementById('dropdownBtn');
    if (dropdownBtn) {
        dropdownBtn.setAttribute('aria-expanded', 'false');
    }

    // Show modal
    const addEntryModal = new bootstrap.Modal(document.getElementById('addEntryModal'));
    addEntryModal.show();
}
