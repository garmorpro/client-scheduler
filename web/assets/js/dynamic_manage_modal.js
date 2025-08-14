document.addEventListener('DOMContentLoaded', () => {
  let currentUserId = null;
  let currentUserName = null;
  let currentWeekStart = null;

  // Cache modals
  const manageAddModalEl = document.getElementById('manageEntryPromptModal');
  const manageAddModal = new bootstrap.Modal(manageAddModalEl);
  const entriesListContainer = document.getElementById('entriesListContainer');
  const addEntriesButton2 = document.getElementById('addEntriesButton2');

  // Add Entry Modal
  const addEntryModalEl = document.getElementById('addEntryModal');
  const addEntryModal = new bootstrap.Modal(addEntryModalEl);

  // 1) Attach click listeners to all cells with class "addable"
  document.querySelectorAll('.addable').forEach(cell => {
    cell.addEventListener('click', (e) => {
      e.stopPropagation();

      currentUserId = cell.getAttribute('data-user-id');
      currentUserName = cell.getAttribute('data-user-name') || '';
      currentWeekStart = cell.getAttribute('data-week-start');

      // Convert week start string to Date object
      let formattedWeekStart = currentWeekStart ? new Date(currentWeekStart) : null;
      let displayWeekStart = formattedWeekStart 
          ? formattedWeekStart.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
          : '—';

      // Fill user info section
      // document.getElementById('entryUserId').textContent = currentUserId;
      document.getElementById('entryUserName').textContent = currentUserName || '—';
      document.getElementById('entryWeekStart').textContent = displayWeekStart;

      entriesListContainer.innerHTML = '<p class="text-muted">Loading entries...</p>';

      // Fetch and render entries immediately
      fetch(`get_entries.php?user_id=${encodeURIComponent(currentUserId)}&week_start=${encodeURIComponent(currentWeekStart)}`)
        .then(res => {
          if (!res.ok) throw new Error('Network response was not OK');
          return res.json();
        })
        .then(entries => renderEntriesList(entries))
        .catch(() => {
          entriesListContainer.innerHTML = '<p class="text-danger">Error loading entries.</p>';
        });

      manageAddModal.show();
    });
  });

  // 2) Add Entry button
  addEntriesButton2.addEventListener('click', () => {
    manageAddModal.hide();
    // Delay to ensure modal is fully hidden before opening the next
    setTimeout(() => {
      openAddEntryModal(currentUserId, currentUserName, currentWeekStart);
    }, 250);
  });

  // 3) Render entries list function
  function renderEntriesList(entriesForWeek) {
    entriesListContainer.innerHTML = '';

    if (!entriesForWeek || entriesForWeek.length === 0) {
      entriesListContainer.innerHTML = '<p class="text-muted">No entries for this week.</p>';
      return;
    }

    entriesForWeek.forEach(entry => {
      const card = document.createElement('div');
      card.classList.add('card', 'mb-3', 'shadow-sm', 'border-0');

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
