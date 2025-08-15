(function() {
    if (!IS_ADMIN) return;

    // Main context menu element
    const contextMenu = document.createElement('div');
    contextMenu.id = 'badgeContextMenu';
    contextMenu.style.cssText = `
        position:absolute;
        display:none;
        z-index:9999;
        background:#fff;
        border:1px solid #ccc;
        margin-top: 15px;
        border-radius:4px;
        box-shadow:0 2px 6px rgba(0,0,0,0.2);
    `;
    document.body.appendChild(contextMenu);

    let selectedCell = null;
    let menuType = null; // 'badge', 'edit', 'add'

    function renderCell(td, timeOffValue = null, entryId = null) {
        td.innerHTML = '';
        if (timeOffValue) {
            const div = document.createElement('div');
            div.className = 'timeoff-corner';
            div.dataset.entryId = entryId;
            div.style.fontSize = '0.8em';
            div.style.color = '#555';
            div.textContent = timeOffValue;
            td.appendChild(div);
        }
        const plus = document.createElement('i');
        plus.className = 'bi bi-plus text-muted';
        td.appendChild(plus);
    }

    async function safeFetchJSON(url, options) {
        try {
            const resp = await fetch(url, options);
            const text = await resp.text();
            try {
                return { ok: resp.ok, data: JSON.parse(text) };
            } catch {
                console.error('Failed to parse JSON:', text);
                return { ok: resp.ok, data: null, error: 'Invalid JSON response' };
            }
        } catch (err) {
            console.error('Network error:', err);
            return { ok: false, data: null, error: err.message };
        }
    }

    // Show context menu
    document.addEventListener('contextmenu', e => {
        contextMenu.style.display = 'none';
        selectedCell = null;
        menuType = null;

        const target = e.target;

        // Right-click on a badge div
        if (target.classList.contains('timeoff-corner')) {
            e.preventDefault();
            selectedCell = target.parentElement;
            menuType = 'badge';
            contextMenu.innerHTML = `
                <ul style="list-style:none; margin:0; padding:5px 0; cursor:pointer;">
                    <li id="deleteEntry" style="padding:5px 15px;">Delete Entry</li>
                </ul>`;
            contextMenu.style.top = `${e.pageY}px`;
            contextMenu.style.left = `${e.pageX}px`;
            contextMenu.style.display = 'block';
        }
        // Right-click on a cell
        else if (target.tagName === 'TD' && target.classList.contains('addable')) {
            e.preventDefault();
            selectedCell = target;
            const timeOff = target.querySelector('.timeoff-corner');
            menuType = timeOff ? 'edit' : 'add';
            contextMenu.innerHTML = `
                <ul style="list-style:none; margin:0; padding:5px 0; cursor:pointer;">
                    <li id="editAddTimeOff" style="padding:5px 15px;">
                        ${timeOff ? 'Edit Time Off' : 'Add Time Off'}
                    </li>
                </ul>`;
            contextMenu.style.top = `${e.pageY}px`;
            contextMenu.style.left = `${e.pageX}px`;
            contextMenu.style.display = 'block';
        }
    });

    // Hide menu when clicking outside
    document.addEventListener('click', e => {
        if (!contextMenu.contains(e.target) && selectedCell) {
            const timeOff = selectedCell.querySelector('.timeoff-corner');
            renderCell(selectedCell, timeOff ? timeOff.textContent : null, timeOff ? timeOff.dataset.entryId : null);
            contextMenu.style.display = 'none';
            selectedCell = null;
            menuType = null;
        }
    });

    // Handle menu clicks
    contextMenu.addEventListener('click', async e => {
        if (!selectedCell || !menuType) return;

        const td = selectedCell;
        const userId = td.dataset.userId;
        const weekStart = td.dataset.weekStart;
        let timeOff = td.querySelector('.timeoff-corner');
        const entryId = timeOff ? timeOff.dataset.entryId : null;

        if (menuType === 'badge' && e.target.id === 'deleteEntry') {
            if (!entryId) return;
            if (!confirm('Are you sure you want to delete this entry?')) return;

            const del = await safeFetchJSON('delete_timeoff.php', {
                method: 'POST',
                credentials: 'same-origin',
                headers: {'Content-Type':'application/json'},
                body: JSON.stringify({ entry_id: entryId })
            });

            if (del.ok && del.data && del.data.success) {
                renderCell(td, null, null);
            } else {
                alert('Failed to delete entry: ' + (del.data?.error || del.error || 'Server error'));
            }
        }

        else if (menuType === 'edit' || menuType === 'add') {
            const currentVal = timeOff ? timeOff.textContent : '';
            td.innerHTML = '';
            const input = document.createElement('input');
            input.type = 'text';
            input.value = currentVal;
            input.className = 'form-control form-control-sm';
            input.style.width = '100%';
            td.appendChild(input);
            input.focus();

            input.addEventListener('keydown', async ev => {
                if (ev.key !== 'Enter') return;
                const val = input.value.trim();
                if (!val) return;

                try {
                    let finalEntryId = entryId;

                    if (finalEntryId) {
                        // Update existing
                        const update = await safeFetchJSON('update_timeoff_new.php', {
                            method: 'POST',
                            credentials: 'same-origin',
                            headers: {'Content-Type':'application/json'},
                            body: JSON.stringify({ entry_id: finalEntryId, assigned_hours: val })
                        });
                        if (update.ok && update.data && update.data.success) {
                            renderCell(td, val, finalEntryId);
                        } else {
                            alert('Failed to update time off.');
                            renderCell(td, currentVal, finalEntryId);
                        }
                    } else {
                        // Add new
                        const add = await safeFetchJSON('add_timeoff_new.php', {
                            method: 'POST',
                            credentials: 'same-origin',
                            headers: {'Content-Type':'application/json'},
                            body: JSON.stringify({ user_id: userId, week_start: weekStart, assigned_hours: val, is_timeoff: 1 })
                        });
                        if (add.ok && add.data && add.data.success && add.data.entry_id) {
                            renderCell(td, val, add.data.entry_id);
                        } else {
                            alert('Failed to add time off.');
                            renderCell(td, null, null);
                        }
                    }
                } catch (err) {
                    console.error('Network error:', err);
                    alert('Network error while saving time off.');
                    renderCell(td, timeOff ? timeOff.textContent : null, entryId);
                }
            });
        }

        contextMenu.style.display = 'none';
        selectedCell = null;
        menuType = null;
    });
})();
