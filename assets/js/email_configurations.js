document.addEventListener('DOMContentLoaded', () => {
    // ----------------------------
    // Modal show/hide logic
    // ----------------------------
    const configureBtn = document.getElementById('configureEmailBtn');
    const modalEl = document.getElementById('emailNotifConfigModal');
    const modal = new bootstrap.Modal(modalEl);

    configureBtn.addEventListener('click', (e) => {
      e.preventDefault();
      modal.show();
    });

    // ----------------------------
    // Elements for test email
    // ----------------------------
    const testEmailInput = document.getElementById('testEmail');
    const sendTestEmailBtn = document.getElementById('sendTestEmailBtn');
    const testEmailStatus = document.getElementById('testEmailStatus');

    // Enable/disable Send Test Email button
    testEmailInput.addEventListener('input', () => {
      const email = testEmailInput.value.trim();
      if (email.length > 0) {
        sendTestEmailBtn.classList.remove('disabled');
        sendTestEmailBtn.style.pointerEvents = 'auto';
        sendTestEmailBtn.style.opacity = '1';
      } else {
        sendTestEmailBtn.classList.add('disabled');
        sendTestEmailBtn.style.pointerEvents = 'none';
        sendTestEmailBtn.style.opacity = '0.5';
      }
      testEmailStatus.classList.add('d-none');
      testEmailStatus.textContent = '';
    });

    // ----------------------------
    // Send Test Email click
    // ----------------------------
    sendTestEmailBtn.addEventListener('click', async (e) => {
      e.preventDefault();
      const email = testEmailInput.value.trim();
      if (!email) return;

      // Show immediate feedback
      testEmailStatus.textContent = 'Sending test email...';
      testEmailStatus.classList.remove('d-none', 'text-success', 'text-danger');
      testEmailStatus.classList.add('text-info');

      try {
        const resp = await fetch('../includes/send_test_email.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ test_email: email })
        });

        const text = await resp.text();
        let result;

        // Safely parse JSON
        try {
          result = JSON.parse(text);
        } catch (err) {
          testEmailStatus.textContent = 'Server returned invalid response';
          testEmailStatus.classList.remove('text-info');
          testEmailStatus.classList.add('text-danger');
          console.error('JSON parse error:', err, text);
          return;
        }

        if (result.success) {
          testEmailStatus.textContent = result.message || 'Test email sent successfully!';
          testEmailStatus.classList.remove('text-info', 'text-danger');
          testEmailStatus.classList.add('text-success');
        } else {
          testEmailStatus.textContent = result.message || 'Failed to send test email';
          testEmailStatus.classList.remove('text-info', 'text-success');
          testEmailStatus.classList.add('text-danger');
        }

      } catch (err) {
        testEmailStatus.textContent = 'Network error: ' + err.message;
        testEmailStatus.classList.remove('text-info', 'text-success');
        testEmailStatus.classList.add('text-danger');
        console.error(err);
      }
    });

    // ----------------------------
    // Save Settings form submit
    // ----------------------------
    const emailForm = document.getElementById('emailNotifConfigForm');
    emailForm.addEventListener('submit', async (e) => {
      e.preventDefault();

      const formData = new FormData(e.target);
      const data = Object.fromEntries(formData.entries());
      data.enable_email_notifications = formData.get('enable_email_notifications') === 'on' ? 'true' : 'false';

      const payload = {
        setting_master_key: 'email',
        settings: data
      };

      console.log('Submitting email settings:', payload);

      try {
        const resp = await fetch('settings_backend.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(payload)
        });

        const text = await resp.text();
        let result;

        try {
          result = JSON.parse(text);
        } catch (err) {
          alert('Server returned invalid response while saving settings');
          console.error('JSON parse error:', err, text);
          return;
        }

        if (result.success) {
          modal.hide();
          console.log('Email settings saved successfully.');
        } else {
          alert('Failed to save settings: ' + (result.error || 'Unknown error'));
        }
      } catch (err) {
        alert('Network error: ' + err.message);
        console.error(err);
      }
    });
  });