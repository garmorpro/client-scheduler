function filterEmployees() {
    const searchTerm = document.getElementById('searchInput').value.toLowerCase();
    const rows = document.querySelectorAll('#employeesTableBody tr');
    const activeRoles = Array.from(document.querySelectorAll('.role-checkbox:checked')).map(cb => cb.value.toLowerCase());

    rows.forEach(row => {
        const nameCell = row.querySelector('.employee-name').textContent.toLowerCase();
        const role = row.dataset.role.toLowerCase();
        const matchesSearch = nameCell.includes(searchTerm);
        const matchesRole = activeRoles.includes(role);

        row.style.display = (matchesSearch && matchesRole) ? '' : 'none';
    });

    updateLastRowRadius(); // <-- ADD THIS
}

document.querySelectorAll('.role-checkbox').forEach(cb => {
    cb.addEventListener('change', filterEmployees);
});

document.getElementById('roleManager').checked = false;
filterEmployees();