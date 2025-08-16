document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('engagementSearch');
    const allRows = Array.from(document.querySelectorAll('#engagement-table tbody tr'));
    const pagination = document.getElementById('pagination-engagements');
    const perPage = 5;
                      
    function showPage(rows, page = 1) {
        const start = (page - 1) * perPage;
        const end = start + perPage;
    
        // Hide all rows first
        allRows.forEach(row => row.style.display = 'none');
    
        // Show only the current page's rows
        rows.forEach((row, index) => {
            if (index >= start && index < end) {
                row.style.display = '';
            }
        });
      
        // Toggle pagination visibility
        pagination.style.display = rows.length <= perPage ? 'none' : '';
    }
  
    function filterRows(query) {
        // Split query by commas, trim spaces, remove empty terms
        const terms = query.split(',').map(t => t.trim().toLowerCase()).filter(t => t.length > 0);
    
        if (terms.length === 0) return allRows;
    
        return allRows.filter(row => {
            const rowText = row.innerText.toLowerCase();
            // Match if any term exists in the row
            return terms.some(term => rowText.includes(term));
        });
    }
  
    searchInput.addEventListener('input', function () {
        const value = this.value.trim();
    
        if (value.length >= 3) {
            const filtered = filterRows(value);
            showPage(filtered, 1);
        } else {
            showPage(allRows, 1);
        }
    });
  
    // Initial load with default pagination
    showPage(allRows, 1);
});


document.addEventListener('DOMContentLoaded', function () {
  const searchInput = document.getElementById('userSearch');
  const allRows = Array.from(document.querySelectorAll('#user-table tbody tr'));
  const pagination = document.getElementById('pagination-users');
  const perPage = 5;

  function showPage(rows, page = 1) {
    const start = (page - 1) * perPage;
    const end = start + perPage;
  
    // Hide all rows first
    allRows.forEach(row => row.style.display = 'none');
  
    // Show only the current page's rows
    rows.forEach((row, index) => {
      if (index >= start && index < end) {
        row.style.display = '';
      }
    });
  
    // Toggle pagination visibility
    pagination.style.display = rows.length <= perPage ? 'none' : '';
  }

  function filterRows(query) {
    // Split query by commas, trim spaces, and filter out empty terms
    const terms = query.split(',').map(t => t.trim().toLowerCase()).filter(t => t.length > 0);
  
    if (terms.length === 0) return allRows;
  
    return allRows.filter(row => {
      const rowText = row.innerText.toLowerCase();
      // Match if any term is found in row text
      return terms.some(term => rowText.includes(term));
    });
  }

  searchInput.addEventListener('input', function () {
    const value = this.value.trim();

    if (value.length >= 3) {
      const filtered = filterRows(value);
      showPage(filtered, 1);
    } else {
      showPage(allRows, 1);
    }
  });

  // Initial load with default pagination
  showPage(allRows, 1);
});