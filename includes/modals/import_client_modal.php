<!-- Import Clients (CSV) - restyled on the same eng-edit-* look as Add/Edit
     Engagement, replacing the old SweetAlert2 popup
     (assets/js/swal-modals/import-clients-modal.js, now removed). This
     modal shell (#importClientsModal) previously sat here unused - the
     live #importClientsBtn click was captured entirely by the Swal
     version, which never touched this markup at all. -->
<div class="modal fade" id="importClientsModal" tabindex="-1" aria-labelledby="importClientsModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" style="max-width: 600px;">
    <div class="modal-content">
      <form id="importClientsForm" enctype="multipart/form-data">
        <div class="modal-body position-relative p-0">
          <button type="button" class="btn-close emp-modal-close" data-bs-dismiss="modal" aria-label="Close"></button>

          <div class="eng-edit-body">
            <div class="eng-edit-modal-title" id="importClientsModalLabel">Import Clients (CSV)</div>
            <p class="eng-edit-subtitle">Upload a CSV file using the template format.</p>

            <div class="eng-edit-field" style="text-align:center;">
              <a href="../assets/templates/bulk_client_template.csv" download class="eng-edit-template-link">
                <i class="bi bi-download"></i> Download CSV Template
              </a>
            </div>

            <div class="eng-edit-field">
              <div id="clientsCsvDropzone" class="eng-edit-dropzone">
                <span id="clientsCsvDropzoneText">Click or drag CSV file here</span>
                <input type="file" id="clients_csv_file" name="csv_file" accept=".csv" hidden required>
              </div>
              <div class="eng-edit-hint">Required columns: <strong>client_name</strong>, <strong>onboarded_date</strong>. Optional: <em>notes</em>.</div>
            </div>

            <div id="clientsCsvPreview" class="eng-edit-csv-preview d-none"></div>

            <div id="clientsImportSummary" class="eng-edit-import-banner d-none"></div>
          </div>

          <div class="eng-edit-footer">
            <button type="button" class="eng-edit-btn-cancel" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="eng-edit-btn-save" id="importClientsSubmitBtn">Upload</button>
            <button type="button" class="eng-edit-btn-save d-none" id="importClientsCloseBtn">Done</button>
          </div>
        </div>
      </form>
    </div>
  </div>
</div>
