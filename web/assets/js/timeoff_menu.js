(function() {
    if (!IS_ADMIN) return;

    let activeInput = null;

    // Render or update the time-off badge
    function renderTimeOff(td, timeOffValue, entryId) {
        console.log('Rendering time off:', { td, timeOffValue, entryId });
        td.style.position = 'relative';
        td.classList.add('timeoff-cell');

        let existingBadge = td.querySelector('.timeoff-corner');
        if (existingBadge) {
            console.log('Updating existing badge');
            existingBadge.textContent = timeOffValue;
            existingBadge.dataset.entryId = entryId;
        } else {
            console.log('Creating new badge');
            const div = document.createElement('div');
            div.className = 'timeoff-corner';
            div.dataset.entryId = entryId;
            div.textContent = timeOffValue;
            td.appendChild(div);
        }

        // Ensure the plus icon exists
        if (!td.querySelector('.bi-plus')) {
            console.log('Adding plus icon');
            const plus = document.createElement('i');
            plus.className = 'bi bi-plus text-muted';
            plus.style.cursor = 'pointer';
            td.appendChild(plus);
        }
    }

    async function safeFetchJSON(url, options) {
        try {
            console.log('Sending request to:', url, options.body);
            const resp = await fetch(url, options);
            const text = await resp.text();
            const data = JSON.parse(text);
            console.log('Server response:', data);
            return { ok: resp.ok, data };
        } catch (err) {
            console.error('Fetch or parse error:', err);
            return { ok: false, data: null, error: err.message };
        }
    }

    // Inline input for adding or editing time-off
    async function handleTimeOffInput(td) {
        if (activeInput) activeInput.remove();

        const existingBadge = td.querySelector('.timeoff-corner');
        const entryId = existingBadge ? existingBadge.dataset.entryId : null;
        const currentVal = existingBadge ? existingBadge.textContent : '';

        console.log('Handling time off input for cell:', td, { entryId, currentVal });

        const input = document.createElement('input');
        input.type = 'text';
        input.value = currentVal;
        input.className = 'form-control form-control-sm';
        input.style.width = '100%';
        td.appendChild(input);
        input.focus();
        activeInput = input;

        input.addEventListener('keydown', async e => {
            if (e.key !== 'Enter') return;
            const val = input.value.trim();
            if (!val) return;

            console.log(entryId ? 'Editing time off' : 'Adding new time off', val);

            try {
                if (entryId) {
                    // Update existing time off
                    const update = await safeFetchJSON('update_timeoff_new.php', {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ entry_id: entryId, assigned_hours: val })
                    });
                    if (update.ok && update.data?.success) {
                        console.log('Time off updated successfully');
                        renderTimeOff(td, val, entryId);
                    } else {
                        console.warn('Failed to update time off');
                        alert('Failed to update time off.');
                    }
                } else {
                    // Add new time off
                    const add = await safeFetchJSON('add_timeoff_new.php', {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ user_id: td.dataset.userId, week_start: td.dataset.weekStart, assigned_hours: val, is_timeoff: 1 })
                    });
                    if (add.ok && add.data?.success && add.data.entry_id) {
                        console.log('Time off added successfully');
                        renderTimeOff(td, val, add.data.entry_id);
                    } else {
                        console.warn('Failed to add time off');
                        alert('Failed to add time off.');
                    }
                }
            } catch (err) {
                console.error('Network error while saving time off:', err);
                alert('Network error while saving time off.');
            } finally {
                input.remove();
                activeInput = null;
            }
        });
    }

    // Right-click on an addable cell triggers input
    document.addEventListener('contextmenu', e => {
        const td = e.target;
        if (td.tagName === 'TD' && td.classList.contains('addable')) {
            e.preventDefault();
            console.log('Right-clicked cell:', td);
            handleTimeOffInput(td);
        }
    });
})();
