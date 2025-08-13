document.addEventListener('DOMContentLoaded', function () {
  const manageAddButtons = document.getElementById('manageAddButtons');
  const assignmentsListing = document.getElementById('assignmentsListing');
  const assignmentsListContainer = document.getElementById('assignmentsListContainer');
  const manageAssignmentsButton = document.getElementById('manageAssignmentsButton');
  const backToButtons = document.getElementById('backToButtons');

  // Context variables (to be set before showing modal)
  let currentUserId = null;
  let currentWeekStart = null;

  // Expose a global setter to initialize context externally
  window.setManageEntryContext = function (userId, weekStart) {
    currentUserId = userId;
    currentWeekStart = weekStart;
  };

  manageAssignmentsButton.addEventListener('click', function () {
    if (!currentUserId || !currentWeekStart) {
      assignmentsListContainer.innerHTML = '<p class="text-danger">Missing user or week info.</p>';
      return;
    }

    manageAddButtons.classList.add('d-none');
    assignmentsListing.classList.remove('d-none');
    assignmentsListContainer.innerHTML = '<p>Loading assignments...</p>';

    fetch(`get_assignments.php?user_id=${encodeURIComponent(currentUserId)}&week_start=${encodeURIComponent(currentWeekStart)}`)
      .then(response => {
        if (!response.ok) throw new Error('Network response was not OK');
        return response.json();
      })
      .then(assignments => {
        renderAssignmentsList(assignments);
      })
      .catch(error => {
        console.error('Error fetching assignments:', error);
        assignmentsListContainer.innerHTML = `<p class="text-danger">Error loading assignments.</p>`;
      });
  });

  backToButtons.addEventListener('click', function () {
    manageAddButtons.classList.remove('d-none');
    assignmentsListing.classList.add('d-none');
  });

  function renderAssignmentsList(assignmentsForWeek) {
    assignmentsListContainer.innerHTML = '';

    if (!assignmentsForWeek || assignmentsForWeek.length === 0) {
      assignmentsListContainer.innerHTML = '<p class="text-muted">No assignments for this week.</p>';
      return;
    }

    assignmentsForWeek.forEach(assignment => {
      const card = document.createElement('div');
      card.classList.add('card', 'mb-3', 'shadow-sm');

      const cardBody = document.createElement('div');
      cardBody.classList.add('card-body', 'd-flex', 'justify-content-between', 'align-items-center');

      const leftDiv = document.createElement('div');
      leftDiv.innerHTML = `
        <div class="fw-semibold fs-6">${assignment.client_name || (assignment.type === 'Time Off' ? 'Time Off' : 'Unnamed Client')}</div>
        <small class="text-muted">Assigned Hours: ${assignment.assigned_hours || 0}</small>
      `;

      const rightDiv = document.createElement('div');

      const editLink = document.createElement('a');
      editLink.href = "#";
      editLink.title = "Edit Assignment";
      editLink.className = "text-primary me-3";
      editLink.style = "font-size: 1.25rem; cursor: pointer; text-decoration: none;";
      editLink.innerHTML = `<i class="bi bi-pencil-square" style="font-size: 16px;"></i>`;
      editLink.setAttribute('data-assignment-id', assignment.assignment_id);
      editLink.setAttribute('data-assigned-hours', assignment.assigned_hours || 0);
      editLink.onclick = function (e) {
        e.preventDefault();
        openEditModal(e); // make sure you have this function implemented
      };

      const deleteLink = document.createElement('a');
      deleteLink.href = "#";
      deleteLink.title = "Delete Assignment";
      deleteLink.className = "text-danger";
      deleteLink.style = "font-size: 1.25rem; cursor: pointer; text-decoration: none;";
      deleteLink.innerHTML = `<i class="bi bi-trash" style="font-size: 16px;"></i>`;
      deleteLink.onclick = function (e) {
        e.preventDefault();
        alert(`Delete assignment ${assignment.assignment_id}`);
      };

      rightDiv.appendChild(editLink);
      rightDiv.appendChild(deleteLink);

      cardBody.appendChild(leftDiv);
      cardBody.appendChild(rightDiv);
      card.appendChild(cardBody);
      assignmentsListContainer.appendChild(card);
    });
  }
});
