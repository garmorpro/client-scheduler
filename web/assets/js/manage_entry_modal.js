function openManageEntryModal(user_id, employeeName, weekStart) {
  const formattedDate = new Date(weekStart).toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
  document.getElementById('entriesModalTitle').innerText = `Manage Entires for Week of ${formattedDate}`;
  document.getElementById('entriesModalSubheading').innerText = `Consultant: ${employeeName}`;

  const entriesForWeek = entries[user_id] && entries[user_id][weekStart] ? entries[user_id][weekStart] : [];
  showEntries(entriesForWeek);

  const manageModal = new bootstrap.Modal(document.getElementById('manageEntryPromptModal'));
  manageModal.show();
}
