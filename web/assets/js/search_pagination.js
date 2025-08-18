document.addEventListener('DOMContentLoaded', () => {

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

  // User Management pagination (5 rows per page)
  function initUserPagination() {
    const rowsPerPage = 5;
    const table = document.getElementById('user-table');
    if (!table) return;
    const tbody = table.querySelector('tbody');
    const rows = Array.from(tbody.querySelectorAll('tr'));
    const paginationContainer = document.getElementById('pagination-users');

    let currentPage = 1;
    const totalPages = Math.ceil(rows.length / rowsPerPage);

    function renderTablePage(page) {
      currentPage = page;
      rows.forEach(row => (row.style.display = 'none'));
      const start = (page - 1) * rowsPerPage;
      const end = start + rowsPerPage;
      rows.slice(start, end).forEach(row => (row.style.display = ''));
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
        renderTablePage(page);
      });
      paginationContainer.appendChild(paginationControls);
    }

    // Optional: filter by search input
    const searchInput = document.getElementById('userSearch');
    if (searchInput) {
      searchInput.addEventListener('input', function () {
        const value = this.value.trim().toLowerCase();
        const filteredRows = rows.filter(row => row.innerText.toLowerCase().includes(value));
        rows.forEach(row => (row.style.display = 'none'));
        filteredRows.forEach((row, index) => {
          if (index < rowsPerPage) row.style.display = '';
        });
        currentPage = 1;
        renderPagination();
      });
    }

    renderTablePage(1);
  }

  // Engagement Management pagination (5 rows per page)
  function initEngagementPagination() {
    const rowsPerPage = 5;
    const table = document.querySelector('#tab-engagements table');
    if (!table) return;
    const tbody = table.querySelector('tbody');
    const rows = Array.from(tbody.querySelectorAll('tr'));
    const paginationContainer = document.getElementById('pagination-engagements');

    let currentPage = 1;
    const totalPages = Math.ceil(rows.length / rowsPerPage);

    function renderTablePage(page) {
      currentPage = page;
      rows.forEach(row => (row.style.display = 'none'));
      const start = (page - 1) * rowsPerPage;
      const end = start + rowsPerPage;
      rows.slice(start, end).forEach(row => (row.style.display = ''));
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
        renderTablePage(page);
      });
      paginationContainer.appendChild(paginationControls);
    }

    // Optional: filter by search input
    const searchInput = document.getElementById('engagementSearch');
    if (searchInput) {
      searchInput.addEventListener('input', function () {
        const value = this.value.trim().toLowerCase();
        const filteredRows = rows.filter(row => row.innerText.toLowerCase().includes(value));
        rows.forEach(row => (row.style.display = 'none'));
        filteredRows.forEach((row, index) => {
          if (index < rowsPerPage) row.style.display = '';
        });
        currentPage = 1;
        renderPagination();
      });
    }

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