document.addEventListener('DOMContentLoaded', () => {
  const table = document.querySelector('#employeesTableBody');

  table.addEventListener('click', (e) => {
    const td = e.target.closest('td.addable');
    if (!td) return;

    e.stopPropagation();

    const userId = td.dataset.userId;
    const userName = td.dataset.userName;
    const weekStart = td.dataset.weekStart;

    // Determine if the cell has entries
    const hasEntries = td.querySelector('.badge-status') !== null;

    if (hasEntries) {
      openManageEntryModal(userId, userName, weekStart);
    } else {
      openAddEntryModal(userId, userName, 15);
    }
  });
});