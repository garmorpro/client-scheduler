function openAddEntryModal(user_id, employeeName, weekStart) {
    // console.log("Original weekStart:", weekStart);
    const SCRIPT_NAME = 'dynamic_manage_modal.js';

document.addEventListener('DOMContentLoaded', () => {
    console.log(`${SCRIPT_NAME} DOM ready ✅`);
});

    if (!weekStart || isNaN(new Date(weekStart).getTime())) {
        console.warn('Invalid weekStart date:', weekStart);
        return;
    }

    // Convert to Date object in local time
    const parts = weekStart.split('-'); // ["2025","08","11"]
    const weekDate = new Date(parts[0], parts[1] - 1, parts[2]); // month is 0-based

    // Add 1 day
    weekDate.setDate(weekDate.getDate());

    // console.log("Adjusted weekDate:", weekDate);

    // Format for display
    const options = { year: 'numeric', month: 'short', day: 'numeric' };
    const formattedDate = weekDate.toLocaleDateString('en-US', options);

    // console.log("Formatted display date:", formattedDate);

    // Update modal fields
    document.getElementById('addEntryUserId').value = user_id;
    document.getElementById('addEntryWeek').value = weekDate.toISOString().split('T')[0];
    document.getElementById('addEntryEmployeeNameDisplay').textContent = employeeName;
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
