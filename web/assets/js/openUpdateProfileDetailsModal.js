document.addEventListener('DOMContentLoaded', () => {
  const viewProfileModal = document.getElementById('viewProfileModal');
  const updateProfileDetailsModal = document.getElementById('updateProfileDetailsModal');
  const updateProfileForm = document.getElementById('updateProfileForm');

  // Open Update User Modal when "Edit Profile" is clicked
  const editProfileBtn = document.querySelector('#viewProfileModal .btn[data-bs-target="#updateProfileDetailsModal"]');

  editProfileBtn.addEventListener('click', async () => {
    const userId = editProfileBtn.dataset.userId;
    if (!userId) {
      console.warn('No user ID found on edit button.');
      return;
    }

    console.log('Opening updateProfileDetailsModal for userId:', userId);

    try {
      const response = await fetch(`get_user.php?user_id=${encodeURIComponent(userId)}`);
      if (!response.ok) throw new Error('Network response was not ok');
      const user = await response.json();

      console.log('Fetched user data:', user);

      // Populate the form fields
      document.getElementById('update_user_id').value = user.id || '';
      document.getElementById('update_first_name').value = user.first_name || '';
      document.getElementById('update_last_name').value = user.last_name || '';
      document.getElementById('update_email').value = user.email || '';
      document.getElementById('update_role').value = user.role || '';
      document.getElementById('update_status').value = user.status || '';

      // Show the modal
      const modal = new bootstrap.Modal(updateProfileDetailsModal);
      modal.show();

    } catch (error) {
      console.error('Failed to fetch user data for update:', error);
    }
  });

  // Optional: handle form submission via AJAX instead of full page reload
  updateProfileForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    const formData = new FormData(updateProfileForm);

    try {
      const response = await fetch(updateProfileForm.action, {
        method: 'POST',
        body: formData,
      });

      const result = await response.json();
      console.log('Update result:', result);

      if (result.success) {
        alert('User updated successfully!');
        // Optionally close both modals
        bootstrap.Modal.getInstance(updateProfileDetailsModal).hide();
        bootstrap.Modal.getInstance(viewProfileModal).hide();
        // Optionally refresh user table or UI here
      } else {
        alert('Failed to update user: ' + (result.message || 'Unknown error'));
      }
    } catch (error) {
      console.error('Error updating user:', error);
      alert('Error updating user. See console for details.');
    }
  });
});
