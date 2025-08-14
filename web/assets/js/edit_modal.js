function openEditModal(entryId, assignedHours, clientName, userName, weekStart, entryType) {
  // Set form fields
  document.getElementById('editEntryId').value = entryId;
  document.getElementById('editAssignedHours').value = assignedHours;

  // Show client name, user name, week start, and entry type
  document.getElementById('editClientName').textContent = clientName || (entryType === 'Time Off' ? 'Time Off Entry' : '—');
  document.getElementById('editUserName').textContent = userName || '—';
  document.getElementById('editEntryType').textContent = entryType || '—';
  const formattedWeekStart = weekStart
    ? new Date(weekStart).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
    : '—';
  document.getElementById('editWeekStart').textContent = formattedWeekStart;

  // Hide the manage entries modal if it's open
  const manageModalEl = document.getElementById('manageEntryPromptModal');
  const manageModalInstance = bootstrap.Modal.getInstance(manageModalEl);
  if (manageModalInstance) manageModalInstance.hide();

  // Show the edit modal
  const editModal = new bootstrap.Modal(document.getElementById('editEntryModal'));
  editModal.show();
}
