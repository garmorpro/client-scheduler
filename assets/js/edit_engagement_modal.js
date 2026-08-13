document.addEventListener('DOMContentLoaded', () => {
    const editModal = document.getElementById('editEngagementModal');
    const editForm = document.getElementById('editEngagementForm');
    if (!editModal || !editForm) return;

    const editModalInstance = new bootstrap.Modal(editModal);

    const tscWrap = document.getElementById('edit_eng_tsc_wrap');
    function syncTscVisibility() {
        const soc2Checked = Array.from(document.querySelectorAll('#edit_eng_audit_types input[name="audit_type_ids[]"]'))
            .some(cb => cb.checked && cb.dataset.auditTypeName === 'SOC 2');
        tscWrap.classList.toggle('d-none', !soc2Checked);
    }
    document.getElementById('edit_eng_audit_types')?.addEventListener('change', syncTscVisibility);

    // data: { engagementId, clientName, budgetedHours, status, manager, notes, auditTypeIds: [...], tsc: [...] }
    function populate(data) {
        document.getElementById('edit_eng_engagement_id').value = data.engagementId;
        document.getElementById('edit_eng_client_name').value = data.clientName;
        document.getElementById('edit_eng_budgeted_hours').value = data.budgetedHours;
        document.getElementById('edit_eng_status').value = data.status;
        document.getElementById('edit_eng_manager').value = data.manager || '';
        document.getElementById('edit_eng_notes').value = data.notes || '';

        const selectedAuditTypes = (data.auditTypeIds || []).map(String);
        document.querySelectorAll('#edit_eng_audit_types input[name="audit_type_ids[]"]').forEach(cb => {
            cb.checked = selectedAuditTypes.includes(cb.value);
        });

        const selectedTsc = data.tsc || [];
        document.querySelectorAll('#edit_eng_tsc input[name="tsc[]"]').forEach(cb => {
            cb.checked = selectedTsc.includes(cb.value);
        });

        syncTscVisibility();
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
