<!-- manage_entries_prompt.php -->
<div class="modal fade" id="manageEntryPromptModal" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content border-0 rounded-3 shadow-sm">
      
      <!-- Header -->
      <div class="modal-header bg-dark text-white py-2">
        <h5 class="modal-title fw-bold">
          <i class="bi bi-journal-text me-2"></i> Manage Entries
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>

      <!-- Body -->
      <div class="modal-body">

        <!-- User Info -->
        <div class="p-3 bg-light rounded border mb-4">
          <div class="row">
            <div class="col-md-4">
              <small class="text-muted">Employee Name</small>
              <div class="fw-semibold" id="entryUserName">--</div>
            </div>
            <div class="col-md-4">
              <small class="text-muted">Week Start</small>
              <div class="fw-semibold" id="entryWeekStart">--</div>
            </div>
          </div>
        </div>

        <!-- Entries & Add Button -->
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h6 class="fw-bold mb-0">Entries for this week</h6>
          <button id="addEntriesButton2" class="btn badge text-white p-2 text-decoration-none fw-medium btn-dark" style="font-size: 14px !important;">
            <i class="bi bi-plus me-1"></i> Add Entry
          </button>
        </div>

        <!-- Listing -->
        <div id="entriesListContainer" class="row g-3">
          <!-- Populated dynamically -->
        </div>

      </div>
    </div>
  </div>
</div>
