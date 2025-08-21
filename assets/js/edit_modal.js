function openEditModal(entryId, assignedHours, clientName, userName, weekStart, entryType, manageModalEl) {
  
  document.getElementById('editEntryId').value = entryId;
  document.getElementById('editAssignedHours').value = assignedHours;

  document.getElementById('editClientName').textContent =
    entryType === 'Time Off' ? 'Timeoff Entry' : (clientName || '—');
  document.getElementById('editUserName').textContent = userName || '—';

  const formattedWeekStart = weekStart
    ? new Date(weekStart).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
    : '—';
  document.getElementById('editWeekStart').textContent = formattedWeekStart;

  // Hide manage entries modal if open
  if (manageModalEl) {
    const manageModalInstance = bootstrap.Modal.getInstance(manageModalEl);
    if (manageModalInstance) manageModalInstance.hide();
  }

  const editModal = new bootstrap.Modal(document.getElementById('editEntryModal'));
  editModal.show();
}
