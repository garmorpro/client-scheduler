// custom_menu.js
(function() {
    if (!IS_ADMIN) return; // only for admins

    const isDark = document.body.classList.contains('dark-mode');

const contextMenu = document.createElement('div');
contextMenu.id = 'badgeContextMenu';
contextMenu.style.cssText = `
    position: absolute;
    display: none;
    z-index: 9999;
    background: ${isDark ? '#2a2a3d' : '#fff'};
    border: ${isDark ? '1px solid #3a3a50' : '1px solid #ccc'};
    margin-top: 15px;
    border-radius: 4px;
    box-shadow: 0 2px 6px rgba(0,0,0,0.2);
`;
    contextMenu.innerHTML = `
        <ul style="list-style:none; margin:0; padding:5px 0; cursor: pointer;">
            <li id="deleteBadge" style="padding:5px 15px; cursor:pointer;">Delete Entry</li>
        </ul>
    `;
    document.body.appendChild(contextMenu);

    let selectedBadge = null;

    function notifyError(title, text) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({ icon: 'error', title, text });
        } else {
            alert(`${title}: ${text}`);
        }
    }

    function showUndoToast(message, onUndo) {
        if (typeof Swal === 'undefined') return;
        Swal.fire({
            toast: true,
            position: 'bottom-end',
            icon: 'success',
            title: message,
            html: '<button type="button" id="undoActionBtn" style="margin-top:6px;padding:4px 12px;border-radius:6px;border:1px solid currentColor;background:transparent;color:inherit;cursor:pointer;font-size:12px;font-weight:600;">Undo</button>',
            showConfirmButton: false,
            showCloseButton: true,
            timer: 6000,
            timerProgressBar: true,
            didOpen: (toastEl) => {
                const btn = toastEl.querySelector('#undoActionBtn');
                if (btn) {
                    btn.addEventListener('click', () => {
                        Swal.close();
                        onUndo();
                    });
                }
            }
        });
    }

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
            contextMenu.style.display = 'none';

            const badgeToDelete = selectedBadge;
            selectedBadge = null;
            const badgeLabel = badgeToDelete.textContent.trim();

            const confirmResult = typeof Swal !== 'undefined'
                ? await Swal.fire({
                    icon: 'warning',
                    title: 'Delete this entry?',
                    text: `This removes "${badgeLabel}" from the schedule.`,
                    showCancelButton: true,
                    confirmButtonText: 'Delete',
                    confirmButtonColor: '#dc3545',
                })
                : { isConfirmed: confirm(`Delete "${badgeLabel}"?`) };

            if (!confirmResult.isConfirmed) return;

            const entryId = badgeToDelete.dataset.entryId;
            const parentCell = badgeToDelete.parentElement;

            // Capture what's needed to recreate the entry if the user hits Undo
            const undoInfo = {
                user_id: badgeToDelete.dataset.userId,
                week_start: badgeToDelete.dataset.weekStart,
                client_name: badgeToDelete.dataset.clientName || badgeLabel.replace(/\s*\([\d.]+\)$/, ''),
                assigned_hours: (badgeLabel.match(/\(([\d.]+)\)$/) || [])[1],
            };

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
                    badgeToDelete.remove();

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

                    showUndoToast('Entry deleted', async () => {
                        if (!undoInfo.user_id || !undoInfo.week_start || !undoInfo.client_name || !undoInfo.assigned_hours) {
                            notifyError('Could not undo delete', 'Missing entry details. Please re-add it manually.');
                            return;
                        }
                        try {
                            const undoResp = await fetch('add_entry_new.php', {
                                method: 'POST',
                                credentials: 'same-origin',
                                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                                body: JSON.stringify(undoInfo)
                            });
                            const undoData = await undoResp.json();
                            if (undoResp.ok && undoData.success) {
                                location.reload();
                            } else {
                                notifyError('Could not undo delete', undoData.error || 'Please re-add the entry manually.');
                            }
                        } catch (err) {
                            console.error(err);
                            notifyError('Network error', 'Could not undo delete.');
                        }
                    });
                } else {
                    notifyError('Failed to delete entry', data.error || 'Server error');
                }
            } catch (err) {
                console.error(err);
                notifyError('Network error', 'Could not delete entry. Please try again.');
            }
        }
    });

})();
