document.addEventListener('DOMContentLoaded', () => {
        const configureBtn = document.getElementById('configureSecurityBtn');
        const modalEl = document.getElementById('securityPolicyConfigModal');
        const modal = new bootstrap.Modal(modalEl);
        const form = document.getElementById('securityPolicyConfigForm');

        // Open modal on Configure button click
        configureBtn.addEventListener('click', (e) => {
          e.preventDefault();
          modal.show();
        });

        // Helper to get form data and convert checkboxes to "true"/"false"
        function getFormData(formElement) {
          const data = {};
          const formData = new FormData(formElement);
          for (const [key, value] of formData.entries()) {
            data[key] = value;
          }

          // Convert unchecked checkboxes (not present in formData) to false
          formElement.querySelectorAll('input[type="checkbox"]').forEach(cb => {
            if (!formData.has(cb.name)) {
              data[cb.name] = "false";
            } else {
              data[cb.name] = "true";
            }
          });

          return data;
        }

        // Handle form submission via AJAX
        form.addEventListener('submit', async (e) => {
          e.preventDefault();

          const submitBtn = form.querySelector('button[type="submit"]');
          submitBtn.disabled = true;
          submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Saving...';

          const settings = getFormData(form);
          const payload = {
            setting_master_key: 'security_policy',
            settings: settings
          };

          try {
            const response = await fetch('settings_backend.php', {
              method: 'POST',
              headers: { 'Content-Type': 'application/json' },
              body: JSON.stringify(payload),
            });
            const result = await response.json();

            if (result.success) {
            //   alert('Security policy settings saved successfully!');
              modal.hide();
              // Optionally refresh page or update UI here
            } else {
              alert('Error saving settings: ' + (result.error || 'Unknown error'));
            }
          } catch (err) {
            alert('Network error: ' + err.message);
          } finally {
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="bi bi-save me-3"></i>Save Settings';
          }
        });
      });