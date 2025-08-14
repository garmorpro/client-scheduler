document.addEventListener('DOMContentLoaded', () => {
  const table = document.querySelector('#employeesTableBody');

  table.addEventListener('click', (e) => {
    const td = e.target.closest('td.addable');
    if (!td) return;

    e.stopPropagation();

    const userId = td.dataset.userId;
    const userName = td.dataset.userName;
    const weekStart = td.dataset.weekStart;

    console.log("Clicked cell values:");
    console.log("User ID:", userId);
    console.log("User Name:", userName);
    console.log("Week Start:", weekStart);

    // Determine if the cell has entries
    const hasEntries = td.querySelector('.badge-status') !== null;
    console.log("Has Entries:", hasEntries);

    if (hasEntries) {
      openManageEntryModal(userId, userName, 15);
    } else {
      openAddEntryModal(userId, userName, weekStart);
    }
  });
});
