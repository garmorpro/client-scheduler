async function loadBackupHistory() {
    const listEl = document.getElementById('backupHistoryList');
    if (!listEl) return;
    listEl.innerHTML = '<div class="settings-empty-row">Loading...</div>';

    try {
        const resp = await fetch('list_backups.php');
        const result = await resp.json();

        if (!result.success) {
            listEl.innerHTML = `<div class="settings-empty-row text-danger">${result.error || 'Could not load backups.'}</div>`;
            return;
        }
        if (!result.backups.length) {
            listEl.innerHTML = '<div class="settings-empty-row">No backups yet.</div>';
            return;
        }

        listEl.innerHTML = result.backups.map(b => `
            <div class="eng-vm-emp-row">
                <div class="eng-vm-emp-info">
                    <div class="eng-vm-emp-name">${b.created}</div>
                    <div class="eng-vm-emp-role">${b.size}</div>
                </div>
                <a href="download_backup.php?file=${encodeURIComponent(b.name)}" class="settings-icon-btn" title="Download">
                    <i class="bi bi-download"></i>
                </a>
            </div>
        `).join('');
    } catch (err) {
        listEl.innerHTML = '<div class="settings-empty-row text-danger">Network error loading backups.</div>';
    }
}

document.getElementById('configureBackupBtn').addEventListener('click', function(e) {
    e.preventDefault();
    const modalEl = document.getElementById('backupConfigModal');
    const modal = new bootstrap.Modal(modalEl);
    modal.show();
    loadBackupHistory();
});

// Run Test Backup button
document.getElementById('runTestBackupBtn').addEventListener('click', async () => {
    const btn = document.getElementById('runTestBackupBtn');
    btn.disabled = true;
    btn.textContent = 'Running Test Backup...';

    try {
        const localDir = document.getElementById('localBackupDir').value;
        if (!localDir) {
            alert('Please enter a local backup directory.');
            btn.disabled = false;
            btn.textContent = 'Run Test Backup';
            return;
        }

        const resp = await fetch('backup_test_run.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({local_backup_directory: localDir})
        });

        const result = await resp.json();
        if (result.success) {
            alert(`Backup successful:\n${result.file}\nSize: ${result.size}`);
            loadBackupHistory();
        } else {
            alert('Backup failed: ' + (result.error || 'Unknown error'));
        }
    } catch (err) {
        alert('Network error: ' + err.message);
    }

    btn.disabled = false;
    btn.textContent = 'Run Test Backup';
});

// Save Settings via AJAX
document.getElementById('backupConfigForm').addEventListener('submit', async function(e) {
    e.preventDefault();

    const masterKey = 'backup_config';
    const settings = {
        enable_automated_backups: document.getElementById('enableAutomatedBackups').checked,
        backup_frequency: document.getElementById('backupFrequency').value,
        backup_time: document.getElementById('backupTime').value,
        retention_period_days: document.getElementById('retentionPeriod').value,
        local_backup_directory: document.getElementById('localBackupDir').value
    };

    try {
        const resp = await fetch('settings_backend.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ setting_master_key: masterKey, settings })
        });

        const result = await resp.json();
        if (result.success) {
            // alert('Settings saved successfully!');
        } else {
            alert('Failed to save settings: ' + (result.error || 'Unknown error'));
        }

    } catch (err) {
        alert('Network error: ' + err.message);
    }
});