const TSC_OPTIONS = ['Security', 'Availability', 'Confidentiality', 'Processing Integrity', 'Privacy'];

document.querySelectorAll('.add-engagement-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const clientId = this.dataset.clientId;
        const clientName = this.dataset.clientName;
        const isDark = document.body.classList.contains('dark-mode');

        const managerOptions = managersList.map(m =>
            `<option value="${m}">${m}</option>`
        ).join('');

        const auditTypeCheckboxes = (typeof auditTypesList !== 'undefined' ? auditTypesList : []).map(at => `
            <label class="eng-audit-type-chip">
                <input type="checkbox" class="swal-audit-type-cb" value="${at.id}" data-audit-type-name="${at.name}">
                <span class="eng-audit-type-dot" style="background:${at.color}"></span>
                ${at.name}
            </label>
        `).join('');

        const tscCheckboxes = TSC_OPTIONS.map(name => `
            <label class="eng-audit-type-chip">
                <input type="checkbox" class="swal-tsc-cb" value="${name}" ${name === 'Security' ? 'checked' : ''}>
                ${name}
            </label>
        `).join('');

        Swal.fire({
            title: 'Add Engagement',
            background: isDark ? '#2a2a3d' : '#fff',
            color: isDark ? '#e0e0e0' : '#1a1a1a',
            html: `
                <div class="mb-3 text-start">
                    <label class="form-label">Client</label>
                    <input type="text" class="form-control" value="${clientName}" disabled>
                </div>
                <div class="mb-3 text-start">
                    <label class="form-label">Location</label>
                    <input type="text" class="form-control" id="swal-location" placeholder="e.g. Charlottesville, VA">
                </div>
                <div class="mb-3 text-start">
                    <label class="form-label">Point of Contact</label>
                    <input type="text" class="form-control" id="swal-poc" placeholder="Client-side contact name">
                </div>
                <div class="mb-3 text-start">
                    <label class="form-label">Budget Hours</label>
                    <input type="number" min="0" class="form-control" id="swal-budget-hours" required>
                </div>
                <div class="mb-3 text-start">
                    <label class="form-label">Status</label>
                    <select class="form-select" id="swal-status">
                        <option value="confirmed">Confirmed</option>
                        <option value="pending">Pending</option>
                        <option value="not_confirmed">Not Confirmed</option>
                    </select>
                </div>
                <div class="mb-3 text-start">
                    <label class="form-label">Manager</label>
                    <select class="form-select" id="swal-manager">
                        <option value="">Select Manager</option>
                        ${managerOptions}
                    </select>
                </div>
                <div class="mb-3 text-start">
                    <label class="form-label">Audit Types</label>
                    <div class="eng-audit-type-list" id="swal-audit-types">
                        ${auditTypeCheckboxes || '<div class="text-muted small">No audit types yet - add some under System Settings.</div>'}
                    </div>
                </div>
                <div class="mb-3 text-start d-none" id="swal-tsc-wrap">
                    <label class="form-label">Trust Services Criteria (SOC 2)</label>
                    <div class="eng-audit-type-list" id="swal-tsc">
                        ${tscCheckboxes}
                    </div>
                </div>
                <div class="mb-3 text-start">
                    <label class="form-label">Scope</label>
                    <textarea class="form-control" id="swal-scope" rows="3" placeholder="Enter scope"></textarea>
                </div>
                <div class="mb-3 text-start form-check">
                    <input type="checkbox" class="form-check-input" id="swal-repeat">
                    <label class="form-check-label" for="swal-repeat">Repeat Engagement</label>
                </div>
                <div class="mb-3 text-start">
                    <label class="form-label">Notes</label>
                    <textarea class="form-control" id="swal-notes" rows="3" placeholder="Enter notes"></textarea>
                </div>
            `,
            showCancelButton: true,
            confirmButtonText: 'Add',
            cancelButtonText: 'Cancel',
            confirmButtonColor: isDark ? '#3a3a50' : '#003f47',
            cancelButtonColor: isDark ? '#555572' : '#6c757d',
            didOpen: () => {
                const auditTypesEl = document.getElementById('swal-audit-types');
                const tscWrap = document.getElementById('swal-tsc-wrap');
                function syncTscVisibility() {
                    const soc2Checked = Array.from(auditTypesEl.querySelectorAll('.swal-audit-type-cb'))
                        .some(cb => cb.checked && cb.dataset.auditTypeName === 'SOC 2');
                    tscWrap.classList.toggle('d-none', !soc2Checked);
                }
                auditTypesEl.addEventListener('change', syncTscVisibility);
            },
            preConfirm: () => {
                const location = document.getElementById('swal-location').value.trim();
                const poc = document.getElementById('swal-poc').value.trim();
                const budgetHours = document.getElementById('swal-budget-hours').value;
                const status = document.getElementById('swal-status').value;
                const manager = document.getElementById('swal-manager').value;
                const scope = document.getElementById('swal-scope').value.trim();
                const repeat = document.getElementById('swal-repeat').checked;
                const notes = document.getElementById('swal-notes').value.trim();
                const auditTypeIds = Array.from(document.querySelectorAll('.swal-audit-type-cb:checked')).map(cb => cb.value);
                const tsc = Array.from(document.querySelectorAll('.swal-tsc-cb:checked')).map(cb => cb.value);

                if (!budgetHours || budgetHours <= 0) {
                    Swal.showValidationMessage('Please enter valid budget hours.');
                    return false;
                }
                if (!manager) {
                    Swal.showValidationMessage('Please select a manager.');
                    return false;
                }

                return fetch('../../../pages/add_engagement.php', {
                    method: 'POST',
                    body: (() => {
                        const formData = new FormData();
                        formData.append('client_id', clientId);
                        formData.append('client_name', clientName);
                        formData.append('location', location);
                        formData.append('poc', poc);
                        formData.append('budget_hours', budgetHours);
                        formData.append('status', status);
                        formData.append('manager', manager);
                        formData.append('scope', scope);
                        formData.append('repeat_flag', repeat ? '1' : '0');
                        formData.append('notes', notes);
                        formData.append('year', new Date().getFullYear());
                        auditTypeIds.forEach(id => formData.append('audit_type_ids[]', id));
                        tsc.forEach(name => formData.append('tsc[]', name));
                        return formData;
                    })()
                })
                .then(res => res.json())
                .catch(err => Swal.showValidationMessage(`Request failed: ${err}`));
            }
        }).then(result => {
            if (result.isConfirmed && result.value && result.value.success) {
                Swal.fire({
                    title: 'Success!',
                    text: 'Engagement added successfully.',
                    icon: 'success',
                    background: isDark ? '#2a2a3d' : '#fff',
                    color: isDark ? '#e0e0e0' : '#1a1a1a',
                    confirmButtonColor: isDark ? '#3a3a50' : '#003f47',
                }).then(() => location.reload());
            }
        });
    });
});
