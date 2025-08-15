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

    // Global dropdown for single-badge (inline) cells
    const globalDropdown = document.createElement('div');
    globalDropdown.style.position = 'absolute';
    globalDropdown.style.zIndex = '9999';
    globalDropdown.style.background = '#fff';
    globalDropdown.style.border = '1px solid #ccc';
    globalDropdown.style.borderRadius = '4px';
    globalDropdown.style.display = 'none';
    globalDropdown.style.maxHeight = '150px';
    globalDropdown.style.overflowY = 'auto';
    document.body.appendChild(globalDropdown);
    globalDropdown.addEventListener('click', e => e.stopPropagation());

    // Click outside listener
    document.addEventListener('click', e => {
        if (activeTd) {
            const clickInsideTd = activeTd.contains(e.target);
            const clickInsideOverlay = activeOverlay && activeOverlay.contains(e.target);
            if (!clickInsideTd && !clickInsideOverlay) {
                closeActiveInputs();
            }
        }
    });

    // Restore bi-plus icon in empty cells
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
            // Only restore plus icon if no badges exist
            if (!activeTd.querySelector('.draggable-badge')) {
                const timeOff = activeTd.querySelector('.timeoff-corner');
                activeTd.innerHTML = '';
                if (timeOff) activeTd.appendChild(timeOff);
                restoreBiPlus(activeTd);
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
            if (e.target.tagName === 'INPUT') return;
            if (activeTd === td) return;

            closeActiveInputs();
            activeTd = td;

            if (td.querySelector('.draggable-badge')) {
                showOverlay(td);
            } else {
                showInlineInputs(td);
            }
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

        [clientInput, hoursInput].forEach(input => input.addEventListener('click', e => e.stopPropagation()));

        attachInputEvents(td, clientInput, hoursInput, true);
        setupAutocomplete(clientInput, globalDropdown);
        clientInput.focus();
    }

    function showOverlay(td) {
        const rect = td.getBoundingClientRect();
        const overlay = document.createElement('div');
        overlay.style.position = 'absolute';
        overlay.style.top = rect.top + window.scrollY + 'px';
        overlay.style.left = rect.left + window.scrollX + 'px';
        overlay.style.width = rect.width + 'px';
        overlay.style.minHeight = '50px';
        overlay.style.background = 'rgba(255,255,255,0.95)';
        overlay.style.backdropFilter = 'blur(2px)';
        overlay.style.border = '1px solid #ccc';
        overlay.style.borderRadius = '4px';
        overlay.style.padding = '5px';
        overlay.style.zIndex = '10000';
        overlay.style.display = 'flex';
        overlay.style.flexDirection = 'column';

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
        overlayDropdown.style.position = 'absolute';
        overlayDropdown.style.zIndex = '10001';
        overlayDropdown.style.background = '#fff';
        overlayDropdown.style.border = '1px solid #ccc';
        overlayDropdown.style.borderRadius = '4px';
        overlayDropdown.style.display = 'none';
        overlayDropdown.style.maxHeight = '150px';
        overlayDropdown.style.overflowY = 'auto';
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
                } else {
                    container.style.display = 'none';
                }
            } else {
                container.style.display = 'none';
            }
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
                            makeBadgeDraggable(span);

                            // Remove plus icon if present
                            const plusIcon = td.querySelector('.bi-plus');
                            if (plusIcon) plusIcon.remove();

                            // Append the new badge without removing existing ones
                            td.appendChild(span);

                            // Keep timeoff-corner on top if exists
                            const timeOff = td.querySelector('.timeoff-corner');
                            if (timeOff) td.appendChild(timeOff);

                            closeActiveInputs();
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
