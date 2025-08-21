<div class="modal fade" id="deleteClientModal" tabindex="-1" aria-labelledby="deleteClientModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg">
      <div class="modal-header bg-light">
        <h5 class="modal-title fw-bold" id="deleteClientModalLabel">
          <i class="bi bi-exclamation-triangle text-danger me-2"></i> Delete Client
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body">
        <p class="mb-3">
          Are you sure you want to permanently delete <strong id="deleteClientName"></strong>? 
          This action <span class="text-danger fw-semibold">cannot be undone</span> and will remove all client data, engagement history, and related records.
        </p>

        <div class="border rounded p-3 bg-light">
          <strong>Client Details</strong><br>
          <span>Client Name: <span class="text-muted" id="deleteClientNameDetails"></span></span><br>
          <span>Confirmed Engagements: <span class="text-muted" id="deleteClientConfirmed"></span></span><br>
          <span>Total Engagements: <span class="text-muted" id="deleteClientTotal"></span></span>
        </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <form id="deleteClientForm" method="POST" action="delete_client.php">
          <input type="hidden" name="client_id" id="deleteClientId">
          <button type="submit" class="btn btn-danger">Delete Client</button>
        </form>
      </div>
    </div>
  </div>
</div>