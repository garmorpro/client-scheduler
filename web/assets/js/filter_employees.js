function filterEmployees() {
  const query = document.getElementById('searchInput').value.trim().toLowerCase();
  const activeRoles = Array.from(document.querySelectorAll('.role-checkbox:checked'))
                           .map(cb => cb.value.toLowerCase());

  // Split query by commas and trim spaces
  const terms = query ? query.split(',').map(t => t.trim()).filter(t => t.length > 0) : [];

  document.querySelectorAll('#employeesTableBody tr').forEach(row => {
    const role = row.dataset.role.toLowerCase();
    const nameCell = row.querySelector('.employee-name');
    const nameText = nameCell ? nameCell.textContent.toLowerCase() : '';

    // Role match
    const matchesRole = activeRoles.includes(role);

    // Name match
    const matchesSearch = terms.length === 0 || terms.some(term => nameText.includes(term));

    // Show only if both match
    row.style.display = (matchesRole && matchesSearch) ? '' : 'none';
  });
}

// Update table whenever a role checkbox changes
document.querySelectorAll('.role-checkbox').forEach(cb => {
  cb.addEventListener('change', filterEmployees);
});

// Update table whenever the search input changes
document.getElementById('searchInput').addEventListener('keyup', filterEmployees);

// Initial filter on page load
document.addEventListener('DOMContentLoaded', () => {
  // Make sure manager is unchecked by default
  document.getElementById('roleManager').checked = false;
  filterEmployees();
});
