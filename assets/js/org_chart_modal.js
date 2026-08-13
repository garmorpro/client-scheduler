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

    // True hierarchy tree (replaces the old flat Admin-tier/Manager-tier
    // card grid) - each node is a person, connected to their manager and
    // reports with actual lines via the classic nested-<ul> CSS technique
    // (see .oc-tree rules in styles.css), recursing to whatever depth the
    // real manager_id chain goes.
    function treeNodeCardHtml(node) {
        const p = node.user;
        return `
            <div class="oc-tree-node">
                ${avatar(p, 'oc-tree-avatar')}
                <div class="oc-tree-node-text">
                    <div class="oc-tree-node-name">${p.full_name}</div>
                    <div class="oc-tree-node-role">${p.job_title || roleLabel(p.role)}</div>
                </div>
            </div>
        `;
    }

    function treeNodeHtml(node) {
        const hasChildren = node.children && node.children.length > 0;
        return `
            <li>
                ${treeNodeCardHtml(node)}
                ${hasChildren ? treeListHtml(node.children) : ''}
            </li>
        `;
    }

    function treeListHtml(nodes) {
        return `<ul class="oc-tree">${nodes.map(treeNodeHtml).join('')}</ul>`;
    }

    function render(data) {
        const sections = [];

        if (data.tree.length > 0) {
            sections.push(`
                <div class="oc-tier">
                    <div class="oc-tier-label">Reporting Structure</div>
                    <div class="oc-tree-wrap">${treeListHtml(data.tree)}</div>
                </div>
            `);
        }

        if (data.unassigned.length > 0) {
            sections.push(`
                <div class="oc-tier">
                    <div class="oc-tier-label">Unassigned <span class="oc-tier-hint">no manager set, no one reports to them</span></div>
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
