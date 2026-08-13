document.addEventListener('DOMContentLoaded', () => {
    const modalEl = document.getElementById('viewEngagementModal');
    if (!modalEl) return;
    const modal = new bootstrap.Modal(modalEl);
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
    function card(title, color, bodyHtml) {
        return `
            <div class="eng-vm-card">
                <div class="eng-vm-card-title"><span class="eng-vm-card-dot" style="background:${color}"></span>${title}</div>
                ${bodyHtml}
            </div>`;
    }
    function detailRow(label, value) {
        return `<div class="detail-row"><span class="detail-label">${label}</span><span class="detail-value">${value || '<span class="text-muted">&mdash;</span>'}</span></div>`;
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
    function renderTimelineSection(audit, engagementId) {
        if (!audit.timeline) return '';
        const canManage = !!audit.can_manage_timeline;
        const canComplete = !!audit.can_complete_timeline;

        const rows = audit.timeline.map(step => {
            const isDone = !!step.completed;

            let dateHtml;
            if (canManage) {
                dateHtml = step.start_column
                    ? `<input type="date" class="eng-vm-tl-date-input" data-kind="timeline" data-engagement-id="${engagementId}" data-column="${step.start_column}" value="${toInputDate(step.start_date)}">
                       <span class="eng-vm-tl-date-sep">&ndash;</span>
                       <input type="date" class="eng-vm-tl-date-input" data-kind="timeline" data-engagement-id="${engagementId}" data-column="${step.date_column}" value="${toInputDate(step.date)}">`
                    : `<input type="date" class="eng-vm-tl-date-input" data-kind="timeline" data-engagement-id="${engagementId}" data-column="${step.date_column}" value="${toInputDate(step.date)}">`;
            } else {
                const range = step.start_date && step.date
                    ? `${fmtDate(step.start_date)} &ndash; ${fmtDate(step.date)}`
                    : fmtDate(step.date);
                dateHtml = `<span class="eng-vm-tl-date">${range || '<span class="text-muted">Not set</span>'}</span>`;
            }

            const dotAttrs = canComplete
                ? `role="button" tabindex="0" data-kind="timeline" data-engagement-id="${engagementId}" data-completed-column="${step.completed_column}" data-completed="${isDone ? '1' : '0'}"`
                : '';

            return `
                <div class="eng-vm-tl-row ${isDone ? 'done' : ''}">
                    <span class="eng-vm-tl-dot ${canComplete ? 'clickable' : ''}" ${dotAttrs}></span>
                    <span class="eng-vm-tl-label">${step.label}</span>
                    ${dateHtml}
                </div>`;
        }).join('');
        const weekly = audit.weekly_status_call
            ? `<div class="eng-vm-tl-weekly">Weekly status call: <strong>${audit.weekly_status_call.day}</strong>${audit.weekly_status_call.group_name ? ` &middot; ${audit.weekly_status_call.group_name}` : ''}</div>`
            : '';
        return card('Timeline', '#5aa8d6', `<div class="eng-vm-tl-list">${rows}</div>${weekly}`);
    }

    function renderMilestonesSection(audit) {
        if (!audit.milestones || audit.milestones.length === 0) return '';
        const canManage = !!audit.can_manage_timeline;
        const canComplete = !!audit.can_complete_timeline;

        const rows = audit.milestones.map(ms => {
            const isDone = ms.is_completed == 1;
            const dateHtml = canManage
                ? `<input type="date" class="eng-vm-tl-date-input" data-kind="milestone" data-milestone-id="${ms.milestone_id}" value="${toInputDate(ms.due_date)}">`
                : `<span class="eng-vm-tl-date">${fmtDate(ms.due_date) || '<span class="text-muted">Not set</span>'}</span>`;
            const dotAttrs = canComplete
                ? `role="button" tabindex="0" data-kind="milestone" data-milestone-id="${ms.milestone_id}" data-completed="${isDone ? '1' : '0'}"`
                : '';
            return `
                <div class="eng-vm-tl-row ${isDone ? 'done' : ''}">
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

    function renderDolSection(audit) {
        if (!audit.dol) return '';
        if (audit.dol.length === 0) {
            return card('Team (DOL)', '#7a8fd6', '<div class="text-muted" style="font-size:13px;">No DOL assigned yet.</div>');
        }

        // Group by role first (Manager/Senior/Staff/Intern, matching the
        // Engagement Tracker reference layout), then by person within it.
        const roleOrder = ['manager', 'senior', 'staff', 'intern'];
        const byRole = new Map();
        audit.dol.forEach(row => {
            const role = (row.role || '').toLowerCase() || 'other';
            if (!byRole.has(role)) byRole.set(role, new Map());
            const byPerson = byRole.get(role);
            if (!byPerson.has(row.user_id)) byPerson.set(row.user_id, { name: row.full_name, criteria: [] });
            byPerson.get(row.user_id).criteria.push({ criterion: row.criterion, color: row.audit_type_color, type: row.audit_type_name });
        });
        const orderedRoles = [...roleOrder.filter(r => byRole.has(r)), ...Array.from(byRole.keys()).filter(r => !roleOrder.includes(r))];

        const groups = orderedRoles.map(role => {
            const people = Array.from(byRole.get(role).values());
            const rows = people.map(person => `
                <div class="eng-vm-dol-row">
                    <div class="eng-vm-dol-name">${person.name}</div>
                    <div class="eng-vm-dol-chips">
                        ${person.criteria.map(c => `<span class="eng-vm-dol-chip" style="border-color:${c.color || '#9aa39d'}" title="${c.type || ''}">${c.criterion}</span>`).join('')}
                    </div>
                </div>`).join('');
            return `
                <div class="eng-vm-role-group">
                    <div class="eng-vm-role-group-title">${roleLabel(role)}${people.length > 1 ? ` (${people.length})` : ''}</div>
                    <div class="eng-vm-dol-list">${rows}</div>
                </div>`;
        }).join('');

        return card('Team (DOL)', '#7a8fd6', groups);
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

    function renderOverviewCard(data) {
        const d = data.details || {};
        const reviewPeriod = d.review_period_start && d.review_period_end
            ? `${fmtDate(d.review_period_start)} &ndash; ${fmtDate(d.review_period_end)}`
            : null;
        const rows = [
            detailRow('Manager', data.manager),
            detailRow('Point of Contact', d.poc),
            detailRow('Location', d.location),
            detailRow('Review Period', reviewPeriod),
            detailRow('Report / SOC Type', d.soc_type),
            detailRow('TSC', d.tsc),
        ].join('');
        return card('Overview', '#003f47', rows);
    }

    function renderDetailsCard(data) {
        const d = data.details;
        if (!d) return '';
        const rows = [
            detailRow('As-of Date', fmtDate(d.as_of_date)),
            detailRow('Scope', d.scope),
            detailRow('Created', fmtDate(d.created_at)),
            detailRow('Last Updated', fmtDate(d.updated_at)),
            d.planning_doc_url ? `<div class="detail-row"><span class="detail-label">Planning Doc</span><span class="detail-value"><a href="${d.planning_doc_url}" target="_blank" rel="noopener">Open link</a></span></div>` : '',
        ].join('');
        return card('Details', '#e0994c', rows);
    }

    function renderNotesCard(data) {
        const notes = (data.details && data.details.notes) || data.notes;
        if (!notes) return '';
        return card('Notes', '#9b6bd6', `<p style="font-size:13px; margin:0; white-space:pre-wrap;">${notes}</p>`);
    }

    let lastOpenArgs = null;

    async function open(engagementId, avatarColor, initials, restrictFinancials) {
        avatarColor = avatarColor || '#4f8ef7';
        initials = initials || '?';
        if (!engagementId) return;
        lastOpenArgs = [engagementId, avatarColor, initials, restrictFinancials];

        modalBody.innerHTML = '<div class="text-center text-muted py-4">Loading...</div>';
        modal.show();

        try {
            const res = await fetch(`engagement-details.php?id=${encodeURIComponent(engagementId)}`);
            const data = await res.json();

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

            const empRowsHtml = employees.length > 0
                ? employees.map(emp => `
                    <div class="eng-vm-emp-row">
                        <div class="eng-vm-emp-avatar">${(emp.name || '?').trim().split(/\s+/).slice(0, 2).map(w => w[0].toUpperCase()).join('')}</div>
                        <div class="eng-vm-emp-info">
                            <div class="eng-vm-emp-name">${emp.name}</div>
                            <div class="eng-vm-emp-role">${emp.role}${emp.audit_type_name ? ` &middot; <span class="audit-type-dot" style="background:${emp.audit_type_color || '#9aa39d'}"></span>${emp.audit_type_name}` : ''}</div>
                        </div>
                        <div class="eng-vm-emp-hours">${emp.hours}h</div>
                    </div>
                `).join('')
                : '<div class="eng-vm-emp-row"><div class="eng-vm-emp-info"><div class="eng-vm-emp-name text-muted">No employees assigned yet</div></div></div>';

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

            modalBody.innerHTML = `
                <div class="eng-vm-header">
                    <div class="eng-vm-client-row">
                        <div class="eng-vm-tile" style="background-color:${avatarColor};">${initials}</div>
                        <div>
                            <div class="eng-vm-client-name">${data.client_name || ''}${data.year ? ` <span class="text-muted" style="font-weight:500;">&middot; ${data.year}</span>` : ''}</div>
                            <span class="eng-status-pill ${statusClass(data.status)}"><span class="dot"></span>${statusLabel(data.status)}</span>
                            ${chipsRow}
                        </div>
                    </div>
                </div>
                <div class="eng-vm-body">
                    ${renderOverviewCard(data)}
                    ${card('Capacity', '#4fbf9f', capacityBody)}
                    ${renderDetailsCard(data)}
                    ${renderNotesCard(data)}
                    ${card('Assigned Employees', '#4f8ef7', `<div class="eng-vm-emp-list">${empRowsHtml}</div>`)}
                    ${renderTimelineSection(data.audit || {}, engagementId)}
                    ${renderMilestonesSection(data.audit || {})}
                    ${renderDolSection(data.audit || {})}
                    ${renderIndependenceSection(data.audit || {})}
                </div>
            `;
        } catch (err) {
            console.error('Failed to load engagement details', err);
            modalBody.innerHTML = '<div class="text-center text-danger py-4">Could not load engagement details.</div>';
        }
    }

    function refresh() {
        if (lastOpenArgs) open(...lastOpenArgs);
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
    modalBody.addEventListener('click', (e) => {
        const dot = e.target.closest('.eng-vm-tl-dot.clickable');
        if (dot) toggleDot(dot);
    });
    modalBody.addEventListener('keydown', (e) => {
        if (e.key !== 'Enter' && e.key !== ' ') return;
        const dot = e.target.closest('.eng-vm-tl-dot.clickable');
        if (dot) { e.preventDefault(); toggleDot(dot); }
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
