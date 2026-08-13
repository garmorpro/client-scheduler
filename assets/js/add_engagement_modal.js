// Add Engagement - restyled on the Engagement Tracker reference's modal
// (see includes/modals/add_engagement_modal.php). Replaces the old
// SweetAlert2-based assets/js/swal-modals/add-engagement-modal.js: this is
// a real Bootstrap modal with named form fields, so submission is just
// `new FormData(form)` instead of manually assembling one field at a time.
document.addEventListener('DOMContentLoaded', () => {
    const addModal = document.getElementById('addEngagementModal');
    const addForm = document.getElementById('addEngagementForm');
    if (!addModal || !addForm) return;

    const addModalInstance = new bootstrap.Modal(addModal);

    const tscWrap = document.getElementById('add_eng_tsc_wrap');
    function syncTscVisibility() {
        const soc2Checked = Array.from(document.querySelectorAll('#add_eng_audit_types input[name="audit_type_ids[]"]'))
            .some(cb => cb.checked && cb.dataset.auditTypeName === 'SOC 2');
        tscWrap.classList.toggle('d-none', !soc2Checked);
    }
    document.getElementById('add_eng_audit_types')?.addEventListener('change', syncTscVisibility);

    function open({ clientId, clientName }) {
        addForm.reset();
        document.getElementById('add_eng_client_id').value = clientId;
        document.getElementById('add_eng_client_name_hidden').value = clientName;
        document.getElementById('add_eng_client_name').value = clientName;
        // .reset() re-checks any checkbox with a `checked` attribute in the
        // markup (Security, under TSC) - resync visibility to match since
        // nothing else is checked yet.
        syncTscVisibility();
        addModalInstance.show();
    }

    // Exposed for consistency with EditEngagementModal/ViewEngagementModal,
    // though nothing outside this page drives Add Engagement today.
    window.AddEngagementModal = { open, modal: addModalInstance, modalEl: addModal };

    document.querySelectorAll('.add-engagement-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            open({
                clientId: btn.getAttribute('data-client-id'),
                clientName: btn.getAttribute('data-client-name'),
            });
        });
    });

    addForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const saveBtn = addForm.querySelector('.eng-edit-btn-save');
        const originalLabel = saveBtn.textContent;
        saveBtn.disabled = true;
        saveBtn.textContent = 'Creating…';

        const formData = new FormData(addForm);
        const budgetHours = formData.get('budget_hours');
        if (!budgetHours || Number(budgetHours) <= 0) {
            saveBtn.disabled = false;
            saveBtn.textContent = originalLabel;
            if (typeof Swal !== 'undefined') {
                Swal.fire({ icon: 'warning', title: 'Missing information', text: 'Please enter valid budget hours.' });
            } else {
                alert('Please enter valid budget hours.');
            }
            return;
        }
        if (!formData.get('manager')) {
            saveBtn.disabled = false;
            saveBtn.textContent = originalLabel;
            if (typeof Swal !== 'undefined') {
                Swal.fire({ icon: 'warning', title: 'Missing information', text: 'Please select a manager.' });
            } else {
                alert('Please select a manager.');
            }
            return;
        }

        try {
            const response = await fetch('add_engagement.php', { method: 'POST', body: formData });
            const result = await response.json();
            if (result.success) {
                addModalInstance.hide();
                location.reload();
            } else {
                saveBtn.disabled = false;
                saveBtn.textContent = originalLabel;
                if (typeof Swal !== 'undefined') {
                    Swal.fire({ icon: 'error', title: 'Could not create engagement', text: result.message || 'Please try again.' });
                } else {
                    alert('Error: ' + result.message);
                }
            }
        } catch (error) {
            console.error('Failed to add engagement', error);
            saveBtn.disabled = false;
            saveBtn.textContent = originalLabel;
            if (typeof Swal !== 'undefined') {
                Swal.fire({ icon: 'error', title: 'Network error', text: 'Could not save. Please try again.' });
            }
        }
    });
});
