document.getElementById('importClientsBtn').addEventListener('click', function(e) {
    e.preventDefault();

    const isDark = document.body.classList.contains('dark-mode');

    Swal.fire({
        title: 'Import Clients (CSV)',
        background: isDark ? '#2a2a3d' : '#fff',
        color: isDark ? '#e0e0e0' : '#1a1a1a',
        html: `
            <p class="pop-up-small small text-muted">Upload a CSV file using the template format.</p>
            <a href="../assets/templates/bulk_client_template.csv" download class="swal-btn p-0 mb-3">
                Download CSV Template
            </a>
            <div class="mb-3"></div>
            <div id="fileWrapper" style="border: 2px dashed #d1d5db; border-radius: 6px; padding: 20px; text-align:center; cursor:pointer; color:#6c757d;">
                Click or drag CSV file here
                <input type="file" id="csvFileInput" accept=".csv" style="display:none;">
            </div>
            <div id="csvPreview" style="margin-top:15px; max-height:200px; overflow:auto;"></div>
        `,
        showCancelButton: true,
        confirmButtonText: 'Upload',
        cancelButtonText: 'Cancel',
        focusConfirm: false,
        width: '700px',
        didOpen: () => {
            const popup = Swal.getPopup();
            const fileInputEl = popup.querySelector('#csvFileInput');
            const previewEl = popup.querySelector('#csvPreview');
            const fileWrapper = popup.querySelector('#fileWrapper');

            fileWrapper.addEventListener('click', () => fileInputEl.click());

            fileWrapper.addEventListener('dragover', (e) => {
                e.preventDefault();
                fileWrapper.classList.add('drag-over');
            });
            fileWrapper.addEventListener('dragleave', () => {
                fileWrapper.classList.remove('drag-over');
            });
            fileWrapper.addEventListener('drop', (e) => {
                e.preventDefault();
                fileWrapper.classList.remove('drag-over');
                if (e.dataTransfer.files.length) {
                    fileInputEl.files = e.dataTransfer.files;
                    triggerPreview(fileInputEl.files[0]);
                }
            });

            fileInputEl.addEventListener('change', function() {
                if (!fileInputEl.files.length) {
                    previewEl.innerHTML = '';
                    return;
                }
                triggerPreview(fileInputEl.files[0]);
            });

            function triggerPreview(file) {
                const reader = new FileReader();
                reader.onload = function(event) {
                    const text = event.target.result;
                    const lines = text.split(/\r\n|\n/).filter(l => l.trim() !== '');
                    if (lines.length < 2) {
                        previewEl.innerHTML = '<p class="text-muted">No data rows found.</p>';
                        return;
                    }
                    const headers = lines[0].split(',');
                    const rows = lines.slice(1);

                    let previewHtml = `<p><strong>Records found:</strong> ${rows.length}</p>`;
                    previewHtml += `<table class="table table-sm table-bordered"><thead><tr>`;
                    headers.forEach(h => previewHtml += `<th>${h.trim()}</th>`);
                    previewHtml += `</tr></thead><tbody>`;
                    rows.slice(0, 5).forEach(row => {
                        const cols = row.split(',');
                        previewHtml += '<tr>';
                        cols.forEach(c => previewHtml += `<td>${c.trim()}</td>`);
                        previewHtml += '</tr>';
                    });
                    previewHtml += '</tbody></table>';
                    if (rows.length > 5) previewHtml += `<p class="small text-muted">...and ${rows.length - 5} more rows</p>`;

                    previewEl.innerHTML = previewHtml;
                };
                reader.readAsText(file);
            }
        },
        preConfirm: () => {
            const popup = Swal.getPopup();
            const fileInputEl = popup.querySelector('#csvFileInput');
            if (!fileInputEl.files.length) {
                Swal.showValidationMessage('Please select a CSV file');
                return false;
            }

            const formData = new FormData();
            formData.append('csv_file', fileInputEl.files[0]);

            Swal.showLoading();

            return fetch('import_clients.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .catch(err => {
                Swal.showValidationMessage(`Request failed: ${err}`);
            });
        }
    }).then(result => {
        if (result.isConfirmed) {
            const data = result.value;
            let htmlMsg = `<p><strong>Successfully imported:</strong> ${data.successCount}</p>`;
            if (data.errors && data.errors.length) {
                htmlMsg += `<p class="text-warning"><strong>Some rows could not be imported. Please check your CSV format and try again.</strong></p>`;
            }

            Swal.fire({
                title: 'Import Results',
                html: htmlMsg,
                icon: data.errors && data.errors.length ? 'warning' : 'success',
                background: isDark ? '#2a2a3d' : '#fff',
                color: isDark ? '#e0e0e0' : '#1a1a1a',
                confirmButtonText: 'OK'
            }).then(() => location.reload());
        }
    });
});