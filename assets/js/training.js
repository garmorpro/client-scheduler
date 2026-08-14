document.addEventListener('DOMContentLoaded', () => {
    const list = document.getElementById('trainingList');
    const modalEl = document.getElementById('trainingStatusModal');
    if (!list || !modalEl) return;

    const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
    const chipsContainer = document.getElementById('trainingStatusChips');
    const addInput = document.getElementById('trainingStatusAddInput');
    const quickAddContainer = document.getElementById('trainingStatusQuickAdd');
    const saveBtn = document.getElementById('trainingStatusSaveBtn');

    // Same canonical list new Staff/Intern get seeded with on hire (see
    // add_user.php/import_users.php) - one-click buttons for whichever of
    // these aren't already chips, so clearing a standard criterion doesn't
    // require typing its exact name.
    const CANONICAL_CRITERIA = ['CC1', 'CC2', 'CC3', 'CC4', 'CC5', 'CC6', 'CC7', 'CC8', 'CC9', 'Availability', 'Confidentiality', 'Privacy', 'Processing Integrity'];

    let activeRow = null;
    let currentCriteria = [];

    function escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    // Recomputes the three stat cards from what's actually on screen -
    // cheaper than a round trip, and always exactly matches the rows below
    // since it's reading the same data-restricted attributes the rows
    // themselves were just updated from.
    function refreshStats() {
        const rows = list.querySelectorAll('.tr-row');
        let restrictedCount = 0;
        rows.forEach(row => {
            if ((row.dataset.restricted || '').trim() !== '') restrictedCount++;
        });
        const totalEl = document.getElementById('trStatTotal');
        const trainedEl = document.getElementById('trStatTrained');
        const restrictedEl = document.getElementById('trStatRestricted');
        if (totalEl) totalEl.textContent = rows.length;
        if (trainedEl) trainedEl.textContent = rows.length - restrictedCount;
        if (restrictedEl) restrictedEl.textContent = restrictedCount;
    }

    function renderChips() {
        if (!currentCriteria.length) {
            chipsContainer.innerHTML = '<span class="tr-editor-chips-empty">Nothing yet - fully trained</span>';
        } else {
            chipsContainer.innerHTML = currentCriteria.map((c, idx) => `
                <span class="tr-editor-chip" data-idx="${idx}">
                    ${escapeHtml(c)}
                    <span class="tr-editor-chip-remove" role="button" tabindex="0" aria-label="Remove ${escapeHtml(c)}"></span>
                </span>`).join('');
        }
        renderQuickAdd();
    }

    // Only offers whichever canonical criteria aren't already chips - once
    // added (by button or by typing), it drops out of this row so there's
    // nothing to click twice.
    function renderQuickAdd() {
        const remaining = CANONICAL_CRITERIA.filter(name => !currentCriteria.some(c => c.toLowerCase() === name.toLowerCase()));
        const wrap = document.getElementById('trainingStatusQuickAddWrap');
        if (!remaining.length) {
            wrap.classList.add('d-none');
            return;
        }
        wrap.classList.remove('d-none');
        quickAddContainer.innerHTML = remaining.map(name => `<button type="button" data-name="${escapeHtml(name)}">+ ${escapeHtml(name)}</button>`).join('');
    }

    quickAddContainer.addEventListener('click', (e) => {
        const btn = e.target.closest('button[data-name]');
        if (!btn) return;
        addCriterion(btn.dataset.name);
    });

    function addCriterion(raw) {
        const value = raw.trim();
        if (!value) return;
        if (!currentCriteria.some(c => c.toLowerCase() === value.toLowerCase())) {
            currentCriteria.push(value);
            renderChips();
        }
        addInput.value = '';
    }

    chipsContainer.addEventListener('click', (e) => {
        const removeBtn = e.target.closest('.tr-editor-chip-remove');
        if (!removeBtn) return;
        const chip = removeBtn.closest('.tr-editor-chip');
        const idx = parseInt(chip.dataset.idx, 10);
        currentCriteria.splice(idx, 1);
        renderChips();
    });

    // Enter or comma commits whatever's typed as a new chip - comma support
    // matters for anyone pasting a list back in (e.g. "CC6, CC9, Privacy").
    addInput.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            addCriterion(addInput.value);
        } else if (e.key === ',') {
            e.preventDefault();
            addCriterion(addInput.value);
        }
    });
    addInput.addEventListener('blur', () => addCriterion(addInput.value));

    function openEditor(row) {
        activeRow = row;
        currentCriteria = (row.dataset.restricted || '').split(',').map(s => s.trim()).filter(Boolean);
        document.getElementById('trainingStatusModalSubtitle').textContent =
            `Criteria ${row.dataset.userName} hasn't completed training on yet — the DOL Generator won't assign these to them until cleared here.`;
        renderChips();
        addInput.value = '';
        modal.show();
    }

    saveBtn.addEventListener('click', async () => {
        if (!activeRow) return;
        // Whatever's still sitting in the add field counts too - no reason
        // to make someone hit Enter before Save "notices" it.
        addCriterion(addInput.value);

        const userId = activeRow.dataset.userId;
        saveBtn.disabled = true;
        try {
            const res = await fetch('update-training-status.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ user_id: userId, restricted: currentCriteria })
            });
            const data = await res.json();
            if (!data.success) {
                appNotify({ icon: 'error', title: 'Error', text: data.error || 'Failed to save training status' });
                return;
            }

            activeRow.dataset.restricted = currentCriteria.join(',');
            const statusEl = activeRow.querySelector('.tr-row-status');
            statusEl.innerHTML = currentCriteria.length
                ? `<span class="eng-status-pill denied"><span class="dot"></span>${currentCriteria.length} restricted</span><span class="tr-row-restricted-list">${escapeHtml(currentCriteria.join(', '))}</span>`
                : '<span class="eng-status-pill confirmed"><span class="dot"></span>Fully trained</span>';
            refreshStats();

            modal.hide();
            if (typeof appNotify !== 'undefined') {
                appNotify({ icon: 'success', title: 'Saved', timer: 1200 });
            }
        } catch (err) {
            console.error('Failed to save training status', err);
            appNotify({ icon: 'error', title: 'Error', text: 'Failed to save training status' });
        } finally {
            saveBtn.disabled = false;
        }
    });

    list.addEventListener('click', (e) => {
        const btn = e.target.closest('.tr-edit-btn');
        if (!btn) return;
        openEditor(btn.closest('.tr-row[data-user-id]'));
    });
});
