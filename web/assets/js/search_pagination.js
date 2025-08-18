document.addEventListener('DOMContentLoaded', function () {
  function setupTable(searchInputId, tableId, paginationId) {
    const searchInput = document.getElementById(searchInputId);
    const allRows = Array.from(document.querySelectorAll(`#${tableId} tbody tr`));
    const pagination = document.getElementById(paginationId);
    const perPage = 5;
    let currentPage = 1;
    let filteredRows = [...allRows];

    function showPage(page = 1) {
      currentPage = page;
      const start = (page - 1) * perPage;
      const end = start + perPage;

      // Hide all rows
      allRows.forEach(row => row.style.display = 'none');

      // Show only rows for this page
      filteredRows.forEach((row, index) => {
        if (index >= start && index < end) {
          row.style.display = '';
        }
      });

      renderPagination();
    }

    function renderPagination() {
      pagination.innerHTML = '';
      const pageCount = Math.ceil(filteredRows.length / perPage);

      if (pageCount <= 1) {
        pagination.style.display = 'none';
        return;
      }
      pagination.style.display = '';

      // Prev button
      const prev = document.createElement('li');
      prev.className = `page-item ${currentPage === 1 ? 'disabled' : ''}`;
      prev.innerHTML = `<a class="page-link" href="#">«</a>`;
      prev.addEventListener('click', e => {
        e.preventDefault();
        if (currentPage > 1) showPage(currentPage - 1);
      });
      pagination.appendChild(prev);

      // Page numbers
      for (let i = 1; i <= pageCount; i++) {
        const li = document.createElement('li');
        li.className = `page-item ${i === currentPage ? 'active' : ''}`;
        const a = document.createElement('a');
        a.className = 'page-link';
        a.href = '#';
        a.textContent = i;

        a.addEventListener('click', function (e) {
          e.preventDefault();
          showPage(i);
        });

        li.appendChild(a);
        pagination.appendChild(li);
      }

      // Next button
      const next = document.createElement('li');
      next.className = `page-item ${currentPage === pageCount ? 'disabled' : ''}`;
      next.innerHTML = `<a class="page-link" href="#">»</a>`;
      next.addEventListener('click', e => {
        e.preventDefault();
        if (currentPage < pageCount) showPage(currentPage + 1);
      });
      pagination.appendChild(next);
    }

    function filterRows(query) {
      const terms = query
        .split(',')
        .map(t => t.trim().toLowerCase())
        .filter(t => t.length > 0);

      if (terms.length === 0) return allRows;

      return allRows.filter(row => {
        const rowText = row.innerText.toLowerCase();
        return terms.some(term => rowText.includes(term));
      });
    }

    searchInput.addEventListener('input', function () {
      const value = this.value.trim();

      if (value.length >= 3) {
        filteredRows = filterRows(value);
      } else {
        filteredRows = [...allRows];
      }

      showPage(1);
    });

    // Initial load
    showPage(1);
  }

  // Setup both tables
  setupTable('userSearch', 'user-table', 'pagination-users');
  setupTable('engagementSearch', 'engagement-table', 'pagination-engagements');
});