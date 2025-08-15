// delete_entry.js
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
        margin-top: 15px;
        box-shadow:0 2px 6px rgba(0,0,0,0.2);
    `;
    contextMenu.innerHTML = `
        <ul style="list-style:none; margin:0; padding:5px 0;">
            <li id="deleteBadge" style="padding:5px 15px; cursor:pointer;">Delete Entry</li>
        </ul>
    `;
    document.body.appendChild(contextMenu);

    let selectedBadge = null;

    // Right-click on badge
    document.addEventListener('contextmenu', function(e) {
        if (e.target.classList.contains('draggable-badge')) {
            e.preventDefault();
            selectedBadge = e.target;

            contextMenu.style.top = `${e.pageY}px`;
            contextMenu.style.left = `${e.pageX}px`;
            contextMenu.style.display = 'block';
        } else {
            contextMenu.style.display = 'none';
        }
    });

    // Click elsewhere hides menu
    document.addEventListener('click', function() {
        contextMenu.style.display = 'none';
    });

    // Delete action
    document.getElementById('deleteBadge').addEventListener('click', async function() {
        if (!selectedBadge) return;

        const entryId = selectedBadge.dataset.entryId;

        if (!confirm('Are you sure you want to delete this entry?')) return;

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
            } else {
                alert('Failed to delete entry: ' + (data.error || 'Server error'));
            }
        } catch (err) {
            console.error(err);
            alert('Network error while deleting entry.');
        }

        contextMenu.style.display = 'none';
    });
})();
