document.addEventListener("DOMContentLoaded", function () {
      const statusDisplay = document.getElementById('engagement-status-display');
      const statusSelect = document.getElementById('engagement-status-select');
      const engagementIdInput = document.getElementById('engagementId');
      const modal = document.getElementById('clientDetailsModal');

      // Listen for clicks on buttons with engagement ID (Opening modal)
      const engagementButtons = document.querySelectorAll('.btn[data-engagement-id]');
      engagementButtons.forEach(button => {
          button.addEventListener('click', function () {
              const engagementId = this.getAttribute('data-engagement-id');
              engagementIdInput.value = engagementId; // Set engagement ID in the hidden input field
              console.log('Set Engagement ID:', engagementId);  // Debugging
          });
      });

      // Fetch engagement details when the modal is opened
      modal.addEventListener('shown.bs.modal', function () {
          const engagementId = engagementIdInput.value;

          console.log('Engagement ID on modal open:', engagementId); // Debugging

          if (!engagementId) {
              console.error('Engagement ID is not set.');
              return;
          }

          // Fetch engagement details
          fetch(`get-engagement-details.php?id=${engagementId}`)
      .then(response => response.text()) // Use text() to get the raw response for debugging
      .then(data => {
          console.log('Response:', data); // Check the raw response in the console
          try {
              const jsonData = JSON.parse(data); // Try to parse it manually
              console.log('Parsed JSON:', jsonData);
          } catch (error) {
              console.error('Error parsing JSON:', error);
          }
      })
      .catch(error => {
          console.error('Error fetching engagement details:', error);
          alert("Failed to fetch engagement details.");
      });
      });

      // Handle status change and update via AJAX
      statusSelect.addEventListener('change', function () {
          const newStatus = this.value;
          const engagementId = engagementIdInput.value;

          console.log('Updating Engagement ID:', engagementId, 'New Status:', newStatus); // Debugging

          // Send the status update to the server via AJAX
          fetch('update-engagement-status.php', {
              method: 'POST',
              headers: {
                  'Content-Type': 'application/json',
              },
              body: JSON.stringify({
                  engagement_id: engagementId,
                  status: newStatus,
              })
          })
          .then(response => response.json())
          .then(data => {
              if (data.success) {
                  // Update the status display with the new value
                  statusDisplay.textContent = capitalize(newStatus.replace('-', ' '));
                  statusDisplay.className = `badge ${getStatusClass(newStatus)}`;

                  // Optionally, hide the dropdown and show the status badge again
                  statusSelect.classList.add('d-none');
                  statusDisplay.classList.remove('d-none');
              } else {
                  alert('Failed to update status.');
              }
          })
          .catch(error => {
              console.error('Error updating status:', error);
              alert('Failed to update status.');
          });
      });

      // Helpers
      function capitalize(str) {
          return str.charAt(0).toUpperCase() + str.slice(1);
      }

      function getStatusClass(status) {
          switch (status) {
              case 'confirmed': return 'bg-success';
              case 'pending': return 'bg-warning text-dark';
              case 'not_confirmed': return 'bg-danger';
              default: return 'bg-secondary';
          }
      }
  });