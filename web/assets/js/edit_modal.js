function openEditModal(entryId, assignedHours, clientName, userId, weekStart) {
    // Populate the form fields
    document.getElementById('editEntryId').value = entryId;
    document.getElementById('editAssignedHours').value = assignedHours;

    // Populate additional details section in the modal
    document.getElementById('editClientName').textContent = clientName || '—';
    document.getElementById('editUserId').textContent = userId || '—';
    const formattedWeekStart = weekStart
        ? new Date(weekStart).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
        : '—';
    document.getElementById('editWeekStart').textContent = formattedWeekStart;

    // Hide the manage entry modal first
    const manageModalEl = document.getElementById('manageEntryPromptModal');
    const manageModal = bootstrap.Modal.getInstance(manageModalEl);
    if (manageModal) manageModal.hide();

    // Then show the edit modal
    const editModal = new bootstrap.Modal(document.getElementById('editEntryModal'));
    editModal.show();
}
