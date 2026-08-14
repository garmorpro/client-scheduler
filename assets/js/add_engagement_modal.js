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
    function checkedAuditTypeNames() {
        return Array.from(document.querySelectorAll('#add_eng_audit_types input[name="audit_type_ids[]"]'))
            .filter(cb => cb.checked).map(cb => cb.dataset.auditTypeName);
    }
    function syncTscVisibility() {
        tscWrap.classList.toggle('d-none', !checkedAuditTypeNames().includes('SOC 2'));
    }

    // PCI Assessment Type + Delivery Date only matter when PCI is checked,
    // regardless of what else is checked alongside it. Not every PCI
    // engagement produces a ROC - could be an AOC or a SAQ variant instead.
    const pciWrap = document.getElementById('add_eng_pci_wrap');
    const pciTypeSelect = document.getElementById('add_eng_pci_assessment_type');
    const pciDateInput = document.getElementById('add_eng_pci_delivery_date');
    function syncPciVisibility() {
        const pciChecked = checkedAuditTypeNames().includes('PCI');
        pciWrap.classList.toggle('d-none', !pciChecked);
        if (!pciChecked) {
            pciTypeSelect.value = '';
            pciDateInput.value = '';
        }
    }

    // Report/SOC Type only matters once SOC 1 or SOC 2 is checked; which of
    // As-of Date vs. Review Period shows depends on Type 1 vs. Type 2.
    const socTypeWrap = document.getElementById('add_eng_soc_type_wrap');
    const socTypeSelect = document.getElementById('add_eng_soc_type');
    const asOfWrap = document.getElementById('add_eng_as_of_wrap');
    const reviewPeriodWrap = document.getElementById('add_eng_review_period_wrap');
    function syncReportTypeFields() {
        const type = socTypeSelect.value;
        asOfWrap.classList.toggle('d-none', type !== 'Type 1');
        reviewPeriodWrap.classList.toggle('d-none', type !== 'Type 2');
    }
    function syncSocTypeVisibility() {
        const names = checkedAuditTypeNames();
        const socChecked = names.includes('SOC 1') || names.includes('SOC 2');
        socTypeWrap.classList.toggle('d-none', !socChecked);
        if (!socChecked) socTypeSelect.value = '';
        syncReportTypeFields();
    }
    socTypeSelect.addEventListener('change', syncReportTypeFields);
    document.getElementById('add_eng_audit_types')?.addEventListener('change', () => {
        syncTscVisibility();
        syncSocTypeVisibility();
        syncPciVisibility();
    });

    function open({ clientId, clientName }) {
        addForm.reset();
        document.getElementById('add_eng_client_id').value = clientId;
        document.getElementById('add_eng_client_name_hidden').value = clientName;
        document.getElementById('add_eng_client_name').value = clientName;
        // .reset() re-checks any checkbox with a `checked` attribute in the
        // markup (Security, under TSC) - resync visibility to match since
        // nothing else is checked yet.
        syncTscVisibility();
        syncSocTypeVisibility();
        syncPciVisibility();
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
            if (typeof appNotify !== 'undefined') {
                appNotify({ icon: 'warning', title: 'Missing information', text: 'Please enter valid budget hours.' });
            } else {
                alert('Please enter valid budget hours.');
            }
            return;
        }
        if (!formData.get('manager')) {
            saveBtn.disabled = false;
            saveBtn.textContent = originalLabel;
            if (typeof appNotify !== 'undefined') {
                appNotify({ icon: 'warning', title: 'Missing information', text: 'Please select a manager.' });
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
                // Land on the Engagements page with the new engagement's
                // View panel already open, instead of just reloading this
                // page - same sessionStorage-flag-then-click idiom
                // engagement-management.php's own load handler already
                // uses for the post-DOL-save reopen.
                if (result.engagement_id) {
                    sessionStorage.setItem('reopenEngagementId', result.engagement_id);
                    window.location.href = 'engagement-management.php';
                } else {
                    location.reload();
                }
            } else {
                saveBtn.disabled = false;
                saveBtn.textContent = originalLabel;
                if (typeof appNotify !== 'undefined') {
                    appNotify({ icon: 'error', title: 'Could not create engagement', text: result.message || 'Please try again.' });
                } else {
                    alert('Error: ' + result.message);
                }
            }
        } catch (error) {
            console.error('Failed to add engagement', error);
            saveBtn.disabled = false;
            saveBtn.textContent = originalLabel;
            if (typeof appNotify !== 'undefined') {
                appNotify({ icon: 'error', title: 'Network error', text: 'Could not save. Please try again.' });
            }
        }
    });
});
