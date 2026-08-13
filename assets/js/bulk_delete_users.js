document.addEventListener('DOMContentLoaded', () => {
      const selectAllCheckbox = document.getElementById('selectAllUsers');
      const userCheckboxes = document.querySelectorAll('.selectUser');
      const bulkDeleteBtn = document.getElementById('bulkDeleteBtn');
      if (!selectAllCheckbox || !bulkDeleteBtn) return;

      function updateBulkDeleteVisibility() {
        const checkedCheckboxes = Array.from(userCheckboxes).filter(cb => cb.checked);
        const count = checkedCheckboxes.length;
        bulkDeleteBtn.style.display = count > 0 ? 'inline-block' : 'none';

        // Update the number displayed
        const selectedCountSpan = document.getElementById('selectedCount');
        if (selectedCountSpan) {
          selectedCountSpan.textContent = count;
        }
      }

      selectAllCheckbox.addEventListener('change', () => {
        userCheckboxes.forEach(cb => cb.checked = selectAllCheckbox.checked);
        updateBulkDeleteVisibility();
      });

      userCheckboxes.forEach(cb => {
        cb.addEventListener('change', () => {
          if (!cb.checked) {
            selectAllCheckbox.checked = false;
          } else if (Array.from(userCheckboxes).every(cb => cb.checked)) {
            selectAllCheckbox.checked = true;
          }
          updateBulkDeleteVisibility();
        });
      });

      async function runBulkDelete(selectedIds) {
        try {
          const response = await fetch('bulk_delete_users.php', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json'
            },
            body: JSON.stringify({ user_ids: selectedIds })
          });
          const result = await response.json();

          if (result.success) {
            const skippedCount = (result.skippedSelf ? 1 : 0) + (result.skippedWithData ? result.skippedWithData.length : 0);
            if (skippedCount > 0 && typeof appNotify !== 'undefined') {
              const reasons = [];
              if (result.skippedSelf) reasons.push('you can\'t delete your own account');
              if (result.skippedWithData && result.skippedWithData.length) reasons.push(`${result.skippedWithData.length} user(s) have scheduled hours or time off`);
              appNotify({
                icon: 'success',
                title: `Deleted ${result.deletedCount}, skipped ${skippedCount}`,
                text: `Skipped because ${reasons.join(' and ')}.`,
                timer: 3200
              });
              setTimeout(() => location.reload(), 3200);
            } else {
              location.reload();
            }
          } else {
            if (typeof appNotify !== 'undefined') {
              appNotify({ icon: 'error', title: 'Could not delete employees', text: result.error || 'Unknown error' });
            } else {
              alert('Error deleting users: ' + (result.error || 'Unknown error'));
            }
          }
        } catch (error) {
          if (typeof appNotify !== 'undefined') {
            appNotify({ icon: 'error', title: 'Request failed', text: error.message });
          } else {
            alert('Network or server error: ' + error.message);
          }
        }
      }

      bulkDeleteBtn.addEventListener('click', (e) => {
        e.preventDefault();
        const selectedIds = Array.from(userCheckboxes)
          .filter(cb => cb.checked)
          .map(cb => cb.getAttribute('data-user-id'));

        if (selectedIds.length === 0) return;

        if (typeof appConfirm !== 'undefined') {
          appConfirm({
            icon: 'warning',
            title: `Delete ${selectedIds.length} employee${selectedIds.length === 1 ? '' : 's'}?`,
            text: 'This action cannot be undone.',
            confirmText: 'Yes, delete',
            danger: true
          }).then(confirmed => { if (confirmed) runBulkDelete(selectedIds); });
        } else if (confirm(`Are you sure you want to delete ${selectedIds.length} user(s)? This action cannot be undone.`)) {
          runBulkDelete(selectedIds);
        }
      });
    });
