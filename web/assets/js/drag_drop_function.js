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
                plus.className = 'bi bi-plus text-muted';
                plus.style.cursor = 'pointer';
                cell.appendChild(plus);
            }
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

            // Remove badge from origin
            draggedElem.remove();
            updatePlusIcon(originCell);

            // Add badge to target
            targetTd.appendChild(draggedElem);
            draggedElem.style.pointerEvents = '';
            draggedElem.classList.remove('dragging');
            updatePlusIcon(targetTd);

            // Send update to server
            try {
                await fetch('update_entry_position.php', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        entry_id: entryId,
                        target_user_id: targetUserId,
                        target_week_start: targetWeekStart
                    })
                });
            } catch (err) {
                console.error('Failed to update server', err);
            }
        }

        setupBadges();
        setupDropTargets();
        document.querySelectorAll('td.addable').forEach(cell => updatePlusIcon(cell));

        const table = document.querySelector('.table-responsive');
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
