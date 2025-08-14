document.addEventListener('DOMContentLoaded', () => {
  const table = document.querySelector('#employeesTableBody');
  // console.log("Table body:", table);

  if (!table) return; // stop if not found

  table.addEventListener('click', (e) => {
    const td = e.target.closest('td.addable');
    console.log("Clicked TD:", td);
    if (!td) return;

    e.stopPropagation();

    const userId = td.dataset.userId;
    const userName = td.dataset.userName;
    const weekStart = td.dataset.weekStart;

    // console.log("Clicked cell values:");
    // console.log("User ID:", userId);
    // console.log("User Name:", userName);
    // console.log("Week Start:", weekStart);

    const hasEntries = td.querySelector('.badge-status') !== null;
    // console.log("Has Entries:", hasEntries);

    if (hasEntries === true) {
      openManageEntryModal(userId, userName, weekStart);
    } else {
      openAddEntryModal(userId, userName, weekStart);
    }
  });
});
