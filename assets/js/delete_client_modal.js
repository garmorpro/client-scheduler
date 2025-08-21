document.addEventListener("DOMContentLoaded", function() {
    const deleteButtons = document.querySelectorAll(".delete-client-btn");
    const deleteModal = new bootstrap.Modal(document.getElementById("deleteClientModal"));

    deleteButtons.forEach(button => {
        button.addEventListener("click", () => {
            const clientId = button.getAttribute("data-client-id");
            const clientName = button.getAttribute("data-client-name");
            const confirmed = button.getAttribute("data-confirmed-engagements");
            const total = button.getAttribute("data-total-engagements");

            // Fill modal with client data
            document.getElementById("deleteClientId").value = clientId;
            document.getElementById("deleteClientName").textContent = `"${clientName}"`;
            document.getElementById("deleteClientNameDetails").textContent = clientName;
            document.getElementById("deleteClientConfirmed").textContent = confirmed;
            document.getElementById("deleteClientTotal").textContent = total;

            deleteModal.show();
        });
    });
});