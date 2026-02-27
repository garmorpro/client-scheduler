const roleCheckboxes = document.querySelectorAll('.role-checkbox');
// const roleFilterBtn = document.getElementById('roleFilterBtn');
const clearRolesBtn = document.getElementById('clearRoles');

let selectedRoles = ["admin", "manager", "senior", "staff"];

roleCheckboxes.forEach(checkbox => {
    checkbox.addEventListener('change', function () {
        selectedRoles = Array.from(roleCheckboxes)
            .filter(cb => cb.checked)
            .map(cb => cb.value);

        updateRoleButtonText();
        currentPage = 1;
        applyFilters();
    });
});

clearRolesBtn.addEventListener('click', function () {
    roleCheckboxes.forEach(cb => cb.checked = false);
    selectedRoles = [];
    // updateRoleButtonText();
    currentPage = 1;
    applyFilters();
});

// function updateRoleButtonText() {
//     roleFilterBtn.innerText = "Filter";
// }

function applyFilters() {
    const searchValue = searchInput.value.toLowerCase();

    const searchTerms = searchValue
        .split(',')
        .map(term => term.trim())
        .filter(term => term.length > 0);

    filteredRows = rows.filter(row => {
        const text = row.innerText.toLowerCase();
        const roleCell = row.children[3].innerText.toLowerCase();

        const roleMatch =
            selectedRoles.length === 0
                ? false
                : selectedRoles.some(role => roleCell.includes(role));

        const searchMatch =
            searchTerms.length === 0 ||
            searchTerms.some(term => text.includes(term));

        return roleMatch && searchMatch;
    });

    renderTable();
}