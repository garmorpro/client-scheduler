function searchQuery() {
      var query = document.getElementById('searchInput').value;
      console.log(query); // Debug log to check the query value
      if (query.length >= 3) {
          fetchSearchResults(query);
      } else {
          document.getElementById('searchResults').style.display = 'none';
      }
  }

  function fetchSearchResults(query) {
      fetch('search.php?query=' + query)
          .then(response => response.json())
          .then(data => {
              let resultsHTML = '';
              data.forEach(result => {
                  resultsHTML += `<a href="#" class="dropdown-item" onclick="openModal(${result.id}, '${result.type}')">${result.name}</a>`;
              });
              if (resultsHTML === '') {
                  resultsHTML = `<a href="#" class="dropdown-item">No results found</a>`;
              }
              document.getElementById('searchResults').innerHTML = resultsHTML;
              document.getElementById('searchResults').style.display = 'block';
          })
          .catch(error => console.error('Error fetching search results:', error));
  }

  function openModal(id, type) {
      if (type === 'employee') {
          openEmployeeModal(id); // Ensure you have this modal function defined
      } else if (type === 'client') {
          openEntryModal(id); // Ensure you have this modal function defined
      }
  }