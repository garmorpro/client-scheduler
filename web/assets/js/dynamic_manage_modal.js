document.addEventListener('DOMContentLoaded', () => {
  let currentUserId = null;
  let currentUserName = null;
  let currentWeekStart = null;
  let currentIsTimeOff = 0;
  let currentTimeOffHours = 0;

  const manageAddModalEl = document.getElementById('manageEntryPromptModal');
  const manageAddModal = new bootstrap.Modal(manageAddModalEl);
  const entriesListContainer = document.getElementById('entriesListContainer');
  const addEntriesButton = document.getElementById('addEntriesButton2');

  const addEntryModalEl = document.getElementById('addEntryModal');
  const addEntryModal = new bootstrap.Modal(addEntryModalEl);

  function formatWeekStart(dateStr) {
    if (!dateStr) return '—';
    const [year, month, day] = dateStr.split('-').map(Number);
    return new Date(year, month - 1, day).toLocaleDateString('en-US', {
      month: 'short',
      day: 'numeric',
      year: 'numeric'
    });
  }

  // -----------------------------
  window.openManageEntryModal = async function(options) {
    currentUserId = options.userId;
    currentUserName = options.userName || '';
    currentWeekStart = options.weekStart;
    currentIsTimeOff = options.is_timeoff || 0;
    currentTimeOffHours = options.timeOffHours || 0;

    document.getElementById('entryUserName').textContent = currentUserName || '—';
    document.getElementById('entryWeekStart').textContent = formatWeekStart(currentWeekStart);

    entriesListContainer.innerHTML = '<p class="text-muted">Loading entries...</p>';

    try {
      const res = await fetch(`get_entries.php?user_id=${encodeURIComponent(currentUserId)}&week_start=${encodeURIComponent(currentWeekStart)}`);
      if (!res.ok) throw new Error('Network response was not OK');
      let entries = await res.json();

      if (currentIsTimeOff && (!entries || entries.length === 0)) {
        entries = [{
          entry_id: null,
          client_name: 'Time Off',
          assigned_hours: currentTimeOffHours,
          type: 'Time Off'
        }];
      }

      await renderEntriesList(entries);

    } catch (err) {
      console.error('Error loading entries:', err);
      entriesListContainer.innerHTML = '<p class="text-danger">Error loading entries.</p>';
    }

    manageAddModal.show();
  }

  // -----------------------------
  async function fetchTeammates(clientName) {
    try {
      const url = `get_teammates.php?current_user_id=${encodeURIComponent(currentUserId)}&week_start=${encodeURIComponent(currentWeekStart)}&client_name=${encodeURIComponent(clientName)}`;
      console.log('Fetching teammates URL:', url);

      const res = await fetch(url);

      // If not OK, try to read text first to see the error
      if (!res.ok) {
        const errorText = await res.text();
        console.error('Server returned an error response:', errorText);
        throw new Error(`Network error: ${res.status}`);
      }

      const data = await res.json();
      console.log('Teammates raw data:', data);

      return data
        .map(e => e.first_name && e.last_name ? `${e.first_name} ${e.last_name}` : 'Unknown')
        .filter(name => name !== currentUserName)
        .map(name => ({ name, hours: 0 }));

    } catch (err) {
      console.error('Error fetching teammates:', err);
      return [];
    }
  }

  // -----------------------------
  addEntriesButton.addEventListener('click', () => {
    manageAddModal.hide();
    setTimeout(() => {
      openAddEntryModal(currentUserId, currentUserName, currentWeekStart);
    }, 250);
  });

  // -----------------------------
  async function renderEntriesList(entriesForWeek) {
    entriesListContainer.innerHTML = '';

    if (!entriesForWeek || entriesForWeek.length === 0) {
      entriesListContainer.innerHTML = '<p class="text-muted">No entries for this week.</p>';
      return;
    }

    const timeOffEntries = [];
    const clientEntries = [];

    entriesForWeek.forEach(entry => {
      const isTimeOff = entry.client_name === 'Time Off' || entry.type === 'Time Off';
      if (isTimeOff) timeOffEntries.push(entry);
      else clientEntries.push(entry);
    });

    const sortedEntries = [...clientEntries, ...timeOffEntries];

    for (const entry of sortedEntries) {
      const card = document.createElement('div');
      card.classList.add('card', 'mb-2', 'shadow-sm', 'px-3', 'py-3');
      card.style.cursor = 'pointer';

      const isTimeOff = entry.client_name === 'Time Off' || entry.type === 'Time Off';
      if (isTimeOff) card.classList.add('timeoff-card');

      card.addEventListener('click', () => {
        const entryType = isTimeOff ? 'Time Off' : 'Client Assignment';
        openEditModal(
          entry.entry_id,
          entry.assigned_hours,
          entry.client_name,
          currentUserName,
          formatWeekStart(currentWeekStart),
          entryType,
          manageAddModalEl
        );
      });

      const cardBody = document.createElement('div');
      cardBody.classList.add('d-flex', 'align-items-center', 'justify-content-between');

      // LEFT
      const leftDiv = document.createElement('div');
      leftDiv.style.flex = '1';
      let leftContent = `<div class="fw-semibold fs-6">${entry.client_name}</div>`;

      if (!isTimeOff) {
        const teammatesData = await fetchTeammates(entry.client_name);
        const teammates = teammatesData.map(t => `${t.name} (${entry.assigned_hours || 0})`);
        console.log(`👥 Client "${entry.client_name}" teammates:`, teammates);

        leftContent += `<small class="text-muted">
                          <strong>Team member(s):</strong> 
                          ${teammates.length ? teammates.join(', ') : 'no other team members assigned'}
                        </small>`;
      }

      leftDiv.innerHTML = leftContent;

      // MIDDLE
      const middleDiv = document.createElement('div');
      middleDiv.style.marginRight = '1rem';
      middleDiv.style.textAlign = 'right';
      middleDiv.innerHTML = `<div class="fw-semibold ${isTimeOff ? 'text-danger' : ''}">${entry.assigned_hours || 0} hrs</div>`;

      // RIGHT
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
    }
  }
});
