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

    // Show context menu on right-click
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

    // Click anywhere hides menu
    document.addEventListener('click', function(e) {
        if (!contextMenu.contains(e.target)) {
            contextMenu.style.display = 'none';
        }
    });

    // Use event delegation for the delete button
    contextMenu.addEventListener('click', async function(e) {
        if (e.target.id === 'deleteBadge' && selectedBadge) {
            // if (!confirm('Are you sure you want to delete this entry?')) return;

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
                    // Remove the badge
                    selectedBadge.remove();
                    selectedBadge = null;

                    // Check if there are any other badges left
                    const hasOtherBadges = parentCell.querySelector('.draggable-badge');

                    // Only add plus icon if no other badges exist
                    if (!hasOtherBadges) {
                        // Check if a plus icon already exists
                        if (!parentCell.querySelector('.bi-plus')) {
                            const plusIcon = document.createElement('i');
                            plusIcon.className = 'bi bi-plus text-muted';
                            parentCell.appendChild(plusIcon);
                        }
                    }
                } else {
                    alert('Failed to delete entry: ' + (data.error || 'Server error'));
                }
            } catch (err) {
                console.error(err);
                alert('Network error while deleting entry.');
            }

            contextMenu.style.display = 'none';
        }
    });

})();
