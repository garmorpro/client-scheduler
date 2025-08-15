(function() {
    if (!IS_ADMIN) return;

    let activeInput = null;

    function renderTimeOff(td, timeOffValue, entryId, allowPlus = false) {
        console.log('Rendering time off:', { td, timeOffValue, entryId, allowPlus });
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

        if (allowPlus && !td.querySelector('.bi-plus')) {
            const plus = document.createElement('i');
            plus.className = 'bi bi-plus text-muted';
            plus.style.cursor = 'pointer';
            td.appendChild(plus);
        }
    }

    async function safeFetchJSON(url, options) {
        try {
            const resp = await fetch(url, options);
            const text = await resp.text();
            const data = JSON.parse(text);
            return { ok: resp.ok, data };
        } catch (err) {
            console.error('Fetch or parse error:', err);
            return { ok: false, data: null, error: err.message };
        }
    }

    async function getTimeOffEntry(td) {
        const user_id = td.dataset.userId;
        const week_start = td.dataset.weekStart;

        const response = await safeFetchJSON('get_timeoff_entry.php', {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ user_id, week_start })
        });

        if (response.ok && response.data?.success) {
            return response.data.entry_id;
        } else {
            return null;
        }
    }

    async function handleTimeOffInput(td) {
        if (activeInput) activeInput.remove();

        const entryId = await getTimeOffEntry(td);
        const existingBadge = td.querySelector('.timeoff-corner');
        const currentVal = existingBadge ? existingBadge.textContent : '';

        const input = document.createElement('input');
        input.type = 'text';
        input.value = currentVal;
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

            try {
                if (entryId) {
                    const update = await safeFetchJSON('update_timeoff_new.php', {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ entry_id: entryId, assigned_hours: val })
                    });
                    if (update.ok && update.data?.success) {
                        renderTimeOff(td, val, entryId, false);
                    } else {
                        alert('Failed to update time off.');
                    }
                } else {
                    const nonTimeOffBadges = Array.from(td.children).filter(
                        el => el.classList.contains('entry-badge')
                    );

                    const add = await safeFetchJSON('add_timeoff_new.php', {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ user_id: td.dataset.userId, week_start: td.dataset.weekStart, assigned_hours: val, is_timeoff: 1 })
                    });
                    if (add.ok && add.data?.success && add.data.entry_id) {
                        renderTimeOff(td, val, add.data.entry_id, nonTimeOffBadges.length === 0);
                    } else {
                        alert('Failed to add time off.');
                    }
                }
            } catch (err) {
                console.error('Network error while saving time off:', err);
                alert('Network error while saving time off.');
            } finally {
                if (input) input.remove();
                activeInput = null;
            }
        });
    }

    document.addEventListener('contextmenu', e => {
        const td = e.target;
        if (td.tagName === 'TD' && td.classList.contains('addable')) {
            e.preventDefault();
            handleTimeOffInput(td);
        }
    });

    // Global Escape listener to close active input
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape' && activeInput) {
            activeInput.remove();
            activeInput = null;
        }
    });

})();
