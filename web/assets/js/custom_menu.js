// custom_menu.js
(function() {
    if (!IS_ADMIN) return; // only for admins

    const contextMenu = document.createElement('div');
    contextMenu.id = 'badgeContextMenu';
    contextMenu.style.cssText = `
        position:absolute;
        display:none;
        z-index:9999;
        background:#fff;
        border:1px solid #ccc;
        border-radius:4px;
        box-shadow:0 2px 6px rgba(0,0,0,0.2);
    `;
    contextMenu.innerHTML = `
        <ul style="list-style:none; margin:0; padding:5px 0;">
            <li id="manageTimeoff" style="padding:5px 15px; cursor:pointer;">Manage Timeoff</li>
            <li id="editEntry" style="padding:5px 15px; cursor:pointer;">Edit Entry</li>
            <li id="deleteBadge" style="padding:5px 15px; cursor:pointer;">Delete Entry</li>
        </ul>
    `;
    document.body.appendChild(contextMenu);

    let selectedBadge = null;

    // Show context menu
    document.addEventListener('contextmenu', function(e) {
        if (e.target.classList.contains('draggable-badge')) {
            e.preventDefault();
            selectedBadge = e.target;

            contextMenu.style.top = `${e.pageY}px`;
            contextMenu.style.left = `${e.pageX}px`;
            contextMenu.style.display = 'block';
        } else {
            contextMenu.style.display = 'none';
            selectedBadge = null;
        }
    });

    // Hide menu on click elsewhere
    document.addEventListener('click', function(e) {
        if (!contextMenu.contains(e.target)) {
            contextMenu.style.display = 'none';
        }
    });

    // Handle context menu actions
    contextMenu.addEventListener('click', async function(e) {
        if (!selectedBadge) return;

        const parentCell = selectedBadge.parentElement;

        switch (e.target.id) {
            case 'manageTimeoff':
                alert(`Manage Timeoff for Entry ID: ${selectedBadge.dataset.entryId}`);
                // TODO: Open your timeoff management modal or UI here
                break;

            case 'editEntry':
                alert(`Edit Entry ID: ${selectedBadge.dataset.entryId}`);
                // TODO: Trigger your edit modal here
                break;

            case 'deleteBadge':
                try {
                    const resp = await fetch('delete_entry_new.php', {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({ entry_id: selectedBadge.dataset.entryId })
                    });

                    const data = await resp.json();
                    if (resp.ok && data.success) {
                        selectedBadge.remove();
                        selectedBadge = null;

                        // If no more badges, keep timeoff-corner if it exists
                        const hasOtherBadges = parentCell.querySelector('.draggable-badge');
                        if (!hasOtherBadges) {
                            const timeOff = parentCell.querySelector('.timeoff-corner');
                            parentCell.innerHTML = '';
                            if (timeOff) parentCell.appendChild(timeOff);
                            parentCell.innerHTML += '<i class="bi bi-plus text-muted"></i>';
                        }
                    } else {
                        alert('Failed to delete entry: ' + (data.error || 'Server error'));
                    }
                } catch (err) {
                    console.error(err);
                    alert('Network error while deleting entry.');
                }
                break;
        }

        contextMenu.style.display = 'none';
    });

})();
