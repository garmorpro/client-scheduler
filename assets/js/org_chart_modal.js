document.addEventListener('DOMContentLoaded', () => {
    const modalEl = document.getElementById('orgChartModal');
    if (!modalEl) return;
    const modal = new bootstrap.Modal(modalEl);
    const contentEl = document.getElementById('ocContent');
    const openBtn = document.getElementById('openOrgChartBtn');

    // Same senior/staff/intern colors used elsewhere (Master Schedule, Who's
    // Available), plus admin/manager colors to round out every role.
    const ROLE_COLORS = {
        admin: 'rgb(23,62,70)',
        manager: 'rgb(155,107,214)',
        senior: 'rgb(230,144,65)',
        staff: 'rgb(66,127,194)',
        intern: 'rgb(76,175,80)',
        crm_team: 'rgb(214,122,168)',
    };
    function roleColor(role) {
        return ROLE_COLORS[(role || '').toLowerCase()] || '#6c757d';
    }
    function initials(name) {
        return (name || '?').trim().split(/\s+/).slice(0, 2).map(w => w[0].toUpperCase()).join('');
    }
    function roleLabel(role) {
        const r = (role || '').toLowerCase();
        if (r === 'crm_team') return 'CRM Team';
        return r ? r.charAt(0).toUpperCase() + r.slice(1) : '';
    }
    function avatar(person, sizeClass) {
        return `<div class="oc-avatar ${sizeClass || ''}" style="background:${roleColor(person.role)};">${initials(person.full_name)}</div>`;
    }

    function chipHtml(person) {
        return `
            <div class="oc-chip">
                ${avatar(person)}
                <div>
                    <div class="oc-chip-name">${person.full_name}</div>
                    <div class="oc-chip-role">${person.job_title || roleLabel(person.role)}</div>
                </div>
            </div>
        `;
    }

    function reportRowHtml(person) {
        return `
            <div class="eng-vm-emp-row">
                ${avatar(person, 'oc-avatar-sm')}
                <div class="eng-vm-emp-info">
                    <div class="eng-vm-emp-name">${person.full_name}</div>
                    <div class="eng-vm-emp-role">${roleLabel(person.role)}</div>
                </div>
            </div>
        `;
    }

    function managerCardHtml(node) {
        const reportsHtml = node.reports.length > 0
            ? node.reports.map(reportRowHtml).join('')
            : '<div class="settings-empty-row">No direct reports yet.</div>';
        return `
            <div class="oc-manager-card">
                <div class="oc-manager-head">
                    ${avatar(node.manager)}
                    <div>
                        <div class="oc-manager-name">${node.manager.full_name}</div>
                        <div class="oc-manager-title">${node.manager.job_title || 'Manager'}</div>
                    </div>
                    <span class="oc-report-count">${node.reports.length}</span>
                </div>
                <div class="oc-reports">${reportsHtml}</div>
            </div>
        `;
    }

    function render(data) {
        const sections = [];

        if (data.admins.length > 0) {
            sections.push(`
                <div class="oc-tier">
                    <div class="oc-tier-label">Admin</div>
                    <div class="oc-chip-row">${data.admins.map(chipHtml).join('')}</div>
                </div>
            `);
        }

        if (data.manager_nodes.length > 0) {
            sections.push(`
                <div class="oc-tier">
                    <div class="oc-tier-label">Managers &amp; Their Teams</div>
                    <div class="oc-manager-grid">${data.manager_nodes.map(managerCardHtml).join('')}</div>
                </div>
            `);
        }

        if (data.unassigned.length > 0) {
            sections.push(`
                <div class="oc-tier">
                    <div class="oc-tier-label">Unassigned <span class="oc-tier-hint">no manager set - includes CRM Team and Interns</span></div>
                    <div class="oc-chip-row">${data.unassigned.map(chipHtml).join('')}</div>
                </div>
            `);
        }

        contentEl.innerHTML = sections.join('') || '<div class="settings-empty-row">No active employees found.</div>';
    }

    async function loadOrgChart() {
        contentEl.innerHTML = '<div class="settings-empty-row">Loading...</div>';
        try {
            const res = await fetch('get_org_chart.php');
            const data = await res.json();
            if (!data.success) {
                contentEl.innerHTML = `<div class="settings-empty-row text-danger">${data.error || 'Could not load the org chart.'}</div>`;
                return;
            }
            render(data);
        } catch (err) {
            console.error('Failed to load org chart', err);
            contentEl.innerHTML = '<div class="settings-empty-row text-danger">Network error loading the org chart.</div>';
        }
    }

    if (openBtn) {
        openBtn.addEventListener('click', () => {
            modal.show();
            loadOrgChart();
        });
    }
});
