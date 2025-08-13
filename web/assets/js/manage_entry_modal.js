function openManageEntryModal(user_id, employeeName, weekStart) {
    const formattedDate = new Date(weekStart).toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
    document.getElementById('assignmentsModalTitle').innerText = `Manage Assignments for Week of ${formattedDate}`;
    document.getElementById('assignmentsModalSubheading').innerText = `Consultant: ${employeeName}`;

    // Fetch assignments for the user and week
    const assignmentsForWeek = assignments[user_id] && assignments[user_id][weekStart] ? assignments[user_id][weekStart] : [];
    showAssignments(assignmentsForWeek);

    const assignmentsModal = new bootstrap.Modal(document.getElementById('assignmentsModal'));
    assignmentsModal.show();
  }