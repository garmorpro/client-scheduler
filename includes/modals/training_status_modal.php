<!-- Training status editor - replaces the old appTextPrompt single-line
     "type a comma-separated list" popup with a real modal: each not-yet-
     trained criterion is its own removable chip, plus a field to type and
     add new ones. Matches the app's own eng-edit-hero/body/footer modal
     shell (see includes/modals/view_engagement_modal.php's independence
     popup for the same pattern). -->
<div class="modal fade" id="trainingStatusModal" tabindex="-1" aria-labelledby="trainingStatusModalTitle" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" style="max-width: 420px;">
    <div class="modal-content">
      <div class="modal-body position-relative p-0">
        <button type="button" class="btn-close emp-modal-close" data-bs-dismiss="modal" aria-label="Close"></button>

        <div class="eng-edit-hero">
          <div class="eng-edit-title" id="trainingStatusModalTitle">Training status</div>
          <p class="text-muted" id="trainingStatusModalSubtitle" style="font-size: 12.5px; margin: 4px 0 0;">Criteria this person hasn't completed training on yet</p>
        </div>

        <div class="eng-edit-body">
          <div class="eng-edit-field">
            <label>Not yet trained on</label>
            <div class="tr-editor-chips" id="trainingStatusChips"></div>
          </div>
          <div class="eng-edit-field">
            <input type="text" class="eng-edit-input" id="trainingStatusAddInput" placeholder="Type a criterion and press Enter">
          </div>
        </div>

        <div class="eng-edit-footer">
          <button type="button" class="eng-edit-btn-cancel" data-bs-dismiss="modal">Cancel</button>
          <button type="button" class="eng-edit-btn-save" id="trainingStatusSaveBtn">Save</button>
        </div>
      </div>
    </div>
  </div>
</div>
