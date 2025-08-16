(async function() {
    if (!IS_ADMIN) return;

    let activeInput = null;

    function renderTimeOff(td, timeOffValue, entryId) {
        td.style.position = 'relative';
        td.classList.add('timeoff-cell');

        let existingBadge = td.querySelector('.timeoff-corner');
        if (existingBadge) {
            existingBadge.textContent = timeOffValue;
            existingBadge.dataset.entryId = entryId;
        } else {
            const div = document.createElement('div');
            div.className = 'timeoff-corner';
            div.dataset.entryId = entryId;
            div.textContent = timeOffValue;
            td.appendChild(div);
        }

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
        const user_id = td.dataset.userId;
        const week_start = td.dataset.weekStart;

        const resp = await safeFetchJSON('get_timeoff_entry.php', {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ user_id, week_start })
        });

        if (resp.ok && resp.data?.success) {
            return {
                entryId: resp.data.entry_id || null,
                totalAssigned: parseFloat(resp.data.assigned_hours || 0)
            };
        }
        return { entryId: null, totalAssigned: 0 };
    }

    async function getGlobalTimeOffHours(week_start) {
        const resp = await safeFetchJSON('check_global_timeoff.php', {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ week_start })
        });

        if (resp.ok && resp.data?.success) {
            return parseFloat(resp.data.assigned_hours || 0);
        }
        return 0;
    }

    async function handleTimeOffInput(td) {
        if (activeInput) activeInput.remove();

        const { entryId, totalAssigned } = await getTimeOffEntry(td);
        const globalHours = await getGlobalTimeOffHours(td.dataset.weekStart);

        let personalHours = entryId ? totalAssigned - globalHours : 0;
        if (personalHours < 0) personalHours = totalAssigned; // fallback

        // Blank if no personal entry
        const inputValue = entryId ? personalHours : '';

        const input = document.createElement('input');
        input.type = 'text';
        input.value = inputValue;
        input.className = 'form-control form-control-sm';
        input.style.width = '100%';
        td.appendChild(input);
        input.focus();
        activeInput = input;

        input.addEventListener('keydown', async e => {
            if (e.key === 'Escape') {
                input.remove();
                activeInput = null;
                return;
            }
            if (e.key !== 'Enter') return;

            const val = input.value.trim();
            if (!val) return;

            const personalValue = parseFloat(val);
            const totalHoursToSave = personalValue + globalHours;

            if (personalValue === 0 && entryId) {
                const del = await safeFetchJSON('delete_timeoff_new.php', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ entry_id: entryId })
                });
                if (del.ok && del.data?.success) {
                    td.querySelector('.timeoff-corner')?.remove();
                    if (!td.querySelector('.bi-plus')) {
                        const plusIcon = document.createElement('i');
                        plusIcon.className = 'bi bi-plus';
                        td.appendChild(plusIcon);
                    }
                }
            } else if (entryId) {
                const update = await safeFetchJSON('update_timeoff_new.php', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ entry_id: entryId, assigned_hours: totalHoursToSave })
                });
                if (update.ok && update.data?.success) renderTimeOff(td, totalHoursToSave, entryId);
            } else {
                const add = await safeFetchJSON('add_timeoff_new.php', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        user_id: td.dataset.userId,
                        week_start: td.dataset.weekStart,
                        assigned_hours: totalHoursToSave,
                        is_timeoff: 1
                    })
                });
                if (add.ok && add.data?.success && add.data.entry_id) {
                    renderTimeOff(td, totalHoursToSave, add.data.entry_id);
                }
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

    document.addEventListener('keydown', e => {
        if (e.key === 'Escape' && activeInput) {
            activeInput.remove();
            activeInput = null;
        }
    });
})();
