function openManageEntryModal(user_id, employeeName, weekStart) {
  // Convert weekStart to Date and add 1 day
  const dateObj = new Date(weekStart);
  dateObj.setDate(dateObj.getDate() + 1);

  // Format for modal title
  const formattedDate = dateObj.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
  document.getElementById('entriesModalTitle').innerText = `Manage Entries for Week of ${formattedDate}`;
  document.getElementById('entriesModalSubheading').innerText = `Consultant: ${employeeName}`;

  // Format date as YYYY-MM-DD to match your entries keys
  const weekKeyPlusOne = dateObj.toISOString().split('T')[0];

  const entriesForWeek = entries[user_id] && entries[user_id][weekKeyPlusOne] ? entries[user_id][weekKeyPlusOne] : [];
  showEntries(entriesForWeek);

  const manageModal = new bootstrap.Modal(document.getElementById('manageEntryPromptModal'));
  manageModal.show();
}
