document.addEventListener('DOMContentLoaded', () => {
    const modalEl = document.getElementById('orgChartModal');
    if (!modalEl) return;
    const modal = new bootstrap.Modal(modalEl);
    const contentEl = document.getElementById('ocContent');
    const openBtn = document.getElementById('openOrgChartBtn');

    // Org Chart's own role palette - every person in the same role reads as
    // the same color at a glance (previously each manager card ended up
    // looking near-identical purple, since role colors weren't distinct
    // enough from each other). Admin and CRM Team share the AARC-360 brand
    // teal since both are "not on the reporting ladder" groups. This is
    // intentionally its own palette, separate from the senior/staff/intern
    // colors used on Master Schedule/Who's Available - those are unchanged.
    const ROLE_COLORS = {
        admin: '#003f47',
        manager: '#1e2f4d',
        senior: '#8457b0',
        staff: '#3f7d52',
        intern: '#9c6b2e',
        crm_team: '#003f47',
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
