document.addEventListener('DOMContentLoaded', () => {
    const modalEl = document.getElementById('viewEngagementModal');
    if (!modalEl) return;
    const modal = new bootstrap.Offcanvas(modalEl);
    const modalBody = document.getElementById('viewEngagementModalBody');

    function statusClass(status) {
        return (status || '').replace(/_/g, '-');
    }
    function statusLabel(status) {
        if (status === 'not_confirmed') return 'Not Confirmed';
        return status ? status.charAt(0).toUpperCase() + status.slice(1) : '';
    }

    // Matches the formatDate() convention already used across the app
    // (request_time_off.js, viewUserModal.js, etc.) for consistency.
    function fmtDate(dateString) {
        if (!dateString) return null;
        const d = new Date(dateString.length <= 10 ? dateString + 'T00:00:00' : dateString.replace(' ', 'T'));
        if (isNaN(d)) return null;
        return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
    }
    // <input type="date"> needs a bare YYYY-MM-DD - strip any time portion.
    function toInputDate(dateString) {
        if (!dateString) return '';
        return dateString.length >= 10 ? dateString.slice(0, 10) : '';
    }
    // Past its due date and not yet marked complete - calendar-day
    // comparison, not exact-time (a date due "today" isn't overdue yet).
    function isOverdue(dateString, completed) {
        if (!dateString || completed) return false;
        const due = toInputDate(dateString);
        const today = new Date().toISOString().slice(0, 10);
        return due < today;
    }
    function notify(message, isError) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({ icon: isError ? 'error' : 'success', title: message, timer: isError ? undefined : 1100, showConfirmButton: !!isError });
        } else if (isError) {
            alert(message);
        }
    }

    // Sectioned "card" shell used by every part of the body below - a
    // colored dot + label, matching the Engagement Tracker reference layout
    // instead of one long undifferentiated scroll.
    function card(title, color, bodyHtml, titleAction, id) {
        return `
            <div class="eng-vm-card"${id ? ` id="${id}"` : ''}>
                <div class="eng-vm-card-title">
                    <span class="eng-vm-card-title-left"><span class="eng-vm-card-dot" style="background:${color}"></span>${title}</span>
                    ${titleAction || ''}
                </div>
                ${bodyHtml}
            </div>`;
    }
    function detailRow(label, value) {
        return `<div class="detail-row"><span class="detail-label">${label}</span><span class="detail-value">${value || '<span class="text-muted">&mdash;</span>'}</span></div>`;
    }

    // Two-up label-over-value fields (Overview/Details) - a flat 2-column
    // CSS grid that auto-flows items in DOM order, matching Engagement
    // Tracker's own .drawer-info-grid exactly (pulled straight from its
    // source, pages/dashboard.php's renderDrawer()) rather than wrapping
    // pairs in row divs. `full` spans both columns (used for Scope).
    function gridField(label, value, full) {
        return `<div class="eng-vm-grid-field${full ? ' full' : ''}"><div class="eng-vm-grid-label">${label}</div><div class="eng-vm-grid-value">${value || '<span class="text-muted">&mdash;</span>'}</div></div>`;
    }

    // Audit tracking sections (timeline/milestones/DOL/independence), added
    // for the Engagement Tracker migration. Each is only present in `audit`
    // if the backend decided the current user has permission to see it -
    // a missing key means "don't render this section", not "empty".
    //
    // can_manage_timeline / can_complete_timeline come pre-scoped from
    // engagement-details.php (admin-or-staffed-on-this-engagement, not just
    // "does this role have the permission at all") - the frontend doesn't
    // re-derive that, it just renders inputs/checkable dots when they're true.
    //
    // Two display modes for the date list itself: read-only (default,
    // matches the Engagement Tracker reference - label-over-date, colored
    // dot, click a row to toggle complete/incomplete) and edit (behind the
    // "Edit Timeline" toggle, for actually changing a date - the original
    // inline <input type="date"> boxes). `timelineEditMode` is module-scoped
    // so it survives a refresh() (e.g. after saving a date) but resets
    // whenever a different engagement is opened - see open().
    function timelineRowReadOnly(step, engagementId, canComplete) {
        const isDone = !!step.completed;
        const overdue = isOverdue(step.date, isDone);
        const dateText = step.start_date && step.date
            ? `${fmtDate(step.start_date)} &ndash; ${fmtDate(step.date)}`
            : fmtDate(step.date);
        const dotState = isDone ? 'done' : (overdue ? 'overdue' : 'pending');
        const clickAttrs = canComplete
            ? `role="button" tabindex="0" data-kind="timeline" data-engagement-id="${engagementId}" data-completed-column="${step.completed_column}" data-completed="${isDone ? '1' : '0'}"`
            : '';
        return `
            <div class="eng-vm-tl-row2 ${canComplete ? 'clickable' : ''}" ${clickAttrs}>
                <span class="eng-vm-tl-dot2 ${dotState}"></span>
                <div class="eng-vm-tl-info">
                    <div class="eng-vm-tl-label2">${step.label}</div>
                    <div class="eng-vm-tl-date2 ${overdue ? 'overdue' : ''}">${dateText || '<span class="text-muted">Not set</span>'}</div>
                </div>
            </div>`;
    }

    function timelineRowEditable(step, engagementId, canComplete) {
        const isDone = !!step.completed;
        const overdue = isOverdue(step.date, isDone);

        const dateHtml = step.start_column
            ? `<input type="date" class="eng-vm-tl-date-input" data-kind="timeline" data-engagement-id="${engagementId}" data-column="${step.start_column}" value="${toInputDate(step.start_date)}">
               <span class="eng-vm-tl-date-sep">&ndash;</span>
               <input type="date" class="eng-vm-tl-date-input" data-kind="timeline" data-engagement-id="${engagementId}" data-column="${step.date_column}" value="${toInputDate(step.date)}">`
            : `<input type="date" class="eng-vm-tl-date-input" data-kind="timeline" data-engagement-id="${engagementId}" data-column="${step.date_column}" value="${toInputDate(step.date)}">`;

        const dotAttrs = canComplete
            ? `role="button" tabindex="0" data-kind="timeline" data-engagement-id="${engagementId}" data-completed-column="${step.completed_column}" data-completed="${isDone ? '1' : '0'}"`
            : '';

        return `
            <div class="eng-vm-tl-row ${isDone ? 'done' : ''} ${overdue ? 'overdue' : ''}">
                <span class="eng-vm-tl-dot ${canComplete ? 'clickable' : ''}" ${dotAttrs}></span>
                <span class="eng-vm-tl-label">${step.label}</span>
                ${dateHtml}
            </div>`;
    }

    function renderTimelineSection(audit, engagementId, details) {
        details = details || {};
        if (!audit.timeline) return '';
        const canManage = !!audit.can_manage_timeline;
        const canComplete = !!audit.can_complete_timeline;
        const editMode = timelineEditMode && canManage;

        const rows = audit.timeline
            .map(step => editMode ? timelineRowEditable(step, engagementId, canComplete) : timelineRowReadOnly(step, engagementId, canComplete))
            .join('');
        const hint = (!editMode && canComplete)
            ? '<div class="eng-vm-tl-hint">Click a date to mark it complete or incomplete.</div>'
            : '';

        const canEditWeekly = canManage;
        const wsc = audit.weekly_status_call || {};
        const dayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
        let weekly = '';
        if (canEditWeekly) {
            const dayOptions = dayNames.map((name, i) =>
                `<option value="${i}" ${wsc.day_index === i ? 'selected' : ''}>${name}</option>`
            ).join('');
            weekly = `
                <div class="eng-vm-tl-weekly-row">
                    <span class="eng-vm-tl-weekly-label"><i class="bi bi-arrow-repeat"></i> Weekly Status Call</span>
                    <select class="eng-vm-tl-date-input" id="engVmWeeklyDayInput" data-engagement-id="${engagementId}">
                        <option value="">No call set</option>
                        ${dayOptions}
                    </select>
                </div>`;
        } else if (wsc.day) {
            weekly = `<div class="eng-vm-tl-weekly">Weekly status call: <strong>${wsc.day}</strong>${wsc.group_name ? ` &middot; ${wsc.group_name}` : ''}</div>`;
        }

        const uploadRow = canManage
            ? `<div class="eng-vm-tl-upload-row">
                   <label class="eng-vm-upload-link">
                       <i class="bi bi-upload"></i> Upload Planning Doc
                       <input type="file" id="engVmPlanningDocInput" data-engagement-id="${engagementId}" hidden>
                   </label>
                   ${details.planning_doc_url ? `<a href="download_planning_doc.php?engagement_id=${engagementId}" class="eng-vm-upload-link">&middot; <i class="bi bi-download"></i> Download current</a>` : ''}
               </div>`
            : (details.planning_doc_url ? `<div class="eng-vm-tl-upload-row"><a href="download_planning_doc.php?engagement_id=${engagementId}" class="eng-vm-upload-link"><i class="bi bi-download"></i> Planning Doc</a></div>` : '');

        const titleAction = canManage
            ? `<a href="#" class="eng-vm-card-title-action" id="engVmTimelineEditToggle">${editMode ? 'Done' : 'Edit Timeline'}</a>`
            : '';
        const rowListClass = editMode ? 'eng-vm-tl-list' : 'eng-vm-tl-list2';

        return card(
            'Timeline & Key Dates', '#2f9e57',
            `${uploadRow}<div class="${rowListClass}">${rows}</div>${hint}${weekly}`,
            titleAction, 'engVmTimelineCard'
        );
    }

    function renderMilestonesSection(audit) {
        if (!audit.milestones || audit.milestones.length === 0) return '';
        const canManage = !!audit.can_manage_timeline;
        const canComplete = !!audit.can_complete_timeline;

        const rows = audit.milestones.map(ms => {
            const isDone = ms.is_completed == 1;
            const overdue = isOverdue(ms.due_date, isDone);
            const dateHtml = canManage
                ? `<input type="date" class="eng-vm-tl-date-input" data-kind="milestone" data-milestone-id="${ms.milestone_id}" value="${toInputDate(ms.due_date)}">`
                : `<span class="eng-vm-tl-date">${fmtDate(ms.due_date) || '<span class="text-muted">Not set</span>'}</span>`;
            const dotAttrs = canComplete
                ? `role="button" tabindex="0" data-kind="milestone" data-milestone-id="${ms.milestone_id}" data-completed="${isDone ? '1' : '0'}"`
                : '';
            return `
                <div class="eng-vm-tl-row ${isDone ? 'done' : ''} ${overdue ? 'overdue' : ''}">
                    <span class="eng-vm-tl-dot ${canComplete ? 'clickable' : ''}" ${dotAttrs}></span>
                    <span class="eng-vm-tl-label">${ms.milestone_type}</span>
                    ${dateHtml}
                </div>`;
        }).join('');
        return card('Milestones', '#d67aa8', `<div class="eng-vm-tl-list">${rows}</div>`);
    }

    function roleLabel(role) {
        const r = (role || '').toLowerCase();
        if (r === 'crm_team') return 'CRM Team';
        if (!r) return 'Other';
        return r.charAt(0).toUpperCase() + r.slice(1);
    }

    function initials(name) {
        return (name || '?').trim().split(/\s+/).slice(0, 2).map(w => w[0].toUpperCase()).join('');
    }

    // One merged "Team" section (Assigned Employees + DOL, previously two
    // separate cards) - grouped by role (Manager > Senior > Staff > Intern),
    // each person's row shows their hours/audit-type same as before, plus
    // their DOL criteria chips underneath when DOL data is visible to this
    // user. `audit.dol` is only present at all if the backend granted
    // view_dol for this request - a missing key means "don't show chips",
    // not "no DOL assigned".
    function renderTeamSection(data, engagementId) {
        const employees = data.assigned_employees || [];
        const audit = data.audit || {};
        const titleAction = audit.can_manage_dol
            ? `<a href="dol-generator.php?engagement_id=${engagementId}" class="eng-vm-card-title-action">Edit DOL</a>`
            : '';

        if (employees.length === 0) {
            return card('Team', '#003f47', '<div class="text-muted" style="font-size:13px;">No employees assigned yet.</div>', titleAction);
        }

        const dolByUser = new Map();
        (audit.dol || []).forEach(row => {
            if (!dolByUser.has(row.user_id)) dolByUser.set(row.user_id, []);
            dolByUser.get(row.user_id).push({ criterion: row.criterion, color: row.audit_type_color, type: row.audit_type_name });
        });

        // Collapse the (user, audit_type) rows the backend sends into one
        // row per person - hours summed across every audit type they're on,
        // DOL chips attached from the map above.
        const roleOrder = ['manager', 'senior', 'staff', 'intern'];
        const byRole = new Map();
        employees.forEach(emp => {
            const role = (emp.role || '').toLowerCase() || 'other';
            if (!byRole.has(role)) byRole.set(role, new Map());
            const byPerson = byRole.get(role);
            if (!byPerson.has(emp.user_id)) {
                byPerson.set(emp.user_id, { name: emp.name, hours: 0, auditTypes: [], criteria: dolByUser.get(emp.user_id) || [] });
            }
            const person = byPerson.get(emp.user_id);
            person.hours += emp.hours;
            if (emp.audit_type_name) person.auditTypes.push({ name: emp.audit_type_name, color: emp.audit_type_color });
        });
        const orderedRoles = [...roleOrder.filter(r => byRole.has(r)), ...Array.from(byRole.keys()).filter(r => !roleOrder.includes(r))];

        const groups = orderedRoles.map(role => {
            const people = Array.from(byRole.get(role).values());
            const rows = people.map(person => `
                <div class="eng-vm-emp-row ${person.criteria.length ? 'has-chips' : ''}">
                    <div class="eng-vm-emp-avatar">${initials(person.name)}</div>
                    <div class="eng-vm-emp-info">
                        <div class="eng-vm-emp-name">${person.name}</div>
                        ${person.auditTypes.length ? `<div class="eng-vm-emp-role">${person.auditTypes.map(t => `<span class="audit-type-dot" style="background:${t.color || '#9aa39d'}"></span>${t.name}`).join(' &middot; ')}</div>` : ''}
                        ${person.criteria.length ? `<div class="eng-vm-dol-chips">${person.criteria.map(c => `<span class="eng-vm-dol-chip" style="border-color:${c.color || '#9aa39d'}" title="${c.type || ''}">${c.criterion}</span>`).join('')}</div>` : ''}
                    </div>
                    <div class="eng-vm-emp-hours">${person.hours}h</div>
                </div>`).join('');
            return `
                <div class="eng-vm-role-group">
                    <div class="eng-vm-role-group-title">${roleLabel(role)}${people.length > 1 ? ` (${people.length})` : ''}</div>
                    <div class="eng-vm-emp-list">${rows}</div>
                </div>`;
        }).join('');

        return card('Team', '#003f47', groups, titleAction);
    }

    function renderIndependenceSection(audit) {
        if (!audit.independence || audit.independence.length === 0) return '';
        const rows = audit.independence.map(person => {
            let icon, cls;
            if (person.independent === 'Y') { icon = '&#10003;'; cls = 'yes'; }
            else if (person.independent === 'N') { icon = '&#10007;'; cls = 'no'; }
            else { icon = '&ndash;'; cls = 'unset'; }
            return `
                <div class="eng-vm-indep-row">
                    <span class="eng-vm-indep-name">${person.full_name}</span>
                    <span class="eng-vm-indep-icon ${cls}">${icon}</span>
                </div>`;
        }).join('');
        return card('Independence', '#5fb85f', `<div class="eng-vm-indep-list">${rows}</div>`);
    }

    // Field set/order/fallbacks copied exactly from Engagement Tracker's
    // own renderDrawer() (pages/dashboard.php) - including its "As of X"
    // fallback when there's no explicit review-period range, and the
    // Type 1/Type 2 -> Type I/Type II relabeling. No TSC row - ET's
    // reference doesn't have one (it's a Client Scheduler-only field not
    // in ET's schema at all).
    function renderOverviewCard(data) {
        const d = data.details || {};
        let reviewPeriod = 'N/A';
        if (d.review_period_start && d.review_period_end) {
            reviewPeriod = `${fmtDate(d.review_period_start) || 'N/A'} &ndash; ${fmtDate(d.review_period_end) || 'N/A'}`;
        } else if (d.as_of_date) {
            reviewPeriod = 'As of ' + (fmtDate(d.as_of_date) || 'N/A');
        }
        const auditTypeNames = (data.audit_types || []).map(t => t.name).join(', ') || 'N/A';
        const reportType = d.soc_type
            ? (d.soc_type === 'Type 1' ? 'Type I' : (d.soc_type === 'Type 2' ? 'Type II' : d.soc_type))
            : null;
        const fields = [
            gridField('Location', d.location || 'N/A'),
            gridField('Review Period', reviewPeriod),
            gridField('Audit Type', auditTypeNames),
            reportType ? gridField('Report Type', reportType) : '',
            gridField('Manager', data.manager || 'Unassigned'),
            gridField('Point of Contact', d.poc || 'N/A'),
        ].join('');
        return card('Overview', '#003f47', `<div class="eng-vm-grid">${fields}</div>`);
    }

    // Same field set as ET's reference Details card: Created/Last Updated,
    // Archive Date, Scope (full width). No As-of Date (folded into
    // Overview's Review Period above, matching ET) and no Planning Doc row
    // (already shown under Timeline, matching ET's own layout - it puts
    // the planning-doc row there too, not in Details). "Archive Date" is
    // always "Not archived" here since this panel only ever opens for
    // active engagements - once one's archived it shows the separate
    // archived-summary view instead (see view_client_modal.js).
    function renderDetailsCard(data) {
        const d = data.details;
        if (!d) return '';
        const fields = [
            gridField('Created', fmtDate(d.created_at) || 'N/A'),
            gridField('Last Updated', fmtDate(d.updated_at) || 'N/A'),
            gridField('Archive Date', 'Not archived'),
            gridField('Scope', d.scope || 'N/A', true),
        ].join('');
        return card('Details', '#e0994c', `<div class="eng-vm-grid">${fields}</div>`);
    }

    function renderNotesCard(data) {
        const notes = (data.details && data.details.notes) || data.notes;
        const body = notes
            ? `<p style="font-size:13px; margin:0; white-space:pre-wrap;">${notes}</p>`
            : `<p class="text-muted" style="font-size:13px; margin:0; font-style:italic;">No notes added yet.</p>`;
        return card('Notes', '#9b6bd6', body);
    }

    let lastOpenArgs = null;
    let lastData = null;
    // Timeline dates are read-only by default (Engagement Tracker's own
    // layout); "Edit Timeline" swaps that one card into the old editable
    // inputs. Module-scoped so it survives a refresh() (saving a date
    // re-fetches and re-renders everything), but reset whenever a
    // *different* engagement is opened - see below.
    let timelineEditMode = false;
    let timelineEditModeEngagementId = null;

    async function open(engagementId, avatarColor, initials, restrictFinancials) {
        avatarColor = avatarColor || '#4f8ef7';
        initials = initials || '?';
        if (!engagementId) return;
        lastOpenArgs = [engagementId, avatarColor, initials, restrictFinancials];
        if (timelineEditModeEngagementId !== String(engagementId)) {
            timelineEditMode = false;
            timelineEditModeEngagementId = String(engagementId);
        }

        modalBody.innerHTML = '<div class="text-center text-muted py-4">Loading...</div>';
        modal.show();

        try {
            const res = await fetch(`engagement-details.php?id=${encodeURIComponent(engagementId)}`);
            const data = await res.json();
            lastData = data;

            const budgeted = data.budgeted_hours || 0;
            const allocated = data.total_hours || 0;
            const isOver = allocated > budgeted;
            const employees = data.assigned_employees || [];

            const pct = budgeted > 0 ? (allocated / budgeted) * 100 : 0;
            const barWidth = Math.min(100, pct);
            const overHours = allocated - budgeted;
            let utilColor;
            if (isOver) utilColor = 'red';
            else if (pct >= 75) utilColor = 'green';
            else utilColor = 'yellow';

            const auditTypeChips = (data.audit_types || []).map(t =>
                `<span class="eng-vm-audit-chip"><span class="eng-vm-audit-chip-dot" style="background:${t.color || '#9aa39d'}"></span>${t.name}</span>`
            ).join('');
            const repeatBadge = data.details && data.details.repeat_flag == 1
                ? '<span class="eng-vm-repeat-badge"><i class="bi bi-arrow-repeat"></i> Repeat</span>'
                : '';
            const chipsRow = (auditTypeChips || repeatBadge)
                ? `<div class="eng-vm-chip-row">${auditTypeChips}${repeatBadge}</div>`
                : '';

            const capacityBody = restrictFinancials
                ? `<div class="eng-vm-stat-row" style="grid-template-columns: 1fr;"><div class="eng-vm-stat-card"><div class="eng-vm-stat-title">Employees</div><div class="eng-vm-stat-value">${employees.length}</div></div></div>`
                : `<div class="eng-vm-stat-row">
                        <div class="eng-vm-stat-card">
                            <div class="eng-vm-stat-title">Budgeted</div>
                            <div class="eng-vm-stat-value">${budgeted}h</div>
                        </div>
                        <div class="eng-vm-stat-card">
                            <div class="eng-vm-stat-title">Allocated</div>
                            <div class="eng-vm-stat-value ${isOver ? 'over' : ''}">${allocated}h</div>
                        </div>
                        <div class="eng-vm-stat-card">
                            <div class="eng-vm-stat-title">Employees</div>
                            <div class="eng-vm-stat-value">${employees.length}</div>
                        </div>
                        <div class="eng-vm-stat-card">
                            <div class="eng-vm-stat-title">Utilization</div>
                            <div class="eng-vm-stat-value ${utilColor === 'red' ? 'over' : ''}">${Math.round(pct)}%</div>
                        </div>
                    </div>
                    <div class="eng-util-cell">
                        <div class="eng-util-track">
                            <div class="eng-util-fill ${utilColor}" style="width: ${barWidth}%"></div>
                        </div>
                        <span class="eng-util-pct ${utilColor}">${Math.round(pct)}%</span>
                    </div>
                    ${isOver ? `<div class="eng-util-over">+${overHours}h over</div>` : ''}`;

            const engCode = `ENG-${data.year || new Date().getFullYear()}-${String(engagementId).padStart(3, '0')}`;
            const canManageEng = !!data.can_manage_engagement;

            const statusOptions = ['confirmed', 'pending', 'not_confirmed']
                .map(s => `<option value="${s}" ${data.status === s ? 'selected' : ''}>${statusLabel(s)}</option>`)
                .join('');
            const statusHtml = canManageEng
                ? `<select class="eng-vm-status-select ${statusClass(data.status)}" id="engVmStatusSelect" data-engagement-id="${engagementId}">${statusOptions}</select>`
                : `<span class="eng-status-pill ${statusClass(data.status)}"><span class="dot"></span>${statusLabel(data.status)}</span>`;

            const actionsHtml = canManageEng
                ? `<div class="eng-vm-actions">
                       <button type="button" class="eng-vm-action-btn primary" id="engVmEditBtn"><i class="bi bi-pencil-square"></i> Edit</button>
                       <button type="button" class="eng-vm-action-btn" id="engVmArchiveBtn"><i class="bi bi-archive"></i> Archive</button>
                       <button type="button" class="eng-vm-action-btn danger" id="engVmDeleteBtn"><i class="bi bi-trash"></i> Delete</button>
                   </div>`
                : '';

            modalBody.innerHTML = `
                <div class="eng-vm-header">
                    <div class="eng-vm-eng-code">${engCode}</div>
                    <div class="eng-vm-client-name-lg">${data.client_name || ''}</div>
                    <div class="eng-vm-status-row">
                        ${statusHtml}
                        ${chipsRow}
                    </div>
                    ${actionsHtml}
                </div>
                <div class="eng-vm-divider"></div>
                <div class="eng-vm-body">
                    ${renderOverviewCard(data)}
                    ${card('Capacity', '#4fbf9f', capacityBody)}
                    ${renderDetailsCard(data)}
                    ${renderNotesCard(data)}
                    ${renderTeamSection(data, engagementId)}
                    ${renderTimelineSection(data.audit || {}, engagementId, data.details)}
                    ${renderMilestonesSection(data.audit || {})}
                    ${renderIndependenceSection(data.audit || {})}
                </div>
            `;

            wireHeaderActions(data, engagementId, avatarColor, initials);
        } catch (err) {
            console.error('Failed to load engagement details', err);
            modalBody.innerHTML = '<div class="text-center text-danger py-4">Could not load engagement details.</div>';
        }
    }

    function refresh() {
        if (lastOpenArgs) open(...lastOpenArgs);
    }

    function wireHeaderActions(data, engagementId, avatarColor, initials) {
        const statusSelect = document.getElementById('engVmStatusSelect');
        if (statusSelect) {
            statusSelect.addEventListener('change', async () => {
                statusSelect.className = `eng-vm-status-select ${statusClass(statusSelect.value)}`;
                try {
                    const res = await fetch('update_engagement_status.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ engagement_id: engagementId, status: statusSelect.value })
                    });
                    const result = await res.json();
                    if (!result.success) notify(result.error || 'Could not save status.', true);
                    refresh();
                } catch (err) {
                    console.error('Failed to save status', err);
                    notify('Network error. Please try again.', true);
                }
            });
        }

        const editBtn = document.getElementById('engVmEditBtn');
        if (editBtn) {
            editBtn.addEventListener('click', () => {
                if (!window.EditEngagementModal) {
                    notify('Edit is only available from the Engagements page right now.', true);
                    return;
                }
                const auditTypeIds = (data.audit_types || []).map(t => t.audit_type_id);
                const tsc = ((data.details && data.details.tsc) || '').split(',').map(s => s.trim()).filter(Boolean);
                modal.hide();
                window.EditEngagementModal.open({
                    engagementId,
                    clientName: data.client_name,
                    budgetedHours: data.budgeted_hours,
                    status: data.status,
                    manager: data.manager,
                    notes: data.notes,
                    auditTypeIds,
                    tsc,
                });
                // Reopen this panel once the edit form closes.
                const onHidden = () => {
                    window.EditEngagementModal.modalEl.removeEventListener('hidden.bs.modal', onHidden);
                    open(engagementId, avatarColor, initials, false);
                };
                window.EditEngagementModal.modalEl.addEventListener('hidden.bs.modal', onHidden);
            });
        }

        const archiveBtn = document.getElementById('engVmArchiveBtn');
        if (archiveBtn) {
            archiveBtn.addEventListener('click', () => {
                const run = async () => {
                    try {
                        const formData = new FormData();
                        formData.append('engagement_id', engagementId);
                        const res = await fetch('archive_engagement.php', { method: 'POST', body: formData });
                        const result = await res.json();
                        if (result.success) {
                            modal.hide();
                            location.reload();
                        } else {
                            notify(result.message || 'Could not archive.', true);
                        }
                    } catch (err) {
                        console.error('Failed to archive engagement', err);
                        notify('Network error. Please try again.', true);
                    }
                };
                if (typeof Swal !== 'undefined') {
                    Swal.fire({ icon: 'warning', title: 'Archive this engagement?', text: 'It will move to Archived and out of the active list.', showCancelButton: true, confirmButtonText: 'Archive', confirmButtonColor: '#285a80' })
                        .then(result => { if (result.isConfirmed) run(); });
                } else if (confirm('Archive this engagement?')) {
                    run();
                }
            });
        }

        const deleteBtn = document.getElementById('engVmDeleteBtn');
        const deleteConfirmModalEl = document.getElementById('viewEngDeleteConfirmModal');
        if (deleteBtn && deleteConfirmModalEl) {
            const deleteConfirmModal = bootstrap.Modal.getOrCreateInstance(deleteConfirmModalEl);
            const deleteInput = document.getElementById('viewEngDeleteConfirmInput');
            const deleteConfirmBtn = document.getElementById('viewEngDeleteConfirmBtn');

            deleteBtn.addEventListener('click', () => {
                deleteInput.value = '';
                deleteConfirmBtn.disabled = true;
                deleteConfirmModal.show();
            });
            deleteInput.oninput = () => {
                deleteConfirmBtn.disabled = deleteInput.value.trim().toLowerCase() !== 'delete';
            };
            deleteConfirmBtn.onclick = async () => {
                if (deleteInput.value.trim().toLowerCase() !== 'delete') return;
                try {
                    const res = await fetch('delete_engagement_permanent.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ engagement_id: engagementId })
                    });
                    const result = await res.json();
                    if (result.success) {
                        deleteConfirmModal.hide();
                        modal.hide();
                        location.reload();
                    } else {
                        notify(result.message || 'Could not delete.', true);
                    }
                } catch (err) {
                    console.error('Failed to delete engagement', err);
                    notify('Network error. Please try again.', true);
                }
            };
        }

        wireTimelineCardActions(engagementId);
    }

    // Wiring for just the Timeline card - split out from wireHeaderActions
    // so it can be re-bound after a local re-render (toggling the
    // "Edit Timeline" link swaps that one card's HTML without a full
    // network refetch - see reRenderTimeline()).
    function wireTimelineCardActions(engagementId) {
        const editToggle = document.getElementById('engVmTimelineEditToggle');
        if (editToggle) {
            editToggle.addEventListener('click', (e) => {
                e.preventDefault();
                timelineEditMode = !timelineEditMode;
                reRenderTimeline();
            });
        }

        const weeklyDayInput = document.getElementById('engVmWeeklyDayInput');
        if (weeklyDayInput) {
            weeklyDayInput.addEventListener('change', async () => {
                try {
                    const res = await fetch('update_audit_weekly_call.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ engagement_id: engagementId, day: weeklyDayInput.value === '' ? null : weeklyDayInput.value })
                    });
                    const result = await res.json();
                    if (!result.success) notify(result.error || 'Could not save.', true);
                    refresh();
                } catch (err) {
                    console.error('Failed to save weekly call day', err);
                    notify('Network error. Please try again.', true);
                }
            });
        }

        const planningDocInput = document.getElementById('engVmPlanningDocInput');
        if (planningDocInput) {
            planningDocInput.addEventListener('change', async () => {
                const file = planningDocInput.files[0];
                if (!file) return;
                const formData = new FormData();
                formData.append('engagement_id', engagementId);
                formData.append('file', file);
                try {
                    const res = await fetch('upload_planning_doc.php', { method: 'POST', body: formData });
                    const result = await res.json();
                    if (!result.success) notify(result.error || 'Could not upload file.', true);
                    refresh();
                } catch (err) {
                    console.error('Failed to upload planning doc', err);
                    notify('Network error. Please try again.', true);
                }
            });
        }
    }

    // Re-renders just the Timeline card from the last-fetched data, with no
    // network round trip - used for the "Edit Timeline" / "Done" toggle,
    // which is a pure display-mode switch, not a data change. The generic
    // change/click/keydown delegation below is bound on modalBody itself,
    // so it keeps working on the new markup automatically; only this
    // card's own listeners (the toggle, weekly select, upload input) need
    // re-wiring after outerHTML replaces the element they were bound to.
    function reRenderTimeline() {
        if (!lastData) return;
        const wrap = document.getElementById('engVmTimelineCard');
        if (!wrap) return;
        const engagementId = lastData.engagement_id;
        wrap.outerHTML = renderTimelineSection(lastData.audit || {}, engagementId, lastData.details);
        wireTimelineCardActions(engagementId);
    }

    async function saveTimelineField(column, engagementId, value) {
        try {
            const res = await fetch('update_audit_timeline_field.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ engagement_id: engagementId, column, value })
            });
            const result = await res.json();
            if (!result.success) notify(result.error || 'Could not save.', true);
            refresh();
        } catch (err) {
            console.error('Failed to save timeline field', err);
            notify('Network error. Please try again.', true);
        }
    }

    async function saveMilestone(milestoneId, action, value) {
        try {
            const res = await fetch('update_audit_milestone.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ milestone_id: milestoneId, action, value })
            });
            const result = await res.json();
            if (!result.success) notify(result.error || 'Could not save.', true);
            refresh();
        } catch (err) {
            console.error('Failed to save milestone', err);
            notify('Network error. Please try again.', true);
        }
    }

    // Event delegation - the timeline/milestone rows are re-rendered wholesale
    // on every open()/refresh(), so listeners are bound once here rather than
    // re-attached per row.
    modalBody.addEventListener('change', (e) => {
        const input = e.target.closest('.eng-vm-tl-date-input');
        if (!input) return;
        if (input.dataset.kind === 'timeline') {
            saveTimelineField(input.dataset.column, input.dataset.engagementId, input.value || null);
        } else if (input.dataset.kind === 'milestone') {
            saveMilestone(input.dataset.milestoneId, 'set_due_date', input.value || null);
        }
    });

    function toggleDot(dot) {
        const nowCompleted = dot.dataset.completed !== '1';
        if (dot.dataset.kind === 'timeline') {
            saveTimelineField(dot.dataset.completedColumn, dot.dataset.engagementId, nowCompleted);
        } else if (dot.dataset.kind === 'milestone') {
            saveMilestone(dot.dataset.milestoneId, 'toggle_complete', nowCompleted);
        }
    }
    // Toggling completion can be triggered from either the editable dot
    // (.eng-vm-tl-dot, edit mode) or the whole read-only row
    // (.eng-vm-tl-row2, default view - matches the "click a date to mark it
    // complete" hint), so both carry the same data-* attributes.
    modalBody.addEventListener('click', (e) => {
        const el = e.target.closest('.eng-vm-tl-dot.clickable, .eng-vm-tl-row2.clickable');
        if (el) toggleDot(el);
    });
    modalBody.addEventListener('keydown', (e) => {
        if (e.key !== 'Enter' && e.key !== ' ') return;
        const el = e.target.closest('.eng-vm-tl-dot.clickable, .eng-vm-tl-row2.clickable');
        if (el) { e.preventDefault(); toggleDot(el); }
    });

    // Exposed so other modals (e.g. the View Client engagement history list)
    // can open this same detail view without needing a static
    // .view-engagement-btn element present at page load.
    window.ViewEngagementModal = { open, modal, modalEl };

    document.querySelectorAll('.view-engagement-btn').forEach(btn => {
        btn.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                btn.click();
            }
        });
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            open(btn.dataset.engagementId, btn.dataset.avatarColor, btn.dataset.initials, btn.dataset.restrictFinancials === '1');
        });
    });
});
