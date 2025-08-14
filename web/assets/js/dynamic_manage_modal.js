document.addEventListener('DOMContentLoaded', () => {
  let currentUserId = null;
  let currentUserName = null;
  let currentWeekStart = null;
  let currentIsTimeOff = 0;
  let currentTimeOffHours = 0;

  // Cache modals
  const manageAddModalEl = document.getElementById('manageEntryPromptModal');
  const manageAddModal = new bootstrap.Modal(manageAddModalEl);
  const entriesListContainer = document.getElementById('entriesListContainer');
  const addEntriesButton = document.getElementById('addEntriesButton2');

  // Add Entry Modal
  const addEntryModalEl = document.getElementById('addEntryModal');
  const addEntryModal = new bootstrap.Modal(addEntryModalEl);

  // Utility to format YYYY-MM-DD string to "Aug 11, 2025"
  function formatWeekStart(dateStr) {
    if (!dateStr) return '—';
    const [year, month, day] = dateStr.split('-').map(Number);
    return new Date(year, month - 1, day).toLocaleDateString('en-US', {
      month: 'short',
      day: 'numeric',
      year: 'numeric'
    });
  }

  // Function to open manage modal with options
  window.openManageEntryModal = function(options) {
    currentUserId = options.userId;
    currentUserName = options.userName || '';
    currentWeekStart = options.weekStart;
    currentIsTimeOff = options.is_timeoff || 0;
    currentTimeOffHours = options.timeOffHours || 0;

    const formattedWeekStart = formatWeekStart(currentWeekStart);

    document.getElementById('entryUserName').textContent = currentUserName || '—';
    document.getElementById('entryWeekStart').textContent = formattedWeekStart;

    entriesListContainer.innerHTML = '<p class="text-muted">Loading entries...</p>';

    fetch(`get_entries.php?user_id=${encodeURIComponent(currentUserId)}&week_start=${encodeURIComponent(currentWeekStart)}`)
      .then(res => {
        if (!res.ok) throw new Error('Network response was not OK');
        return res.json();
      })
      .then(entries => {
        // If only time-off, create a dummy entry for display
        if (currentIsTimeOff && (!entries || entries.length === 0)) {
          entries = [{
            entry_id: null,
            client_name: 'Time Off',
            assigned_hours: currentTimeOffHours,
            type: 'Time Off'
          }];
        }
        renderEntriesList(entries);
      })
      .catch(() => {
        entriesListContainer.innerHTML = '<p class="text-danger">Error loading entries.</p>';
      });

    manageAddModal.show();
  }

  // Add Entry button inside Manage modal
  addEntriesButton.addEventListener('click', () => {
    manageAddModal.hide();
    setTimeout(() => {
      openAddEntryModal(currentUserId, currentUserName, currentWeekStart);
    }, 250);
  });

  // Render entries list function
  function renderEntriesList(entriesForWeek) {
    entriesListContainer.innerHTML = '';

    if (!entriesForWeek || entriesForWeek.length === 0) {
      entriesListContainer.innerHTML = '<p class="text-muted">No entries for this week.</p>';
      return;
    }

    const timeOffEntries = [];
    const clientEntries = [];

    entriesForWeek.forEach(entry => {
      const isTimeOff = entry.client_name === 'Time Off' || entry.type === 'Time Off';
      if (isTimeOff) {
        entry.client_name = 'Time Off';
        timeOffEntries.push(entry);
      } else {
        clientEntries.push(entry);
      }
    });

    const sortedEntries = [...clientEntries, ...timeOffEntries];

    sortedEntries.forEach(entry => {
      const card = document.createElement('div');
      card.classList.add('card', 'mb-2', 'shadow-sm', 'px-3', 'py-3');
      card.style.cursor = 'pointer';

      const isTimeOff = entry.client_name === 'Time Off' || entry.type === 'Time Off';
      if (isTimeOff) card.classList.add('timeoff-card');

      card.addEventListener('click', () => {
        const entryType = isTimeOff ? 'Time Off' : 'Client Assignment';
        const formattedWeekStart = formatWeekStart(currentWeekStart);

        openEditModal(
          entry.entry_id,
          entry.assigned_hours,
          entry.client_name,
          currentUserName,
          formattedWeekStart,
          entryType,
          manageAddModalEl
        );
      });

      const cardBody = document.createElement('div');
      cardBody.classList.add('d-flex', 'align-items-center', 'justify-content-between');

      // LEFT column
      const leftDiv = document.createElement('div');
      leftDiv.style.flex = '1';
      let leftContent = `<div class="fw-semibold fs-6">${entry.client_name}</div>`;

      if (!isTimeOff) {
        const teammateMap = {};

        entriesForWeek
          .filter(e => e.client_name === entry.client_name)
          .forEach(e => {
            const name =
              e.user_name?.trim() ||
              ((e.first_name && e.last_name) ? `${e.first_name} ${e.last_name}` : 'Unknown');

            // Skip current user completely
            if (name === currentUserName) return;

            if (!teammateMap[name]) teammateMap[name] = 0;
            teammateMap[name] += Number(e.assigned_hours) || 0;
          });

        const teammates = Object.entries(teammateMap).map(
          ([name, hours]) => `${name} (${hours})`
        );

        // DEBUG: Log teammates for each client
        console.log(`Client: ${entry.client_name} | Teammates:`, teammates);

        leftContent += `<small class="text-muted">
                          <strong>Team member(s):</strong> 
                          ${teammates.length ? teammates.join(', ') : 'no other team members assigned'}
                        </small>`;
      }

      leftDiv.innerHTML = leftContent;

      // MIDDLE column
      const middleDiv = document.createElement('div');
      middleDiv.style.marginRight = '1rem';
      middleDiv.style.textAlign = 'right';
      middleDiv.innerHTML = `<div class="fw-semibold ${isTimeOff ? 'text-danger' : ''}">${entry.assigned_hours || 0} hrs</div>`;

      // RIGHT column
      const rightDiv = document.createElement('div');
      if (entry.entry_id) {
        const deleteLink = document.createElement('a');
        deleteLink.href = "#";
        deleteLink.title = "Delete Entry";
        deleteLink.className = "text-danger";
        deleteLink.style = "font-size: 1.25rem; cursor: pointer; text-decoration: none;";
        deleteLink.innerHTML = `<i class="bi bi-trash" style="font-size: 16px;"></i>`;
        deleteLink.onclick = (e) => {
          e.preventDefault();
          e.stopPropagation();
          deleteEntry(entry.entry_id);
        };
        rightDiv.appendChild(deleteLink);
      }

      cardBody.appendChild(leftDiv);
      cardBody.appendChild(middleDiv);
      cardBody.appendChild(rightDiv);
      card.appendChild(cardBody);
      entriesListContainer.appendChild(card);
    });
  }
});
