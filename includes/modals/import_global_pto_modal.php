<!-- Bulk Global PTO Modal - restyled on the eng-edit-* look established for
     Add/Edit Engagement. Note: as of this restyle, nothing in pages/
     includes this modal or its JS (assets/js/import_global_pto_modal.js) -
     it's currently unreachable from the UI, same orphan pattern found
     elsewhere this session. Left functionally as-is (just restyled) since
     wiring it up is a separate decision. -->
<div class="modal fade" id="importGlobalPtoModal" tabindex="-1" aria-labelledby="importGlobalPtoModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" style="max-width: 520px;">
    <div class="modal-content">
      <form id="importGlobalPtoForm" enctype="multipart/form-data">
        <div class="modal-body position-relative p-0">
          <button type="button" class="btn-close emp-modal-close" data-bs-dismiss="modal" aria-label="Close"></button>

          <div class="eng-edit-body">
            <div class="eng-edit-modal-title" id="importGlobalPtoModalLabel">Import Global PTO (CSV)</div>
            <p class="eng-edit-subtitle">
              Use the <a href="../assets/templates/bulk_global_pto_template.csv" download>CSV template</a> to ensure correct format.
            </p>

            <div class="eng-edit-field">
              <label for="global_pto_csv_file">Select CSV File</label>
              <input type="file" class="eng-edit-input" id="global_pto_csv_file" name="csv_file" accept=".csv" required>
              <div class="eng-edit-hint">Required columns: <strong>week_start, assigned_hours, timeoff_note</strong></div>
            </div>

            <div id="globalPtoImportSummary" class="eng-edit-import-banner d-none"></div>
          </div>

          <div class="eng-edit-footer">
            <button type="button" class="eng-edit-btn-cancel" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="eng-edit-btn-save" id="importGlobalPtoSubmitBtn">Import</button>
            <button type="button" class="eng-edit-btn-save d-none" id="importGlobalPtoCloseBtn">OK</button>
          </div>
        </div>
      </form>
    </div>
  </div>
</div>
