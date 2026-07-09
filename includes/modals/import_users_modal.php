<div class="modal fade" id="importUsersModal" tabindex="-1" aria-labelledby="importUsersModalTitle" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" style="max-width: 520px;">
    <div class="modal-content">
      <form id="importUsersForm">
        <div class="modal-body position-relative p-0">
          <button type="button" class="btn-close emp-modal-close" data-bs-dismiss="modal" aria-label="Close"></button>

          <div class="eng-edit-hero">
            <div class="eng-edit-title" id="importUsersModalTitle">Import Users</div>
          </div>

          <div class="eng-edit-body">
            <div class="eng-edit-field">
              <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:6px;">
                <label style="margin-bottom:0;">CSV File</label>
                <a href="../assets/templates/bulk_import_user_template.csv" download class="iu-template-link">
                  <i class="bi bi-download"></i> Download template
                </a>
              </div>
              <label for="csvFileInput" class="iu-dropzone">
                <i class="bi bi-cloud-arrow-up" style="font-size:20px; display:block; margin-bottom:4px;"></i>
                Click to choose a .csv file (max 2MB)<br>
                Columns: email, full_name, job_title, role
              </label>
              <input type="file" id="csvFileInput" name="csv_file" accept=".csv" required style="display:none;">
            </div>

            <div class="iu-preview-wrap" id="csvPreviewContainer" style="display:none;">
              <div class="iu-preview-hint" id="csvRowCount"></div>
              <div style="overflow-x:auto; border:1px solid #e3e7e5; border-radius:10px;">
                <table id="csvPreviewTable">
                  <thead></thead>
                  <tbody></tbody>
                </table>
              </div>
            </div>
          </div>

          <div class="eng-edit-footer">
            <button type="button" class="eng-edit-btn-cancel" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="eng-edit-btn-save">Import</button>
          </div>
        </div>
      </form>
    </div>
  </div>
</div>
