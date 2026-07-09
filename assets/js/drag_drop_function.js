(function () {
    if (!IS_ADMIN) return;

    document.addEventListener('DOMContentLoaded', function () {
        let draggedElem = null;
        let originCell = null;

        function setupBadges() {
            document.querySelectorAll('.draggable-badge').forEach(badge => {
                badge.removeEventListener('dragstart', onDragStart);
                badge.removeEventListener('dragend', onDragEnd);
                badge.addEventListener('dragstart', onDragStart);
                badge.addEventListener('dragend', onDragEnd);
            });
        }

        function setupDropTargets() {
            document.querySelectorAll('td.addable').forEach(td => {
                td.removeEventListener('dragover', onDragOver);
                td.removeEventListener('dragenter', onDragEnter);
                td.removeEventListener('dragleave', onDragLeave);
                td.removeEventListener('drop', onDrop);

                td.addEventListener('dragover', onDragOver);
                td.addEventListener('dragenter', onDragEnter);
                td.addEventListener('dragleave', onDragLeave);
                td.addEventListener('drop', onDrop);
            });
        }

        function onDragStart(e) {
            draggedElem = this;
            originCell = this.closest('td');
            e.dataTransfer.setData('text/plain', this.dataset.entryId);
            try { e.dataTransfer.setDragImage(this, 10, 10); } catch {}
            this.classList.add('dragging');
        }

        function onDragEnd() {
            if (draggedElem) draggedElem.classList.remove('dragging');
            removeDropTargetHints();
            draggedElem = null;
            originCell = null;
        }

        function onDragOver(e) { e.preventDefault(); e.dataTransfer.dropEffect = 'move'; this.classList.add('drop-target'); }
        function onDragEnter(e) { e.preventDefault(); this.classList.add('drop-target'); }
        function onDragLeave() { this.classList.remove('drop-target'); }
        function removeDropTargetHints() {
            document.querySelectorAll('td.addable.drop-target').forEach(td => td.classList.remove('drop-target'));
        }

        function updatePlusIcon(cell) {
            cell.querySelectorAll('.bi-plus').forEach(icon => icon.remove());
            if (!cell.querySelector('.draggable-badge')) {
                const plus = document.createElement('i');
                plus.className = 'bi bi-plus';
                plus.style.cursor = 'pointer';
                cell.appendChild(plus);
            }
        }

        function notifyDragError(title, text) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({ icon: 'error', title, text });
            } else {
                alert(`${title}: ${text}`);
            }
        }

        function showUndoToast(message, onUndo, onSettle) {
            if (typeof Swal === 'undefined') {
                onSettle();
                return;
            }
            let undoRequested = false;
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
                        btn.addEventListener('click', async () => {
                            undoRequested = true;
                            Swal.close();
                            await onUndo();
                            onSettle();
                        });
                    }
                },
                willClose: () => {
                    if (!undoRequested) onSettle();
                }
            });
        }

        async function onDrop(e) {
    e.preventDefault();
    removeDropTargetHints();
    const targetTd = this;
    const targetUserId = targetTd.dataset.userId;
    const targetWeekStart = targetTd.dataset.weekStart;

    const entryId = e.dataTransfer.getData('text/plain');
    if (!entryId || !draggedElem) return;
    if (originCell.dataset.userId === targetUserId && originCell.dataset.weekStart === targetWeekStart) return;

    const movedElem = draggedElem;
    const sourceCell = originCell;

    function revertMove() {
        movedElem.remove();
        updatePlusIcon(targetTd);
        sourceCell.appendChild(movedElem);
        updatePlusIcon(sourceCell);
    }

    // Remove badge from origin
    movedElem.remove();
    updatePlusIcon(sourceCell);

    // Add badge to target
    targetTd.appendChild(movedElem);
    movedElem.style.pointerEvents = '';
    movedElem.classList.remove('dragging');
    updatePlusIcon(targetTd);

    // Send update to server
    try {
        const resp = await fetch('update_entry_position.php', {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                entry_id: entryId,
                target_user_id: targetUserId,
                target_week_start: targetWeekStart
            })
        });

        const data = await resp.json().catch(() => null);

        if (!resp.ok || !data || !data.success) {
            revertMove();
            notifyDragError('Could not move entry', (data && data.error) || 'Please try again.');
            return;
        }

        // Save scroll position for when we reload
        const container = document.querySelector('.sheet-container');
        if (container) {
            sessionStorage.setItem('scheduleScrollLeft', container.scrollLeft);
            sessionStorage.setItem('scheduleScrollTop', container.scrollTop);
        }

        const originUserId = sourceCell.dataset.userId;
        const originWeekStart = sourceCell.dataset.weekStart;

        showUndoToast('Entry moved', async () => {
            try {
                const undoResp = await fetch('update_entry_position.php', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        entry_id: entryId,
                        target_user_id: originUserId,
                        target_week_start: originWeekStart
                    })
                });
                const undoData = await undoResp.json().catch(() => null);
                if (!undoResp.ok || !undoData || !undoData.success) {
                    notifyDragError('Could not undo move', (undoData && undoData.error) || 'Please try again.');
                }
            } catch (err) {
                console.error('Failed to undo move', err);
                notifyDragError('Network error', 'Could not undo move.');
            }
        }, () => {
            location.reload();
        });

    } catch (err) {
        console.error('Failed to update server', err);
        revertMove();
        notifyDragError('Network error', 'Could not move entry. Please try again.');
    }
}

        setupBadges();
        setupDropTargets();
        document.querySelectorAll('td.addable').forEach(cell => updatePlusIcon(cell));

        const table = document.querySelector('.sheet-container');
        if (table) {
            new MutationObserver(mutations => {
                mutations.forEach(m => {
                    m.addedNodes.forEach(node => {
                        if (node.nodeType === 1 && node.classList.contains('draggable-badge')) setupBadges();
                    });
                });
            }).observe(table, { childList: true, subtree: true });
        }
    });
})();
