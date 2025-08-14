function openEditModal(entryId, assignedHours, clientName, userName, weekStart, entryType) {
  document.getElementById('editEntryId').value = entryId;
  document.getElementById('editAssignedHours').value = assignedHours;

  // Show client name or default
  document.getElementById('editClientName').textContent = clientName || '—';
  document.getElementById('editUserName').textContent = userName || '—';
  const formattedWeekStart = weekStart
    ? new Date(weekStart).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
    : '—';
  document.getElementById('editWeekStart').textContent = formattedWeekStart;

  // New: Show entry type
  document.getElementById('editEntryType').textContent = entryType;

  // Hide manage modal before showing edit modal
  const manageModalInstance = bootstrap.Modal.getInstance(manageAddModalEl);
  if (manageModalInstance) manageModalInstance.hide();

  const editModal = new bootstrap.Modal(document.getElementById('editEntryModal'));
  editModal.show();
}
