(function() {
    if (!IS_ADMIN) return;

    let activeClients = [];

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

    // Stop click propagation inside dropdown
    dropdown.addEventListener('click', e => e.stopPropagation());

    document.addEventListener('click', e => {
        if (!dropdown.contains(e.target) && !e.target.closest('.overlay-input')) {
            dropdown.style.display = 'none';
        }
    });

    function makeBadgeDraggable(badge) {
        if (!badge) return;
        badge.setAttribute('draggable', 'true');
        if (typeof handleDragStart === 'function') badge.addEventListener('dragstart', handleDragStart);
        if (typeof handleDragEnd === 'function') badge.addEventListener('dragend', handleDragEnd);
    }

    document.querySelectorAll('td.addable').forEach(td => {
        td.addEventListener('click', function(e) {
            if (e.target.tagName === 'INPUT') return;

            if (td.querySelector('.draggable-badge')) {
                showOverlay(td);
                return;
            }

            showInlineInputs(td);
        });
    });

    function showInlineInputs(td) {
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

        [clientInput, hoursInput].forEach(input => input.addEventListener('click', e => e.stopPropagation()));

        attachInputEvents(td, clientInput, hoursInput);
        setupAutocomplete(clientInput);
    }

    function showOverlay(td) {
        const overlay = document.createElement('div');
        overlay.className = 'overlay-input';
        overlay.style.position = 'absolute';
        overlay.style.top = td.getBoundingClientRect().top + window.scrollY + 'px';
        overlay.style.left = td.getBoundingClientRect().left + window.scrollX + 'px';
        overlay.style.width = td.offsetWidth + 'px';
        overlay.style.height = td.offsetHeight + 'px';
        overlay.style.background = 'rgba(255,255,255,0.95)';
        overlay.style.backdropFilter = 'blur(2px)';
        overlay.style.border = '1px solid #ccc';
        overlay.style.borderRadius = '4px';
        overlay.style.padding = '5px';
        overlay.style.zIndex = '10000';
        overlay.style.display = 'flex';
        overlay.style.flexDirection = 'column';
        overlay.style.justifyContent = 'center';

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

        overlay.appendChild(clientInput);
        overlay.appendChild(hoursInput);
        document.body.appendChild(overlay);
        clientInput.focus();

        [clientInput, hoursInput].forEach(input => input.addEventListener('click', e => e.stopPropagation()));
        overlay.addEventListener('click', e => e.stopPropagation());

        // Remove overlay when clicking outside
        const removeOverlay = e => {
            if (!overlay.contains(e.target)) {
                overlay.remove();
                document.removeEventListener('click', removeOverlay);
            }
        };
        setTimeout(() => document.addEventListener('click', removeOverlay), 0);

        attachInputEvents(td, clientInput, hoursInput, overlay);
        setupAutocomplete(clientInput, overlay);
    }

    function setupAutocomplete(clientInput, container = dropdown) {
        clientInput.addEventListener('input', () => {
            const val = clientInput.value.trim();
            if (val.length >= 3) {
                const matches = searchClients(val);
                container.innerHTML = '';
                matches.forEach(client => {
                    const div = document.createElement('div');
                    div.textContent = client.client_name;
                    div.style.padding = '5px 10px';
                    div.style.cursor = 'pointer';
                    div.addEventListener('click', e => {
                        e.stopPropagation();
                        clientInput.value = client.client_name;
                        container.style.display = 'none';
                    });
                    container.appendChild(div);
                });
                if (matches.length > 0) {
                    const rect = clientInput.getBoundingClientRect();
                    container.style.top = rect.bottom + window.scrollY + 'px';
                    container.style.left = rect.left + window.scrollX + 'px';
                    container.style.width = rect.width + 'px';
                    container.style.display = 'block';
                } else {
                    container.style.display = 'none';
                }
            } else {
                container.style.display = 'none';
            }
        });
    }

    function attachInputEvents(td, clientInput, hoursInput, overlay = null) {
        [clientInput, hoursInput].forEach(input => {
            input.addEventListener('keydown', async e => {
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

                            td.appendChild(span);
                            makeBadgeDraggable(span);

                            if (overlay) overlay.remove();
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
    }

})();
