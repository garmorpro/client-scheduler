document.addEventListener('DOMContentLoaded', function() {
    const fileInput = document.querySelector('input[name="csv_file"]');
    const previewContainer = document.getElementById('csvPreviewContainer');
    const previewTable = document.getElementById('csvPreviewTable');
    const rowCountText = document.getElementById('csvRowCount');

    fileInput.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (!file) return;

        const reader = new FileReader();
        reader.onload = function(event) {
            const text = event.target.result;
            const lines = text.split(/\r\n|\n/).filter(l => l.trim() !== "");
            
            if (lines.length < 2) {
                previewContainer.style.display = 'none';
                return;
            }

            // Get headers
            const headers = lines[0].split(',');
            const dataRows = lines.slice(1);

            // Show row count
            rowCountText.textContent = `Records found: ${dataRows.length}`;

            // Build table
            const thead = previewTable.querySelector('thead');
            const tbody = previewTable.querySelector('tbody');

            thead.innerHTML = `<tr>${headers.map(h => `<th>${h.trim()}</th>`).join('')}</tr>`;
            tbody.innerHTML = '';

            // Only show first 5 rows for preview
            dataRows.slice(0, 5).forEach((row, i) => {
                const cols = row.split(',');
                tbody.innerHTML += `<tr>${cols.map(c => `<td>${c.trim()}</td>`).join('')}</tr>`;
            });

            previewContainer.style.display = 'block';
        };
        reader.readAsText(file);
    });
});