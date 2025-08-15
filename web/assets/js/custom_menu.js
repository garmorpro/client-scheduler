(function() {
    if (!IS_ADMIN) return;

    // Context menu
    const contextMenu = document.createElement('div');
    contextMenu.id = 'badgeContextMenu';
    contextMenu.style.cssText = `
        position:absolute;
        display:none;
        z-index:9999;
        background:#fff;
        border:1px solid #ccc;
        margin-top:15px;
        border-radius:4px;
        box-shadow:0 2px 6px rgba(0,0,0,0.2);
    `;
    document.body.appendChild(contextMenu);

    let selectedCell = null;
    let selectedBadge = null;
    let menuType = null; // 'badge', 'edit', 'add'
    let activeInput = null; // for dynamic input

    // Updated renderCell
    function renderCell(td, timeOffValue = null, entryId = null) {
        td.innerHTML = '';
        td.style.background = timeOffValue ? '#f0f0f0' : '';
        td.style.position = 'relative';

        if (timeOffValue) {
            const div = document.createElement('div');
            div.className = 'timeoff-corner';
            div.dataset.entryId = entryId;
            div.style.position = 'absolute';
            div.style.top = '2px';
            div.style.right = '2px';
            div.style.background = '#ddd';
            div.style.padding = '2px 5px';
            div.style.borderRadius = '4px';
            div.style.fontSize = '0.75em';
            div.style.color = '#555';
            div.style.cursor = 'default';
            div.textContent = timeOffValue;
            td.appendChild(div);
        }

        const plus = document.createElement('i');
        plus.className = 'bi bi-plus text-muted';
        plus.style.cursor = 'pointer';
        td.appendChild(plus);
    }

    async function safeFetchJSON(url, options) {
        try {
            const resp = await fetch(url, options);
            const text = await resp.text();
            return { ok: resp.ok, data: JSON.parse(text) };
        } catch (err) {
            console.error('Fetch or parse error:', err);
            return { ok: false, data: null, error: err.message };
        }
    }

    // Show context menu
    document.addEventListener('contextmenu', e => {
        contextMenu.style.display = 'none';
        selectedCell = null;
        selectedBadge = null;
        menuType = null;

        const target = e.target;

        // Right-click on badge
        if (target.classList.contains('timeoff-corner')) {
            e.preventDefault();
            selectedBadge = target;
            selectedCell = target.parentElement;
            menuType = 'badge';
            contextMenu.innerHTML = `
                <ul style="list-style:none;margin:0;padding:5px 0;cursor:pointer;">
                    <li id="deleteBadge" style="padding:5px 15px;">Delete Entry</li>
                </ul>`;
            contextMenu.style.top = `${e.pageY}px`;
            contextMenu.style.left = `${e.pageX}px`;
            contextMenu.style.display = 'block';
        }
        // Right-click on cell
        else if (target.tagName === 'TD' && target.classList.contains('addable')) {
            e.preventDefault();
            selectedCell = target;
            const timeOff = target.querySelector('.timeoff-corner');
            menuType = timeOff ? 'edit' : 'add';
            contextMenu.innerHTML = `
                <ul style="list-style:none;margin:0;padding:5px 0;cursor:pointer;">
                    <li id="editAddTimeOff" style="padding:5px 15px;">
                        ${timeOff ? 'Edit Time Off' : 'Add Time Off'}
                    </li>
                </ul>`;
            contextMenu.style.top = `${e.pageY}px`;
            contextMenu.style.left = `${e.pageX}px`;
            contextMenu.style.display = 'block';
        }
    });

    // Hide menu
    document.addEventListener('click', e => {
        if (!contextMenu.contains(e.target)) {
            contextMenu.style.display = 'none';
            selectedCell = null;
            selectedBadge = null;
            menuType = null;
        }
    });

    // Dynamic inline input for add/edit
    async function handleTimeOffInput(td, entryId = null) {
        if (activeInput) activeInput.remove();

        const currentVal = entryId ? td.querySelector('.timeoff-corner').textContent : '';
        const input = document.createElement('input');
        input.type = 'text';
        input.value = currentVal;
        input.className = 'form-control form-control-sm';
        input.style.width = '100%';
        td.innerHTML = '';
        td.appendChild(input);
        input.focus();
        activeInput = input;

        input.addEventListener('keydown', async e => {
            if (e.key !== 'Enter') return;
            const val = input.value.trim();
            if (!val) return;

            try {
                if (entryId) {
                    // Update existing
                    const update = await safeFetchJSON('update_timeoff_new.php', {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: {'Content-Type':'application/json'},
                        body: JSON.stringify({ entry_id: entryId, assigned_hours: val })
                    });
                    if (update.ok && update.data?.success) renderCell(td, val, entryId);
                    else alert('Failed to update time off.');
                } else {
                    // Add new
                    const add = await safeFetchJSON('add_timeoff_new.php', {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: {'Content-Type':'application/json'},
                        body: JSON.stringify({ user_id: td.dataset.userId, week_start: td.dataset.weekStart, assigned_hours: val, is_timeoff: 1 })
                    });
                    if (add.ok && add.data?.success && add.data.entry_id) renderCell(td, val, add.data.entry_id);
                    else alert('Failed to add time off.');
                }
            } catch (err) {
                console.error(err);
                alert('Network error while saving time off.');
            }
        });
    }

    // Menu click handling
    contextMenu.addEventListener('click', async e => {
        if (!menuType) return;

        // Delete badge
        if (menuType === 'badge' && e.target.id === 'deleteBadge') {
            const entryId = selectedBadge.dataset.entryId;
            if (!entryId) return;
            if (!confirm('Are you sure you want to delete this entry?')) return;

            const del = await safeFetchJSON('delete_timeoff.php', {
                method: 'POST',
                credentials: 'same-origin',
                headers: {'Content-Type':'application/json'},
                body: JSON.stringify({ entry_id: entryId })
            });

            if (del.ok && del.data?.success) renderCell(selectedBadge.parentElement, null, null);
            else alert('Failed to delete entry.');
        }

        // Add or Edit time off
        else if ((menuType === 'edit' || menuType === 'add') && e.target.id === 'editAddTimeOff') {
            const td = selectedCell;
            const timeOffDiv = td.querySelector('.timeoff-corner');
            const entryId = timeOffDiv ? timeOffDiv.dataset.entryId : null;
            handleTimeOffInput(td, entryId);
        }

        selectedCell = null;
        selectedBadge = null;
        menuType = null;
    });
})();
