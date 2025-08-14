function openAddEntryModal(user_id, employeeName, weekStart) {
    if (!weekStart) {
        console.warn('Invalid weekStart date:', weekStart);
        return;
    }

    // Set user ID and employee name
    document.getElementById('addEntryUserId').value = user_id;
    document.getElementById('addEntryEmployeeNameDisplay').textContent = employeeName;

    // Parse the date manually to avoid timezone issues
    const [year, month, day] = weekStart.split('-').map(Number); // ["2025","08","11"] -> [2025,8,11]
    const weekDate = new Date(year, month - 1, day); // month is 0-based in JS

    // Format date as "Aug 11, 2025"
    const monthNames = ["Jan", "Feb", "Mar", "Apr", "May", "Jun",
                        "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
    const formattedDate = `${monthNames[weekDate.getMonth()]} ${weekDate.getDate()}, ${weekDate.getFullYear()}`;
    document.getElementById('addEntryWeekDisplay').textContent = formattedDate;

    // Hidden input for form submission
    document.getElementById('addEntryWeek').value = weekStart;

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
