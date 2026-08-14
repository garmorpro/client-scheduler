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
        if (typeof appNotify !== 'undefined') {
            appNotify({ icon: isError ? 'error' : 'success', title: message, timer: isError ? undefined : 1100 });
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
    // Dates are always displayed read-only here - label-over-date, colored
    // status dot, click a row to toggle complete/incomplete - matching the
    // Engagement Tracker reference. Actually changing a date happens in the
    // separate #editTimelineModal (a real modal, not inline boxes - see
    // openEditTimelineModal()), which is also where Import Timeline routes
    // its matched dates for review before anything saves.
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

    function renderTimelineSection(audit, engagementId, details) {
        details = details || {};
        if (!audit.timeline) return '';
        const canManage = !!audit.can_manage_timeline;
        const canComplete = !!audit.can_complete_timeline;

        const rows = audit.timeline.map(step => timelineRowReadOnly(step, engagementId, canComplete)).join('');
        const hint = canComplete
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
                    <div class="eng-vm-tl-weekly-inputs">
                        <select class="eng-vm-tl-date-input" id="engVmWeeklyDayInput" data-engagement-id="${engagementId}" data-group-name="${wsc.group_name || ''}">
                            <option value="">No call set</option>
                            ${dayOptions}
                        </select>
                        <input type="time" class="eng-vm-tl-date-input" id="engVmWeeklyTimeInput" data-engagement-id="${engagementId}" value="${wsc.time_input || ''}">
                    </div>
                </div>`;
        } else if (wsc.day) {
            weekly = `<div class="eng-vm-tl-weekly">Weekly status call: <strong>${wsc.day}${wsc.time_label ? ` at ${wsc.time_label}` : ''}</strong>${wsc.group_name ? ` &middot; ${wsc.group_name}` : ''}</div>`;
        }

        // Once a doc exists, "Upload" stops making sense as the primary
        // action - View/Replace/Remove instead. The hidden file input is
        // still there either way, just relabeled to "Replace" when it's
        // swapping an existing file rather than adding a first one.
        const fileInput = `<input type="file" id="engVmPlanningDocInput" data-engagement-id="${engagementId}" hidden>`;
        let uploadRow;
        if (canManage && details.planning_doc_url) {
            uploadRow = `<div class="eng-vm-tl-upload-row">
                   <a href="#" class="eng-vm-upload-link eng-vm-view-planning-doc" data-engagement-id="${engagementId}" data-doc-url="${details.planning_doc_url}"><i class="bi bi-eye"></i> View Planning Doc</a>
                   <label class="eng-vm-upload-link"><i class="bi bi-arrow-repeat"></i> Replace${fileInput}</label>
                   <a href="#" class="eng-vm-upload-link danger eng-vm-remove-planning-doc" data-engagement-id="${engagementId}"><i class="bi bi-trash"></i> Remove</a>
               </div>`;
        } else if (canManage) {
            uploadRow = `<div class="eng-vm-tl-upload-row">
                   <label class="eng-vm-upload-link"><i class="bi bi-upload"></i> Upload Planning Doc${fileInput}</label>
               </div>`;
        } else {
            uploadRow = details.planning_doc_url
                ? `<div class="eng-vm-tl-upload-row"><a href="#" class="eng-vm-upload-link eng-vm-view-planning-doc" data-engagement-id="${engagementId}" data-doc-url="${details.planning_doc_url}"><i class="bi bi-eye"></i> View Planning Doc</a></div>`
                : '';
        }

        const titleAction = canManage
            ? `<div class="eng-vm-tl-title-actions">
                   <a href="#" class="eng-vm-card-title-action" id="engVmTimelineImportBtn">Import Timeline</a>
                   <a href="#" class="eng-vm-card-title-action" id="engVmTimelineEditBtn">Edit Timeline</a>
                   <input type="file" id="engVmTimelineImportInput" accept=".xlsx,.xls,.csv" hidden>
               </div>`
            : '';

        return card(
            'Timeline & Key Dates', '#2f9e57',
            `${uploadRow}<div class="eng-vm-tl-list2">${rows}</div>${hint}${weekly}`,
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

    // Role -> CSS variable, matching ET's --manager/--senior/--staff/--intern
    // tokens exactly (defined at the top of styles.css, light + dark).
    const TEAM_ROLE_COLOR_VAR = { manager: '--eng-role-manager', senior: '--eng-role-senior', staff: '--eng-role-staff', intern: '--eng-role-intern' };
    function teamAvatar(role, name, extraClass) {
        const colorVar = `var(${TEAM_ROLE_COLOR_VAR[role] || '--eng-role-staff'})`;
        return `<div class="eng-vm-team-avatar${extraClass ? ' ' + extraClass : ''}" style="background:${colorVar}">${initials(name)}</div>`;
    }

    // One merged "Team" section (Assigned Employees + DOL, previously two
    // separate cards) - structure and styling copied from Engagement
    // Tracker's own renderDrawerTeam() (pages/dashboard.php): the Manager
    // gets a tinted "lead row" instead of sitting in a Manager group of
    // its own, Senior/Staff/Intern are divided rows (not boxed) under a
    // role-colored avatar, and DOL is listed per audit type
    // ("SOC 2  [CC4][CC5]...") rather than one flat chip row. Hours stay
    // on the right - ET's own Team(DOL) card doesn't show hours at all
    // (it's a separate card there), but ours intentionally merged
    // Assigned Employees + DOL into one, so hours is real functionality
    // to keep, not something to drop just to match visually.
    // Read-only indicator for a teammate's independence status, or a
    // clickable trigger opening the Independence popup if this row belongs
    // to the person looking at it - independence is self-certified
    // (audit_team_independence is one attestation per person per
    // engagement), never set on someone else's behalf.
    function independenceIconParts(value) {
        if (value === 'Y') return { icon: '&#10003;', cls: 'yes', label: 'Independent' };
        if (value === 'N') return { icon: '&#10007;', cls: 'no', label: 'Not independent' };
        return { icon: '?', cls: 'unset', label: 'Not yet confirmed' };
    }

    function independenceControl(person, independenceByUser, engagementId, clientName) {
        const value = independenceByUser.get(person.user_id) || null;
        const isSelf = person.user_id === (window.CURRENT_USER_ID || 0);
        const { icon, cls, label } = independenceIconParts(value);

        if (!isSelf) {
            return `<span class="eng-vm-indep-icon ${cls}" title="Independence: ${label}">${icon}</span>`;
        }

        return `<button type="button" class="eng-vm-indep-icon self ${cls}" data-engagement-id="${engagementId}" data-client-name="${clientName}" data-value="${value || ''}" title="Independence: ${label} - click to change">${icon}</button>`;
    }

    function renderTeamSection(data, engagementId) {
        const employees = data.assigned_employees || [];
        const audit = data.audit || {};
        const clientName = data.client_name || '';
        const canManageEng = !!data.can_manage_engagement;
        const titleAction = [
            audit.can_manage_dol ? `<a href="dol-generator.php?engagement_id=${engagementId}" class="eng-vm-card-title-action">Edit DOL</a>` : '',
            canManageEng ? `<a href="#" class="eng-vm-card-title-action eng-vm-add-team-member-btn" data-engagement-id="${engagementId}">+ Add Team Member</a>` : '',
        ].filter(Boolean).join('');

        // manager_user (resolved server-side from engagements.manager, a
        // plain name field - see engagement-details.php) can exist even when
        // nobody has logged hours yet, so an assigned-manager-only
        // engagement still isn't "no employees assigned".
        if (employees.length === 0 && !data.manager_user) {
            return card('Team', '#003f47', '<div class="text-muted" style="font-size:13px;">No employees assigned yet.</div>', titleAction);
        }

        // Per person, DOL grouped by audit type name (not one flat list) -
        // matches ET's "AUDIT TYPE  [chip][chip]" line-per-type format.
        const dolByUser = new Map();
        (audit.dol || []).forEach(row => {
            if (!dolByUser.has(row.user_id)) dolByUser.set(row.user_id, new Map());
            const byType = dolByUser.get(row.user_id);
            const typeName = row.audit_type_name || '';
            if (!byType.has(typeName)) byType.set(typeName, { color: row.audit_type_color, criteria: [] });
            byType.get(typeName).criteria.push(row.criterion);
        });

        const independenceByUser = new Map();
        (audit.independence || []).forEach(row => independenceByUser.set(row.user_id, row.independent));

        const roleOrder = ['manager', 'senior', 'staff', 'intern'];
        const byRole = new Map();
        employees.forEach(emp => {
            const role = (emp.role || '').toLowerCase() || 'other';
            if (!byRole.has(role)) byRole.set(role, new Map());
            const byPerson = byRole.get(role);
            if (!byPerson.has(emp.user_id)) {
                byPerson.set(emp.user_id, { user_id: emp.user_id, name: emp.name, hours: 0, dolByType: dolByUser.get(emp.user_id) || new Map() });
            }
            byPerson.get(emp.user_id).hours += emp.hours;
        });

        function dolLinesHtml(person) {
            const groups = Array.from(person.dolByType.entries()).filter(([, g]) => g.criteria.length);
            if (!groups.length) return '<span class="eng-vm-team-no-dol">No DOL assigned</span>';
            return `<div class="eng-vm-team-dol-lines">${groups.map(([typeName, g]) => `
                <div class="eng-vm-team-dol-line">
                    <span class="eng-vm-team-dol-audit-label">${typeName}</span>
                    <span class="eng-vm-team-dol-chips">${g.criteria.map(c => `<span class="eng-vm-team-dol-chip" style="background:${g.color || '#9aa39d'}22; color:${g.color || '#6b7570'}">${c}</span>`).join('')}</span>
                </div>`).join('')}</div>`;
        }

        function memberRow(role, person) {
            // Blank rather than "0h" - same treatment as the lead row above.
            // In practice 0h only ever happens for someone staged via Add
            // Team Member (a placeholder entries row with no hours yet),
            // since anyone else here has real logged work.
            return `
                <div class="eng-vm-team-member-row">
                    ${teamAvatar(role, person.name)}
                    <div class="eng-vm-team-member-info">
                        <div class="eng-vm-team-member-name">${person.name}</div>
                        ${dolLinesHtml(person)}
                    </div>
                    ${independenceControl(person, independenceByUser, engagementId, clientName)}
                    <div class="eng-vm-team-hours">${person.hours > 0 ? person.hours + 'h' : ''}</div>
                </div>`;
        }

        let html = '';
        // The lead row is always the engagement's official manager (the
        // `manager` field on `engagements`, resolved server-side to a real
        // user) - prefer their entries-based row (real hours/DOL) when they
        // have one, falling back to a bare 0h/no-DOL placeholder when they
        // haven't logged anything on this engagement yet.
        //
        // Someone else can also hold role=manager and have real entries
        // here without being the engagement's official manager - e.g. staffed
        // as a senior/staff, then promoted to manager afterward. They still
        // need to show up (and stay DOL-assignable), so they get a normal
        // "Manager (N)" role group below instead of being silently dropped -
        // this used to only ever show ONE manager total, since byRole's
        // 'manager' bucket was entirely excluded from the group loop.
        const managerPeople = byRole.has('manager') ? Array.from(byRole.get('manager').values()) : [];
        const officialManagerId = data.manager_user ? data.manager_user.user_id : null;
        const managerFromEntries = officialManagerId != null
            ? managerPeople.find(p => p.user_id === officialManagerId)
            : managerPeople[0];
        const manager = managerFromEntries || (data.manager_user
            ? { user_id: data.manager_user.user_id, name: data.manager_user.full_name, hours: 0, dolByType: new Map() }
            : null);
        if (manager) {
            html += `
                <div class="eng-vm-team-lead-row">
                    ${teamAvatar('manager', manager.name, 'lead')}
                    <div class="eng-vm-team-lead-info">
                        <div class="eng-vm-team-lead-name">${manager.name}</div>
                        <div class="eng-vm-team-lead-role">Manager</div>
                    </div>
                    ${independenceControl(manager, independenceByUser, engagementId, clientName)}
                    <div class="eng-vm-team-hours">${manager.hours > 0 ? manager.hours + 'h' : ''}</div>
                </div>`;
        }
        // Any other staffed manager goes right under the lead row, ahead of
        // Senior/Staff/Intern - same seniority ordering (manager > senior >
        // staff > intern) as everywhere else, just with the engagement's
        // official manager pulled out into its own lead row above it.
        const otherManagers = managerPeople.filter(p => !manager || p.user_id !== manager.user_id);
        if (otherManagers.length > 0) {
            html += `
                <div class="eng-vm-team-role-group">
                    <div class="eng-vm-team-role-label">${roleLabel('manager')} (${otherManagers.length})</div>
                    ${otherManagers.map(p => memberRow('manager', p)).join('')}
                </div>`;
        }

        const otherRoles = [...roleOrder.filter(r => r !== 'manager' && byRole.has(r)), ...Array.from(byRole.keys()).filter(r => !roleOrder.includes(r))];
        otherRoles.forEach(role => {
            const people = Array.from(byRole.get(role).values());
            html += `
                <div class="eng-vm-team-role-group">
                    <div class="eng-vm-team-role-label">${roleLabel(role)} (${people.length})</div>
                    ${people.map(p => memberRow(role, p)).join('')}
                </div>`;
        });

        return card('Team', '#003f47', html, titleAction);
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
        // PCI Assessment Type + Delivery Date only matter for PCI
        // engagements, regardless of what else is checked alongside PCI. Not
        // every PCI engagement produces a ROC (Report on Compliance) -
        // smaller merchants/service providers instead get an AOC or file a
        // SAQ variant, so the assessment type is tracked separately.
        const isPci = (data.audit_types || []).some(t => t.name === 'PCI');
        const fields = [
            gridField('Location', d.location || 'N/A'),
            gridField('Review Period', reviewPeriod),
            gridField('Audit Type', auditTypeNames),
            reportType ? gridField('Report Type', reportType) : '',
            isPci ? gridField('PCI Assessment Type', d.pci_assessment_type || 'N/A') : '',
            isPci ? gridField('PCI Delivery Date', fmtDate(d.pci_delivery_date) || 'N/A') : '',
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

    // `isRefresh` is only ever true when called from refresh() itself - a
    // real "open a (possibly different) engagement" click always scrolls to
    // the top, same as before. On a refresh (re-fetching the same
    // engagement after a save, e.g. clicking a Timeline & Key Dates item),
    // skip the "Loading..." placeholder and restore the scroll position
    // once the refreshed content is back in. The actual scrolling element
    // is .eng-vm-body (see styles.css - modalBody itself doesn't scroll,
    // .offcanvas-body has overflow:hidden), and it gets torn down and
    // rebuilt fresh on every render since it's part of the template
    // string below - so the scrollTop has to be read from and re-applied
    // to that inner element specifically, not modalBody.
    async function open(engagementId, avatarColor, initials, restrictFinancials, isRefresh) {
        avatarColor = avatarColor || '#4f8ef7';
        initials = initials || '?';
        if (!engagementId) return;
        lastOpenArgs = [engagementId, avatarColor, initials, restrictFinancials];

        const existingBodyEl = isRefresh ? modalBody.querySelector('.eng-vm-body') : null;
        const preservedScrollTop = existingBodyEl ? existingBodyEl.scrollTop : 0;
        if (!isRefresh) {
            modalBody.innerHTML = '<div class="text-center text-muted py-4">Loading...</div>';
        }
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

            // Staff/Senior viewing their own schedule (restrictFinancials)
            // don't get a Capacity card at all - not even the Employees
            // count on its own, since that's not their business to see from
            // My Schedule. Managers/Admin/whoever else can manage the
            // engagement still get the full budget/allocation/utilization
            // breakdown.
            const capacityBody = `<div class="eng-vm-stat-row">
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
                    <div class="eng-vm-client-name-lg">${data.display_name || data.client_name || ''}</div>
                    <div class="eng-vm-status-row">
                        ${statusHtml}
                        ${chipsRow}
                    </div>
                    ${actionsHtml}
                </div>
                <div class="eng-vm-divider"></div>
                <div class="eng-vm-body">
                    ${renderOverviewCard(data)}
                    ${restrictFinancials ? '' : card('Capacity', '#4fbf9f', capacityBody)}
                    ${renderDetailsCard(data)}
                    ${renderNotesCard(data)}
                    ${renderTeamSection(data, engagementId)}
                    ${renderTimelineSection(data.audit || {}, engagementId, data.details)}
                    ${renderMilestonesSection(data.audit || {})}
                </div>
            `;

            wireHeaderActions(data, engagementId, avatarColor, initials);
            if (isRefresh) {
                const newBodyEl = modalBody.querySelector('.eng-vm-body');
                if (newBodyEl) newBodyEl.scrollTop = preservedScrollTop;
            }
        } catch (err) {
            console.error('Failed to load engagement details', err);
            modalBody.innerHTML = '<div class="text-center text-danger py-4">Could not load engagement details.</div>';
        }
    }

    function refresh() {
        if (lastOpenArgs) open(lastOpenArgs[0], lastOpenArgs[1], lastOpenArgs[2], lastOpenArgs[3], true);
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
                    engagementName: data.engagement_name,
                    budgetedHours: data.budgeted_hours,
                    status: data.status,
                    manager: data.manager,
                    notes: data.notes,
                    location: data.details && data.details.location,
                    poc: data.details && data.details.poc,
                    scope: data.details && data.details.scope,
                    repeatFlag: !!(data.details && data.details.repeat_flag == 1),
                    socType: data.details && data.details.soc_type,
                    asOfDate: toInputDate(data.details && data.details.as_of_date),
                    reviewPeriodStart: toInputDate(data.details && data.details.review_period_start),
                    reviewPeriodEnd: toInputDate(data.details && data.details.review_period_end),
                    pciAssessmentTypes: ((data.details && data.details.pci_assessment_type) || '').split(',').map(s => s.trim()).filter(Boolean),
                    pciDeliveryDate: toInputDate(data.details && data.details.pci_delivery_date),
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
                if (typeof appConfirm !== 'undefined') {
                    appConfirm({ icon: 'warning', title: 'Archive this engagement?', text: 'It will move to Archived and out of the active list.', confirmText: 'Archive' })
                        .then(confirmed => { if (confirmed) run(); });
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
    // so it can be re-bound after saveTimelineField's refresh() re-renders
    // the whole panel from scratch.
    function wireTimelineCardActions(engagementId) {
        const editBtn = document.getElementById('engVmTimelineEditBtn');
        if (editBtn) {
            editBtn.addEventListener('click', (e) => {
                e.preventDefault();
                openEditTimelineModal(engagementId);
            });
        }

        const importBtn = document.getElementById('engVmTimelineImportBtn');
        const importInput = document.getElementById('engVmTimelineImportInput');
        if (importBtn && importInput) {
            importBtn.addEventListener('click', (e) => {
                e.preventDefault();
                importInput.click();
            });
            importInput.addEventListener('change', () => {
                const file = importInput.files[0];
                importInput.value = '';
                if (file) importTimelineFromFile(file, engagementId);
            });
        }

        // Day and time save together (not two independent partial updates) -
        // the endpoint sets every column on each call, so saving one without
        // the other would silently clear it.
        const weeklyDayInput = document.getElementById('engVmWeeklyDayInput');
        const weeklyTimeInput = document.getElementById('engVmWeeklyTimeInput');
        if (weeklyDayInput || weeklyTimeInput) {
            const saveWeeklyCall = async () => {
                try {
                    const res = await fetch('update_audit_weekly_call.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            engagement_id: engagementId,
                            day: weeklyDayInput && weeklyDayInput.value !== '' ? weeklyDayInput.value : null,
                            time: weeklyTimeInput && weeklyTimeInput.value !== '' ? weeklyTimeInput.value : null,
                            group_name: (weeklyDayInput && weeklyDayInput.dataset.groupName) || ''
                        })
                    });
                    const result = await res.json();
                    if (!result.success) notify(result.error || 'Could not save.', true);
                    refresh();
                } catch (err) {
                    console.error('Failed to save weekly call', err);
                    notify('Network error. Please try again.', true);
                }
            };
            if (weeklyDayInput) weeklyDayInput.addEventListener('change', saveWeeklyCall);
            if (weeklyTimeInput) weeklyTimeInput.addEventListener('change', saveWeeklyCall);
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

        const removeDocBtn = document.querySelector('.eng-vm-remove-planning-doc');
        if (removeDocBtn) {
            removeDocBtn.addEventListener('click', async (e) => {
                e.preventDefault();
                const run = async () => {
                    try {
                        const res = await fetch('remove_planning_doc.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ engagement_id: engagementId })
                        });
                        const result = await res.json();
                        if (!result.success) notify(result.error || 'Could not remove file.', true);
                        refresh();
                    } catch (err) {
                        console.error('Failed to remove planning doc', err);
                        notify('Network error. Please try again.', true);
                    }
                };
                if (typeof appConfirm !== 'undefined') {
                    const confirmed = await appConfirm({ icon: 'warning', title: 'Remove this planning doc?', text: 'This deletes the uploaded file. You can upload a new one afterward.', confirmText: 'Remove', danger: true });
                    if (confirmed) run();
                } else if (confirm('Remove this planning doc?')) {
                    run();
                }
            });
        }
    }

    // `silent` skips the auto-refresh() - used by the Import Timeline flow
    // below, which fires several of these at once and does one combined
    // refresh() at the end instead of one full panel refetch per field.
    async function saveTimelineField(column, engagementId, value, silent) {
        try {
            const res = await fetch('update_audit_timeline_field.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ engagement_id: engagementId, column, value })
            });
            const result = await res.json();
            if (!result.success) notify(result.error || 'Could not save.', true);
            if (!silent) refresh();
            return !!result.success;
        } catch (err) {
            console.error('Failed to save timeline field', err);
            notify('Network error. Please try again.', true);
            return false;
        }
    }

    // ---------- Import Timeline (.xlsx/.xls/.csv) ----------
    // Field-matching logic (exact task-name match first, falling back to
    // looser keyword patterns) copied directly from Engagement Tracker's
    // own matchTimelineFieldsFromRows()/TIMELINE_IMPORT_PATTERNS
    // (pages/dashboard.php) - the column names are identical between the
    // two apps (this schema was migrated from there), so no field mapping
    // was needed, just the matching rules themselves. Unlike ET, which
    // routes matched dates into a separate Edit Timeline modal for review,
    // this saves straight into the same inline editable fields behind our
    // own "Edit Timeline" toggle (switching into edit mode first if
    // needed) - one less UI to build, same "always reviewable, never a
    // silent blind save" outcome since the dates land in editable boxes,
    // not committed instantly without a chance to look at them.
    const TIMELINE_FIELD_LABELS = {
        internal_planning_call_date: 'Internal Planning Call',
        planning_memo_date: 'Planning Memo',
        irl_due_date: 'IRL Due',
        client_planning_call_date: 'Client Planning Call',
        fieldwork_client_calls_end_date: 'Fieldwork - Client Calls',
        fieldwork_documentation_end_date: 'Fieldwork - Documentation',
        leadsheet_date: 'Leadsheet Due',
        conclusion_memo_date: 'Conclusion Memo',
        draft_report_due_date: 'Draft Report Due',
        final_report_date: 'Final Report',
        archive_date: 'Archive',
    };
    const TIMELINE_IMPORT_PATTERNS = {
        internal_planning_call_date: { exact: 'Internal Team Planning Call', fallback: [/internal.*planning.*call/i] },
        planning_memo_date: { exact: 'Compose Planning Memo', fallback: [/\bplanning memo\b/i] },
        irl_due_date: { exact: 'Send Information Request List (IRL)', fallback: [/\birl\b/i, /information request list/i] },
        client_planning_call_date: { exact: 'Client Planning Call', fallback: [/client.*planning.*call/i] },
        fieldwork_client_calls_end_date: { exact: 'Client Calls', fallback: [/\bclient\s*calls?\b/i, /fieldwork.*client.*call/i] },
        fieldwork_documentation_end_date: { exact: 'Documentation', fallback: [/\bdocumentation\b/i] },
        leadsheet_date: { exact: 'Lead Sheets Due', fallback: [/lead\s*sheet/i] },
        conclusion_memo_date: { exact: 'Compose Conclusion Memo', fallback: [/conclusion memo/i] },
        draft_report_due_date: { exact: 'Draft Report Due', fallback: [/draft report.*due/i] },
        final_report_date: { exact: 'Final Report Due', fallback: [/final report/i] },
        archive_date: { exact: 'Alek Archive', fallback: [/\barchive\b/i] },
    };
    // End-date field -> paired start-date field, both filled from the same
    // matched spreadsheet row when the sheet has a start-ish column.
    const RANGE_FIELD_PAIRS = {
        fieldwork_client_calls_end_date: 'fieldwork_client_calls_start_date',
        fieldwork_documentation_end_date: 'fieldwork_documentation_start_date',
    };

    let xlsxLibPromise = null;
    function loadXlsxLib() {
        if (window.XLSX) return Promise.resolve();
        if (!xlsxLibPromise) {
            xlsxLibPromise = new Promise((resolve, reject) => {
                const s = document.createElement('script');
                s.src = 'https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js';
                s.onload = resolve;
                s.onerror = () => reject(new Error('Could not load the spreadsheet library.'));
                document.head.appendChild(s);
            });
        }
        return xlsxLibPromise;
    }

    function parseSpreadsheetRows(file) {
        return new Promise((resolve, reject) => {
            const reader = new FileReader();
            reader.onload = (e) => {
                try {
                    const data = new Uint8Array(e.target.result);
                    // cellDates:false - read raw values (Excel serials / plain
                    // text) so date conversion never passes through a JS
                    // Date/timezone at all.
                    const workbook = XLSX.read(data, { type: 'array', cellDates: false, raw: true });
                    const sheet = workbook.Sheets[workbook.SheetNames[0]];
                    resolve(XLSX.utils.sheet_to_json(sheet, { defval: '', raw: true }));
                } catch (err) {
                    reject(err);
                }
            };
            reader.onerror = () => reject(new Error('Could not read the file'));
            reader.readAsArrayBuffer(file);
        });
    }

    // No Date object anywhere in here on purpose - Date/.toISOString() shifts
    // date-only values by a day depending on local timezone.
    function parseDateCell(value) {
        if (value === '' || value === null || value === undefined) return null;
        if (typeof value === 'number') {
            const d = XLSX.SSF.parse_date_code(value);
            if (!d) return null;
            return `${d.y}-${String(d.m).padStart(2, '0')}-${String(d.d).padStart(2, '0')}`;
        }
        const str = String(value).trim();
        const slash = str.match(/^(\d{1,2})\/(\d{1,2})\/(\d{2,4})$/);
        if (slash) {
            let [, mo, da, yr] = slash;
            if (yr.length === 2) yr = (Number(yr) < 70 ? '20' : '19') + yr;
            return `${yr}-${mo.padStart(2, '0')}-${da.padStart(2, '0')}`;
        }
        const iso = str.match(/^(\d{4})-(\d{2})-(\d{2})/);
        if (iso) return `${iso[1]}-${iso[2]}-${iso[3]}`;
        return null;
    }

    function matchTimelineFieldsFromRows(rows) {
        if (!rows.length) return { matched: {}, unmatched: Object.keys(TIMELINE_IMPORT_PATTERNS) };
        const keys = Object.keys(rows[0]);
        const taskKey = keys.find(k => /task/i.test(k) && /name/i.test(k)) || keys.find(k => /task/i.test(k)) || keys[0];
        const dateKey = keys.find(k => /planned/i.test(k) && /finish/i.test(k))
            || keys.find(k => /finish/i.test(k))
            || keys.find(k => /due/i.test(k));
        const startDateKey = keys.find(k => /planned/i.test(k) && /start/i.test(k))
            || keys.find(k => /^start/i.test(k.trim()))
            || keys.find(k => /start/i.test(k));

        const matched = {};
        const unmatched = [];
        Object.entries(TIMELINE_IMPORT_PATTERNS).forEach(([field, { exact, fallback }]) => {
            if (!dateKey) { unmatched.push(field); return; }

            const startField = RANGE_FIELD_PAIRS[field];
            if (startField) {
                let matchedRows = rows.filter(r => String(r[taskKey] || '').trim().toLowerCase() === exact.toLowerCase());
                if (!matchedRows.length) {
                    for (const pattern of fallback) {
                        matchedRows = rows.filter(r => pattern.test(String(r[taskKey] || '').trim()));
                        if (matchedRows.length) break;
                    }
                }
                const endDates = matchedRows.map(r => parseDateCell(r[dateKey])).filter(Boolean).sort();
                if (!endDates.length) { unmatched.push(field); return; }
                matched[field] = endDates[endDates.length - 1];

                if (startDateKey) {
                    const startDates = matchedRows.map(r => parseDateCell(r[startDateKey])).filter(Boolean).sort();
                    if (startDates.length) matched[startField] = startDates[0];
                }
                return;
            }

            let row = rows.find(r => String(r[taskKey] || '').trim().toLowerCase() === exact.toLowerCase());
            if (!row) {
                for (const pattern of fallback) {
                    row = rows.find(r => pattern.test(String(r[taskKey] || '').trim()));
                    if (row) break;
                }
            }
            const parsedDate = row ? parseDateCell(row[dateKey]) : null;
            if (parsedDate) {
                matched[field] = parsedDate;
            } else {
                unmatched.push(field);
            }
        });
        return { matched, unmatched };
    }

    // Every date column shown in #editTimelineModal - matches ET's own
    // Edit Timeline modal field set exactly, including both halves of the
    // two fieldwork ranges.
    const TIMELINE_MODAL_COLUMNS = [
        'internal_planning_call_date', 'planning_memo_date', 'irl_due_date', 'client_planning_call_date',
        'fieldwork_client_calls_start_date', 'fieldwork_client_calls_end_date',
        'fieldwork_documentation_start_date', 'fieldwork_documentation_end_date',
        'leadsheet_date', 'conclusion_memo_date', 'draft_report_due_date', 'final_report_date', 'archive_date',
    ];

    // Flattens the last-fetched audit.timeline array (one object per step,
    // carrying date_column/start_column) into a plain column -> value map,
    // so the modal's inputs can be pre-filled from whatever the last load
    // actually has, regardless of overrides from an import.
    function timelineCurrentValues() {
        const values = {};
        ((lastData && lastData.audit && lastData.audit.timeline) || []).forEach(step => {
            if (step.date_column) values[step.date_column] = toInputDate(step.date);
            if (step.start_column) values[step.start_column] = toInputDate(step.start_date);
        });
        return values;
    }

    let editTimelineModalInstance = null;
    let editTimelineEngagementId = null;

    // `overrides`/`unmatchedLabels` are only passed by the Import Timeline
    // flow below - a plain "Edit Timeline" click opens with just the
    // engagement's current values and no banner.
    function openEditTimelineModal(engagementId, overrides, unmatchedLabels) {
        const modalEl = document.getElementById('editTimelineModal');
        if (!modalEl) return;
        if (!editTimelineModalInstance) editTimelineModalInstance = bootstrap.Modal.getOrCreateInstance(modalEl);
        editTimelineEngagementId = engagementId;

        const current = timelineCurrentValues();
        TIMELINE_MODAL_COLUMNS.forEach(column => {
            const input = document.getElementById('tl_' + column);
            if (input) input.value = (overrides && overrides[column]) || current[column] || '';
        });

        const banner = document.getElementById('editTimelineImportBanner');
        if (overrides) {
            const importedCount = Object.keys(overrides).length;
            let html = importedCount
                ? `Filled ${importedCount} date${importedCount === 1 ? '' : 's'} from your spreadsheet.`
                : 'Could not match any task names from your spreadsheet.';
            if (unmatchedLabels && unmatchedLabels.length) html += `<br>Couldn't find a match for: ${unmatchedLabels.join(', ')}.`;
            html += ' Review before saving.';
            banner.innerHTML = html;
            banner.classList.remove('d-none');
        } else {
            banner.classList.add('d-none');
            banner.innerHTML = '';
        }

        editTimelineModalInstance.show();
    }

    let independenceModalInstance = null;
    let independenceEngagementId = null;

    function openIndependenceModal(engagementId, clientName, currentValue) {
        const modalEl = document.getElementById('independenceModal');
        if (!modalEl) return;
        if (!independenceModalInstance) independenceModalInstance = bootstrap.Modal.getOrCreateInstance(modalEl);
        independenceEngagementId = engagementId;

        document.getElementById('independenceModalSubtitle').textContent = `Confirm your independence from ${clientName || 'this client'}`;
        document.querySelectorAll('#independenceOptions input[name="independentValue"]').forEach(input => {
            input.checked = input.value === (currentValue || '');
        });

        independenceModalInstance.show();
    }

    let planningDocPreviewModalInstance = null;

    // Only pdf/png/jpg/jpeg actually render in an iframe/img - everything
    // else (doc/xlsx/pptx/etc.) has no in-browser renderer here, so those
    // get a plain "download instead" message rather than a broken preview.
    const PLANNING_DOC_PREVIEWABLE_EXTENSIONS = ['pdf', 'png', 'jpg', 'jpeg'];

    function openPlanningDocPreview(engagementId, docUrl) {
        const modalEl = document.getElementById('planningDocPreviewModal');
        if (!modalEl) return;
        if (!planningDocPreviewModalInstance) planningDocPreviewModalInstance = bootstrap.Modal.getOrCreateInstance(modalEl);

        const ext = (docUrl.split('.').pop() || '').toLowerCase();
        const viewUrl = `download_planning_doc.php?engagement_id=${engagementId}&mode=view`;
        const downloadUrl = `download_planning_doc.php?engagement_id=${engagementId}`;
        const body = document.getElementById('planningDocPreviewBody');
        document.getElementById('planningDocDownloadLink').href = downloadUrl;

        if (ext === 'pdf') {
            body.innerHTML = `<iframe src="${viewUrl}" title="Planning document preview" style="width:100%; height:70vh; border:none;"></iframe>`;
        } else if (PLANNING_DOC_PREVIEWABLE_EXTENSIONS.includes(ext)) {
            body.innerHTML = `<img src="${viewUrl}" alt="Planning document preview" style="max-width:100%; max-height:70vh; display:block; margin:0 auto;">`;
        } else {
            body.innerHTML = `<p class="text-muted" style="font-size:13px; text-align:center; padding:32px 16px; margin:0;">Preview isn't available for .${ext} files - use Download below to open it.</p>`;
        }

        planningDocPreviewModalInstance.show();
    }

    let addTeamMemberModalInstance = null;

    // Populates the employee picker (everyone active, minus whoever's
    // already on the team) and the audit type picker (only shown when this
    // engagement actually has audit types selected) using `lastData` - the
    // same payload the Team card itself was just rendered from, so "already
    // on the team" always matches what's on screen.
    function openAddTeamMemberModal(engagementId) {
        const modalEl = document.getElementById('addTeamMemberModal');
        if (!modalEl) return;
        if (!addTeamMemberModalInstance) addTeamMemberModalInstance = bootstrap.Modal.getOrCreateInstance(modalEl);

        const employeeSelect = document.getElementById('addTeamMemberEmployeeSelect');
        const auditTypeWrap = document.getElementById('addTeamMemberAuditTypeWrap');
        const auditTypeList = document.getElementById('addTeamMemberAuditTypeList');
        const saveBtn = document.getElementById('addTeamMemberSaveBtn');
        saveBtn.dataset.engagementId = engagementId;

        // Checkboxes, not a single-select - someone can be staffed under
        // more than one of the engagement's audit types at once (e.g. both
        // HIPAA and PCI), same as Master Schedule's own staffing card.
        const auditTypes = (lastData && lastData.audit_types) || [];
        if (auditTypes.length > 0) {
            auditTypeList.innerHTML = auditTypes.map(t => `
                <label class="eng-audit-type-chip">
                    <input type="checkbox" name="add_team_member_audit_type_ids[]" value="${t.audit_type_id}">
                    ${t.name}
                </label>`).join('');
            auditTypeWrap.classList.remove('d-none');
        } else {
            auditTypeList.innerHTML = '';
            auditTypeWrap.classList.add('d-none');
        }

        employeeSelect.innerHTML = '<option value="">Loading&hellip;</option>';
        employeeSelect.disabled = true;
        addTeamMemberModalInstance.show();

        const alreadyOnTeam = new Set((lastData && lastData.assigned_employees || []).map(e => e.user_id));
        if (lastData && lastData.manager_user) alreadyOnTeam.add(lastData.manager_user.user_id);

        fetch('get_active_employees.php')
            .then(res => res.json())
            .then(list => {
                const available = (Array.isArray(list) ? list : []).filter(e => !alreadyOnTeam.has(e.user_id));
                if (available.length === 0) {
                    employeeSelect.innerHTML = '<option value="">Everyone active is already on this team</option>';
                    return;
                }
                employeeSelect.innerHTML = '<option value="">Select employee&hellip;</option>' +
                    available.map(e => `<option value="${e.user_id}">${e.full_name} (${roleLabel(e.role)})</option>`).join('');
                employeeSelect.disabled = false;
            })
            .catch(err => {
                console.error('Failed to load employees', err);
                employeeSelect.innerHTML = '<option value="">Could not load employees</option>';
            });
    }

    // Parses/matches the file, then always routes into the modal above for
    // review before anything saves - matches ET's own "never a blind
    // import" behavior. Nothing is written to the database until Save
    // Changes is clicked in the modal.
    async function importTimelineFromFile(file, engagementId) {
        try {
            await loadXlsxLib();
            const rows = await parseSpreadsheetRows(file);
            const { matched, unmatched } = matchTimelineFieldsFromRows(rows);
            const unmatchedLabels = unmatched.map(f => TIMELINE_FIELD_LABELS[f] || f);
            openEditTimelineModal(engagementId, matched, unmatchedLabels);
        } catch (err) {
            console.error('Failed to import timeline', err);
            notify(err.message || 'Could not read that file.', true);
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

    // value is 'Y', 'N', or null (Not answered yet - clears any existing
    // attestation rather than storing a third enum state).
    async function saveIndependence(engagementId, value) {
        try {
            const res = await fetch('update_audit_independence.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ engagement_id: engagementId, independent: value || null })
            });
            const result = await res.json();
            if (!result.success) notify(result.error || 'Could not save.', true);
            refresh();
        } catch (err) {
            console.error('Failed to save independence', err);
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

        const indepTrigger = e.target.closest('.eng-vm-indep-icon.self');
        if (indepTrigger) {
            openIndependenceModal(indepTrigger.dataset.engagementId, indepTrigger.dataset.clientName, indepTrigger.dataset.value || null);
        }

        const viewDocTrigger = e.target.closest('.eng-vm-view-planning-doc');
        if (viewDocTrigger) {
            e.preventDefault();
            openPlanningDocPreview(viewDocTrigger.dataset.engagementId, viewDocTrigger.dataset.docUrl);
        }

        const addTeamMemberTrigger = e.target.closest('.eng-vm-add-team-member-btn');
        if (addTeamMemberTrigger) {
            e.preventDefault();
            openAddTeamMemberModal(addTeamMemberTrigger.dataset.engagementId);
        }
    });
    modalBody.addEventListener('keydown', (e) => {
        if (e.key !== 'Enter' && e.key !== ' ') return;
        const el = e.target.closest('.eng-vm-tl-dot.clickable, .eng-vm-tl-row2.clickable');
        if (el) { e.preventDefault(); toggleDot(el); }
    });

    // #editTimelineModal's own markup is static (not re-rendered per open()
    // like the cards above), so its submit listener is wired once here
    // rather than re-bound on every wireTimelineCardActions() call.
    const editTimelineForm = document.getElementById('editTimelineForm');
    if (editTimelineForm) {
        editTimelineForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            if (!editTimelineEngagementId) return;
            const saveBtn = document.getElementById('editTimelineSaveBtn');
            saveBtn.disabled = true;
            saveBtn.textContent = 'Saving...';
            try {
                await Promise.all(TIMELINE_MODAL_COLUMNS.map(column => {
                    const input = document.getElementById('tl_' + column);
                    return saveTimelineField(column, editTimelineEngagementId, input.value || null, true);
                }));
                editTimelineModalInstance.hide();
                refresh();
            } finally {
                saveBtn.disabled = false;
                saveBtn.textContent = 'Save Changes';
            }
        });
    }

    // #independenceModal's own markup is static, same reasoning as
    // #editTimelineModal above - wired once here, not per row render.
    const independenceSaveBtn = document.getElementById('independenceSaveBtn');
    if (independenceSaveBtn) {
        independenceSaveBtn.addEventListener('click', async () => {
            if (!independenceEngagementId) return;
            const selected = document.querySelector('#independenceOptions input[name="independentValue"]:checked');
            const value = selected ? selected.value : '';
            independenceSaveBtn.disabled = true;
            try {
                await saveIndependence(independenceEngagementId, value || null);
                independenceModalInstance.hide();
            } finally {
                independenceSaveBtn.disabled = false;
            }
        });
    }

    // #addTeamMemberModal's own markup is static too - wired once here,
    // same reasoning as #independenceModal above.
    const addTeamMemberSaveBtn = document.getElementById('addTeamMemberSaveBtn');
    if (addTeamMemberSaveBtn) {
        addTeamMemberSaveBtn.addEventListener('click', async () => {
            const engagementId = addTeamMemberSaveBtn.dataset.engagementId;
            const userId = document.getElementById('addTeamMemberEmployeeSelect').value;
            const auditTypeIds = Array.from(document.querySelectorAll('#addTeamMemberAuditTypeList input[name="add_team_member_audit_type_ids[]"]:checked')).map(cb => cb.value);
            if (!engagementId || !userId) {
                notify('Please select an employee.', true);
                return;
            }
            addTeamMemberSaveBtn.disabled = true;
            try {
                const res = await fetch('add_team_member.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ engagement_id: engagementId, user_id: userId, audit_type_ids: auditTypeIds })
                });
                const result = await res.json();
                if (!result.success) {
                    notify(result.error || 'Could not add team member.', true);
                    return;
                }
                if (addTeamMemberModalInstance) addTeamMemberModalInstance.hide();
                refresh();
            } catch (err) {
                console.error('Failed to add team member', err);
                notify('Network error. Please try again.', true);
            } finally {
                addTeamMemberSaveBtn.disabled = false;
            }
        });
    }

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
