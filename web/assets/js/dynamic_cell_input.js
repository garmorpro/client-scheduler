(function() {
    if (!IS_ADMIN) return;

    let activeClients = [];

    // Fetch clients via AJAX
    fetch('get_clients.php')
        .then(res => res.json())
        .then(data => { activeClients = data; })
        .catch(err => console.error('Failed to fetch clients:', err));

    function searchClients(query) {
        query = query.toLowerCase();
        return activeClients.filter(c => c.client_name.toLowerCase().includes(query));
    }

    function getClientStatus(clientName) {
        const name = clientName.trim().toLowerCase();
        const client = activeClients.find(c => c.client_name.trim().toLowerCase() === name);
        return client && client.status ? client.status : 'confirmed';
    }

    const dropdown = document.createElement('div');
    dropdown.style.position = 'absolute';
    dropdown.style.zIndex = '9999';
    dropdown.style.background = '#fff';
    dropdown.style.border = '1px solid #ccc';
    dropdown.style.borderRadius = '4px';
    dropdown.style.display = 'none';
    dropdown.style.maxHeight = '150px';
    dropdown.style.overflowY = 'auto';
    document.body.appendChild(dropdown);

    // Stop click propagation inside dropdown so it doesn't close the cell
    dropdown.addEventListener('click', e => e.stopPropagation());

    document.addEventListener('click', e => {
        if (!dropdown.contains(e.target)) dropdown.style.display = 'none';
    });

    function makeBadgeDraggable(badge) {
        if (!badge) return;
        badge.setAttribute('draggable', 'true');
        if (typeof handleDragStart === 'function') badge.addEventListener('dragstart', handleDragStart);
        if (typeof handleDragEnd === 'function') badge.addEventListener('dragend', handleDragEnd);
    }

    document.querySelectorAll('td.addable').forEach(td => {
        td.addEventListener('click', function(e) {
            if (td.querySelector('.draggable-badge')) return;

            td.innerHTML = '';

            const clientInput = document.createElement('input');
            clientInput.type = 'text';
            clientInput.placeholder = 'Client Name';
            clientInput.className = 'form-control form-control-sm mb-1';
            clientInput.style.width = '100%';

            const hoursInput = document.createElement('input');
            hoursInput.type = 'number';
            hoursInput.min = '0';
            hoursInput.placeholder = 'Hours';
            hoursInput.className = 'form-control form-control-sm';
            hoursInput.style.width = '100%';

            td.appendChild(clientInput);
            td.appendChild(hoursInput);
            clientInput.focus();

            function updateDropdownPosition() {
                const rect = clientInput.getBoundingClientRect();
                dropdown.style.top = rect.bottom + window.scrollY + 'px';
                dropdown.style.left = rect.left + window.scrollX + 'px';
                dropdown.style.width = rect.width + 'px';
            }

            clientInput.addEventListener('input', () => {
                const val = clientInput.value.trim();
                if (val.length >= 3) {
                    const matches = searchClients(val);
                    dropdown.innerHTML = '';
                    matches.forEach(client => {
                        const div = document.createElement('div');
                        div.textContent = client.client_name;
                        div.style.padding = '5px 10px';
                        div.style.cursor = 'pointer';
                        div.addEventListener('click', (e) => {
                            e.stopPropagation(); // <-- Prevent td click from firing
                            clientInput.value = client.client_name;
                            dropdown.style.display = 'none';
                        });
                        dropdown.appendChild(div);
                    });
                    if (matches.length > 0) {
                        updateDropdownPosition();
                        dropdown.style.display = 'block';
                    } else {
                        dropdown.style.display = 'none';
                    }
                } else {
                    dropdown.style.display = 'none';
                }
            });

            [clientInput, hoursInput].forEach(input => {
                input.addEventListener('keydown', async (e) => {
                    if (e.key === 'Enter') {
                        const clientName = clientInput.value.trim();
                        const hours = parseFloat(hoursInput.value);
                        if (!clientName || !hours || hours <= 0) {
                            alert('Please enter valid client and hours.');
                            return;
                        }

                        try {
                            const resp = await fetch('add_entry_new.php', {
                                method: 'POST',
                                credentials: 'same-origin',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'Accept': 'application/json'
                                },
                                body: JSON.stringify({
                                    user_id: td.dataset.userId,
                                    week_start: td.dataset.weekStart,
                                    client_name: clientName,
                                    assigned_hours: hours
                                })
                            });

                            const data = await resp.json();
                            if (resp.ok && data.success) {
                                const span = document.createElement('span');

                                const statusClass = getClientStatus(clientName);

                                span.className = `badge badge-status badge-${statusClass} mt-1 draggable-badge`;
                                span.dataset.entryId = data.entry_id;
                                span.dataset.userId = td.dataset.userId;
                                span.dataset.weekStart = td.dataset.weekStart;
                                span.textContent = `${clientName} (${hours})`;
                                span.setAttribute('draggable', 'true');

                                td.innerHTML = '';
                                td.appendChild(span);

                                makeBadgeDraggable(span);
                            } else {
                                alert('Failed to add entry: ' + (data.error || 'Server error'));
                            }
                        } catch (err) {
                            console.error(err);
                            alert('Network error while adding entry.');
                        }
                    }
                });
            });
        });
    });
})();
