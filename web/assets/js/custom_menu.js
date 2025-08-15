// custom_menu.js
(function() {
    if (!IS_ADMIN) return; // only for admins

    // Create context menu
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
            <li id="deleteBadge" style="padding:5px 15px; cursor:pointer;">Delete Entry</li>
        </ul>
    `;
    document.body.appendChild(contextMenu);

    let selectedBadge = null;
    let selectedCell = null;

    // Show context menu on right-click
    document.addEventListener('contextmenu', function(e) {
        contextMenu.style.display = 'none';
        selectedBadge = null;
        selectedCell = null;

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
            const menuItem = contextMenu.querySelector('li');

            menuItem.textContent = timeOff ? 'Edit Time Off' : 'Add Time Off';

            contextMenu.style.top = `${e.pageY}px`;
            contextMenu.style.left = `${e.pageX}px`;
            contextMenu.style.display = 'block';
        }
    });

    // Click anywhere hides menu
    document.addEventListener('click', function(e) {
        if (!contextMenu.contains(e.target)) {
            contextMenu.style.display = 'none';
            selectedBadge = null;
            selectedCell = null;
        }
    });

    // Click on menu item
    contextMenu.addEventListener('click', async function(e) {
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
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ entry_id: entryId })
                });

                const data = await resp.json();

                if (resp.ok && data.success) {
                    selectedBadge.remove();
                    selectedBadge = null;

                    // Add plus icon if no badges remain
                    if (!parentCell.querySelector('.draggable-badge') && !parentCell.querySelector('.bi-plus')) {
                        const plusIcon = document.createElement('i');
                        plusIcon.className = 'bi bi-plus text-muted';
                        parentCell.appendChild(plusIcon);
                    }
                } else {
                    alert('Failed to delete entry: ' + (data.error || 'Server error'));
                }
            } catch (err) {
                console.error(err);
                alert('Network error while deleting entry.');
            }
        } 

        // ADD / EDIT TIME OFF
        else if (menuItem.id === 'deleteBadge' && selectedCell) {
            let timeOff = selectedCell.querySelector('.timeoff-corner');
            if (timeOff) {
                // Edit existing time off
                const currentVal = timeOff.textContent;
                const newVal = prompt('Edit Time Off:', currentVal);
                if (newVal !== null) {
                    timeOff.textContent = newVal;
                }
            } else {
                // Add new time off
                const newVal = prompt('Add Time Off:', '');
                if (newVal) {
                    const div = document.createElement('div');
                    div.className = 'timeoff-corner';
                    div.style.fontSize = '0.8em';
                    div.style.color = '#555';
                    div.textContent = newVal;
                    selectedCell.appendChild(div);
                }
            }
            selectedCell = null;
        }

        contextMenu.style.display = 'none';
    });

})();
