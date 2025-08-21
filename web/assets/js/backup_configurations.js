// Show/hide storage options based on storageLocation select
function updateStorageVisibility() {
  const storageSelect = document.getElementById('storageLocation');
  const localSettings = document.getElementById('localStorageSettings');
  const cloudSettings = document.getElementById('cloudStorageSettings');

  if (storageSelect.value === 'local') {
    localSettings.style.display = 'block';
    cloudSettings.style.display = 'none';
  } else if (storageSelect.value === 'cloud') {
    localSettings.style.display = 'none';
    cloudSettings.style.display = 'block';
  } else {
    localSettings.style.display = 'none';
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

// Listen for storage type change
document.getElementById('storageLocation').addEventListener('change', updateStorageVisibility);

// Initialize visibility on page load
updateStorageVisibility();

// Run Test Backup button handler
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
    data[key] = value;
  });

  // Handle unchecked checkboxes: ensure keys exist and are 'false'
  ['enable_automated_backups', 'backup_users', 'backup_engagements', 'backup_entries', 'backup_settings'].forEach(key => {
    if (!formData.has(key)) {
      data[key] = 'false';
    }
  });

  // Ensure local backup directory is sent if local storage is selected
  if (data['storage_location'] === 'local' && !data['local_backup_directory']) {
    data['local_backup_directory'] = '';
  }

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
    } else {
      alert('Failed to save settings: ' + (result.error || 'Unknown error'));
    }
  } catch (err) {
    alert('Network error: ' + err.message);
  }
});
