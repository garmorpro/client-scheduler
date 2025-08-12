<?php


?>


<!-- Script: Render Assignments Listing -->
  <script>
    function renderAssignmentsList(user_id, weekStart) {
  const assignments = <?php echo json_encode($assignments); ?>;
  const assignmentsForWeek = assignments[user_id] && assignments[user_id][weekStart] ? assignments[user_id][weekStart] : [];

  console.log('Assignments for user', user_id, 'week', weekStart, assignmentsForWeek);

  const container = document.getElementById('assignmentsListContainer');
  container.innerHTML = '';

  if (assignmentsForWeek.length === 0) {
    container.innerHTML = '<p class="text-muted">No assignments for this week.</p>';
    return;
  }

  assignmentsForWeek.forEach(assignment => {
    const card = document.createElement('div');
    card.classList.add('card', 'mb-3', 'shadow-sm');

    const cardBody = document.createElement('div');
    cardBody.classList.add('card-body', 'd-flex', 'justify-content-between', 'align-items-center');

    const leftDiv = document.createElement('div');
    leftDiv.innerHTML = `
      <div class="fw-semibold fs-6">${assignment.client_name || 'Unnamed Client'}</div>
      <small class="text-muted">Assigned Hours: ${assignment.assigned_hours || 0}</small>
    `;

    const rightDiv = document.createElement('div');

    // Edit link
    const editLink = document.createElement('a');
    editLink.href = "#";
    editLink.title = "Edit Assignment";
    editLink.className = "text-primary me-3";
    editLink.style = "font-size: 1.25rem; cursor: pointer; text-decoration: none;";
    editLink.innerHTML = `<i class="bi bi-pencil-square" style="font-size: 16px;"></i>`;
    editLink.onclick = (e) => {
      e.preventDefault();
      const parentModalEl = document.getElementById('manageEntryModal');
      const editModalEl = document.getElementById('editAssignmentModal');

      // Hide parent modal
      const parentModal = bootstrap.Modal.getInstance(parentModalEl) || new bootstrap.Modal(parentModalEl);
      parentModal.hide();

      // Show edit modal
      const editModal = new bootstrap.Modal(editModalEl);
      document.getElementById('editAssignmentId').value = assignment.assignment_id || assignment.id || '';
      document.getElementById('editAssignedHours').value = assignment.assigned_hours || 0;
      editModal.show();

      // When edit modal closes, re-show parent modal
      editModalEl.addEventListener('hidden.bs.modal', () => {
        parentModal.show();
      }, { once: true });
    };

    // Delete link
    const deleteLink = document.createElement('a');
    deleteLink.href = "#";
    deleteLink.title = "Delete Assignment";
    deleteLink.className = "text-danger";
    deleteLink.style = "font-size: 1.25rem; cursor: pointer; text-decoration: none;";
    deleteLink.innerHTML = `<i class="bi bi-trash" style="font-size: 16px;"></i>`;
    deleteLink.onclick = (e) => {
      e.preventDefault();
      console.log('Delete clicked for assignment:', assignment);
      // Your delete logic here (confirm and delete)
    };

    rightDiv.appendChild(editLink);
    rightDiv.appendChild(deleteLink);

    cardBody.appendChild(leftDiv);
    cardBody.appendChild(rightDiv);
    card.appendChild(cardBody);
    container.appendChild(card);
  });
  }

  </script>
<!-- end Script: Render Assignments Listing -->



<!-- dropdown menu -->
    <script>
      const dropdownBtn = document.getElementById('dropdownBtn');
    const dropdownList = document.getElementById('dropdownList');
    const selectedClient = document.getElementById('selectedClient');
    const engagementInput = document.getElementById('engagementInput');

    dropdownBtn.addEventListener('click', () => {
      const isOpen = dropdownList.style.display === 'block';
      dropdownList.style.display = isOpen ? 'none' : 'block';
      dropdownBtn.setAttribute('aria-expanded', !isOpen);
    });

    dropdownBtn.addEventListener('keydown', (e) => {
      if (e.key === 'ArrowDown' || e.key === 'Enter' || e.key === ' ') {
        e.preventDefault();
        dropdownList.style.display = 'block';
        dropdownBtn.setAttribute('aria-expanded', 'true');
        dropdownList.querySelector('.dropdown-item').focus();
      }
    });

    dropdownList.querySelectorAll('.dropdown-item').forEach(item => {
      item.addEventListener('click', () => {
        selectClient(item);
      });
      item.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' || e.key === ' ') {
          e.preventDefault();
          selectClient(item);
        }
        else if (e.key === 'ArrowDown') {
          e.preventDefault();
          const next = item.nextElementSibling || dropdownList.querySelector('.dropdown-item');
          next.focus();
        }
        else if (e.key === 'ArrowUp') {
          e.preventDefault();
          const prev = item.previousElementSibling || dropdownList.querySelector('.dropdown-item:last-child');
          prev.focus();
        }
        else if (e.key === 'Escape') {
          closeDropdown();
          dropdownBtn.focus();
        }
      });
    });

    document.addEventListener('click', (e) => {
      if (!dropdownBtn.contains(e.target) && !dropdownList.contains(e.target)) {
        closeDropdown();
      }
    });

    function selectClient(item) {
      const clientName = item.getAttribute('data-client-name');
      const engagementId = item.getAttribute('data-engagement-id');
      selectedClient.textContent = clientName;
      engagementInput.value = engagementId;
      closeDropdown();
    }

    function closeDropdown() {
      dropdownList.style.display = 'none';
      dropdownBtn.setAttribute('aria-expanded', 'false');
    }

    </script>
<!-- end dropdown menu -->