function showEntries(entriesForWeek) {
  const container = document.getElementById('entriesListContainer');
  container.innerHTML = ''; // clear existing content

  if (!entriesForWeek || entriesForWeek.length === 0) {
    container.innerHTML = '<p>No entries for this week.</p>';
    return;
  }

  entriesForWeek.forEach(entry => {
    const card = document.createElement('div');
    card.classList.add('card', 'mb-2');
    card.innerHTML = `
      <div class="card-body">
        <h5 class="card-title">${entry.client_name}</h5>
        <p class="card-text">Hours: ${entry.assigned_hours}</p>
        <p class="card-text"><small>Status: ${entry.engagement_status || 'Confirmed'}</small></p>
      </div>
    `;
    container.appendChild(card);
  });
}
