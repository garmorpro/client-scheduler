document.addEventListener('DOMContentLoaded', () => {
  const modal = document.getElementById('viewProfileModal');
  if (!modal) {
    console.error('viewProfileModal element not found');
    return;
  }

  modal.addEventListener('show.bs.modal', async (event) => {
    console.log('Modal show event triggered');

    const button = event.relatedTarget;
    const userId = button ? button.getAttribute('data-user-id') : null;
    console.log('userId from trigger:', userId);

    if (!userId) {
      console.warn('No userId provided, aborting modal population.');
      return;
    }

    try {
      const response = await fetch(`get_user.php?user_id=${encodeURIComponent(userId)}`);
      if (!response.ok) throw new Error('Network response was not ok');

      const user = await response.json();
      console.log('Fetched user data:', user);

      // Helper functions
      function setText(id, text) {
        const el = document.getElementById(id);
        if (!el) {
          console.warn(`Element with ID "${id}" not found.`);
          return;
        }
        el.textContent = text && text.toString().trim() ? text : '-';
        console.log(`Set #${id} textContent to:`, el.textContent);
      }

      function formatDate(dateString) {
        if (!dateString) return '-';
        const d = new Date(dateString);
        if (isNaN(d)) return '-';
        return `${d.getMonth()+1}/${d.getDate()}/${d.getFullYear()}`;
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
        switch(role?.toLowerCase()) {
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

      // Populate fields
      const fullName = user.full_name || '-';
      const initials = fullName.split(' ').map(n => n.charAt(0).toUpperCase()).slice(0,2).join('');

      setText('view_user_initials', initials);
      setText('view_user_fullname', fullName);
      setText('view_user_fullname_intro', fullName);
      setText('view_email', user.email);
      setText('view_user_role', user.role);
      setText('view_first_name_detail', fullName);
      setText('view_last_name_detail', '');
      setText('view_email_detail', user.email);
      setText('view_status', user.status);
      setText('view_acct_status', user.status);
      setText('view_acct_created', formatDate(user.created));
      setText('view_acct_last_active', formatDate(user.last_active));
      setText('view_acct_role', user.role);
      setText('view_acct_access_level', getAccessLevel(user.role));

      // MFA
      const mfaEl = document.getElementById('view_acct_mfa');
      if (mfaEl) {
        const statusText = boolToEnabledDisabled(user.mfa_enabled);
        mfaEl.textContent = statusText;
        mfaEl.classList.remove('text-success', 'text-danger');
        mfaEl.classList.add(statusText === 'Enabled' ? 'text-success' : 'text-danger');
      }

      // Status badge
      const statusEl = document.getElementById('view_status');
      if (statusEl) {
        statusEl.classList.remove('active','inactive');
        statusEl.classList.add(user.status?.toLowerCase() === 'active' ? 'active' : 'inactive');
      }

      // Recent activity
      const activityList = document.getElementById('view_recent_activity');
      if (activityList) {
        activityList.innerHTML = '';
        if (user.recent_activities && user.recent_activities.length) {
          user.recent_activities.forEach(act => {
            const card = document.createElement('div');
            card.className = 'activity-card';
            const desc = document.createElement('div');
            desc.className = 'activity-description';
            desc.title = act.description || '';
            desc.textContent = act.description || '(no description)';
            const time = document.createElement('div');
            time.className = 'activity-time';
            const createdAt = new Date(act.created_at);
            time.textContent = isNaN(createdAt) ? 'Invalid date' : timeSince(createdAt);
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

    } catch (err) {
      console.error('Failed to load user data:', err);
    }
  });
});
