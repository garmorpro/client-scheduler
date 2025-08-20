document.addEventListener('DOMContentLoaded', () => {
  // Delay modal opening
  const viewButtons = document.querySelectorAll('.view-user-btn');
  viewButtons.forEach(button => {
    button.addEventListener('click', (e) => {
      e.preventDefault(); // Prevent immediate modal open
      const targetModalId = button.getAttribute('data-bs-target');

      setTimeout(() => {
        const modalEl = document.querySelector(targetModalId);
        if (modalEl) {
          const bsModal = new bootstrap.Modal(modalEl);
          bsModal.show();
        }
      }, 2000); // 2-second delay
    });
  });

  // Existing modal population logic
  const viewUserModal = document.getElementById('viewUserModal');
  if (!viewUserModal) {
    console.warn("Modal element #viewUserModal not found.");
    return;
  }

  viewUserModal.addEventListener('show.bs.modal', async (event) => {
    const button = event.relatedTarget;
    const userId = button?.getAttribute('data-user-id');
    if (!userId) return;

    try {
      const response = await fetch(`get_user.php?user_id=${encodeURIComponent(userId)}`);
      if (!response.ok) throw new Error('Network response was not ok');
      const user = await response.json();

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
        if (isNaN(past.getTime())) return '-';
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

      const fullName = user.full_name || '-';
      const initials = fullName.split(' ').map(n => n.charAt(0).toUpperCase()).slice(0,2).join('');

      setText('view_user_initials2', initials);
      setText('view_user_fullname2', fullName);
      setText('view_user_fullname_intro2', fullName);
      setText('full_name_1', fullName);
      setText('full_name_4', fullName);
      setText('full_name_5', fullName);
      setText('view_email2', user.email);
      setText('view_user_role2', user.role);
      setText('view_first_name_detail', fullName);
      setText('view_email_detail2', user.email);
      setText('view_status2', user.status);
      setText('view_acct_status2', user.status);
      setText('view_acct_created', formatDate(user.created));
      setText('view_acct_last_active2', formatDate(user.last_active));
      setText('view_acct_role2', user.role);
      setText('view_acct_access_level2', getAccessLevel(user.role));

      const mfaEl = document.getElementById('view_acct_mfa2');
      if (mfaEl) {
        const statusText = boolToEnabledDisabled(user.mfa_enabled);
        mfaEl.textContent = statusText;
        mfaEl.classList.remove('text-success', 'text-danger');
        mfaEl.classList.add(statusText === 'Enabled' ? 'text-success' : 'text-danger');
      }

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

      const statusEl = document.getElementById('view_status2');
      if (statusEl) {
        statusEl.classList.remove('active', 'inactive');
        statusEl.classList.add((user.status || '').toLowerCase() === 'active' ? 'active' : 'inactive');
      }

    } catch (error) {
      console.error('Failed to load user data:', error);
    }
  });
});