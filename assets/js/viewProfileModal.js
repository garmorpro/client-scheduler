document.addEventListener('DOMContentLoaded', () => {
  const viewProfileModal = document.getElementById('viewProfileModal');
  // console.log('viewProfileModal element:', viewProfileModal);

  viewProfileModal.addEventListener('show.bs.modal', async (event) => {
    // console.log('viewProfileModal show.bs.modal triggered');
    
    const button = event.relatedTarget;
    // console.log('Related trigger element:', button);
    
    const userId = button ? button.getAttribute('data-user-id') : null;
    // console.log('userId from trigger:', userId);

    if (!userId) {
      // console.warn('No userId provided, aborting modal population.');
      return;
    }

    try {
      const response = await fetch(`get_user.php?user_id=${encodeURIComponent(userId)}`);
      // console.log('Fetch response:', response);

      if (!response.ok) throw new Error('Network response was not ok');

      const user = await response.json();
      // console.log('Fetched user data:', user);

      function setText(id, text) {
        const el = document.getElementById(id);
        if (!el) {
          // console.warn(`Element with ID "${id}" not found.`);
          return;
        }
        el.textContent = (text && text.toString().trim()) ? text : '-';
        // console.log(`Set #${id} textContent to:`, el.textContent);
      }

      function formatDate(dateString) {
        if (!dateString) return '-';
        const d = new Date(dateString);
        if (isNaN(d)) return '-';
        const month = d.getMonth() + 1;
        const day = d.getDate();
        const year = d.getFullYear();
        return `${month}/${day}/${year}`;
      }

      function timeSince(dateString) {
        if (!dateString) return '-';
        const now = new Date();
        const past = new Date(dateString);
        if (isNaN(past.getTime())) return '-';
        let seconds = Math.floor((now - past) / 1000);
        if (seconds < 0) seconds = 0;
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

      const fullName = user.full_name || '-';
const initials = fullName
    .split(' ')
    .map(name => name.charAt(0).toUpperCase())
    .slice(0, 2) // get at most first two initials
    .join('');

setText('view_user_initials', initials);

setText('full_name_1', fullName);
setText('full_name_2', fullName);
setText('full_name_3', fullName);
setText('full_name_4', fullName);
setText('full_name_5', fullName);
setText('view_email', user.email);
setText('view_user_role', user.role);

setText('view_first_name_detail', fullName); // if you still need a detail field
setText('view_last_name_detail', ''); // empty since no last_name exists
setText('view_email_detail', user.email);

setText('view_status', user.status);
setText('view_acct_status', user.status);
setText('view_acct_created', formatDate(user.created));
setText('view_acct_last_active', formatDate(user.last_active));

      function getAccessLevel(role) {
        switch(role.toLowerCase()) {
          case 'admin': return 'Full Access';
          case 'manager': return 'High Access';
          case 'senior': 
          case 'staff': 
          case 'intern': return 'Restricted Access';
          default: return 'Unknown Access';
        }
      }

      setText('view_acct_role', user.role);
      setText('view_acct_access_level', getAccessLevel(user.role || ''));

      function boolToEnabledDisabled(value) {
        return value == 1 ? 'Enabled' : 'Disabled';
      }

      const mfaEl = document.getElementById('view_acct_mfa');
      if (mfaEl) {
        const statusText = boolToEnabledDisabled(user.mfa_enabled);
        mfaEl.textContent = statusText;
        mfaEl.classList.remove('text-success', 'text-danger');
        if (statusText === 'Enabled') {
          mfaEl.classList.add('text-success');
        } else {
          mfaEl.classList.add('text-danger');
        }
        // console.log('MFA status updated:', statusText);
      }

      const activityList = document.getElementById('view_recent_activity');
      if (activityList) {
        activityList.innerHTML = '';
        if (user.recent_activities && user.recent_activities.length > 0) {
          user.recent_activities.forEach(act => {
            const card = document.createElement('div');
            card.className = 'activity-card';

            const desc = document.createElement('div');
            desc.className = 'activity-description';
            desc.title = act.description || '';
            desc.textContent = act.description || '(no description)';

            const time = document.createElement('div');
            time.className = 'activity-time';

            let createdAt = new Date(act.created_at);
            if (isNaN(createdAt.getTime())) {
              time.textContent = 'Invalid date';
            } else {
              time.textContent = timeSince(createdAt);
            }

            card.appendChild(desc);
            card.appendChild(time);
            activityList.appendChild(card);
          });
          // console.log('Recent activities populated:', user.recent_activities.length);
        } else {
          const empty = document.createElement('div');
          empty.className = 'text-muted px-3';
          empty.textContent = 'No recent activity found.';
          activityList.appendChild(empty);
          console.log('No recent activities found.');
        }
      } else {
        // console.warn("Element with id 'view_recent_activity' not found in DOM.");
      }

      const statusEl = document.getElementById('view_status');
      if (statusEl) {
        statusEl.classList.remove('active', 'inactive');
        if (user.status && user.status.toLowerCase() === 'active') {
          statusEl.classList.add('active');
        } else {
          statusEl.classList.add('inactive');
        }
        // console.log('User status class updated:', statusEl.className);
      }

    } catch (error) {
      console.error('Failed to load user data:', error);
    }
  });
});
