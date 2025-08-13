function openEditModal(event) {
    const buttonElement = event.currentTarget; // safer than event.target in case of icon click
    const assignmentId = buttonElement.getAttribute('data-assignment-id');
    const assignedHours = buttonElement.getAttribute('data-assigned-hours');

    document.getElementById('editAssignmentId').value = assignmentId;
    document.getElementById('editAssignedHours').value = assignedHours;

    const editModal = new bootstrap.Modal(document.getElementById('editAssignmentModal'));
    editModal.show();
  }