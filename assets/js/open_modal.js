document.addEventListener('DOMContentLoaded', () => {
  const table = document.querySelector('#employeesTableBody');
  if (!table) return; // stop if not found

  table.addEventListener('click', (e) => {
    const td = e.target.closest('td.addable');
    if (!td) return;

    e.stopPropagation();

    const userId = td.dataset.userId;
    const userName = td.dataset.userName;
    const weekStart = td.dataset.weekStart;

    // Determine if the cell has any regular entries
    const hasRegularEntries = td.querySelector('.badge-status') !== null;
    // Determine if the cell has a time off entry
    const timeOffElement = td.querySelector('.timeoff-corner');
    const hasTimeOff = timeOffElement !== null;
    const timeOffHours = hasTimeOff ? parseFloat(timeOffElement.textContent) : 0;

    if (hasRegularEntries || hasTimeOff) {
      // If only time off exists, pass is_timeoff=1
      const options = {
        userId,
        userName,
        weekStart,
        is_timeoff: (!hasRegularEntries && hasTimeOff) ? 1 : 0,
        timeOffHours
      };
      openManageEntryModal(options);
    } else {
      openAddEntryModal(userId, userName, weekStart);
    }
  });
});
