document.addEventListener('DOMContentLoaded', () => {
  const updateProfileDetailsModal = document.getElementById('updateProfileDetailsModal');
  if (!updateProfileDetailsModal) return;
  const updateProfileForm = document.getElementById('updateProfileForm');
  const saveBtn = document.getElementById('updateProfileSaveBtn');

  updateProfileDetailsModal.addEventListener('show.bs.modal', async (event) => {
    const button = event.relatedTarget;
    const userId = button?.getAttribute('data-user-id');

    updateProfileForm.reset();
    if (!userId) return;

    try {
      const response = await fetch(`get_user.php?user_id=${encodeURIComponent(userId)}`);
      if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
      const user = await response.json();

      updateProfileForm.querySelector('#update_user_id').value = user.user_id ?? '';
      updateProfileForm.querySelector('#update_first_name').value = user.first_name ?? '';
      updateProfileForm.querySelector('#update_last_name').value = user.last_name ?? '';
      updateProfileForm.querySelector('#update_email').value = user.email ?? '';
    } catch (error) {
      console.error('Failed to load user data:', error);
    }
  });

  updateProfileForm.addEventListener('submit', async (e) => {
    e.preventDefault();

    const payload = {
      user_id: updateProfileForm.querySelector('#update_user_id').value,
      first_name: updateProfileForm.querySelector('#update_first_name').value.trim(),
      last_name: updateProfileForm.querySelector('#update_last_name').value.trim()
    };

    if (!payload.first_name || !payload.last_name) return;

    saveBtn.disabled = true;
    const originalLabel = saveBtn.textContent;
    saveBtn.textContent = 'Saving...';

    try {
      const res = await fetch('update_profile.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
      });
      const data = await res.json().catch(() => null);

      if (!res.ok || !data || !data.success) {
        throw new Error((data && data.error) || 'Please try again.');
      }

      bootstrap.Modal.getInstance(updateProfileDetailsModal).hide();

      if (typeof Swal !== 'undefined') {
        Swal.fire({
          icon: 'success', title: 'Profile updated',
          timer: 1400, showConfirmButton: false
        }).then(() => location.reload());
      } else {
        location.reload();
      }
    } catch (error) {
      console.error('Failed to update profile:', error);
      if (typeof Swal !== 'undefined') {
        Swal.fire({ icon: 'error', title: 'Could not update profile', text: error.message });
      } else {
        alert('Could not update profile: ' + error.message);
      }
    } finally {
      saveBtn.disabled = false;
      saveBtn.textContent = originalLabel;
    }
  });
});
