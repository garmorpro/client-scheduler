// ===== INITIAL SETUP =====
const rows = Array.from(document.querySelectorAll('#usersTableBody tr'));
const searchInput = document.getElementById('userSearch');
const paginationContainer = document.getElementById('pagination');
const paginationInfo = document.querySelector('.pagination-info');

const roleCheckboxes = document.querySelectorAll('.role-checkbox');
const clearRolesBtn = document.getElementById('clearRoles');

const perPage = 10;
let currentPage = 1;

// Default: all checked roles
let selectedRoles = Array.from(roleCheckboxes)
    .filter(cb => cb.checked)
    .map(cb => cb.value);

let filteredRows = [...rows];


// ===== PAGINATION RENDER =====
function renderTable() {
    rows.forEach(row => row.style.display = 'none');

    const start = (currentPage - 1) * perPage;
    const end = start + perPage;

    const pageRows = filteredRows.slice(start, end);

    pageRows.forEach(row => row.style.display = '');

    updatePagination();
    updateInfo();
}

function updatePagination() {
    paginationContainer.innerHTML = '';

    const totalPages = Math.ceil(filteredRows.length / perPage);
    if (totalPages <= 1) return;

    for (let i = 1; i <= totalPages; i++) {
        const li = document.createElement('li');
        li.className = 'page-item ' + (i === currentPage ? 'active' : '');

        const a = document.createElement('a');
        a.className = 'page-link';
        a.href = '#';
        a.innerText = i;

        a.addEventListener('click', function (e) {
            e.preventDefault();
            currentPage = i;
            renderTable();
        });

        li.appendChild(a);
        paginationContainer.appendChild(li);
    }
}

function updateInfo() {
    if (filteredRows.length === 0) {
        paginationInfo.innerText = 'No users found';
        return;
    }

    const start = (currentPage - 1) * perPage + 1;
    const end = Math.min(currentPage * perPage, filteredRows.length);

    paginationInfo.innerText = `Showing ${start}–${end} of ${filteredRows.length}`;
}


// ===== FILTER LOGIC =====
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

    currentPage = 1;
    renderTable();
}


// ===== SEARCH LISTENER =====
searchInput.addEventListener('input', function () {
    applyFilters();
});


// ===== ROLE CHECKBOX LISTENERS =====
roleCheckboxes.forEach(checkbox => {
    checkbox.addEventListener('change', function () {
        selectedRoles = Array.from(roleCheckboxes)
            .filter(cb => cb.checked)
            .map(cb => cb.value);

        applyFilters();
    });
});


// ===== CLEAR ROLES BUTTON =====
clearRolesBtn.addEventListener('click', function () {
    roleCheckboxes.forEach(cb => cb.checked = false);
    selectedRoles = [];
    applyFilters();
});


// ===== INITIAL RENDER =====
renderTable();