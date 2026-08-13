<?php require_once __DIR__ . '/../csrf.php'; ?>
<!-- Delete Client Modal - restyled on the eng-edit-* look established for
     Add/Edit Engagement, reusing the app's existing .detail-row/-label/
     -value classes for the client-details box. -->
<div class="modal fade" id="deleteClientModal" tabindex="-1" aria-labelledby="deleteClientModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" style="max-width: 480px;">
    <div class="modal-content">
      <div class="modal-body position-relative p-0">
        <button type="button" class="btn-close emp-modal-close" data-bs-dismiss="modal" aria-label="Close"></button>

        <div class="eng-edit-body">
          <div class="eng-edit-modal-title" id="deleteClientModalLabel"><i class="bi bi-exclamation-triangle text-danger"></i> Delete Client</div>

          <p style="font-size: 13px; color: #6b7570; margin-bottom: 14px;">
            Are you sure you want to permanently delete <strong id="deleteClientName"></strong>?
            This action <strong style="color:#c0392b;">cannot be undone</strong> and will remove all client data, engagement history, and related records.
          </p>

          <div class="eng-edit-field" style="border: 1px solid #e3e7e5; border-radius: 10px; padding: 12px 14px;">
            <div class="detail-row"><span class="detail-label">Client Name</span><span class="detail-value" id="deleteClientNameDetails"></span></div>
            <div class="detail-row"><span class="detail-label">Confirmed Engagements</span><span class="detail-value" id="deleteClientConfirmed"></span></div>
            <div class="detail-row"><span class="detail-label">Total Engagements</span><span class="detail-value" id="deleteClientTotal"></span></div>
          </div>

          <div class="eng-edit-import-banner d-none" id="deleteClientBlocked" style="border-left-color:#c0392b; background:#fbe8e6; color:#c0392b;">
            This client still has engagements. Remove or reassign them before deleting the client.
          </div>
        </div>

        <div class="eng-edit-footer">
          <button type="button" class="eng-edit-btn-cancel" data-bs-dismiss="modal">Cancel</button>
          <form id="deleteClientForm" method="POST" action="delete_client.php" style="margin:0;">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">
            <input type="hidden" name="client_id" id="deleteClientId">
            <button type="submit" class="eng-edit-btn-save" id="deleteClientSubmitBtn" style="background:#c0392b;">Delete Client</button>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>
