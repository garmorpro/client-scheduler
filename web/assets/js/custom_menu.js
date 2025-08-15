(function() {
    if (!IS_ADMIN) return;

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
    let selectedBadge = null; // badge div specifically
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
            return { ok: resp.ok, data: JSON.parse(text) };
        } catch (err) {
            console.error('Fetch or parse error:', err);
            return { ok: false, data: null, error: err.message };
        }
    }

    // Show context menu
    document.addEventListener('contextmenu', async e => {
        contextMenu.style.display = 'none';
        selectedCell = null;
        selectedBadge = null;
        menuType = null;

        const target = e.target;

        // 1️⃣ Right-click on badge
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
        // 2️⃣ / 3️⃣ Right-click on cell
        else if (target.tagName === 'TD' && target.classList.contains('addable')) {
            e.preventDefault();
            selectedCell = target;

            // check if timeoff exists
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

    // Close context menu
    document.addEventListener('click', e => {
        if (!contextMenu.contains(e.target)) {
            contextMenu.style.display = 'none';
            selectedCell = null;
            selectedBadge = null;
            menuType = null;
        }
    });

    contextMenu.addEventListener('click', async e => {
        if (!menuType) return;

        // 1️⃣ Delete badge
        if (menuType === 'badge' && e.target.id === 'deleteBadge') {
            if (!selectedBadge) return;
            const entryId = selectedBadge.dataset.entryId;
            if (!entryId) return;

            if (!confirm('Are you sure you want to delete this entry?')) return;

            const del = await safeFetchJSON('delete_timeoff.php', {
                method: 'POST',
                credentials: 'same-origin',
                headers: {'Content-Type':'application/json'},
                body: JSON.stringify({ entry_id: entryId })
            });

            if (del.ok && del.data?.success) {
                renderCell(selectedBadge.parentElement, null, null);
            } else {
                alert('Failed to delete entry.');
            }
        }

        // 2️⃣ Edit or 3️⃣ Add time off
        else if ((menuType === 'edit' || menuType === 'add') && e.target.id === 'editAddTimeOff') {
            const td = selectedCell;
            const userId = td.dataset.userId;
            const weekStart = td.dataset.weekStart;

            // fetch existing entry ID if editing
            let timeOffDiv = td.querySelector('.timeoff-corner');
            let entryId = timeOffDiv ? timeOffDiv.dataset.entryId : null;

            // input element
            td.innerHTML = '';
            const input = document.createElement('input');
            input.type = 'text';
            input.value = timeOffDiv ? timeOffDiv.textContent : '';
            input.className = 'form-control form-control-sm';
            input.style.width = '100%';
            td.appendChild(input);
            input.focus();
            contextMenu.style.display = 'none';

            input.addEventListener('keydown', async ev => {
                if (ev.key !== 'Enter') return;
                const val = input.value.trim();
                if (!val) return;

                try {
                    // If editing, update
                    if (entryId) {
                        const update = await safeFetchJSON('update_timeoff_new.php', {
                            method: 'POST',
                            credentials: 'same-origin',
                            headers: {'Content-Type':'application/json'},
                            body: JSON.stringify({ entry_id: entryId, assigned_hours: val })
                        });
                        if (update.ok && update.data?.success) {
                            renderCell(td, val, entryId);
                        } else {
                            alert('Failed to update time off.');
                            renderCell(td, input.value, entryId);
                        }
                    } 
                    // If adding, create new
                    else {
                        const add = await safeFetchJSON('add_timeoff_new.php', {
                            method: 'POST',
                            credentials: 'same-origin',
                            headers: {'Content-Type':'application/json'},
                            body: JSON.stringify({ user_id: userId, week_start: weekStart, assigned_hours: val, is_timeoff: 1 })
                        });
                        if (add.ok && add.data?.success && add.data.entry_id) {
                            renderCell(td, val, add.data.entry_id);
                        } else {
                            alert('Failed to add time off.');
                            renderCell(td, null, null);
                        }
                    }
                } catch (err) {
                    console.error('Network error:', err);
                    alert('Network error while saving time off.');
                    renderCell(td, timeOffDiv ? timeOffDiv.textContent : null, entryId);
                }
            });
        }

        selectedCell = null;
        selectedBadge = null;
        menuType = null;
    });
})();
