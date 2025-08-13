function openEditModal(event) {
    const buttonElement = event.currentTarget; // safer than event.target in case of icon click
    const entryId = buttonElement.getAttribute('data-entry-id');
    const assignedHours = buttonElement.getAttribute('data-assigned-hours');

    document.getElementById('editEntryId').value = entryId;
    document.getElementById('editAssignedHours').value = assignedHours;

    const editModal = new bootstrap.Modal(document.getElementById('editEntryModal'));
    editModal.show();
  }