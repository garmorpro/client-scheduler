(async function() {
    if (!IS_ADMIN) return;
    let activeInput = null;

    function renderTimeOff(td, timeOffValue, entryId) {
        td.style.position = 'relative';
        td.classList.add('timeoff-cell');
        let badge = td.querySelector('.timeoff-corner');
        if (!badge) {
            badge = document.createElement('div');
            badge.className = 'timeoff-corner';
            td.appendChild(badge);
        }
        badge.textContent = timeOffValue;
        badge.dataset.entryId = entryId;

        if (!td.querySelector('.bi-plus')) {
            const plusIcon = document.createElement('i');
            plusIcon.className = 'bi bi-plus';
            td.appendChild(plusIcon);
        }
    }

    async function safeFetchJSON(url, options) {
        try {
            const resp = await fetch(url, options);
            const text = await resp.text();
            return { ok: resp.ok, data: JSON.parse(text) };
        } catch (err) {
            console.error('Fetch or parse error:', err);
            return { ok: false, data: null };
        }
    }

    async function getTimeOffEntry(td) {
        const resp = await safeFetchJSON('get_timeoff_entry.php', {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ user_id: td.dataset.userId, week_start: td.dataset.weekStart })
        });
        if (resp.ok && resp.data?.success && resp.data.entry_id) {
            return {
                entryId: resp.data.entry_id,
                personalHours: resp.data.assigned_hours !== null ? parseFloat(resp.data.assigned_hours) : 0
            };
        }
        return { entryId: null, personalHours: 0 };
    }

    async function getGlobalTimeOffHours(week_start) {
        const resp = await safeFetchJSON('check_global_timeoff.php', {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ week_start })
        });
        if (resp.ok && resp.data?.success && resp.data.assigned_hours !== null) {
            return parseFloat(resp.data.assigned_hours);
        }
        return 0;
    }

    async function handleTimeOffInput(td) {
        if (activeInput) activeInput.remove();

        const { entryId, personalHours } = await getTimeOffEntry(td);
        const globalHours = await getGlobalTimeOffHours(td.dataset.weekStart);

        // Show only personal hours in input; blank if no entry
        const input = document.createElement('input');
        input.type = 'text';
        input.value = entryId ? personalHours : '';
        input.className = 'form-control form-control-sm';
        input.style.width = '100%';
        td.appendChild(input);
        input.focus();
        activeInput = input;

        input.addEventListener('keydown', async e => {
            if (e.key === 'Escape') { input.remove(); activeInput = null; return; }
            if (e.key !== 'Enter') return;

            const val = input.value.trim();
            const personalValue = val ? parseFloat(val) : 0;
            const totalToSave = personalValue + globalHours;

            if (entryId) {
                if (personalValue === 0) {
                    // delete
                    const del = await safeFetchJSON('delete_timeoff_new.php', { 
                        method: 'POST', credentials: 'same-origin', headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ entry_id: entryId })
                    });
                    if (del.ok && del.data?.success) td.querySelector('.timeoff-corner')?.remove();
                } else {
                    // update
                    const update = await safeFetchJSON('update_timeoff_new.php', {
                        method: 'POST', credentials: 'same-origin', headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ entry_id: entryId, assigned_hours: totalToSave })
                    });
                    if (update.ok && update.data?.success) renderTimeOff(td, totalToSave, entryId);
                }
            } else if (personalValue > 0) {
                // add new
                const add = await safeFetchJSON('add_timeoff_new.php', {
                    method: 'POST', credentials: 'same-origin', headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        user_id: td.dataset.userId,
                        week_start: td.dataset.weekStart,
                        assigned_hours: totalToSave,
                        is_timeoff: 1
                    })
                });
                if (add.ok && add.data?.success && add.data.entry_id) renderTimeOff(td, totalToSave, add.data.entry_id);
            }

            input.remove();
            activeInput = null;
        });
    }

    document.addEventListener('contextmenu', e => {
        const td = e.target;
        if (td.tagName === 'TD' && td.classList.contains('addable')) {
            e.preventDefault();
            handleTimeOffInput(td);
        }
    });
})();
