const rows = Array.from(document.querySelectorAll('#usersTableBody tr'));
const searchInput = document.getElementById('userSearch');
const paginationContainer = document.getElementById('pagination');
const paginationInfo = document.querySelector('.pagination-info');

const perPage = 10;
let currentPage = 1;
let filteredRows = [...rows];

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

searchInput.addEventListener('input', function () {
    const value = this.value.toLowerCase();

    const searchTerms = value
        .split(',')
        .map(term => term.trim())
        .filter(term => term.length > 0);

    filteredRows = rows.filter(row => {
        const text = row.innerText.toLowerCase();

        if (searchTerms.length === 0) return true;

        return searchTerms.some(term => text.includes(term));
    });

    currentPage = 1;
    renderTable();
});

// Initial render
renderTable();