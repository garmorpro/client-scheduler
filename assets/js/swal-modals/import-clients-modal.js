document.getElementById('importClientsBtn').addEventListener('click', function(e) {
    e.preventDefault();
    
    const isDark = document.body.classList.contains('dark-mode');

    Swal.fire({
        title: 'Import Clients from CSV',
        background: isDark ? '#2a2a3d' : '#fff',
        color: isDark ? '#e0e0e0' : '#1a1a1a',
        html: `
            <p>Please use the <a href="../assets/templates/bulk_client_template.csv" download style="color: ${isDark ? '#a3cc38' : '#003f47'}">CSV template</a> to ensure correct format.</p>
            <div class="mb-3 text-start">
                <label class="form-label">Select CSV File</label>
                <input type="file" class="form-control" id="swal-csv-file" accept=".csv" required>
            </div>
            <div class="alert alert-info small text-start">
                Only CSV files are supported. Required columns: 
                <strong>client_name and onboarded_date</strong><br>
                Optional Column: <em>notes</em>
            </div>
            <div id="swal-import-summary" style="max-height:300px; overflow-y:auto;"></div>
        `,
        showCancelButton: true,
        confirmButtonText: 'Import',
        cancelButtonText: 'Cancel',
        confirmButtonColor: isDark ? '#3a3a50' : '#003f47',
        cancelButtonColor: isDark ? '#555572' : '#6c757d',
        preConfirm: () => {
            const file = document.getElementById('swal-csv-file').files[0];
            if (!file) {
                Swal.showValidationMessage('Please select a CSV file.');
                return false;
            }

            const formData = new FormData();
            formData.append('csv_file', file);

            return fetch('import_clients.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (!data.success) throw new Error(data.error || 'Import failed');
                return data;
            })
            .catch(err => {
                Swal.showValidationMessage(err.message);
            });
        }
    }).then(result => {
        if (result.isConfirmed && result.value) {
            Swal.fire({
                title: 'Import Complete!',
                text: result.value.message || 'Clients imported successfully.',
                icon: 'success',
                background: isDark ? '#2a2a3d' : '#fff',
                color: isDark ? '#e0e0e0' : '#1a1a1a',
                confirmButtonColor: isDark ? '#3a3a50' : '#003f47',
            });
        }
    });
});