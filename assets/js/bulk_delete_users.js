document.addEventListener('DOMContentLoaded', () => {
      const selectAllCheckbox = document.getElementById('selectAllUsers');
      const userCheckboxes = document.querySelectorAll('.selectUser');
      const bulkDeleteBtn = document.getElementById('bulkDeleteBtn');

      function updateBulkDeleteVisibility() {
        const anyChecked = Array.from(userCheckboxes).some(cb => cb.checked);
        bulkDeleteBtn.style.display = anyChecked ? 'inline-block' : 'none';
      }

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

      bulkDeleteBtn.addEventListener('click', async (e) => {
        e.preventDefault();
        const selectedIds = Array.from(userCheckboxes)
          .filter(cb => cb.checked)
          .map(cb => cb.getAttribute('data-user-id'));

        if (selectedIds.length === 0) return;

        if (!confirm(`Are you sure you want to delete ${selectedIds.length} user(s)? This action cannot be undone.`)) {
          return;
        }

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
            // alert(`Deleted ${result.deletedCount} user(s) successfully.`);
            // Optionally reload page or remove deleted rows from table
            location.reload();
          } else {
            alert('Error deleting users: ' + (result.error || 'Unknown error'));
          }
        } catch (error) {
          alert('Network or server error: ' + error.message);
        }
      });
    });