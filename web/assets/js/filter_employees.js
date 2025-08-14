function filterEmployees() {
  const query = document.getElementById('searchInput').value.trim().toLowerCase();

  // Only search if 3 or more chars (or at least one valid term)
  if (!query || query.length < 3) {
    // Show all rows if less than 3 characters
    document.querySelectorAll('#employeesTableBody tr').forEach(row => {
      row.style.display = '';
    });
    return;
  }

  // Split query by commas and trim spaces
  const terms = query.split(',').map(t => t.trim()).filter(t => t.length > 0);

  // Loop through all employee rows
  document.querySelectorAll('#employeesTableBody tr').forEach(row => {
    const nameCell = row.querySelector('.employee-name');
    if (!nameCell) return;

    const nameText = nameCell.textContent.toLowerCase();

    // Check if any term matches
    const matches = terms.some(term => nameText.includes(term));

    row.style.display = matches ? '' : 'none';
  });
}
