<!-- Bulk Global PTO Modal -->
<div class="modal fade" id="importGlobalPtoModal" tabindex="-1" aria-labelledby="importGlobalPtoModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="importGlobalPtoForm" enctype="multipart/form-data">
        <div class="modal-header">
          <h5 class="modal-title" id="importGlobalPtoModalLabel">Import Global PTO from CSV</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

        <div class="modal-body">
          <p>
            Please use the <a href="../assets/templates/bulk_global_pto_template.csv" download>CSV template</a> to ensure correct format.
          </p>

          <div class="mb-3">
            <label for="global_pto_csv_file" class="form-label">Select CSV File</label>
            <input type="file" class="form-control" id="global_pto_csv_file" name="csv_file" accept=".csv" required>
          </div>

          <div class="alert alert-info small">
            Only CSV files are supported. Required columns: 
            <strong>week_start, assigned_hours, timeoff_note</strong>
          </div>

          <!-- Import Summary Container -->
          <div id="globalPtoImportSummary" class="mt-3" style="max-height: 300px; overflow-y: auto; display: none;">
            <!-- Filled dynamically by JS -->
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary" id="importGlobalPtoSubmitBtn">Import</button>
          <button type="button" class="btn btn-success d-none" id="importGlobalPtoCloseBtn">OK</button>
        </div>
      </form>
    </div>
  </div>
</div>
