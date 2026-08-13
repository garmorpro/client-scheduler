// Import Clients (CSV) - restyled on the eng-edit-* look (see
// includes/modals/import_client_modal.php), replacing the SweetAlert2
// popup assets/js/swal-modals/import-clients-modal.js used to render.
document.addEventListener('DOMContentLoaded', () => {
    const modalEl = document.getElementById('importClientsModal');
    const form = document.getElementById('importClientsForm');
    if (!modalEl || !form) return;

    const modal = new bootstrap.Modal(modalEl);
    const dropzone = document.getElementById('clientsCsvDropzone');
    const dropzoneText = document.getElementById('clientsCsvDropzoneText');
    const fileInput = document.getElementById('clients_csv_file');
    const previewEl = document.getElementById('clientsCsvPreview');
    const summaryEl = document.getElementById('clientsImportSummary');
    const submitBtn = document.getElementById('importClientsSubmitBtn');
    const closeBtn = document.getElementById('importClientsCloseBtn');

    function resetModal() {
        form.reset();
        dropzoneText.textContent = 'Click or drag CSV file here';
        dropzone.classList.remove('has-file');
        previewEl.classList.add('d-none');
        previewEl.innerHTML = '';
        summaryEl.classList.add('d-none');
        summaryEl.innerHTML = '';
        submitBtn.classList.remove('d-none');
        submitBtn.disabled = false;
        submitBtn.textContent = 'Upload';
        closeBtn.classList.add('d-none');
    }

    document.getElementById('importClientsBtn')?.addEventListener('click', (e) => {
        e.preventDefault();
        resetModal();
        modal.show();
    });

    dropzone.addEventListener('click', () => fileInput.click());
    dropzone.addEventListener('dragover', (e) => { e.preventDefault(); dropzone.classList.add('drag-over'); });
    dropzone.addEventListener('dragleave', () => dropzone.classList.remove('drag-over'));
    dropzone.addEventListener('drop', (e) => {
        e.preventDefault();
        dropzone.classList.remove('drag-over');
        if (e.dataTransfer.files.length) {
            fileInput.files = e.dataTransfer.files;
            handleFile(fileInput.files[0]);
        }
    });
    fileInput.addEventListener('change', () => {
        if (fileInput.files.length) handleFile(fileInput.files[0]);
    });

    function handleFile(file) {
        dropzoneText.textContent = file.name;
        dropzone.classList.add('has-file');

        const reader = new FileReader();
        reader.onload = (event) => {
            const text = event.target.result;
            const lines = text.split(/\r\n|\n/).filter(l => l.trim() !== '');
            if (lines.length < 2) {
                previewEl.innerHTML = '<p class="text-muted mb-0">No data rows found.</p>';
                previewEl.classList.remove('d-none');
                return;
            }
            const headers = lines[0].split(',');
            const rows = lines.slice(1);

            let html = `<p class="eng-edit-csv-count"><strong>${rows.length}</strong> record${rows.length === 1 ? '' : 's'} found</p>`;
            html += '<table class="eng-edit-csv-table"><thead><tr>';
            headers.forEach(h => { html += `<th>${h.trim()}</th>`; });
            html += '</tr></thead><tbody>';
            rows.slice(0, 5).forEach(row => {
                html += '<tr>';
                row.split(',').forEach(c => { html += `<td>${c.trim()}</td>`; });
                html += '</tr>';
            });
            html += '</tbody></table>';
            if (rows.length > 5) html += `<p class="eng-edit-csv-more">&hellip;and ${rows.length - 5} more row${rows.length - 5 === 1 ? '' : 's'}</p>`;

            previewEl.innerHTML = html;
            previewEl.classList.remove('d-none');
        };
        reader.readAsText(file);
    }

    function notify(message, isError) {
        if (typeof appNotify !== 'undefined') {
            appNotify({ icon: isError ? 'warning' : 'success', title: message });
        } else {
            alert(message);
        }
    }

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const file = fileInput.files[0];
        if (!file) {
            notify('Please select a CSV file to upload.', true);
            return;
        }

        submitBtn.disabled = true;
        submitBtn.textContent = 'Uploading…';

        const formData = new FormData();
        formData.append('csv_file', file);

        try {
            const response = await fetch('import_clients.php', { method: 'POST', body: formData });
            const result = await response.json();

            let html = `<p><strong>Successfully imported:</strong> ${result.successCount}</p>`;
            if (result.errors && result.errors.length) {
                html += `<p class="eng-edit-import-banner-warn">Errors (${result.errors.length}):</p><ul class="eng-edit-import-errors">`;
                result.errors.forEach(err => { html += `<li>Row ${err.row}: ${err.message}</li>`; });
                html += '</ul>';
            } else {
                html += '<p class="eng-edit-import-banner-ok">No errors found.</p>';
            }
            summaryEl.innerHTML = html;
            summaryEl.classList.remove('d-none');

            submitBtn.classList.add('d-none');
            closeBtn.classList.remove('d-none');
        } catch (error) {
            console.error('Failed to import clients', error);
            submitBtn.disabled = false;
            submitBtn.textContent = 'Upload';
            notify('Error processing import: ' + error.message, true);
        }
    });

    closeBtn.addEventListener('click', () => {
        modal.hide();
        location.reload();
    });
});
