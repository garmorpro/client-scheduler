<div class="offcanvas offcanvas-end eng-vm-panel" tabindex="-1" id="viewEngagementModal" aria-labelledby="viewEngagementModalTitle" aria-hidden="true">
  <div class="offcanvas-body position-relative p-0">
    <button type="button" class="btn-close emp-modal-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    <div id="viewEngagementModalBody">
      <!-- content injected here -->
    </div>
  </div>
</div>

<div class="modal fade" id="viewEngDeleteConfirmModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" style="max-width: 420px;">
    <div class="modal-content">
      <div class="modal-body position-relative p-0">
        <button type="button" class="btn-close emp-modal-close" data-bs-dismiss="modal" aria-label="Close"></button>
        <div class="eng-edit-hero">
          <div class="eng-edit-title">Delete Engagement</div>
        </div>
        <div class="eng-edit-body">
          <p style="font-size: 13px; color: #6b7570; margin-bottom: 14px;">This permanently deletes the engagement and cannot be undone. Archive it instead if you just want to keep it out of the active list.</p>
          <div class="eng-edit-field">
            <label for="viewEngDeleteConfirmInput">Type <strong>DELETE</strong> to confirm</label>
            <input type="text" id="viewEngDeleteConfirmInput" class="eng-edit-input" autocomplete="off" placeholder="DELETE">
          </div>
        </div>
        <div class="eng-edit-footer">
          <button type="button" class="eng-edit-btn-cancel" data-bs-dismiss="modal">Cancel</button>
          <button type="button" class="eng-edit-btn-save" id="viewEngDeleteConfirmBtn" style="background:#c0392b;" disabled>Delete Permanently</button>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Edit Timeline - a real modal instead of the old inline date boxes, so
     "editing" reads as a deliberate, separate action rather than the
     always-editable-inline look. Also where Import Timeline's matched
     dates land for review before anything saves. -->
<div class="modal fade" id="editTimelineModal" tabindex="-1" aria-labelledby="editTimelineModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" style="max-width: 600px;">
    <div class="modal-content">
      <form id="editTimelineForm">
        <div class="modal-body position-relative p-0">
          <button type="button" class="btn-close emp-modal-close" data-bs-dismiss="modal" aria-label="Close"></button>

          <div class="eng-edit-hero">
            <div class="eng-edit-title" id="editTimelineModalLabel">Edit Timeline &amp; Key Dates</div>
          </div>

          <div class="eng-edit-body" style="max-height: 62vh; overflow-y: auto;">
            <div id="editTimelineImportBanner" class="eng-edit-import-banner d-none"></div>

            <div class="eng-edit-row">
              <div class="eng-edit-field">
                <label for="tl_internal_planning_call_date">Internal Planning Call</label>
                <input type="date" class="eng-edit-input" id="tl_internal_planning_call_date">
              </div>
              <div class="eng-edit-field">
                <label for="tl_planning_memo_date">Planning Memo</label>
                <input type="date" class="eng-edit-input" id="tl_planning_memo_date">
              </div>
              <div class="eng-edit-field">
                <label for="tl_irl_due_date">IRL Due</label>
                <input type="date" class="eng-edit-input" id="tl_irl_due_date">
              </div>
              <div class="eng-edit-field">
                <label for="tl_client_planning_call_date">Client Planning Call</label>
                <input type="date" class="eng-edit-input" id="tl_client_planning_call_date">
              </div>
            </div>

            <div class="eng-edit-field">
              <label>Fieldwork &ndash; Client Calls</label>
              <div class="eng-edit-row">
                <div class="eng-edit-field">
                  <label for="tl_fieldwork_client_calls_start_date">Start</label>
                  <input type="date" class="eng-edit-input" id="tl_fieldwork_client_calls_start_date">
                </div>
                <div class="eng-edit-field">
                  <label for="tl_fieldwork_client_calls_end_date">End</label>
                  <input type="date" class="eng-edit-input" id="tl_fieldwork_client_calls_end_date">
                </div>
              </div>
            </div>

            <div class="eng-edit-field">
              <label>Fieldwork &ndash; Documentation</label>
              <div class="eng-edit-row">
                <div class="eng-edit-field">
                  <label for="tl_fieldwork_documentation_start_date">Start</label>
                  <input type="date" class="eng-edit-input" id="tl_fieldwork_documentation_start_date">
                </div>
                <div class="eng-edit-field">
                  <label for="tl_fieldwork_documentation_end_date">End</label>
                  <input type="date" class="eng-edit-input" id="tl_fieldwork_documentation_end_date">
                </div>
              </div>
            </div>

            <div class="eng-edit-row">
              <div class="eng-edit-field">
                <label for="tl_leadsheet_date">Leadsheet Due</label>
                <input type="date" class="eng-edit-input" id="tl_leadsheet_date">
              </div>
              <div class="eng-edit-field">
                <label for="tl_conclusion_memo_date">Conclusion Memo</label>
                <input type="date" class="eng-edit-input" id="tl_conclusion_memo_date">
              </div>
              <div class="eng-edit-field">
                <label for="tl_draft_report_due_date">Draft Report Due</label>
                <input type="date" class="eng-edit-input" id="tl_draft_report_due_date">
              </div>
              <div class="eng-edit-field">
                <label for="tl_final_report_date">Final Report</label>
                <input type="date" class="eng-edit-input" id="tl_final_report_date">
              </div>
            </div>

            <div class="eng-edit-field">
              <label for="tl_archive_date">Archive</label>
              <input type="date" class="eng-edit-input" id="tl_archive_date">
            </div>
          </div>

          <div class="eng-edit-footer">
            <button type="button" class="eng-edit-btn-cancel" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="eng-edit-btn-save" id="editTimelineSaveBtn">Save Changes</button>
          </div>
        </div>
      </form>
    </div>
  </div>
</div>
