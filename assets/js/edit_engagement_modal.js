document.addEventListener('DOMContentLoaded', () => {
    const editModal = document.getElementById('editEngagementModal');
    const editForm = document.getElementById('editEngagementForm');
    if (!editModal || !editForm) return;

    const editModalInstance = new bootstrap.Modal(editModal);

    const tscWrap = document.getElementById('edit_eng_tsc_wrap');
    function checkedAuditTypeNames() {
        return Array.from(document.querySelectorAll('#edit_eng_audit_types input[name="audit_type_ids[]"]'))
            .filter(cb => cb.checked).map(cb => cb.dataset.auditTypeName);
    }
    function syncTscVisibility() {
        tscWrap.classList.toggle('d-none', !checkedAuditTypeNames().includes('SOC 2'));
    }

    const socTypeWrap = document.getElementById('edit_eng_soc_type_wrap');
    const socTypeSelect = document.getElementById('edit_eng_soc_type');
    const asOfWrap = document.getElementById('edit_eng_as_of_wrap');
    const reviewPeriodWrap = document.getElementById('edit_eng_review_period_wrap');
    function syncReportTypeFields() {
        const type = socTypeSelect.value;
        asOfWrap.classList.toggle('d-none', type !== 'Type 1');
        reviewPeriodWrap.classList.toggle('d-none', type !== 'Type 2');
    }
    function syncSocTypeVisibility() {
        const names = checkedAuditTypeNames();
        const socChecked = names.includes('SOC 1') || names.includes('SOC 2');
        socTypeWrap.classList.toggle('d-none', !socChecked);
        syncReportTypeFields();
    }
    socTypeSelect.addEventListener('change', syncReportTypeFields);
    document.getElementById('edit_eng_audit_types')?.addEventListener('change', () => {
        syncTscVisibility();
        syncSocTypeVisibility();
    });

    // data: { engagementId, clientName, budgetedHours, status, manager, notes, location, poc, scope, repeatFlag, socType, asOfDate, reviewPeriodStart, reviewPeriodEnd, auditTypeIds: [...], tsc: [...] }
    function populate(data) {
        document.getElementById('edit_eng_engagement_id').value = data.engagementId;
        document.getElementById('edit_eng_client_name').value = data.clientName;
        document.getElementById('edit_eng_location').value = data.location || '';
        document.getElementById('edit_eng_poc').value = data.poc || '';
        document.getElementById('edit_eng_budgeted_hours').value = data.budgetedHours;
        document.getElementById('edit_eng_status').value = data.status;
        document.getElementById('edit_eng_manager').value = data.manager || '';
        document.getElementById('edit_eng_scope').value = data.scope || '';
        document.getElementById('edit_eng_repeat_flag').checked = !!data.repeatFlag;
        document.getElementById('edit_eng_notes').value = data.notes || '';
        socTypeSelect.value = data.socType || '';
        document.getElementById('edit_eng_as_of_date').value = data.asOfDate || '';
        document.getElementById('edit_eng_review_period_start').value = data.reviewPeriodStart || '';
        document.getElementById('edit_eng_review_period_end').value = data.reviewPeriodEnd || '';

        const selectedAuditTypes = (data.auditTypeIds || []).map(String);
        document.querySelectorAll('#edit_eng_audit_types input[name="audit_type_ids[]"]').forEach(cb => {
            cb.checked = selectedAuditTypes.includes(cb.value);
        });

        const selectedTsc = data.tsc || [];
        document.querySelectorAll('#edit_eng_tsc input[name="tsc[]"]').forEach(cb => {
            cb.checked = selectedTsc.includes(cb.value);
        });

        syncTscVisibility();
        syncSocTypeVisibility();
    }

    function open(data) {
        populate(data);
        editModalInstance.show();
    }

    // Exposed so the View Engagement panel (used on pages that don't
    // otherwise include this modal, like My Schedule) can drive it directly.
    window.EditEngagementModal = { open, modal: editModalInstance, modalEl: editModal };

    document.querySelectorAll('.edit-engagement-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            open({
                engagementId: btn.getAttribute('data-engagement-id'),
                clientName: btn.getAttribute('data-client-name'),
                budgetedHours: btn.getAttribute('data-budgeted-hours'),
                status: btn.getAttribute('data-status'),
                manager: btn.getAttribute('data-manager') || '',
                notes: btn.getAttribute('data-notes') || '',
                location: btn.getAttribute('data-location') || '',
                poc: btn.getAttribute('data-poc') || '',
                scope: btn.getAttribute('data-scope') || '',
                repeatFlag: btn.getAttribute('data-repeat-flag') === '1',
                socType: btn.getAttribute('data-soc-type') || '',
                asOfDate: btn.getAttribute('data-as-of-date') || '',
                reviewPeriodStart: btn.getAttribute('data-review-period-start') || '',
                reviewPeriodEnd: btn.getAttribute('data-review-period-end') || '',
                auditTypeIds: (btn.getAttribute('data-audit-types') || '').split(',').map(s => s.trim()).filter(Boolean),
                tsc: (btn.getAttribute('data-tsc') || '').split(',').map(s => s.trim()).filter(Boolean),
            });
        });
    });

    editForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(editForm);
        try {
            const response = await fetch('edit_engagement.php', { method: 'POST', body: formData });
            const result = await response.json();
            if (result.success) {
                editModalInstance.hide();
                location.reload();
            } else {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({ icon: 'error', title: 'Could not save changes', text: result.message || 'Please try again.' });
                } else {
                    alert('Error: ' + result.message);
                }
            }
        } catch (error) {
            console.error('Failed to save engagement', error);
        }
    });
});
