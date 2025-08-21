// Show/hide local storage input
function updateLocalStorageVisibility() {
    const storageSelect = document.getElementById('storageLocation');
    const localSettings = document.getElementById('localStorageSettings');
    if (storageSelect.value === 'local') {
        localSettings.style.display = 'block';
    } else {
        localSettings.style.display = 'none';
    }
}

document.getElementById('storageLocation').addEventListener('change', updateLocalStorageVisibility);
updateLocalStorageVisibility();

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
        } else {
            alert('Backup failed: ' + (result.error || 'Unknown error'));
        }
    } catch (err) {
        alert('Network error: ' + err.message);
    }

    btn.disabled = false;
    btn.textContent = 'Run Test Backup';
});
