document.getElementById('userSearch').addEventListener('input', function () {
    const searchValue = this.value.toLowerCase();

    // Split by comma and trim spaces
    const searchTerms = searchValue
        .split(',')
        .map(term => term.trim())
        .filter(term => term.length > 0);

    const rows = document.querySelectorAll('#usersTableBody tr');
    let visibleCount = 0;

    rows.forEach(row => {
        const rowText = row.innerText.toLowerCase();

        // If no search terms, show all
        if (searchTerms.length === 0) {
            row.style.display = '';
            visibleCount++;
            return;
        }

        // Check if ANY search term matches the row
        const match = searchTerms.some(term => rowText.includes(term));

        if (match) {
            row.style.display = '';
            visibleCount++;
        } else {
            row.style.display = 'none';
        }
    });

    // Update "Showing X of Y" text
    const paginationInfo = document.querySelector('.pagination-info');
    if (paginationInfo) {
        paginationInfo.innerText = `Showing ${visibleCount} of <?= $totalUsers ?>`;
    }
});