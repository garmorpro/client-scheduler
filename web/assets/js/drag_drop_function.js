(function () {
    if (!IS_ADMIN) return;

    document.addEventListener('DOMContentLoaded', function () {
        let draggedElem = null;
        let draggedEntryId = null;
        let originCell = null;

        // ------------------------------
        // Setup draggable badges
        // ------------------------------
        function setupBadges() {
            document.querySelectorAll('.draggable-badge').forEach(badge => {
                badge.removeEventListener('dragstart', onDragStart);
                badge.removeEventListener('dragend', onDragEnd);

                badge.addEventListener('dragstart', onDragStart);
                badge.addEventListener('dragend', onDragEnd);
            });
        }

        // ------------------------------
        // Setup drop targets
        // ------------------------------
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

        // ------------------------------
        // Drag events
        // ------------------------------
        function onDragStart(e) {
            draggedElem = this;
            draggedEntryId = this.dataset.entryId;
            originCell = this.closest('td');

            e.dataTransfer.setData('text/plain', draggedEntryId);
            try { e.dataTransfer.setDragImage(this, 10, 10); } catch (err) {}
            this.classList.add('dragging');
        }

        function onDragEnd() {
            if (draggedElem) draggedElem.classList.remove('dragging');
            removeDropTargetHints();
            draggedElem = null;
            draggedEntryId = null;
            originCell = null;
        }

        function onDragOver(e) {
            e.preventDefault();
            e.dataTransfer.dropEffect = 'move';
            this.classList.add('drop-target');
        }

        function onDragEnter(e) {
            e.preventDefault();
            this.classList.add('drop-target');
        }

        function onDragLeave() {
            this.classList.remove('drop-target');
        }

        function removeDropTargetHints() {
            document.querySelectorAll('td.addable.drop-target').forEach(td => td.classList.remove('drop-target'));
        }

        // ------------------------------
        // Update plus icon in a cell
        // ------------------------------
        function updatePlusIcon(cell) {
            cell.querySelectorAll('.bi-plus').forEach(icon => icon.remove());
            if (!cell.querySelector('.draggable-badge')) {
                const plusIcon = document.createElement('i');
                plusIcon.className = 'bi bi-plus text-muted';
                plusIcon.style.cursor = 'pointer';
                cell.appendChild(plusIcon);
            }
        }

        // ------------------------------
        // Refresh the table (or just the tbody)
        // ------------------------------
        async function refreshTable() {
            try {
                const resp = await fetch('fetch_schedule_table.php', {
                    method: 'GET',
                    credentials: 'same-origin'
                });
                const html = await resp.text();

                const tableContainer = document.querySelector('.table-responsive');
                if (tableContainer) {
                    tableContainer.innerHTML = html;
                    setupBadges();
                    setupDropTargets();
                    document.querySelectorAll('td.addable').forEach(cell => updatePlusIcon(cell));
                }
            } catch (err) {
                console.error('Error refreshing table:', err);
            }
        }

        // ------------------------------
        // Handle badge drop
        // ------------------------------
        async function onDrop(e) {
            e.preventDefault();
            removeDropTargetHints();

            const targetTd = this;
            const targetUserId = targetTd.dataset.userId;
            const targetWeekStart = targetTd.dataset.weekStart;

            const entryId = e.dataTransfer.getData('text/plain') || draggedEntryId;
            if (!entryId) return;

            if (originCell &&
                originCell.dataset.userId === targetUserId &&
                originCell.dataset.weekStart === targetWeekStart) {
                return;
            }

            const badge = document.querySelector('#badge-entry-' + entryId);
            if (!badge) return;

            badge.style.pointerEvents = 'none';
            badge.classList.add('dragging');

            const loadingDot = document.createElement('span');
            loadingDot.className = 'ms-1 text-muted';
            loadingDot.innerText = '...';
            targetTd.appendChild(loadingDot);

            try {
                const resp = await fetch('update_entry_position.php', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        entry_id: entryId,
                        target_user_id: targetUserId,
                        target_week_start: targetWeekStart
                    })
                });

                const data = await resp.json();
                if (!resp.ok || !data.success) {
                    alert('Failed to move entry: ' + (data.error || 'Server error'));
                    badge.style.pointerEvents = '';
                    badge.classList.remove('dragging');
                    loadingDot.remove();
                    return;
                }

                // Refresh the table to remove any ghost elements
                await refreshTable();

            } catch (err) {
                console.error(err);
                alert('Network error while moving entry.');
                badge.style.pointerEvents = '';
                badge.classList.remove('dragging');
                loadingDot.remove();
            }
        }

        // ------------------------------
        // INITIAL SETUP
        // ------------------------------
        setupBadges();
        setupDropTargets();
        document.querySelectorAll('td.addable').forEach(cell => updatePlusIcon(cell));
    });
})();
