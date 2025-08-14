document.addEventListener('DOMContentLoaded', () => {
  let currentUserId = null;
  let currentUserName = null;
  let currentWeekStart = null;

  // Cache modals
  const manageAddModalEl = document.getElementById('manageEntryPromptModal');
  const manageAddModal = new bootstrap.Modal(manageAddModalEl);
  const manageAddButtons = document.getElementById('manageAddButtons');
  const entriesListing = document.getElementById('entriesListing');
  const entriesListContainer = document.getElementById('entriesListContainer');
  const manageEntriesButton = document.getElementById('manageEntriesButton');
  const addEntriesButton = document.getElementById('addEntriesButton');
  const backToButtons = document.getElementById('backToButtons');

  // Add Entry Modal
  const addEntryModalEl = document.getElementById('addEntryModal');
  const addEntryModal = new bootstrap.Modal(addEntryModalEl);

  // 1) Attach click listeners to all cells with class "addable"
  document.querySelectorAll('.addable').forEach(cell => {
    cell.addEventListener('click', (e) => {
      e.stopPropagation();

      currentUserId = cell.getAttribute('data-user-id');
      currentUserName = cell.getAttribute('data-user-name') || null;

      // --- ADD 1 DAY TO WEEK START ---
      let weekStartDate = new Date(cell.getAttribute('data-week-start'));
      weekStartDate.setDate(weekStartDate.getDate() + 1);
      currentWeekStart = weekStartDate.toISOString().split('T')[0];
      // -------------------------------

      const hasEntries = cell.querySelectorAll('.badge').length > 0;

      if (hasEntries) {
        manageAddButtons.classList.remove('d-none');
        entriesListing.classList.add('d-none');
        entriesListContainer.innerHTML = '';
        manageAddModal.show();
      } else {
        openAddEntryModal(currentUserId, currentUserName, currentWeekStart);
      }
    });
  });

  // 2) When clicking "Manage Existing Entries"
  manageEntriesButton.addEventListener('click', () => {
    if (!currentUserId || !currentWeekStart) {
      entriesListContainer.innerHTML = '<p class="text-danger">Missing user or week info.</p>';
      return;
    }

    manageAddButtons.classList.add('d-none');
    entriesListing.classList.remove('d-none');
    entriesListContainer.innerHTML = '<p>Loading entries...</p>';

    fetch(`get_entries.php?user_id=${encodeURIComponent(currentUserId)}&week_start=${encodeURIComponent(currentWeekStart)}`)
      .then(response => {
        if (!response.ok) throw new Error('Network response was not OK');
        return response.json();
      })
      .then(entries => {
        renderEntriesList(entries);
      })
      .catch(error => {
        console.error('Error fetching entries:', error);
        entriesListContainer.innerHTML = `<p class="text-danger">Error loading entries.</p>`;
      });
  });

  // 3) Clicking "Add New Entry" button in manageAddModal
  addEntriesButton.addEventListener('click', () => {
    manageAddModal.hide();
    // Wait for the fade-out before showing AddEntry modal
    setTimeout(() => {
        openAddEntryModal(currentUserId, currentUserName, currentWeekStart);
    }, 250); // Bootstrap fade ~250ms
  });

  // 4) Back button inside manageAddModal
  backToButtons.addEventListener('click', () => {
    manageAddButtons.classList.remove('d-none');
    entriesListing.classList.add('d-none');
  });

  // 5) Render entries list function
  function renderEntriesList(entriesForWeek) {
    entriesListContainer.innerHTML = '';

    if (!entriesForWeek || entriesForWeek.length === 0) {
      entriesListContainer.innerHTML = '<p class="text-muted">No entries for this week.</p>';
      return;
    }

    entriesForWeek.forEach(entry => {
      const card = document.createElement('div');
      card.classList.add('card', 'mb-3', 'shadow-sm');

      const cardBody = document.createElement('div');
      cardBody.classList.add('card-body', 'd-flex', 'justify-content-between', 'align-items-center');

      const leftDiv = document.createElement('div');
      leftDiv.innerHTML = `
        <div class="fw-semibold fs-6">${entry.client_name || (entry.type === 'Time Off' ? 'Time Off' : 'Unnamed Client')}</div>
        <small class="text-muted">Assigned Hours: ${entry.assigned_hours || 0}</small>
      `;

      const rightDiv = document.createElement('div');

      const editLink = document.createElement('a');
      editLink.href = "#";
      editLink.title = "Edit Entry";
      editLink.className = "text-primary me-3";
      editLink.style = "font-size: 1.25rem; cursor: pointer; text-decoration: none;";
      editLink.innerHTML = `<i class="bi bi-pencil-square" style="font-size: 16px;"></i>`;
      editLink.setAttribute('data-entry-id', entry.entry_id);
      editLink.setAttribute('data-assigned-hours', entry.assigned_hours || 0);
      editLink.onclick = (e) => {
        e.preventDefault();
        openEditModal(e);
      };

      const deleteLink = document.createElement('a');
      deleteLink.href = "#";
      deleteLink.title = "Delete Entry";
      deleteLink.className = "text-danger";
      deleteLink.style = "font-size: 1.25rem; cursor: pointer; text-decoration: none;";
      deleteLink.innerHTML = `<i class="bi bi-trash" style="font-size: 16px;"></i>`;
      deleteLink.onclick = (e) => {
        e.preventDefault();
        deleteEntry(entry.entry_id);
      };

      rightDiv.appendChild(editLink);
      rightDiv.appendChild(deleteLink);

      cardBody.appendChild(leftDiv);
      cardBody.appendChild(rightDiv);
      card.appendChild(cardBody);
      entriesListContainer.appendChild(card);
    });
  }
});
