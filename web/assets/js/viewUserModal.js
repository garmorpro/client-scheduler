document.addEventListener('DOMContentLoaded', () => {

  // --- Delay modal opening ---
  const userButtons = document.querySelectorAll('.view-user-btn[data-bs-target="#viewUserModal"]');

  userButtons.forEach(button => {
    button.addEventListener('click', (e) => {
      e.preventDefault(); // prevent immediate modal open
      const userId = button.getAttribute('data-user-id');

      // Delay opening by 2 seconds
      setTimeout(() => {
        const modalEl = document.getElementById('viewUserModal');
        const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
        modalEl.dataset.userId = userId; // attach userId
        modal.show();
      }, 2000);
    });
  });

  // --- Modal show listener ---
  const viewUserModal = document.getElementById('viewUserModal');
  if (!viewUserModal) {
    console.warn("Modal element #viewUserModal not found.");
    return;
  }

  viewUserModal.addEventListener('show.bs.modal', async () => {
    const userId = viewUserModal.dataset.userId;
    if (!userId) return;

    try {
      const response = await fetch(`get_user.php?user_id=${encodeURIComponent(userId)}`);
      if (!response.ok) throw new Error('Network response was not ok');
      const user = await response.json();

      console.log('Fetched user data:', user);

      // --- Utility functions ---
      function setText(id, text) {
        const el = document.getElementById(id);
        if (!el) return;
        el.textContent = text && text.toString().trim() ? text : '-';
      }

      function formatDate(dateString) {
        if (!dateString) return '-';
        const d = new Date(dateString);
        if (isNaN(d)) return '-';
        return `${d.getMonth() + 1}/${d.getDate()}/${d.getFullYear()}`;
      }

      function timeSince(dateString) {
        if (!dateString) return '-';
        const now = new Date();
        const past = new Date(dateString);
        if (isNaN(past)) return '-';

        let seconds = Math.floor((now - past) / 1000);
        if (seconds < 5) return 'just now';
        if (seconds < 60) return `${seconds}s ago`;

        const minutes = Math.floor(seconds / 60);
        if (minutes < 60) return `${minutes}m ago`;

        const hours = Math.floor(minutes / 60);
        if (hours < 24) return `${hours}h ago`;

        const days = Math.floor(hours / 24);
        if (days < 7) return `${days}d ago`;

        return formatDate(dateString);
      }

      function getAccessLevel(role) {
        switch ((role || '').toLowerCase()) {
          case 'admin': return 'Full Access';
          case 'manager': return 'High Access';
          case 'senior':
          case 'staff':
          case 'intern': return 'Restricted Access';
          default: return 'Unknown Access';
        }
      }

      function boolToEnabledDisabled(value) {
        return value == 1 ? 'Enabled' : 'Disabled';
      }

      // --- Populate user details ---
      const fullName = user.full_name || '-';
      const nameParts = fullName.trim().split(' ');
      const firstName = nameParts[0] || '-';
      const lastName = nameParts.length > 1 ? nameParts.slice(1).join(' ') : '-';

      const initials = fullName
        .split(' ')
        .map(n => n.charAt(0).toUpperCase())
        .slice(0, 2)
        .join('');

      setText('view_user_initials2', initials);
      setText('view_user_fullname2', fullName);
      setText('view_user_fullname_intro2', fullName);
      setText('full_name_1', fullName);
      setText('view_first_name_detail2', firstName);
      setText('view_last_name_detail2', lastName);
      setText('view_email2', user.email);
      setText('view_user_role2', user.role);
      setText('view_first_name_detail', firstName);
      setText('view_email_detail2', user.email);
      setText('view_status2', user.status);
      setText('view_acct_status2', user.status);
      setText('view_acct_created2', formatDate(user.created_at));
      setText('view_acct_last_active2', formatDate(user.last_active));
      setText('view_acct_role2', user.role);
      setText('view_acct_access_level2', getAccessLevel(user.role));

      // --- MFA ---
      const mfaEl = document.getElementById('view_acct_mfa2');
      if (mfaEl) {
        const statusText = boolToEnabledDisabled(user.mfa_enabled);
        mfaEl.textContent = statusText;
        mfaEl.classList.remove('text-success', 'text-danger');
        mfaEl.classList.add(statusText === 'Enabled' ? 'text-success' : 'text-danger');
      }

      // --- Status badge ---
      const statusEl = document.getElementById('view_status2');
      if (statusEl) {
        statusEl.classList.remove('active', 'inactive');
        statusEl.classList.add((user.status || '').toLowerCase() === 'active' ? 'active' : 'inactive');
      }

      // --- Recent activities ---
      const activityList = document.getElementById('view_recent_activity2');
      if (activityList) {
        activityList.innerHTML = '';
        if (Array.isArray(user.recent_activities) && user.recent_activities.length > 0) {
          user.recent_activities.forEach(act => {
            const card = document.createElement('div');
            card.className = 'activity-card';

            const desc = document.createElement('div');
            desc.className = 'activity-description';
            desc.title = act.description || '';
            desc.textContent = act.description || '(no description)';

            const time = document.createElement('div');
            time.className = 'activity-time';
            time.textContent = timeSince(act.created_at);

            card.appendChild(desc);
            card.appendChild(time);
            activityList.appendChild(card);
          });
        } else {
          const empty = document.createElement('div');
          empty.className = 'text-muted px-3';
          empty.textContent = 'No recent activity found.';
          activityList.appendChild(empty);
        }
      }

    } catch (error) {
      console.error('Failed to load user data:', error);
    }
  });
});
