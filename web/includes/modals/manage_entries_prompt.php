<div class="modal fade" id="manageEntryPromptModal" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg rounded-3">
      
      <!-- Modal Header -->
      <div class="modal-header bg-dark text-white">
        <h5 class="modal-title fw-bold">
          <i class="bi bi-journal-text me-2"></i>Manage or Add Entry
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>

      <!-- Modal Body -->
      <div class="modal-body">
        
        <!-- User Details Section -->
        <div class="mb-4">
          <div class="p-3 bg-light rounded border">
            <h6 class="fw-bold mb-2">Entry Details</h6>
            <div class="row">
              <div class="col-md-6">
                <small class="text-muted">User ID:</small>
                <p class="mb-0 fw-semibold" id="entryUserId">--</p>
              </div>
              <div class="col-md-6">
                <small class="text-muted">Week Start:</small>
                <p class="mb-0 fw-semibold" id="entryWeekStart">--</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Initial Buttons -->
        <div id="manageAddButtons" class="text-center">
          <p class="mb-3 fw-medium">Please choose the type of entry:</p>
          <div class="d-flex justify-content-center gap-2 flex-wrap">
            <button id="manageEntriesButton" 
              class="btn btn-dark btn-sm px-4 py-2">
              <i class="bi bi-pencil-square me-1"></i> Manage Existing Entries
            </button>
            <button id="addEntriesButton" 
              class="btn btn-primary btn-sm px-4 py-2">
              <i class="bi bi-plus-circle me-1"></i> Add New Entry
            </button>
          </div>
        </div>

        <!-- Entries Listing -->
        <div id="entriesListing" class="d-none">
          <button id="backToButtons" class="btn btn-outline-secondary btn-sm mb-3">
            <i class="bi bi-arrow-left me-1"></i> Back
          </button>
          <div id="entriesListContainer" class="row g-3">
            <!-- Cards will render here -->
          </div>
        </div>

      </div>
    </div>
  </div>
</div>
