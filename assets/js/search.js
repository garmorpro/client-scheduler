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
      fetch('search.php?query=' + encodeURIComponent(query))
          .then(response => response.json())
          .then(data => {
              const container = document.getElementById('searchResults');
              container.innerHTML = '';

              if (data.length === 0) {
                  const empty = document.createElement('a');
                  empty.href = '#';
                  empty.className = 'dropdown-item';
                  empty.textContent = 'No results found';
                  container.appendChild(empty);
              } else {
                  data.forEach(result => {
                      const link = document.createElement('a');
                      link.href = '#';
                      link.className = 'dropdown-item';
                      link.textContent = result.name;
                      link.addEventListener('click', function (e) {
                          e.preventDefault();
                          openModal(result.id, result.type);
                      });
                      container.appendChild(link);
                  });
              }

              container.style.display = 'block';
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