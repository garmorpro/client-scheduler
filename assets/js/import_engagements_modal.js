document.addEventListener('DOMContentLoaded', () => {
    const modalEl = document.getElementById('importEngagementsModal');
    if (!modalEl) return;

    const fileInput = document.getElementById('ieFileInput');
    const uploadStatus = document.getElementById('ieUploadStatus');
    const reportStep = document.getElementById('ieReportStep');
    const summaryGrid = document.getElementById('ieSummaryGrid');
    const errorsWrap = document.getElementById('ieErrorsWrap');
    const errorsList = document.getElementById('ieErrorsList');
    const warningsWrap = document.getElementById('ieWarningsWrap');
    const warningsList = document.getElementById('ieWarningsList');
    const successStep = document.getElementById('ieSuccessStep');
    const successText = document.getElementById('ieSuccessText');
    const confirmBtn = document.getElementById('ieConfirmBtn');

    let lastValidatedOk = false;

    function resetPanels() {
        reportStep.classList.add('d-none');
        successStep.classList.add('d-none');
        errorsWrap.classList.add('d-none');
        warningsWrap.classList.add('d-none');
        confirmBtn.disabled = true;
        lastValidatedOk = false;
    }

    modalEl.addEventListener('hidden.bs.modal', () => {
        fileInput.value = '';
        uploadStatus.innerHTML = '';
        resetPanels();
    });

    function issueRow(issue) {
        const rowLabel = issue.row ? `Row ${issue.row}` : 'File';
        return `<div class="ie-issue-row"><span class="ie-issue-sheet">${issue.sheet}${issue.row ? ' &middot; ' + rowLabel : ''}</span><span class="ie-issue-msg">${issue.message}</span></div>`;
    }

    function renderSummary(summary) {
        if (!summary) { summaryGrid.innerHTML = ''; return; }
        const cards = [
            ['New Clients', summary.new_clients],
            ['New Engagements', summary.new_engagements],
            ['Hours Rows', summary.hours_rows],
            ['Total Hours', summary.total_hours],
        ];
        summaryGrid.innerHTML = cards.map(([label, value]) => `
            <div class="ie-summary-card">
                <div class="ie-summary-value">${value}</div>
                <div class="ie-summary-label">${label}</div>
            </div>
        `).join('');
    }

    function renderReport(data) {
        reportStep.classList.remove('d-none');
        renderSummary(data.summary);

        if (data.errors && data.errors.length > 0) {
            errorsWrap.classList.remove('d-none');
            errorsList.innerHTML = data.errors.map(issueRow).join('');
        } else {
            errorsWrap.classList.add('d-none');
        }

        if (data.warnings && data.warnings.length > 0) {
            warningsWrap.classList.remove('d-none');
            warningsList.innerHTML = data.warnings.map(issueRow).join('');
        } else {
            warningsWrap.classList.add('d-none');
        }

        lastValidatedOk = !!data.ok;
        confirmBtn.disabled = !lastValidatedOk;
    }

    fileInput.addEventListener('change', async () => {
        resetPanels();
        successStep.classList.add('d-none');
        const file = fileInput.files[0];
        if (!file) { uploadStatus.innerHTML = ''; return; }

        uploadStatus.innerHTML = '<span class="ie-upload-loading"><i class="bi bi-hourglass-split"></i> Validating...</span>';

        try {
            const formData = new FormData();
            formData.append('import_file', file);
            const res = await fetch('import_engagements_validate.php', { method: 'POST', body: formData });
            const data = await res.json();

            if (data.error) {
                uploadStatus.innerHTML = `<span class="ie-upload-error"><i class="bi bi-x-circle"></i> ${data.error}</span>`;
                return;
            }

            uploadStatus.innerHTML = data.ok
                ? '<span class="ie-upload-ok"><i class="bi bi-check-circle"></i> Looks good - review the report below, then confirm.</span>'
                : '<span class="ie-upload-error"><i class="bi bi-x-circle"></i> Fix the issues below, then re-upload.</span>';
            renderReport(data);
        } catch (err) {
            console.error('Failed to validate import file', err);
            uploadStatus.innerHTML = '<span class="ie-upload-error"><i class="bi bi-x-circle"></i> Network error validating this file.</span>';
        }
    });

    confirmBtn.addEventListener('click', () => {
        const file = fileInput.files[0];
        if (!file || !lastValidatedOk) return;

        const run = async () => {
            confirmBtn.disabled = true;
            confirmBtn.textContent = 'Importing...';
            try {
                const formData = new FormData();
                formData.append('import_file', file);
                const res = await fetch('import_engagements_commit.php', { method: 'POST', body: formData });
                const data = await res.json();

                if (!data.success) {
                    if (typeof appNotify !== 'undefined') {
                        appNotify({ icon: 'error', title: 'Could not import', text: data.error || 'Please try again.' });
                    }
                    if (data.errors) renderReport(data);
                    confirmBtn.textContent = 'Confirm Import';
                    confirmBtn.disabled = !lastValidatedOk;
                    return;
                }

                const s = data.summary;
                reportStep.classList.add('d-none');
                successStep.classList.remove('d-none');
                successText.innerHTML = `Created <strong>${s.clients_created}</strong> client${s.clients_created === 1 ? '' : 's'}, <strong>${s.engagements_created}</strong> engagement${s.engagements_created === 1 ? '' : 's'}, and <strong>${s.hours_rows_inserted}</strong> hours row${s.hours_rows_inserted === 1 ? '' : 's'} (${s.total_hours}h total).`;
                confirmBtn.classList.add('d-none');
                fileInput.disabled = true;

                if (typeof appNotify !== 'undefined') {
                    appNotify({ icon: 'success', title: 'Import complete', text: 'Reload Client Management or Master Schedule to see the new data.' });
                }
            } catch (err) {
                console.error('Failed to commit import', err);
                if (typeof appNotify !== 'undefined') {
                    appNotify({ icon: 'error', title: 'Import failed', text: 'Network error - nothing was saved.' });
                }
                confirmBtn.textContent = 'Confirm Import';
                confirmBtn.disabled = !lastValidatedOk;
            }
        };

        if (typeof appConfirm !== 'undefined') {
            appConfirm({
                icon: 'question',
                title: 'Import this file?',
                text: 'This creates the clients, engagements, and hours shown in the report above. This cannot be undone automatically.',
                confirmText: 'Yes, import'
            }).then(confirmed => { if (confirmed) run(); });
        } else if (confirm('Import this file? This cannot be undone automatically.')) {
            run();
        }
    });
});
