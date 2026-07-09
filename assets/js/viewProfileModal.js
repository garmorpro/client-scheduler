document.addEventListener('DOMContentLoaded', () => {
  const viewProfileModal = document.getElementById('viewProfileModal');
  if (!viewProfileModal) return;

  function setText(id, text) {
    const el = document.getElementById(id);
    if (!el) return;
    el.textContent = (text && text.toString().trim()) ? text : '-';
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

  viewProfileModal.addEventListener('show.bs.modal', async (event) => {
    const button = event.relatedTarget;
    const userId = button ? button.getAttribute('data-user-id') : null;
    if (!userId) return;

    try {
      const response = await fetch(`get_user.php?user_id=${encodeURIComponent(userId)}`);
      if (!response.ok) throw new Error('Network response was not ok');
      const user = await response.json();

      const fullName = user.full_name || '-';
      const initials = fullName.split(' ').map(n => n.charAt(0).toUpperCase()).slice(0, 2).join('');
      const nameParts = fullName.trim().split(/\s+/);
      const firstName = nameParts[0] || '';
      const lastName = nameParts.length > 1 ? nameParts[nameParts.length - 1] : '';

      setText('pd_avatar', initials);
      setText('pd_name', fullName);
      setText('pd_email', user.email);
      setText('pd_role_pill', user.role);
      setText('pd_status_text', user.status);

      setText('pd_first_name', firstName);
      setText('pd_last_name', lastName);
      setText('pd_email_detail', user.email);

      setText('pd_created', formatDate(user.created_at));
      setText('pd_last_active', formatDate(user.last_active));
      setText('pd_status_detail', user.status);

      setText('pd_role_detail', user.role);
      setText('pd_access_level', getAccessLevel(user.role));

      const statusPill = document.getElementById('pd_status_pill');
      statusPill.classList.remove('active', 'inactive');
      statusPill.classList.add((user.status || '').toLowerCase() === 'active' ? 'active' : 'inactive');

      const activityList = document.getElementById('pd_activity_list');
      activityList.innerHTML = '';
      if (Array.isArray(user.recent_activities) && user.recent_activities.length > 0) {
        user.recent_activities.forEach(act => {
          const row = document.createElement('div');
          row.className = 'pd-activity-row';
          row.innerHTML = `
            <span>${act.description || '(no description)'}</span>
            <span class="pd-activity-time">${timeSince(act.created_at)}</span>
          `;
          activityList.appendChild(row);
        });
      } else {
        activityList.innerHTML = '<div class="pd-activity-empty">No recent activity found.</div>';
      }
    } catch (error) {
      console.error('Failed to load user data:', error);
    }
  });
});
