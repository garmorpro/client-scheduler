(function() {
    if (!IS_ADMIN) return;

    let activeClients = [];
    let activeTd = null;
    let activeOverlay = null;

    // Fetch clients
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

    function getBadgeClass(status) {
        switch ((status || '').toLowerCase()) {
            case 'confirmed': return 'confirmed';
            case 'pending': return 'pending';
            case 'not_confirmed': return 'not-confirmed';
            default: return 'confirmed';
        }
    }

    // Global dropdown for inline cells
    const globalDropdown = document.createElement('div');
    Object.assign(globalDropdown.style, {
        position: 'absolute',
        zIndex: '9999',
        background: '#fff',
        border: '1px solid #ccc',
        borderRadius: '4px',
        display: 'none',
        maxHeight: '150px',
        overflowY: 'auto'
    });
    document.body.appendChild(globalDropdown);
    globalDropdown.addEventListener('click', e => e.stopPropagation());

    document.addEventListener('click', e => {
        if (activeTd) {
            const clickInsideTd = activeTd.contains(e.target);
            const clickInsideOverlay = activeOverlay && activeOverlay.contains(e.target);
            if (!clickInsideTd && !clickInsideOverlay) closeActiveInputs();
        }
    });

    function restoreBiPlus(td) {
        const plus = document.createElement('i');
        plus.className = 'bi bi-plus';
        plus.style.cursor = 'pointer';
        td.appendChild(plus);
    }

    function closeActiveInputs() {
        if (activeOverlay) {
            activeOverlay.remove();
            activeOverlay = null;
        }
        if (activeTd) {
            if (!activeTd.querySelector('.draggable-badge')) {
                const timeOff = activeTd.querySelector('.timeoff-corner');
                activeTd.innerHTML = '';
                if (timeOff) activeTd.appendChild(timeOff);

                const hasOtherBadges = activeTd.querySelectorAll('.draggable-badge').length === 0;
                if (hasOtherBadges) restoreBiPlus(activeTd);
            }
            activeTd = null;
        }
        globalDropdown.style.display = 'none';
    }

    function makeBadgeDraggable(badge) {
        badge.setAttribute('draggable', 'true');
        if (typeof handleDragStart === 'function') badge.addEventListener('dragstart', handleDragStart);
        if (typeof handleDragEnd === 'function') badge.addEventListener('dragend', handleDragEnd);
    }

    document.querySelectorAll('td.addable').forEach(td => {
        td.addEventListener('click', e => {
            const target = e.target;
            if (target.classList.contains('draggable-badge') || target.tagName === 'INPUT' || target.classList.contains('timeoff-corner')) return;
            if (activeTd === td) return;

            closeActiveInputs();
            activeTd = td;

            const hasBadges = td.querySelector('.draggable-badge') !== null;
            const timeOff = td.querySelector('.timeoff-corner');

            if (hasBadges) {
                showOverlay(td);
                return;
            }

            showInlineInputs(td, timeOff);
        });
    });

    function showInlineInputs(td, timeOff = null) {
        td.innerHTML = '';
        if (timeOff) td.appendChild(timeOff);

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

        [clientInput, hoursInput].forEach(input => input.addEventListener('click', e => e.stopPropagation()));

        attachInputEvents(td, clientInput, hoursInput, true);
        setupAutocomplete(clientInput, globalDropdown);
        clientInput.focus();
    }

    function showOverlay(td) {
        const rect = td.getBoundingClientRect();
        const overlay = document.createElement('div');
        Object.assign(overlay.style, {
            position: 'absolute',
            top: rect.top + window.scrollY + 'px',
            left: rect.left + window.scrollX + 'px',
            width: rect.width + 'px',
            minHeight: '50px',
            background: 'rgba(255,255,255,0.95)',
            border: '1px solid #ccc',
            borderRadius: '4px',
            padding: '5px',
            zIndex: '10000',
            display: 'flex',
            flexDirection: 'column'
        });

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
        activeOverlay = overlay;

        [clientInput, hoursInput].forEach(input => input.addEventListener('click', e => e.stopPropagation()));
        overlay.addEventListener('click', e => e.stopPropagation());

        const overlayDropdown = document.createElement('div');
        Object.assign(overlayDropdown.style, {
            position: 'absolute',
            zIndex: '10001',
            background: '#fff',
            border: '1px solid #ccc',
            borderRadius: '4px',
            display: 'none',
            maxHeight: '150px',
            overflowY: 'auto'
        });
        document.body.appendChild(overlayDropdown);

        setupAutocomplete(clientInput, overlayDropdown);
        attachInputEvents(td, clientInput, hoursInput, false, overlay);
        clientInput.focus();
    }

    function setupAutocomplete(clientInput, container) {
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
                } else container.style.display = 'none';
            } else container.style.display = 'none';
        });
    }

    function attachInputEvents(td, clientInput, hoursInput, inline = true, overlay = null) {
        [clientInput, hoursInput].forEach(input => {
            input.addEventListener('keydown', async e => {
                if (e.key === 'Enter') {
                    const clientName = clientInput.value.trim();
                    const hours = parseFloat(hoursInput.value);

                    if (!clientName || !hours || hours <= 0) {
                        alert('Please enter valid client and hours.');
                        return;
                    }

                    const selectedClient = activeClients.find(c => c.client_name.trim().toLowerCase() === clientName.toLowerCase());
                    if (!selectedClient) {
                        alert('Selected client not found.');
                        return;
                    }

                    closeActiveInputs();
                    if (inline) globalDropdown.style.display = 'none';
                    else if (overlay && overlay.nextSibling) overlay.nextSibling.style.display = 'none';

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
                                client_id: selectedClient.client_id, // updated here
                                assigned_hours: hours
                            })
                        });

                        const data = await resp.json();
                        if (resp.ok && data.success) {
                            const span = document.createElement('span');
                            const statusClass = getBadgeClass(selectedClient.status);
                            span.className = `badge badge-status badge-${statusClass} mt-1 draggable-badge`;
                            span.dataset.entryId = data.entry_id;
                            span.dataset.userId = td.dataset.userId;
                            span.dataset.weekStart = td.dataset.weekStart;
                            span.textContent = `${clientName} (${hours})`;
                            makeBadgeDraggable(span);

                            const plusIcon = td.querySelector('.bi-plus');
                            if (plusIcon) plusIcon.remove();
                            td.appendChild(span);

                            const timeOff = td.querySelector('.timeoff-corner');
                            if (timeOff) td.appendChild(timeOff);
                        } else {
                            alert('Failed to add entry: ' + (data.error || 'Server error'));
                        }
                    } catch (err) {
                        console.error(err);
                        alert('Network error while adding entry.');
                    }
                } else if (e.key === 'Escape') {
                    closeActiveInputs();
                }
            });
        });
    }

    // Double-click to edit existing badge
    document.addEventListener('dblclick', e => {
        if (!e.target.classList.contains('draggable-badge')) return;
        e.stopPropagation();

        const badge = e.target;
        const td = badge.closest('td');
        activeTd = td;

        const match = badge.textContent.match(/^(.*)\s+\(([\d.]+)\)$/);
        const currentName = match ? match[1] : '';
        const currentHours = match ? match[2] : '';

        const selectedClient = activeClients.find(c => c.client_name.trim().toLowerCase() === currentName.toLowerCase());

        const rect = td.getBoundingClientRect();
        const overlay = document.createElement('div');
        Object.assign(overlay.style, {
            position: 'absolute',
            top: rect.top + window.scrollY + 'px',
            left: rect.left + window.scrollX + 'px',
            width: rect.width + 'px',
            minHeight: '50px',
            background: 'rgba(255,255,255,0.95)',
            border: '1px solid #ccc',
            borderRadius: '4px',
            padding: '5px',
            zIndex: '10000',
            display: 'flex',
            flexDirection: 'column'
        });

        const clientInput = document.createElement('input');
        clientInput.type = 'text';
        clientInput.value = currentName;
        clientInput.className = 'form-control form-control-sm mb-1';

        const hoursInput = document.createElement('input');
        hoursInput.type = 'number';
        hoursInput.min = '0';
        hoursInput.value = currentHours;
        hoursInput.className = 'form-control form-control-sm';

        overlay.appendChild(clientInput);
        overlay.appendChild(hoursInput);
        document.body.appendChild(overlay);
        activeOverlay = overlay;

        const dropdown = document.createElement('div');
        Object.assign(dropdown.style, {
            position: 'absolute',
            zIndex: '10001',
            background: '#fff',
            border: '1px solid #ccc',
            borderRadius: '4px',
            display: 'none',
            maxHeight: '150px',
            overflowY: 'auto'
        });
        document.body.appendChild(dropdown);

        setupAutocomplete(clientInput, dropdown);

        [clientInput, hoursInput].forEach(input => {
            input.addEventListener('keydown', async ev => {
                if (ev.key === 'Enter') {
                    dropdown.style.display = 'none';
                    const newName = clientInput.value.trim();
                    const newHours = parseFloat(hoursInput.value);

                    if (!newName || !newHours || newHours <= 0) {
                        alert('Please enter valid client and hours.');
                        return;
                    }

                    const updatedClient = activeClients.find(c => c.client_name.trim().toLowerCase() === newName.toLowerCase());
                    if (!updatedClient) {
                        alert('Selected client not found.');
                        return;
                    }

                    closeActiveInputs();

                    try {
                        const resp = await fetch('update_entry_new.php', {
                            method: 'POST',
                            credentials: 'same-origin',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({
                                entry_id: badge.dataset.entryId,
                                client_id: updatedClient.client_id, // updated here
                                assigned_hours: newHours
                            })
                        });
                    
                        const data = await resp.json();
                        if (resp.ok && data.success) {
                            badge.textContent = `${newName} (${newHours})`;
                            const statusClass = getBadgeClass(updatedClient.status);
                            badge.className = `badge badge-status badge-${statusClass} mt-1 draggable-badge`;
                        } else {
                            alert('Failed to update entry: ' + (data.error || 'Server error'));
                        }
                    } catch (err) {
                        console.error(err);
                        alert('Network error while updating entry.');
                    }
                } else if (ev.key === 'Escape') {
                    closeActiveInputs();
                }
            });
        });

        clientInput.focus();
    });

    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') closeActiveInputs();
    });

})();
