document.addEventListener('DOMContentLoaded', () => {
  const viewProfileModal = document.getElementById('viewProfileModal');
  if (!viewProfileModal) return;

  const canUnassign = !!document.getElementById('pf_unassign_all_btn');
  let currentUserId = null;
  let allEngagements = [];
  let currentEngPage = 1;
  const ENG_ROWS_PER_PAGE = 10;

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

  function roleLabel(role) {
    const r = (role || '').toLowerCase();
    if (r === 'crm_team') return 'CRM Team';
    if (!r) return '';
    return r.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
  }

  // Tabs
  viewProfileModal.querySelectorAll('.ud-tab').forEach(tab => {
    tab.addEventListener('click', () => {
      viewProfileModal.querySelectorAll('.ud-tab').forEach(t => t.classList.remove('active'));
      viewProfileModal.querySelectorAll('.ud-panel').forEach(p => p.classList.remove('active'));
      tab.classList.add('active');
      document.getElementById(`pf_panel_${tab.dataset.pfTab}`).classList.add('active');
    });
  });

  function renderEngagementsPage(page) {
    currentEngPage = page;
    const list = document.getElementById('pf_eng_list');
    const paginationEl = document.getElementById('pf_eng_pagination');

    list.innerHTML = '';
    if (allEngagements.length === 0) {
      list.innerHTML = '<div class="eng-empty">No engagements assigned</div>';
      paginationEl.style.display = 'none';
      return;
    }

    const totalPages = Math.ceil(allEngagements.length / ENG_ROWS_PER_PAGE);
    const start = (page - 1) * ENG_ROWS_PER_PAGE;
    const pageItems = allEngagements.slice(start, start + ENG_ROWS_PER_PAGE);

    pageItems.forEach(eng => {
      const wrap = document.createElement('div');
      wrap.className = 'eng-row-wrap';

      const weeks = (eng.weeks || []).slice().sort((a, b) => (a.week_start || '').localeCompare(b.week_start || ''));
      const weeksHtml = weeks.map(w => `
        <div class="eng-week-row">
          <span>Week of ${formatDate(w.week_start)}</span>
          <span>${w.hours}h</span>
        </div>
      `).join('');

      wrap.innerHTML = `
        <div class="eng-row clickable status-${eng.status}" data-weeks-toggle>
          <div class="eng-dot"></div>
          <div class="eng-name">${eng.client_name}</div>
          <div class="eng-hours">${eng.total_hours}h</div>
          <i class="bi bi-chevron-down eng-weeks-chevron"></i>
          ${canUnassign ? `<button type="button" class="eng-unassign-btn" title="Unassign" data-engagement-id="${eng.engagement_id}" data-client-name="${eng.client_name}"><i class="bi bi-trash"></i></button>` : ''}
        </div>
        <div class="eng-weeks-panel">
          ${weeksHtml || '<div class="eng-week-row text-muted">No weekly hours recorded</div>'}
          <div class="eng-week-row eng-week-total">
            <span>Total</span>
            <span>${eng.total_hours}h</span>
          </div>
        </div>
      `;
      list.appendChild(wrap);
    });

    if (totalPages <= 1) {
      paginationEl.style.display = 'none';
      return;
    }

    paginationEl.style.display = 'flex';
    paginationEl.innerHTML = '';

    function addPageItem(label, disabled, active, onClick) {
      const li = document.createElement('li');
      li.className = 'page-item' + (disabled ? ' disabled' : '') + (active ? ' active' : '');
      const a = document.createElement('a');
      a.className = 'page-link';
      a.href = '#';
      a.textContent = label;
      if (!disabled) a.addEventListener('click', e => { e.preventDefault(); onClick(); });
      li.appendChild(a);
      paginationEl.appendChild(li);
    }

    addPageItem('Prev', page === 1, false, () => renderEngagementsPage(page - 1));
    for (let i = 1; i <= totalPages; i++) {
      addPageItem(i, false, i === page, () => renderEngagementsPage(i));
    }
    addPageItem('Next', page === totalPages, false, () => renderEngagementsPage(page + 1));
  }

  function renderEngagements(engagements) {
    allEngagements = engagements;
    const totalHours = engagements.reduce((sum, e) => sum + e.total_hours, 0);

    setText('pf_stat_eng_count', engagements.length);
    setText('pf_stat_hours', totalHours);
    setText('pf_tab_eng_count', engagements.length);
    setText('pf_eng_hint', `${engagements.length} active assignment${engagements.length === 1 ? '' : 's'}`);

    const unassignAllBtn = document.getElementById('pf_unassign_all_btn');
    if (unassignAllBtn) unassignAllBtn.disabled = engagements.length === 0;

    renderEngagementsPage(1);
  }

  async function loadEngagements(userId) {
    try {
      const res = await fetch(`get_user_engagements.php?user_id=${encodeURIComponent(userId)}`);
      const data = await res.json();
      renderEngagements(data.engagements || []);
    } catch (err) {
      console.error('Failed to load engagements', err);
      renderEngagements([]);
    }
  }

  function notify(title, text) {
    if (typeof Swal !== 'undefined') {
      Swal.fire({ icon: 'error', title, text });
    } else {
      alert(`${title}: ${text}`);
    }
  }

  async function doUnassign(url, body) {
    try {
      const res = await fetch(url, {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(body)
      });
      const data = await res.json().catch(() => null);
      if (!res.ok || !data || !data.success) {
        notify('Could not unassign', (data && data.error) || 'Please try again.');
        return;
      }
      loadEngagements(currentUserId);
    } catch (err) {
      console.error('Failed to unassign', err);
    }
  }

  document.getElementById('pf_eng_list').addEventListener('click', (e) => {
    if (e.target.closest('.eng-unassign-btn')) return;
    const toggle = e.target.closest('[data-weeks-toggle]');
    if (!toggle) return;
    toggle.closest('.eng-row-wrap').classList.toggle('expanded');
  });

  if (canUnassign) {
    document.getElementById('pf_eng_list').addEventListener('click', (e) => {
      const btn = e.target.closest('.eng-unassign-btn');
      if (!btn || !currentUserId) return;
      const engagementId = btn.dataset.engagementId;
      const clientName = btn.dataset.clientName;
      if (typeof Swal !== 'undefined') {
        Swal.fire({
          icon: 'warning', title: 'Unassign engagement?',
          text: `Remove all assigned hours for ${clientName}.`,
          showCancelButton: true, confirmButtonText: 'Unassign', confirmButtonColor: '#c0392b'
        }).then(result => { if (result.isConfirmed) doUnassign('unassign_user_engagement.php', { user_id: currentUserId, engagement_id: engagementId }); });
      } else if (confirm(`Unassign ${clientName}?`)) {
        doUnassign('unassign_user_engagement.php', { user_id: currentUserId, engagement_id: engagementId });
      }
    });

    document.getElementById('pf_unassign_all_btn').addEventListener('click', () => {
      if (!currentUserId) return;
      if (typeof Swal !== 'undefined') {
        Swal.fire({
          icon: 'warning', title: 'Unassign all engagements?',
          text: 'This removes every assigned hour across all engagements.',
          showCancelButton: true, confirmButtonText: 'Unassign All', confirmButtonColor: '#c0392b'
        }).then(result => { if (result.isConfirmed) doUnassign('unassign_all_user_engagements.php', { user_id: currentUserId }); });
      } else if (confirm('Unassign all engagements?')) {
        doUnassign('unassign_all_user_engagements.php', { user_id: currentUserId });
      }
    });
  }

  viewProfileModal.addEventListener('show.bs.modal', async (event) => {
    const button = event.relatedTarget;
    const userId = button ? button.getAttribute('data-user-id') : null;
    if (!userId) return;
    currentUserId = userId;

    // Reset to Overview tab on every open
    viewProfileModal.querySelectorAll('.ud-tab').forEach(t => t.classList.remove('active'));
    viewProfileModal.querySelectorAll('.ud-panel').forEach(p => p.classList.remove('active'));
    viewProfileModal.querySelector('.ud-tab[data-pf-tab="overview"]').classList.add('active');
    document.getElementById('pf_panel_overview').classList.add('active');

    loadEngagements(userId);

    try {
      const response = await fetch(`get_user.php?user_id=${encodeURIComponent(userId)}`);
      if (!response.ok) throw new Error('Network response was not ok');
      const user = await response.json();

      const fullName = user.full_name || '-';
      const initials = fullName.split(' ').map(n => n.charAt(0).toUpperCase()).slice(0, 2).join('');
      const nameParts = fullName.trim().split(/\s+/);
      const firstName = nameParts[0] || '';
      const lastName = nameParts.length > 1 ? nameParts[nameParts.length - 1] : '';

      setText('pf_avatar', initials);
      setText('pf_name', fullName);
      setText('pf_email', user.email);
      setText('pf_role_pill', roleLabel(user.role));
      setText('pf_status_text', user.status);
      setText('pf_stat_last_active', user.last_active ? formatDate(user.last_active) : 'Never');

      setText('pf_first_name', firstName);
      setText('pf_last_name', lastName);
      setText('pf_email_detail', user.email);

      setText('pf_created', formatDate(user.created_at));
      setText('pf_status_detail', user.status);

      setText('pf_role_detail', roleLabel(user.role));
      setText('pf_access_level', getAccessLevel(user.role));

      const statusPill = document.getElementById('pf_status_pill');
      statusPill.classList.remove('active', 'inactive');
      statusPill.classList.add((user.status || '').toLowerCase() === 'active' ? 'active' : 'inactive');

      const activityList = document.getElementById('pf_activity_list');
      activityList.innerHTML = '';
      if (Array.isArray(user.recent_activities) && user.recent_activities.length > 0) {
        user.recent_activities.forEach(act => {
          const row = document.createElement('div');
          row.className = 'activity-row';
          row.innerHTML = `
            <span>${act.description || '(no description)'}</span>
            <span class="activity-time">${timeSince(act.created_at)}</span>
          `;
          activityList.appendChild(row);
        });
      } else {
        activityList.innerHTML = '<div class="activity-empty">No recent activity found.</div>';
      }
    } catch (error) {
      console.error('Failed to load user data:', error);
    }
  });
});
