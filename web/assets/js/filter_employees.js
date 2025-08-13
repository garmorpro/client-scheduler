function filterEmployees() {
  const query = document.getElementById('searchInput').value.trim().toLowerCase();

  // Only search if 3 or more chars
  if (query.length < 3) {
    // Show all rows if less than 3 characters
    document.querySelectorAll('#employeesTableBody tr').forEach(row => {
      row.style.display = '';
    });
    return;
  }

  // Loop through all employee rows
  document.querySelectorAll('#employeesTableBody tr').forEach(row => {
    const nameCell = row.querySelector('.employee-name');
    if (!nameCell) return;

    const nameText = nameCell.textContent.toLowerCase();
    if (nameText.includes(query)) {
      row.style.display = ''; // show row
    } else {
      row.style.display = 'none'; // hide row
    }
  });
}
