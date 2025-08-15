(function() {
    if (!IS_ADMIN) return;

    let activeInput = null;

    // Render cell with gray background and top-right assigned_hours
    function renderCell(td, timeOffValue = null, entryId = null) {
        td.innerHTML = '';
        td.classList.remove('timeoff-cell');
        td.style.position = 'relative';

        if (timeOffValue) {
            td.classList.add('timeoff-cell'); // gray highlight
            const div = document.createElement('div');
            div.className = 'timeoff-corner';
            div.dataset.entryId = entryId;
            div.textContent = timeOffValue;
            td.appendChild(div);
        }

        const plus = document.createElement('i');
        plus.className = 'bi bi-plus text-muted';
        plus.style.cursor = 'pointer';
        td.appendChild(plus);
    }

    async function safeFetchJSON(url, options) {
        try {
            const resp = await fetch(url, options);
            const text = await resp.text();
            return { ok: resp.ok, data: JSON.parse(text) };
        } catch (err) {
            console.error('Fetch or parse error:', err);
            return { ok: false, data: null, error: err.message };
        }
    }

    // Add or edit time off inline
    async function handleTimeOffInput(td, entryId = null) {
        if (activeInput) activeInput.remove();

        const currentVal = entryId ? td.querySelector('.timeoff-corner').textContent : '';
        const input = document.createElement('input');
        input.type = 'text';
        input.value = currentVal;
        input.className = 'form-control form-control-sm';
        input.style.width = '100%';
        td.innerHTML = '';
        td.appendChild(input);
        input.focus();
        activeInput = input;

        input.addEventListener('keydown', async e => {
            if (e.key !== 'Enter') return;
            const val = input.value.trim();
            if (!val) return;

            try {
                if (entryId) {
                    // Update existing time off
                    const update = await safeFetchJSON('update_timeoff_new.php', {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ entry_id: entryId, assigned_hours: val })
                    });
                    if (update.ok && update.data?.success) renderCell(td, val, entryId);
                    else alert('Failed to update time off.');
                } else {
                    // Add new time off
                    const add = await safeFetchJSON('add_timeoff_new.php', {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ user_id: td.dataset.userId, week_start: td.dataset.weekStart, assigned_hours: val, is_timeoff: 1 })
                    });
                    if (add.ok && add.data?.success && add.data.entry_id) {
                        renderCell(td, val, add.data.entry_id);
                    } else {
                        alert('Failed to add time off.');
                    }
                }
            } catch (err) {
                console.error(err);
                alert('Network error while saving time off.');
            } finally {
                activeInput = null;
            }
        });
    }

    // Right-click to add or edit time off
    document.addEventListener('contextmenu', e => {
        const target = e.target;
        if (target.tagName === 'TD' && target.classList.contains('addable')) {
            e.preventDefault();
            const timeOffDiv = target.querySelector('.timeoff-corner');
            const entryId = timeOffDiv ? timeOffDiv.dataset.entryId : null;
            handleTimeOffInput(target, entryId);
        }
    });
})();
