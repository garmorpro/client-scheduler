document.addEventListener('DOMContentLoaded', function () {
  function setupTable(searchInputId, tableId, paginationId, perPage = 5) {
    const searchInput = searchInputId ? document.getElementById(searchInputId) : null;
    const allRows = Array.from(document.querySelectorAll(`#${tableId} tbody tr`));
    const pagination = document.getElementById(paginationId);
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

    // Only add search if searchInput exists
    if (searchInput) {
      searchInput.addEventListener('input', function () {
        const value = this.value.trim();

        if (value.length >= 3) {
          filteredRows = filterRows(value);
        } else {
          filteredRows = [...allRows];
        }

        showPage(1);
      });
    }

    // Initial load
    showPage(1);
  }

  // Users table (search + pagination, 5 per page)
  setupTable('userSearch', 'user-table', 'pagination-users', 5);

  // Engagement table (search + pagination, 5 per page)
  setupTable('engagementSearch', 'engagement-table', 'pagination-engagements', 5);

  // System Activity (pagination only, 3 per page)
  const activityList = document.getElementById('activity-list');
  const activityCards = Array.from(activityList.querySelectorAll('.activity-card'));
  const pagination = document.getElementById('activity-pagination');
  const perPage = 3;
  let currentPage = 1;

  function showActivityPage(page = 1) {
    currentPage = page;
    const start = (page - 1) * perPage;
    const end = start + perPage;

    activityCards.forEach(card => (card.style.display = 'none'));
    activityCards.forEach((card, index) => {
      if (index >= start && index < end) {
        card.style.display = 'flex';
      }
    });

    renderActivityPagination();
  }

  function renderActivityPagination() {
    pagination.innerHTML = '';
    const pageCount = Math.ceil(activityCards.length / perPage);

    if (pageCount <= 1) {
      pagination.style.display = 'none';
      return;
    }
    pagination.style.display = '';

    // Prev
    const prev = document.createElement('li');
    prev.className = `page-item ${currentPage === 1 ? 'disabled' : ''}`;
    prev.innerHTML = `<a class="page-link" href="#">«</a>`;
    prev.addEventListener('click', e => {
      e.preventDefault();
      if (currentPage > 1) showActivityPage(currentPage - 1);
    });
    pagination.appendChild(prev);

    // Pages
    for (let i = 1; i <= pageCount; i++) {
      const li = document.createElement('li');
      li.className = `page-item ${i === currentPage ? 'active' : ''}`;
      const a = document.createElement('a');
      a.className = 'page-link';
      a.href = '#';
      a.textContent = i;

      a.addEventListener('click', e => {
        e.preventDefault();
        showActivityPage(i);
      });

      li.appendChild(a);
      pagination.appendChild(li);
    }

    // Next
    const next = document.createElement('li');
    next.className = `page-item ${currentPage === pageCount ? 'disabled' : ''}`;
    next.innerHTML = `<a class="page-link" href="#">»</a>`;
    next.addEventListener('click', e => {
      e.preventDefault();
      if (currentPage < pageCount) showActivityPage(currentPage + 1);
    });
    pagination.appendChild(next);
  }

  // Initial load system activity
  showActivityPage(1);
});
