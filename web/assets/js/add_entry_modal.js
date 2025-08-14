function openAddEntryModal(user_id, employeeName, weekStart) {
    if (!weekStart || isNaN(new Date(weekStart).getTime())) {
        console.warn('Invalid weekStart date:', weekStart);
        return;
    }

    document.getElementById('addEntryUserId').value = user_id;
    document.getElementById('addEntryWeek').value = weekStart;  // must be "YYYY-MM-DD"
    document.getElementById('addEntryEmployeeNameDisplay').textContent = employeeName;

    // Parse the weekStart string manually in local time
    const [year, month, day] = weekStart.split('-').map(Number);
    const weekDate = new Date(year, month - 1, day); // month is 0-based

    // Make sure it’s the Monday of that week
    const dayOfWeek = weekDate.getDay(); // 0=Sun, 1=Mon, ..., 6=Sat
    const diffToMonday = (dayOfWeek + 5) % 7; // days since Monday
    weekDate.setDate(weekDate.getDate() - diffToMonday);

    // Format date as "Aug 11, 2025"
    const monthNames = ["Jan", "Feb", "Mar", "Apr", "May", "Jun",
                        "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
    const formattedDate = `${monthNames[weekDate.getMonth()]} ${weekDate.getDate()}, ${weekDate.getFullYear()}`;
    
    // Update modal
    document.getElementById('addEntryWeekDisplay').textContent = formattedDate;

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
