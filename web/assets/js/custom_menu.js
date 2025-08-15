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
        margin-top: 15px;
        border-radius:4px;
        box-shadow:0 2px 6px rgba(0,0,0,0.2);
    `;
    contextMenu.innerHTML = `
        <ul style="list-style:none; margin:0; padding:5px 0; cursor: pointer;">
            <li id="deleteBadge" style="padding:5px 15px; cursor:pointer;">Delete Entry / Time Off</li>
        </ul>
    `;
    document.body.appendChild(contextMenu);

    let selectedCell = null;

    function closeActiveInput(td) {
        if (!td) return;
        const timeOff = td.querySelector('.timeoff-corner');
        td.innerHTML = '';
        if (timeOff) td.appendChild(timeOff);

        const plus = document.createElement('i');
        plus.className = 'bi bi-plus text-muted';
        td.appendChild(plus);
        selectedCell = null;
    }

    document.addEventListener('contextmenu', e => {
        contextMenu.style.display = 'none';
        selectedCell = null;

        if (e.target.tagName === 'TD' && e.target.classList.contains('addable')) {
            e.preventDefault();
            selectedCell = e.target;
            const timeOff = selectedCell.querySelector('.timeoff-corner');
            contextMenu.querySelector('li').textContent = timeOff ? 'Edit Time Off' : 'Add Time Off';
            contextMenu.style.top = `${e.pageY}px`;
            contextMenu.style.left = `${e.pageX}px`;
            contextMenu.style.display = 'block';
        }
    });

    document.addEventListener('click', e => {
        if (!contextMenu.contains(e.target) && selectedCell) {
            closeActiveInput(selectedCell);
            contextMenu.style.display = 'none';
        }
    });

    async function safeFetchJSON(url, options) {
        try {
            const resp = await fetch(url, options);
            const text = await resp.text();
            try {
                return { ok: resp.ok, data: JSON.parse(text) };
            } catch (jsonErr) {
                console.error('Failed to parse JSON:', text);
                return { ok: resp.ok, data: null, error: 'Invalid JSON response' };
            }
        } catch (err) {
            console.error('Network error:', err);
            return { ok: false, data: null, error: err.message };
        }
    }

    contextMenu.addEventListener('click', async e => {
        if (e.target.id !== 'deleteBadge' || !selectedCell) return;

        const td = selectedCell;
        const userId = td.dataset.userId;
        const weekStart = td.dataset.weekStart;
        let timeOff = td.querySelector('.timeoff-corner');
        const currentVal = timeOff ? timeOff.textContent : '';

        td.innerHTML = '';
        if (timeOff) td.appendChild(timeOff);

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
                let entryId = timeOff ? timeOff.dataset.entryId : null;

                if (!entryId) {
                    // check for existing entry
                    const lookup = await safeFetchJSON('get_timeoff_entry.php', {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: {'Content-Type':'application/json'},
                        body: JSON.stringify({ user_id: userId, week_start: weekStart })
                    });
                    console.log('Lookup response:', lookup);
                    if (lookup.ok && lookup.data && lookup.data.success && lookup.data.entry_id) {
                        entryId = lookup.data.entry_id;
                    }
                }

                if (entryId) {
                    // update existing
                    const update = await safeFetchJSON('update_timeoff_new.php', {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: {'Content-Type':'application/json'},
                        body: JSON.stringify({ entry_id: entryId, assigned_hours: val })
                    });
                    console.log('Update response:', update);

                    if (update.ok && update.data && update.data.success) {
                        if (!timeOff) {
                            timeOff = document.createElement('div');
                            timeOff.className = 'timeoff-corner';
                            td.appendChild(timeOff);
                        }
                        timeOff.dataset.entryId = entryId;
                        timeOff.textContent = val;
                        closeActiveInput(td); // safe to close input
                    } else {
                        alert('Failed to update time off: ' + (update.data?.error || update.error || 'Server error'));
                    }
                } else {
                    // add new
                    const add = await safeFetchJSON('add_timeoff_new.php', {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: {'Content-Type':'application/json'},
                        body: JSON.stringify({ user_id: userId, week_start: weekStart, assigned_hours: val, is_timeoff: 1 })
                    });
                    console.log('Add response:', add);

                    if (add.ok && add.data && add.data.success && add.data.entry_id) {
                        const div = document.createElement('div');
                        div.className = 'timeoff-corner';
                        div.dataset.entryId = add.data.entry_id;
                        div.style.fontSize = '0.8em';
                        div.style.color = '#555';
                        div.textContent = val;
                        td.innerHTML = ''; // clear input but keep div
                        td.appendChild(div);
                        // do NOT call closeActiveInput here, div stays
                    } else {
                        alert('Failed to add time off: ' + (add.data?.error || add.error || 'Server error'));
                    }
                }

            } catch (err) {
                console.error('Network error while saving time off:', err);
                alert('Network error while saving time off.');
            }
        });
    });
})();
