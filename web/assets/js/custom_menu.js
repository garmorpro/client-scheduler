// custom_menu.js (inline time off input with console.log)
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

    let selectedBadge = null;
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
        selectedBadge = null;
        selectedCell = null;

        if (e.target.classList.contains('draggable-badge')) {
            e.preventDefault();
            selectedBadge = e.target;
            contextMenu.querySelector('li').textContent = 'Delete Entry';
            contextMenu.style.top = `${e.pageY}px`;
            contextMenu.style.left = `${e.pageX}px`;
            contextMenu.style.display = 'block';
        } else if (e.target.tagName === 'TD' && e.target.classList.contains('addable')) {
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
            if (ev.key === 'Enter') {
                const val = input.value.trim();
                if (!val) return;

                try {
                    if (timeOff) {
                        console.log('Updating time off:', {
                            entry_id: timeOff.dataset.entryId,
                            assigned_hours: val
                        });

                        const resp = await fetch('update_timeoff_new.php', {
                            method: 'POST',
                            credentials: 'same-origin',
                            headers: {'Content-Type':'application/json','Accept':'application/json'},
                            body: JSON.stringify({ entry_id: timeOff.dataset.entryId, assigned_hours: val })
                        });
                        const data = await resp.json();
                        if (resp.ok && data.success) {
                            timeOff.textContent = val;
                        } else {
                            alert('Failed to update time off: ' + (data.error || 'Server error'));
                        }
                    } else {
                        console.log('Adding time off:', {
                            user_id: userId,
                            week_start: weekStart,
                            assigned_hours: val,
                            is_timeoff: 1
                        });

                        const resp = await fetch('add_timeoff_new.php', {
                            method: 'POST',
                            credentials: 'same-origin',
                            headers: {'Content-Type':'application/json','Accept':'application/json'},
                            body: JSON.stringify({ user_id: userId, week_start: weekStart, assigned_hours: val, is_timeoff: 1 })
                        });
                        const data = await resp.json();
                        if (resp.ok && data.success) {
                            const div = document.createElement('div');
                            div.className = 'timeoff-corner';
                            div.dataset.entryId = data.entry_id;
                            div.style.fontSize = '0.8em';
                            div.style.color = '#555';
                            div.textContent = val;
                            td.appendChild(div);
                        } else {
                            alert('Failed to add time off: ' + (data.error || 'Server error'));
                        }
                    }
                } catch (err) {
                    console.error('Network error while saving time off:', err);
                    alert('Network error while saving time off.');
                }

                closeActiveInput(td);
            }
        });
    });
})();
