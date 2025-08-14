function openAddEntryModal(user_id, employeeName, weekStart) {
    if (!weekStart || isNaN(new Date(weekStart).getTime())) {
        console.warn('Invalid weekStart date:', weekStart);
        return;
    }

    document.getElementById('addEntryUserId').value = user_id;
    document.getElementById('addEntryWeek').value = weekStart;  // must be "YYYY-MM-DD"
    document.getElementById('addEntryEmployeeNameDisplay').textContent = employeeName;

    const options = { year: 'numeric', month: 'short', day: 'numeric' };
const parts = weekStart.split('-'); // assuming format YYYY-MM-DD
const weekDate = new Date(parts[0], parts[1]-1, parts[2]); // month is 0-based

document.getElementById('addEntryWeekDisplay').textContent =
    weekDate.toLocaleDateString(undefined, options);


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
