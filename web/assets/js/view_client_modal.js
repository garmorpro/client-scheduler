document.addEventListener('DOMContentLoaded', () => {
    console.log('Client modal script loaded');

    const viewButtons = document.querySelectorAll('.view-btn');
    const modalEl = document.getElementById('viewClientModal');
    const modalBody = document.getElementById('viewClientModalBody');

    if (!modalEl || !modalBody) {
        console.error('Modal elements not found:', { modalEl, modalBody });
        return;
    }

    if (typeof bootstrap === 'undefined') {
        console.error('Bootstrap JS is not loaded!');
        return;
    }

    const modal = new bootstrap.Modal(modalEl, { keyboard: true });
    console.log('Bootstrap modal initialized:', modal);

    viewButtons.forEach(button => {
        button.addEventListener('click', async () => {
            const clientId = button.dataset.clientId;
            console.log('View button clicked for client ID:', clientId);

            if (!clientId) {
                console.warn('No client ID found on button');
                return;
            }

            try {
                console.log('Fetching client data...');
                const res = await fetch(`view_client.php?client_id=${clientId}`);
                console.log('Fetch response status:', res.status);

                if (!res.ok) throw new Error('Network response was not OK');

                const data = await res.json();
                console.log('Fetched data:', data);

                if (data.error) {
                    alert(data.error);
                    return;
                }

                const client = data.client;
                const history = data.history || [];

                console.log('Client info:', client);
                console.log('Engagement history:', history);

                const ucfirst = str => str ? str.charAt(0).toUpperCase() + str.slice(1) : '';
                const statusClass = client.status?.toLowerCase() === 'active' ? 'text-success'
                                  : client.status?.toLowerCase() === 'inactive' ? 'text-warning'
                                  : 'text-muted';

                modalBody.innerHTML = `
<div style="background-color: rgb(245,245,247); border-radius: 15px; padding: 10px;">
    <div class="d-flex justify-content-between">
        <div><span class="fw-semibold">${client.client_name}</span><br><span class="${statusClass}">${ucfirst(client.status)}</span></div>
        <small class="text-end">Onboarded<br><span class="text-muted">${client.onboarded_date ? new Date(client.onboarded_date).toLocaleDateString() : 'N/A'}</span></small>
    </div>
</div>
<div class="d-flex gap-2 mt-2">
    <div class="flex-fill bg-white rounded p-2 text-center shadow-sm">
        <div class="fw-semibold">${client.total_engagements ?? 0}</div>
        <div class="text-muted" style="font-size: 12px;">Total Engagements</div>
    </div>
    <div class="flex-fill bg-white rounded p-2 text-center shadow-sm">
        <div class="fw-semibold">${client.confirmed_engagements ?? 0}</div>
        <div class="text-muted" style="font-size: 12px;">Confirmed Engagements</div>
    </div>
</div>
<hr>
<div id="engagementHistoryContainer"></div>`;

                const historyContainer = document.getElementById('engagementHistoryContainer');
                if (!history || history.length === 0) {
                    console.log('No engagement history for this client.');
                    historyContainer.innerHTML = `<p class="text-muted">No records available.</p>`;
                } else {
                    const formatListItems = val => val ? val.split(',').map(i => `<div>${i.trim()}</div>`).join('') : '';
                    const formatDate = d => d ? new Date(d).toLocaleDateString('en-US', { year:'numeric', month:'short', day:'numeric' }) : 'N/A';

                    historyContainer.innerHTML = history.map(h => {
                        const managerHtml = formatListItems(h.manager);
                        const seniorHtml = formatListItems(h.senior);
                        const staffHtml = formatListItems(h.staff);
                        const archiveDate = formatDate(h.archive_date);

                        return `
<div class="card p-2 mb-2">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div><span class="me-2">${h.engagement_year}</span><span class="badge" style="font-size:10px;background-color:black !important;">${h.status || 'Archived'}</span></div>
        <div style="font-size:10px;"><span class="me-2"><span class="text-muted">Budgeted:</span> <span class="fw-bold text-black">${h.budgeted_hours}h</span></span> <span class="text-muted">Allocated:</span> <span class="fw-bold text-black">${h.allocated_hours}h</span></div>
    </div>
    <div class="d-flex fw-semibold border-bottom pb-1 mb-1" style="font-size:10px;"><div style="flex:1;">Manager</div><div style="flex:1;">Senior</div><div style="flex:1;">Staff</div></div>
    <div class="d-flex text-muted" style="font-size:10px;">
        <div style="flex:1;" class="d-flex flex-column">${managerHtml}</div>
        <div style="flex:1;" class="d-flex flex-column">${seniorHtml}</div>
        <div style="flex:1;" class="d-flex flex-column">${staffHtml}</div>
    </div>
    <hr>
    <div style="font-size:10px;">Archived: ${archiveDate} by ${h.archived_by}</div>
</div>`;
                    }).join('');
                }

                console.log('Showing modal...');
                modal.show();
            } catch (err) {
                console.error('Error fetching or rendering client data:', err);
                alert('Error fetching client details. Check console.');
            }
        });
    });
});