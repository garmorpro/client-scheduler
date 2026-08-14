<div class="modal fade" id="importEngagementsModal" tabindex="-1" aria-labelledby="importEngagementsModalTitle" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" style="max-width: 640px;">
    <div class="modal-content">
      <div class="modal-body position-relative p-0">
        <button type="button" class="btn-close emp-modal-close" data-bs-dismiss="modal" aria-label="Close"></button>

        <div class="eng-edit-hero">
          <div class="eng-edit-title" id="importEngagementsModalTitle">Import Engagements</div>
          <p class="text-muted" style="font-size: 12.5px; margin: 4px 0 0;">Bulk-create clients, engagements, and weekly hours from a spreadsheet</p>
        </div>

        <div class="eng-edit-body" style="max-height: 62vh; overflow-y: auto;">
          <div class="ie-step">
            <div class="detail-section-title">1. Download the template</div>
            <p class="ie-step-hint">Three tabs - Clients, Engagements, Weekly Hours. Read the Instructions tab first; only add rows for records that don't already exist.</p>
            <a href="download_engagement_import_template.php" class="ie-download-btn">
              <i class="bi bi-download"></i> Download Template (.xlsx)
            </a>
          </div>

          <div class="ie-step">
            <div class="detail-section-title">2. Upload your filled-in file</div>
            <p class="ie-step-hint">Nothing is saved yet - this checks the file and shows a full report first.</p>
            <input type="file" id="ieFileInput" accept=".xlsx" class="eng-edit-input">
            <div id="ieUploadStatus" class="ie-upload-status"></div>
          </div>

          <div class="ie-step d-none" id="ieReportStep">
            <div class="detail-section-title">Validation report</div>
            <div class="ie-summary-grid" id="ieSummaryGrid"></div>
            <div id="ieErrorsWrap" class="d-none">
              <div class="ie-list-title ie-list-title-error"><i class="bi bi-x-circle-fill"></i> Needs fixing before this can be imported</div>
              <div class="ie-issue-list" id="ieErrorsList"></div>
            </div>
            <div id="ieWarningsWrap" class="d-none">
              <div class="ie-list-title ie-list-title-warning"><i class="bi bi-exclamation-triangle-fill"></i> Worth a look, won't block the import</div>
              <div class="ie-issue-list" id="ieWarningsList"></div>
            </div>
          </div>

          <div class="ie-step d-none" id="ieSuccessStep">
            <div class="ie-success-banner">
              <i class="bi bi-check-circle-fill"></i>
              <div id="ieSuccessText"></div>
            </div>
          </div>
        </div>

        <div class="eng-edit-footer">
          <button type="button" class="eng-edit-btn-cancel" data-bs-dismiss="modal">Close</button>
          <button type="button" class="eng-edit-btn-save" id="ieConfirmBtn" disabled>Confirm Import</button>
        </div>
      </div>
    </div>
  </div>
</div>
