document.addEventListener('DOMContentLoaded', () => {
  let currentUserId = null;
  let currentUserName = null;
  let currentWeekStart = null;

  // Cache modals
  const manageAddModalEl = document.getElementById('manageEntryPromptModal');
  const manageAddModal = new bootstrap.Modal(manageAddModalEl);
  const entriesListContainer = document.getElementById('entriesListContainer');
  const addEntriesButton = document.getElementById('addEntriesButton2');

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

      // Format week start to "Aug 11, 2025"
      const formattedWeekStart = currentWeekStart
        ? new Date(currentWeekStart).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
        : '—';

      // Fill user info section
      document.getElementById('entryUserName').textContent = currentUserName || '—';
      document.getElementById('entryWeekStart').textContent = formattedWeekStart;

      entriesListContainer.innerHTML = '<p class="text-muted">Loading entries...</p>';

      // Fetch and render entries
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
  addEntriesButton.addEventListener('click', () => {
    manageAddModal.hide();
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
      card.style.cursor = 'pointer';

      // Inside renderEntriesList
      card.addEventListener('click', () => {
        // Determine type for modal
        const entryType = entry.client_name ? 'Client Assignment' : 'Time Off';
        openEditModal(
          entry.entry_id,
          entry.assigned_hours,
          entry.client_name,
          currentUserName,
          currentWeekStart,
          entryType
        );
      });


      const cardBody = document.createElement('div');
      cardBody.classList.add('card-body', 'd-flex', 'justify-content-between', 'align-items-center');

      const leftDiv = document.createElement('div');
      leftDiv.innerHTML = `
        <div class="fw-semibold fs-6">${entry.client_name || (entry.type === 'Time Off' ? 'Time Off' : 'Unnamed Client')}</div>
        <small class="text-muted">Assigned Hours: ${entry.assigned_hours || 0}</small>
      `;

      const rightDiv = document.createElement('div');

      // Keep delete button only
      const deleteLink = document.createElement('a');
      deleteLink.href = "#";
      deleteLink.title = "Delete Entry";
      deleteLink.className = "text-danger";
      deleteLink.style = "font-size: 1.25rem; cursor: pointer; text-decoration: none;";
      deleteLink.innerHTML = `<i class="bi bi-trash" style="font-size: 16px;"></i>`;
      deleteLink.onclick = (e) => {
        e.preventDefault();
        e.stopPropagation(); // Prevent triggering card click
        deleteEntry(entry.entry_id);
      };

      rightDiv.appendChild(deleteLink);

      cardBody.appendChild(leftDiv);
      cardBody.appendChild(rightDiv);
      card.appendChild(cardBody);
      entriesListContainer.appendChild(card);
    });
  }

  // 4) Open edit modal function with extra details
  function openEditModal(entryId, assignedHours, clientName, userName, weekStart) {
    document.getElementById('editEntryId').value = entryId;
    document.getElementById('editAssignedHours').value = assignedHours;

    // Populate edit modal details section
    document.getElementById('editClientName').textContent = clientName || '—';
    // document.getElementById('editUserId').textContent = userId || '—';
    document.getElementById('editUserName').textContent = userName || '—';
    const formattedWeekStart = weekStart
      ? new Date(weekStart).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
      : '—';
    document.getElementById('editWeekStart').textContent = formattedWeekStart;

    // Hide manage modal before showing edit modal
    const manageModalInstance = bootstrap.Modal.getInstance(manageAddModalEl);
    if (manageModalInstance) manageModalInstance.hide();

    const editModal = new bootstrap.Modal(document.getElementById('editEntryModal'));
    editModal.show();
  }
});
