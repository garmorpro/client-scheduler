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

    // Autocomplete suggestion list, shared by every open card
    const suggestionsList = document.createElement('div');
    suggestionsList.className = 'cell-edit-suggestions';
    suggestionsList.style.display = 'none';
    document.body.appendChild(suggestionsList);
    suggestionsList.addEventListener('click', e => e.stopPropagation());

    document.addEventListener('click', e => {
        if (activeTd) {
            const clickInsideTd = activeTd.contains(e.target);
            const clickInsideOverlay = activeOverlay && activeOverlay.contains(e.target);
            if (!clickInsideTd && !clickInsideOverlay) closeActiveInputs();
        }
    });

    // The floating card never touches the cell's own markup (badges, the
    // "+" icon, the time-off corner) - it just sits on top - so closing it
    // is always just "remove the overlay," nothing to restore underneath.
    function closeActiveInputs() {
        if (activeOverlay) {
            activeOverlay.remove();
            activeOverlay = null;
        }
        activeTd = null;
        suggestionsList.style.display = 'none';
    }

    document.querySelectorAll('td.addable').forEach(td => {
        td.addEventListener('click', e => {
            const target = e.target;
            if (target.classList.contains('draggable-badge') || target.classList.contains('timeoff-corner')) return;
            if (activeTd === td) return;

            closeActiveInputs();
            openEntryCard(td, null);
        });
    });

    // Double-click an existing badge to edit it
    document.addEventListener('dblclick', e => {
        if (!e.target.classList.contains('draggable-badge')) return;
        e.stopPropagation();
        closeActiveInputs();
        activeTd = e.target.closest('td');
        openEntryCard(activeTd, e.target);
    });

    function buildField(labelText, inputEl) {
        const wrap = document.createElement('div');
        wrap.className = 'cell-edit-field';
        const label = document.createElement('label');
        label.className = 'cell-edit-label';
        label.textContent = labelText;
        wrap.appendChild(label);
        wrap.appendChild(inputEl);
        return wrap;
    }

    // Builds (or clears) the audit-type picker for a resolved engagement,
    // wrapped as a labeled field. One-click pills instead of a <select> -
    // this card gets used every time someone's staffed on a multi-audit-type
    // engagement, so a single click beats open-dropdown-then-click. The
    // chosen id is tracked on the container itself (`dataset.selectedAuditTypeId`)
    // since there's no underlying <select> element to read a .value from.
    function renderAuditTypeField(container, engagement, currentAuditTypeId) {
        container.innerHTML = '';
        delete container.dataset.selectedAuditTypeId;
        if (!engagement || !engagement.audit_types || engagement.audit_types.length === 0) return;

        // Only one choice - nothing to pick, just show it as a fixed pill.
        if (engagement.audit_types.length === 1) {
            container.dataset.selectedAuditTypeId = String(engagement.audit_types[0].id);
            const single = document.createElement('span');
            single.className = 'cell-edit-audit-pill active single';
            single.textContent = engagement.audit_types[0].name;
            container.appendChild(buildField('Audit Type', single));
            return;
        }

        const pillRow = document.createElement('div');
        pillRow.className = 'cell-edit-audit-pills';
        engagement.audit_types.forEach(at => {
            const pill = document.createElement('button');
            pill.type = 'button';
            pill.className = 'cell-edit-audit-pill';
            pill.textContent = at.name;
            pill.dataset.id = at.id;
            if (currentAuditTypeId && String(currentAuditTypeId) === String(at.id)) {
                pill.classList.add('active');
                container.dataset.selectedAuditTypeId = String(at.id);
            }
            pill.addEventListener('click', e => {
                e.stopPropagation();
                pillRow.querySelectorAll('.cell-edit-audit-pill').forEach(p => p.classList.remove('active'));
                pill.classList.add('active');
                container.dataset.selectedAuditTypeId = String(at.id);
            });
            pillRow.appendChild(pill);
        });
        container.appendChild(buildField('Audit Type', pillRow));
    }

    // Single floating card used for both adding a new entry to a cell and
    // editing an existing one - `existingBadge` is null when adding.
    function openEntryCard(td, existingBadge) {
        activeTd = td;

        const isEdit = !!existingBadge;
        const match = isEdit ? existingBadge.textContent.match(/^(.*)\s+\(([\d.]+)\)$/) : null;
        const initialName = match ? match[1] : '';
        const initialHours = match ? match[2] : '';
        const initialEngagementId = isEdit ? (existingBadge.dataset.engagementId || null) : null;
        const initialAuditTypeId = isEdit ? (existingBadge.dataset.auditTypeId || null) : null;

        const rect = td.getBoundingClientRect();
        const overlay = document.createElement('div');
        overlay.className = 'cell-edit-card';
        Object.assign(overlay.style, {
            position: 'absolute',
            top: rect.top + window.scrollY + 'px',
            left: rect.left + window.scrollX + 'px',
            minWidth: Math.max(rect.width, 190) + 'px',
            zIndex: '10000'
        });
        document.body.appendChild(overlay);
        activeOverlay = overlay;
        overlay.addEventListener('click', e => e.stopPropagation());

        const clientInput = document.createElement('input');
        clientInput.type = 'text';
        clientInput.placeholder = 'Client name';
        clientInput.className = 'cell-edit-input';
        clientInput.value = initialName;

        const auditTypeContainer = document.createElement('div');

        const hoursInput = document.createElement('input');
        hoursInput.type = 'number';
        hoursInput.min = '0';
        hoursInput.placeholder = 'Hours';
        hoursInput.className = 'cell-edit-input';
        hoursInput.value = initialHours;

        const actions = document.createElement('div');
        actions.className = 'cell-edit-actions';
        const saveBtn = document.createElement('button');
        saveBtn.type = 'button';
        saveBtn.className = 'cell-edit-btn-save';
        saveBtn.textContent = isEdit ? 'Save' : 'Add';
        const cancelBtn = document.createElement('button');
        cancelBtn.type = 'button';
        cancelBtn.className = 'cell-edit-btn-cancel';
        cancelBtn.textContent = 'Cancel';
        actions.appendChild(cancelBtn);
        actions.appendChild(saveBtn);

        overlay.appendChild(buildField('Client', clientInput));
        overlay.appendChild(auditTypeContainer);
        overlay.appendChild(buildField('Hours', hoursInput));
        overlay.appendChild(actions);

        // Editing an existing badge already has an unambiguous engagement_id
        // (unlike typing a fresh client name), so pre-populate its audit
        // types immediately instead of waiting for a new autocomplete pick.
        let selectedEngagement = isEdit ? findEngagementById(initialEngagementId) : null;
        if (selectedEngagement) renderAuditTypeField(auditTypeContainer, selectedEngagement, initialAuditTypeId);

        clientInput.addEventListener('input', () => {
            selectedEngagement = null;
            auditTypeContainer.innerHTML = '';

            const val = clientInput.value.trim();
            if (val.length < 3) {
                suggestionsList.style.display = 'none';
                return;
            }
            const matches = searchClients(val);
            suggestionsList.innerHTML = '';
            matches.forEach(client => {
                const item = document.createElement('div');
                item.className = 'cell-edit-suggestion';
                item.textContent = client.client_name;
                item.addEventListener('click', e => {
                    e.stopPropagation();
                    clientInput.value = client.client_name;
                    suggestionsList.style.display = 'none';
                    selectedEngagement = client;
                    renderAuditTypeField(auditTypeContainer, client, null);
                });
                suggestionsList.appendChild(item);
            });
            if (matches.length > 0) {
                const inputRect = clientInput.getBoundingClientRect();
                suggestionsList.style.top = inputRect.bottom + window.scrollY + 'px';
                suggestionsList.style.left = inputRect.left + window.scrollX + 'px';
                suggestionsList.style.width = inputRect.width + 'px';
                suggestionsList.style.display = 'block';
            } else {
                suggestionsList.style.display = 'none';
            }
        });

        function trySubmit() {
            const clientName = clientInput.value.trim();
            const hours = parseFloat(hoursInput.value);

            if (!clientName || !hours || hours <= 0) {
                notify('warning', 'Missing information', 'Please enter a valid client and hours.');
                return;
            }
            const needsAuditType = auditTypeContainer.children.length > 0;
            const auditTypeId = auditTypeContainer.dataset.selectedAuditTypeId || null;
            if (needsAuditType && !auditTypeId) {
                notify('warning', 'Missing information', 'Please choose an audit type.');
                return;
            }

            const engagementId = selectedEngagement ? selectedEngagement.engagement_id : initialEngagementId;
            closeActiveInputs();

            const payload = {
                client_name: clientName,
                engagement_id: engagementId,
                audit_type_id: auditTypeId,
                assigned_hours: hours
            };
            const request = isEdit
                ? fetch('update_entry_new.php', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify({ ...payload, entry_id: existingBadge.dataset.entryId })
                })
                : fetch('add_entry_new.php', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify({ ...payload, user_id: td.dataset.userId, week_start: td.dataset.weekStart })
                });

            request
                .then(resp => resp.json().then(data => ({ ok: resp.ok, data })))
                .then(({ ok, data }) => {
                    if (ok && data.success) {
                        saveScrollAndReload();
                    } else {
                        notify('error', isEdit ? 'Failed to update entry' : 'Failed to add entry', data.error || 'Server error');
                    }
                })
                .catch(err => {
                    console.error(err);
                    notify('error', 'Network error', 'Could not save. Please try again.');
                });
        }

        saveBtn.addEventListener('click', trySubmit);
        cancelBtn.addEventListener('click', () => closeActiveInputs());

        [clientInput, hoursInput].forEach(input => {
            input.addEventListener('keydown', e => {
                if (e.key === 'Enter') { e.preventDefault(); trySubmit(); }
                else if (e.key === 'Escape') closeActiveInputs();
            });
        });

        (isEdit ? hoursInput : clientInput).focus();
        if (isEdit) hoursInput.select();
    }

    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') closeActiveInputs();
    });

})();
