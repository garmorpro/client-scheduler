document.addEventListener('DOMContentLoaded', () => {
  const table = document.querySelector('#employeesTableBody');
  if (!table) return; // stop if not found

  table.addEventListener('click', (e) => {
    const td = e.target.closest('td.addable');
    if (!td) return;

    e.stopPropagation();

    const userId = td.dataset.userId;
    const userName = td.dataset.userName || '';
    const weekStart = td.dataset.weekStart;

    // Check if the cell has any entries (regular OR time-off)
    const hasRegularEntries = td.querySelector('.badge-status') !== null;
    const hasTimeOffEntry = td.querySelector('.timeoff-entry') !== null;
    const hasEntries = hasRegularEntries || hasTimeOffEntry;

    if (hasEntries) {
      // If only a time-off entry exists, pass is_timeoff = 1 to the Manage modal
      const isTimeOff = !hasRegularEntries && hasTimeOffEntry ? 1 : 0;

      openManageEntryModal(userId, userName, weekStart, isTimeOff);
    } else {
      openAddEntryModal(userId, userName, weekStart);
    }
  });
});
