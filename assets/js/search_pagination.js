document.addEventListener('DOMContentLoaded', () => {

  // Works out how many rows fit in the visible viewport below the table,
  // so a tall monitor shows more rows per page than a laptop screen instead
  // of everyone getting the same fixed count regardless of window size.
  function calcRowsPerPage(table, options = {}) {
    const minRows = options.minRows ?? 5;
    const maxRows = options.maxRows ?? 25;
    const reserved = options.reserved ?? 140; // space for pagination controls + page padding below the table

    const sampleRow = table.querySelector('tbody tr');
    const rowHeight = sampleRow && sampleRow.getBoundingClientRect().height > 0
      ? sampleRow.getBoundingClientRect().height
      : 53;

    const tableTop = table.getBoundingClientRect().top;
    const available = window.innerHeight - tableTop - reserved;
    const rows = Math.floor(available / rowHeight);

    return Math.max(minRows, Math.min(maxRows, rows));
  }

  // Helper: create pagination controls (Prev, pages, Next)
  function createPaginationControls(totalPages, currentPage, onPageChange) {
    const ul = document.createElement('ul');
    ul.className = 'pagination justify-content-center';

    function createPageItem(label, disabled, active, clickHandler) {
      const li = document.createElement('li');
      li.className = 'page-item' + (disabled ? ' disabled' : '') + (active ? ' active' : '');
      const a = document.createElement('a');
      a.className = 'page-link';
      a.href = '#';
      a.innerText = label;
      if (!disabled) {
        a.addEventListener('click', e => {
          e.preventDefault();
          clickHandler();
        });
      }
      li.appendChild(a);
      return li;
    }

    // Prev button
    ul.appendChild(createPageItem('Prev', currentPage === 1, false, () => onPageChange(currentPage - 1)));

    // Determine visible page range (max 10 pages)
    const maxVisiblePages = 10;
    let startPage = 1;
    let endPage = totalPages;

    if (totalPages > maxVisiblePages) {
      if (currentPage < maxVisiblePages) {
        startPage = 1;
        endPage = maxVisiblePages;
      } else {
        endPage = currentPage;
        startPage = currentPage - maxVisiblePages + 1;
        if (endPage > totalPages) {
          endPage = totalPages;
          startPage = endPage - maxVisiblePages + 1;
        }
      }
    }

    // Add page number buttons
    for (let i = startPage; i <= endPage; i++) {
      ul.appendChild(createPageItem(i, false, i === currentPage, () => onPageChange(i)));
    }

    // Next button
    ul.appendChild(createPageItem('Next', currentPage === totalPages, false, () => onPageChange(currentPage + 1)));

    return ul;
  }

  // User Management pagination (rows per page sized to fit the viewport)
  function initUserPagination() {
    const table = document.getElementById('user-table');
    if (!table) return;
    const tbody = table.querySelector('tbody');
    const allRows = Array.from(tbody.querySelectorAll('tr'));
    const paginationContainer = document.getElementById('pagination-users');

    let currentPage = 1;
    let rowsPerPage = calcRowsPerPage(table);
    let filteredRows = [...allRows];

    function renderTablePage(page) {
      const totalPages = Math.max(1, Math.ceil(filteredRows.length / rowsPerPage));
      currentPage = Math.min(page, totalPages);
      allRows.forEach(row => (row.style.display = 'none'));
      const start = (currentPage - 1) * rowsPerPage;
      const end = start + rowsPerPage;
      filteredRows.slice(start, end).forEach(row => (row.style.display = ''));
      renderPagination(totalPages);
    }

    function renderPagination(totalPages) {
      paginationContainer.innerHTML = '';
      if (totalPages <= 1) {
        paginationContainer.style.display = 'none';
        return;
      }
      paginationContainer.style.display = 'flex';

      const paginationControls = createPaginationControls(totalPages, currentPage, page => {
        renderTablePage(page);
      });
      paginationContainer.appendChild(paginationControls);
    }

    // Optional: filter by search input
    const searchInput = document.getElementById('userSearch');
    if (searchInput) {
      searchInput.addEventListener('input', function () {
        const value = this.value.trim().toLowerCase();
        filteredRows = value
          ? allRows.filter(row => row.innerText.toLowerCase().includes(value))
          : [...allRows];
        renderTablePage(1);
      });
    }

    let resizeTimer;
    window.addEventListener('resize', () => {
      clearTimeout(resizeTimer);
      resizeTimer = setTimeout(() => {
        const newRowsPerPage = calcRowsPerPage(table);
        if (newRowsPerPage !== rowsPerPage) {
          rowsPerPage = newRowsPerPage;
          renderTablePage(1);
        }
      }, 200);
    });

    renderTablePage(1);
  }

  // Engagement Management pagination (rows per page sized to fit the viewport)
  function initEngagementPagination() {
    const table = document.querySelector('#engagements table');
    if (!table) return;
    const tbody = table.querySelector('tbody');
    const allRows = Array.from(tbody.querySelectorAll('tr'));
    const paginationContainer = document.getElementById('pagination-engagements');

    let currentPage = 1;
    let rowsPerPage = calcRowsPerPage(table);
    let filteredRows = [...allRows];

    function renderTablePage(page) {
        const totalPages = Math.max(1, Math.ceil(filteredRows.length / rowsPerPage));
        currentPage = Math.min(page, totalPages);
        allRows.forEach(row => (row.style.display = 'none'));
        const start = (currentPage - 1) * rowsPerPage;
        const end = start + rowsPerPage;
        filteredRows.slice(start, end).forEach(row => (row.style.display = ''));
        renderPagination(totalPages);
    }

    function renderPagination(totalPages) {
        paginationContainer.innerHTML = '';
        if (totalPages <= 1) {
            paginationContainer.style.display = 'none';
            return;
        }
        paginationContainer.style.display = 'flex';
        const paginationControls = createPaginationControls(totalPages, currentPage, page => {
            renderTablePage(page);
        });
        paginationContainer.appendChild(paginationControls);
    }

    // Search input
    const searchInput = document.getElementById('engagementSearch');
    if (searchInput) {
        searchInput.addEventListener('input', function () {
            const value = this.value.trim().toLowerCase();
            if (value.length >= 3) {
                filteredRows = allRows.filter(row => row.innerText.toLowerCase().includes(value));
            } else {
                filteredRows = [...allRows];
            }
            renderTablePage(1);
        });
    }

    let resizeTimer;
    window.addEventListener('resize', () => {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(() => {
            const newRowsPerPage = calcRowsPerPage(table);
            if (newRowsPerPage !== rowsPerPage) {
                rowsPerPage = newRowsPerPage;
                renderTablePage(1);
            }
        }, 200);
    });

    renderTablePage(1);
}


  // System Activity pagination (3 cards per page)
  function initActivityPagination() {
    const cardsPerPage = 3;
    const activityList = document.getElementById('activity-list');
    if (!activityList) return;
    const cards = Array.from(activityList.querySelectorAll('.activity-card'));
    const paginationContainer = document.getElementById('activity-pagination');

    let currentPage = 1;
    const totalPages = Math.ceil(cards.length / cardsPerPage);

    function showPage(page) {
      currentPage = page;
      cards.forEach(card => {
        card.style.display = 'none';
        card.classList.remove('d-flex');
      });
      const start = (page - 1) * cardsPerPage;
      const end = start + cardsPerPage;
      for (let i = start; i < end && i < cards.length; i++) {
        cards[i].style.display = '';
        cards[i].classList.add('d-flex');
      }
      renderPagination();
    }

    function renderPagination() {
      paginationContainer.innerHTML = '';
      if (totalPages <= 1) {
        paginationContainer.style.display = 'none';
        return;
      }
      paginationContainer.style.display = 'flex';

      const paginationControls = createPaginationControls(totalPages, currentPage, page => {
        showPage(page);
      });
      paginationContainer.appendChild(paginationControls);
    }

    showPage(1);
  }

  // Tab switching + reset pagination
  document.querySelectorAll('.custom-tabs button').forEach(btn => {
    btn.addEventListener('click', () => {
      document.querySelectorAll('.custom-tabs button').forEach(b => b.classList.remove('active'));
      btn.classList.add('active');

      document.querySelectorAll('.tab-content').forEach(tab => tab.classList.add('d-none'));
      const currentTab = document.getElementById('tab-' + btn.dataset.tab);
      currentTab.classList.remove('d-none');

      if (btn.dataset.tab === 'users') {
        document.dispatchEvent(new Event('reinitUserPagination'));
      } else if (btn.dataset.tab === 'activity') {
        document.dispatchEvent(new Event('reinitActivityPagination'));
      } else if (btn.dataset.tab === 'engagements') {
        document.dispatchEvent(new Event('reinitEngagementPagination'));
      }
    });
  });

  // Initialize paginations on DOM ready
  initUserPagination();
  initActivityPagination();
  initEngagementPagination();

  // Reinit paginations on tab switch
  document.addEventListener('reinitUserPagination', initUserPagination);
  document.addEventListener('reinitActivityPagination', initActivityPagination);
  document.addEventListener('reinitEngagementPagination', initEngagementPagination);

});