document.addEventListener('DOMContentLoaded', () => {
  const viewUserModal = document.getElementById('viewUserModal');
  if (!viewUserModal) return;

  let currentUserId = null;

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

  function statusLabel(status) {
    if (status === 'not_confirmed') return 'Not Confirmed';
    return status ? status.charAt(0).toUpperCase() + status.slice(1) : '';
  }

  // Tabs
  viewUserModal.querySelectorAll('.ud-tab').forEach(tab => {
    tab.addEventListener('click', () => {
      viewUserModal.querySelectorAll('.ud-tab').forEach(t => t.classList.remove('active'));
      viewUserModal.querySelectorAll('.ud-panel').forEach(p => p.classList.remove('active'));
      tab.classList.add('active');
      document.getElementById(`ud_panel_${tab.dataset.udTab}`).classList.add('active');
    });
  });

  const ENG_ROWS_PER_PAGE = 10;
  let allEngagements = [];
  let currentEngPage = 1;

  function renderEngagementsPage(page) {
    currentEngPage = page;
    const list = document.getElementById('ud_eng_list');
    const paginationEl = document.getElementById('ud_eng_pagination');

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
      const row = document.createElement('div');
      row.className = `eng-row status-${eng.status}`;
      row.innerHTML = `
        <div class="eng-dot"></div>
        <div class="eng-name">${eng.client_name}</div>
        <div class="eng-hours">${eng.total_hours}h</div>
        <button type="button" class="eng-unassign-btn" title="Unassign" data-engagement-id="${eng.engagement_id}" data-client-name="${eng.client_name}">
          <i class="bi bi-trash"></i>
        </button>
      `;
      list.appendChild(row);
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

    setText('ud_stat_eng_count', engagements.length);
    setText('ud_stat_hours', totalHours);
    setText('ud_tab_eng_count', engagements.length);
    setText('ud_eng_hint', `${engagements.length} active assignment${engagements.length === 1 ? '' : 's'}`);

    document.getElementById('ud_unassign_all_btn').disabled = engagements.length === 0;

    renderEngagementsPage(1);
  }

  async function loadUserEngagements(userId) {
    try {
      const res = await fetch(`get_user_engagements.php?user_id=${encodeURIComponent(userId)}`);
      const data = await res.json();
      renderEngagements(data.engagements || []);
    } catch (err) {
      console.error('Failed to load user engagements', err);
      renderEngagements([]);
    }
  }

  function timeoffStatusPillClass(status) {
    if (status === 'approved') return 'confirmed';
    if (status === 'denied') return 'denied';
    if (status === 'changes_requested') return 'not-confirmed';
    return 'pending';
  }
  function timeoffStatusLabel(status) {
    if (status === 'changes_requested') return 'Changes Requested';
    return status.charAt(0).toUpperCase() + status.slice(1);
  }
  function categoryLabel(category) {
    return category.charAt(0).toUpperCase() + category.slice(1);
  }

  function formatDateRange(days) {
    if (days.length === 1) return formatDate(days[0].date);
    const sorted = [...days].sort((a, b) => a.date.localeCompare(b.date));
    return `${formatDate(sorted[0].date)} &ndash; ${formatDate(sorted[sorted.length - 1].date)} (${days.length}d)`;
  }
  function formatDateTime(dateString) {
    if (!dateString) return '-';
    const d = new Date(dateString.length <= 10 ? dateString + 'T00:00:00' : dateString.replace(' ', 'T'));
    if (isNaN(d)) return '-';
    return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) +
      ' at ' + d.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' });
  }
  function ordinal(n) {
    const s = ['th', 'st', 'nd', 'rd'];
    const v = n % 100;
    return n + (s[(v - 20) % 10] || s[v] || s[0]);
  }
  function formatDayLong(dateString) {
    if (!dateString) return '-';
    const d = new Date(dateString.length <= 10 ? dateString + 'T00:00:00' : dateString);
    if (isNaN(d)) return '-';
    const weekday = d.toLocaleDateString('en-US', { weekday: 'long' });
    const month = d.toLocaleDateString('en-US', { month: 'long' });
    return `${weekday}, ${month} ${ordinal(d.getDate())}, ${d.getFullYear()}`;
  }
  function escapeHtml(str) {
    const div = document.createElement('div');
    div.textContent = str || '';
    return div.innerHTML;
  }
  const timeoffPalette = ['#4f8ef7', '#9b6bd6', '#4fbf9f', '#e0994c', '#5fb85f', '#5aa8d6', '#d67aa8', '#7a8fd6'];
  function hashColor(name) {
    let hash = 0;
    for (let i = 0; i < (name || '').length; i++) hash = (hash * 31 + name.charCodeAt(i)) >>> 0;
    return timeoffPalette[hash % timeoffPalette.length];
  }
  function initialsOf(name) {
    return (name || '?').trim().split(/\s+/).slice(0, 2).map(w => w[0].toUpperCase()).join('');
  }

  let allTimeOffRequests = [];
  const timeOffDetailModal = new bootstrap.Modal(document.getElementById('udTimeOffDetailModal'));

  function renderTimeOff(requests) {
    allTimeOffRequests = requests;
    const list = document.getElementById('ud_timeoff_list');
    setText('ud_tab_timeoff_count', requests.length);
    setText('ud_timeoff_hint', `${requests.length} request${requests.length === 1 ? '' : 's'}`);
    document.getElementById('ud_timeoff_click_hint').style.display = requests.length > 0 ? '' : 'none';

    list.innerHTML = '';
    if (requests.length === 0) {
      list.innerHTML = '<div class="eng-empty">No time off requests</div>';
      return;
    }

    requests.forEach(r => {
      const row = document.createElement('div');
      row.className = 'eng-row clickable';
      row.dataset.requestGroup = r.request_group;
      row.innerHTML = `
        <span class="category-pill ${r.category}" style="margin-right:8px;">${categoryLabel(r.category)}</span>
        <div class="eng-name">${formatDateRange(r.days)}</div>
        <span class="eng-status-pill ${timeoffStatusPillClass(r.status)}" style="min-width:118px; justify-content:center; margin-right:8px;">
          <span class="dot"></span>${timeoffStatusLabel(r.status)}
        </span>
        <div class="eng-hours" style="min-width:36px; text-align:right;">${r.total_hours}h</div>
      `;
      list.appendChild(row);
    });
  }

  document.getElementById('ud_timeoff_list').addEventListener('click', (e) => {
    const row = e.target.closest('.eng-row[data-request-group]');
    if (!row) return;
    const r = allTimeOffRequests.find(req => req.request_group === row.dataset.requestGroup);
    if (r) openTimeOffDetail(r);
  });

  async function loadCommentHistory(r) {
    const wrap = document.getElementById('udtoHistoryWrap');
    const list = document.getElementById('udtoCommentHistory');
    wrap.style.display = '';
    list.innerHTML = '<div class="text-muted" style="font-size:12px;">Loading...</div>';

    const entries = [];
    if (r.reason) {
      entries.push({ full_name: document.getElementById('ud_name').textContent, comment: r.reason, created: r.created });
    }

    try {
      const res = await fetch(`get_time_off_comments.php?request_group=${encodeURIComponent(r.request_group)}`);
      const data = await res.json();
      if (data.success) entries.push(...data.comments);
    } catch (err) {
      console.error('Failed to load comment history', err);
    }

    if (entries.length === 0) {
      wrap.style.display = 'none';
      list.innerHTML = '';
      return;
    }

    list.innerHTML = entries.map(e => `
      <div class="timeoff-comment-item">
        <div class="timeoff-comment-avatar" style="background-color:${hashColor(e.full_name)};">${initialsOf(e.full_name)}</div>
        <div class="timeoff-comment-body">
          <div class="timeoff-comment-meta">
            <span class="timeoff-comment-name">${e.full_name}</span>
            <span class="timeoff-comment-time">${formatDateTime(e.created)}</span>
          </div>
          <p class="timeoff-comment-text">${escapeHtml(e.comment)}</p>
        </div>
      </div>
    `).join('');
  }

  function openTimeOffDetail(r) {
    document.getElementById('udtoTitle').textContent = `${categoryLabel(r.category)} Request`;
    const catEl = document.getElementById('udtoCategory');
    catEl.className = `category-pill ${r.category}`;
    catEl.textContent = categoryLabel(r.category);

    const statusEl = document.getElementById('udtoStatus');
    statusEl.className = `eng-status-pill ${timeoffStatusPillClass(r.status)}`;
    document.getElementById('udtoStatusText').textContent = timeoffStatusLabel(r.status);

    document.getElementById('udtoTotalHours').textContent = `${r.total_hours}h`;
    document.getElementById('udtoRequested').textContent = formatDate(r.created);

    const daysList = document.getElementById('udtoDaysList');
    daysList.innerHTML = [...r.days].sort((a, b) => a.date.localeCompare(b.date)).map(d => `
      <div class="eng-vm-emp-row">
        <div class="eng-vm-emp-info">
          <div class="eng-vm-emp-name">${formatDayLong(d.date)}</div>
        </div>
        <div class="eng-vm-emp-hours">${d.hours}h</div>
      </div>
    `).join('');

    loadCommentHistory(r);

    timeOffDetailModal.show();
  }

  async function loadUserTimeOff(userId) {
    try {
      const res = await fetch(`get_user_time_off.php?user_id=${encodeURIComponent(userId)}`);
      const data = await res.json();
      renderTimeOff(data.requests || []);
    } catch (err) {
      console.error('Failed to load user time off', err);
      renderTimeOff([]);
    }
  }

  async function loadUser(userId) {
    try {
      const response = await fetch(`get_user.php?user_id=${encodeURIComponent(userId)}`);
      if (!response.ok) throw new Error('Network response was not ok');
      const user = await response.json();

      const fullName = user.full_name || '-';
      const initials = fullName
        .split(' ')
        .map(name => name.charAt(0).toUpperCase())
        .slice(0, 2)
        .join('');

      setText('ud_avatar', initials);
      setText('ud_name', fullName);
      setText('ud_email', user.email);
      setText('ud_role_pill', user.role);
      setText('ud_status_text', user.status);
      setText('ud_stat_last_active', user.last_active ? formatDate(user.last_active) : 'Never');

      setText('ud_detail_fullname', fullName);
      setText('ud_detail_email', user.email);
      setText('ud_detail_created', formatDate(user.created_at));
      setText('ud_detail_role', user.role);
      setText('ud_detail_access_level', getAccessLevel(user.role));
      setText('ud_detail_status', user.status);

      const managerRow = document.getElementById('ud_detail_manager_row');
      if (['staff', 'senior'].includes((user.role || '').toLowerCase())) {
          managerRow.style.display = '';
          setText('ud_detail_manager', user.manager_name || 'Unassigned');
      } else {
          managerRow.style.display = 'none';
      }

      const statusPill = document.getElementById('ud_status_pill');
      statusPill.classList.remove('active', 'inactive');
      statusPill.classList.add((user.status || '').toLowerCase() === 'active' ? 'active' : 'inactive');

      const activityList = document.getElementById('ud_activity_list');
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
  }

  function confirmAndUnassign({ title, text, url, body, onSuccess }) {
    if (typeof Swal === 'undefined') {
      if (!confirm(text)) return;
      doUnassign(url, body, onSuccess);
      return;
    }
    Swal.fire({
      icon: 'warning',
      title,
      text,
      showCancelButton: true,
      confirmButtonText: 'Unassign',
      confirmButtonColor: '#c0392b'
    }).then(result => {
      if (result.isConfirmed) doUnassign(url, body, onSuccess);
    });
  }

  async function doUnassign(url, body, onSuccess) {
    try {
      const res = await fetch(url, {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(body)
      });
      const data = await res.json().catch(() => null);
      if (!res.ok || !data || !data.success) {
        const errMsg = (data && data.error) || 'Please try again.';
        if (typeof Swal !== 'undefined') {
          Swal.fire({ icon: 'error', title: 'Could not unassign', text: errMsg });
        } else {
          alert(errMsg);
        }
        return;
      }
      onSuccess();
    } catch (err) {
      console.error('Failed to unassign', err);
    }
  }

  // Individual unassign (event delegation, list is rebuilt on every load)
  document.getElementById('ud_eng_list').addEventListener('click', (e) => {
    const btn = e.target.closest('.eng-unassign-btn');
    if (!btn || !currentUserId) return;
    const engagementId = btn.dataset.engagementId;
    const clientName = btn.dataset.clientName;
    confirmAndUnassign({
      title: 'Unassign engagement?',
      text: `Remove all of this employee's assigned hours for ${clientName}.`,
      url: 'unassign_user_engagement.php',
      body: { user_id: currentUserId, engagement_id: engagementId },
      onSuccess: () => loadUserEngagements(currentUserId)
    });
  });

  // Unassign all
  document.getElementById('ud_unassign_all_btn').addEventListener('click', () => {
    if (!currentUserId) return;
    confirmAndUnassign({
      title: 'Unassign all engagements?',
      text: 'This removes every assigned hour for this employee across all engagements.',
      url: 'unassign_all_user_engagements.php',
      body: { user_id: currentUserId },
      onSuccess: () => loadUserEngagements(currentUserId)
    });
  });

  viewUserModal.addEventListener('show.bs.modal', (event) => {
    const button = event.relatedTarget;
    const userId = button?.getAttribute('data-user-id');
    if (!userId) return;
    currentUserId = userId;

    // Reset to Overview tab on every open
    viewUserModal.querySelectorAll('.ud-tab').forEach(t => t.classList.remove('active'));
    viewUserModal.querySelectorAll('.ud-panel').forEach(p => p.classList.remove('active'));
    viewUserModal.querySelector('.ud-tab[data-ud-tab="overview"]').classList.add('active');
    document.getElementById('ud_panel_overview').classList.add('active');

    loadUser(userId);
    loadUserEngagements(userId);
    loadUserTimeOff(userId);
  });
});
