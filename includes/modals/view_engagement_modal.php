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

<!-- Independence - a self-attestation popup opened from your own row in the
     Team card. Always about the person looking at it, so there's no name
     field - just which engagement/client it's for. -->
<div class="modal fade" id="independenceModal" tabindex="-1" aria-labelledby="independenceModalTitle" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" style="max-width: 420px;">
    <div class="modal-content">
      <div class="modal-body position-relative p-0">
        <button type="button" class="btn-close emp-modal-close" data-bs-dismiss="modal" aria-label="Close"></button>

        <div class="eng-edit-hero">
          <div class="eng-edit-title" id="independenceModalTitle">Independence</div>
          <p class="text-muted" id="independenceModalSubtitle" style="font-size: 12.5px; margin: 4px 0 0;">Confirm your independence from this client</p>
        </div>

        <div class="eng-edit-body">
          <div class="eng-indep-options" id="independenceOptions">
            <label class="eng-indep-option yes">
              <input type="radio" name="independentValue" value="Y">
              <span class="eng-indep-option-dot"></span>
              <span class="eng-indep-option-label">Yes, independent</span>
            </label>
            <label class="eng-indep-option no">
              <input type="radio" name="independentValue" value="N">
              <span class="eng-indep-option-dot"></span>
              <span class="eng-indep-option-label">No, not independent</span>
            </label>
            <label class="eng-indep-option unset">
              <input type="radio" name="independentValue" value="">
              <span class="eng-indep-option-dot"></span>
              <span class="eng-indep-option-label">Not answered yet</span>
            </label>
          </div>
        </div>

        <div class="eng-edit-footer">
          <button type="button" class="eng-edit-btn-cancel" data-bs-dismiss="modal">Cancel</button>
          <button type="button" class="eng-edit-btn-save" id="independenceSaveBtn">Save</button>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Add Team Member - a genuine 0-hour staffing row (see add_team_member.php),
     same mechanism Master Schedule uses to staff someone, just with no hours
     yet. Lets someone be added to the roster (and become DOL-assignable)
     even without logged hours - e.g. a person promoted to manager after
     doing real senior/staff-level work on the engagement. -->
<div class="modal fade" id="addTeamMemberModal" tabindex="-1" aria-labelledby="addTeamMemberModalTitle" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" style="max-width: 420px;">
    <div class="modal-content">
      <div class="modal-body position-relative p-0">
        <button type="button" class="btn-close emp-modal-close" data-bs-dismiss="modal" aria-label="Close"></button>

        <div class="eng-edit-hero">
          <div class="eng-edit-title" id="addTeamMemberModalTitle">Add Team Member</div>
          <p class="text-muted" id="addTeamMemberModalSubtitle" style="font-size: 12.5px; margin: 4px 0 0;">Stage someone on this engagement with no hours yet</p>
        </div>

        <div class="eng-edit-body">
          <div class="eng-edit-field">
            <label for="addTeamMemberEmployeeSelect">Employee</label>
            <select class="eng-edit-input" id="addTeamMemberEmployeeSelect">
              <option value="">Select employee&hellip;</option>
            </select>
          </div>
          <div class="eng-edit-field d-none" id="addTeamMemberAuditTypeWrap">
            <label>Audit Type(s) <span class="eng-edit-optional">(optional - leave all unchecked if not specific to one type)</span></label>
            <div class="eng-audit-type-list" id="addTeamMemberAuditTypeList"></div>
          </div>
        </div>

        <div class="eng-edit-footer">
          <button type="button" class="eng-edit-btn-cancel" data-bs-dismiss="modal">Cancel</button>
          <button type="button" class="eng-edit-btn-save" id="addTeamMemberSaveBtn">Add</button>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Planning Doc preview - "View current" opens this instead of triggering
     a download. pdf/png/jpg/jpeg render inline (iframe/img against
     download_planning_doc.php?mode=view); everything else shows a
     "download instead" message, since browsers can't render Office formats
     natively without external infrastructure this app doesn't have. -->
<div class="modal fade" id="planningDocPreviewModal" tabindex="-1" aria-labelledby="planningDocPreviewModalTitle" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" style="max-width: 760px;">
    <div class="modal-content">
      <div class="modal-body position-relative p-0">
        <button type="button" class="btn-close emp-modal-close" data-bs-dismiss="modal" aria-label="Close"></button>

        <div class="eng-edit-hero">
          <div class="eng-edit-title" id="planningDocPreviewModalTitle">Planning Doc</div>
        </div>

        <div class="eng-edit-body" id="planningDocPreviewBody"></div>

        <div class="eng-edit-footer">
          <a href="#" id="planningDocDownloadLink" class="eng-edit-btn-cancel" target="_blank" rel="noopener">
            <i class="bi bi-download"></i> Download
          </a>
          <button type="button" class="eng-edit-btn-save" data-bs-dismiss="modal">Close</button>
        </div>
      </div>
    </div>
  </div>
</div>
