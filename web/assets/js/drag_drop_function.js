(function () {
        // Only enable DnD if admin
        if (!IS_ADMIN) return;

        document.addEventListener('DOMContentLoaded', function () {
          let draggedElem = null;
          let draggedEntryId = null;
          let originCell = null;

          // make badges draggable (already have draggable attr if admin)
          function setupBadges() {
            document.querySelectorAll('.draggable-badge').forEach(badge => {
              // ensure we don't double-bind
              badge.removeEventListener('dragstart', onDragStart);
              badge.removeEventListener('dragend', onDragEnd);

              badge.addEventListener('dragstart', onDragStart);
              badge.addEventListener('dragend', onDragEnd);
            });
          }

          // make td.addable drop targets
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
            draggedEntryId = this.dataset.entryId || this.getAttribute('data-entry-id');
            originCell = this.closest('td');

            e.dataTransfer.setData('text/plain', draggedEntryId);
            try { e.dataTransfer.setDragImage(this, 10, 10); } catch (err) {}
            this.classList.add('dragging');
          }

          function onDragEnd(e) {
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

          function onDragLeave(e) {
            this.classList.remove('drop-target');
          }

          function removeDropTargetHints() {
            document.querySelectorAll('td.addable.drop-target').forEach(td => td.classList.remove('drop-target'));
          }

          async function onDrop(e) {
            e.preventDefault();
            removeDropTargetHints();

            const targetTd = this;
            const targetUserId = targetTd.getAttribute('data-user-id');
            const targetWeekStart = targetTd.getAttribute('data-week-start');

            const entryId = e.dataTransfer.getData('text/plain') || draggedEntryId;
            if (!entryId) return;

            // If dropped into same cell: do nothing
            if (originCell && originCell.getAttribute('data-user-id') === targetUserId && originCell.getAttribute('data-week-start') === targetWeekStart) {
              // nothing to do
              return;
            }

            // Find the badge element by id or data-entry-id
            let badge = document.querySelector('#badge-entry-' + entryId) || document.querySelector('[data-entry-id="'+entryId+'"]');
            if (!badge) {
              alert('Badge element not found for entry ' + entryId);
              return;
            }

            // Disable interactions while moving
            badge.style.pointerEvents = 'none';
            badge.classList.add('dragging');

            // Show a subtle loading indicator
            const loadingDot = document.createElement('span');
            loadingDot.className = 'ms-1 text-muted';
            loadingDot.innerText = '...';
            targetTd.appendChild(loadingDot);

            // Send AJAX to server
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
                console.error('Server error', data);
                alert('Failed to move entry: ' + (data.error || 'Server error'));
                // restore
                badge.style.pointerEvents = '';
                badge.classList.remove('dragging');
                if (loadingDot && loadingDot.parentNode) loadingDot.parentNode.removeChild(loadingDot);
                return;
              }

              // Move element in DOM
              // Remove placeholder '+' if present and cell only contains the placeholder
              // Append badge and a <br> after it
              if (targetTd.querySelector('.text-muted') && targetTd.querySelectorAll('span, .badge').length === 1) {
    targetTd.querySelector('.text-muted').remove();
}

// Append the badge (move node)
targetTd.appendChild(badge);
targetTd.appendChild(document.createElement('br'));

// Update the badge's dataset to reflect new location
badge.setAttribute('data-user-id', targetUserId);
badge.setAttribute('data-week-start', targetWeekStart);

// Check if origin cell is now empty (no badges, no timeoff-corner)
if (originCell && originCell !== targetTd) {
    const hasBadges = originCell.querySelector('.draggable-badge');
    const hasTimeoff = originCell.querySelector('.timeoff-corner');
    if (!hasBadges && !hasTimeoff) {
        const plusSpan = document.createElement('span');
        plusSpan.className = 'text-muted';
        plusSpan.innerText = '+';
        originCell.appendChild(plusSpan);
    }
}

// restore visuals
badge.style.pointerEvents = '';
badge.classList.remove('dragging');

// Remove loading dot
if (loadingDot && loadingDot.parentNode) loadingDot.parentNode.removeChild(loadingDot);

            } catch (err) {
              console.error(err);
              alert('Network error while moving entry.');
              badge.style.pointerEvents = '';
              badge.classList.remove('dragging');
              if (loadingDot && loadingDot.parentNode) loadingDot.parentNode.removeChild(loadingDot);
            }
          }

          // Initial setup
          setupBadges();
          setupDropTargets();

          // If your page can change badges dynamically (e.g. modals add new entries),
          // re-run setupBadges()/setupDropTargets() after DOM updates.
          // Example: observe table for new badges
          const tableObserver = new MutationObserver(function () {
            setupBadges();
            setupDropTargets();
          });
          const table = document.querySelector('.table-responsive');
          if (table) {
            tableObserver.observe(table, { childList: true, subtree: true });
          }
        });
      })();