let selectedRole = "all";

const roleFilterMenu = document.getElementById('roleFilterMenu');
const roleFilterBtn = document.getElementById('roleFilterBtn');

roleFilterMenu.addEventListener('click', function (e) {
    e.preventDefault();

    if (!e.target.dataset.role) return;

    // Remove active from all
    document.querySelectorAll('#roleFilterMenu .dropdown-item')
        .forEach(item => item.classList.remove('active'));

    // Add active to selected
    e.target.classList.add('active');

    selectedRole = e.target.dataset.role;
    roleFilterBtn.innerText = e.target.innerText;

    currentPage = 1;
    applyFilters();
});

function applyFilters() {
    const searchValue = searchInput.value.toLowerCase();

    const searchTerms = searchValue
        .split(',')
        .map(term => term.trim())
        .filter(term => term.length > 0);

    filteredRows = rows.filter(row => {
        const text = row.innerText.toLowerCase();

        const roleCell = row.children[3].innerText.toLowerCase();

        // Role filter
        const roleMatch =
            selectedRole === "all" ||
            roleCell.includes(selectedRole);

        // Search filter
        const searchMatch =
            searchTerms.length === 0 ||
            searchTerms.some(term => text.includes(term));

        return roleMatch && searchMatch;
    });

    renderTable();
}

// Replace your old search listener with this:
searchInput.addEventListener('input', function () {
    currentPage = 1;
    applyFilters();
});
