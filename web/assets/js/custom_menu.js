// custom_menu.js (time off addition)
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
    let activeOverlay = null;

    function closeActiveInput() {
        if (activeOverlay) {
            activeOverlay.remove();
            activeOverlay = null;
        }
    }

    document.addEventListener('contextmenu', e => {
        contextMenu.style.display = 'none';
        selectedBadge = null;
        selectedCell = null;
        closeActiveInput();

        // Right-click on a badge
        if (e.target.classList.contains('draggable-badge')) {
            e.preventDefault();
            selectedBadge = e.target;
            contextMenu.querySelector('li').textContent = 'Delete Entry';
            contextMenu.style.top = `${e.pageY}px`;
            contextMenu.style.left = `${e.pageX}px`;
            contextMenu.style.display = 'block';
        } 
        // Right-click on a cell
        else if (e.target.tagName === 'TD' && e.target.classList.contains('addable')) {
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
        if (!contextMenu.contains(e.target)) {
            contextMenu.style.display = 'none';
            selectedBadge = null;
            selectedCell = null;
            closeActiveInput();
        }
    });

    contextMenu.addEventListener('click', async e => {
        const menuItem = e.target;
        if (!menuItem.id) return;

        // DELETE BADGE
        if (menuItem.id === 'deleteBadge' && selectedBadge) {
            const entryId = selectedBadge.dataset.entryId;
            const parentCell = selectedBadge.parentElement;
            try {
                const resp = await fetch('delete_entry_new.php', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {'Content-Type': 'application/json', 'Accept': 'application/json'},
                    body: JSON.stringify({ entry_id: entryId })
                });
                const data = await resp.json();
                if (resp.ok && data.success) {
                    selectedBadge.remove();
                    selectedBadge = null;
                    if (!parentCell.querySelector('.draggable-badge') && !parentCell.querySelector('.bi-plus')) {
                        const plus = document.createElement('i');
                        plus.className = 'bi bi-plus text-muted';
                        parentCell.appendChild(plus);
                    }
                } else alert('Failed to delete entry: ' + (data.error || 'Server error'));
            } catch (err) { console.error(err); alert('Network error while deleting entry.'); }
        }

        // ADD / EDIT TIME OFF (inline input)
        else if (menuItem.id === 'deleteBadge' && selectedCell) {
            closeActiveInput();
            const td = selectedCell;
            const userId = td.dataset.userId;
            const weekStart = td.dataset.weekStart;
            let timeOff = td.querySelector('.timeoff-corner');
            const currentVal = timeOff ? timeOff.textContent : '';

            // Overlay input
            const rect = td.getBoundingClientRect();
            const overlay = document.createElement('div');
            Object.assign(overlay.style, {
                position: 'absolute',
                top: rect.top + window.scrollY + 'px',
                left: rect.left + window.scrollX + 'px',
                width: rect.width + 'px',
                minHeight: '30px',
                background: 'rgba(255,255,255,0.95)',
                border: '1px solid #ccc',
                borderRadius: '4px',
                padding: '2px',
                zIndex: '10000',
                display: 'flex'
            });
            const input = document.createElement('input');
            input.type = 'text';
            input.value = currentVal;
            input.className = 'form-control form-control-sm';
            input.style.width = '100%';
            overlay.appendChild(input);
            document.body.appendChild(overlay);
            activeOverlay = overlay;
            input.focus();

            // Save on Enter
            input.addEventListener('keydown', async ev => {
                if (ev.key === 'Enter') {
                    const val = input.value.trim();
                    if (!val) return;

                    try {
                        if (timeOff) {
                            const resp = await fetch('update_timeoff_new.php', {
                                method: 'POST',
                                credentials: 'same-origin',
                                headers: {'Content-Type':'application/json','Accept':'application/json'},
                                body: JSON.stringify({ entry_id: timeOff.dataset.entryId, timeoff_note: val })
                            });
                            const data = await resp.json();
                            if (resp.ok && data.success) timeOff.textContent = val;
                            else alert('Failed to update time off: ' + (data.error || 'Server error'));
                        } else {
                            const resp = await fetch('add_timeoff_new.php', {
                                method: 'POST',
                                credentials: 'same-origin',
                                headers: {'Content-Type':'application/json','Accept':'application/json'},
                                body: JSON.stringify({ user_id: userId, week_start: weekStart, timeoff_note: val, is_timeoff: 1 })
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
                            } else alert('Failed to add time off: ' + (data.error || 'Server error'));
                        }
                    } catch (err) { console.error(err); alert('Network error while saving time off.'); }

                    closeActiveInput();
                }
            });

            // Click outside closes input
            document.addEventListener('click', function clickOutside(ev) {
                if (activeOverlay && !td.contains(ev.target) && ev.target !== input) {
                    closeActiveInput();
                    if (timeOff) timeOff.style.display = '';
                    document.removeEventListener('click', clickOutside);
                }
            });
        }

        contextMenu.style.display = 'none';
    });

})();
