(function() {
    if (!IS_ADMIN) return;

    let activeClients = [];
    let activeTd = null;
    let activeOverlay = null;

    function notify(icon, title, text) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({ icon, title, text });
        } else {
            alert(text ? `${title}: ${text}` : title);
        }
    }

    // Fetch clients (one row per engagement, each carrying its own audit types)
    function loadClients() {
        fetch('get_clients.php')
            .then(res => res.json())
            .then(data => { activeClients = data; })
            .catch(err => console.error('Failed to fetch clients:', err));
    }
    loadClients();
    document.addEventListener('auditTypesUpdated', loadClients);

    function searchClients(query) {
        query = query.toLowerCase();
        return activeClients.filter(c => c.client_name.toLowerCase().includes(query));
    }

    // Find the single engagement a badge's data-engagement-id refers to, so
    // the edit-existing-entry flow can offer the right audit type choices
    // without any ambiguity (unlike matching by client_name, which breaks
    // when a client has more than one engagement).
    function findEngagementById(engagementId) {
        return activeClients.find(c => String(c.engagement_id) === String(engagementId)) || null;
    }

    function saveScrollAndReload() {
        const container = document.querySelector('.sheet-container');
        sessionStorage.setItem('scheduleScrollLeft', container.scrollLeft);
        sessionStorage.setItem('scheduleScrollTop', container.scrollTop);
        location.reload();
    }

    // Global dropdown for inline cells
    const globalDropdown = document.createElement('div');
    Object.assign(globalDropdown.style, {
        position: 'absolute',
        zIndex: '9999',
        background: document.body.classList.contains('dark-mode') ? '#2a2a3d' : '#fff',
        border: document.body.classList.contains('dark-mode') ? '1px solid #3a3a50' : '1px solid #ccc',
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

    // Builds (or clears) the audit-type <select> for a resolved engagement.
    // Returns the <select> if one was created, or null if the engagement has
    // no audit types to choose from (nothing to show).
    function renderAuditTypeSelect(container, engagement, currentAuditTypeId) {
        container.innerHTML = '';
        if (!engagement || !engagement.audit_types || engagement.audit_types.length === 0) return null;

        const select = document.createElement('select');
        select.className = 'form-select form-select-sm mb-1 audit-type-select';
        select.style.width = '100%';
        if (engagement.audit_types.length > 1) {
            const placeholder = document.createElement('option');
            placeholder.value = '';
            placeholder.textContent = 'Audit Type...';
            select.appendChild(placeholder);
        }
        engagement.audit_types.forEach(at => {
            const opt = document.createElement('option');
            opt.value = at.id;
            opt.textContent = at.name;
            select.appendChild(opt);
        });
        if (engagement.audit_types.length === 1) {
            select.value = engagement.audit_types[0].id;
        } else if (currentAuditTypeId) {
            select.value = currentAuditTypeId;
        }
        select.addEventListener('click', e => e.stopPropagation());
        container.appendChild(select);
        return select;
    }

    document.querySelectorAll('td.addable').forEach(td => {
        td.addEventListener('click', e => {
            const target = e.target;
            if (target.classList.contains('draggable-badge') || target.tagName === 'INPUT' || target.tagName === 'SELECT' || target.classList.contains('timeoff-corner')) return;
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

        const auditTypeContainer = document.createElement('div');

        const hoursInput = document.createElement('input');
        hoursInput.type = 'number';
        hoursInput.min = '0';
        hoursInput.placeholder = 'Hours';
        hoursInput.className = 'form-control form-control-sm';
        hoursInput.style.width = '100%';

        td.appendChild(clientInput);
        td.appendChild(auditTypeContainer);
        td.appendChild(hoursInput);

        let selectedEngagement = null;
        [clientInput, hoursInput].forEach(input => input.addEventListener('click', e => e.stopPropagation()));

        setupAutocomplete(clientInput, globalDropdown, (client) => {
            selectedEngagement = client;
            renderAuditTypeSelect(auditTypeContainer, client, null);
        });

        attachInputEvents(td, clientInput, hoursInput, true, null, () => selectedEngagement, auditTypeContainer);
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
            background: document.body.classList.contains('dark-mode') ? '#2a2a3d' : '#fff',
            border: document.body.classList.contains('dark-mode') ? '1px solid #3a3a50' : '1px solid #ccc',
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

        const auditTypeContainer = document.createElement('div');

        const hoursInput = document.createElement('input');
        hoursInput.type = 'number';
        hoursInput.min = '0';
        hoursInput.placeholder = 'Hours';
        hoursInput.className = 'form-control form-control-sm';
        hoursInput.style.width = '100%';

        overlay.appendChild(clientInput);
        overlay.appendChild(auditTypeContainer);
        overlay.appendChild(hoursInput);
        document.body.appendChild(overlay);
        activeOverlay = overlay;

        let selectedEngagement = null;
        [clientInput, hoursInput].forEach(input => input.addEventListener('click', e => e.stopPropagation()));
        overlay.addEventListener('click', e => e.stopPropagation());

        const overlayDropdown = document.createElement('div');
        Object.assign(overlayDropdown.style, {
            position: 'absolute',
            zIndex: '10001',
            background: document.body.classList.contains('dark-mode') ? '#2a2a3d' : '#fff',
            border: document.body.classList.contains('dark-mode') ? '1px solid #3a3a50' : '1px solid #ccc',
            borderRadius: '4px',
            display: 'none',
            maxHeight: '150px',
            overflowY: 'auto'
        });
        document.body.appendChild(overlayDropdown);

        setupAutocomplete(clientInput, overlayDropdown, (client) => {
            selectedEngagement = client;
            renderAuditTypeSelect(auditTypeContainer, client, null);
        });
        attachInputEvents(td, clientInput, hoursInput, false, overlay, () => selectedEngagement, auditTypeContainer);
        clientInput.focus();
    }

    function setupAutocomplete(clientInput, container, onSelect) {
        clientInput.addEventListener('input', () => {
            if (onSelect) onSelect(null); // typing again invalidates any prior selection
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
                        if (onSelect) onSelect(client);
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

    function attachInputEvents(td, clientInput, hoursInput, inline = true, overlay = null, getEngagement = () => null, auditTypeContainer = null) {
        [clientInput, hoursInput].forEach(input => {
            input.addEventListener('keydown', async e => {
                if (e.key === 'Enter') {
                    const clientName = clientInput.value.trim();
                    const hours = parseFloat(hoursInput.value);

                    if (!clientName || !hours || hours <= 0) {
                        notify('warning', 'Missing information', 'Please enter a valid client and hours.');
                        return;
                    }

                    const engagement = getEngagement();
                    const auditSelect = auditTypeContainer ? auditTypeContainer.querySelector('select') : null;
                    if (auditSelect && !auditSelect.value) {
                        notify('warning', 'Missing information', 'Please choose an audit type.');
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
                                client_name: clientName,
                                engagement_id: engagement ? engagement.engagement_id : null,
                                audit_type_id: auditSelect ? auditSelect.value : null,
                                assigned_hours: hours
                            })
                        });

                        const data = await resp.json();
                        if (resp.ok && data.success) {
                            saveScrollAndReload();
                        } else {
                            notify('error', 'Failed to add entry', data.error || 'Server error');
                        }
                    } catch (err) {
                        console.error(err);
                        notify('error', 'Network error', 'Could not add entry. Please try again.');
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
        const currentEngagementId = badge.dataset.engagementId || null;
        const currentAuditTypeId = badge.dataset.auditTypeId || null;

        const rect = td.getBoundingClientRect();
        const overlay = document.createElement('div');
        Object.assign(overlay.style, {
            position: 'absolute',
            top: rect.top + window.scrollY + 'px',
            left: rect.left + window.scrollX + 'px',
            width: rect.width + 'px',
            minHeight: '50px',
            background: document.body.classList.contains('dark-mode') ? '#2a2a3d' : '#fff',
            border: document.body.classList.contains('dark-mode') ? '1px solid #3a3a50' : '1px solid #ccc',
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

        const auditTypeContainer = document.createElement('div');

        const hoursInput = document.createElement('input');
        hoursInput.type = 'number';
        hoursInput.min = '0';
        hoursInput.value = currentHours;
        hoursInput.className = 'form-control form-control-sm';

        overlay.appendChild(clientInput);
        overlay.appendChild(auditTypeContainer);
        overlay.appendChild(hoursInput);
        document.body.appendChild(overlay);
        activeOverlay = overlay;

        // Editing an existing badge already has an unambiguous engagement_id
        // (unlike typing a fresh client name), so pre-populate its audit
        // types straight away without waiting for a new autocomplete pick.
        let selectedEngagement = findEngagementById(currentEngagementId);
        renderAuditTypeSelect(auditTypeContainer, selectedEngagement, currentAuditTypeId);

        const dropdown = document.createElement('div');
        Object.assign(dropdown.style, {
            position: 'absolute',
            zIndex: '10001',
            background: document.body.classList.contains('dark-mode') ? '#2a2a3d' : '#fff',
            border: document.body.classList.contains('dark-mode') ? '1px solid #3a3a50' : '1px solid #ccc',
            borderRadius: '4px',
            display: 'none',
            maxHeight: '150px',
            overflowY: 'auto'
        });
        document.body.appendChild(dropdown);

        setupAutocomplete(clientInput, dropdown, (client) => {
            selectedEngagement = client;
            renderAuditTypeSelect(auditTypeContainer, client, null);
        });

        [clientInput, hoursInput].forEach(input => {
            input.addEventListener('keydown', async ev => {
                if (ev.key === 'Enter') {
                    dropdown.style.display = 'none';
                    const newName = clientInput.value.trim();
                    const newHours = parseFloat(hoursInput.value);

                    if (!newName || !newHours || newHours <= 0) {
                        notify('warning', 'Missing information', 'Please enter a valid client and hours.');
                        return;
                    }

                    const auditSelect = auditTypeContainer.querySelector('select');
                    if (auditSelect && !auditSelect.value) {
                        notify('warning', 'Missing information', 'Please choose an audit type.');
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
                                client_name: newName,
                                engagement_id: selectedEngagement ? selectedEngagement.engagement_id : currentEngagementId,
                                audit_type_id: auditSelect ? auditSelect.value : null,
                                assigned_hours: newHours
                            })
                        });

                        const data = await resp.json();
                        if (resp.ok && data.success) {
                            saveScrollAndReload();
                        } else {
                            notify('error', 'Failed to update entry', data.error || 'Server error');
                        }
                    } catch (err) {
                        console.error(err);
                        notify('error', 'Network error', 'Could not update entry. Please try again.');
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
