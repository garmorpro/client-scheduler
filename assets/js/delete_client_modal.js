document.addEventListener("DOMContentLoaded", function() {
    const deleteButtons = document.querySelectorAll(".delete-client-btn");
    const deleteModal = new bootstrap.Modal(document.getElementById("deleteClientModal"));

    deleteButtons.forEach(button => {
        button.addEventListener("click", () => {
            const clientId = button.getAttribute("data-client-id");
            const clientName = button.getAttribute("data-client-name");
            const confirmed = button.getAttribute("data-confirmed-engagements");
            const total = parseInt(button.getAttribute("data-total-engagements"), 10) || 0;

            // Fill modal with client data
            document.getElementById("deleteClientId").value = clientId;
            document.getElementById("deleteClientName").textContent = `"${clientName}"`;
            document.getElementById("deleteClientNameDetails").textContent = clientName;
            document.getElementById("deleteClientConfirmed").textContent = confirmed;
            document.getElementById("deleteClientTotal").textContent = total;

            // Deleting a client with any engagements would orphan their schedule
            // entries - block it client-side too (the server enforces this
            // regardless, this just avoids the round trip).
            const blockedNotice = document.getElementById("deleteClientBlocked");
            const submitBtn = document.getElementById("deleteClientSubmitBtn");
            const hasEngagements = total > 0;
            blockedNotice.classList.toggle("d-none", !hasEngagements);
            submitBtn.disabled = hasEngagements;

            deleteModal.show();
        });
    });
});