// DOL Generator - ported from Engagement Tracker's pages/tool/dol-generator.php,
// re-pointed at Client Scheduler's real user_id-based data instead of free-text
// names. The split algorithm itself (computeSplit/buildBundles/weighting) is
// unchanged - it's already tested logic, only the data source and save target
// changed. One difference from the source: training restrictions are now
// edited on the Training page, not inline here - this page only reads them
// (already included in get-dol-setup.php's team response) to block/flag
// assignment, matching how the source used them for the split itself.

const DOL_AUDIT_TYPE_NAMES = ['SOC 1', 'SOC 2', 'HIPAA', 'HITRUST', 'FISMA'];
const ROLE_COLORS = { senior: 'rgb(230,144,65)', staff: 'rgb(66,127,194)', intern: 'rgb(76,175,80)' };
const ROLE_LABELS = { senior: 'Senior', staff: 'Staff', intern: 'Intern' };

let engagementData = null;   // { engagement, audit_types, team } from get-dol-setup.php
let eligibleMembers = [];    // team members (already senior/staff/intern only, with .restricted)
let selectedAuditType = null;   // { audit_type_id, name }
let lastResult = null;       // computed split, kept for Save
let lastUnassignable = [];   // bundles nobody was eligible for
let lastTotalHours = 0;
let criteriaWeights = {};    // name -> weight
let draggingChip = null;     // { fromIdx, criterion } while a chip drag is in progress

const SOC2_DEFAULT_WEIGHTS = {
    'CC1': 1, 'CC2': 1, 'CC3': 2, 'CC4': 1, 'CC5': 1,
    'CC6': 3, 'CC7': 2, 'CC8': 3, 'CC9': 1,
    'Availability': 2, 'Confidentiality': 1, 'Privacy': 3, 'Processing Integrity': 3
};
function getDefaultWeight(name) {
    return SOC2_DEFAULT_WEIGHTS[name] || 1;
}

const SOC2_CRITERIA_ORDER = ['CC1', 'CC2', 'CC3', 'CC4', 'CC5', 'CC6', 'CC7', 'CC8', 'CC9', 'Availability', 'Confidentiality', 'Processing Integrity', 'Privacy'];
function sortSoc2Criteria(names) {
    return [...names].sort((a, b) => {
        const ia = SOC2_CRITERIA_ORDER.indexOf(a);
        const ib = SOC2_CRITERIA_ORDER.indexOf(b);
        if (ia === -1 && ib === -1) return 0;
        if (ia === -1) return 1;
        if (ib === -1) return -1;
        return ia - ib;
    });
}

// Criteria that share evidence/context and should never split across two
// people - bundled into one unit (combined weight) before assignment runs.
const SOC2_CRITERIA_GROUPS = [
    ['CC3', 'CC9'],
    ['CC4', 'CC7', 'Availability'],
    ['CC6', 'Confidentiality']
];
function buildBundles(criteria, applyGroups) {
    if (!applyGroups) {
        return criteria.map(c => ({ names: [c.name], weight: c.weight }));
    }
    const byName = {};
    criteria.forEach(c => { byName[c.name] = c; });
    const used = new Set();
    const bundles = [];

    SOC2_CRITERIA_GROUPS.forEach(group => {
        const present = group.filter(name => byName[name] && !used.has(name));
        if (present.length > 1) {
            const weight = present.reduce((sum, name) => sum + byName[name].weight, 0);
            bundles.push({ names: present, weight });
            present.forEach(name => used.add(name));
        }
    });

    criteria.forEach(c => {
        if (!used.has(c.name)) {
            bundles.push({ names: [c.name], weight: c.weight });
            used.add(c.name);
        }
    });

    return bundles;
}

function initials(name) {
    return (name || '').split(' ').filter(Boolean).map(p => p[0].toUpperCase()).join('');
}
function escapeHtml(str) {
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}
function escAttr(str) {
    return String(str).replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
}

function deriveSoc2Criteria(tsc) {
    const t = (tsc || '').toLowerCase();
    const criteria = [];
    if (t.includes('security')) criteria.push('CC1', 'CC2', 'CC3', 'CC4', 'CC5', 'CC6', 'CC7', 'CC8', 'CC9');
    if (t.includes('availability')) criteria.push('Availability');
    if (t.includes('confidentiality')) criteria.push('Confidentiality');
    if (t.includes('processing integrity')) criteria.push('Processing Integrity');
    if (t.includes('privacy')) criteria.push('Privacy');
    return criteria;
}

function parseCriteriaInput(text) {
    return text.split(/[,\n]/).map(c => c.trim()).filter(Boolean);
}

function syncCriteriaWeights() {
    const names = parseCriteriaInput(document.getElementById('criteriaInput').value);
    const next = {};
    names.forEach(name => {
        next[name] = criteriaWeights.hasOwnProperty(name) ? criteriaWeights[name] : getDefaultWeight(name);
    });
    criteriaWeights = next;
    renderWeightEditor(names);
}

function renderWeightEditor(names) {
    const container = document.getElementById('weightEditorList');
    if (!names.length) {
        container.innerHTML = '<div class="text-muted small py-2">Enter criteria above to set weights.</div>';
        return;
    }
    container.innerHTML = names.map(name => `
        <div class="dolgen-weight-row" data-name="${escAttr(name)}">
            <span class="dolgen-weight-name">${escapeHtml(name)}</span>
            <div class="d-flex align-items-center gap-2">
                <input type="number" class="form-control form-control-sm criterion-weight-input" style="width:64px;" min="1" step="1" value="${criteriaWeights[name]}">
                <span class="text-muted small">weight</span>
            </div>
        </div>
    `).join('');
    container.querySelectorAll('.dolgen-weight-row').forEach(row => {
        const name = row.dataset.name;
        row.querySelector('.criterion-weight-input').addEventListener('input', (e) => {
            criteriaWeights[name] = parseFloat(e.target.value) || 1;
        });
    });
}

function buildWeightedCriteria() {
    return parseCriteriaInput(document.getElementById('criteriaInput').value)
        .map(name => ({ name, weight: criteriaWeights[name] || 1 }));
}

// Splits by weight, not raw item count, so a person's share of the work
// stays proportional to their hours. Bundles are assigned one at a time,
// heaviest first, to whoever's furthest under their target weight (among
// those not restricted from every item in the bundle). Returns
// { members: state, unassignable } - unassignable lists any bundle where
// everyone was restricted from at least one item, so it had to assign
// anyway rather than silently giving someone untrained work.
function computeSplit(members, criteria, groupingEnabled) {
    const totalHours = members.reduce((sum, m) => sum + m.hours, 0);
    const totalWeight = criteria.reduce((sum, c) => sum + c.weight, 0);

    const state = members.map(m => ({
        ...m,
        targetWeight: totalHours > 0 ? (m.hours / totalHours) * totalWeight : 0,
        assignedWeight: 0,
        assigned: []
    }));

    const bundles = buildBundles(criteria, groupingEnabled);
    const unassignable = [];
    const byWeightDesc = [...bundles].sort((a, b) => b.weight - a.weight);
    byWeightDesc.forEach(bundle => {
        const eligible = state.filter(s => !bundle.names.some(name => (s.restricted || []).includes(name)));
        if (!eligible.length) unassignable.push(bundle);
        const pool = eligible.length ? eligible : state;

        let best = pool[0];
        let bestSlack = -Infinity;
        pool.forEach(s => {
            const slack = s.targetWeight - s.assignedWeight;
            if (slack > bestSlack) { bestSlack = slack; best = s; }
        });
        best.assigned.push(...bundle.names);
        best.assignedWeight += bundle.weight;
    });

    return { members: state, unassignable };
}

// ---------- Engagement selection ----------
document.getElementById('engagementSelect').addEventListener('change', async (ev) => {
    const engId = ev.target.value;
    resetResult();
    if (!engId) {
        document.getElementById('setupSections').classList.add('d-none');
        return;
    }
    try {
        const res = await fetch('get-dol-setup.php?engagement_id=' + encodeURIComponent(engId));
        const data = await res.json();
        if (!data.success) {
            Swal.fire('Error', data.error || 'Failed to load engagement', 'error');
            return;
        }
        engagementData = data;
        eligibleMembers = data.team || [];
        document.getElementById('setupSections').classList.remove('d-none');
        renderTeamHours();
        renderAuditTypePills();
    } catch (err) {
        console.error('Error:', err);
        Swal.fire('Error', 'Failed to load engagement', 'error');
    }
});

function renderAuditTypePills() {
    const container = document.getElementById('auditTypePills');
    const relevant = (engagementData.audit_types || []).filter(t => DOL_AUDIT_TYPE_NAMES.includes(t.name));

    if (!relevant.length) {
        container.innerHTML = '<div class="text-muted small py-2">This engagement has no audit types with DOL support assigned.</div>';
        selectedAuditType = null;
        return;
    }

    selectedAuditType = relevant[0];
    container.innerHTML = relevant.map(t => `<button type="button" class="dolgen-pill ${t.audit_type_id === selectedAuditType.audit_type_id ? 'active' : ''}" data-id="${t.audit_type_id}">${escapeHtml(t.name)}</button>`).join('');
    container.querySelectorAll('.dolgen-pill').forEach(btn => {
        btn.addEventListener('click', () => {
            container.querySelectorAll('.dolgen-pill').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            selectedAuditType = relevant.find(t => String(t.audit_type_id) === btn.dataset.id);
            loadSelectedAuditType();
        });
    });
    loadSelectedAuditType();
}

// Whatever this engagement already has saved for the given audit type, or
// null if there's nothing to show yet (a brand-new split still needs Step
// 4 + Generate).
function existingDolFor(auditTypeId) {
    const byUser = (engagementData.existing_dol && engagementData.existing_dol[auditTypeId]) || null;
    return byUser && Object.keys(byUser).length ? byUser : null;
}

// Picking an audit type (on load or via pill click) always preps Step 4 in
// case someone wants to regenerate from scratch via "Back & Adjust" -> but
// if this engagement already has DOL saved for that type, skip straight to
// the review/edit view instead of forcing a re-split of something that's
// already there.
function loadSelectedAuditType() {
    updateCriteriaForAuditType();
    const existing = existingDolFor(selectedAuditType.audit_type_id);
    if (existing) {
        showExistingDol(existing);
    } else {
        resetResult();
        document.getElementById('setupSections').classList.remove('d-none');
    }
}

// Populates lastResult from what's already saved (rather than a freshly
// computed split) and reuses renderResult()'s same review/edit UI - drag a
// chip or use Swap, then Save replaces this audit type's DOL like any other
// save. Weights are display-only here (SOC 2 defaults, else 1) - nothing
// about editing existing DOL depends on them the way generating a fresh
// split does.
function showExistingDol(existingByUser) {
    criteriaWeights = {};
    Object.values(existingByUser).flat().forEach(name => {
        if (!(name in criteriaWeights)) criteriaWeights[name] = getDefaultWeight(name);
    });

    lastTotalHours = eligibleMembers.reduce((sum, m) => sum + (m.hours || 0), 0);
    lastUnassignable = [];
    lastResult = eligibleMembers.map(m => {
        let assigned = existingByUser[m.user_id] || [];
        if (selectedAuditType.name === 'SOC 2') assigned = sortSoc2Criteria(assigned);
        const assignedWeight = assigned.reduce((sum, c) => sum + (criteriaWeights[c] || 1), 0);
        return { ...m, assigned, assignedWeight };
    });

    const allCriteria = Object.keys(criteriaWeights);
    const totalWeight = allCriteria.reduce((sum, c) => sum + (criteriaWeights[c] || 1), 0);
    renderResult(lastTotalHours, allCriteria.length, totalWeight);

    // renderResult()'s summary line assumes a just-generated split ("split
    // by hours... = N total") - this is existing data being reviewed, not
    // a fresh computation, so it gets its own wording.
    document.getElementById('resultSummary').innerHTML =
        `${escapeHtml(engagementData.engagement.client_name)} &middot; ${escapeHtml(selectedAuditType.name)} &middot; showing the current DOL (${allCriteria.length} criteria across ${lastResult.length} people). Drag a chip onto someone else to move it, or Swap, then Save.`;
}

function updateCriteriaForAuditType() {
    const textarea = document.getElementById('criteriaInput');
    const hint = document.getElementById('criteriaHint');
    criteriaWeights = {};
    if (selectedAuditType && selectedAuditType.name === 'SOC 2') {
        const derived = deriveSoc2Criteria(engagementData.engagement.tsc);
        textarea.value = derived.join(', ');
        hint.textContent = derived.length
            ? `Derived from this engagement's TSC ("${engagementData.engagement.tsc || ''}"). Edit if needed.`
            : `This engagement's TSC field doesn't mention any known SOC 2 category — enter criteria manually.`;
    } else {
        textarea.value = '';
        hint.textContent = 'Paste or type the criteria to split for this audit type.';
    }
    syncCriteriaWeights();
}
document.getElementById('criteriaInput').addEventListener('input', () => syncCriteriaWeights());

function restrictedNoteHtml(restricted) {
    return (restricted && restricted.length)
        ? `<div class="dolgen-restricted-note"><i class="bi bi-exclamation-triangle-fill"></i> Not trained: ${escapeHtml(restricted.join(', '))}</div>`
        : '';
}

function renderTeamHours() {
    const container = document.getElementById('teamHoursList');
    if (!eligibleMembers.length) {
        container.innerHTML = '<div class="text-muted small py-2">No Senior, Staff, or Intern staffed on this engagement yet (via Master Schedule).</div>';
        return;
    }
    container.innerHTML = eligibleMembers.map(m => {
        const roleKey = (m.role || '').toLowerCase();
        return `
            <div class="dolgen-member-row" data-user-id="${m.user_id}">
                <div class="dolgen-avatar" style="background:${ROLE_COLORS[roleKey] || '#6c757d'}">${initials(m.full_name)}</div>
                <div class="flex-grow-1">
                    <div class="fw-bold small">${escapeHtml(m.full_name)}</div>
                    <div class="text-muted small text-uppercase" style="font-size:10.5px;">${ROLE_LABELS[roleKey] || m.role}</div>
                    ${restrictedNoteHtml(m.restricted)}
                </div>
                <div class="d-flex align-items-center gap-2">
                    <input type="number" class="form-control form-control-sm member-hours-input" style="width:72px;" min="0" step="0.5" value="${m.hours || 0}">
                    <span class="text-muted small">hrs</span>
                </div>
            </div>
        `;
    }).join('');
}

// ---------- Generate ----------
document.getElementById('generateBtn').addEventListener('click', () => {
    const errorBox = document.getElementById('genErrorBox');
    errorBox.classList.add('d-none');

    if (!eligibleMembers.length) {
        errorBox.textContent = 'This engagement has no eligible team members.';
        errorBox.classList.remove('d-none');
        return;
    }
    if (!selectedAuditType) {
        errorBox.textContent = 'This engagement has no audit type with DOL support assigned.';
        errorBox.classList.remove('d-none');
        return;
    }

    const memberRows = document.querySelectorAll('#teamHoursList .dolgen-member-row');
    const members = Array.from(memberRows).map(row => {
        const userId = row.dataset.userId;
        const member = eligibleMembers.find(m => String(m.user_id) === String(userId));
        const hours = parseFloat(row.querySelector('.member-hours-input').value) || 0;
        return { ...member, hours };
    });

    const totalHours = members.reduce((sum, m) => sum + m.hours, 0);
    if (totalHours <= 0) {
        errorBox.textContent = 'Enter hours for at least one team member.';
        errorBox.classList.remove('d-none');
        return;
    }

    const criteria = buildWeightedCriteria();
    if (!criteria.length) {
        errorBox.textContent = 'Enter at least one criterion to split.';
        errorBox.classList.remove('d-none');
        return;
    }

    const splitResult = computeSplit(members, criteria, selectedAuditType.name === 'SOC 2');
    lastResult = splitResult.members;
    lastUnassignable = splitResult.unassignable;
    if (selectedAuditType.name === 'SOC 2') {
        lastResult.forEach(m => { m.assigned = sortSoc2Criteria(m.assigned); });
    }
    lastTotalHours = totalHours;
    const totalWeight = criteria.reduce((sum, c) => sum + c.weight, 0);
    renderResult(totalHours, criteria.length, totalWeight);
});

function renderResult(totalHours, criteriaCount, totalWeight) {
    document.getElementById('setupSections').classList.add('d-none');
    document.getElementById('resultSection').classList.remove('d-none');

    const clientName = escapeHtml(engagementData.engagement.client_name);
    const hoursBreakdown = lastResult.map(m => m.hours).join(' / ');
    document.getElementById('resultSummary').innerHTML =
        `${clientName} &middot; ${escapeHtml(selectedAuditType.name)} &middot; ${criteriaCount} criteria (${totalWeight} total weight) across ${lastResult.length} people, split by hours (${hoursBreakdown} = ${totalHours} total)`;

    const unassignableBox = document.getElementById('unassignableWarning');
    if (lastUnassignable.length) {
        const names = lastUnassignable.flatMap(b => b.names).join(', ');
        unassignableBox.innerHTML = `<i class="bi bi-exclamation-octagon-fill"></i> No one on this team is trained on <strong>${escapeHtml(names)}</strong> — assigned anyway (marked below), but someone needs to finish training (see the Training page) or you should reassign it by hand.`;
        unassignableBox.classList.remove('d-none');
    } else {
        unassignableBox.classList.add('d-none');
    }

    renderResultMembers();

    document.getElementById('saveWarningText').innerHTML =
        `Saving will replace this engagement's <strong>${escapeHtml(selectedAuditType.name)}</strong> DOL for these ${lastResult.length} people. Their DOL for any other audit type on this engagement is untouched.`;
}

// Rebuilds just the per-person cards - called after generating, and again
// after any manual move/swap, without re-deriving the summary line.
function renderResultMembers() {
    const totalHours = lastTotalHours;
    document.getElementById('resultMembers').innerHTML = lastResult.map((m, idx) => {
        const roleKey = (m.role || '').toLowerCase();
        const pct = totalHours > 0 ? Math.round((m.hours / totalHours) * 100) : 0;
        const restricted = m.restricted || [];
        const chips = m.assigned.length
            ? m.assigned.map(c => {
                const isRestricted = restricted.includes(c);
                return `<span class="dolgen-chip ${isRestricted ? 'restricted' : ''}" draggable="true" data-member-idx="${idx}" data-criterion="${escAttr(c)}" title="${isRestricted ? escAttr(m.full_name) + " isn't trained on this yet — " : ''}Drag onto someone else, or click to move">${isRestricted ? '<i class="bi bi-exclamation-triangle-fill"></i> ' : ''}${escapeHtml(c)}</span>`;
            }).join('')
            : '<span class="text-muted small">No criteria assigned</span>';
        return `
            <div class="dolgen-result-member" data-member-idx="${idx}">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <div class="dolgen-avatar" style="background:${ROLE_COLORS[roleKey] || '#6c757d'}">${initials(m.full_name)}</div>
                    <div class="flex-grow-1">
                        <div class="fw-bold small">${escapeHtml(m.full_name)}</div>
                        <div class="text-muted small text-uppercase" style="font-size:10.5px;">${ROLE_LABELS[roleKey] || m.role}</div>
                    </div>
                    <span class="dolgen-share">${m.hours} hrs &middot; ${pct}% &middot; ${m.assigned.length} criteria (${m.assignedWeight} wt)</span>
                    <button type="button" class="btn btn-sm btn-outline-secondary dolgen-swap-btn" data-member-idx="${idx}" title="Swap all criteria with someone else"><i class="bi bi-arrow-left-right"></i> Swap</button>
                </div>
                <div class="d-flex flex-wrap gap-1 dolgen-chip-zone">${chips}</div>
            </div>
        `;
    }).join('');

    document.querySelectorAll('.dolgen-chip[data-criterion]').forEach(chip => {
        chip.addEventListener('click', () => openMoveMenu(parseInt(chip.dataset.memberIdx), chip.dataset.criterion));
        chip.addEventListener('dragstart', (e) => {
            draggingChip = { fromIdx: parseInt(chip.dataset.memberIdx), criterion: chip.dataset.criterion };
            chip.classList.add('dragging');
            e.dataTransfer.effectAllowed = 'move';
            // Firefox won't fire dragstart at all without setData called.
            e.dataTransfer.setData('text/plain', chip.dataset.criterion);
        });
        chip.addEventListener('dragend', () => {
            chip.classList.remove('dragging');
            draggingChip = null;
        });
    });
    document.querySelectorAll('.dolgen-swap-btn').forEach(btn => {
        btn.addEventListener('click', () => openSwapMenu(parseInt(btn.dataset.memberIdx)));
    });

    // Drop target is the whole card, not just the chip row, so there's a
    // generous target to aim for.
    document.querySelectorAll('.dolgen-result-member').forEach(card => {
        const toIdx = parseInt(card.dataset.memberIdx);
        card.addEventListener('dragover', (e) => {
            if (!draggingChip) return;
            e.preventDefault();
            e.dataTransfer.dropEffect = 'move';
        });
        card.addEventListener('dragenter', (e) => {
            if (!draggingChip) return;
            e.preventDefault();
            card.classList.add('dolgen-drop-target');
        });
        card.addEventListener('dragleave', (e) => {
            if (!card.contains(e.relatedTarget)) card.classList.remove('dolgen-drop-target');
        });
        card.addEventListener('drop', (e) => {
            e.preventDefault();
            card.classList.remove('dolgen-drop-target');
            if (!draggingChip || draggingChip.fromIdx === toIdx) return;
            moveCriterion(draggingChip.fromIdx, toIdx, draggingChip.criterion);
            draggingChip = null;
        });
    });
}

function moveCriterion(fromIdx, toIdx, criterion) {
    const from = lastResult[fromIdx];
    const to = lastResult[toIdx];
    const weight = criteriaWeights[criterion] ?? 1;
    from.assigned = from.assigned.filter(c => c !== criterion);
    from.assignedWeight -= weight;
    to.assigned.push(criterion);
    to.assignedWeight += weight;
    if (selectedAuditType.name === 'SOC 2') {
        from.assigned = sortSoc2Criteria(from.assigned);
        to.assigned = sortSoc2Criteria(to.assigned);
    }
    renderResultMembers();
}

function swapAllCriteria(idxA, idxB) {
    const a = lastResult[idxA];
    const b = lastResult[idxB];
    [a.assigned, b.assigned] = [b.assigned, a.assigned];
    [a.assignedWeight, b.assignedWeight] = [b.assignedWeight, a.assignedWeight];
    renderResultMembers();
}

async function openMoveMenu(fromIdx, criterion) {
    const others = lastResult.map((m, i) => ({ m, i })).filter(x => x.i !== fromIdx);
    if (!others.length) return;
    const { value: toIdx } = await Swal.fire({
        title: `Move ${criterion} to…`,
        input: 'select',
        inputOptions: Object.fromEntries(others.map(x => [x.i, x.m.full_name])),
        showCancelButton: true,
        confirmButtonText: 'Move'
    });
    if (toIdx === undefined || toIdx === '') return;
    moveCriterion(fromIdx, parseInt(toIdx), criterion);
}

async function openSwapMenu(idxA) {
    const others = lastResult.map((m, i) => ({ m, i })).filter(x => x.i !== idxA);
    if (!others.length) return;
    const { value: idxB } = await Swal.fire({
        title: `Swap ${lastResult[idxA].full_name}'s criteria with…`,
        input: 'select',
        inputOptions: Object.fromEntries(others.map(x => [x.i, x.m.full_name])),
        showCancelButton: true,
        confirmButtonText: 'Swap'
    });
    if (idxB === undefined || idxB === '') return;
    swapAllCriteria(idxA, parseInt(idxB));
}

function resetResult() {
    lastResult = null;
    lastUnassignable = [];
    lastTotalHours = 0;
    document.getElementById('resultSection').classList.add('d-none');
    document.getElementById('genErrorBox').classList.add('d-none');
}

document.getElementById('backToEditBtn').addEventListener('click', () => {
    document.getElementById('resultSection').classList.add('d-none');
    document.getElementById('setupSections').classList.remove('d-none');
});

// ---------- Save ----------
document.getElementById('saveBtn').addEventListener('click', async () => {
    if (!lastResult || !selectedAuditType) return;
    const saveBtn = document.getElementById('saveBtn');
    saveBtn.disabled = true;
    saveBtn.textContent = 'Saving…';

    try {
        const res = await fetch('save-dol-assignments.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                engagement_id: engagementData.engagement.engagement_id,
                audit_type_id: selectedAuditType.audit_type_id,
                assignments: lastResult.map(m => ({ user_id: m.user_id, criteria: m.assigned }))
            })
        });
        const data = await res.json();

        saveBtn.disabled = false;
        saveBtn.innerHTML = '<i class="bi bi-check-lg"></i> Save to Engagement';

        if (!data.success) {
            Swal.fire('Error', data.error || 'Failed to save DOL', 'error');
            return;
        }

        // Same idiom as everywhere else in the app that navigates away and
        // needs to land back on a specific detail view - flag it in
        // sessionStorage, then engagement-management.php's own load
        // handler clicks that engagement's View button for us.
        sessionStorage.setItem('reopenEngagementId', engagementData.engagement.engagement_id);
        window.location.href = 'engagement-management.php';
        return;
    } catch (err) {
        console.error('Error:', err);
        saveBtn.disabled = false;
        saveBtn.innerHTML = '<i class="bi bi-check-lg"></i> Save to Engagement';
        Swal.fire('Error', 'Failed to save DOL', 'error');
    }
});

// If the page loaded with an option pre-selected server-side (the
// ?engagement_id= deep link from the View Engagement panel's "Manage Team"
// link), fire the same load path a manual pick would trigger. This script
// tag sits at the bottom of the page, so the DOM (and the select's
// server-rendered `selected` option) is already in place by the time this
// runs - no need to wait on DOMContentLoaded.
(() => {
    const select = document.getElementById('engagementSelect');
    if (select && select.value) select.dispatchEvent(new Event('change'));
})();
