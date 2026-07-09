function filterEmployees() {
  const query = document.getElementById('searchInput').value.trim().toLowerCase();
  const activeRoles = Array.from(document.querySelectorAll('.role-checkbox:checked'))
                           .map(cb => cb.value.toLowerCase());
  const selectedClients = Array.from(document.querySelectorAll('.client-checkbox:checked'))
                                .map(cb => cb.value)
                                .filter(v => v !== '');

  // Split query by commas and trim spaces
  const terms = query ? query.split(',').map(t => t.trim()).filter(t => t.length > 0) : [];

  document.querySelectorAll('#employeesTableBody tr').forEach(row => {
    const role = row.dataset.role.toLowerCase();
    const nameCell = row.querySelector('.employee-name');
    const nameText = nameCell ? nameCell.textContent.toLowerCase() : '';

    // Role match
    const matchesRole = activeRoles.includes(role);

    // Name match
    const matchesSearch = terms.length === 0 || terms.some(term => nameText.includes(term));

    // Client match: row must have at least one entry badge for a selected client
    let matchesClient = true;
    if (selectedClients.length > 0) {
      const badges = row.querySelectorAll('[data-client-name]');
      matchesClient = Array.from(badges).some(b => selectedClients.includes(b.dataset.clientName));
    }

    // Show only if all match
    row.style.display = (matchesRole && matchesSearch && matchesClient) ? '' : 'none';
  });

  const countEl = document.getElementById('clientFilterCount');
  if (countEl) {
    countEl.textContent = selectedClients.length > 0 ? ` (${selectedClients.length})` : '';
  }

  updateLastRowRadius(); // <-- ADD THIS
}

// Update table whenever a role checkbox changes
document.querySelectorAll('.role-checkbox').forEach(cb => {
  cb.addEventListener('change', filterEmployees);
});

// Update table whenever the search input changes
const searchInput = document.getElementById('searchInput');
searchInput.addEventListener('keyup', filterEmployees);

// Handle the built-in "×" clear button in type="search" inputs
searchInput.addEventListener('search', () => {
  filterEmployees();
});

// Client filter: "All Clients" is mutually exclusive with picking specific clients
function setupClientFilter() {
  const allClientsCb = document.getElementById('clientAll');
  if (!allClientsCb) return; // this page doesn't have a client filter

  const specificClientCbs = Array.from(document.querySelectorAll('.client-checkbox'))
                                  .filter(cb => cb !== allClientsCb);

  allClientsCb.addEventListener('change', () => {
    if (allClientsCb.checked) {
      specificClientCbs.forEach(cb => cb.checked = false);
    } else if (specificClientCbs.every(cb => !cb.checked)) {
      // Don't allow leaving every client checkbox unchecked
      allClientsCb.checked = true;
      return;
    }
    filterEmployees();
  });

  specificClientCbs.forEach(cb => {
    cb.addEventListener('change', () => {
      if (cb.checked) {
        allClientsCb.checked = false;
      } else if (specificClientCbs.every(c => !c.checked)) {
        allClientsCb.checked = true;
      }
      filterEmployees();
    });
  });
}

// Client search box: narrows the checkbox list itself, separate from
// filterEmployees() which acts on the schedule table.
function setupClientSearch() {
  const searchBox = document.getElementById('clientFilterSearch');
  const list = document.getElementById('clientCheckboxList');
  if (!searchBox || !list) return;

  const items = Array.from(list.querySelectorAll('li[data-client-search]'));
  const noResults = document.getElementById('clientFilterNoResults');

  searchBox.addEventListener('click', e => e.stopPropagation());
  searchBox.addEventListener('keydown', e => e.stopPropagation());

  searchBox.addEventListener('input', () => {
    const query = searchBox.value.trim().toLowerCase();
    const terms = query ? query.split(',').map(t => t.trim()).filter(t => t.length > 0) : [];
    let visibleCount = 0;

    items.forEach(li => {
      const matches = terms.length === 0 || terms.some(term => li.dataset.clientSearch.includes(term));
      li.classList.toggle('d-none', !matches);
      if (matches) visibleCount++;
    });

    if (noResults) noResults.classList.toggle('d-none', visibleCount > 0);
  });
}

// Initial filter on page load
document.addEventListener('DOMContentLoaded', () => {
  // Make sure manager is unchecked by default
  document.getElementById('roleManager').checked = false;
  setupClientFilter();
  setupClientSearch();
  filterEmployees();
});
