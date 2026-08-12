document.addEventListener('DOMContentLoaded', () => {
    const addEngagementModal = document.getElementById('addEngagementModal');
    const clientButtons = document.querySelectorAll('[data-bs-target="#addEngagementModal"]');
    const addForm = document.getElementById('addEngagementForm');

    // Populate client info when modal opens
    clientButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            const clientId = btn.getAttribute('data-client-id');
            const clientName = btn.getAttribute('data-client-name');

            document.getElementById('modal_client_id').value = clientId;
            document.getElementById('modal_client_name').value = clientName;
        });
    });

    // TSC only applies to SOC 2 - show that section only when a SOC 2
    // audit type checkbox is checked.
    const auditTypeList = document.getElementById('add_eng_audit_types');
    const tscWrap = document.getElementById('add_eng_tsc_wrap');
    function syncTscVisibility() {
        const soc2Checked = Array.from(auditTypeList.querySelectorAll('input[name="audit_type_ids[]"]'))
            .some(cb => cb.checked && cb.dataset.auditTypeName === 'SOC 2');
        tscWrap.classList.toggle('d-none', !soc2Checked);
    }
    auditTypeList?.addEventListener('change', syncTscVisibility);
    // Modal stays in the DOM across opens (no page reload unless a save
    // happened) - reset just the audit-type/TSC checkboxes on reopen so a
    // cancelled-without-saving attempt doesn't leak into the next one.
    // Deliberately not a full form.reset() - that would also clear
    // modal_client_id/modal_client_name, which the client-button click
    // listener above sets, and event ordering between that and
    // show.bs.modal isn't guaranteed.
    addEngagementModal.addEventListener('show.bs.modal', () => {
        auditTypeList?.querySelectorAll('input[type="checkbox"]').forEach(cb => { cb.checked = false; });
        tscWrap?.querySelectorAll('input[type="checkbox"]').forEach(cb => { cb.checked = cb.value === 'Security'; });
        syncTscVisibility();
    });

    // Handle form submission via AJAX
    addForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(addForm);

        try {
            const response = await fetch('add_engagement.php', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();

            if (result.success) {
                addEngagementModal.querySelector('.btn-close').click();
                location.reload(); // Refresh to update list
            } else {
                alert('Error: ' + result.message);
            }
        } catch (error) {
            alert('Error adding engagement: ' + error.message);
        }
    });
});
