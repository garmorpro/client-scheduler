document.addEventListener('DOMContentLoaded', () => {
  const importForm = document.getElementById('importGlobalPtoForm');
  const fileInput = document.getElementById('global_pto_csv_file');
  const importSummary = document.getElementById('globalPtoImportSummary');
  const importSubmitBtn = document.getElementById('importGlobalPtoSubmitBtn');
  const importCloseBtn = document.getElementById('importGlobalPtoCloseBtn');
  const importModal = new bootstrap.Modal(document.getElementById('importGlobalPtoModal'));

  importForm.addEventListener('submit', async (e) => {
    e.preventDefault();

    importSummary.style.display = 'none';
    importSummary.innerHTML = '';
    importCloseBtn.classList.add('d-none');
    importSubmitBtn.classList.remove('d-none');

    const file = fileInput.files[0];
    if (!file) {
      alert('Please select a CSV file to upload.');
      return;
    }
    if (file.type !== 'text/csv' && !file.name.endsWith('.csv')) {
      alert('Only CSV files are allowed.');
      return;
    }

    const formData = new FormData();
    formData.append('csv_file', file);

    try {
      const response = await fetch('import_global_pto.php', {
        method: 'POST',
        body: formData
      });
      const result = await response.json();

      importSummary.style.display = 'block';

      let html = `<p><strong>Import Results:</strong></p>`;
      html += `<p>Successfully imported: ${result.successCount}</p>`;

      if (result.errors && result.errors.length > 0) {
        html += `<p class="text-danger">Errors (${result.errors.length}):</p><ul>`;
        result.errors.forEach(err => {
          html += `<li>Row ${err.row}: ${err.message}</li>`;
        });
        html += `</ul>`;
      } else {
        html += `<p class="text-success">No errors found.</p>`;
      }

      importSummary.innerHTML = html;

      importCloseBtn.classList.remove('d-none');
      importSubmitBtn.classList.add('d-none');

      fileInput.value = '';

    } catch (error) {
      alert('Error processing import: ' + error.message);
    }
  });

  importCloseBtn.addEventListener('click', () => {
    importModal.hide();
    location.reload(); // reload page to show updated global PTO entries
  });
});
