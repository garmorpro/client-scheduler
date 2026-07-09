document.addEventListener('DOMContentLoaded', () => {
    const modalEl = document.getElementById('assignManagerModal');
    const form = document.getElementById('assignManagerForm');
    const bulkBtn = document.getElementById('bulkAssignManagerBtn');
    if (!modalEl || !form || !bulkBtn) return;

    const modal = new bootstrap.Modal(modalEl);
    const userCheckboxes = document.querySelectorAll('.selectUser');
    const chipsWrap = document.getElementById('am_employee_chips');
    let selectedUsers = [];

    function refreshBulkButton() {
        const checked = Array.from(userCheckboxes).filter(cb => cb.checked);
        const eligible = checked.filter(cb => ['staff', 'senior'].includes(cb.dataset.role));

        if (checked.length > 0 && eligible.length === checked.length) {
            bulkBtn.style.display = 'inline-block';
            document.getElementById('assignSelectedCount').textContent = eligible.length;
        } else {
            bulkBtn.style.display = 'none';
        }
    }

    // Selection state changes on every checkbox click/select-all, not just at page load.
    document.getElementById('selectAllUsers')?.addEventListener('change', refreshBulkButton);
    userCheckboxes.forEach(cb => cb.addEventListener('change', refreshBulkButton));
    refreshBulkButton();

    bulkBtn.addEventListener('click', (e) => {
        e.preventDefault();
        selectedUsers = Array.from(userCheckboxes)
            .filter(cb => cb.checked)
            .map(cb => ({ id: cb.dataset.userId, name: cb.dataset.userName }));

        if (selectedUsers.length === 0) return;

        chipsWrap.innerHTML = selectedUsers.map(u => `<span class="am-chip">${u.name}</span>`).join('');
        modal.show();
    });

    modalEl.addEventListener('hidden.bs.modal', () => form.reset());

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const managerId = document.getElementById('am_manager_id').value;
        if (!managerId) return;

        try {
            const response = await fetch('assign_manager.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    user_ids: selectedUsers.map(u => u.id),
                    manager_id: managerId
                })
            });
            const result = await response.json();
            if (result.success) {
                modal.hide();
                location.reload();
            } else {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({ icon: 'error', title: 'Could not assign manager', text: result.error || 'Please try again.' });
                } else {
                    alert('Error: ' + (result.error || 'Could not assign manager.'));
                }
            }
        } catch (error) {
            console.error('Failed to assign manager', error);
        }
    });
});
