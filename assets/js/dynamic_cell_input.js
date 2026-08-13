(function() {
    if (!IS_ADMIN) return;

    let activeClients = [];
    let activeTd = null;
    let activeOverlay = null;

    function notify(icon, title, text) {
        if (typeof appNotify !== 'undefined') {
            appNotify({ icon, title, text });
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
    // engagement, so a single click beats open-dropdown-then-click.
    //
    // `allowMultiple` is only true when adding a fresh entry: someone's
    // often staffed across more than one audit type on the same engagement
    // in the same week, and each entries row can only hold one
    // audit_type_id, so picking several here means several entries get
    // created (with their own hours each - see renderHoursFields).
    // Editing an existing badge is always single-select, since that one
    // entry already has exactly one audit_type_id.
    //
    // `selectedIds` is a Set the caller owns (mutated in place) and
    // `onChange` is called after every pick/unpick so the caller can
    // re-sync whatever depends on the selection (the hours fields).
    function renderAuditTypeField(container, engagement, selectedIds, allowMultiple, onChange) {
        container.innerHTML = '';
        if (!engagement || !engagement.audit_types || engagement.audit_types.length === 0) return;

        // Only one choice - nothing to pick, just show it as a fixed pill.
        if (engagement.audit_types.length === 1) {
            selectedIds.clear();
            selectedIds.add(String(engagement.audit_types[0].id));
            const single = document.createElement('span');
            single.className = 'cell-edit-audit-pill active single';
            single.textContent = engagement.audit_types[0].name;
            container.appendChild(buildField('Audit Type', single));
            onChange();
            return;
        }

        const pillRow = document.createElement('div');
        pillRow.className = 'cell-edit-audit-pills';
        engagement.audit_types.forEach(at => {
            const id = String(at.id);
            const pill = document.createElement('button');
            pill.type = 'button';
            pill.className = 'cell-edit-audit-pill';
            pill.textContent = at.name;
            pill.dataset.id = id;
            if (selectedIds.has(id)) pill.classList.add('active');
            pill.addEventListener('click', e => {
                e.stopPropagation();
                if (selectedIds.has(id)) {
                    selectedIds.delete(id);
                    pill.classList.remove('active');
                } else {
                    if (!allowMultiple) {
                        selectedIds.clear();
                        pillRow.querySelectorAll('.cell-edit-audit-pill').forEach(p => p.classList.remove('active'));
                    }
                    selectedIds.add(id);
                    pill.classList.add('active');
                }
                onChange();
            });
            pillRow.appendChild(pill);
        });
        container.appendChild(buildField(allowMultiple ? 'Audit Type(s)' : 'Audit Type', pillRow));
    }

    // One hours input per selected audit type (labeled with the type name
    // once there's more than one, so it's clear which box is which). Falls
    // back to a single plain hours field when the engagement has no audit
    // types at all - same as before this existed. `hoursByType` persists
    // values across re-renders (toggling a pill off and back on keeps
    // whatever was typed).
    function renderHoursFields(container, engagement, selectedIds, hoursByType, fallbackInput) {
        container.innerHTML = '';
        const hasTypes = engagement && engagement.audit_types && engagement.audit_types.length > 0;
        if (!hasTypes) {
            container.appendChild(buildField('Hours', fallbackInput));
            return;
        }
        if (selectedIds.size === 0) {
            const hint = document.createElement('div');
            hint.className = 'cell-edit-hint';
            hint.textContent = 'Choose an audit type above.';
            container.appendChild(hint);
            return;
        }
        // Keep the engagement's own ordering, not selection order.
        engagement.audit_types
            .filter(at => selectedIds.has(String(at.id)))
            .forEach(at => {
                const id = String(at.id);
                const input = document.createElement('input');
                input.type = 'number';
                input.min = '0';
                input.step = '0.25';
                input.placeholder = 'Hours';
                input.className = 'cell-edit-input';
                input.value = hoursByType.get(id) || '';
                input.addEventListener('click', e => e.stopPropagation());
                input.addEventListener('input', () => hoursByType.set(id, input.value));
                const label = selectedIds.size > 1 ? `Hours — ${at.name}` : 'Hours';
                container.appendChild(buildField(label, input));
            });
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

        const hoursContainer = document.createElement('div');
        hoursContainer.className = 'cell-edit-hours-fields';

        overlay.appendChild(buildField('Client', clientInput));
        overlay.appendChild(auditTypeContainer);
        overlay.appendChild(hoursContainer);
        overlay.appendChild(actions);

        // Editing an existing badge already has an unambiguous engagement_id
        // (unlike typing a fresh client name), so pre-populate its audit
        // type immediately instead of waiting for a new autocomplete pick.
        let selectedEngagement = isEdit ? findEngagementById(initialEngagementId) : null;
        const selectedAuditTypeIds = new Set(isEdit && initialAuditTypeId ? [String(initialAuditTypeId)] : []);
        const hoursByType = new Map(isEdit && initialAuditTypeId ? [[String(initialAuditTypeId), initialHours]] : []);

        function syncHoursFields() {
            renderHoursFields(hoursContainer, selectedEngagement, selectedAuditTypeIds, hoursByType, hoursInput);
        }

        if (selectedEngagement) {
            renderAuditTypeField(auditTypeContainer, selectedEngagement, selectedAuditTypeIds, !isEdit, syncHoursFields);
        }
        syncHoursFields();

        clientInput.addEventListener('input', () => {
            selectedEngagement = null;
            selectedAuditTypeIds.clear();
            hoursByType.clear();
            auditTypeContainer.innerHTML = '';
            syncHoursFields();

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
                    selectedAuditTypeIds.clear();
                    hoursByType.clear();
                    renderAuditTypeField(auditTypeContainer, client, selectedAuditTypeIds, !isEdit, syncHoursFields);
                    syncHoursFields();
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

        // Fires one request per selected audit type (each entries row can
        // only carry one audit_type_id) and reloads once they've all
        // settled. If one fails after others already saved, there's no
        // rollback across separate inserts - the error names which type
        // failed and the grid still reloads to show whatever did save.
        function submitEntries(clientName, engagementId, items) {
            closeActiveInputs();
            const requests = items.map(item => {
                const payload = {
                    client_name: clientName,
                    engagement_id: engagementId,
                    audit_type_id: item.auditTypeId,
                    assigned_hours: item.hours
                };
                return isEdit
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
            });

            Promise.all(requests.map(r => r.then(resp => resp.json().then(data => ({ ok: resp.ok, data })))))
                .then(results => {
                    const failed = results.find(r => !(r.ok && r.data.success));
                    if (failed) {
                        notify('error', isEdit ? 'Failed to update entry' : 'Failed to add entry', failed.data.error || 'Server error');
                    }
                    saveScrollAndReload();
                })
                .catch(err => {
                    console.error(err);
                    notify('error', 'Network error', 'Could not save. Please try again.');
                });
        }

        function trySubmit() {
            const clientName = clientInput.value.trim();
            if (!clientName) {
                notify('warning', 'Missing information', 'Please enter a client.');
                return;
            }

            const engagementId = selectedEngagement ? selectedEngagement.engagement_id : initialEngagementId;
            const hasAuditTypes = selectedEngagement && selectedEngagement.audit_types && selectedEngagement.audit_types.length > 0;

            if (!hasAuditTypes) {
                const hours = parseFloat(hoursInput.value);
                if (!hours || hours <= 0) {
                    notify('warning', 'Missing information', 'Please enter valid hours.');
                    return;
                }
                submitEntries(clientName, engagementId, [{ auditTypeId: null, hours }]);
                return;
            }

            if (selectedAuditTypeIds.size === 0) {
                notify('warning', 'Missing information', 'Please choose an audit type.');
                return;
            }

            const items = [];
            for (const id of selectedAuditTypeIds) {
                const hours = parseFloat(hoursByType.get(id));
                if (!hours || hours <= 0) {
                    const name = (selectedEngagement.audit_types.find(a => String(a.id) === id) || {}).name || id;
                    notify('warning', 'Missing information', `Please enter valid hours for ${name}.`);
                    return;
                }
                items.push({ auditTypeId: id, hours });
            }
            submitEntries(clientName, engagementId, items);
        }

        saveBtn.addEventListener('click', trySubmit);
        cancelBtn.addEventListener('click', () => closeActiveInputs());

        clientInput.addEventListener('keydown', e => {
            if (e.key === 'Enter') { e.preventDefault(); trySubmit(); }
            else if (e.key === 'Escape') closeActiveInputs();
        });
        // Delegated so it keeps working as hoursContainer's children get
        // swapped out every time the audit-type selection changes.
        hoursContainer.addEventListener('keydown', e => {
            if (e.target.tagName !== 'INPUT') return;
            if (e.key === 'Enter') { e.preventDefault(); trySubmit(); }
            else if (e.key === 'Escape') closeActiveInputs();
        });

        if (isEdit) {
            const firstHoursInput = hoursContainer.querySelector('input');
            if (firstHoursInput) { firstHoursInput.focus(); firstHoursInput.select(); }
        } else {
            clientInput.focus();
        }
    }

    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') closeActiveInputs();
    });

})();
