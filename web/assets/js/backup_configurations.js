// Show/hide cloud storage options based on storageLocation select
  function updateCloudStorageVisibility() {
    const storageSelect = document.getElementById('storageLocation');
    const cloudSettings = document.getElementById('cloudStorageSettings');
    if (storageSelect.value === 'cloud') {
      cloudSettings.style.display = 'block';
    } else {
      cloudSettings.style.display = 'none';
    }
  }

  // Show Backup Configuration Modal on configure button click
  document.getElementById('configureBackupBtn').addEventListener('click', function(e) {
    e.preventDefault();
    const modalEl = document.getElementById('backupConfigModal');
    const modal = new bootstrap.Modal(modalEl);
    modal.show();
  });

  document.getElementById('storageLocation').addEventListener('change', updateCloudStorageVisibility);

  // Initialize visibility on page load
  updateCloudStorageVisibility();

  // Run Test Backup button handler (example)
  document.getElementById('runTestBackupBtn').addEventListener('click', async () => {
    const btn = document.getElementById('runTestBackupBtn');
    btn.disabled = true;
    btn.textContent = 'Running Test Backup...';

    try {
      const resp = await fetch('/api/run_test_backup.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({})
      });
      const result = await resp.json();

      if (result.success) {
        alert('Test backup ran successfully!');
      } else {
        alert('Test backup failed: ' + (result.error || 'Unknown error'));
      }
    } catch (err) {
      alert('Network error: ' + err.message);
    }

    btn.disabled = false;
    btn.textContent = 'Run Test Backup';
  });

  // AJAX form submission for backup settings
  document.getElementById('backupConfigForm').addEventListener('submit', async (e) => {
    e.preventDefault();

    const formData = new FormData(e.target);

    // Convert FormData to plain object
    const data = {};
    formData.forEach((value, key) => {
      // For unchecked checkboxes, no value submitted; we want to record 'false'
      if (data[key] === undefined) {
        data[key] = value;
      }
    });

    // Handle unchecked checkboxes: ensure keys exist and are 'false'
    ['enable_automated_backups', 'backup_users', 'backup_engagements', 'backup_entries', 'backup_settings'].forEach(key => {
      if (!formData.has(key)) {
        data[key] = 'false';
      }
    });

    const payload = {
      setting_master_key: 'backup',
      settings: data
    };

    try {
      const resp = await fetch('settings_backend.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(payload)
      });
      const result = await resp.json();

      if (result.success) {
        // Hide modal on success
        const modalEl = document.getElementById('backupConfigModal');
        const modalInstance = bootstrap.Modal.getInstance(modalEl);
        modalInstance.hide();
        // alert('Backup settings saved successfully!');
      } else {
        alert('Failed to save settings: ' + (result.error || 'Unknown error'));
      }
    } catch (err) {
      alert('Network error: ' + err.message);
    }
  });