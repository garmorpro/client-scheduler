function openEditModal(entryId, assignedHours, clientName, userName, weekStart, entryType) {
  // Set form fields
  document.getElementById('editEntryId').value = entryId;
  document.getElementById('editAssignedHours').value = assignedHours;

  // Show client name or "Timeoff Entry" for time off
  document.getElementById('editClientName').textContent =
    entryType === 'Time Off' ? 'Timeoff Entry' : (clientName || '—');
  document.getElementById('editUserName').textContent = userName || '—';
  
  const formattedWeekStart = weekStart
    ? new Date(weekStart).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
    : '—';
  document.getElementById('editWeekStart').textContent = formattedWeekStart;

  // Hide manage entries modal if open
  const manageModalEl = document.getElementById('manageEntryPromptModal');
  const manageModalInstance = bootstrap.Modal.getInstance(manageModalEl);
  if (manageModalInstance) manageModalInstance.hide();

  // Show the edit modal
  const editModal = new bootstrap.Modal(document.getElementById('editEntryModal'));
  editModal.show();
}
