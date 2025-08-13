<div class="modal fade" id="manageEntryPromptModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Manage or Add Entry</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">

  <!-- Initial buttons -->
  <div id="entryTypePrompt" class="text-center">
    <p>Please choose the type of entry:</p>
    <button id="manageEntriesButton" class="badge text-bg-info text-white p-2 text-decoration-none fw-medium me-2" style="font-size: .875rem; background-color: rgb(3,2,18);border: none !important;">Manage Existing Entries</button>
    <button id="addEntriesButton" class="badge text-white p-2 text-decoration-none fw-medium" style="font-size: .875rem; background-color: rgb(3,2,18); border: none !important;">Add New Entry</button>
  </div>

  <div id="entriesListing" class="d-none">
  <button id="backToButtons" class="btn btn-secondary mb-3">Back</button>
  <div id="entriesListContainer"><!-- Cards will render here --></div>
  </div>

  </div>
    </div>
  </div>
  </div>