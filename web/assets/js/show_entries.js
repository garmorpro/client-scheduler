function showEntries(assignmentsForWeek) {
  const container = document.getElementById('assignmentsListContainer');
  container.innerHTML = ''; // clear existing content

  if (!assignmentsForWeek || assignmentsForWeek.length === 0) {
    container.innerHTML = '<p>No assignments for this week.</p>';
    return;
  }

  assignmentsForWeek.forEach(assignment => {
    const card = document.createElement('div');
    card.classList.add('card', 'mb-2');
    card.innerHTML = `
      <div class="card-body">
        <h5 class="card-title">${assignment.client_name}</h5>
        <p class="card-text">Hours: ${assignment.assigned_hours}</p>
        <p class="card-text"><small>Status: ${assignment.engagement_status || 'Confirmed'}</small></p>
      </div>
    `;
    container.appendChild(card);
  });
}
